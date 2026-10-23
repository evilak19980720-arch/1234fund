<?php

namespace App\Http\Resources;

use App\Handlers\HtmlHandler;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticeResource extends JsonResource
{
    public function toArray($request)
    {
        $this->resource->makeHidden('updated_at','deleted_at')->toArray();
        $data = parent::toArray($request);

        $title = transtableadmin('notices.title.'.$this->id);
        if(!empty($title) && $title != null){
            $data['title'] = $title;
        }
        $data['top'] = $data['top'] ? true : false;
        if (isset($data['eject'])) {
            $data['eject'] = $data['eject'] ? true : false;
        }
        $data['created_at'] = $this->resource->created_at->timestamp;
        if(isset($data['content'])){
            $content = transtableadmin('notices.content.'.$this->id.'.html');
            if(empty($content) || $content == null){
                $content = $data['content'];
            }
            $data['content'] = HtmlHandler::makeContent($content);
        }
        return $data;
    }
}
