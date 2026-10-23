<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/8
 * Time: 18:22
 */

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use QL\QueryList;
use function Clue\StreamFilter\fun;

class HomeController extends Controller
{

    public function customer(Request $request){
        $url = Configuration::getItem('customer_service_url');
        header("location:$url");
    }

    public function agreement()
    {
        return view('agreement');
    }

    public function privacy()
    {
        return view('privacy');
    }

    public function payment(Request $request)
    {
        return view('payment',[
            'url' => Env::get('URL')
        ]);
    }
}
