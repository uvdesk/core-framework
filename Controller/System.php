<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class System extends AbstractController
{   
    /**
     * @Route("/ini-parameter/xhr/{parameter}", name="uvdesk_get_ini_parameter", methods={"GET"})
     */
    public function getIniParameterXhr($parameter, Request $request) {
        
        if (true === $request->isXmlHttpRequest()) {
            if ($parameter) {
                $value = ini_get($parameter);
            }
            if (!empty($value)) {
                return new Response(json_encode([$parameter => $value]), 200, ['Content-Type' => 'application/json']);
            }
        }
        return new Response(json_encode([]), 404, ['Content-Type' => 'application/json']);
    }
}
