<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\ManualRequest;
use App\Models\Manual;
use Illuminate\Http\Response;

class ManualController extends Controller
{
    public function index(ManualRequest $request)
    {
        $limit = $request->get('limit', 10);
        $page = $this->paging($request->page, $limit);

        $manuals = Manual::getList($page,$limit);
        return $this->success($manuals);
    }

    public function show($id)
    {
        if ($id > 0) {
            return $this->success(Manual::getManual($id));
        }
        $this->errorResponse(Response::HTTP_NOT_FOUND,'',Response::HTTP_NOT_FOUND);
    }
}
