<?php

namespace Webkul\UVDesk\CoreBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Utils\TokenGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UVDeskService
{
	protected $container;
	protected $requestStack;
    protected $entityManager;
    private $avoidArray = [
        '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '+', '-', '=', '/', '\\', ':', '{', '}', '[', ']', '<', '>', '.', '?', ';', '"', '\'', ',', '|',
        '1', '2', '3', '4', '5', '6', '7', '8', '9', '0',
        ' true ', ' false ',
        ' do ', ' did ',
        ' is ', ' are ', ' am ', ' was ', ' were ',
        ' has ', ' have ', ' had ',
        ' will ', ' would ', ' shall ', ' should ', ' must ', ' can ', ' could ',
        ' not ', ' never ',
        ' neither ', ' either ',
        ' the ', ' a ', ' an ', ' this ', ' that ',
        ' here ', ' there ',
        ' then ', ' when ', ' since ',
        ' he ', ' him ', ' himself ', ' she ', ' her ', ' herself ', ' i ', ' me ', ' myself ', ' mine ', ' you ', ' your ' ,' yourself ', ' ur ', ' we ', ' ourself ', ' it ', ' its ',
        ' for ', ' from ', ' on ', ' and ', ' in ', ' be ', ' to ', ' or ', ' of ', ' with ',
        ' what ', ' why ', ' where ', ' who ', ' whom ', ' which ',
        ' a ', ' b ', ' c ', ' d ', ' e ' , ' f ' , ' g ' , ' h ' , ' i ' , ' j ' , ' k ' , ' l ' , ' m ' , ' n ' , ' o ' , ' p ' , ' q ' , ' r ' , ' s ' , ' t ' , ' u ' , ' v ' , ' w ' , ' x ' , ' y ' , ' z ' ,
        '  ',
    ];

	public function __construct(ContainerInterface $container, RequestStack $requestStack, EntityManager $entityManager)
	{
		$this->container = $container;
		$this->requestStack = $requestStack;
		$this->entityManager = $entityManager;
	}

	public function getLocales()
	{
		return [
            'en' => 'English',
            'fr' => 'French',
            'it' => 'Italian',
            'ar' => 'Arabic',
            'de' => 'German',
            'es' => 'Spanish',
            'tr' => 'Turkish',
            'da' => 'Danish'
        ];
    }
    
    public function getTimezones()
    {
        return \DateTimeZone::listIdentifiers();
    }

    public function getPrivileges() {
        $agentPrivilegeCollection = [];
        // $agentPrivilegeCollection = $this->entityManager->getRepository('UserBundle:AgentPrivilege')->findAll();

        return $agentPrivilegeCollection;
    }

	public function getLocaleUrl($locale)
	{
		$request = $this->requestStack->getCurrentRequest();

		return str_replace('/' . $request->getLocale() . '/', '/' . $locale . '/', $request->getRequestUri());
    }

    public function getHelpdeskDashboadPanelItems()
    {
        return $this->container->getParameter('uvdesk.helpdesk.dashboard_items');
    }

    public function getHelpdeskNavigationSidebarItems()
    {
        return $this->container->getParameter('uvdesk.helpdesk.navigation_items');
    }

    public function getFileUploadManager()
    {
        return $this->container->get($this->container->getParameter('uvdesk.upload_manager.id'));
    }

