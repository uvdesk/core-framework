<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Mailer\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\SwiftMailer\BaseConfiguration;

/**
 * The mailer.configuration.removed event is dispatched each time a mailer configuration
 * is removed from the system.
 */
class ConfigurationRemovedEvent extends Event
{
    CONST NAME = 'mailer.configuration.removed';

    private $configuration;

    public function __construct(BaseConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getSwiftMailerConfiguration(): BaseConfiguration
    {
        return $this->configuration;
    }
}
