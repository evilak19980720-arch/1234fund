<?php

namespace App\Services;

use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;

class TronService
{

    private $tron;
    private $contractAddress;

    /**
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function __construct()
    {
        $url                   = 'https://api.trongrid.io';
        $this->contractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
        $fullNode              = new HttpProvider($url);
        $solidityNode          = new HttpProvider($url);
        $eventServer           = new HttpProvider($url);
        $this->tron            = new Tron($fullNode, $solidityNode, $eventServer);

    }

    /**
     * 查询余额
     *
     * @param string $address 地址
     *
     * @return string
     * @throws \IEXBase\TronAPI\Exception\TRC20Exception
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function balance(string $address): string
    {

        //$contract = $this->tron->contract($this->contractAddress);
        $contract = new TRC20Service($this->tron, $this->contractAddress);
        // 查询余额
        return $contract->balanceOf($address);
    }

    public function balanceTrx(string $address)
    {
        $contract = $this->tron->getBalance($address,true);
        return $contract;
    }

    /**
     *
     * 授权转账
     *
     * @param string $from
     * @param string $to
     * @param        $number
     * @param string $auth_address
     *
     * @return false|mixed
     * @throws \IEXBase\TronAPI\Exception\TRC20Exception
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function transferFrom(string $from, string $to, $number, string $auth_address,string $private)
    {
        //$private = env('TRC_PRIVATE');
        $this->tron->setAddress($auth_address);// 设置授权地址和
        $this->tron->setPrivateKey($private);
        info('SQZZ:'.$auth_address.'|'.$private.'|'.$from);
        $contract = new TRC20Service($this->tron, $this->contractAddress);
        //
        $transaction = $contract->transferFrom($to, $number, $from);
        if (isset($transaction['result']) && $transaction['result']) {
            return $transaction['txid'];
        }

        return false;
    }


    /**
     *
     * 转账
     *
     * @param string $from
     * @param string $to
     * @param        $number
     * @param string $auth_address
     *
     * @return false|mixed
     * @throws \IEXBase\TronAPI\Exception\TRC20Exception
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function transfer(string $to,float $amount ,string $from, string $private)
    {
        //$private = env('TRC_PRIVATE');
        $this->tron->setAddress($from);// 设置授权地址和
        $this->tron->setPrivateKey($private);
        //info('TXZZ:'.$from.'|'.$private);
        $contract = new TRC20Service($this->tron, $this->contractAddress);
        //
        $transaction = $contract->transfer($to, $amount);
        if (isset($transaction['result']) && $transaction['result']) {
            return $transaction['txid'];
        }
        return false;
    }


    /**
     *
     * 转账
     *
     * @param string $from
     * @param string $to
     * @param        $number
     * @param string $auth_address
     *
     * @return false|mixed
     * @throws \IEXBase\TronAPI\Exception\TRC20Exception
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function transactionReceipt(string $ttxid)
    {
        $contract = new TRC20Service($this->tron, $this->contractAddress);
        //
        $transaction = $contract->getTransaction($ttxid);
        return $transaction;
    }

    public function getTransactions(string $address)
    {
        $contract = new TRC20Service($this->tron, $this->contractAddress);
        //
        $transaction = $contract->getTransactions($address);
        return $transaction;
    }


    public function getTrxTransactionsToAddress(string $address)
    {
        return $this->tron->getManager()
            ->request("v1/accounts/{$address}/transactions?limit=100", [], 'get');
        //$contract = $this->tron->getTransactionsToAddress($address);
        //return $contract;
    }


    /*
     * TRX查询
     * */
    public function getTrxTransaction($transactionID){

        //getTransactionInfo
        return $this->tron->getTransaction($transactionID);
    }

    /*
     * TRX交易
     * */
    public function sendTrxTransaction($from , $private, $to, $amount){

        $this->tron->setAddress($from);// 设置授权地址和
        $this->tron->setPrivateKey($private);
        $re =  $this->tron->sendTransaction($to, $amount);

        return $re;
    }



    /**
     * 设置合约地址
     *
     * @param string $contractAddress
     *
     * @return $this
     */
    public function setContractAddress(string $contractAddress): TronService
    {
        $this->contractAddress = $contractAddress;

        return $this;
    }


    //createAccount
    public function createAccount()
    {
        $add = $this->tron->generateAddress();

        return $add;
    }

    public function getAddress($address){
        $this->tron->setAddress($address);
        return $this->tron->getAddress();
    }

    public function validateAddress($address){
        $re = $this->tron->validateAddress($address);
        return $re;
    }


}
