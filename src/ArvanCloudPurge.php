<?php


namespace Drupal\ar_drplugin;


use GuzzleHttp\Exception\RequestException;

class ArvanCloudPurge
{
    /**
     * Function to get response.
     *
     * @param string $zoneId
     *   CF zone ID.
     * @param string $authorization
     *   CF authorization.
     *
     * @return int
     *   Return code status.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function arPurgeCache(string $domain, string $apiKey,$urls=null) {
        $url = "https://napi.arvancloud.com/cdn/4.0/domains/{$domain}/caching?purge=all";
        $method = 'DELETE';

        try {
            $client = \Drupal::httpClient();
            $options = [
                'headers' => [
                    'Authorization' => $apiKey,
                ],
            ];
            $response = $client->request($method, $url, $options);
            $code = $response->getStatusCode();
            if ($code == 200) {
                return $code;
            }
        }
        catch (RequestException $e) {
            watchdog_exception('ar_drplugin', $e);
        }

    }
    public static function setStatus(string $domain,string $apiKey,string $status){
        $url = "https://napi.arvancloud.com/cdn/4.0/domains/{$domain}/caching";
        $method = 'PATCH';

        try {
            $client = \Drupal::httpClient();
            $options = [
                'json' => [
                    'cache_status' => $status,
                ],
                'headers' => [
                    'Authorization' => $apiKey,
                ],
            ];
            $response = $client->request($method, $url, $options);
            $code = $response->getStatusCode();
            if ($code == 200) {
                return $code;
            }
        }
        catch (RequestException $e) {
            watchdog_exception('ar_drplugin', $e);
        }
    }
}