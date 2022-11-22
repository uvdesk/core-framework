<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\Configuration;

use Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\BaseConfiguration;

class DefaultConfiguration extends BaseConfiguration
{
    CONST TRANSPORT_CODE = 'smtp';
    CONST TRANSPORT_NAME = 'SMTP';

    CONST TEMPLATE = <<<MAILER
            [[ id ]]: [[ scheme ]]://[[ user ]]:[[ pass ]]@[[ host ]][[ port ]][[ options ]]

MAILER;
    
    public static function getTransportCode()
    {
        return self::TRANSPORT_CODE;
    }

    public static function getTransportName()
    {
        return self::TRANSPORT_NAME;
    }

    public function getScheme()
    {
        return 'smtp';
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setPort($port)
    {
        $this->port = $port;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getWritableConfigurations()
    {
        $params = [
            '[[ id ]]' => $this->getId(),
            '[[ scheme ]]' => $this->getScheme(),
            '[[ user ]]' => $this->getUser(),
            '[[ pass ]]' => $this->getPass(),
            '[[ host ]]' => $this->getHost(),
            '[[ port ]]' => $this->getPort(),
            '[[ options ]]' => '', 
        ];

        // dump(self::TEMPLATE, strtr(self::TEMPLATE, $params));

        return strtr(self::TEMPLATE, $params);
    }

    public function castArray()
    {
        return [
            'transport' => $this->getTransportCode(),
            'id' => $this->getId(),
            'user' => $this->getUser(),
            'pass' => $this->getPass(),
            'host' => $this->getHost(),
            'port' => $this->getPort(),
        ];
    }

    public function initializeParams(array $params, $reset = false)
    {
        foreach ($params as $param => $value) {
            switch ($param) {
                case 'id':
                    if (true == $reset) {
                        $this->setId($value);
                    }
                    break;
                default:
                    $method = 'set' . ucfirst($param);

                    // dump($method);
                    
                    if (is_callable([$this, $method])) {
                        $this->{$method}($value);
                    }

                    break;
            }
        }
    }

    public function resolveTransportConfigurations(array $params = [])
    {
        foreach ($params as $param => $value) {
            $method = 'set' . ucfirst($param);

            switch ($param) {
                case 'host':
                    $this->setHost($value);

                    break;
                case 'user':
                    $this->setUser($value);
                    
                    break;
                case 'pass':
                    $this->setPass($value);
                    
                    break;
                case 'port':
                    $this->setPort($value);
                    
                    break;
                // case 'disable_delivery':
                //     $this->setDeliveryStatus(!(bool) $value);
                //     break;
                // case 'auth_mode':
                //     $this->setAuthenticationMode($value);
                //     break;
                // case 'encryption':
                //     $this->setEncryptionMode($value);
                //     break;
                default:
                    // dump($method);
                    // $method = 'set' . ucfirst($param);
                    
                    // if (is_callable([$this, $method])) {
                    //     $this->{$method}($value);
                    // }

                    break;
            }
        }
    }
}
