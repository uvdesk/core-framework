<?php

namespace Webkul\UVDesk\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Webkul\UVDesk\PackageManager\Extensions as UVDeskPackageExtensions;
use Webkul\UVDesk\PackageManager\ExtensionOptions as UVDeskPackageExtensionOptions;

class CoreExtension extends Extension
{
    public function getAlias()
    {
        return 'uvdesk';
    }

    public function getConfiguration(array $configs, ContainerBuilder $container)
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // Load bundle configurations
        $configuration = $this->getConfiguration($configs, $container);
        foreach ($this->processConfiguration($configuration, $configs) as $param => $value) {
            switch ($param) {
                case 'emails':
                case 'support_email':
                case 'upload_manager':
                    foreach ($value as $field => $fieldValue) {
                        $container->setParameter("uvdesk.$param.$field", $fieldValue);
                    }
                    break;
                case 'default':
                    foreach ($value as $defaultItem => $defaultItemValue) {
                        switch ($defaultItem) {
                            case 'templates':
                                foreach ($defaultItemValue as $template => $templateValue) {
                                    $container->setParameter("uvdesk.default.templates.$template", $templateValue);
                                }
                                break;
                            case 'ticket':
                                foreach ($defaultItemValue as $option => $optionValue) {
                                    $container->setParameter("uvdesk.default.ticket.$option", $optionValue);
                                }
                                break;
                            default:
                                $container->setParameter("uvdesk.default.$defaultItem", $defaultItemValue);
                                break;
                        }
                    }
                    break;
                case 'mailboxes':
                    $container->setParameter("uvdesk.mailboxes", array_keys($value));

                    foreach ($value as $mailboxId => $mailboxDetails) {
                        $container->setParameter("uvdesk.mailboxes.$mailboxId", $mailboxDetails);
                    }
                    break;
                default:
                    $container->setParameter("uvdesk.$param", $value);
                    break;
            }
        }

        // Extension Defaults
        $helpdeskDashboardItemCollection = [
            UVDeskPackageExtensionOptions\HelpdeskExtension\Section::CHANNELS => [],
            UVDeskPackageExtensionOptions\HelpdeskExtension\Section::USERS => [],
            UVDeskPackageExtensionOptions\HelpdeskExtension\Section::AUTOMATION => [],
            UVDeskPackageExtensionOptions\HelpdeskExtension\Section::KNOWLEDGEBASE => [],
            UVDeskPackageExtensionOptions\HelpdeskExtension\Section::SETTINGS => [],
        ];

        $helpdeskNavigationItemCollection = [];

        // Register extensions
        $registeredExtensionClassPaths = require $container->getParameter('kernel.project_dir') . '/config/extensions.php';

        foreach ($registeredExtensionClassPaths as $extensionClassPath) {
            if (false == class_exists($extensionClassPath)) {
                throw new \Exception("Registered extension \"$extensionClassPath\" not found.");
            }

            $extensionConfiguration = new $extensionClassPath();

            switch (true) {
                case $extensionConfiguration instanceof UVDeskPackageExtensions\HelpdeskExtension:
                    // Register helpdesk extension dashboard items
                    foreach ($extensionConfiguration->loadDashboardItems() as $section => $dashboardItemCollection) {
                        foreach ($dashboardItemCollection as $dashboardItem) {
                            if (array_key_exists($section, $helpdeskDashboardItemCollection)) {
                                array_push($helpdeskDashboardItemCollection[$section], $dashboardItem);
                            }
                        }
                    }

                    // Register helpdesk extension panel navigation items
                    foreach ($extensionConfiguration->loadNavigationItems() as $navigationItem) {
                        array_push($helpdeskNavigationItemCollection, $navigationItem);
                    }
                    break;
                default:
                    break;
            }
        }

        $container->setParameter("uvdesk.helpdesk.dashboard_items", $helpdeskDashboardItemCollection);
        $container->setParameter("uvdesk.helpdesk.navigation_items", $helpdeskNavigationItemCollection);
    }
}
