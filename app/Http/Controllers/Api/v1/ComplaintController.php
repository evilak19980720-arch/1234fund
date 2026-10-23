<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\ComplaintRequest;
use App\Http\Resources\ComplaintReource;
use App\Models\Complaint;
use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * 投诉举报
 * @package App\Http\Controllers\Api
 */
class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $page = $this->paging($page, $limit);
        $user = $request->user();

        $complaints = $user->complaints()->offset($page)->limit($limit)->orderBy('created_at', 'desc')->get();

        return $this->success(ComplaintReource::collection($complaints));
    }

    public function show(Complaint $complaint, Request $request)
    {
        $complaint->username = $request->user()->name;
        return $this->success($complaint);
    }

    public function store(ComplaintRequest $request)
    {
        $user = $request->user();
        $data = $request->only(['type', 'title', 'content', 'contact']);
        $user->complaints()->create($data);
        return $this->success();
    }
}
