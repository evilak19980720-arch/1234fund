<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\ImageUploadHandler;
use App\Http\Requests\Api\v1\ImageRequest;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagesController extends Controller
{
    public function store(ImageRequest $request, ImageUploadHandler $uploader)
    {
        $user = $request->user();
        $type = $request->type;
        $prefix_path = $type . '/';
        $path = $prefix_path . Carbon::now()->format('Ymd');
        $result = Storage::disk('local')->put($path, $request->file('image'));
        if ($result === false) {
            $this->errorResponse(Response::HTTP_OK, transtable('30001'), 30001);
        }

        return $this->success($result);
    }
}
