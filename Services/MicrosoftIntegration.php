<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft\MicrosoftApp;

class MicrosoftIntegration
{
    const MICROSOFT_OAUTH = "https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize?client_id={client_id}&response_type=code&redirect_uri={redirect_uri}&response_mode=query&scope={scope}&state={state}";

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getAuthorizationUrl(MicrosoftApp $app, $redirectEndpoint, array $state = [])
    {
        $params = [
            '{tenant}'       => $app->getTenantId(), 
            '{client_id}'    => $app->getClientId(), 
            '{redirect_uri}' => urlencode($redirectEndpoint), 
            '{scope}'        => urlencode(implode(' ', $app->getApiPermissions())), 
        ];

        if (!empty($state)) {
            $params['{state}'] = urlencode(json_encode($state));
        }

        return strtr(self::MICROSOFT_OAUTH, $params);
    }

    public function getAccessToken(MicrosoftApp $app, $accessCode, $redirectEndpoint)
    {
        $tenantId = $app->getTenantId();
        $endpoint = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";

        $curlHandler = curl_init();

        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_POST, 1);
        curl_setopt($curlHandler, CURLOPT_URL, $endpoint);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $app->getClientId(), 
            'scope' => urldecode(implode(' ', $app->getApiPermissions())), 
            'code' => $accessCode, 
            'redirect_uri' => $redirectEndpoint, 
            'grant_type' => 'authorization_code', 
            'client_secret' => $app->getClientSecret(), 
        ]));

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);

        if (curl_errno($curlHandler)) {
            $error_msg = curl_error($curlHandler);
        }

        curl_close($curlHandler);

        return $jsonResponse;
    }

    public function refreshAccessToken(MicrosoftApp $app, $refreshToken)
    {
        $tenantId = $app->getTenantId();
        $endpoint = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";

        $curlHandler = curl_init();

        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_POST, 1);
        curl_setopt($curlHandler, CURLOPT_URL, $endpoint);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, http_build_query([
            'tenant' => $app->getTenantId(), 
            'client_id' => $app->getClientId(), 
            'grant_type' => 'refresh_token', 
            'scope' => urldecode(implode(' ', $app->getApiPermissions())), 
            'refresh_token' => $refreshToken, 
            'client_secret' => $app->getClientSecret(), 
        ]));

        $curlResponse = curl_exec($curlHandler);
        $jsonResponse = json_decode($curlResponse, true);

        if (curl_errno($curlHandler)) {
            $error_msg = curl_error($curlHandler);
        }

        curl_close($curlHandler);

        return $jsonResponse;
    }
}