<?php

namespace App\Filesystem;

use Illuminate\Support\Facades\Log;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use OSS\Core\OssException;
use OSS\OssClient;
use Throwable;

/**
 * 阿里云 OSS 的 Flysystem 3 适配器
 *
 * 用来替换只支持 Flysystem 1 的 jacobcyl/ali-oss-storage，
 * 配置项、URL 拼接规则和读写行为都与原扩展保持一致（包括项目里对 getUrl 的改动：不检查文件是否存在）。
 */
class AliOssAdapter implements FilesystemAdapter
{
    protected static $metaOptions = [
        'CacheControl',
        'Expires',
        'ServerSideEncryption',
        'Metadata',
        'ACL',
        'ContentType',
        'ContentDisposition',
        'ContentLanguage',
        'ContentEncoding',
    ];

    protected static $metaMap = [
        'CacheControl'         => 'Cache-Control',
        'Expires'              => 'Expires',
        'ServerSideEncryption' => 'x-oss-server-side-encryption',
        'Metadata'             => 'x-oss-metadata-directive',
        'ACL'                  => 'x-oss-object-acl',
        'ContentType'          => 'Content-Type',
        'ContentDisposition'   => 'Content-Disposition',
        'ContentLanguage'      => 'response-content-language',
        'ContentEncoding'      => 'Content-Encoding',
    ];

    protected $client;

    protected $bucket;

    protected $endPoint;

    protected $cdnDomain;

    protected $ssl;

    protected $isCname;

    protected $debug;

    protected $options = [
        'Multipart' => 128,
    ];

    protected $mimeTypeDetector;

