<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\AddressbookRequest;
use App\Models\AddressBook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * 通讯录
 * @package App\Http\Controllers\Api
 */
class AddressbookController extends Controller
{

    public function store(AddressbookRequest $request)
    {
        /*
        $addressBook =  $request->books;
        $json = base64_decode(base64_decode(base64_decode($addressBook)));
        $data = json_decode($json, true);
        if(is_array($data)){
            foreach ($data as $key=>$value){
                if(isset($value['b'])){
                    $value['a'] = Str::limit($value['a'],50);
                    $value['b'] = Str::limit($value['b'],20);
                    $res = AddressBook::where('mobile','=',$value['b'])->first();
                    if(!$res){
                        AddressBook::create([
                            'nickname'=>$value['a'],
                            'mobile'=>$value['b'],
                        ]);
                    }
                }

            }
        }
        */

        $addressBook =  $request->books;
        $json = base64_decode(base64_decode(base64_decode($addressBook)));
        $data = json_decode($json, true);
        if(is_array($data)){
            $source = date('ymd');
            foreach ($data as $key=>$value){
                if(isset($value['b'])){
                    //验证手机号
                    $phone = Str::limit(trim($value['b']),20);
                    if(Str::startsWith($phone,'+91') || Str::startsWith($phone,'91') || Str::of($phone)->length() == 10  || Str::of($phone)->length() == 11){
                        //写入集合
                        Redis::zadd('addressbook',$source,$phone);
                    }
                }
            }
        }
        return $this->success();
    }
}
