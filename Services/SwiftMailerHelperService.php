<?php

namespace Webkul\UVDesk\CoreBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SwiftMailerHelperService
{
	protected $container;
	protected $requestStack;

    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->container = $container;
    }

    public function getSwiftmailerIds()
    {
        $listSwiftmailer = '';
        $swiftmailerIDs = [];

        $file_content_array = Yaml::parse(file_get_contents(dirname(__FILE__, 5) . '/config/packages/swiftmailer.yaml'), 6);
        if (isset($file_content_array['swiftmailer']['mailers'])) {
            $listSwiftmailer = $file_content_array['swiftmailer']['mailers'];
        }
        
        if (!empty($listSwiftmailer)) {
            foreach($listSwiftmailer as  $key => $value){
                $swiftmailerIDs[] = $key;
            }
        }

        return $swiftmailerIDs;
    }
}