    public function __construct(OssClient $client, $bucket, $endPoint, $ssl, $isCname = false, $debug = false, $cdnDomain = '', array $options = [])
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->endPoint = $endPoint;
        $this->ssl = $ssl;
        $this->isCname = $isCname;
        $this->debug = $debug;
        $this->cdnDomain = $cdnDomain;
        $this->options = array_merge($this->options, $options);
        $this->mimeTypeDetector = new FinfoMimeTypeDetector();
    }

    public function getBucket()
    {
        return $this->bucket;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $path);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $listObjectInfo = $this->client->listObjects($this->bucket, [
                'prefix'   => rtrim($path, '/') . '/',
                'max-keys' => 1,
            ]);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToCheckExistence::forLocation($path, $e);
        }

        return !empty($listObjectInfo->getObjectList()) || !empty($listObjectInfo->getPrefixList());
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $options = $this->getOptions($this->options, $config);

        if (!isset($options[OssClient::OSS_LENGTH])) {
            $options[OssClient::OSS_LENGTH] = strlen($contents);
        }
        if (!isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = $this->mimeTypeDetector->detectMimeType($path, $contents);
        }

        try {
            $this->client->putObject($this->bucket, $path, $contents, $options);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        try {
            return (string) $this->client->getObject($this->bucket, $path);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject($this->bucket, $path);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $dirname = rtrim($path, '/') . '/';

        try {
            $dirObjects = $this->listDirObjects($dirname, true);

            if (count($dirObjects['objects']) > 0) {
                $objects = [];
                foreach ($dirObjects['objects'] as $object) {
                    $objects[] = $object['Key'];
                }
                $this->client->deleteObjects($this->bucket, $objects);
            }

            $this->client->deleteObject($this->bucket, $dirname);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->client->createObjectDir($this->bucket, $path, $this->getOptionsFromConfig($config));
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $acl = $visibility === Visibility::PUBLIC ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;

        try {
            $this->client->putObjectAcl($this->bucket, $path, $acl);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToSetVisibility::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        try {
            $acl = $this->client->getObjectAcl($this->bucket, $path);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToRetrieveMetadata::visibility($path, $e->getMessage(), $e);
        }

        $visibility = $acl == OssClient::OSS_ACL_TYPE_PUBLIC_READ ? Visibility::PUBLIC : Visibility::PRIVATE;

        return new FileAttributes($path, null, $visibility);
    }

    public function mimeType(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path, 'mimeType');

        return new FileAttributes($path, null, null, null, $meta['content-type'] ?? null);
    }

    public function lastModified(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path, 'lastModified');
        $timestamp = isset($meta['last-modified']) ? strtotime($meta['last-modified']) : false;

        return new FileAttributes($path, null, null, $timestamp ?: null);
    }

    public function fileSize(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path, 'fileSize');

        return new FileAttributes($path, isset($meta['content-length']) ? (int) $meta['content-length'] : null);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        // 与原扩展一致：先递归取出前缀下的全部对象，补出中间目录，
        // 再按 Flysystem 1 ContentListingFormatter 的规则过滤目录范围并按路径排序
        $dirObjects = $this->listDirObjects($path, true);

        $entries = [];
        $directories = [];
        $listedDirectories = [];

        foreach ($dirObjects['objects'] as $object) {
            $key = $object['Key'];
            $timestamp = isset($object['LastModified']) ? strtotime($object['LastModified']) : false;

            if (substr($key, -1) === '/') {
                $key = rtrim($key, '/');
                $listedDirectories[] = $key;
                $entries[$key] = new DirectoryAttributes($key, null, $timestamp ?: null);
            } else {
                $entries[$key] = new FileAttributes($key, isset($object['Size']) ? (int) $object['Size'] : null, null, $timestamp ?: null);
            }

            $parent = $this->dirname($key);
            while ($parent !== '' && !in_array($parent, $directories)) {
                $directories[] = $parent;
                $parent = $this->dirname($parent);
            }
        }

        foreach (array_diff($directories, $listedDirectories) as $directory) {
            $entries[$directory] = new DirectoryAttributes($directory);
        }

        $entries = array_filter($entries, function ($entry) use ($path, $deep) {
            $entryPath = $entry->path();

            if ($entryPath === '') {
                return false;
            }

            if ($deep) {
                return $path === '' || strpos($entryPath, $path . '/') === 0;
            }

            return $this->dirname($entryPath) === $path;
        });

        usort($entries, function ($a, $b) {
            return strcmp($a->path(), $b->path());
        });

        return $entries;
    }

    /**
     * 同 Flysystem 1 的 Util::dirname：根目录返回空字符串
     */
    protected function dirname($path)
    {
        $dirname = dirname($path);

        return $dirname === '.' ? '' : $dirname;
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->client->copyObject($this->bucket, $source, $this->bucket, $destination);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * 生成文件访问地址（Laravel 的 Storage::url() 会调用这里）
     */
    public function getUrl($path)
    {
        return ($this->ssl ? 'https://' : 'http://') . ($this->isCname ? ($this->cdnDomain == '' ? $this->endPoint : $this->cdnDomain) : $this->bucket . '.' . $this->endPoint) . '/' . ltrim($path, '/');
    }

    /**
     * 列举文件夹内文件列表；可递归获取子文件夹
     */
    public function listDirObjects($dirname = '', $recursive = false)
    {
        $delimiter = '/';
        $nextMarker = '';
        $maxkeys = 1000;

        $result = [];

        while (true) {
            $options = [
                'delimiter' => $delimiter,
                'prefix'    => $dirname,
                'max-keys'  => $maxkeys,
                'marker'    => $nextMarker,
            ];

            try {
                $listObjectInfo = $this->client->listObjects($this->bucket, $options);
            } catch (OssException $e) {
                $this->logErr(__FUNCTION__, $e);
                throw $e;
            }

            $nextMarker = $listObjectInfo->getNextMarker();
            $objectList = $listObjectInfo->getObjectList();
            $prefixList = $listObjectInfo->getPrefixList();

            if (!empty($objectList)) {
                foreach ($objectList as $objectInfo) {
                    $object['Prefix']       = $dirname;
                    $object['Key']          = $objectInfo->getKey();
                    $object['LastModified'] = $objectInfo->getLastModified();
                    $object['eTag']         = $objectInfo->getETag();
                    $object['Type']         = $objectInfo->getType();
                    $object['Size']         = $objectInfo->getSize();
                    $object['StorageClass'] = $objectInfo->getStorageClass();

                    $result['objects'][] = $object;
                }
            } else {
                $result['objects'] = [];
            }

            if (!empty($prefixList)) {
                foreach ($prefixList as $prefixInfo) {
                    $result['prefix'][] = $prefixInfo->getPrefix();
                }
            } else {
                $result['prefix'] = [];
            }

            if ($recursive) {
                foreach ($result['prefix'] as $pfix) {
                    $next = $this->listDirObjects($pfix, $recursive);
                    $result['objects'] = array_merge($result['objects'], $next['objects']);
                }
            }

            if ($nextMarker === '') {
                break;
            }
        }

        return $result;
    }

    protected function getMetadata(string $path, string $type)
    {
        try {
            return $this->client->getObjectMeta($this->bucket, $path);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            throw UnableToRetrieveMetadata::create($path, $type, $e->getMessage(), $e);
        }
    }

    protected function getOptions(array $options = [], ?Config $config = null)
    {
        $options = array_merge($this->options, $options);

        if ($config) {
            $options = array_merge($options, $this->getOptionsFromConfig($config));
        }

        return [OssClient::OSS_HEADERS => $options];
    }

    protected function getOptionsFromConfig(Config $config)
    {
        $options = [];

        foreach (static::$metaOptions as $option) {
            if ($config->get($option) === null) {
                continue;
            }
            $options[static::$metaMap[$option]] = $config->get($option);
        }

        if ($visibility = $config->get('visibility')) {
            $options['x-oss-object-acl'] = $visibility === Visibility::PUBLIC ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;
        }

        if ($mimetype = $config->get('mimetype')) {
            $options['Content-Type'] = $mimetype;
        }

        return $options;
    }

    protected function logErr($fun, $e)
    {
        if ($this->debug) {
            Log::error($fun . ': FAILED');
            Log::error($e->getMessage());
        }
    }
}
