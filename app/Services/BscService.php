<?php

namespace App\Services;

use Binance\Formatter;
use Binance\PEMHelper;
use Binance\Wallet;
use Binance\NodeApi;
use Binance\Bnb;
use Binance\BEP20;
use Binance\Utils;
use Illuminate\Support\Facades\Http;
use Web3p\EthereumTx\Transaction;

class BscService
{
    public $api;
    public $bnb;
    public $bep20;

    public function __construct()
    {
        $uri = 'https://bsc-dataseed1.binance.org';// Mainnet
        //$uri = 'https://bsc-mainnet.nodereal.io/v1/' . config('services.nodereal.api_key'); 备用的
        $this->api = new NodeApi($uri);
        $this->bnb = new Bnb($this->api);

        $config = [
            'contract_address' => '0x55d398326f99059fF775485246999027B3197955',// USDT BEP20
            'decimals' => 18,
        ];
        $this->bep20 = new BEP20($this->api, $config);
    }


    //createAccount
    public function createAccount()
    {
        $wallet = new Wallet();
        // 生成私钥创建账户
        $add = $wallet->newAccountByPrivateKey();

        /*
         * array (
  'key' => '私钥（64 位十六进制）',
  'address' => '0x 开头的地址',
)
         * */
        return $add;
    }

    //查BSC20余额
    public function balance(string $address): string
    {
        return $this->bep20->balance($address);
    }

