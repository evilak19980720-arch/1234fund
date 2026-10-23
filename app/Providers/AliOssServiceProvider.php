<?php

namespace App\Providers;

use App\Filesystem\AliOssAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;

/**
 * 注册 oss 存储驱动（替代 jacobcyl/ali-oss-storage，配置项保持不变）
 */
class AliOssServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Storage::extend('oss', function ($app, $config) {
            $accessId  = $config['access_id'];
            $accessKey = $config['access_key'];

            $cdnDomain = empty($config['cdnDomain']) ? '' : $config['cdnDomain'];
            $bucket    = $config['bucket'];
            $ssl       = empty($config['ssl']) ? false : $config['ssl'];
            $isCname   = empty($config['isCName']) ? false : $config['isCName'];
            $debug     = empty($config['debug']) ? false : $config['debug'];

            $endPoint   = $config['endpoint']; // 默认作为外部节点
            $epInternal = $isCname ? $cdnDomain : (empty($config['endpoint_internal']) ? $endPoint : $config['endpoint_internal']); // 内部节点

            if ($debug) Log::debug('OSS config:', $config);

            $client  = new OssClient($accessId, $accessKey, $epInternal, $isCname);
            $adapter = new AliOssAdapter($client, $bucket, $endPoint, $ssl, $isCname, $debug, $cdnDomain);

            $filesystem = new Filesystem($adapter, Arr::only($config, ['directory_visibility', 'disk', 'temporary_url', 'url', 'visibility']));

            return new FilesystemAdapter($filesystem, $adapter, $config);
        });
    }
}
