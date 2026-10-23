<?php


namespace App\Http\Controllers\Api\v1;
/**
 * 转让 或 饲养
 * */

use App\Http\Requests\Api\v1\PetCrowdRequest;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Issue;
use App\Models\Pet;
use App\Models\PetOrder;
use App\Models\Rule;
use App\Models\User;
use App\Models\CrowdsOrder;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PetCrowdController extends Controller
{


    /**
     * 饲养
     * 宠物添加到购买记录中
     * */
    public function rearing(PetCrowdRequest $request){
        return $this->success([]);
//        $user = $request->user();
//        $res = DB::transaction(function () use ($request,$user) {
//            $crowd_id = $request->crowd_id;
//            $issue = $request->issue;
//            $winUser = CrowdsOrder::with('user')->with('crowd')
//                ->where('crowd_id','=',$crowd_id)
//                ->where('issue','=',$issue)
//                ->where('win','=',1)
//                ->where('user_id','=',$user->id)
//                ->lockForUpdate()
//                ->first();
//            if(!$winUser){  return 90000;  }
//            if($winUser->status != 0){  return 429;  }
//            $issues = Issue::where('crowd_id','=',$crowd_id)->where('issue','=',$issue)->first();
//            $data = [
//                'user_id' => $winUser->user->id,
//                'pet_id' => $winUser->crowd->pet_id,
//                'price' => $issues->total_price,
//                'number' => 1,
//                'amount' => $issues->total_price,
//                'profit_amount' => $issues->profit,
//                'selling_price' => $issues->total_price + $issues->profit,
//                'selling_amount' => $issues->total_price + $issues->profit,
//                'day' => $issues->day,
//                'type' => 1,
//                'state' => 0,
//                'source' => 1,
//                'no' => only_code(),
//                'start_at'  => date('Y-m-d')
//            ];
//            //创建订单
//            PetOrder::create($data);
//            //Pet::where('id','=',$winUser->crowd->pet_id)->decrement('stock_quantity', 1); //减库存
//            $winUser->status = 1;
//            $winUser->save();
//            return 1;
//        });
//        if ($res == 1) {
//            return $this->success();
//        }
//        $this->errorResponse(Response::HTTP_OK, trans('error.' . $res), $res);
    }

    /**
     * 转让
     * 系统扣手续费回收
     * */
    public function transfer(PetCrowdRequest $request){
        return $this->success([]);
//        $user = $request->user();
//        $res = DB::transaction(function () use ($request,$user) {
//            $crowd_id = $request->crowd_id;
//            $issue = $request->issue;
//            $winUser = CrowdsOrder::with('user')->with('crowd')
//                ->where('crowd_id','=',$crowd_id)
//                ->where('issue','=',$issue)
//                ->where('win','=',1)
//                ->where('user_id','=',$user->id)
//                ->lockForUpdate()
//                ->first();
//            if(!$winUser){  return 90000;  }
//            if($winUser->status != 0){  return 429;  }
//            $issues = Issue::where('crowd_id','=',$crowd_id)->where('issue','=',$issue)->first();
//            $price_rate = intval($issues->total_price * $winUser->crowd->rate * 0.01);  //手续费
//            $price = $issues->total_price - $price_rate;
//            //增加用户余额
//            User::where('id','=',$winUser->user->id)->increment('balance', $price);
//            //添加流水记录
//            Account::create([
//                'type' => Account::TRANSFER,
//                'to' => $winUser->user->id,
//                'amount' => $price,
//                'order_id' => $winUser->id
//            ]);
//            $winUser->status = 2;
//            $winUser->save();
//            return 1;
//        });
//        if ($res == 1) {
//            return $this->success();
//        }
//        $this->errorResponse(Response::HTTP_OK, trans('error.' . $res), $res);
    }


}
