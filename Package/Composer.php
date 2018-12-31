<?php

namespace Webkul\UVDesk\CoreBundle\Package;

use Webkul\UVDesk\PackageManager\Composer\ComposerPackage;
use Webkul\UVDesk\PackageManager\Composer\ComposerPackageExtension;

class Composer extends ComposerPackageExtension
{
    public function loadConfiguration()
    {
        ($composerPackage = new ComposerPackage(new UVDeskCoreConfiguration()))
            ->movePackageConfig('config/packages/uvdesk.yaml', 'Templates/config.yaml')
            ->movePackageConfig('config/routes/uvdesk.yaml', 'Templates/routes.yaml')
            ->movePackageConfig('templates/mail.html.twig', 'Templates/Email/base.html.twig')
            ->movePackageConfig('config/packages/security.yaml', 'Templates/security.yaml')
            ->movePackageConfig('config/packages/doctrine.yaml', 'Templates/doctrine.yaml')
            ->combineProjectConfig('config/packages/twig.yaml', 'Templates/twig.yaml')
            ->writeToConsole(require __DIR__ . "/../Templates/CLI/on-boarding.php");
        
        return $composerPackage;
    }
}
