<?php

namespace Ufree\LaravelDogeCloud;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class DogeCloud
{
    const CACHE_KEY = 'dogecloud';

    public static function initConfig(): void
    {
        Config::set('filesystems.disks.doge', [
            'driver' => 'doge',
        ]);
        self::refreshDogeCloudToken();
    }

    /**
     * @throws Exception|GuzzleException
     */
    public static function refreshDogeCloudToken(): void
    {

        $token = self::getCacheValue('token');
        if (empty($token)) {
            $data = self::tmpToken();
            self::setCacheValue('token', $data['sessionToken']);
            self::setCacheValue('access_key_id', $data['accessKeyId']);
            self::setCacheValue('secret_access_key', $data['secretAccessKey']);
        }
    }

    /**
     * @return array
     * @throws Exception|GuzzleException
     */
    public static function tmpToken(): array
    {
        $data = self::api('/auth/tmp_token.json', array(
            "channel" => "OSS_FULL",
            "scopes" => array("*")
        ));
        return $data['Credentials'];
    }

    /**
     * @throws GuzzleException
     * @throws DogeCloudException
     */
    public static function api($apiPath, $data = array())
    {
        $client = new Client([
            'base_uri' => 'https://api.dogecloud.com',
            'timeout' => 2.0
        ]);
        $accessKey = config('dogecloud.access_key');
        $secretKey = config('dogecloud.secret_key');
        $signStr = $apiPath . "\n" . json_encode($data);
        $sign = hash_hmac('sha1', $signStr, $secretKey);
        $Authorization = "TOKEN " . $accessKey . ":" . $sign;
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => $Authorization
        ];
        $rep = $client->post($apiPath, [
            'json' => $data,
            'headers' => $headers
        ]);
        if ($rep->getStatusCode() !== 200) {
            $content = 'http request error.' . "\n";
            $content .= 'error code:' . $rep->getStatusCode() . "\n";
            $content .= 'response content:' . $rep->getBody() . "\n";
            throw new DogeCloudException($content);
        }
        $repData = json_decode($rep->getBody()->getContents(), true) ?? '';
        if (empty($repData)) {
            $content = 'doge cloud Server not responding to data.' . "\n";
            throw new DogeCloudException($content);
        }
        if (!empty($repData['code']) && $repData['code'] != 200) {
            $content = 'doge cloud api error.' . "\n";
            $content .= 'error code:' . $repData['err_code'] . "\n";
            $content .= 'response content:' . $repData['msg'] . "\n";
            throw new DogeCloudException($content);
        }
        return $repData['data'];
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected static function getCacheValue(string $key): mixed
    {
        return Cache::get(self::CACHE_KEY . '.' . $key);
    }

    /**
     * @param string $key
     * @param mixed $val
     * @return bool
     */
    protected static function setCacheValue(string $key, mixed $val): bool
    {
        return Cache::put(self::CACHE_KEY . '.' . $key, $val, now()->addHours(2));
    }

    /**
     * @return Repository|Application|mixed|string[]
     */
    public static function getConfig(): mixed
    {
        $config = config('dogecloud');
        $config += ['version' => 'latest'];
        $config['key'] = self::getCacheValue('access_key_id');
        $config['secret'] = self::getCacheValue('secret_access_key');
        $config['token'] = self::getCacheValue('token');
        $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        unset($config['token'],$config['access_key'], $config['secret_key']);
        return $config;
    }
}
