<?php

namespace Ufree\LaravelDogeCloud;

use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter as AwsS3PortableVisibilityConverter;
use League\Flysystem\Visibility;

class DogeCloudAdapter extends S3Adapter
{
    protected S3ClientInterface $clientCopy;
    public function __construct(array $config)
    {
        $root = $config['root'] ?? '';
        $visibility = new AwsS3PortableVisibilityConverter(
            $config['visibility'] ?? Visibility::PUBLIC
        );
        $streamReads = $config['stream_reads'] ?? false;
        $client = new S3Client($config);
        $this->clientCopy = $client;
        parent::__construct($client, $config['bucket'], $root, $visibility, null, $config['options'] ?? [], $streamReads);
    }

    /**
     * @return S3ClientInterface|S3Client
     */
    public function getClient(): S3ClientInterface|S3Client
    {
        return $this->clientCopy;
    }
}
