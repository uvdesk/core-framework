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

    public static function messages($accessToken)
    {
    	$endpoint = self::BASE_ENDPOINT . "/messages";

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
