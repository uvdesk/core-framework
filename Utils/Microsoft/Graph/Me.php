<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Utils\Microsoft\Graph;

class Me
{
    const BASE_ENDPOINT = "https://graph.microsoft.com/v1.0/me";

    public static function me($accessToken)
    {
    	$endpoint = self::BASE_ENDPOINT;

        $curlHandler = curl_init();

        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken, 
        ]);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_URL, $endpoint);

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);

        if (curl_errno($curlHandler)) {
            $error_msg = curl_error($curlHandler);
        }

        curl_close($curlHandler);

        return $jsonResponse;
    }

    public static function messages($accessToken, array $filters = [])
    {
        $resolvedFilters = [];

        foreach ($filters as $filter => $details) {
            switch ($details['operation']) {
                case '>':
                    $resolvedFilters[] = sprintf("%s ge %s", $filter, $details['value']);

                    break;
                default:
                    break;
            }
        }

    	$endpoint = self::BASE_ENDPOINT . '/messages?$count=true';

        if (!empty($resolvedFilters)) {
            $endpoint .= "&\$filter=" . urlencode(implode(' and ', $resolvedFilters));
        }

        $curlHandler = curl_init();

        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken, 
        ]);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_URL, $endpoint);

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);

        if (curl_errno($curlHandler)) {
            $error_msg = curl_error($curlHandler);
        }

        curl_close($curlHandler);

        return $jsonResponse;
    }

    public static function message($id, $accessToken)
    {
    	$endpoint = self::BASE_ENDPOINT . "/messages/$id";

        $curlHandler = curl_init();

        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken, 
        ]);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_URL, $endpoint);

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);

        if (curl_errno($curlHandler)) {
            $error_msg = curl_error($curlHandler);
        }

        curl_close($curlHandler);

        return $jsonResponse;
    }
}
