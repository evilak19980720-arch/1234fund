<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\BannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index(BannerRequest $request)
    {
        $location = $request->get('type');
        if($location == 'down'){
            $location = 2;
        }
        $banner = Banner::getBanner($location);
        return $this->success(BannerResource::collection($banner));
    }
}


