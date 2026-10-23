<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/8
 * Time: 18:22
 */

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\AgentConf;
use App\Models\Configuration;
use App\Services\CustomerService\CsSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use QL\QueryList;
use function Clue\StreamFilter\fun;

class HomeController extends Controller
{

    public function customer(Request $request){
        // 后台设置为本站客服时，跳转到 H5 的站内客服页
        if (CsSettings::mode() === CsSettings::MODE_INTERNAL) {
            $url = rtrim((string) app(AgentConf::class)->getH5Url(null), '/') . '/service';
        } else {
            $url = Configuration::getItem('customer_service_url');
        }
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
