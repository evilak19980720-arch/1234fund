<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\TypeResource;
use App\Models\Type;
use Illuminate\Http\Request;

/**
 * 任务类型
 * @package App\Http\Controllers\Api
 */
class TypeController extends Controller
{
    public function index(Request $request)
    {
        return $this->success(TypeResource::collection(Type::getType()));
    }

    public function taskType(Request $request)
    {
        $data = [];
        $types = Type::getType() ?: $data;
        if ($types) {
            foreach ($types as $type) {
                array_push($data,[
                    'id' => $type->id,
                    'text' => $type->name
                ]);
            }
        }

        return $data;
    }
}
