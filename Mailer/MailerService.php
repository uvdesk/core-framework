<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Mailer;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\Configuration\DefaultConfiguration;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\Configuration\GmailConfiguration;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\Configuration\YahooConfiguration;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\Configuration\OutlookConfiguration;

class MailerService
{
    const PATH_TO_CONFIG = '/config/packages/mailer.yaml';
    const MAILER_TEMPLATE = __DIR__ . "/../Templates/Mailer/configurations.php";
    const MAILER_NULL_TEMPLATE = __DIR__ . "/../Templates/Mailer/null-configurations.php";

	protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function getPathToConfigurationFile()
    {
        return $this->container->get('kernel')->getProjectDir() . self::PATH_TO_CONFIG;
    }

    public function createConfiguration($transport, $id = null)
    {
        switch ($transport) {
            case 'smtp':
                $configuration = new DefaultConfiguration($id);
                break;
            case 'gmail':
                $configuration = new GmailConfiguration($id);
                break;
            case 'yahoo':
                $configuration = new YahooConfiguration($id);
                break;
            case 'outlook':
                $configuration = new OutlookConfiguration($id);
                break;
            default:
                break;
        }

        return $configuration ?? null;
    }

    public function parseMailerConfigurations() 
    {
        $configurations = [];
        $pathToFile = $this->getPathToConfigurationFile();

        if (file_exists($pathToFile)) {
            $parsedConfigurations = Yaml::parse(file_get_contents($pathToFile));

            if (!empty($parsedConfigurations['framework']['mailer'])) {
                if (empty($parsedConfigurations['framework']['mailer']['transports']) && !empty($parsedConfigurations['framework']['mailer']['dsn'])) {
                    // Only one single mailer is defined
                    $configurations[] = $this->resolveTransportConfigurations($parsedConfigurations['framework']['mailer']['dsn']);
                } else if (!empty($parsedConfigurations['framework']['mailer']['transports'])) {
                    // Multiple mailers defined
                    foreach ($parsedConfigurations['framework']['mailer']['transports'] as $mailerId => $mailerConfigurations) {
                        $configuration = null;

                        if (strpos($mailerConfigurations, '%env(') !== false) {
                            $envId = str_replace(['%env(', ')%'], '', $mailerConfigurations);
                            $mailerConfigurations = !empty($_ENV[$envId]) ? $_ENV[$envId] : null;
                        }

                        $mailerConfigurations = parse_url($mailerConfigurations);

                        switch ($mailerConfigurations['scheme'] ?? '') {
                            case 'smtp':
                                switch ($mailerConfigurations['host']) {
                                    case 'smtp.gmail.com':
                                        $configuration = new GmailConfiguration($mailerId);

                                        break;
                                    case 'smtp.mail.yahoo.com':
                                        $configuration = new YahooConfiguration($mailerId);

                                        break;
                                    case 'smtp.office365.com':
                                        $configuration = new OutlookConfiguration($mailerId);

                                        break;
                                    default:
                                        $configuration = new DefaultConfiguration($mailerId);

                                        break;
                                }

                                $configuration->resolveTransportConfigurations($mailerConfigurations);
                                $configurations[] = $configuration;

                                break;
                            default:
                                break;
                        }
                    }
                }
            }
        }

        return $configurations;
    }

    public function writeMailerConfigurations(array $configurations = [], array $defaults = [])
    {
        if (empty($configurations) && empty($defaults)) {
            $stream = require self::MAILER_NULL_TEMPLATE;

            // Write to configs.
            file_put_contents($this->getPathToConfigurationFile(), $stream);

            return;
        }
        
        $references = [];
        $configurationStream = '';
        $use_defaults = count($configurations) <= 1 ? true : false;

        // Iteratively build up mailers config.
        foreach ($configurations as $configuration) {
            if (in_array($configuration->getId(), $references)) {
                throw new \Exception('Mailer configuration already exist with same id.');
            }

            $references[] = $configuration->getId();

            $configurationStream .= $configuration->getWritableConfigurations($_ENV['MAILER_DSN'] ?? null);
        }

        // Default_mailer configuration
        // @TODO: Needs to be improved. We shouldn't just randomly set the first mailer as the default mailer.
        $stream = require self::MAILER_TEMPLATE;

        // @TODO: Setup a default mailer id
        // if (!empty($references[0])) {
        //     $stream = strtr($stream, [
        //         '[[ DEFAULT_MAILER ]]' => $references[0],
        //     ]);
        // }

        // Prepare the complete mailer configuration file
        $stream = strtr($stream, [
            '[[ CONFIGURATIONS ]]' => $configurationStream,
        ]);

        file_put_contents($this->getPathToConfigurationFile(), $stream);
    }
}
