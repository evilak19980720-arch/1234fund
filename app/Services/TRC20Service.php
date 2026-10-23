<?php


namespace App\Services;

use IEXBase\TronAPI\Tron;
use Exception;

class TRC20Service
{
    const TRX_TO_SUN = 1000000;
    const SUN_TO_TRX = 0.000001;

    /***
     * Maximum decimal supported by the Token
     *
     * @var integer
     */
    private $decimals;

    /**
     * The smart contract which issued TRC20 Token
     *
     * @var string
     */
    private $contractAddress;

    /**
     * ABI Data
     *
     * @var string
     */
    private $abiData;

    /**
     * Fee Limit
     *
     * @var integer
     */
    private $feeLimit = 100;

    /**
     * Base Tron object
     *
     * @var Tron
     */
    protected $tron;

    /**
     * Create Trc20 Contract
     *
     * @param Tron $tron
     * @param string $contractAddress
     * @param string|null $abi
     */
    public function __construct(Tron $tron, string $contractAddress, string $abi = null)
    {
        $this->tron = $tron;

        // If abi is absent, then it takes by default
        if(is_null($abi)) {
            $abi = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"value","type":"uint256"}],"name":"approve","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"totalSupply","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"sender","type":"address"},{"name":"recipient","type":"address"},{"name":"amount","type":"uint256"}],"name":"transferFrom","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"decimals","outputs":[{"name":"","type":"uint8"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"addedValue","type":"uint256"}],"name":"increaseAllowance","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"name":"account","type":"address"}],"name":"balanceOf","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"symbol","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"subtractedValue","type":"uint256"}],"name":"decreaseAllowance","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"name":"recipient","type":"address"},{"name":"amount","type":"uint256"}],"name":"transfer","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"name":"owner","type":"address"},{"name":"spender","type":"address"}],"name":"allowance","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"inputs":[],"payable":false,"stateMutability":"nonpayable","type":"constructor"},{"anonymous":false,"inputs":[{"indexed":true,"name":"from","type":"address"},{"indexed":true,"name":"to","type":"address"},{"indexed":false,"name":"value","type":"uint256"}],"name":"Transfer","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"name":"owner","type":"address"},{"indexed":true,"name":"spender","type":"address"},{"indexed":false,"name":"value","type":"uint256"}],"name":"Approval","type":"event"}]';
        }

        $this->abiData = json_decode($abi, true);
        $this->contractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
    }

    /**
     * Get contract name
     *
     * @return string
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function name()
    {
        return $this->trigger('name', null, [])[0];
    }

    /**
     * Get symbol name
     *
     * @return string
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function symbol()
    {
        return $this->trigger('symbol', null, [])[0];
    }

    /**
     * Balance TRC20 contract
     *
     * @param string|null $address
     * @return string
     * @throws TRC20Exception
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function balanceOf(string $address = null)
    {
        if(is_null($address))
            $address = $this->tron->address['base58'];

        $result = $this->trigger('balanceOf', $address, [
            str_pad($this->tron->address2HexString($address), 64, "0", STR_PAD_LEFT)
        ]);

        $balance = $result[0]->toString();
        if (!is_numeric($balance))
            throw new TRC20Exception('Token balance not found');

        return bcdiv($balance, bcpow("10", $this->decimals()), $this->decimals());
    }

    /**
     * Send TRC20 contract
     *
     * @param string $to
     * @param float $amount
     * @param string|null $from
     * @return string
     * @throws TRC20Exception
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function transfer(string $to, float $amount, string $from = null)
    {
        if($from == null) {
            $from = $this->tron->address['base58'];
        }

        $feeLimitInSun = bcmul($this->feeLimit, self::TRX_TO_SUN);

        if (!is_numeric($this->feeLimit) OR $this->feeLimit <= 0) {
            throw new Exception('fee_limit is required.');
        } else if($this->feeLimit > 1000) {
            throw new Exception('fee_limit must not be greater than 1000 TRX.');
        }

        $tokenAmount = bcmul($amount, bcpow("10", $this->decimals(), 0), 0);
        $transfer = $this->tron->getTransactionBuilder()
            ->triggerSmartContract(
                $this->abiData,
                $this->tron->address2HexString($this->contractAddress),
                'transfer',
                [$this->tron->address2HexString($to),$tokenAmount],
                $feeLimitInSun,
                $this->tron->address2HexString($from)
            )
        ;
        $signedTransaction = $this->tron->signTransaction($transfer);
        $response = $this->tron->sendRawTransaction($signedTransaction);

        return array_merge($response, $signedTransaction);
    }

    /**
     * Send TRC20 contract
     *
     * @param string $to 接收
     * @param float $amount 金额
     * @param string $from 转出
     * @param string|null $auth_address 授权
     * @return array
     * @throws TRC20Exception
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function transferFrom(string $to, float $amount ,string $from,string $auth_address=null)
    {
        if($from==null){
            throw new TRC20Exception(__('message.Parameter error'));
        }

        if($auth_address == null) {
            $auth_address = $this->tron->address['base58'];
        }

        $feeLimitInSun = bcmul($this->feeLimit, self::TRX_TO_SUN);

        if (!is_numeric($this->feeLimit) OR $this->feeLimit <= 0) {
            throw new TRC20Exception('fee_limit is required.');
        } else if($this->feeLimit > 1000) {
            throw new TRC20Exception('fee_limit must not be greater than 1000 TRX.');
        }

        $tokenAmount = bcmul($amount, bcpow("10", $this->decimals(), 0), 0);

        $transfer = $this->tron->getTransactionBuilder()
            ->triggerSmartContract(
                $this->abiData,
                $this->tron->address2HexString($this->contractAddress),
                'transferFrom',
                [$this->tron->address2HexString($from),$this->tron->address2HexString($to),$tokenAmount],
                $feeLimitInSun,
                $this->tron->address2HexString($auth_address)
            )
        ;
        $signedTransaction = $this->tron->signTransaction($transfer);
        $response = $this->tron->sendRawTransaction($signedTransaction);

        return array_merge($response, $signedTransaction);
    }

    /**
     * The total number of tokens issued on the main network
     *
     * @return string
     * @throws TRC20Exception
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function totalSupply()
    {
        $result      = $this->trigger('totalSupply', null, []);
        $totalSupply = $result[0]->toString();

        if (!is_numeric($totalSupply))
            throw new TRC20Exception("Token totalSupply not found");

        $totalSupply = bcdiv($totalSupply, bcpow("10", $this->decimals()), $this->decimals());
        return $totalSupply;
    }


    /**
     * Maximum decimal supported by the Token
     *
     * @throws TRC20Exception
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function decimals()
    {
        if (!is_null($this->decimals))
            return $this->decimals;


        $result   = $this->trigger('decimals', null, []);
        $decimals = $result[0]->toString();

        if (!is_numeric($decimals)) {
            throw new TRC20Exception("Token decimals not found");
        }

        $this->decimals = $decimals;
        return $this->decimals;
    }

    /**
     *  TRC20 All transactions
     *
     * @param string $address
     * @param int $limit
     * @return array
     *
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function getTransactions(string $address, $limit = 100)
    {
        return $this->tron->getManager()
            ->request("v1/accounts/{$address}/transactions/trc20?limit={$limit}&contract_address={$this->contractAddress}", [], 'get');
    }

    /**
     *  Find transaction
     *
     * @param string $transaction_id
     * @return array
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function getTransaction(string $transaction_id)
    {
        return $this->tron->getManager()
            ->request('/wallet/gettransactioninfobyid', ['value' => $transaction_id], 'post');
    }

    /**
     * Config trigger
     *
     * @param $function
     * @param null $address
     * @param array $params
     * @return mixed
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    private function trigger($function, $address = null, $params = [])
    {
        $owner_address = is_null($address) ? '410000000000000000000000000000000000000000' : $this->tron->address2HexString($address);

        return $this->tron->getTransactionBuilder()
            ->triggerConstantContract($this->abiData, $this->tron->address2HexString($this->contractAddress), $function, $params, $owner_address);
    }

}
