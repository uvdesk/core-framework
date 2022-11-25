<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\Configuration;

use Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\BaseConfiguration;

class OutlookModernAuthConfiguration extends BaseConfiguration
{
    CONST SCHEME = 'microsoftgraph';
    CONST TRANSPORT_CODE = 'outlook_oauth';
    CONST TRANSPORT_NAME = 'Outlook Modern Auth';

    CONST CONFIGURATION = <<<MAILER
[[ scheme ]]://[[ user ]]@[[ client ]][[ options ]]
MAILER;

    private $client = null;

    public static function getScheme()
    {
        return self::SCHEME;
    }

    public static function getTransportCode()
    {
        return self::TRANSPORT_CODE;
    }

    public static function getTransportName()
    {
        return self::TRANSPORT_NAME;
    }

    public function getClient()
    {
        return $this->client;
    }
    
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    public function castArray()
    {
        return [
            'scheme' => $this->getScheme(), 
            'transport' => $this->getTransportCode(), 
            'id' => $this->getId(), 
            'user' => $this->getUser(), 
            'client' => $this->getClient(), 
            'useStrictMode' => $this->getUseStrictMode(), 
            'disableEmailDelivery' => $this->getDisableEmailDelivery(), 
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
                case 'useStrictMode':
                    $this->setUseStrictMode($value == 'on' ? true : false);

                    break;
                case 'disableEmailDelivery':
                    $this->setDisableEmailDelivery($value == 'on' ? true : false);

                    break;
                default:
                    $method = 'set' . ucfirst($param);

                    if (is_callable([$this, $method])) {
                        $this->{$method}($value);
                    }

                    break;
            }
        }
    }

    public function getWritableConfigurations($defaultMailerDsnConfig = null)
    {
        $options = [];

        if ($this->getDisableEmailDelivery()) {
            $options['disableDelivery'] = 1;
        }

        $params = [
            '[[ scheme ]]' => $this->getScheme(),
            '[[ user ]]' => $this->getUser(),
            '[[ client ]]' => $this->getClient(),
            '[[ options ]]' => !empty($options) ? '?' . http_build_query($options) : '', 
        ];

        $configuration = strtr(self::CONFIGURATION, $params);

        if (!empty($defaultMailerDsnConfig) && $configuration == $defaultMailerDsnConfig) {
            $configuration = "'%env(MAILER_DSN)%'";
        }

        $params = [
            '[[ id ]]' => $this->getId(),
            '[[ configuration ]]' => $configuration,
        ];

        return strtr(BaseConfiguration::TEMPLATE, $params);
    }

    public function resolveTransportConfigurations(array $params = [])
    {
        foreach ($params as $param => $value) {
            $method = 'set' . ucfirst($param);

            switch ($param) {
                case 'query':
                    $options = [];
                    
                    parse_str($value, $options);

                    foreach ($options as $option => $optionValue) {
                        switch ($option) {
                            case 'disableDelivery':
                                $this->setDisableEmailDelivery((int) $optionValue == 0 ? false : true);

                                break;
                            default:
                                break;
                        }
                    }

                    break;
                case 'host':
                    $this->setClient($value);

                    break;
                default:
                    $method = 'set' . ucfirst($param);
                    
                    if (is_callable([$this, $method])) {
                        $this->{$method}($value);
                    }

                    break;
            }
        }
    }
}
