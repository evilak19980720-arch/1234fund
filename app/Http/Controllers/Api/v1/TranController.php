<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\TypeResource;
use App\Models\Tran;
use Illuminate\Http\Request;

class TranController extends Controller
{

    //客户端获取一种语言的全部文案
    public function list(Request $request)
    {
        $locale = $request->get('locale',config('app.api_locale'));
        $data = [];
        $trans = Tran::getTrans( 0, $locale) ?: $data;
        if ($trans) {
            foreach ($trans as $tran) {
                $data[$tran->key] = $tran->value;
            }
        }
        return $this->success($data);
    }



}
