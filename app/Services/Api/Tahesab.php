<?php
namespace App\Services\Api;
use App\Models\Etiket;
use Illuminate\Support\Facades\Http;

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
            return $response->json(); // return decoded body
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
        if(!isset($etitkets['error']) ){
            foreach ($etitkets as $etiket) {
                Etiket::query()->updateOrCreate(
                    [
                    'code' => $etiket['Code']
                    ],[
                    'code' => $etiket['Code'],
                    'name' => $etiket['Name'],
                    'weight' => $etiket['Vazn'],
                    'price' => $etiket['OnlinePrice'],
                    'ojrat' => $etiket['DarsadVazn'],
                    'is_mojood' => $etiket['IsMojood'],
                ]);
            }
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
            $maxCode = $GetEtiketTableInfo['MaxCode'];
            $currentMax = 500;

            echo "Starting to fetch etikets from code $minCode to $maxCode\n";

            while (true) {
                echo "Fetching etikets from $minCode to $currentMax\n";
                $this->getEtiketsAndStore($minCode, $currentMax);
                echo "done!\n";
                $minCode = $currentMax;
                $currentMax = $currentMax + 500;

                if ($currentMax > $maxCode) {
                    $currentMax = $maxCode;
                }
                if(Etiket::query()->where('code', $maxCode)->exists()) {
                    break;
                }
            }

            echo "Finished fetching all etikets.\n";
        } else {
            echo "Etiket table info fetch failed or missing required data.\n";
        }
    }

}