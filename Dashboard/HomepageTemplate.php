<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Dashboard;

use Symfony\Component\Routing\RouterInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\Framework\ExtendableComponentInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\HomepageSectionInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\HomepageSectionItemInterface;

class HomepageTemplate implements ExtendableComponentInterface
{
	CONST SECTION_TEMPLATE = '<div class="uv-brick"><div class="uv-brick-head"><h6>[[ TITLE ]]</h6><p>[[ DESCRIPTION ]]</p></div><div class="uv-brick-section">[[ COLLECTION ]]</div></div>';
	CONST SECTION_ITEM_TEMPLATE = '<a href="[[ PATH ]]"><div class="uv-brick-container"><div class="uv-brick-icon">[[ SVG ]]</div><p>[[ TITLE ]]</p></div></a>';

	private $sections = [];
	private $sectionItems = [];
	private $isOrganized = false;

	public function __construct(RouterInterface $router, UserService $userService)
	{
		$this->router = $router;
		$this->userService = $userService;
	}

	public function appendSection(HomepageSectionInterface $section, $tags = [])
	{
		$this->sections[] = $section;
	}

	public function appendSectionItem(HomepageSectionItemInterface $sectionItem, $tags = [])
	{
		$this->sectionItems[] = $sectionItem;
	}

	private function organizeCollection()
	{
		$references = [];
		
		// Sort segments alphabetically
		usort($this->sections, function($section_1, $section_2) {
			return strcasecmp($section_1::getTitle(), $section_2::getTitle());
		});

		// @TODO: Refactor!!!
		$findSectionByName = function(&$array, $name) {
			for ($i = 0; $i < count($array); $i++) {
				if (strtolower($array[$i]::getTitle()) === $name) {
					return array($i, $array[$i]);
				}
			}
		};

		// re-inserting users section
		$users_sec = $findSectionByName($this->sections, "users"); 
		array_splice($this->sections, $users_sec[0], 1);
		array_splice($this->sections, $findSectionByName($this->sections, "knowledgebase")[0] + 1, 0, [$users_sec[1]]);

		usort($this->sectionItems, function($item_1, $item_2) {
			return strcasecmp($item_1::getTitle(), $item_2::getTitle());
		});

		// Maintain array references
		foreach ($this->sections as $reference => $section) {
			$references[get_class($section)] = $reference;
		}

		// Iteratively add child segments to their respective parent segments
		foreach ($this->sectionItems as $sectionItem) {
			if (!array_key_exists($sectionItem::getSectionReferenceId(), $references)) {
				continue;

				// @TODO: Handle exception
				throw new \Exception("No dashboard section [" . $sectionItem::getSectionReferenceId() . "] found for section item " . $sectionItem::getTitle() . " [" . get_class($sectionItem) . "].");
			}

			$this->sections[$references[$sectionItem::getSectionReferenceId()]]->appendItem($sectionItem);
		}

		$this->isOrganized = true;
	}

	private function isSegmentAccessible($segment)
	{
		if ($segment::getRoles() != null) {
			$is_accessible = false;

			foreach ($segment::getRoles() as $accessRole) {
				if ($this->userService->isAccessAuthorized($accessRole)) {
					$is_accessible = true;
	
					break;
				}
			}

			return $is_accessible;
		}

		return true;
	}

	private function getAccessibleSegments()
	{
		$whitelist = [];

		// Filter segments based on user credentials
		foreach ($this->sections as $segment) {
			if (false == $this->isSegmentAccessible($segment)) {
				continue;
			}

			foreach ($segment->getItemCollection() as $childSegment) {
				if (false == $this->isSegmentAccessible($childSegment)) {
					continue;
				}

				$whitelist[get_class($segment)][] = get_class($childSegment);
			}
		}
		
		//Disable Mailbox section item when Workflow privilege is given to agent, (currently no option to give an Agent Mailbox privilege, but it is still given by default).
		if(in_array('ROLE_AGENT', $this->userService->getCurrentUser()->getRoles())  &&  isset($whitelist["Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Homepage\Sections\Productivity"])  &&  in_array("Webkul\UVDesk\AutomationBundle\UIComponents\Dashboard\Homepage\Items\Workflows", $whitelist["Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Homepage\Sections\Productivity"]))
		{ 
			foreach($whitelist["Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Homepage\Sections\Settings"] as $key=>$value)
			{
				if($value == "Webkul\UVDesk\MailboxBundle\UIComponents\Dashboard\Homepage\Items\Mailbox")
				{
					unset($whitelist["Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Homepage\Sections\Settings"][$key]);
				}
			}
		}

		return $whitelist;
	}

	public function render()
	{
		if (false == $this->isOrganized) {
			$this->organizeCollection();
		}

		$html = '';
		$whitelist = $this->getAccessibleSegments();

		// Render user accessible segments
		foreach ($this->sections as $segment) {
			if (empty($whitelist[get_class($segment)])) {
				continue;
			}

			$sectionHtml = '';
			$references = $whitelist[get_class($segment)];

			foreach ($segment->getItemCollection() as $childSegment) {
				if (!in_array(get_class($childSegment), $references)) {
					continue;
				}

				$sectionHtml .= strtr(self::SECTION_ITEM_TEMPLATE, [
					'[[ SVG ]]' => $childSegment::getIcon(),
					'[[ TITLE ]]' => $childSegment::getTitle(),
					'[[ PATH ]]' => $this->router->generate($childSegment::getRouteName()),
				]);
			}

			$html .= strtr(self::SECTION_TEMPLATE, [
				'[[ TITLE ]]' => $segment::getTitle(),
				'[[ DESCRIPTION ]]' => $segment::getDescription(),
				'[[ COLLECTION ]]' => $sectionHtml,
			]);
		}

		return $html;
	}
}
