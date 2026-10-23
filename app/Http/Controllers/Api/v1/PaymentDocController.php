<?php


namespace App\Http\Controllers\Api\v1;


use App\Http\Requests\Api\v1\PaymentDocRequest;
use App\Http\Resources\PaymentDocResource;
use App\Models\PaymentDoc;

class PaymentDocController extends Controller
{
    public function docShow(PaymentDocRequest $request)
    {
        $doc = PaymentDoc::where('order_id', $request->order_id)->first(['id', 'image_url']);
        if (!$doc) {
            return $this->success(new \stdClass());
        }
        return $this->success(new PaymentDocResource($doc));
    }

    public function docUpload(PaymentDocRequest $request)
    {
        $user_id = $request->user()->id;
        $pay_doc = PaymentDoc::where('order_id', $request->order_id)->first();
        if (!$pay_doc) {
            PaymentDoc::create([
                'user_id' => $user_id,
                'order_id' => $request->order_id,
                'image_url' => $request->image_url,
            ]);
        } else {
            $pay_doc->image_url = $request->image_url;
            $pay_doc->save();
        }
        return $this->success();
    }
}
