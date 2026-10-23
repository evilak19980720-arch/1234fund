<?php

use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Godruoyi\Snowflake\Snowflake;

/**
 * 获取错误信息
 * @param $code
 * @return mixed
 */
function getMsg($code)
{
    return config('error')[(int)$code];
}


function set_config($dotKey, $value)
{
    return (new \App\Models\ConfigModel())->setConfig($dotKey, $value);
}

//时间格式化（时间戳）
function uc_time_ago($ptime)
{

    switch ($ptime) {
        //今天
        case date('Ymd', $ptime) == date('Ymd'):
            $msg = date('H:i', $ptime);
            break;
        //昨天
        case date('Ymd', $ptime) == date('Ymd', time() - 60 * 60 * 24):
            $msg = '昨天' . date('H:i', $ptime);;
            break;
        //今年
        case date('Y', $ptime) == date('Y'):
            $msg = date('m月d日H:i', $ptime);
            break;
        //其他
        default:
            $msg = date('Y年m月d日H:i', $ptime);
    }
    return $msg;
}

function save_cache($cacheKey, int $value = 0)
{
    if (Illuminate\Support\Facades\Cache::has($cacheKey)) {
        $arr = Illuminate\Support\Facades\Cache::get($cacheKey);
        if (!in_array($value, $arr['ids'])) {
            $passTime = time() - $arr['time'];
            !empty($value) && $arr['ids'][] = $value;
            $arr['time'] = time();
            $arr['ttl'] = $arr['ttl'];
            $arr['count'] += 1;
            Illuminate\Support\Facades\Cache::put($cacheKey, $arr, $arr['ttl'] - $passTime);
        }
    } else {
        $ttl = strtotime(date('Y-m-d', strtotime('+1 day'))) - time();
        $count = empty($value) ? 0 : 1;
        Illuminate\Support\Facades\Cache::put($cacheKey, ['time' => time(), 'ttl' => $ttl, 'ids' => [$value], 'count' => $count], $ttl);
    }
}

function save_cache_count($cacheKey, $value = 0)
{
    if (Illuminate\Support\Facades\Cache::has($cacheKey)) {
        $arr = Illuminate\Support\Facades\Cache::get($cacheKey);
        if (!in_array($value, $arr['ids'])) {
            !empty($value) && $arr['ids'][] = $value;
        }
        $passTime = time() - $arr['time'];
        $arr['time'] = time();
        $arr['ttl'] = $arr['ttl'];
        $arr['count'] += 1;
        Illuminate\Support\Facades\Cache::put($cacheKey, $arr, $arr['ttl'] - $passTime);
    } else {
        $ttl = strtotime(date('Y-m-d', strtotime('+1 day'))) - time();
        $count = empty($value) ? 0 : 1;
        Illuminate\Support\Facades\Cache::put($cacheKey, ['time' => time(), 'ttl' => $ttl, 'ids' => [$value], 'count' => $count], $ttl);
    }
}

function get_config_cache($name)
{

    if (Cache::has('cf_' . $name)) {
        return Cache::get('cf_' . $name);
    }
    //从config表中寻找
    $res = \App\Models\ConfigModel::where('name', $name)->first();
    if (empty($res)) {
        return false;
    }
    Cache::put('cf_' . $res->name, [
        'title' => $res->title,
        'name' => $res->name,
        'value' => $res->value,
        'description' => $res->description,
        'image' => $res->image,
        'type' => $res->type
    ], 3600 * 24 * 10);
    return Cache::get('cf_' . $name);

}

/**
 * 生成人物关系
 * @param $str  邀请人的人物关系
 * @param $id   邀请人的id
 * @param int $level 层级限制
 * @return false|string
 */
function generateRelation($str, $id, $level = 3)
{
    $str = sprintf('%s%d,', $str, $id);
    if (substr_count($str, ',') > ($level + 1)) {
        return substr($str, strpos($str, ',', 2));
    } elseif (strpos($str, ',') !== 0) {
        return sprintf(',%s', $str);
    } else {
        return $str;
    }
}


