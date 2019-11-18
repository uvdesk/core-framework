<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Tickets;

use Twig\Environment as TwigEnvironment;
use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\DashboardTemplate;
use Webkul\UVDesk\CoreFrameworkBundle\Framework\ExtendableComponentInterface;

class AsideLinkCollection implements ExtendableComponentInterface
{
	private $collection = [];

	public function __construct(TwigEnvironment $twig, DashboardTemplate $dashboard)
    {
		$this->twig = $twig;
		$this->dashboard = $dashboard;
    }

	public function add(AsideLinkInterface $asideLink)
	{
		$this->collection[] = $asideLink;
	}

	public function injectTemplates()
	{
		return array_reduce($this->collection, function ($stream, $asideLink) {
			return $stream .= $asideLink->renderTemplate($this->twig);
		}, '');
	}

	public function prepareAssets()
	{
		foreach ($this->collection as $asideLink) {
			$asideLink->prepareDashboard($this->dashboard);
		}
	}
}
