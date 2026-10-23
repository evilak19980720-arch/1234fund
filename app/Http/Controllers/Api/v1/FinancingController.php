<?php

namespace App\Http\Controllers\Api\v1;


use App\Http\Requests\Api\v1\FinancingRequest;
use App\Http\Resources\FriendResource;
use App\Models\Vip;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * 朋友圈
 * @package App\Http\Controllers\Api\v1
 */
class FinancingController extends Controller
{

    public function store(FinancingRequest $request)
    {
        $user = $request->user();
        $vip = Vip::getVip($user->vip_id);

        if ($request->get('amount') > $vip->financing_max_amount) {
            return $this->fail(90006, transtable('90006'));
        }

        $financing = $user->financing()->create([
            'amount' => $request->get('amount'),
            'whatsapp' => $request->get('whatsapp'),
            'project_name' => $request->get('project_name'),
            'project_plan' => $request->get('project_plan'),
        ]);
        return $this->success();
    }

}
