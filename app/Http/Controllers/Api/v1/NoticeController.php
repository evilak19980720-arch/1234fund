<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\NoticeRequest;
use App\Http\Resources\NoticeResource;
use App\Models\AgentConf;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 公告
 * @package App\Http\Controllers\Api\v1
 */
class NoticeController extends Controller
{
    public function index(Request $request)
    {
        return $this->success(NoticeResource::collection(Notice::getNotices($request->user()->admin_user_id)));
    }


    public function top(Request $request)
    {
        return $this->success(NoticeResource::collection(Notice::getNotices($request->user()->admin_user_id,'top')));
    }

    public function eject(Request $request)
    {
        return $this->success(NoticeResource::collection(Notice::getNotices($request->user()->admin_user_id,'eject')));
    }

    public function show($id,Request $request)
    {
        $notice = Notice::getNotice($request->user()->admin_user_id,$id);
        if ($notice) {
            return $this->success(new NoticeResource($notice));
        }
        $this->errorResponse(Response::HTTP_NOT_FOUND,transtable('404'),Response::HTTP_NOT_FOUND);
    }
}
