<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\CardRequest;
use App\Http\Requests\Api\v1\CardEditRequest;
use App\Http\Resources\CardResource;
use App\Models\Bank;
use App\Models\Card;
use App\Models\UserEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CardController extends Controller
{
    private $key = 'card';

    // 个人银行卡
    public function index(Request $request)
    {
        $user = $request->user();
        $key = sprintf('%s:%d',$this->key,$user->id);
        if (!Cache::has($key)) {
            $cards = $user->cards()->get();
            $cards && Cache::put($key,$cards,60);
        }

        return $this->success(CardResource::collection(Cache::get($key)));
    }


    // 绑定银行卡
    public function store(CardRequest $request)
    {
        $user = $request->user();

        //$data = $request->all(['number','username','bank_id','phone','email','province','city','ifsc','address']);
        $data = $request->all(['number','currery','username','ifsc','email','phone']);
        $data['province'] = $data['city'] = $data['address'] = '';
        if(!isset($data['currery']) || empty($data['currery'])){
            $data['currery'] = Card::DEFAULT_CURRERY;
        }
        $data['bank_id'] = 0;
        $number = $data['number'];
        if(in_array($data['currery'],['TRX','USDT-TRC']) && !isValidTRCAddress($number)){
            $this->errorResponse(Response::HTTP_OK,transtable('10036'),10036);
        }
        if(in_array($data['currery'],['USDT-BSC']) && !isValidBSCAddress($number)){
            $this->errorResponse(Response::HTTP_OK,transtable('10036'),10036);
        }

        if(Card::where('number','=',$data['number'])->where('currery','=',$data['currery'])->first()){
            $this->errorResponse(Response::HTTP_OK,transtable('10018'),10018);
        }

        if (!isset($data['username']) || empty($data['username'])){ $data['username'] = ""; }
        if (!isset($data['phone']) || empty($data['phone'])){ $data['phone'] = ""; }
        if (!isset($data['email']) || empty($data['email'])){ $data['email'] = ""; }
        if (!isset($data['ifsc']) || empty($data['ifsc'])){ $data['ifsc'] = ""; }
        if (!isset($data['bank_id']) || empty($data['bank_id'])){ $data['bank_id'] = 0; }

        $card = DB::transaction(function () use ($user,$data) {
            if ($user->cards()->count() == 0) {
                $data['default_card'] = 1;
            }else{
                $data['default_card'] = 0;
            }
            $card =  $user->cards()->create($data);
            UserEvent::insertEvent($user, UserEvent::BINDCARD);
            return $card;
        });


        /*
        if(Card::where('number','=',$data['number'])->where('currery','=',$data['currery'])->where('user_id','<>',$user->id)->withTrashed()->first()){
            $this->errorResponse(Response::HTTP_OK,transtable('10018'),10018);
        }
        $card = DB::transaction(function () use ($user,$data) {
            if ($user->cards()->where('currery','=',$data['currery'])->count() != 0) {
                $user->cards()->where('currery','=',$data['currery'])->delete();
            }
            $card =  $user->cards()->create($data);
            UserEvent::insertEvent($user, UserEvent::BINDCARD);
            return $card;
        });
*/
        return $this->success(new CardResource($card));
    }




    // 银行列表
    public function bank(Request $request)
    {
        return $this->success(Bank::getBank());
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Card $card)
    {
        //$this->authorize('view', $card);
        $card->bank;
        return $this->success($card);
    }


    public function editcards(CardEditRequest $request){
        $user = $request->user();
        $card_id = $request->card_id;
        $attributes = $request->all(['card_id','number','username','bank_id','phone','email','ifsc','default_card']);
        $card = Card::query()->where('id','=',$card_id)->where('user_id','=',$user->id)->first();
        if(Card::where('number','=',$attributes['number'])->where('currery','=',$card->currery)->where('id','<>',$card_id)->first()){
            $this->errorResponse(Response::HTTP_OK, transtable('10018'), 10018);
        }


        if (isset($attributes['bank_id']) && !empty($attributes['bank_id'])){
            $card->bank_id = $attributes['bank_id'];
        }
        if (isset($attributes['number']) && !empty($attributes['number'])){
            if(in_array($card->currery,['TRX','USDT-TRC'])){
                if(!isValidTRCAddress($attributes['number'])){
                    $this->errorResponse(Response::HTTP_OK,transtable('10036'),10036);
                }
            }
            $card->number = $attributes['number'];
        }

        if (isset($attributes['number']) && !empty($attributes['number'])){
            if(in_array($card->currery,['USDT-BSC'])){
                if(!isValidBSCAddress($attributes['number'])){
                    $this->errorResponse(Response::HTTP_OK,transtable('10036'),10036);
                }
            }
            $card->number = $attributes['number'];
        }

        if (isset($attributes['phone']) && !empty($attributes['phone'])){
            $card->phone = $attributes['phone'];
        }
        if (isset($attributes['username']) && !empty($attributes['username'])){
            $card->username = $attributes['username'];
        }
        if (isset($attributes['email']) && !empty($attributes['email'])){
            $card->email = $attributes['email'];
        }
        if (isset($attributes['ifsc']) && !empty($attributes['ifsc'])){
            $card->ifsc = $attributes['ifsc'];
        }
        if (isset($attributes['default_card']) && $attributes['default_card']==1){
            Card::where('user_id','=',$user->id)->update(['default_card'=>0]);
            $card->default_card = 1;
        }
        $card->save();
        return $this->success();
    }


}
