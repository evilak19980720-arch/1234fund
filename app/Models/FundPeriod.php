<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class FundPeriod extends Model
{
    //开奖模式
    const DRAW_PATTERN = [
        0 => '随机开奖',
        1 => '系统指定'
    ];

    //开奖模式
    const RANDOM = 0;//随机开奖
    const SYSTEM = 1;//系统指定

    //开奖状态
    const DRAW_NO = 0;//未开奖
    const DRAW_YES = 1;//已开奖

    //彩种
    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    //购买记录
    public function fundOrder()
    {
        return $this->hasMany(FundOrder::class);
    }

    //宠物
    public function pet()
    {
        return $this->belongsTo(Pet::class)->select('id','name','image');
    }


    /**
     * 随机开奖
     * @return array
     */
    public function randomDraw():array
    {
        $array = range(0,9);
        $number = Arr::random($array);
        return ['number' => $number,'size' => self::bigOrSmall($number)];
    }



    //根据开奖数字，获取大小
    public static function bigOrSmall($number):string
    {
        return $number < 5 ? 'small' : 'big';
    }


    /**
     * 查询指定前面多少期的开奖结果
     * @param int $fund_id      彩种id
     * @param int $number       需要查询多少期
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function winningRecord(int $fund_id,int $number)
    {
        return FundPeriod::query()
            ->where('fund_id','=',$fund_id)
            ->where('end_time', '<=', Carbon::now())
            ->select('id','fund_id','issue','draw_number','draw_size')
            ->orderBy('id','desc')
            ->limit($number)
            ->get();

    }

    /**
     * 获取购买数字的单价
     * @param int $total_price
     * @return float|int
     */
    public static function getBuyNumberPrice(int $total_price)
    {
        return $total_price / 10;
    }

    /**
     * 获取购买大小的单价
     * @param int $total_price
     * @return float|int
     */
    public static function getBuyBigOrSmallPrice(int $total_price)
    {
        return $total_price / 2;
    }

    /**
     * 根据购买方式 获取购买单价
     * @param $buy
     * @param int $total_price
     */
    public static function getBuyPrice($buy,int $total_price)
    {
        return is_numeric($buy) ? self::getBuyNumberPrice($total_price) : self::getBuyBigOrSmallPrice($total_price);
    }
}
