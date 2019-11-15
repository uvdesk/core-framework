<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Dashboard;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\NavigationInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Framework\ExtendableComponentInterface;

class NavigationTemplate implements ExtendableComponentInterface
{
	CONST TEMPLATE = '<ul class="uv-menubar">[[ COLLECTION ]]</ul>';
	CONST TEMPLATE_ITEM = '<li title = "[[ NAME ]]" class = "[[ ATTRIBUTES ]]" data-toggle = "tooltip" data-placement = "right"><a href="[[ URL ]]"><span class="uv-icon">[[ SVG ]]</span><span class="uv-menu-item">[[ NAME ]]</span></a></li>';
	
	private $segments = [];

	public function __construct(ContainerInterface $container, RequestStack $requestStack, RouterInterface $router, UserService $userService)
	{
		$this->router = $router;
		$this->container = $container;
		$this->requestStack = $requestStack;
		$this->userService = $userService;
	}

	public function appendNavigation(NavigationInterface $segment, $tags = [])
	{
		$this->segments[] = $segment;
	}

	public function render()
	{
		$router = $this->router;
		$request = $this->requestStack->getCurrentRequest();

		// Compile accessible segments by end-user
		$accessibleSegments = [];

		foreach ($this->segments as $item) {
			if (null == $item::getRoles()) {
				$accessibleSegments[] = $item;
			} else {
				foreach ($item::getRoles() as $requiredPermission) {
					if ($this->userService->isAccessAuthorized($requiredPermission)) {
						$accessibleSegments[] = $item;

						break;
					}
				}
			}
		}

		// Reduce the accessible segments into injectible html snippet
		$html = array_reduce($accessibleSegments, function($html, $segment) use ($router, $request) {
			$html .= strtr(self::TEMPLATE_ITEM, [
				'[[ SVG ]]' => $segment::getIcon(),
				'[[ NAME ]]' => $segment::getTitle(),
				'[[ URL ]]' => $router->generate($segment::getRouteName()),
			]);

			return $html;
		}, '');

		return strtr(self::TEMPLATE, ['[[ COLLECTION ]]' => $html]);
	}
}
