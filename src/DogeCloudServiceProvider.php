<?php

namespace Ufree\LaravelDogeCloud;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;

class DogeCloudServiceProvider extends ServiceProvider
{

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__) . '/config/dogecloud.php' => config_path('dogecloud.php'),
            ], 'dogecloud-config');
        }
        DogeCloud::initConfig();
        DogeCloud::refreshDogeCloudToken();
        Storage::extend('doge', function () {
            $config = DogeCloud::getConfig();
            $adapter = new DogeCloudAdapter($config);
            $driver = new Flysystem($adapter, Arr::only($config, [
                'directory_visibility',
                'disable_asserts',
                'temporary_url',
                'url',
                'visibility',
            ]));
            return new AwsS3V3Adapter($driver, $adapter, $config, $adapter->getClient());
        });

    }
}