function upload_image($path, $file, $drive = 'oss')
{
    $disk = Storage::disk($drive);

    //将图片上传到OSS中，并返回图片路径信息 值如:avatar/WsH9mBklpAQUBQB4mL.jpeg
    $path = $disk->put($path, $file);

    //由于图片不在本地，所以我们应该获取图片的完整路径，
    //值如：https://test.oss-cn-hongkong.aliyuncs.com/avatar/8GdIcz1NaCZ.jpeg
    return $disk->url($path);
}

// 拼凑阿里oss域名前缀
function aliyun_domain()
{
    return 'https://'.env('OSS_URL');
    //return 'https://'.env('OSS_URL').'/'.env('OSS_PREFIX');
    /*
    return Env::get('ALIYUN_IS_CNAME') ?
        Env::get('ALIYUN_CDN_DOMAIN') :
        sprintf('%s://%s.%s', Env::get('ALIYUN_SSL') ? 'https' : 'http', Env::get('ALIYUN_BUCKET'), Env::get('ALIYUN_ENDPOINT'));
    */
}

/**
 * 生成红包
 * @param $amount 金额
 * @param $number   数量
 * @param float $min 最少得多少
 * @return mixed
 */
function generateRedEnvelope($amount, $number, $min = 0.01)
{
    for ($i = 1; $i < $number; $i++) {
        $safe_total = ($amount - ($number - $i) * $min) / ($number - $i);
        $money = round(mt_rand($min * 100, $safe_total * 100) / 100);
        $money = $money ? $money : 1;
        $amount = bcsub($amount, $money, 2);
        $arr[] = $money;
    }
    $arr[] = (int)$amount;
    rsort($arr);
    $str = implode(',', $arr);
    return $str;
}


/**
 * 取得根域名
 * @param type $domain 域名
 * @return string 返回根域名
 */
function GetUrlToDomain($url='') {
        if(!$url){
            return $url;
        }
        #列举域名中固定元素
        $state_domain = array(
            'al','dz','af','ar','ae','aw','om','az','eg','et','ie','ee','ad','ao','ai','ag','at','au','mo','bb','pg','bs','pk','py','ps','bh','pa','br','by','bm','bg','mp','bj','be','is','pr','ba','pl','bo','bz','bw','bt','bf','bi','bv','kp','gq','dk','de','tl','tp','tg','dm','do','ru','ec','er','fr','fo','pf','gf','tf','va','ph','fj','fi','cv','fk','gm','cg','cd','co','cr','gg','gd','gl','ge','cu','gp','gu','gy','kz','ht','kr','nl','an','hm','hn','ki','dj','kg','gn','gw','ca','gh','ga','kh','cz','zw','cm','qa','ky','km','ci','kw','cc','hr','ke','ck','lv','ls','la','lb','lt','lr','ly','li','re','lu','rw','ro','mg','im','mv','mt','mw','my','ml','mk','mh','mq','yt','mu','mr','us','um','as','vi','mn','ms','bd','pe','fm','mm','md','ma','mc','mz','mx','nr','np','ni','ne','ng','nu','no','nf','na','za','aq','gs','eu','pw','pn','pt','jp','se','ch','sv','ws','yu','sl','sn','cy','sc','sa','cx','st','sh','kn','lc','sm','pm','vc','lk','sk','si','sj','sz','sd','sr','sb','so','tj','tw','th','tz','to','tc','tt','tn','tv','tr','tm','tk','wf','vu','gt','ve','bn','ug','ua','uy','uz','es','eh','gr','hk','sg','nc','nz','hu','sy','jm','am','ac','ye','iq','ir','il','it','in','id','uk','vg','io','jo','vn','zm','je','td','gi','cl','cf','cn','yr','com','arpa','edu','gov','int','mil','net','org','biz','info','pro','name','museum','coop','aero','xxx','idv','me','mobi','asia','ax','bl','bq','cat','cw','gb','jobs','mf','rs','su','sx','tel','travel','in'
        );

        if(!preg_match("/^http/is", $url)){
            $url="http://".$url;
        }

        $res = (object)[];
        $res->domain = null;
        $res->host = null;
        $url_parse = parse_url(strtolower($url));
        $urlarr = explode(".", $url_parse['host']);
        $count = count($urlarr);

        if($count <= 2){
            #当域名直接根形式不存在host部分直接输出
            $res->domain = $url_parse['host'];
        }elseif($count > 2){
            $last = array_pop($urlarr);
            $last_1 = array_pop($urlarr);
            $last_2 = array_pop($urlarr);

            $res->domain = $last_1.'.'.$last;
            $res->host = $last_2;

            if(in_array($last, $state_domain)){
                $res->domain=$last_1.'.'.$last;
                $res->host=implode('.', $urlarr);
            }

            if(in_array($last_1, $state_domain)){
                $res->domain = $last_2.'.'.$last_1.'.'.$last;
                $res->host = implode('.', $urlarr);
            }
            #print_r(get_defined_vars());die;
        }
        return $res->domain;
}

