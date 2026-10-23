<?php

namespace App\Services\Sms;


use Doctrine\DBAL\Configuration;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;

class KmiCloudSmsService
{

    /**
     * api地址
     * @var string
     */
    protected string $api = 'http://api.kmicloud.com/sms/send/v1/otp';

    /**
     * key
     * @var string
     */
    protected $access_key;

    /**
     * 秘钥
     * @var string
     */
    protected $secret_key;

    /**
     * 有效期
     * @var string
     */
    protected $valid_minutes;

    /**
     * @var string
     */
    protected string $from;


    /**
     * 模板
     * @var string
     */
    protected string $message;

    public function __construct(array $config)
    {
        $this->access_key = $config['access_key'];
        $this->secret_key = $config['secret_key'];
        $this->valid_minutes = $config['valid_minutes'];
        $this->from = $config['from'];
        $this->message = $config['message'];
    }


    /**
     * 发送短信验证码
     * @param string $to
     * @param string $code
     * @return bool
     * @throws \Exception
     */
    public function send(string $to, string $code)
    {
        $param = [
            'accessKey' => $this->access_key,
            'secretKey' => $this->secret_key,
            'from' => $this->from,
            'to' => '00' . $to,
            'message' => sprintf($this->message, $code),
        ];
        $response = Http::post($this->api, $param);
        $result = json_decode($response->body(), true);//message
        if (!$result['success']) {
            throw  new \Exception($result['message']);
        }
        return true;
    }
}
