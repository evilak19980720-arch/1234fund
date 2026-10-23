<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Models\Configuration;
use App\Models\Currency;
use App\Models\Pet;
use App\Models\Pixcel;
use App\Models\RedLadder;
use App\Models\Type;
use App\Models\Country;
use App\Models\Locale;

use Carbon\Carbon;
use danielme85\Geoip2\Facade\Reader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class HomeController extends Controller
{
    public $name = [
        'Alita', 'Almeta', 'Antonietta', 'Adaline', 'Astra', 'Antonina', 'Agate', 'Acadia', 'Angelle', 'Ambrosius', 'Anwar', 'Abu', 'Adolphus', 'Arron', 'Arturo', 'Arlin', 'Adil', 'Armin',
        'Blue', 'Bohannon', 'Bane', 'Brune', 'Bade', 'Bruni', 'Binger', 'Byran', 'Barbra', 'Benoit', 'Baptiste', 'Barra', 'Brun', 'Berthold', 'Barris', 'Brennon', 'Basel', 'Bilal',
        'Crosley', 'Carole', 'Chrysanna', 'Cath', 'Calandre', 'Cadynn', 'Cissy', 'Chela', 'Cesario', 'Cully', 'Coman', 'Claudius', 'Conal', 'Corran', 'Conner', 'Cordero', 'Casper',
        'Douglas', 'Dwight', 'Dunn', 'Duncan', 'Dennis', 'Dylan', 'Darcy', 'Dempsey', 'Dana', 'Darnell', 'Donahue', 'Darren', 'Devin', 'Dean', 'Daniel', 'Dominic', 'David', 'Drew', 'Dave', 'Derrick', 'Donald'
        , 'Emmanuel', 'Evan', 'Eugene', 'Ernest', 'Ellis', 'Elroy', 'Everley', 'Eden', 'Eric', 'Egbert', 'Earl', 'Edgar', 'Elton', 'Edmund', 'Elliot', 'Edwiin', 'Elmer', 'Edward', 'Enoch', 'Edison', 'Elijah', 'Elvis', 'Frank', 'Fitch', 'Fitzgerald', 'Fabian', 'Felix', 'Ferdinand', 'Franklin', 'Francis', 'Frederic'
        , 'George', 'Gerald', 'Gene', 'Godfery', 'Gregary', 'Gavin', 'Gabriel', 'Gale', 'Gordon', 'Glenn', 'Grover', 'Gilbert', 'Gary', 'Gustave', 'Geoffrey', 'Goddard', 'Geoff', 'Giles', 'Griffith'
        , 'Herman', 'Harriet', 'Hayden', 'Harlan', 'Herbert', 'Hubery', 'Hobart', 'Hunter', 'Haley', 'Hardy', 'Hogan', 'Hamiltion', 'Harley', 'Hyman', 'Harvey', 'Humphrey', 'Heather', 'Harry', 'Hilary', 'Horace', 'Henry', 'Hale', 'Howar', 'Hiram', 'Harold'
        , 'Ingemar', 'Ingram', 'Isaac', 'Isidore'
        , 'Joshua', 'Jerome', 'Julius', 'Jason', 'Julian', 'Jerry', 'Jeremy', 'Joyce', 'Jeffrey', 'Jesse', 'Jonathan', 'Joseph', 'Justin', 'Jared', 'Jacob', 'James', 'Jonas'
        , 'Keith', 'Kerwin', 'Kevin', 'Kent', 'Kerr', 'Kelly', 'Kyle', 'Kennedy', 'Kenneth', 'Kirk'
        , 'Lucien', 'Luther', 'Lambert', 'Lance', 'Lennon', 'Leonard', 'Leopold', 'Louis', 'Larry', 'Lawrence', 'Leif', 'Lyndon', 'Lester', 'Levi', 'Lewis', 'Lionel'
        , 'Matt', 'Malcolm', 'Mandel', 'Marvin', 'Miles', 'Maximilian', 'Murray', 'Marico', 'Merle', 'Mortimer', 'Marcus', 'Morgan', 'Maxwell', 'Moore', 'Michael', 'Marshall', 'Morton', 'Meredith', 'Mick', 'Montague', 'Moses', 'Matthew', 'Michell', 'Marlon', 'Merlin', 'Murphy', 'Marsh', 'Myron', 'Martin', 'Monroe', 'Magee', 'Maurice'
        , 'Nigel', 'Norman', 'Nathan', 'Noel', 'Norton', 'Nicholas', 'Nathaniel', 'Nelson', 'Newman'
        , 'Ogden', 'Oscar', 'Osmond', 'Orville', 'Osborn', 'Oswald'
        , 'Parker', 'Philip', 'Paul', 'Peter', 'Phil', 'Payne', 'Paddy', 'Patrick', 'Perry', 'Porter', 'Page', 'Prescott', 'Primo', 'Quentin', 'Quinn', 'Quintion', 'Quennel', 'Quincy'
        , 'Rory', 'Richard', 'Roderick', 'Rachel', 'Rupert', 'Rock', 'Rudolf', 'Robert', 'Reuben', 'Robin', 'Ronald', 'Regan', 'Ryan', 'Raymond', 'Reginald', 'Ralap', 'Randolph', 'Rodney'
        , 'Sampson', 'Saxon', 'Scott', 'Sebastian', 'Stanford', 'Samuel', 'Spencer', 'Sidney', 'Silvester', 'Simon', 'Solomon', 'Sandy', 'Sean', 'Stanley', 'Stan', 'Steven', 'Stev', 'Steward', 'Taylor'
        , 'Tyrone', 'Truman', 'Tracy', 'Thomas', 'Troy', 'Tiffany', 'Tony', 'Tobias', 'Theodore', 'Theobald', 'Tyler', 'Timothy', 'Todd', 'Toby', 'Ternence'
        , 'Upton', 'Uriah', 'Ulysses', 'Valentine', 'Verne', 'Vincent', 'Virgil', 'Victor', 'Valentine', 'Vivian'
        , 'Walker', 'Wythe', 'Wordsworth', 'Winston', 'Walter', 'Webb', 'Woodrow', 'Webster', 'Winfred', 'Wade', 'Wilbur', 'Wayne', 'Warner', 'Wright', 'Wendell', 'Werner', 'Willie
', 'William', 'Will', 'Ward', 'Xavier'
        , 'Yale', 'Yves', 'Yehudi', 'York', 'Zachary', 'Zebulon'
    ];

    public $data = [];


    public function locales(Request $request){
        $ip = $request->get('ip');
        $host = $request->get('host');
        if(empty($ip)){
            $ip = getIp2();
        }
        //info('获取的IP:'.$ip);
        try {
            $reader = Reader::connect();
            $result = $reader->city($ip);
            $isoCode = $result->country->isoCode;
        }catch (\Exception $exception){
            $isoCode = config('app.default_country');
        }

        $countries = Country::getCountries();
        $redata['country'] = [];
        foreach ($countries as &$item){
            if($item->country_i == $isoCode){
                unset($item->country_name);
                $redata['country'] = $item;
            }
        }
        $redata['locales'] = Locale::getLocales();
        $curreryObj = Currency::getCurrencies(Configuration::getConfig(null,'currency_symbol'));

        $redata['currency_symbol'] = $curreryObj->name;;
        $redata['currency_symbol_symbol'] = $curreryObj->symbol;


        $redata['pixcel'] = Pixcel::getPixcel($host);
        $redata['tkpixcel'] = Pixcel::getTkpixcel($host);
        $redata['system_model'] = config('app.system_model');
        $redata['revenue_model'] = config('app.revenue_model');
        $redata['down_title'] = transtableadmin('configuration.down_title');
        $down_logo = '/icon/icon-avatar-5a.png';
        $redata['down_logo'] = strpos($down_logo,'https') === false ? sprintf('%s%s', aliyun_domain(), $down_logo)  : $down_logo;
        return $this->success($redata);
    }


    public function countries(Request $request){
        $countries = Country::getCountries();
        foreach ($countries as &$item){
            $item->country_name = transtable('country.'.$item->country_i);
        }
        return $this->success($countries);
    }


    //红包领取记录
    public function rewardRecord()
    {
        $logs = Cache::remember('reward_record', KeysHandler::RED_RECORD_EXPIRE, function () {
            $home = new HomeController();
            $name = $home->name;
            shuffle($name);
            $name = array_slice($name, 0, 20);
            $arr = [];
            $current = Carbon::now();
            foreach ($name as $k => $v) {
//                $name = sprintf('%s***%s', substr($item, 0, 2), substr($item, -2));
                $name = sprintf('%s****%s', mt_rand(901, 998), mt_rand(101, 999));
                $arr[$k]['phone'] = $name;
                $arr[$k]['red'] = rand(960,990)*100;
                $thistime = $current->subMinutes(rand(1,5));
                $arr[$k]['time'] = $thistime->format('Y-m-d H:i').':'.rand(10,60);
            }
            return array_values($arr);
        });

        $data = [
            'list' => $logs,
            'join_group_str' => Configuration::getConfig(null,'join_group_str'),
            'join_group_link' => Configuration::getConfig(null,'join_group_link')
        ];

        return $this->success($data);
    }


    //生成收益记录
    public function profitRecord()
    {
        $logs = Cache::remember('profit_record', KeysHandler::RED_LOG_EXPIRE, function () {
            $home = new HomeController();
            $name = $home->name;
            shuffle($name);
            $name = array_slice($name, 0, 20);
            $arr = [];
            $red_ladder = RedLadder::where('state', 1)->get();
            $red = [];
            foreach ($red_ladder as $val){
                $red[] = $val->red_withdrew_value;
            }
            foreach ($name as $k => $v) {
//                $name = sprintf('%s***%s', substr($item, 0, 2), substr($item, -2));
                $name = sprintf('%s***%s', mt_rand(6000, 9999), mt_rand(10, 99));
                $arr[$k]['avatar'] = mt_rand(1, 12);
                $arr[$k]['text'] = sprintf('%s has received ', $name);
                $arr[$k]['text'] = $name;
                $arr[$k]['red'] = $red[array_rand($red)];
            }
            return array_values($arr);
        });
        return $this->success($logs);
    }



    //生成收益记录
    public function profitLog()
    {
        $logs = Cache::remember('profit_logs', KeysHandler::RED_LOG_EXPIRE, function () {
            $home = new HomeController();
            $name = $home->name;
            shuffle($name);
            $name = array_slice($name, 0, 10);
            $pets = Pet::where('state', 1)->get(['name', 'profit'])->toArray();
            $arr = [];
            foreach ($name as $item) {
//                $name = sprintf('%s***%s', substr($item, 0, 2), substr($item, -2));
                $name = sprintf('%s***%s', mt_rand(6000, 9999), mt_rand(10, 99));
                $content = "%s complete cultivating %s to earn %s rs";
                $pet = $pets[array_rand($pets)];
                $arr[] = sprintf($content, $name, $pet['name'], $pet['profit'] / 100);
            }
            return $arr;
        });
        return $this->success($logs);
    }

    public function index(Request $request)
    {
        $this->getName()->buildMoney();
        foreach ($this->data as &$item) {
            $item['name'] = sprintf('%s***%s', substr($item['name'], 0, 2), substr($item['name'], -2));
        }
        return $this->success($this->data);
    }

    private function getName()
    {
        shuffle($this->name);
        $this->data = array_slice($this->name, 0, 10);
        return $this;
    }

    private function buildMoney()
    {
        $types = Type::getType()->toArray();

        $time = Carbon::now()->timestamp;
        foreach ($this->data as &$item) {
            $time = rand(0, 100) % 2 === 0 ? Carbon::parse($time)->subSeconds(rand(15, 60)) : Carbon::parse($time)->subMinutes(rand(1, 9));
            $item = [
                'name' => $item,
                'profit' => rand(100, 999) * 100,
                'created_at' => $time->timestamp,
                'task' => call_user_func(function () use ($types) {
                    $type = $types[array_rand($types, 1)];
                    return [
                        'name' => $type['name'],
                        'icon' => $type['icon']
                    ];
                })
            ];
        }
        return $this;
    }



    /**
     * 消息推送-sse-实时推送
     */
    public function realTimeMessagePush(Request $request){
        $userId = $request->get("userId")??0; # 因多个用户，向指定用户推送
        ob_end_clean(); #函数清除,该函数清除php缓冲区内的内容,并且关闭输出缓冲区
        $response =  response()->stream(function () use ($userId){
            while (true) {
                #推送缓存
                # 将数据存储到redis-list中，缓存10秒，实现当用户在线时推送，不在线不推送
                $ping = false;
                $messageRedis = Redis::lpop("mailMessage:".$userId);
                if ($messageRedis){
                    $messageRedis = json_decode($messageRedis,true);
                    if (isset($userId)){
                        #echo "retry: 2000\n";#失败重连（毫秒）
                        echo 'data: '.json_encode($messageRedis)."\n";
                        echo "\n";
                    }
                }else{
                    $ping = true;
                    echo 'data: '.json_encode(["event"=>"ping"])."\n";
                    echo "\n";
                }

                if(ob_get_level()>0){
                    ob_flush();
                }
                flush();
                if (connection_aborted()) {break;}
                sleep(5);
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Content-Type' => 'text/event-stream',
        ]);
        return $response;
    }

    public function error(Request $request){
        $text = $request->text;
        info('客户端上报:'.$text);
        return $this->success();
    }



}
