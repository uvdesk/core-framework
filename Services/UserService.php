<?php

namespace Webkul\UVDesk\CoreBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Criteria;
use Webkul\UVDesk\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Webkul\UVDesk\CoreBundle\Entity\SupportRole;
use Webkul\UVDesk\CoreBundle\Entity\UserInstance;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserService
{
    protected $container;
	protected $requestStack;
    protected $entityManager;

    public function __construct(ContainerInterface $container, RequestStack $requestStack, EntityManager $entityManager)
    {
        $this->container = $container;
		$this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    public function isGranted($role) {
        $securityContext = $this->container->get('security.token_storage');
       
        try {
            return (bool) ($role == $securityContext->getToken()->getRoles()[0]->getRole());
        } catch (AuthenticationCredentialsNotFoundException $e) {
            // @TODO: Handle Authentication Failure
        }

        return false;
    }
    
    public function getSessionUser()
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        return $user instanceof User ? $user : null;
    }

    public function getCurrentUser()
    {
        if ($this->container->get('security.token_storage')->getToken()) {
            return $this->container->get('security.token_storage')->getToken()->getUser();
        } else {
            return false;
        }
    }
    
    public function isAccessAuthorized($scope, User $user = null)
    {
        // Return false if no user is provided
        if (empty($user) && null == $this->getSessionUser()) {
            return false;
        }
        
        switch ($scope) {
            default:
                break;
        }

        return true;
    }

    public function checkPermission($role)
    {
        $securityContext = $this->container->get('security.token_storage')->getToken();
        
        if ($this->isGranted('ROLE_SUPER_ADMIN') || $this->isGranted('ROLE_ADMIN')) {
            return true;
        } else if ($this->isGranted('ROLE_AGENT')) {
            $agentPrivileges = $this->getUserPrivileges($this->getCurrentUser()->getId());
            $agentPrivileges = array_merge($agentPrivileges, ['saved_filters_action', 'saved_replies']);
            
            return in_array($role, $agentPrivileges) ? true : false;
        } else {
            return false;
        }
    }

    public function getUserPrivileges($userId)
    {
        static $agentPrivilege = [];
        
        if (isset($agentPrivilege[$userId])) {
            return $agentPrivilege[$userId];
        }
        
        $userPrivileges = array();
        $user = $this->entityManager->getRepository('UVDeskCoreBundle:User')->find($userId);
        $privileges = $user->getAgentInstance()->getSupportPrivileges();  
      
        if ($privileges) {
            foreach ($privileges as $privilege) {
                $userPrivileges = array_merge($userPrivileges, $privilege->getPrivileges());
            }
        }
        
        $agentPrivilege[$userId] = $this->agentPrivilege[$userId] = $userPrivileges;  
        return $userPrivileges;
    }

    public function getSupportPrivileges()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("supportPrivilege")->from('UVDeskCoreBundle:SupportPrivilege', 'supportPrivilege');
        
        return $qb->getQuery()->getArrayResult();
    }

    public function getSupportGroups(Request $request = null)
    {
        static $results;
        if(null !== $results)
            return $results;
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('supportGroup.id, supportGroup.name')->from('UVDeskCoreBundle:SupportGroup', 'supportGroup')
                ->andwhere('supportGroup.isActive = 1');
        if($request) {
            $qb->andwhere("supportGroup.name LIKE :groupName");
            $qb->setParameter('groupName', '%'.urldecode($request->query->get('query')).'%');
            $qb->andwhere("supportGroup.id NOT IN (:ids)");
            $qb->setParameter('ids', explode(',',urldecode($request->query->get('not'))));
        }
        return $results = $qb->getQuery()->getArrayResult();
    }

    public function getSupportTeams(Request $request = null)
    {
        static $results;
        if(null !== $results)
            return $results;
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select("user.id, user.email, CONCAT(user.firstName, ' ', user.lastName) as name, userInstance.profileImagePath as smallThumbnail")
            ->from('UVDeskCoreBundle:User', 'user')
            ->leftJoin('user.userInstance', 'userInstance')
            ->leftJoin('userInstance.supportRole', 'supportRole')
            ->where('supportRole.code != :customerRole')->setParameter('customerRole', 'ROLE_CUSTOMER')
            ->andWhere('userInstance.isActive = :isUserActive')->setParameter('isUserActive', true)
            ->orderBy('name', Criteria::ASC);

        if ($request && null != $request->query->get('query')) {
            $queryBuilder
                ->andWhere("CONCAT(dt.firstName,' ', dt.lastName) LIKE :customerName")
                ->setParameter('customerName', '%'.urldecode($request->query->get('query')).'%');
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('supportTeam.id, supportTeam.name')
           ->from('UVDeskCoreBundle:SupportTeam', 'supportTeam');
        $qb->andwhere('supportTeam.isActive = 1');
        
        if($request) {
            $qb->andwhere("supportTeam.name LIKE :subGroupName");
            $qb->setParameter('subGroupName', '%'.urldecode($request->query->get('query')).'%');
            $qb->andwhere("supportTeam.id NOT IN (:ids)");
            $qb->setParameter('ids', explode(',',urldecode($request->query->get('not'))));
        }

        return $results = $qb->getQuery()->getResult();
    }

    public function createUserInstance($email, $name, SupportRole $role, array $extras = [])
    {
        $user = $this->entityManager->getRepository('UVDeskCoreBundle:User')->findOneByEmail($email) ?: new User();

        if (null == $user->getId()) {
            $name = explode(' ', trim($name));

            $user->setEmail($email);
            $user->setFirstName(array_shift($name));
            $user->setLastName(trim(implode(' ', $name)));
            $user->setIsEnabled(true);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        
        $userInstance = 'ROLE_CUSTOMER' == $role->getCode() ? $user->getCustomerInstance() : $user->getAgentInstance();
        
        if (empty($userInstance)) {
            $userInstance = new UserInstance();
                
            $userInstance->setUser($user);
            $userInstance->setSupportRole($role);
            $userInstance->setContactNumber(!empty($extras['contact']) ? $extras['contact'] : null);
            $userInstance->setSkypeId(!empty($extras['skype']) ? $extras['skype'] : null);
            $userInstance->setDesignation(!empty($extras['designation']) ? $extras['designation'] : null);
            $userInstance->setSignature(!empty($extras['signature']) ? $extras['signature'] : null);

            $userInstance->setSource(!empty($extras['source']) ? $extras['source'] : 'website');
            $userInstance->setIsActive(!empty($extras['active']) ? (bool) $extras['active'] : false);
            $userInstance->setIsVerified(!empty($extras['verified']) ? (bool) $extras['verified'] : false);
            $userInstance->setIsStarred(!empty($extras['starred']) ? (bool) $extras['starred'] : false);

            if (!empty($extras['image'])) {
                $fileName = $this->container->get('uvdesk.service')->getFileUploadManager()->upload($extras['image']);

                if (!empty($fileName)) {
                    $userInstance->setProfileImagePath($fileName);
                }
            }

            $this->entityManager->persist($userInstance);
            $this->entityManager->flush();

            $user->addUserInstance($userInstance);
        }

        return $user;
    }

    public function getAgentPartialDataCollection(Request $request = null)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select("user.id, user.email, CONCAT(user.firstName, ' ', user.lastName) as name, userInstance.profileImagePath as smallThumbnail")
            ->from('UVDeskCoreBundle:User', 'user')
            ->leftJoin('user.userInstance', 'userInstance')
            ->leftJoin('userInstance.supportRole', 'supportRole')
            ->where('supportRole.code != :customerRole')->setParameter('customerRole', 'ROLE_CUSTOMER')
            ->andWhere('userInstance.isActive = :isUserActive')->setParameter('isUserActive', true)
            ->orderBy('name', Criteria::ASC);

        if ($request && null != $request->query->get('query')) {
            $queryBuilder
                ->andWhere("CONCAT(user.firstName,' ', user.lastName) LIKE :customerName")
                ->setParameter('customerName', '%'.urldecode($request->query->get('query')).'%');
        }

        if ($request && null != $request->query->get('not')) {
            $queryBuilder
                ->andwhere("u.id NOT IN (:ids)")
                ->setParameter('ids', explode(',', urldecode($request->query->get('not'))));
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getAgentsPartialDetails(Request $request = null) {
        static $agents;
        if (null !== $agents)
            return $agents;

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("u.id, userInstance.id as udId,u.email,CONCAT(u.firstName,' ', u.lastName) AS name,userInstance.profileImagePath as smallThumbnail")->from('UVDeskCoreBundle:User', 'u')
                ->leftJoin('u.userInstance', 'userInstance')
                ->andwhere('userInstance.supportRole != :roles')
                ->setParameter('roles', 4)
                ->andwhere('userInstance.isActive = 1')
                ->orderBy('name','ASC');

        if($request) {
            $qb->andwhere("CONCAT(u.firstName,' ', u.lastName) LIKE :customerName");
            $qb->setParameter('customerName', '%'.urldecode($request->query->get('query')).'%');
            $qb->andwhere("u.id NOT IN (:ids)");
            $qb->setParameter('ids', explode(',',urldecode($request->query->get('not'))));
        }

        $data = $agents = $qb->getQuery()->getArrayResult();
        return $data;
    }

    public function getAgentDetailById($agentId) {
        if(!$agentId) return;
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("DISTINCT u.id,u.email,CONCAT(u.firstName,' ', u.lastName) AS name,u.firstName,u.lastName,userInstance.profileImagePath,userInstance.profileImagePath as smallThumbnail,userInstance.isActive, userInstance.isVerified, userInstance.designation, userInstance.contactNumber,userInstance.signature,userInstance.ticketAccessLevel")
            ->from('UVDeskCoreBundle:User', 'u')
            ->leftJoin('u.userInstance', 'userInstance')
            ->andwhere('userInstance.supportRole != :roles')
            ->andwhere('u.id = :agentId')
            ->setParameter('roles', 4)
            ->setParameter('agentId', $agentId);

        $result = $qb->getQuery()->getResult();

        return isset($result[0]) ? $result[0] : null;
    }

    public function getUsersByGroupId($groupId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("DISTINCT user.id, user.email, CONCAT(user.firstName, ' ', user.lastName) AS name, userInstance.profileImagePath as smallThumbnail")
            ->from('UVDeskCoreBundle:User', 'user')
            ->leftJoin('user.userInstance', 'userInstance')
                ->leftJoin('userInstance.supportGroups', 'supportGroup')
                ->andWhere('userInstance.supportRole != :roles')->setParameter('roles', 4)
                ->andwhere('supportGroup.id = :groupId')->setParameter('groupId', $groupId)
                ->andwhere('userInstance.isActive = 1');

        $data = $qb->getQuery()->getArrayResult();
        return $data;
    }

    public function getUsersBySubGroupId($subGroupId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("DISTINCT user.id, supportTeam.id as udId,user.email,CONCAT(user.firstName,' ', user.lastName) AS name,userInstance.profileImagePath as smallThumbnail")
                ->from('UVDeskCoreBundle:User', 'user')
                ->leftJoin('user.userInstance', 'userInstance')
                ->leftJoin('userInstance.supportTeams', 'supportTeam')
                ->andwhere('userInstance.supportRole != :roles')
                ->andwhere('supportTeam.id = :subGroupId')
                ->setParameter('roles', 4)
                ->setParameter('subGroupId', $subGroupId)
                ->andwhere('supportTeam.isActive = 1')
                ->andwhere('userInstance.isActive = 1');

        $data = $qb->getQuery()->getArrayResult();
        return $data;
    }

    public function getCustomerDetailsById($customerId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("user.id,user.email,CONCAT(user.firstName,' ', user.lastName) AS name,user.firstName,user.lastName,userInstance.contactNumber,userInstance.profileImagePath,userInstance.profileImagePath as smallThumbnail,userInstance.isActive, userInstance.isVerified")->from('UVDeskCoreBundle:User', 'user')
                ->leftJoin('user.userInstance', 'userInstance')
                ->andwhere('userInstance.supportRole = :roles')
                ->andwhere('user.id = :customerId')
                ->setParameter('roles', 4)
                ->setParameter('customerId', $customerId);

        $result = $qb->getQuery()->getResult();
        return ($result ? $result[0] : null);
    }

    public function getCustomerPartialDetailById($customerId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("u.id,u.email,CONCAT(u.firstName,' ', u.lastName) AS name,u.firstName,u.lastName,userInstance.contactNumber,userInstance.profileImagePath,userInstance.profileImagePath as smallThumbnail")->from('UVDeskCoreBundle:User', 'u')
            ->leftJoin('u.userInstance', 'userInstance')
            ->andwhere('userInstance.supportRole = :roles')
            ->andwhere('u.id = :customerId')
            ->setParameter('roles', 4)
            ->setParameter('customerId', $customerId);

        $result = $qb->getQuery()->getResult();
        return ($result ? $result[0] : null);
    }

    public function getCustomersPartial(Request $request = null)
    {
        $qb = $this->entityManager->createQueryBuilder();

        if($this->getCurrentUser()->getRole() == "ROLE_AGENT" && $this->getCurrentUser()->detail['agent']->getTicketView() != UserData::GLOBAL_ACCESS) {
            $qb->from('UVDeskCoreBundle:Ticket', 't')
                ->leftJoin('t.customer', 'c');

            $this->entityManager->getRepository('WebkulCoreBundle:Ticket')->addPermissionFilter($qb, $this->container, false);
        } else {
            $qb->from('WebkulUserBundle:User', 'c');
        }

        $qb->select("DISTINCT c.id,CONCAT(userInstance.firstName,' ', userInstance.lastName) AS name, c.profileImage as smallThumbnail ")
                ->leftJoin('c.data', 'userInstance')
                ->andwhere('userInstance.companyId = :userCompanyId')
                ->andwhere('userInstance.userRole = :roles')
                ->setParameter('roles', 4)
                ->setParameter('userCompanyId', $this->getCompany()->getId())
                ->orderBy('name','ASC');

        if($request) {
            if($request->query->get('query') && $this->getCompany() && $this->getCompany()->getId() !== 1) {
                $qb->andwhere("CONCAT(userInstance.firstName,' ', userInstance.lastName) LIKE :customerName OR c.email LIKE :customerName");
            } else {
                $qb->andwhere("CONCAT(userInstance.firstName,' ', userInstance.lastName) LIKE :customerName");
            }
            $qb->setParameter('customerName', '%'.urldecode($request->query->get('query')).'%');
            $qb->andwhere("c.id NOT IN (:ids)");
            $qb->setParameter('ids', explode(',',urldecode($request->query->get('not'))));
        }

        $query = $qb->getQuery();

        // $query->useResultCache(true, 3600, 'customer_list_'.$this->getCompany()->getId());

        $result = $query->getScalarResult();
        return $result;
    }

    public function getCustomersCount()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select($qb->expr()->countDistinct('c.id')."as customerCount")->from('UVDeskCoreBundle:Ticket', 't')
                ->leftJoin('t.customer', 'c');

        $this->entityManager->getRepository('UVDeskCoreBundle:Ticket')->addPermissionFilter($qb, $this->container, false);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getUserSubGroupIds($userId) {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('supportTeams.id')->from('UVDeskCoreBundle:User', 'user')
                ->leftJoin('user.userInstance','userInstance')
                ->leftJoin('userInstance.supportTeams','supportTeams')
                ->andwhere('user.id = :userId')
                ->andwhere('userInstance.supportRole != :agentRole')
                ->andwhere('supportTeams.isActive = 1')
                ->setParameter('userId', $userId)
                ->setParameter('agentRole', '4'); 

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }

    public function getUserGroupIds($userId) {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('supportGroup.id')->from('UVDeskCoreBundle:User', 'user')
                ->leftJoin('user.userInstance','userInstance')
                ->leftJoin('userInstance.supportGroups','supportGroup')
                ->andwhere('user.id = :userId')
                ->andwhere('supportGroup.isActive = 1')
                ->setParameter('userId', $userId);

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }

    public function createUser($data)
    {
        $user = new User();
        $user->setEmail($data['from']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setIsEnabled($data['isActive']);
        $this->entityManager->persist($user);
        // $this->entityManager->flush();
        $role = $this->entityManager->getRepository('UVDeskCoreBundle:SupportRole')->find($data['role']);
    
        $userInstance = new UserInstance();
        $userInstance->setSupportRole($role);
        $userInstance->setUser($user);
        $userInstance->setIsActive($data['isActive']);
        $userInstance->setIsVerified(0);
        if(isset($data['source']))
            $userInstance->setSource($data['source']);
        else
            $userInstance->setSource('website');
        if(isset($data['contactNumber'])) {
            $userInstance->setContactNumber($data['contactNumber']);
        }
        if(isset($data['profileImage']) && $data['profileImage']) {
                $userInstance->setProfileImagePath($data['profileImage']);
        }
        $this->entityManager->persist($userInstance);
        $this->entityManager->flush();

        $user->addUserInstance($userInstance);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        //$user->setUserName($userInstance->getName());
        return $user;
    }

    public function getWebsiteConfiguration($code)
    {
        $enabled_bundles = $this->container->getParameter('kernel.bundles');
        
        if (!in_array('UVDeskSupportCenterBundle', array_keys($enabled_bundles))) {
            return [
                'id' =>  1,
                'website' =>  1,
                'status' =>  1,
                'brandColor' => '#7E91F0',
                'pageBackgroundColor' => '#FFFFFF',
                'headerBackgroundColor' => '#FFFFFF',
                'bannerBackgroundColor' => '#7085F4',
                'navTextColor' =>  '#7085F4',
                'navActiveColor' => '#7085F4',
                'linkColor' => '#7085F4',
                'linkHoverColor' => '#7085F4',
                'headerLinks' => null,
                'footerLinks' => null,
                'articleTextColor' => '#7085F4',
                'whiteList' => null,
                'blackList' => null,
                'siteDescritption' => 'Hi! how can i help you.',
                'metaDescription' => null,
                'metaKeywords' => null,
                'homepageContent' => null,
                'ticketCreateOption' =>  1,
                'createdAt' =>  '2018-09-21 16:20:01',
                'updatedat' =>  '2018-09-21 16:20:01',
                'broadcastMessage' => null,
                'removeCustomerLoginButton' => null,
                'disableCustomerlogin' =>  0,
                'removeBrandingContent' => null,
                'loginRequiredToCreate' => null,
                'script' => null,
                'customCss' => null,
                'isActive' => 1,
            ];
        }

        // find current user from session(admin or customer)
        $em = $this->entityManager;
        $websiteRepo = $em->getRepository('UVDeskCoreBundle:Website');
        $configurationRepo = $em->getRepository('UVDeskSupportCenterBundle:KnowledgebaseWebsite');

        $website = $websiteRepo->findOneByCode($code);
        if ($website)
            $configuration = $configurationRepo->findOneBy(['website' => $website->getId(), 'isActive' => 1]);

        return $configuration ?: false;
    }

    public function getWebsiteDetails($currentUser)
    {
        // find current user from session(admin or customer)
        $em = $this->entityManager;
        $websiteRepo = $em->getRepository('UVDeskCoreBundle:Website');

        $website = $websiteRepo->findOneBy(['code' => $currentUser]);

        return $website ? $website : false;
    }

    public function getUserDetails($data)
    {
        $user = $this->entityManager->getRepository('UVDeskCoreBundle:User')->findOneBy(['email' => $data['from']]);
        $role = $this->entityManager->getRepository('UVDeskCoreBundle:SupportRole')->find($data['role']);

        if(!$user) {
            //create user
            $user = $this->createUserInstance($data['from'], $data['fullname'], $role, $data);
        } else {
            $checkCustomer = $this->entityManager->getRepository('UVDeskCoreBundle:User')->findOneBy(['email' => $data['from']]);

            if(!$checkCustomer) {
                $role = $this->entityManager->getRepository('UVDeskCoreBundle:Role')->find($data['role']);
                $userData = new UserData();
                $userData->setUserRole($role);
                $userData->setUser($user);
                $userData->setFirstName($data['firstName']);
                $userData->setLastName($data['lastName']);
                if(isset($data['isActive']))
                    $userData->setIsActive($data['isActive']);
                else
                    $userData->setIsActive(1);
                $userData->setIsVerified(0);
                if(isset($data['source']))
                    $userData->setSource($data['source']);
                else
                    $userData->setSource('website');
                if(isset($data['contactNumber']))
                    $userData->setContactNumber($data['contactNumber']);
                $this->entityManager->persist($userData);
                $this->entityManager->flush();

                $user->addData($userData);
                $user->setValidationCode($key = str_shuffle(time()));
                $user->setIsEmailPending(1);
                if(isset($data['profileImage']) && $data['profileImage']) {
                    $user->setProfileImage($data['profileImage']);
                }
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $user->setUserName($userData->getName());

                if(!(isset($data['skipWorkflow']) && $data['skipWorkflow'])) {
                    $this->container->get('event.manager')->trigger([
                            'event' => 'customer.created',
                            'entity' => $user
                        ]);
                }
            }
        }

        return $user;
    }

    public function convertToTimezone($date, $format = "d-m-Y h:ia")
    {
        if(!$date)
            return "N/A";
        $currentUser = $this->getCurrentUser();
        $date = date_format($date,$format);
        $dateTime = date('Y-m-d H:i:s',strtotime($date));
        $scheduleDate = new \DateTime($dateTime, new \DateTimeZone(date_default_timezone_get()));
        $this->domain = $this->container->get('router')->getContext()->getHost();

        $scheduleDate->setTimeZone(new \DateTimeZone('Asia/Kolkata'));

        return $scheduleDate->format('Asia/Kolkata');
    }

    public function convertToDatetimeTimezoneTimestamp($date, $format = "d-m-Y h:ia")
    {
        if(!$date)
            return "N/A";
        $currentUser = $this->getCurrentUser();
        $date = date_format($date, $format);
        $dateTime = date('Y-m-d H:i:s',strtotime($date));
        $scheduleDate = new \DateTime($dateTime, new \DateTimeZone(date_default_timezone_get()));
        $this->domain = $this->container->get('router')->getContext()->getHost();

        $scheduleDate->setTimeZone(new \DateTimeZone('Asia/Kolkata'));

        return $scheduleDate->getTimestamp();
    }

    public function removeCustomer($customer)
    {
        $userData = $this->entityManager->getRepository('UVDeskCoreBundle:UserInstance')->findBy(array('user' => $customer->getId()));
        $count = count($userData);

        // getCustomerTickets
        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->delete('UVDeskCoreBundle:Ticket', 't')
                    ->andwhere('t.customer = :customerId')
                    ->setParameter('customerId', $customer->getId())
                    ->getQuery();

        $query->execute();

        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->delete('UVDeskCoreBundle:UserInstance', 'userInstance')
                    ->andwhere('userInstance.user = :customerId')
                    ->andwhere('userInstance.supportRole = :roleId')
                    ->setParameter('customerId', $customer->getId())
                    ->setParameter('roleId', 4)
                    ->getQuery();

        $query->execute();

        if($count == 1) {
            $this->entityManager->remove($customer);
            $this->entityManager->flush();
        }
    }
    
    public function removeAgent($user)
    {
        $userData = $this->entityManager->getRepository('UVDeskCoreBundle:UserInstance')->findBy(array('user' => $user->getId()));
        $count = count($userData);

        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->delete('UVDeskCoreBundle:UserInstance', 'ud')
                    ->andwhere('ud.user = :userId')
                    ->andwhere('ud.supportRole != :roleId')
                    ->setParameter('userId', $user->getId())
                    ->setParameter('roleId', 3)
                    ->getQuery();

        $query->execute();
        
        foreach ($user->getAgentInstance()->getSupportGroups() as $group) {
                $user->getAgentInstance()->removeSupportGroup($group);
                $this->entityManager->persist($group);
                $this->entityManager->flush();
            
        }

        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->update('UVDeskCoreBundle:Ticket', 't')
                    ->set('t.agent', ':nullAgent')
                    ->andwhere('t.agent = :agentId')
                    ->setParameter('agentId', $user->getId())
                    ->setParameter('nullAgent', null)
                    ->getQuery();

        $query->execute();

       
        if($count == 1) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    public function getWebsiteView()
    {
        $website = $this->entityManager->getRepository('UVDeskCoreBundle:Website')->findOneBy(['code'=>'knowledgebase']);
        $layout  = $this->entityManager->getRepository('UVDeskSupportCenterBundle:KnowledgebaseWebsite')->findOneBy(['website'=>$website->getId()]);
      
        $homepageContent = $layout->getHomepageContent();
        return (!empty($homepageContent)) ? $homepageContent . 'View' : 'masonryView';
    }

    public function getUserDetailById($userId) {
        $user = $this->entityManager->getRepository('UVDeskCoreBundle:User')->find($userId);
        foreach ($user->getUserInstance() as $row) {
            if($row->getSupportRole()->getId() != 4)
                return $row;
        }
        return null;
    }

    public function getUserPrivilegeIds($userId) 
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('supportPrivileges.id')->from('UVDeskCoreBundle:User', 'user')
                ->leftJoin('user.userInstance','userInstance')
                ->leftJoin('userInstance.supportPrivileges','supportPrivileges')
                ->andwhere('user.id = :userId')
                ->setParameter('userId', $userId);

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }
}
