<?php
namespace App\Services\SMS;
use App\Interfaces\SmsInterface;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Kavenegar;
class Kavehnegar {


    public function send($receiver, $message)
    {
        try{
            $sender = "10009000700011";		//This is the Sender number
            $receptor = array($receiver);			//Receptors numbers

            $result = Kavenegar::Send($sender,$receptor,$message);
            if($result){
                foreach($result as $r){
                    $this->log(1,$receiver,$message,$r);
                }
            }
        }
        catch(\Kavenegar\Exceptions\ApiException $e){
            // در صورتی که خروجی وب سرویس 200 نباشد این خطا رخ می دهد
            $result = $e->errorMessage();
            $this->log(0,$receiver,$message,$result);
        }
        catch(\Kavenegar\Exceptions\HttpException $e){
            // در زمانی که مشکلی در برقرای ارتباط با وب سرویس وجود داشته باشد این خطا رخ می دهد
            $result = $e->errorMessage();
            $this->log(0,$receiver,$message,$result);
        }
    }

    public function send_with_pattern($receiver,$token,$template)
    {
        $t = '38357570726F49527A66306C4550426A7042714833394C4E32366B2F7A37782B';
        $url = 'https://api.kavenegar.com/v1/'.$t.'/verify/lookup.json';
        try {
            $d = [
                'receptor' => $receiver,
                'token' => $token,
                'template' => $template
            ];
            $post = Http::get($url,$d);
            $this->log(1,$receiver,$token,json_encode($d));
        }catch (\Exception $exception)
        {
            $this->log(0,$receiver,$token,json_encode($exception));
        }
    }
    public function send_with_three_token($receiver,$token,$token2,$token3,$template)
    {
        $t = '38357570726F49527A66306C4550426A7042714833394C4E32366B2F7A37782B';
        $url = 'https://api.kavenegar.com/v1/'.$t.'/verify/lookup.json';
        try {
            $d = [
                'receptor' => $receiver,
                'token' => $token,
                'token2' => $token2,
                'token3' => $token3,
                'template' => $template
            ];
            $post = Http::get($url,$d);
            $this->log(1,$receiver,$token,json_encode($d));
        }catch (\Exception $exception)
        {
            $this->log(0,$receiver,$token,json_encode($exception));
        }
    }

    function log($success, $receiver, $message, $result)
    {
        SmsLog::create([
            'success' => $success,
            'receiver' => $receiver ,
            'message' => $message,
            'result' => json_encode($result)
        ]);
    }
}