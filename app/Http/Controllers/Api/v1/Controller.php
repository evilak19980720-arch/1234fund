<?php

namespace App\Http\Controllers\Api\v1;

use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Controller extends BaseController
{

    /**
     * 成功响应
     * @param $data
     * @param int $httpCode
     * @return \Illuminate\Http\JsonResponse|object
     */
    public function success($data=null,$httpCode=Response::HTTP_OK)
    {
        return response()->json([
            'code' => 0,
            'msg' => '',
            'data' => $data,
            'now' => Carbon::now()->timestamp
        ])->setStatusCode($httpCode);
    }

    public function fail($code,$msg='')
    {
        if(empty($msg)){
            info('返回错误空1');
        }
        return response()->json([
            'code' => $code,
            'msg' => $msg ?: getMsg($code),
            'now' => Carbon::now()->timestamp
        ])->setStatusCode(Response::HTTP_OK);
    }

    protected function paging($page,$limit)
    {
        $page = $page - 1;
        if ($page != 0) {
            $page = $limit * $page;
        }
        return $page;
    }

    public function errorResponse($statusCode, $message=null, $code=0)
    {
        if(empty($message)){
            info('返回错误空2');
        }
        throw new HttpException($statusCode, $message, null, [], $code);
    }

    public function paginate($list)
    {
        $paginate['current_page'] = $list->currentPage();
        $paginate['last_page'] = $list->lastPage();
        $paginate['per_page'] = (int) $list->perPage();
        $paginate['total'] = $list->total();

        return $paginate;
    }
}
