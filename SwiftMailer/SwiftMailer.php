<?php

namespace Webkul\UVDesk\CoreBundle\SwiftMailer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreBundle\Utils\SwiftMailer\Configuration as MailerConfigurations;

class SwiftMailer
{
    const PATH_TO_CONFIG = '/config/packages/swiftmailer.yaml';
    const SWIFTMAILER_TEMPLATE = __DIR__ . "/../Templates/SwiftMailer/configurations.php";
    const SWIFTMAILER_NULL_TEMPLATE = __DIR__ . "/../Templates/SwiftMailer/null-configurations.php";

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
                $configuration = new MailerConfigurations\SMTP($id);
                break;
            case 'gmail':
                $configuration = new MailerConfigurations\Gmail($id);
                break;
            case 'yahoo':
                $configuration = new MailerConfigurations\Yahoo($id);
                break;
            default:
                break;
        }

        return $configuration ?? null;
    }

    public function parseSwiftMailerConfigurations() 
    {
        $configurations = [];
        $pathToFile = $this->getPathToConfigurationFile();

        if (file_exists($pathToFile)) {
            $parsedConfigurations = Yaml::parse(file_get_contents($pathToFile));

            if (!empty($parsedConfigurations['swiftmailer'])) {
                if (empty($parsedConfigurations['swiftmailer']['mailers']) && !empty($parsedConfigurations['swiftmailer']['transport'])) {
                    // Only one single mailer is defined
                    $configurations[] = $this->resolveTransportConfigurations($parsedConfigurations['swiftmailer']);
                } else if (!empty($parsedConfigurations['swiftmailer']['mailers'])) {
                    // Multiple mailers defined
                    foreach ($parsedConfigurations['swiftmailer']['mailers'] as $mailer_id => $mailer_configurations) {
                        $configuration = null;

                        switch ($mailer_configurations['transport'] ?? '') {
                            case 'smtp':
                                if ('smtp.mail.yahoo.com' == $mailer_configurations['host']) {
                                    $configuration = new MailerConfigurations\Yahoo($mailer_id);
                                } else {
                                    $configuration = new MailerConfigurations\SMTP($mailer_id);
                                }
                                
                                $configuration->resolveTransportConfigurations($mailer_configurations);

                                $configurations[] = $configuration;
                                break;
                            case 'gmail':
                                $configuration = new MailerConfigurations\Gmail($mailer_id);
                                $configuration->resolveTransportConfigurations($mailer_configurations);

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

    public function writeSwiftMailerConfigurations(array $configurations = [], array $defaults = [])
    {
        
        if (empty($configurations) && empty($defaults)) {
            $stream = require self::SWIFTMAILER_NULL_TEMPLATE;
            // Write to configs.
            file_put_contents($this->getPathToConfigurationFile(), $stream);
            return;
        }
        
        $configurationStream = '';
        $use_defaults = count($configurations) <= 1 ? true : false;

        // Iteratively build up mailers config.
        foreach ($configurations as $configuration) {
            $configurationStream .= $configuration->getWritableConfigurations();
        }

        // Prepare the complete swiftmailer configuration file
        $stream = strtr(require self::SWIFTMAILER_TEMPLATE, [
            '[[ CONFIGURATIONS ]]' => $configurationStream,
        ]);
        

        file_put_contents($this->getPathToConfigurationFile(), $stream);
    }
}
