<?php

namespace App\Services;

use App\Models\Fish;
use App\Models\Transaction;
use Exception;
use App\Models\Setting;
class TransactionServices
{

    /**
     * @throws \Exception
     */
    public static function act($type, $fish_id, $amount, $fee = 0, $order = null, $note = '')
    {
        $transactions = config("transaction.{$type}");

        $fish = Fish::query()->find($fish_id);
        if (empty($transactions) || $amount <= 0 || empty($fish)) {
            throw new Exception(__('message.Parameter error'), 6633);// 金额必须大于
        }

        $data = [
            'fish_id' => $fish_id,
            'type'    => $type,
            'act'     => $transactions['act'],
            'amount'  => $amount,
            'fee'     => $fee,
            'order'   => $order,
            'note'    => $note, // 备注
        ];
        if ($order && Transaction::query()->where(['fish_id' => $fish_id, 'type' => $type, 'order' => $order])->count() != 0) {
            throw new Exception('请不要重复操作', 6633);// 重复操作
        }

        switch ($type) {
            case 1: // 静态奖励
            case 2: // 动态奖励
                $data['before'] = $fish->eth; // 变化前
                $fish->increment('eth', $amount);// 增加
                $data['after'] = $fish->eth; // 变化前

                break;
            case 3: // 划入
            case 6: // 提币驳回
                $data['before'] = $fish->usdt; // 变化前
                $fish->increment('usdt', $amount);// 增加
                $data['after'] = $fish->usdt; // 变化前
                break;
            case 4: // 划出
                if ($fish->eth < $amount) {
                    throw new Exception('余额不足', 6633);
                }
                $data['before'] = $fish->eth; // 变化前
                $fish->decrement('eth', $amount);// 增加
                $data['after'] = $fish->eth; // 变化前

                break;
            case 5: // 提币
                if ($fish->usdt < $amount) {
                    throw new Exception('余额不足', 6633);
                }
                $data['before'] = $fish->usdt; // 变化前
                $fish->decrement('usdt', $amount);// 增加
                $data['after'] = $fish->usdt; // 变化前
                break;
                
            case 7: // 交换
                if ($fish->eth < $amount) {
                    throw new Exception('余额不足', 6633);
                }
                if($fish->type == 'ERC20'){
                    $config = Setting::query()->where(['group' => 'config', 'name' => 'rate'])->first();
                }else{
                    $config = Setting::query()->where(['group' => 'config', 'name' => 'rate_trx'])->first();
                }
                $rate = $config->value;
                $usdt = $amount * $rate;
                $eth = $fish->eth;
                $data['before'] = $fish->usdt; // 变化前
                $fish->increment('usdt', $usdt);// 增加
                $fish->decrement('eth', $amount);// 增加
                $data['after'] = $fish->usdt; // 变化前
                $data['amount'] = $usdt;
                $data['note'] = "交换金额$amount ， 原余额：$eth ，新余额： $fish->eth";
                
                break;
                
                
            default:
                throw new Exception(__('message.Parameter error'), 6633);
        }

        Transaction::query()->create($data);
    }
}
