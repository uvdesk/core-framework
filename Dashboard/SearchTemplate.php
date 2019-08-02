<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Dashboard;

use Twig\Environment as TwigEnvironment;
use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\SearchItemInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Framework\ExtendableComponentInterface;

class SearchTemplate implements ExtendableComponentInterface
{
    CONST ITEM_TEMPLATE_PATH = '@UVDeskCoreFramework/Templates/Search/item-template.html.twig';
    CONST COLLECTION_TEMPLATE_PATH = '@UVDeskCoreFramework/Templates/Search/collection-template.html.twig';

    private $segments = [];

    public function __construct(TwigEnvironment $twig)
    {
        $this->twig = $twig;
    }
    public function appendSearchItem(SearchItemInterface $segment, $tags = [])
    {
        $this->segments[] = $segment;
    }

    public function render()
    {
        $segments = $this->segments;

        $html = array_reduce($this->segments, function($html, $segment) {
            $html .= $this->twig->render(self::ITEM_TEMPLATE_PATH, [
                'svg' => $segment::getIcon(),
                'name' => $segment::getTitle(),
                'route' => $segment::getRouteName(),
            ]);

            return $html;
        }, '');

        return $this->twig->render(self::COLLECTION_TEMPLATE_PATH, [
            'searchItems' => $html
        ]);
    }
}
