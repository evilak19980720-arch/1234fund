<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\ReplyRequest;
use App\Http\Resources\ReplyResource;
use App\Models\Complaint;
use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReplyController extends Controller
{
    public function index(ReplyRequest $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $page = $this->paging($page, $limit);

        $replies = Reply::where('complaint_id','=',$request->complaint_id)->select('id','complaint_id','admin_user_id','user_id','content','created_at')
            ->offset($page)->limit($limit)->orderBy('created_at', 'asc')->get();
        return $this->success(ReplyResource::collection($replies));
    }

    public function store(ReplyRequest $request)
    {
        $user = $request->user();

        $row = Complaint::find($request->complaint_id);
        if (!$row) {
            $this->errorResponse(Response::HTTP_OK, transtable('404'), 404);
        }

        $row->replies()->create([
            'user_id' => $user->id,
            //'content' => $request->content
        ]);

        return $this->success();
    }
}
