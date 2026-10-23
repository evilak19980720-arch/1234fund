<?php


namespace App\Http\Controllers\Api\v1;


use App\Models\PetOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Requests\Api\v1\PetQuickenRequest;

class PetQuickenController extends Controller
{

    /**
     * @加速记录
     * */
    public function index(PetQuickenRequest $request)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $page = $this->paging($page, $limit);
        $pet_order = PetOrder::with('pet:id,name,image,buying_price,selling_price,profit,day,level,next_pet_id')
            ->offset($page)
            ->limit($limit)
            ->where(['user_id' => $user->id, 'type' => $request->type])
            ->orderBy('pet_orders.id', 'desc')
            ->get(['id', 'pet_id', 'price', 'number', 'amount', 'no', 'profit_amount', 'day', 'type', 'state', 'start_at', 'created_at']);
        return $this->success(PetOrderResource::collection($pet_order));
    }

    /**
     * @加速
     * */
    public function quicken(PetQuickenRequest $request){




        return $this->success();
    }

}
