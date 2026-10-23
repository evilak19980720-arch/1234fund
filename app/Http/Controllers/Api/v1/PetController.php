<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Http\Resources\PetResource;
use App\Models\Agent;
use App\Models\Pet;
use App\Models\PetActivity;
use App\Models\Vip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 宠物列表
 * @package App\Http\Controllers\Api\v1
 */
class PetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $agent = Agent::getAgent($user->id);
        //$pets = Pet::where(['state' => 1])->orderBy('sort', 'asc')->where('admin_user_id', $agent)
        //    ->get(['id', 'name', 'buying_price', 'selling_price', 'day', 'created_at']);
        $pets = Pet::where(['state' => 1])->orderBy('sort', 'asc')->where('admin_user_id', $agent)
            ->get(['id', 'name', 'buying_price', 'selling_price', 'day'
                , 'rate_of_return','profit', 'stock_quantity', 'state', 'sales_volume', 'created_at']);

        //缓存时间不一致，单独筛选
        $time = time();
        $act = PetActivity::query()
            ->where(['state' => 1])
            ->where('start_at', '<=', $time)
            ->where('end_at', '>=', $time)
            ->pluck('pet_id');
        $act = $act ? $act->toArray() : [];
        $pets = PetResource::collection($pets);
        $result = [];
        $user_vip = Vip::getVip($user->vip_id);
        foreach ($pets as $k => $pet) {
            if (!in_array($pet['id'], $act)) {
                if($user->discount_switch == 1){
                    $pet['buying_price'] = bcmul($pet->buying_price, $user_vip->discount);
                }
                $result[] = $pet;
            }
        }
        return $this->success($result);
    }

    public function detail(Request $request)
    {
        $pet_id = $request->pet_id;
        $pet = Pet::withTrashed()->with('atlases')->find($pet_id, ['id', 'name', 'image', 'buying_price', 'selling_price', 'profit', 'day', 'level', 'stock_quantity', 'holding_quantity', 'describe', 'introduction']);
        if (!$pet) {
            return $this->success();
        }
        return $this->success(new PetResource($pet));
    }
}
