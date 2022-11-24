<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Mailer\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer\BaseConfiguration;

/**
 * The mailer.configuration.updated event is dispatched each time a mailer configuration
 * is updated in the system.
 */
class ConfigurationUpdatedEvent extends Event
{
    CONST NAME = 'mailer.configuration.updated';

    private $updatedConfiguration;
    private $existingConfiguration;

    public function __construct(BaseConfiguration $updatedConfiguration, BaseConfiguration $existingConfiguration)
    {
        $this->updatedConfiguration = $updatedConfiguration;
        $this->existingConfiguration = $existingConfiguration;
    }

    public function getUpdatedMailerConfiguration(): BaseConfiguration
    {
        return $this->updatedConfiguration;
    }

    public function getExistingMailerConfiguration(): BaseConfiguration
    {
        return $this->existingConfiguration;
    }
}
