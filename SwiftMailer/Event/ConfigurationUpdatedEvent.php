<?php

namespace Webkul\UVDesk\CoreBundle\SwiftMailer\Event;

use Symfony\Component\EventDispatcher\Event;
use Webkul\UVDesk\CoreBundle\Utils\SwiftMailer\BaseConfiguration;

/**
 * The swiftmailer.configuration.removed event is dispatched each time a mailer configuration
 * is removed from the system.
 */
class ConfigurationUpdatedEvent extends Event
{
    
    CONST NAME = 'swiftmailer.configuration.updated';

    private $existingConfiguration;
    private $updatedConfiguration;

    public function __construct(BaseConfiguration $existingConfiguration, BaseConfiguration $updatedConfiguration)
    {
        $this->existingConfiguration = $existingConfiguration;
        $this->updatedConfiguration = $updatedConfiguration;
    }

    public function getExistingSwiftMailerConfiguration(): BaseConfiguration
    {
        return $this->existingConfiguration;
    }

    public function getUpdatedSwiftMailerConfiguration(): BaseConfiguration
    {
        return $this->updatedConfiguration;
    }
}