//获取唯一码
function only_code(): string
{
    $no = (new Snowflake())->id();
    $lock = Cache::lock('snowflake_'.$no, 1);
    if ($lock->get()) {
        return $no;
    }
    return only_code();
}

//获取用户真实 ip_api
function getIp()
{
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_REAL_IP')) {
        $ip = getenv('HTTP_X_REAL_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
        $ips = explode(',', $ip);
        $ip = $ips[0];
    } elseif (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = '0.0.0.0';
    }

    return $ip;
}

//获取用户真实 ip_api
function getIp2()
{
    if (getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    }
    else if (getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    }
    else if (getenv("HTTP_X_FORWARDED")) {
        $ip = getenv("HTTP_X_FORWARDED");
    }
    else if (getenv("HTTP_FORWARDED_FOR")) {
        $ip = getenv("HTTP_FORWARDED_FOR");
    }
    else if (getenv("HTTP_FORWARDED")) {
        $ip = getenv("HTTP_FORWARDED");
    }
    else {
        $ip = $_SERVER["REMOTE_ADDR"];
    }



    return explode(',',$ip)[0];
}


if (! function_exists('transtable')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @return \Illuminate\Contracts\Translation\Translator|string|array|null
     */
    function transtable($key = null, $lang = null)
    {
        $massage = "null";
        if($lang){
            $tran = \App\Models\Tran::getTrans(1, $lang ,$key);
        }else{
            $tran = \App\Models\Tran::getTrans(1, App::getLocale() ,$key);
        }

        if($tran){
            $massage = $tran;
        }
        if(is_numeric($key)){
            //return $massage;
            return $key.'.'.$massage;
        }
        return $massage;
    }
}

if (! function_exists('transtableadmin')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @return \Illuminate\Contracts\Translation\Translator|string|array|null
     */
    function transtableadmin($key = null)
    {
        $massage = null;
        $tran = \App\Models\Tran::getTrans(2, App::getLocale() ,$key);
        if($tran){
            $massage = $tran;
        }
        return $massage;
    }
}


function isValidTRCAddress($address) {
    $re = substr($address,0,1)=="T" and strlen($address)==34;
    return $re;
}
function isValidBSCAddress($address) {
    $re = substr($address,0,2)=="0x" and strlen($address)==42;
    return $re;
}


//字符串中变量解析
function stringParsingVariable($str, $array){
    $match = array();
    preg_match_all('/{\$(.*?)}/', $str, $match);
    foreach ($match[1] as $key => $value) {
        if (isset($array[$value])) {
            $str = str_replace($match[0][$key], $array[$value], $str);
        }elseif (Str::startsWith($value, 'rand')){
            $rand_arr = Str::of($value)->explode('_');
            $str = str_replace($match[0][$key], rand($rand_arr[1],$rand_arr[2]), $str);
        }
    }
    return $str;
}

//飞机自动群发消息 转义符
/*
任何代码在 1 到 126 之间的字符都可以在前面有 '\' 字符的任何地方进行转义，在这种情况下，它被视为普通字符而不是标记的一部分。这意味着“\”字符通常必须使用前面的“\”字符进行转义。
pre在实体内部code，所有“`”和“\”字符必须使用前面的“\”字符进行转义。
在内(...)联链接和自定义表情符号定义部分内，所有 ')' 和 '\' 必须使用前面的 '\' 字符进行转义。
在所有其他位置，字符 '_'、'*'、'['、']'、'('、')'、'~'、'`'、'>'、'#'、'+'、' -'、'='、'|'、'{'、'}'、'.'、'!' 必须使用前面的字符“\”进行转义。
 *
 * 程序处理会 导致 MarkdownV2 自带的语法也不能使用---暂不处理
 * */
