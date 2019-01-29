<?php

namespace Webkul\UVDesk\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EmailSettings extends Controller
{
    public function loadSettings()
    {
        return $this->render('@UVDeskCore//Email//emailSettings.html.twig', [
            'swiftmailers' => $this->container->get('swiftmailer.service')->getSwiftmailerIds(),
        ]);
    }
}