<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Dashboard;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\SearchItemInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Framework\ExtendableComponentInterface;

class SearchItemTemplate implements ExtendableComponentInterface
{
	CONST TEMPLATE = '[[ COLLECTION ]]';
  CONST TEMPLATE_ITEM = '<a href="[[ URL ]]"><div class="uv-search-result-row"><div class="uv-brick-icon">"[[ SVG ]]"</div><p>[[ NAME ]]</p></div></a>';

	private $segments = [];

	public function __construct(ContainerInterface $container, RequestStack $requestStack, RouterInterface $router)
	{
		$this->router = $router;
		$this->container = $container;
		$this->requestStack = $requestStack;
	}

	public function appendSearchItem(SearchItemInterface $segment, $tags = [])
	{
		$this->segments[] = $segment;
	}

	public function render()
	{
		$router = $this->router;
		$request = $this->requestStack->getCurrentRequest();

		$html = array_reduce($this->segments, function($html, $segment) use ($router, $request) {
			$html .= strtr(self::TEMPLATE_ITEM, [
				'[[ SVG ]]' => $segment::getIcon(),
				'[[ NAME ]]' => $segment::getTitle(),
				'[[ URL ]]' => $router->generate($segment::getRouteName()),
			]);

			return $html;
		}, '');

		return strtr(self::TEMPLATE, [
			'[[ COLLECTION ]]' => $html
		]);
	}
}