    /*查询交易列表
{
  "status": "1",
  "message": "OK",
  "result": [
    {
      "blockNumber": "54678472",
      "timeStamp": "1753006763",
      "hash": "0x776a073d9b5c0dfce8198e5d02e57d91b0b25954a8835cdac363aff81b0294ad",
      "nonce": "26233863",
      "blockHash": "0x8e27d8aa165d2e67130432d6c4b292aa59cc00775b1b951b52e7d4bd89c1ab2e",
      "from": "0xeb2d2f1b8c558a40207669291fda468e50c8a0bb",
      "contractAddress": "0x55d398326f99059ff775485246999027b3197955",
      "to": "0x312aca25ce7316340461176383c094acda772664",
      "value": "10000000000000000000",
      "tokenName": "Binance-Peg BSC-USD",
      "tokenSymbol": "BSC-USD",
      "tokenDecimal": "18",
      "transactionIndex": "65",
      "gas": "207128",
      "gasPrice": "200000000",
      "gasUsed": "51603",
      "cumulativeGasUsed": "9883346",
      "input": "deprecated",
      "methodId": "0xa9059cbb",
      "functionName": "transfer(address dst, uint256 amount)",
      "confirmations": "93092"
    }
  ]
}
     */
    public function getTransactions(string $address , $limit = 10)
    {
        $contractAddress = '0x55d398326f99059fF775485246999027B3197955'; // USDT BEP20

        // 使用 NodeReal 节点通过 JSON-RPC 查询 Transfer 事件
        $nodeRealApiKey = config('services.nodereal.api_key');
        $nodeRealUrl = "https://bsc-mainnet.nodereal.io/v1/{$nodeRealApiKey}";

        // Transfer 事件签名
        $transferEventSignature = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';

        // 获取当前区块号
        $currentBlockHex = $this->api->blockNumber();
        $currentBlock = is_numeric($currentBlockHex) ? $currentBlockHex : hexdec($currentBlockHex);
        $fromBlock = max(0, $currentBlock - 10000); // 查询最近 10000 个区块

        // 构建地址 topic (32字节，左补0)
        $addressWithoutPrefix = strtolower(substr($address, 2));
        $addressTopic = '0x' . str_pad($addressWithoutPrefix, 64, '0', STR_PAD_LEFT);

        // 构建 JSON-RPC 请求
        $rpcRequest = [
            'jsonrpc' => '2.0',
            'method' => 'eth_getLogs',
            'params' => [[
                'fromBlock' => Utils::toHex($fromBlock, true),
                // 只查已有 15 个确认的区块，避免把还可能回滚的交易入账
                'toBlock' => Utils::toHex(max(0, $currentBlock - 15), true),
                'address' => $contractAddress,
                'topics' => [
                    $transferEventSignature,
                    null, // from 地址 (null 表示匹配所有发送者)
                    $addressTopic // to 地址 (匹配接收者)
                ]
            ]],
            'id' => 1
        ];

        try {
            // 使用 Laravel Http 库发送 JSON-RPC POST 请求
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->asJson()
                ->post($nodeRealUrl, $rpcRequest);

            if ($response->successful()) {
                $rpcResponse = $response->json();

                if (isset($rpcResponse['result']) && is_array($rpcResponse['result'])) {
                    // 日志按区块从旧到新排列，取最新的 $limit 条
                    $logs = array_slice($rpcResponse['result'], -$limit);

                    // 转换为类似 BSCScan 的格式
                    $result = [];
                    foreach ($logs as $log) {
                        if (isset($log['blockNumber']) && isset($log['transactionHash'])) {
                            $fromAddress = isset($log['topics'][1]) ? '0x' . substr($log['topics'][1], 26) : '';
                            $toAddress = isset($log['topics'][2]) ? '0x' . substr($log['topics'][2], 26) : '';

                            // 将十六进制的 data 转换为十进制的 value（字符串格式）
                            // Transfer 事件的 data 字段包含转账金额（uint256，十六进制）
                            $dataHex = $log['data'] ?? '0x0';
                            $hexValue = ltrim($dataHex, '0x');

                            // 转换为十进制字符串（处理大整数）
                            if (extension_loaded('gmp')) {
                                $valueDecimal = gmp_strval(gmp_init($hexValue, 16));
                            } else {
                                // 回退方案：使用 bcmath（如果可用）或简化处理
                                $valueDecimal = '0';
                                if (function_exists('bcadd')) {
                                    $len = strlen($hexValue);
                                    for ($i = 0; $i < $len; $i++) {
                                        $digit = hexdec($hexValue[$i]);
                                        $valueDecimal = bcadd(bcmul($valueDecimal, '16', 0), (string)$digit, 0);
                                    }
                                } else {
                                    // 最后的回退：直接使用十六进制（不推荐，但至少不会报错）
                                    $valueDecimal = $dataHex;
                                }
                            }

                            $result[] = [
                                'blockNumber' => $log['blockNumber'],
                                'hash' => $log['transactionHash'],
                                'from' => strtolower($fromAddress),
                                'to' => strtolower($toAddress),
                                'value' => $valueDecimal, // 十进制字符串格式，如 "10000000000000000000"
                                'contractAddress' => strtolower($contractAddress), // 统一转换为小写
                                'tokenDecimal' => '18' // USDT BEP20 的小数位数是 18
                            ];
                        }
                    }

                    if (!empty($result)) {
                        return $result;
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('BSC 交易查询失败', [
                'error' => $e->getMessage(),
                'address' => $address
            ]);
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
    public function transfer(string $to, float $amount ,string $from, string $private)
    {
        //return $this->bep20->transfer($private, $to, $amount);
        //上面的转账方法因为手续费原因报错 下面重写

        $from = PEMHelper::privateKeyToAddress($private);
        $nonce = $this->api->getNonce($from);
        $params = [
            'nonce' => "$nonce",
            'from' => $from,
            'to' => "0x55d398326f99059fF775485246999027B3197955",
            'gas' => '0x3d090',
            'gasPrice' => "0x5f5e100",
            'value' => Utils::NONE,
            'chainId' => "56",
        ];
        $val = Utils::toMinUnitByDecimals("$amount", 18);

        $method = 'transfer(address,uint256)';
        $formatMethod = Formatter::toMethodFormat($method);
        $formatAddress = Formatter::toAddressFormat($to);
        $formatInteger = Formatter::toIntegerFormat($val);

        $params['data'] = "0x{$formatMethod}{$formatAddress}{$formatInteger}";
        $transaction = new Transaction($params);

        $raw = $transaction->sign($private);
        $res = $this->api->sendRawTransaction('0x' . $raw);
        return $res;



    }


    /**
     *
     * 转账查询
     */
    public function transactionReceipt(string $txHash)
    {
        return $this->bep20->getTransactionReceipt($txHash);
    }

    public function transferbnb(string $to, float $amount ,string $from, string $private){
        $from = PEMHelper::privateKeyToAddress($private);
        $nonce = $this->api->getNonce($from);

        $eth = Utils::toWei("$amount", 'ether');
        //        $eth = $value * 1e16;
        $eth = Utils::toHex($eth, true);

        $transaction = new Transaction([
            'nonce' => "$nonce",
            'from' => $from,
            'to' => $to,
            'gas' => '0x3d090',
            'gasPrice' => "0x5f5e100",
            'value' => "$eth",
            'chainId' => "56",
        ]);

        $raw = $transaction->sign($private);
        $res = $this->api->sendRawTransaction('0x' . $raw);
        return $res;

    }



}