	public function getPanelSidebarRoutes()
	{
		$router = $this->container->get('router');
		$navigationPanel = ['name' => null, 'routes' => []];

		switch (strtoupper($this->requestStack->getCurrentRequest()->get('panelId'))) {
			case 'USERS':
				$navigationPanel = [
                    'name' => 'Users',
                    'routes' => [
                        [
                            'name' => 'Groups',
                            'link' => $router->generate('helpdesk_member_support_group_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => "ROLE_AGENT_MANAGE_GROUP",
                        ],
                        [
                            'name' => 'Teams',
                            'link' => $router->generate('helpdesk_member_support_team_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => "ROLE_AGENT_MANAGE_SUB_GROUP",
                        ],
                        [
                            'name' => 'Agents',
                            'link' => $router->generate('helpdesk_member_account_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => "ROLE_AGENT_MANAGE_AGENT",
                        ],
                        [
                            'name' => 'Privileges',
                            'link' => $router->generate('helpdesk_member_privilege_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => "ROLE_AGENT_MANAGE_AGENT_PRIVILEGE",
                        ],
                        [
                            'name' => 'Customers',
                            'link' => $router->generate('helpdesk_member_manage_customer_account_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => "ROLE_AGENT_MANAGE_CUSTOMER",
                        ],
                    ],
                ];
                break;
            case 'ACCOUNT':
                $navigationPanel = [
                    'name' => 'Account',
                    'routes' => [
                        [
                            'name' => 'Profile',
                            'link' => $router->generate('helpdesk_member_profile'),
                            'isActive' => false,
                            'isEnabled' => true,
                        ],
                    ],
                ];
                break;
            case 'PRODUCTIVITY':
                $navigationPanel = [
                    'name' => 'Productivity',
                    'routes' => [
                        [
                            'name' => 'Workflows',
                            'link' => $router->generate('helpdesk_member_workflow_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => 'ROLE_AGENT_MANAGE_WORKFLOW_AUTOMATIC',
                        ],
                        [
                            'name' => 'Tags',
                            'link' => $router->generate('helpdesk_member_ticket_tag_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => 'ROLE_AGENT_MANAGE_TAG',
                        ],
                        [
                            'name' => 'Prepared Responses',
                            'link' => $router->generate('prepare_response_action'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => 'ROLE_AGENT_MANAGE_WORKFLOW_MANUAL',
                        ],
                        [
                            'name' => 'Ticket Types',
                            'link' => $router->generate('helpdesk_member_ticket_type_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => 'ROLE_AGENT_MANAGE_TICKET_TYPE',
                        ],
                    ],
                ];
                break;
            case 'SETTINGS':
                $navigationPanel = [
                    'name' => 'Settings',
                    'routes' => [
                        [
                            'name' => 'Branding',
                            'link' => $router->generate('helpdesk_member_knowledgebase_theme'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => 'ROLE_ADMIN',
                        ],
                        [
                            'name' => 'Email Templates',
                            'link' => $router->generate('email_templates_action'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => 'ROLE_AGENT_MANAGE_EMAIL_TEMPLATE',
                        ],
                        [
                            'name' => 'Block Spam',
                            'link' => $router->generate('helpdesk_member_knowledgebase_spam'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => 'ROLE_ADMIN',
                        ],
                    ],
                ];
                break;
            case 'THEMES':
                $enabled_bundles = $this->container->getParameter('kernel.bundles');

                $navigationPanel = [
                    'name' => 'Branding',
                    'routes' => [
                        [
                            'name' => 'Helpdesk',
                            'link' => $router->generate('helpdesk_member_helpdesk_theme'),
                            'isActive' => false,
                            'isEnabled' => true,
                        ],
                    ],
                ];

                if (in_array('UVDeskSupportCenterBundle', array_keys($enabled_bundles))) {
                    $navigationPanel['routes'][1] = [
                        'name' => 'Support Center',
                        'link' => $router->generate('helpdesk_member_knowledgebase_theme'),
                        'isActive' => false,
                        'isEnabled' => true,
                    ];
                }
                break;
            case 'KNOWLEDGEBASE':
                $navigationPanel = [
                    'name' => 'Knowledgebase',
                    'routes' => [
                        [
                            'name' => 'Folders',
                            'link' => $router->generate('helpdesk_member_knowledgebase_folders_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => 'ROLE_AGENT_MANAGE_KNOWLEDGEBASE',
                        ],
                        [
                            'name' => 'Categories',
                            'link' => $router->generate('helpdesk_member_knowledgebase_category_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => 'ROLE_AGENT_MANAGE_KNOWLEDGEBASE',
                        ],
                        [
                            'name' => 'Articles',
                            'link' => $router->generate('helpdesk_member_knowledgebase_article_collection'),
                            'isActive' => false,
                            'isEnabled' => true,
                            'permission' => 'ROLE_AGENT_MANAGE_KNOWLEDGEBASE',
                        ],
                    ],
                ];
                break;
			default:
				break;
        }

		return $navigationPanel;
    }
    
    public function buildPaginationQuery(array $query = [])
    {
        $params = array();
        $query['page'] = "replacePage";

        if (isset($query['domain'])) unset($query['domain']);
        if (isset($query['_locale'])) unset($query['_locale']);
        
        foreach ($query as $key => $value) {
            $params[] = !isset($value) ? $key : $key . '/' . str_replace('%2F', '/', rawurlencode($value));
        }

        $http_query = implode('/', $params);
        
        if (isset($query['new'])) {
            $http_query = str_replace('new/1', 'new', $http_query);
        } else if (isset($query['unassigned'])) {
            $http_query = str_replace('unassigned/1', 'unassigned', $http_query);
        } else if (isset($query['notreplied'])) {
            $http_query = str_replace('notreplied/1', 'notreplied', $http_query);
        } else if (isset($query['mine'])) {
            $http_query = str_replace('mine/1', 'mine', $http_query);
        } else if (isset($query['starred'])) {
            $http_query = str_replace('starred/1', 'starred', $http_query);
        } else if (isset($query['trashed'])) {
            $http_query = str_replace('trashed/1', 'trashed', $http_query);
        }
        
        return $http_query;
    }

    public function getEntityManagerResult($entity, $callFunction, $args = false, $extraPrams = false)
    {

        if($extraPrams)
            return $this->entityManager->getRepository($entity)
                        ->$callFunction($args, $extraPrams);
        else
            return $this->entityManager->getRepository($entity)
                        ->$callFunction($args);
    }

    public function getPopularArticles()
    {
        return $this->container->get('doctrine')
                ->getRepository('UVDeskSupportCenterBundle:Article')
                ->getPopularTranslatedArticles($this->requestStack->getCurrentRequest()->getLocale());
    }

    public function getValidBroadcastMessage($msg, $format = 'Y-m-d H:i:s')
    {
        $broadcastMessage = !empty($msg) ? json_decode($msg, true) : false;

        if(!empty($broadcastMessage) && isset($broadcastMessage['isActive']) && $broadcastMessage['isActive']) {
            $timezone = new \DateTimeZone('Asia/Kolkata');
            $nowTimestamp = date('U');
            if(array_key_exists('from', $broadcastMessage) && ($fromDateTime = \DateTime::createFromFormat($format, $broadcastMessage['from'], $timezone))) {
                $fromTimeStamp = $fromDateTime->format('U');
                if($nowTimestamp < $fromTimeStamp) {
                    return false;
                }
            }
            if(array_key_exists('to', $broadcastMessage) && ($toDateTime = \DateTime::createFromFormat($format, $broadcastMessage['to'], $timezone))) {
                $toTimeStamp = $toDateTime->format('U');;
                if($nowTimestamp > $toTimeStamp) {
                    return false;
                }
            }
        } else {
            return false;
        }

        // return valid broadcast message Array
        return $broadcastMessage;
    }

    public function getConfigParameter($param)
	{
		if($param && $this->container->hasParameter($param)) {
			return $this->container->getParameter($param);
		} else {
			return false;
		}
    }
    
    public function isDarkSkin($brandColor) {
        $brandColor = str_replace('#', '', $brandColor);
        if(strlen($brandColor) == 3)
            $brandColor .= $brandColor;

        $chars = str_split($brandColor);

        $a2fCount = 0;
        foreach ($chars as $key => $char) {
            if(in_array($key, [0, 2, 4]) && in_array(strtoupper($char), ['A', 'B', 'C', 'D', 'E', 'F'])) {
                $a2fCount++;
            }
        }

        if($a2fCount >= 2)
            return true;
        else
            return false;
    }

    public function getActiveConfiguration($websiteId)
    {
        $configurationRepo = $this->entityManager->getRepository('UVDeskSupportCenterBundle:KnowledgebaseWebsite');
        $configuration = $configurationRepo->findOneBy(['website' => $websiteId, 'isActive' => 1]);

        return $configuration;
    }

    public function getSupportPrivelegesResources()
    {
        $translator = $this->container->get('translator');
        return [
            'ticket' => [
                'ROLE_AGENT_CREATE_TICKET' => $translator->trans('Can create ticket'),
                'ROLE_AGENT_EDIT_TICKET' => $translator->trans('Can edit ticket'),
                'ROLE_AGENT_DELETE_TICKET' => $translator->trans('Can delete ticket'),
                'ROLE_AGENT_RESTORE_TICKET' => $translator->trans('Can restore trashed ticket'),
                'ROLE_AGENT_ASSIGN_TICKET' => $translator->trans('Can assign ticket'),
                'ROLE_AGENT_ASSIGN_TICKET_GROUP' => $translator->trans('Can assign ticket group'),
                'ROLE_AGENT_UPDATE_TICKET_STATUS' => $translator->trans('Can update ticket status'),
                'ROLE_AGENT_UPDATE_TICKET_PRIORITY' => $translator->trans('Can update ticket priority'),
                'ROLE_AGENT_UPDATE_TICKET_TYPE' => $translator->trans('Can update ticket type'),
                'ROLE_AGENT_ADD_NOTE' => $translator->trans('Can add internal notes to ticket'),
                'ROLE_AGENT_EDIT_THREAD_NOTE' => $translator->trans('Can edit thread/notes'),
                'ROLE_AGENT_MANAGE_LOCK_AND_UNLOCK_THREAD' => $translator->trans('Can lock/unlock thread'),
                'ROLE_AGENT_ADD_COLLABORATOR_TO_TICKET' => $translator->trans('Can add collaborator to ticket'),
                'ROLE_AGENT_DELETE_COLLABORATOR_FROM_TICKET' => $translator->trans('Can delete collaborator from ticket'),
                'ROLE_AGENT_DELETE_THREAD_NOTE' => $translator->trans('Can delete thread/notes'),
                'ROLE_AGENT_APPLY_WORKFLOW' => $translator->trans('Can apply prepared response on ticket'),
                'ROLE_AGENT_ADD_TAG' => $translator->trans('Can add ticket tags'),
                'ROLE_AGENT_DELETE_TAG' => $translator->trans('Can delete ticket tags'),
                'ROLE_AGENT_AGENT_KICK' => $translator->trans('Can kick other ticket users')
            ],
            'task' => [
                'ROLE_AGENT_EDIT_TASK' => $translator->trans('Can edit task'),
                'ROLE_AGENT_CREATE_TASK' => $translator->trans('Can create task'),
                'ROLE_AGENT_DELETE_TASK' => $translator->trans('Can delete task'),
                'ROLE_AGENT_ADD_MEMBER_TO_TASK' => $translator->trans('Can add member to task'),
                'ROLE_AGENT_DELETE_MEMBER_FROM_TASK' => $translator->trans('Can remove member from task')
            ],
            'advanced' => [
                'ROLE_AGENT_MANAGE_EMAIL_TEMPLATE' => $translator->trans('Can manage email templates'),
                'ROLE_AGENT_MANAGE_GROUP' => $translator->trans('Can manage groups'),
                'ROLE_AGENT_MANAGE_SUB_GROUP' => $translator->trans('Can manage Sub-Groups/ Teams'),
                'ROLE_AGENT_MANAGE_AGENT' => $translator->trans('Can manage agents'),
                'ROLE_AGENT_MANAGE_AGENT_PRIVILEGE' => $translator->trans('Can manage agent privileges'),
                'ROLE_AGENT_MANAGE_TICKET_TYPE' => $translator->trans('Can manage ticket types'),
                'ROLE_AGENT_MANAGE_CUSTOM_FIELD' => $translator->trans('Can manage ticket custom fields'),
                'ROLE_AGENT_MANAGE_CUSTOMER' => $translator->trans('Can manage customers'),
                'ROLE_AGENT_MANAGE_WORKFLOW_MANUAL' => $translator->trans('Can manage Prepared Responses'),
                'ROLE_AGENT_MANAGE_WORKFLOW_AUTOMATIC' => $translator->trans('Can manage Automatic workflow'),
                'ROLE_AGENT_MANAGE_TAG' => $translator->trans('Can manage tags'),
                'ROLE_AGENT_MANAGE_KNOWLEDGEBASE' => $translator->trans('Can manage knowledgebase'),
                // 'ROLE_AGENT_MANAGE_GROUP_SAVED_REPLY' => $translator->trans("Can manage Group's Saved Reply"),
            ]
        ];
    }

    public function generateCsrfToken($intention)
    {
        $csrf = $this->container->get('security.csrf.token_manager');

        return $csrf->getToken($intention)->getValue();
    }

    /**
     * This function will create content text from recived text, which we can use in meta content and as well in searching save like elastic
     * @param  string $text String text
     * @param  no. $lenght max return lenght string (which will convert to array)
     * @param  boolean $returnArray what return type required
     * @return string/ array comma seperated/ []
     */
    public function createConentToKeywords($text, $lenght = 255, $returnArray = false)
    {
        //to remove all tags from text, if any tags are in encoded form
        $newText = preg_replace('/[\s]+/', ' ', str_replace($this->avoidArray, ' ', strtolower(strip_tags(html_entity_decode(strip_tags($text))))));
        if($lenght)
            $newText = substr($newText, 0, $lenght);
        return ($returnArray ? explode(' ', $newText) : str_replace(' ', ',', $newText));
    }

    public function requestHeadersSent()
    {
        return headers_sent() ? true : false;
    }
}
