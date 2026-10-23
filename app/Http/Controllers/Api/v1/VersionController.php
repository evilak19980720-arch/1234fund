<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\VersionRequest;
use App\Http\Resources\VersionResource;
use App\Models\Configuration;
use App\Models\Version;
use App\Models\AgentConf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VersionController extends Controller
{
    public function index(VersionRequest $request)
    {
        if (Cache::has($request->type) === false) {
            $id = Version::where('type', $request->type)->max('id');
            $version = Version::where('id',$id)->first();

            Cache::put($request->type,[
                'id' => $version->id,
                'version' => $version->version,
                'version_num' => $version->version_num,
                'path' => $version->path,
                'explain' => $version->explain,
                'force' => $version->force,
                'created_at' => $version->created_at->timestamp
            ]);
        }
        $version = Cache::get($request->type);

        if($request->type == 'ios'){
            $agent = AgentConf::isAgent();
            if($agent !== false){
                $version['path'] = $agent->h5_link;
            }
        }
        $version['h5'] = app(AgentConf::class)->getH5Url(auth('api')->user());
        $agent_conf = app(AgentConf::class)->getAgentConf();
        $version['join_group_link'] = $agent_conf ? $agent_conf['join_group_link'] : Configuration::getConfig(auth('api')->user(), 'join_group_link');
        return $this->success(new VersionResource($version));
    }

    public function all()
    {
        return $this->success(Version::getversion());
    }


}
