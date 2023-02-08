<?php
namespace Ufree\LaravelDogeCloud;
use Aws\S3\S3Client;
use Exception;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter as AwsS3PortableVisibilityConverter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Visibility;

class DogeCloudServiceProvider extends ServiceProvider
{
    /**
     * @throws Exception
     */
    public function boot()
    {
        if($this->app->runningInConsole()){
            $this->publishes([
                __DIR__ . '/config/dogecloud.php' => config_path('dogecloud.php'),
            ]);
        }
        if(config('dogecloud.enable')){
            DogeCloud::refreshDogeCloudToken();
            Storage::extend('doge', function ($app, $config) {
                $config += ['version' => 'latest'];
                $config['key'] = Cache::get('dogecloud.access_key_id');
                $config['secret'] = Cache::get('dogecloud.secret_access_key');
                $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
                $root = $config['root'] ?? '';

                $visibility = new AwsS3PortableVisibilityConverter(
                    $config['visibility'] ?? Visibility::PUBLIC
                );
                $streamReads = $config['stream_reads'] ?? false;
                unset($config['token']);
                $client = new S3Client($config);

                $adapter = new S3Adapter($client, $config['bucket'], $root, $visibility, null, $config['options'] ?? [], $streamReads);
                if ($config['read-only'] ?? false === true) {
                    $adapter = new ReadOnlyFilesystemAdapter($adapter);
                }
                if (! empty($config['prefix'])) {
                    $adapter = new PathPrefixedAdapter($adapter, $config['prefix']);
                }
                $driver = new Flysystem($adapter, Arr::only($config, [
                    'directory_visibility',
                    'disable_asserts',
                    'temporary_url',
                    'url',
                    'visibility',
                ]));
                return new AwsS3V3Adapter($driver, $adapter, $config, $client);
            });
        }

    }
}
