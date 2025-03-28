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

    public static function mailFolders($accessToken, array $filters = [])
    {
    	$endpoint = self::BASE_ENDPOINT . '/mailFolders';

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

    public static function messages($accessToken, $mailFolderId = null, array $filters = [], $top = 100)
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

        if (empty($mailFolderId)) {
            $endpoint = self::BASE_ENDPOINT . "/messages?\$count=true&\$top=$top";
        } else {
            $endpoint = self::BASE_ENDPOINT . "/mailFolders/$mailFolderId/messages?\$count=true&\$top=$top";
        }

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

    public static function getMessagesWithNextLink($nextLink, $accessToken)
    {
        $curlHandler = curl_init();

        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_URL, $nextLink);

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
    	$endpoint = self::BASE_ENDPOINT . "/messages/".$id."?\$expand=attachments";
    
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


    public static function attachment($id, $attachmentId, $accessToken)
    {
    	$endpoint = self::BASE_ENDPOINT . "/messages/$id/attachment/$attachmentId";

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

    public static function sendMail($accessToken, $params)
    {
    	$endpoint = self::BASE_ENDPOINT . "/sendMail";

        $curlHandler = curl_init();

        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken, 
            'Content-Type: application/json', 
        ]);
        
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_URL, $endpoint);
        curl_setopt($curlHandler, CURLOPT_POST, 1);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, json_encode(['message' => $params]));

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);

        if (curl_errno($curlHandler)) {
            $error_msg = curl_error($curlHandler);
        }

        curl_close($curlHandler);

        return $jsonResponse;
    }
}