<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Mailer\Event\ConfigurationRemovedEvent;
use Webkul\UVDesk\CoreFrameworkBundle\Mailer\Event\ConfigurationUpdatedEvent;

class Mailer
{
    protected $container;

    public final function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function getPathToConfigurationFile()
    {
        return $this->container->getParameter('kernel.project_dir') . '/config/packages/uvdesk.yaml';
    }

    private function updateMailerConfigurationId($mailerId = null)
    {
        $supportId = $this->container->getParameter('uvdesk.support_email.id');
        $supportName = $this->container->getParameter('uvdesk.support_email.name');
        
        if (!empty($supportId) && !empty($supportName)) {
            $template = require __DIR__ . '/../Templates/uvdesk.php';
            $content = strtr($template, [
                '{{ SITE_URL }}' => $this->container->getParameter('uvdesk.site_url'),
                '{{ SUPPORT_EMAIL_ID }}' => $supportId,
                '{{ SUPPORT_EMAIL_NAME }}' => $supportName,
                '{{ SUPPORT_EMAIL_MAILER_ID }}'  => $mailerId ?? '~',
            ]);

            file_put_contents($this->getPathToConfigurationFile(), $content);
        }

        return;
    }

    public function onMailerConfigurationUpdated(ConfigurationUpdatedEvent $event)
    {
        $mailerId = $this->container->hasParameter('uvdesk.support_email.mailer_id') ? $this->container->getParameter('uvdesk.support_email.mailer_id') : null;
        
        if (!empty($mailerId)) {
            $updatedMailerConfiguration = $event->getUpdatedMailerConfiguration();
            $existingMailerConfiguration = $event->getExistingMailerConfiguration();

            if ($existingMailerConfiguration->getId() == $mailerId) {
                $this->updateMailerConfigurationId($updatedMailerConfiguration->getId());
            }
        }

        return;
    }

    public function onMailerConfigurationRemoved(ConfigurationRemovedEvent $event)
    {
        $mailerId = $this->container->hasParameter('uvdesk.support_email.mailer_id') ? $this->container->getParameter('uvdesk.support_email.mailer_id') : null;
        
        if (!empty($mailerId)) {
            $mailerConfiguration = $event->getMailerConfiguration();

            if ($mailerConfiguration->getId() == $mailerId) {
                $this->updateMailerConfigurationId(null);
            }
        }

        return;
    }
}
