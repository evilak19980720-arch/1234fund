<?php

namespace App\Services\Sms;


use Doctrine\DBAL\Configuration;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;

class XqtxhnService
{

    /**
     * api地址
     * @var string
     */
    protected string $api = 'http://api.wftqm.com/api/sms/mtsend';

    /**
     * key
     * @var string
     */
    protected string $account;

    /**
     * 秘钥
     * @var string
     */
    protected string $password;


    public function __construct(array $config)
    {
        $this->account = $config['account'];
        $this->password = $config['password'];
    }


    /**
     * 发送短信验证码
     * @param string $to
     * @param string $code
     * @return bool
     * @throws Exception
     */
    public function send(string $to, string $code): bool
    {
        $param = [
            'appkey' => $this->account,
            'secretkey' => $this->password,
            'phone' => $to,
            'content' => "SMS verification code is {$code}, valid for 5 minutes, please don't tell others."
        ];
        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded'
        ])->asForm()->post($this->api, $param);
        $result = json_decode($response->body(), true);//message
        if ($result['code']!=0) {
            throw  new \Exception($result['result']);
        }
        return true;
    }
}
