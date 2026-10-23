<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 文章
 * @package App\Http\Controllers\Api\v1
 */
class ArticleController extends Controller
{

    /**
     * 文章列表
     * @return \Illuminate\Http\JsonResponse|object
     */
    public function index(Request $request)
    {
        $pageSize = $request->get('page_size', 10);
        $query = Article::query();
        if (!empty($request->key)) {
            $query->where('key', $request->key);
        }
        $list = $query->orderByDesc('sort')->orderByDesc('created_at')->paginate($pageSize);

        $data = [
            'list' => ArticleResource::collection($list->items()),
            'paginate' => $this->paginate($list),
        ];

        return $this->success($data);
    }

    public function show(Request $request)
    {
        if (!$request->has('id') && !$request->has('key')) {
            return $this->success([]);
        }
        $article = Article::query()->when($request->get('id'), function (Builder $builder) use ($request) {
            $builder->where('id', '=', $request->get('id'));
        })->when($request->get('key'), function (Builder $builder) use ($request) {
            $builder->where('key', '=', $request->get('key'));
        })->first();
        if ($article) {
            $article->increment('page_views', rand(1, 3));
        }
        return $this->success($article ? new ArticleResource($article) : []);

    }

}
