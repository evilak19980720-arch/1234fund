<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Env;

class TelegramCrond extends Model
{

    //https://core.telegram.org/bots/api#formatting-options

    use DefaultDatetimeFormat;
    protected $table = 'telegram_cronds';
    protected $fillable = [
        'id', 'send_time', 'group_id', 'status', 'last_send_time', 'type'
    ];

    /*
    public function group(): BelongsTo
    {
        return $this->belongsTo(TelegramGroup::class,'group_id','id');
    }
    */

    protected $repeat_num = 5;
    protected $send_num = 0;
    public $content;

    //普通群发消息
    //整体引用计划 {$jihua}
    //开场白：{$kaichang}
    //结束语:{$jieshu}
    public function sendMsg(){
        //info($this->id);
        $group_ids = $this->group_id;
        $group_id_arr = explode(',', $group_ids);
        foreach ($group_id_arr as $group_id){
            self::sendMessageGroup($group_id,$this->type);
            $this->last_send_time = Carbon::now();
            $this->save();

        }
    }


    public static function sendMessageGroup($group_id ,$type){
        $group = TelegramGroup::query()->with(['bot','template'])->where('id','=',$group_id)->first();
        if($group){
            $param['chat_id'] = $group->chart_id;
            $param['parse_mode'] = 'MarkdownV2';
            $bot = $group->bot->bot_token;
            if($type == 'ad'){
                $paramarr['invite_link'] = escapeSpecificChars(Env::get('H5URL').'/register?invite_code=' . $group->agent_id, ['.','=','/','_']);
                info($paramarr['invite_link']);
                $send_content = stringParsingVariable($group->template->content, $paramarr);
                $send_content = telegramGroupChartcontentMarkdownV2($send_content);
                $param['text'] = $param['caption'] = $send_content;
                $url = "https://api.telegram.org/bot{$bot}/sendMessage";
                self::sendMsgTelegram($param, $url);
            }elseif($type == 'share'){
                $url = "https://api.telegram.org/bot{$bot}/sendPhoto";
                //查询图片
                if(!empty($group->last_send_time)){
                    $last_send_time = $group->last_send_time;
                    $friends = Friend::query()->with(['imgs'])->where('ok','=',1)->where('created_at','>', $last_send_time)->get();
                }else{
                    $friends = Friend::query()->with(['imgs'])->where('ok','=',1)->get();
                }

                $photos = [];
                $shuiyin = sprintf('?x-oss-process=image/watermark,size_60,text_%s,color_bbbbbb,shadow_10,t_100,rotate_0,g_se,x_15,y_15',config('app.friend_shuiyin'));
                foreach ($friends as $friend){
                    if(!empty($friend->imgs)){
                        foreach ($friend->imgs as $img){
                            $photos[] =sprintf('%s/%s', aliyun_domain(), $img->img. $shuiyin);
                        }
                    }
                }
                //循环发送
                foreach ($photos as $photo){
                    $param['photo'] = $photo;
                    self::sendMsgTelegram($param, $url);
                }
                $group->last_send_share_time = Carbon::now();
                $group->save();
            }

        }
    }


    protected static function sendMsgTelegram($param, $url){
        try {
            $response = Http::asForm()->post($url, $param);
            $result = json_decode($response->body(), true);
            info('群发结果'.json_encode($result,JSON_UNESCAPED_UNICODE));
            if($result['ok'] == true){

                //
                return true;
            }else{
                info('群发失败1'.json_encode($result,JSON_UNESCAPED_UNICODE));
                info('群发失败内容1'.json_encode($param,JSON_UNESCAPED_UNICODE));
                //{"ok":false,"error_code":401,"description":"Unauthorized"}
                //if($result['ok'] == false && $result['error_code'] == 401 && $result['description'] == 'Unauthorized'){
                //    $this->bot_key = $this->bot_key+1;
                //    return $this->sendMsgTelegram($param, $url);
                //}
            }
        }catch (Exception $e){
            info('群发失败2'.json_encode($result,JSON_UNESCAPED_UNICODE));
            info('群发失败内容2'.json_encode($param,JSON_UNESCAPED_UNICODE));
        }
    }








}
