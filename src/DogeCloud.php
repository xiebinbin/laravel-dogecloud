<?php

namespace Ufree\LaravelDogeCloud;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class DogeCloud
{
    /**
     * @throws \Exception
     */
    public static function refreshDogeCloudToken(): void
    {

        $token = Cache::get('dogecloud.token');
        if (empty($token)) {
            $api = self::dogeCloudApi('/auth/tmp_token.json', array(
                "channel" => "OSS_FULL",
                "scopes" => array("*")
            ), true);
            if ($api && $api['code'] == 200) {
                $credentials = $api['data']['Credentials'];
                Cache::put('dogecloud.token', $credentials['sessionToken'], now()->addHours(2));
                Cache::put('dogecloud.access_key_id', $credentials['accessKeyId'], now()->addHours(2));
                Cache::put('dogecloud.secret_access_key', $credentials['secretAccessKey'], now()->addHours(2));
            } else {
                // 失败
                throw new \Exception('doge token error');
            }
        }
    }
    public static function dogeCloudApi($apiPath, $data = array(), $jsonMode = false)
    {
        $accessKey = config('dogecloud.access_key');
        $secretKey = config('dogecloud.secret_key');
        dd(config('app'));
        $body = $jsonMode ? json_encode($data) : http_build_query($data);
        $signStr = $apiPath . "\n" . $body;
        $sign = hash_hmac('sha1', $signStr, $secretKey);
        $Authorization = "TOKEN " . $accessKey . ":" . $sign;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.dogecloud.com" . $apiPath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        if (isset($data) && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: ' . ($jsonMode ? 'application/json' : 'application/x-www-form-urlencoded'),
                'Authorization: ' . $Authorization
            ));
        }

        $ret = curl_exec($ch);
        curl_close($ch);
        return json_decode($ret, true);
    }
}
