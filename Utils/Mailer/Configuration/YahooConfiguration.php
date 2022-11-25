<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\Configuration;

use Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\BaseConfiguration;

class YahooConfiguration extends BaseConfiguration
{
    CONST SCHEME = 'smtp';
    CONST TRANSPORT_CODE = 'yahoo';
    CONST TRANSPORT_NAME = 'Yahoo';

    CONST HOST = 'smtp.mail.yahoo.com';
    CONST PORT = '587';

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

    public function getHost()
    {
        return self::HOST;
    }

    public function getPort()
    {
        return self::PORT;
    }

    public function castArray()
    {
        return [
            'scheme' => $this->getScheme(), 
            'transport' => $this->getTransportCode(), 
            'id' => $this->getId(), 
            'user' => $this->getUser(), 
            'pass' => $this->getPass(), 
            'host' => $this->getHost(), 
            'port' => $this->getPort(), 
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

        if (false == $this->getUseStrictMode()) {
            $options['verify_peer'] = 0;
        }

        if ($this->getDisableEmailDelivery()) {
            $options['disableDelivery'] = 1;
        }

        $params = [
            '[[ scheme ]]' => $this->getScheme(),
            '[[ user ]]' => $this->getUser(),
            '[[ pass ]]' => $this->getPass(),
            '[[ host ]]' => $this->getHost(),
            '[[ port ]]' => $this->getPort() ? ":" . $this->getPort() : '', 
            '[[ options ]]' => !empty($options) ? '?' . http_build_query($options) : '', 
        ];

        $configuration = strtr(BaseConfiguration::CONFIGURATION, $params);

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
                            case 'verify_peer':
                                $this->setUseStrictMode((int) $optionValue == 0 ? false : true);

                                break;
                            default:
                                break;
                        }
                    }

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
