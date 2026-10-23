<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\State;
use Illuminate\Http\Request;

/**
 * 城邦
 * @package App\Http\Controllers\Api\v1
 */
class StateController extends Controller
{
    public function index(Request $request)
    {
        return $this->success(State::getStates());
    }
}
