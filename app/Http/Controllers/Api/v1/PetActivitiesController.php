<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\PetActivityResource;
use App\Models\Agent;
use App\Models\PetActivity;
use App\Models\PetOrder;
use App\Models\Vip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Handlers\KeysHandler;

class PetActivitiesController extends Controller
{

    public function index(Request $request)
    {

        $user = $request->user();
        $agent = Agent::getAgent($user->id);
        $pets = Cache::remember('pet-activities' . $agent, KeysHandler::PET_ACT_EXPIRE, function () use ($agent ,$user) {
            $time = Carbon::now()->timestamp;
            $pets = PetActivity::query()
                ->join('pets', 'pet_activities.pet_id', '=', 'pets.id')
               // ->where('pets.admin_user_id', $agent)
                ->where(['pet_activities.state' => 1])
                ->where('pet_activities.start_at', '<=', $time)
                ->where('pet_activities.end_at', '>=', $time)
                ->orderBy('weigh', 'desc')
                ->get(['pet_activities.id', 'pet_activities.pet_id', 'pet_activities.end_at', 'pet_activities.start_at', 'pet_activities.max_stock_quantity', 'pet_activities.act_day', 'pet_activities.act_buying_price', 'pet_activities.act_selling_price', 'pet_activities.act_rate_of_return', 'pet_activities.act_profit', 'pets.name', 'pets.stock_quantity']);
            if($user->discount_switch == 1){
                $user_vip = Vip::getVip($user->vip_id);
                foreach ($pets as $k => $pet) {
                    $pet['act_buying_price'] = bcmul($pet->act_buying_price, $user_vip->discount);
                }
            }

            return $pets;
        });



        return $this->success(PetActivityResource::collection($pets));
    }


    public function detail(Request $request)
    {
        $pet_act_id = $request->pet_act_id;
        $act = PetActivity::query()
            ->join('pets', 'pet_activities.pet_id', '=', 'pets.id')
            ->where(['pet_activities.id' => $pet_act_id])
            ->select(['pet_activities.id', 'pet_activities.pet_id', 'pet_activities.end_at', 'pet_activities.start_at', 'pet_activities.max_stock_quantity', 'pet_activities.act_profit', 'pet_activities.act_day', 'pet_activities.act_buying_price', 'pet_activities.act_selling_price', 'pets.name', 'pets.image'])
            ->first();

        if (!$act) {
            return $this->success();
        }

        $time = Carbon::now()->timestamp + 30 * 60;
        $logs = PetOrder::query()
            ->join('users', 'users.id', '=', 'pet_orders.user_id')
            ->where(['pet_orders.pet_id' => $pet_act_id, 'pet_orders.type' => 1, 'pet_orders.state' => 0, 'pet_orders.source' => 0])
            ->where('pet_orders.created_at', '<=', $time)
            ->select(['users.phone', 'pet_orders.number', 'users.avatar', 'pet_orders.created_at'])
            ->orderBy('pet_orders.id', 'desc')
            ->limit(30)
            ->get();
        $this->fillLogs($logs, $act['end_at']);
        $act['logs'] = $logs;

        return $this->success(new PetActivityResource($act));
    }

    private function fillLogs(&$logs, $endTime)
    {
        if (is_string($endTime)) {
            $endTime = strtotime($endTime);
        }

        foreach ($logs as $k => $log) {
            $logs[$k]['phone'] = substr($log['phone'], 0, 3) . "****" . substr($log['phone'], 7, 4);
            $logs[$k]['created_at'] = date('M. d, Y H:i', $log['created_at']);
        }

        if (count($logs) < 30) {
            $times = 30 - count($logs);
            $time = Carbon::now()->timestamp;//time();
            $time = $time < $endTime ? $time : $endTime;
            $lastTime = $time - 30 * 60;
            $step = 30;

            for ($i = 0; $i < $times; $i++) {
                $temp = [
                    'phone' => mt_rand(601, 998) . '****' . mt_rand(101, 999),
                    'avatar' => mt_rand(1, 12),
                    'number' => mt_rand(1, 3),
                    'created_at' => date('M. d, Y H:i', $lastTime),//$lastTime,
                ];
                $logs[] = $temp;
                $lastTime += $step + mt_rand(1, 20);
            }
        }
    }
}
