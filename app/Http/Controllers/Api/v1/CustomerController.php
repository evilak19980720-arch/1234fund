<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cache;

/**
 * 客服
 * @package App\Http\Controllers\Api\v1
 */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = null;
        $authorization = $request->header('authorization');
        if ($authorization) {
            $user = JWTAuth::parseToken()->touser();
            $customers = Customer::getCustomer(0, $user->id);
        } else {
            $customers = Customer::getCustomer();
        }
        if ($customers) {
            return $this->success([new CustomerResource($customers[0])]);
        }

        return $this->success([]);
    }
}