function telegramGroupChartcontentMarkdownV2($str)
{
    return $str;
}


function escapeSpecificChars($string, $charsToEscape) {
    foreach ($charsToEscape as $char) {
        $string = str_replace($char, '\\' . $char, $string);
    }
    return $string;
}

/**
 * 检查设备号是否存在
 * @param string $deviceNumber 设备号
 * @return bool|null 返回 true 表示存在，false 表示不存在，null 表示查询失败
 * https://coinadmin.147358.top/api/client/device/check-exists?device_number=333
 */
function checkDeviceExists($deviceNumber)
{
    if (empty($deviceNumber)) {
        return null;
    }

    // 如果设备号中包含2个或以上减号，直接返回false
    if (substr_count($deviceNumber, '-') >= 2) {
        return false;
    }

    // 从系统配置中获取API前缀
    $apiPrefix = \App\Models\Configuration::getItem('coin_check_device');

    if (empty($apiPrefix)) {
        return null;
    }

    // 构建完整的API URL
    $apiUrl = $apiPrefix . '/client/device/check-exists?device_number=' . urlencode($deviceNumber);

    try {
        // 使用 cURL 或 Guzzle 发送请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            \Illuminate\Support\Facades\Log::error('设备号查询失败', [
                'url' => $apiUrl,
                'http_code' => $httpCode,
                'error' => $error
            ]);
            return null;
        }

        // 解析返回的JSON
        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Illuminate\Support\Facades\Log::error('设备号查询返回格式错误', [
                'url' => $apiUrl,
                'response' => $response
            ]);
            return null;
        }

        // 检查返回格式：{"code":0,"msg":"","data":{"exists":false},"now":1767111904}
        if (isset($result['code']) && $result['code'] === 0 && isset($result['data']['exists'])) {
            return (bool)$result['data']['exists'];
        }
        return null;
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('设备号查询异常', [
            'url' => $apiUrl,
            'exception' => $e->getMessage()
        ]);
        return null;
    }

}



    /**
     * 检查设备是否有效
     *
     * @param string $deviceNumber
     * @return bool|null true=有效, false=无效, null=接口调用失败
     */
    function checkDeviceValid($deviceNumber, $field = 'is_valid')
    {
        // 从系统配置中获取API域名
        $baseUrl = \App\Models\Configuration::getItem('coin_check_device');

        if (empty($baseUrl)) {
            \Illuminate\Support\Facades\Log::warning('设备检查接口域名未配置', ['config_key' => 'coin_check_device']);
            return null;
        }

        // 构建完整的API URL
        $apiUrl = rtrim($baseUrl, '/') . '/client/device/check-valid?device_number=' . urlencode($deviceNumber);

        try {
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->get($apiUrl);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::error('设备有效性检查接口调用失败', [
                    'url' => $apiUrl,
                    'http_code' => $response->status(),
                    'error' => $response->body(),
                    'device_number' => $deviceNumber
                ]);
                return null;
            }

            // 解析返回的JSON
            $result = $response->json();

            if (!is_array($result)) {
                \Illuminate\Support\Facades\Log::error('设备有效性检查返回格式错误', [
                    'url' => $apiUrl,
                    'response' => $response->body(),
                    'device_number' => $deviceNumber
                ]);
                return null;
            }

            if($field == 'is_valid'){
                // 检查返回格式：{"code":0,"msg":"","data":{"valid":true/false},"now":...}
                if (isset($result['code']) && $result['code'] === 0 && isset($result['data']['is_valid'])) {
                    return (bool)$result['data']['is_valid'];
                }
            }else{
                if (isset($result['code']) && $result['code'] === 0 && isset($result['data'])) {
                    return $result['data'];
                }
            }


            \Illuminate\Support\Facades\Log::warning('设备有效性检查返回格式异常', [
                'url' => $apiUrl,
                'response' => $result,
                'device_number' => $deviceNumber
            ]);
            return null;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('设备有效性检查异常', [
                'url' => $apiUrl,
                'exception' => $e->getMessage(),
                'device_number' => $deviceNumber
            ]);
            return null;
        }
    }
