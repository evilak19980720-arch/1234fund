<?php

namespace App\Http\Resources;

use App\Models\Issue;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CrowdResource extends JsonResource
{
    public function toArray($request)
    {
        $this->resource->makeHidden('pet_id');

        $data = parent::toArray($request);
        $data['pet']['image'] = sprintf('%s/%s', aliyun_domain(), $data['pet']['image']);

        if (!$data['issues']) {
            $data['issues'] = Issue::select('id', 'crowd_id', 'issue', 'portion', 'total_price', 'bought', 'start_time', 'end_time')
                ->where('start_time', '>', Carbon::now())
                ->where('crowd_id', '=', $data['id'])
                ->limit(1)
                ->orderBy('start_time','asc')
                ->get();
        }

        if ($data['issues']) {
            $data['issue'] = $data['issues'][0];
            unset($data['issue']['crowd_id']);

            $data['issue']['start_time'] = Carbon::parse($data['issues'][0]['start_time'])->timestamp;
            $data['issue']['end_time'] = Carbon::parse($data['issues'][0]['end_time'])->timestamp;
        } else {
            unset($data);
        }

        $data['now'] = Carbon::now()->timestamp;

        unset($data['issues']);

        return $data;
    }
}
