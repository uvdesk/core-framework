<?php

namespace Webkul\UVDesk\CoreBundle\EventListener;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\RequestStack;
use Webkul\UVDesk\MailboxBundle\Utils\Mailbox\Mailbox;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreBundle\EventListener\EventListenerInterface;
use Webkul\UVDesk\CoreBundle\SwiftMailer\Event\ConfigurationRemovedEvent;
use Webkul\UVDesk\CoreBundle\SwiftMailer\Event\ConfigurationUpdatedEvent;

class Swiftmailer implements EventListenerInterface
{
    protected $container;
    protected $requestStack;

    public final function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->requestStack = $requestStack;
    }

    public function onSwiftMailerConfigUvdeskYmlRemove(ConfigurationRemovedEvent $event)
    {
        $configuration = $event->getSwiftMailerConfiguration(); 
        $oldMailerId = $configuration->getId();
        $newMailerId = null;
        // updating uvdesk.yaml file.
        $this->updateUvdeskYmlFile($oldMailerId, $newMailerId);
        return;
    }

    public function onSwiftMailerConfigUvdeskYmlUpdate(ConfigurationUpdatedEvent $event)
    {
        $updatedConfiguration = $event->getUpdatedSwiftMailerConfiguration();
        $existingConfiguration = $event->getExistingSwiftMailerConfiguration();
        $newMailerId = $updatedConfiguration->getId();
        $oldMailerId = $existingConfiguration->getId();
        // updating uvdesk.yaml file.
        $this->updateUvdeskYmlFile($oldMailerId, $newMailerId);
        return;
    }

    public function updateUvdeskYmlFile($oldMailerId, $newMailerId)
    {
        $filePath = $this->container->get('kernel')->getProjectDir() . '/config/packages/uvdesk.yaml';
        $file_content = file_get_contents($filePath);
        $file_content_array = Yaml::parse($file_content, 6); 
        $result = $file_content_array['uvdesk']['support_email'];
       
        if($result['mailer_id'] == $oldMailerId){
            $templatePath = $this->container->get('kernel')->getProjectDir() . '/vendor/uvdesk/core-framework/Templates/uvdesk.php';
            
            $malierIdValue = is_null($newMailerId) ? '~' : $newMailerId;

            $file_data_array = strtr(require $templatePath, [
                '{{ SITE_URL }}' => $file_content_array['uvdesk']['site_url'],
                '{{ SUPPORT_EMAIL_ID }}' => $file_content_array['uvdesk']['support_email']['id'] ,
                '{{ SUPPORT_EMAIL_NAME }}' => $file_content_array['uvdesk']['support_email']['name'],
                '{{ SUPPORT_EMAIL_MAILER_ID }}'  => $malierIdValue,
            ]);
            // updating contents of uvdesk.yaml file.
            file_put_contents($filePath, $file_data_array);
        }
        return;
    }

    
}
