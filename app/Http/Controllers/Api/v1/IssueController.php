<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\IssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use Carbon\Carbon;

class IssueController extends Controller
{
    public function index(IssueRequest $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $page = $this->paging($page, $limit);
        $now = Carbon::now();

        $issues = Issue::with([
            'crowd.pet' => function ($query) {
                $query->select('id', 'name','image');
            }
        ])
            ->where('start_time','<',$now)->where('end_time','>',$now)
            ->select('id','crowd_id','issue','portion','total_price','start_time','end_time')
            ->offset($page)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get();

        return $this->success(IssueResource::collection($issues));
    }
}
