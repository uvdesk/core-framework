<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Search;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\SearchItemInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;

class Folders implements SearchItemInterface
{
    CONST SVG = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30px" height="30px" viewBox="0 0 60 60">
    <path fill-rule="evenodd" d="M25.216,11.023h-14.4a4.708,4.708,0,0,0-4.777,4.624L6.011,43.394a4.729,4.729,0,0,0,4.8,4.625H49.223a4.729,4.729,0,0,0,4.8-4.625L54,21a5.234,5.234,0,0,0-5-5H30Z"></path>
</svg>
SVG;

    public static function getIcon() : string
    {
        return self::SVG;
    }

    public static function getTitle() : string
    {
        return "Folders";
    }

    public static function getRouteName() : string
    {
        return 'access_token_xhr';
    }

    public static function getRoles() : array
    {
        return ['ROLE_AGENT_MANAGE_KNOWLEDGEBASE'];
    }

    public function getChildrenRoutes() : array
    {
        return [];
    }
}
