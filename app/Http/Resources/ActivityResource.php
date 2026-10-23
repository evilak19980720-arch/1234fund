<?php

namespace App\Http\Resources;

use App\Handlers\KeysHandler;
use App\Models\ActivityUser;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class ActivityResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);
        if (!isset($data['received'])) {
            $key = sprintf(KeysHandler::ACTIVITY_RECEIVED, $data['id'], $request->user()->id);
            $received = Cache::get($key);
            if (!$received) {
                $received = ActivityUser::query()->where('activity_id', '=', $data['id'])
                    ->where('user_id', '=', $request->user()->id)
                    ->count();
                Cache::put($key, $received, 600);
            }
            $data['received'] = $received >= $data['num'];

            if($data['received']){
                $data['completed_number'] = $data['number'];
            }

        }
        $data['name'] = transtableadmin('activity.name.'.$this->id);
        unset($data['table'],$data['num'],$data['subordinate_buy_number'],$data['condition'],$data['pet_id']);
        return $data;
    }
}
