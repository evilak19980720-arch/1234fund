<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\PaymentResource;
use App\Models\Agent;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        return $this->success(Payment::query()->open()
            ->where('admin_user_id', '=', $request->user()->admin_user_id)
            ->pay(Payment::PAY_OPEN)
            ->select(['id', 'title', 'upper', 'lower', 'give'])
            ->orderBy('sort', 'desc')
            ->get());
    }
}
