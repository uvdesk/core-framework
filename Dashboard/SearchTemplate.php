<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Dashboard;

use Symfony\Component\Routing\RouterInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\SearchItemInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Framework\ExtendableComponentInterface;

class SearchTemplate implements ExtendableComponentInterface
{
	CONST TEMPLATE = <<<TEMPLATE
<div class="uv-search-wrapper uv-no-error-success-icon">
	<input placeholder="Search" class="uv-search-bar" type="text" name="">
	<div class="uv-search-result-wrapper" id="beauty-scroll">
		<h6>Results</h6>
		
		[[ SEARCH_ITEMS ]]

		<div class="uv-search-result-row uv-no-results" style="display: none">
			<p>{{ 'No result found'|trans }}</p>
		</div>
	</div>
</div>
TEMPLATE;

	CONST ITEM_TEMPLATE = <<<ITEM_TEMPLATE
<a href="[[ URL ]]">
	<div class="uv-search-result-row">
		<div class="uv-brick-icon">"[[ SVG ]]"</div>
		<p>[[ NAME ]]</p>
	</div>
</a>';
ITEM_TEMPLATE;

	private $segments = [];

	public function __construct(RouterInterface $router)
	{
		$this->router = $router;
	}

	public function appendSearchItem(SearchItemInterface $segment, $tags = [])
	{
		$this->segments[] = $segment;
	}

	public function render()
	{
		$router = $this->router;

		$html = array_reduce($this->segments, function($html, $segment) use ($router) {
			$html .= strtr(self::ITEM_TEMPLATE, [
				'[[ SVG ]]' => $segment::getIcon(),
				'[[ NAME ]]' => $segment::getTitle(),
				'[[ URL ]]' => $router->generate($segment::getRouteName()),
			]);

			return $html;
		}, '');

		return strtr(self::TEMPLATE, [
			'[[ SEARCH_ITEMS ]]' => $html
		]);
	}
}
