<?php
namespace App\Services\Api;
use App\Models\Etiket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class Tahesab{
    public $api_endpoint;
    public $api_key;
    public $api_db;

    public function __construct()
    {
        $this->api_endpoint = setting('api_endpoint');
        $this->api_key = setting('api_key');
        $this->api_db = setting('api_db');
    }

    /**
     * Send API request
     *
     * @param string $method - HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param mixed $body - Raw body (usually array or JSON string)
     * @return \Illuminate\Http\Client\Response
     */
    public function makeRequest(string $method, $body)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'DBName' => $this->api_db,
        ];

        $client = Http::withHeaders($headers)->withoutVerifying(); // <- disables SSL verification

        return $client->send($method, $this->api_endpoint, [
            'body' => is_string($body) ? $body : json_encode($body),
        ]);
    }

    public function getEtikets($from = 1, $to = 10)
    {
        $params = [
            'DoListEtiket' => [$from, $to]
        ];

        $response = $this->makeRequest('GET', $params);
        if ($response->successful()) {
//            return $response->body();
            $response = $response->json();
            if(isset($response['Error'])){
                return [
                    'error' => true,
                    'status' => 404,
                    'message' => "یافت نشد"
                ];
            }
            return $response;
        }

        // Optionally handle errors
        return [
            'error' => true,
            'status' => $response->status(),
            'message' => $response->body()
        ];
    }
    public function getEtiketsAndStore($from = 1 , $to = 10)
    {
        $etitkets = $this->getEtikets($from, $to);

        if (!is_array($etitkets)) {
            echo "Invalid response from getEtikets($from, $to): ";
            print_r($etitkets);
            return;
        }

        // Optional: check for error key
        if (isset($etitkets['error'])) {
            echo "Error in response from getEtikets($from, $to): " . $etitkets['error'] . "\n";
            return;
        }

        foreach ($etitkets as $etiket) {
            if (!is_array($etiket)) {
                echo "Error in response from getEtikets($from, $to)\n";
                echo "Invalid etiket entry: ";
                print_r($etiket);
                continue;
            }

            $this->updateOrCreateEtiket($etiket);
        }
    }


    public function GetEtiketTableInfo()
    {
        $params = [
            'GetEtiketTableInfo' => []
        ];

        $response = $this->makeRequest('GET', $params);

        if ($response->successful()) {
            return $response->json(); // return decoded body
        }

        // Optionally handle errors
        return [
            'error' => true,
            'status' => $response->status(),
            'message' => $response->body()
        ];
    }

    public function getAllTicketsAndStoreOrUpdate()
    {
        echo "Fetching Etiket table info...\n";
        $GetEtiketTableInfo = $this->GetEtiketTableInfo();

        if (!isset($GetEtiketTableInfo['error']) &&
            $GetEtiketTableInfo['CountALL'] &&
            $GetEtiketTableInfo['MinCode'] &&
            $GetEtiketTableInfo['MaxCode']) {

            $minCode = $GetEtiketTableInfo['MinCode'];
            $maxCode = $GetEtiketTableInfo['MaxCode'] - 1;
            $currentMax = 500;

            echo "Starting to fetch etikets from code $minCode to $maxCode\n";

            while ($minCode < $maxCode) {
                echo "Fetching etikets from $minCode to $currentMax\n";
                $this->getEtiketsAndStore($minCode, $currentMax);
                echo "done!\n";
                sleep(1);
                $minCode = $currentMax;
                $currentMax = $currentMax + 500;

                if ($currentMax > $maxCode) {
                    $currentMax = $maxCode;
                }
            }

            echo "Finished fetching all etikets.\n";
        } else {
            echo "Etiket table info fetch failed or missing required data.\n";
        }
    }

    public function getUpdatedEtikets($from, $to )
    {
        $params = [
            'DoListGetUpdatedEtiket' => [$from, $to]
        ];

        $response = $this->makeRequest('GET', $params);

        if ($response->successful()) {
            $response = $response->json();
            if(isset($response['Error'])){
                return [
                    'error' => true,
                    'status' => 404,
                    'message' => "یافت نشد"
                ];
            }
            return $response;
        }

        // Optionally handle errors
        return [
            'error' => true,
            'status' => $response->status(),
            'message' => $response->body()
        ];
    }


    public function getUpdatedEtiketsAndStore()
    {
        $from = Jalalian::forge(now()->addDays(-1))->format('Y-m-d');
        $to = Jalalian::forge(now()->addDays(1))->format('Y-m-d');
        $etitkets = $this->getUpdatedEtikets($from, $to);

        if (!is_array($etitkets)) {
            echo "Invalid response from getEtikets($from, $to): ";
            print_r($etitkets);
            return;
        }

        // Optional: check for error key
        if (isset($etitkets['error'])) {
            echo "Error in response from getEtikets($from, $to): " . $etitkets['error'] . "\n";
            return;
        }

        foreach ($etitkets as $etiket) {
            if (!is_array($etiket)) {
                echo "Invalid etiket entry: ";
                print_r($etiket);
                continue;
            }

            $this->updateOrCreateEtiket($etiket);
        }
    }

    public function updateOrCreateEtiket($etiket)
    {
        $_etiket = Etiket::query()->updateOrCreate(
            [
                'code' => $etiket['Code']
            ],
            [
                'code' => $etiket['Code'],
                'name' => $etiket['Name'],
                'weight' => $etiket['Vazn'],
                'price' => $etiket['OnlinePrice'],
                'ojrat' => $etiket['DarsadVazn'],
                'is_mojood' => $etiket['IsMojood'],
                'darsad_kharid' => $etiket['DarsadVaznMaye'],
                'mazaneh' => $etiket['Mazaneh'],
            ]
        );
        $_etiket->touch();
    }

    public function DoNewSanadBuySaleEtiket($order_number, $etiket_code, $mazaneh, $price, $full_name  )
    {
        $year = Jalalian::now()->format('Y');
        $month = Jalalian::now()->format('m');
        $day = Jalalian::now()->format('d');
        Log::info('DoNewSanadBuySaleEtiket:\n'.json_encode( [
            1,
            3037,
            $order_number,
            1,
            intval($year),
            intval($month),
            intval($day),
            0,
            0,
            0,
            "",
            1,
            intval($mazaneh),
            0,
            intval($price),
            1,
            0,
            0,
            0,
            0,
            0,
            0,
            "",
            0,
            intval($etiket_code),
            $full_name,
            "Order-".$order_number,
            0,
            1
        ]));
        $params = [
            'DoNewSanadBuySaleEtiket' => [
                1,
                3037,
                $order_number,
                1,
                intval($year),
                intval($month),
                intval($day),
                0,
                0,
                0,
                "",
                1,
                intval($mazaneh),
                0,
                intval($price),
                1,
                0,
                0,
                0,
                0,
                0,
                0,
                "",
                0,
                intval($etiket_code),
                $full_name,
                intval($order_number),
                0,
                1
            ]
        ];
        $response = $this->makeRequest('GET', $params);

        if ($response->successful()) {
            $response = $response->json();
            if(isset($response['Error'])){
                return [
                    'error' => true,
                    'status' => 404,
                    'message' => "یافت نشد"
                ];
            }
            return $response;
        }

        // Optionally handle errors
        return [
            'error' => true,
            'status' => $response->status(),
            'message' => $response->body()
        ];
    }
    public function DoNewSanadTalabBedehi( $order_number , $IsBedehiOrTalab_0_1, $Mablagh, $HavalehBe_Mcode, $MultiRadif  )
    {
        $year = Jalalian::now()->format('Y');
        $month = Jalalian::now()->format('m');
        $day = Jalalian::now()->format('d');
        $params = [
            'DoNewSanadTalabBedehi' => [
                1,
                3037,
                $order_number,
                -1,
                intval($year),
                intval($month),
                intval($day),
                $IsBedehiOrTalab_0_1,
                $Mablagh,
                "Sharh",
                "Baabat",
                0,
                intval($order_number),
                intval($HavalehBe_Mcode),
                intval($MultiRadif)
            ]
        ];
//        return $params;
        $response = $this->makeRequest('GET', $params);

        if ($response->successful()) {
            $response = $response->json();
            if(isset($response['Error'])){
                return [
                    'error' => true,
                    'status' => 404,
                    'message' => "یافت نشد"
                ];
            }
            return $response;
        }

        // Optionally handle errors
        return [
            'error' => true,
            'status' => $response->status(),
            'message' => $response->body()
        ];
    }
    public function DoDeleteSanad( $order_number  )
    {
        $params = [
            'DoDeleteSanad' => [
                strval($order_number)
            ]
        ];
//        return $params;
        $response = $this->makeRequest('GET', $params);

        if ($response->successful()) {
            $response = $response->json();
            if(isset($response['Error'])){
                return [
                    'error' => true,
                    'status' => 404,
                    'message' => "یافت نشد"
                ];
            }
            return $response;
        }

        // Optionally handle errors
        return [
            'error' => true,
            'status' => $response->status(),
            'message' => $response->body()
        ];
    }
}