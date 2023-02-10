<?php

namespace Ufree\LaravelDogeCloud;

use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Arr;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter as AwsS3PortableVisibilityConverter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Visibility;

class DogeCloudAdapter extends AwsS3V3Adapter
{
    public function __construct(array $config)
    {
        $root = $config['root'] ?? '';
        $visibility = new AwsS3PortableVisibilityConverter(
            $config['visibility'] ?? Visibility::PUBLIC
        );
        $streamReads = $config['stream_reads'] ?? false;
        $client = new S3Client($config);
        $adapter = new S3Adapter($client, $config['bucket'], $root, $visibility, null, $config['options'] ?? [], $streamReads);
        $driver = new Flysystem($adapter, Arr::only($config, [
            'directory_visibility',
            'disable_asserts',
            'temporary_url',
            'url',
            'visibility',
        ]));
        parent::__construct($driver, $adapter, $config, $client);
    }
}
