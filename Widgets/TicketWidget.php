<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Widgets;

use Webkul\UVDesk\CoreFrameworkBundle\Framework\ExtendableComponentInterface;

class TicketWidget implements ExtendableComponentInterface
{
	private $widgets = [];

	public function addWidget(TicketWidgetInterface $widget)
	{
		$this->widgets[] = $widget;
	}

	// Render side filter icons
	public function embedSideFilterIcons()
	{
		return array_reduce($this->widgets, function($html, $segment) {
			$html .= '<span class="app-glyph filter-view-trigger" data-target="' . $segment::getDataTarget() . '" data-toggle="tooltip" data-placement="top" title="' . $segment::getTitle() . '">' . $segment::getIcon() . '</span>';

			return $html;
		}, '');
	}

	// Render side filter content
	public function embedSideFilterContent()
	{
		return array_reduce($this->widgets, function($html, $segment) {
			$html .= $segment->getTemplate();

			return $html;
		}, '');
	}
}
