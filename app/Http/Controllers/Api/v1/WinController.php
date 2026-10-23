<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\WinResource;
use App\Models\Account;
use App\Models\Win;
use Illuminate\Http\Request;

class WinController extends Controller
{
    public function index(Request $request)
    {
        $win_rank = Win::query()
            ->where('show', '=', Win::SHOW_TRUE)
            ->orderByDesc('amount')
            ->select('phone', 'amount')
            ->get();
        return $this->success([
            'win_rank' => WinResource::collection($win_rank),
            //需要获取真数据需要想下面代码修改成
            //'this_week_rank' => WinResource::collection(app(Account::class)->thisWeekCommissionTopFive()),
            'this_week_rank' => WinResource::collection(app(Account::class)->fakeThisWeekCommissionTopFive()),
        ]);
    }
}
