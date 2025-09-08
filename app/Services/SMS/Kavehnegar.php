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
            $sender = "20006000412";		//This is the Sender number
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
        $t = '3438695A44513638314F654D4A71722F67493430396B4D616B50524B3153445457484B7670496C357A34303D';
        $url = 'https://api.kavenegar.com/v1/'.$t.'/verify/lookup.json';
        try {
            $d = [
                'receptor' => $receiver,
                'token' => $token,
                'template' => $template
            ];
            return  Http::get($url,$d);
            $this->log(1,$receiver,$token,json_encode($d));
        }catch (\Exception $exception)
        {
            $this->log(0,$receiver,$token,json_encode($exception));
        }
    }
    public function send_with_two_token($receiver,$token,$token2,$template)
    {
        $t = '3438695A44513638314F654D4A71722F67493430396B4D616B50524B3153445457484B7670496C357A34303D';
        $url = 'https://api.kavenegar.com/v1/'.$t.'/verify/lookup.json';
        try {
            $d = [
                'receptor' => $receiver,
                'token' => $token,
                'token2' => $token2,
                'template' => $template
            ];
            return Http::get($url,$d);
            $this->log(1,$receiver,$token,json_encode($d));
        }catch (\Exception $exception)
        {
            $this->log(0,$receiver,$token,json_encode($exception));
        }
    }
    public function send_with_three_token($receiver,$token,$token2,$token3,$template)
    {
        $t = '3438695A44513638314F654D4A71722F67493430396B4D616B50524B3153445457484B7670496C357A34303D';
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