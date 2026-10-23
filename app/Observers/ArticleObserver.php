<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Article;
use Illuminate\Support\Facades\Cache;

class ArticleObserver
{
    public function saved(Article $article)
    {
        Cache::forget(sprintf(KeysHandler::ARTICLE,$article->key));
    }

    public function deleted(Article $article)
    {
        Cache::forget(sprintf(KeysHandler::ARTICLE,$article->key));
    }
}
