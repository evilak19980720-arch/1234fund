<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Http\Resources\RankingResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 排行榜
 * @package App\Http\Controllers\Api\v1
 */
class RankingController extends Controller
{
//    public function index(Request $request)
//    {
//        $page = $request->get('page', 1);
//        $limit = $request->get('limit', 10);
//        $page = $this->paging($page, $limit);
//
//        $users = User::orderBy('revenue','desc')->select('id','name','avatar','revenue','vip_id')->offset($page)->limit($limit)->get();
//        return $this->success(RankingResource::collection($users));
//    }

    public function index(Request $request)
    {
        $user = $request->user();
        $seed = Carbon::now()->timestamp % 2 === 0 ? rand(5, 15) * 1 : rand(5, 15) * -1;

        $data = Cache::get(KeysHandler::RANKING);
        if (!$data) {
            $names = [
                'Ada', 'Anna', 'Dinah', 'Eileen', 'Hilda', 'Iris', 'Lee', 'Nancy', 'Ruby', 'Una', 'Yuri', 'Tina', 'Susan', 'Rachel', 'Pearl', 'Aaron', 'Ace', 'Ari', 'Baron', 'Ben', 'Bruce', 'Charlie',
                'Colin', 'Evan', 'Gary', 'Guy', 'Harry', 'Jason', 'Jerry', 'Kevin', 'Kent', 'Leo', 'Marvin', 'Mike', 'Nick', 'Noah', 'Otto', 'Paul', 'Peter', 'Quincy', 'Robert', 'Roy', 'Sean', 'Tony',
                'Tyler', 'Will', 'Wright', 'Woodrow', 'York', 'Ziv'
            ];

            for ($i = 0; $i < 15; $i++) {
                $completed[] = rand(10,100) * $i + rand(100,1000);
                $profit[] = rand(100000,999999) * 100;
                $num[] = rand(100,999);
            }
            sort($profit);
            sort($completed);
            sort($num);

            $data = [];
            for ($i = 1; $i <= 15; $i++) {
                shuffle($names);

                array_push($data, [
                    'ranking' => $i,
                    'avatar' => $i,
                    'name' => call_user_func(function ($name) {
                        return sprintf('%s***%s', substr($name, 0, 2), substr($name, -2));
                    }, array_pop($names)),
                    'completed' => array_pop($completed),
                    'inviter_number' => array_pop($num),
                    'total_revenue' => array_pop($profit)
                ]);
            }

            Cache::put(KeysHandler::RANKING,$data,60*60*24*7);
        }

        $myRanking = Cache::get(sprintf(KeysHandler::USER_RANKING,$user->id));
        if (!$myRanking) {
            $myRanking = substr(round((Carbon::now()->timestamp / 100000)), -4) + $seed;
            Cache::put(sprintf(KeysHandler::USER_RANKING,$user->id),$myRanking,60*60*24*5);
        }

        return $this->success([
            'id' => $user->id,
            'avatar' => $user->avatar,
            'name' => $user->name,
            'ranking' => (int)$myRanking,
            'list' => $data
        ]);
    }
}
