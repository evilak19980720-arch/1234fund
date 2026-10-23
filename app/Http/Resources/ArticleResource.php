<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Article */
class ArticleResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title' => transtableadmin('articles.title.'.$this->id), //$this->title
            'content' => transtableadmin('articles.content.'.$this->id.'.html'), //$this->content,
            'page_views' => $this->page_views,
            'created_at' => $this->created_at->timestamp,
        ];
    }
}
