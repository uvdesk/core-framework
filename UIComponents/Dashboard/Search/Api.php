<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Search;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\SearchItemInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;

class Api implements SearchItemInterface
{
    CONST SVG = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30px" height="30px" viewBox="0 0 60 60">
<path fill-rule="evenodd" d="M6,44v5H22V44H6ZM6,11v5H33V11H6ZM33,54V49H54V44H33V39H28V54h5ZM17,23v5H6v5H17v5h5V23H17ZM54,33V28H28v5H54ZM39,21h5V16H54V11H44V6H39V21Z"></path>
</svg>
SVG;

    public static function getIcon() : string
    {
        return self::SVG;
    }

    public static function getTitle() : string
    {
        return "API";
    }

    public static function getRouteName() : string
    {
        return 'uvdesk_api_load_configurations';
    }

    public static function getRoles() : array
    {
        return ['ROLE_ADMIN'];
    }

    public function getChildrenRoutes() : array
    {
        return [];
    }
}
