<?php

namespace App\Models;

use App\Events\BuyPetSuccessEvent;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;


class UserEvent extends Model
{
    use DefaultDatetimeFormat;

    const BINDCARD = "bindcard";
    const FIRST_BINDCARD = "first_bindcard";

    const PETORDER = "petorder";
    const FIRST_PETORDER = "first_petorder";

    const DOWNLOAD = "downapp";
    const FIRST_DOWNLOAD = "first_downapp";

    protected $fillable = [
        'user_id', 'event_name'
    ];
    //const CacheTag = "user_interest_logs";

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function insertEvent($user, $event, $attch = []){
        $data['user_id'] = $user->id;
        switch ($event){
            case self::BINDCARD:
                $data['event_name'] = self::FIRST_BINDCARD;
                if(self::query()->where('user_id',$data['user_id'])->where('event_name',$data['event_name'])->doesntExist()){
                    self::create($data);
                    User::query()->where('id',$user->parent_id)->increment('sub_first_bindcard');
                }
                break;
            case self::DOWNLOAD:
                $data['event_name'] = self::FIRST_DOWNLOAD;
                if(self::query()->where('user_id',$data['user_id'])->where('event_name',$data['event_name'])->doesntExist()){
                    self::create($data);
                    //这里验证是否有效下级
                    $user = User::query()->where('id',$data['user_id'])->first();
                    if(!empty($user->device_number)){
                        $isValid = checkDeviceValid($user->device_number);
                        if($isValid === true){
                            User::query()->where('id',$user->parent_id)->increment('sub_first_downapp');
                        }
                    }
                }
                break;
            case self::PETORDER:
                $data['event_name'] = self::FIRST_PETORDER;
                if(self::query()->where('user_id',$data['user_id'])->where('event_name',$data['event_name'])->doesntExist()){
                    self::create($data);
                    User::query()->where('id',$user->parent_id)->increment('sub_first_petorder');
                    //购买成功的事件,首次购买 执行升级VIPEVENT
                    event(new BuyPetSuccessEvent($user));

                    //参与代理争霸活动
                    AgencyContestUser::userJoin($user);

                }
                break;
            default:
                //暂无其它事件
                break;

        }
    }




}
