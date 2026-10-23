<?php

namespace App\Admin\Actions\RechargeAddress;

use App\Models\UserRechargeAddress;
use App\Models\CollectTrctask;

use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Facades\Admin;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Collect extends BatchAction
{
    public $name = '归集资金';
    private $expire = 30;

    public function handle(Collection $collection)
    {
        $limits = request()->get('limits');
        $type = request()->get('type');
        $collect_key = request()->get('collect_key');
        $toaddress = request()->get('toaddress');
        if ($collect_key != config('ThirdPay.collect_key')){
            return $this->response()->error('归集密码错误');
        }
        Cache::lock('collecttrc', $this->expire)->get(function () use ($collection, $limits , $type, $toaddress) {
            DB::transaction(function ()use($collection, $limits , $type, $toaddress) {
                //info('执行');
                if($type == 2){
                    $field = 'trx_balance';
                }else{
                    $field = 'balance';
                }
                $collect_trctask = CollectTrctask::query()->create([
                    'type' => $type,
                    'toaddress' => $toaddress
                ]);
                if($limits == 'all'){
                    if($type == 2){
                        UserRechargeAddress::query()->where('block','=','TRC')->where($field,'>',2)->chunkById(100, function ($collection)use($collect_trctask,$field) {
                            self::insertdata($collection, $collect_trctask, $field);
                        });
                    }elseif($type == 1){
                        UserRechargeAddress::query()->where('block','=','TRC')->where($field,'>',0)->chunkById(100, function ($collection)use($collect_trctask,$field) {
                            self::insertdata($collection, $collect_trctask, $field);
                        });
                    }elseif($type == 3){
                        UserRechargeAddress::query()->where('block','=','BSC')->where($field,'>',0)->chunkById(100, function ($collection)use($collect_trctask,$field) {
                            self::insertdata($collection, $collect_trctask, $field);
                        });
                    }


                }else{
                    self::insertdata($collection, $collect_trctask, $field);
                }
                sleep(2);
            });
        });
        return $this->response()->success('Success message...')->refresh();
    }


    public function insertdata($collection, $collect_trctask,$field){
        $insert_data = [];
        foreach ($collection as $model) {
            if( ($collect_trctask->type ==1 && $model[$field] > 0)
                ||
                ($collect_trctask->type ==2 && $model[$field] > 2)
                ||
                ($collect_trctask->type ==3 && $model[$field] > 0)
            ){
                $insert_data[] = [
                    'collect_trctask_id' => $collect_trctask->id,
                    'address_id' => $model->id,
                    'address' => $model->address,
                    'private_key' => $model->private_key
                ];
            }
        }
        Db::table('collect_trctask_logs')->insert($insert_data);
    }


    public function form()
    {
        $this->radio('type', '状态')->options(CollectTrctask::TYPE_ARR)->default(1)->required();
        $this->radio('limits', '范围')->options([
            'picking' => '归集所选',
            'all' => '归集全部'
        ])->default('picking')->required()->help('全部,则归集所有余额大于0的地址,TRX归集大于2的地址');
        $this->text('toaddress', '收款地址')->required();;
        $this->password('collect_key', '归集密码')->required();;

    }


}
