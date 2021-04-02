<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Recaptcha;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webkul\UVDesk\CoreFrameworkBundle\Form\UserAccount;
use Webkul\UVDesk\CoreFrameworkBundle\Form\UserProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\UserInstance;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UVDeskService;
use Webkul\UVDesk\CoreFrameworkBundle\FileSystem\FileSystem;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;


class Account extends AbstractController
{
    private $userService;
    private $authenticationUtils;
    private $eventDispatcher;
    private $translator;
    private $passwordEncoder;
    private $uvdeskService;
    private $fileSystem;

    public function __construct(UserService $userService, EventDispatcherInterface $eventDispatcher, TranslatorInterface $translator, UserPasswordEncoderInterface $passwordEncoder, UVDeskService $uvdeskService, FileSystem $fileSystem)
    {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->passwordEncoder = $passwordEncoder;
        $this->uvdeskService = $uvdeskService;
        $this->fileSystem = $fileSystem;
        
    }

    private function encodePassword(User $user, $plainPassword)
    {
        $encodedPassword = $this->passwordEncoder->encodePassword($user, $plainPassword);
    }

    public function loadDashboard(Request $request)
    {
        return $this->render('@UVDeskCoreFramework//dashboard.html.twig', []);
    }

    public function listAgents(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_AGENT')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskCoreFramework/Agents/listSupportAgents.html.twig');
    }
    
    public function loadProfile(Request $request)
    {
        // @TODO: Refactor
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $originalUser = clone $user;
        $errors = [];
        $dataFiles = $request->files->get('user_form');

        if ($request->getMethod() == "POST") {
            $data     = $request->request->all();
            $dataFiles = $request->files->get('user_form');

            // Profile upload validation
            $validMimeType = ['image/jpeg', 'image/png', 'image/jpg'];
            if(isset($dataFiles['profileImage'])){
                if(!in_array($dataFiles['profileImage']->getMimeType(), $validMimeType)){
                    $this->addFlash('warning', $this->translator->trans('Error ! Profile image is not valid, please upload a valid format'));
                    return $this->redirect($this->generateUrl('helpdesk_member_profile'));
                }
            }

            $data = $data['user_form'];
            $checkUser = $em->getRepository('UVDeskCoreFrameworkBundle:User')->findOneBy(array('email' => $data['email']));

            $errorFlag = 0;
            if ($checkUser) {
                if($checkUser->getId() != $user->getId())
                    $errorFlag = 1;
            }

            if (!$errorFlag) {
                $password = $user->getPassword();

                $form = $this->createForm(UserProfile::class, $user);
                $form->handleRequest($request);
                $form->submit($data);
                
                if ($form->isValid()) {
                    if ($data != null) {
                        $submittedPassword = $data['password']['first'];
                        $encoder = $this->passwordEncoder;

                        // save previous password if password is blank or null provided
                        $encodedPassword = empty($submittedPassword) ? $password : $encoder->encodePassword($user, $submittedPassword);

                        if (!empty($encodedPassword) ) {
                            $user->setPassword($encodedPassword);
                        } else {
                            $this->addFlash('warning', $this->translator->trans('Error! Given current password is incorrect.'));
                            return $this->redirect($this->generateUrl('helpdesk_member_profile'));
                        }
                    }

                    $user->setFirstName($data['firstName']);
                    $user->setLastName($data['lastName']);
                    $user->setEmail($data['email']);
                    $user->setTimezone($data['timezone']);
                    $user->setTimeformat($data['timeformat']);

                    $em->persist($user);
                    $em->flush();

                    $userInstance = $em->getRepository('UVDeskCoreFrameworkBundle:UserInstance')->findOneBy(array('user' => $user->getId()));
                    $userInstance = $this->userService->getUserDetailById($user->getId());

                    if (isset($dataFiles['profileImage'])) {
                        $assetDetails = $this->fileSystem->getUploadManager()->uploadFile($dataFiles['profileImage'], 'profile');
                        $userInstance->setProfileImagePath($assetDetails['path']);
                    }

                    $userInstance  = $userInstance->setContactNumber($data['contactNumber']);
                    $userInstance  = $userInstance->setSignature($data['signature']);
                    $em->persist($userInstance);
                    $em->flush();

                    $roleId = $user->getAgentInstance()->getSupportRole()->getId();
                    
                    if(in_array($roleId,  [1,2])) {
                        // Recaptcha Setting
                        $recaptchaSetting = $em->getRepository('UVDeskCoreFrameworkBundle:Recaptcha')->findOneBy(['id' => 1]);

                        if($recaptchaSetting) {
                            $recaptchaSetting->setSiteKey($data['recaptcha_site_key']);
                            $recaptchaSetting->setSecretKey($data['recaptcha_secret_key']);
                            if(isset($data['recaptcha_status'])) {
                                $recaptchaSetting->setIsActive(true);
                            } else {
                                $recaptchaSetting->setIsActive(false);
                            }

                            $em->persist($recaptchaSetting);
                            $em->flush();
                        } else {
                            $recaptchaNew = new Recaptcha;
                            $recaptchaNew->setSiteKey($data['recaptcha_site_key']);
                            $recaptchaNew->setSecretKey($data['recaptcha_secret_key']);
                            if(isset($data['recaptcha_status'])) {
                                $recaptchaNew->setIsActive(true);
                            } else {
                                $recaptchaNew->setIsActive(false);
                            }

                            $em->persist($recaptchaNew);
                            $em->flush();
                        }
                    }

                    $this->addFlash('success', $this->translator->trans('Success ! Profile update successfully.'));

                    return $this->redirect($this->generateUrl('helpdesk_member_profile'));
                } else {
                    $errors = $form->getErrors();
                    dump($errors);
                    die;
                    $errors = $this->getFormErrors($form);
                }
            } else {
                $this->addFlash('warning', $this->translator->trans('Error ! User with same email is already exist.'));

                return $this->redirect($this->generateUrl('helpdesk_member_profile'));
            }
        }

        return $this->render('@UVDeskCoreFramework//profile.html.twig', array(
            'user' => $user,
            'errors' => json_encode($errors)
        ));
    }

    public function editAgent($agentId)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_AGENT')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

            // @TODO: Refactor
        $em = $this->getDoctrine()->getManager();
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $activeUser = $this->userService->getSessionUser();
        $user = $em->getRepository('UVDeskCoreFrameworkBundle:User')->find($agentId);
        $instanceRole = $user->getAgentInstance()->getSupportRole()->getCode();

        if (empty($user)) {
            dump('Not found');die;
        }

        switch (strtoupper($request->getMethod())) {
            case 'POST':
                $formErrors = [];
                $data      = $request->request->get('user_form');
                $dataFiles = $request->files->get('user_form');

                // Agent Profile upload validation
                $validMimeType = ['image/jpeg', 'image/png', 'image/jpg'];
                if(isset($dataFiles['profileImage'])){
                    if(!in_array($dataFiles['profileImage']->getMimeType(), $validMimeType)){

                        $this->addFlash('warning', $this->translator->trans('Error ! Profile image is not valid, please upload a valid format'));
                        $response = $this->render('@UVDeskCoreFramework/Agents/updateSupportAgent.html.twig', [
                            'user'         => $user,
                            'instanceRole' => $instanceRole,
                            'errors'       => json_encode([])
                        ]);
                        break;
                    }
                }
                $checkUser = $em->getRepository('UVDeskCoreFrameworkBundle:User')->findOneBy(array('email'=> $data['email']));
                $errorFlag = 0;

                if ($checkUser && $checkUser->getId() != $agentId) {
                    $errorFlag = 1;
                }

                if (!$errorFlag) {
                    if (
                        isset($data['password']['first']) && !empty(trim($data['password']['first'])) 
                        && isset($data['password']['second'])  && !empty(trim($data['password']['second'])) 
                        && trim($data['password']['first']) == trim($data['password']['second'])) {
                        $encodedPassword = $this->passwordEncoder->encodePassword($user, $data['password']['first']);
                        $user->setPassword($encodedPassword);
                    }

                    $user->setFirstName($data['firstName']);
                    $user->setLastName($data['lastName']);
                    $user->setEmail($data['email']);
                    $user->setIsEnabled(isset($data['isActive']));
                    
                    $userInstance = $em->getRepository('UVDeskCoreFrameworkBundle:UserInstance')->findOneBy(['user' => $agentId]);

                    $oldSupportTeam = ($supportTeamList = $userInstance->getSupportTeams()) ? $supportTeamList->toArray() : [];
                    $oldSupportGroup  = ($supportGroupList = $userInstance->getSupportGroups()) ? $supportGroupList->toArray() : [];
                    $oldSupportedPrivilege = ($supportPrivilegeList = $userInstance->getSupportPrivileges())? $supportPrivilegeList->toArray() : [];

                    if(isset($data['role'])) {
                        $role = $em->getRepository('UVDeskCoreFrameworkBundle:SupportRole')->findOneBy(array('code' => $data['role']));
                        $userInstance->setSupportRole($role);
                    }

                    if (isset($data['ticketView'])) {
                        $userInstance->setTicketAccessLevel($data['ticketView']);
                    }

                    $userInstance->setDesignation($data['designation']);
                    $userInstance->setContactNumber($data['contactNumber']);
                    $userInstance->setSource('website');

                    if (isset($dataFiles['profileImage'])) {
                        $assetDetails = $this->fileSystem->getUploadManager()->uploadFile($dataFiles['profileImage'], 'profile');
                        $userInstance->setProfileImagePath($assetDetails['path']);
                    }

                    $userInstance->setSignature($data['signature']);
                    $userInstance->setIsActive(true);

                    if(isset($data['userSubGroup'])){
                        foreach ($data['userSubGroup'] as $userSubGroup) {
                            if($userSubGrp = $this->uvdeskService->getEntityManagerResult(
                                'UVDeskCoreFrameworkBundle:SupportTeam',
                                'findOneBy', [
                                    'id' => $userSubGroup
                                ]
                            )
                            )
                                if(!$oldSupportTeam || !in_array($userSubGrp, $oldSupportTeam)){
                                    $userInstance->addSupportTeam($userSubGrp);

                                }elseif($oldSupportTeam && ($key = array_search($userSubGrp, $oldSupportTeam)) !== false)
                                    unset($oldSupportTeam[$key]);
                        }

                        foreach ($oldSupportTeam as $removeteam) {
                            $userInstance->removeSupportTeam($removeteam);
                            $em->persist($userInstance);
                        }
                    }

                    if(isset($data['groups'])){
                        foreach ($data['groups'] as $userGroup) {
                            if($userGrp = $this->uvdeskService->getEntityManagerResult(
                                'UVDeskCoreFrameworkBundle:SupportGroup',
                                'findOneBy', [
                                    'id' => $userGroup
                                ]
                            )
                            )

                                if(!$oldSupportGroup || !in_array($userGrp, $oldSupportGroup)){
                                    $userInstance->addSupportGroup($userGrp);

                                }elseif($oldSupportGroup && ($key = array_search($userGrp, $oldSupportGroup)) !== false)
                                    unset($oldSupportGroup[$key]);
                        }

                        foreach ($oldSupportGroup as $removeGroup) {
                            $userInstance->removeSupportGroup($removeGroup);
                            $em->persist($userInstance);
                        }
                    }

                    if(isset($data['agentPrivilege'])){
                        foreach ($data['agentPrivilege'] as $supportPrivilege) {
                            if($supportPlg = $this->uvdeskService->getEntityManagerResult(
                                'UVDeskCoreFrameworkBundle:SupportPrivilege',
                                'findOneBy', [
                                    'id' => $supportPrivilege
                                ]
                            )
                            )
                                if(!$oldSupportedPrivilege || !in_array($supportPlg, $oldSupportedPrivilege)){
                                    $userInstance->addSupportPrivilege($supportPlg);

                                }elseif($oldSupportedPrivilege && ($key = array_search($supportPlg, $oldSupportedPrivilege)) !== false)
                                    unset($oldSupportedPrivilege[$key]);
                        }
                        foreach ($oldSupportedPrivilege as $removeGroup) {
                            $userInstance->removeSupportPrivilege($removeGroup);
                            $em->persist($userInstance);
                        }
                    }

                    $userInstance->setUser($user);
                    $user->addUserInstance($userInstance);
                    $em->persist($user);
                    $em->persist($userInstance);
                    $em->flush();

                    // Trigger customer Update event
                    $event = new GenericEvent(CoreWorkflowEvents\Agent\Update::getId(), [
                        'entity' => $user,
                    ]);

                    $this->eventDispatcher->dispatch('uvdesk.automation.workflow.execute', $event);

                    $this->addFlash('success', $this->translator->trans('Success ! Agent updated successfully.'));
                    return $this->redirect($this->generateUrl('helpdesk_member_account_collection'));
                } else {
                    $this->addFlash('warning', $this->translator->trans('Error ! User with same email is already exist.'));
                }

                $response = $this->render('@UVDeskCoreFramework/Agents/updateSupportAgent.html.twig', [
                    'user' => $user,
                    'instanceRole' => $instanceRole,
                    'errors' => json_encode([])
                ]);
                break;
            default:
                $response = $this->render('@UVDeskCoreFramework/Agents/updateSupportAgent.html.twig', [
                    'user'         => $user,
                    'instanceRole' => $instanceRole,
                    'errors'       => json_encode([])
                ]);
                break;
        }

        return $response;
    }

    public function createAgent(Request $request)
    {
        // @TODO: Refactor
        if(!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_AGENT')){          
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $user = new User();
        $userServiceContainer = $this->userService;

        if ('POST' == $request->getMethod()) {
            $formDetails = $request->request->get('user_form');
            $uploadedFiles = $request->files->get('user_form');
            $entityManager = $this->getDoctrine()->getManager();

            // Profile upload validation
            $validMimeType = ['image/jpeg', 'image/png', 'image/jpg'];
            if(isset($uploadedFiles['profileImage'])){
                if(!in_array($uploadedFiles['profileImage']->getMimeType(), $validMimeType)){
                    $this->addFlash('warning', $this->translator->trans('Error ! Profile image is not valid, please upload a valid format'));
                    return $this->redirect($this->generateUrl('helpdesk_member_create_account'));
                }
            }

            $user = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User')->findOneByEmail($formDetails['email']);
            $agentInstance = !empty($user) ? $user->getAgentInstance() : null;

            if (empty($agentInstance)) {
                if (!empty($formDetails)) {
                    $fullname = trim(implode(' ', [$formDetails['firstName'], $formDetails['lastName']]));
                    $supportRole = $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportRole')->findOneByCode($formDetails['role']);

                    $user = $this->userService->createUserInstance($formDetails['email'], $fullname, $supportRole, [
                        'contact' => $formDetails['contactNumber'],
                        'source' => 'website',
                        'active' => !empty($formDetails['isActive']) ? true : false,
                        'image' => $uploadedFiles['profileImage'],
                        'signature' => $formDetails['signature'],
                        'designation' => $formDetails['designation'],
                    ]);

                    $userInstance = $user->getAgentInstance();

                    if (isset($formDetails['ticketView'])) {
                        $userInstance->setTicketAccessLevel($formDetails['ticketView']);
                    }

                    // Map support team
                    if (!empty($formDetails['userSubGroup'])) {
                        $supportTeamRepository = $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportTeam');

                        foreach ($formDetails['userSubGroup'] as $supportTeamId) {
                            $supportTeam = $supportTeamRepository->findOneById($supportTeamId);

                            if (!empty($supportTeam)) {
                                $userInstance->addSupportTeam($supportTeam);
                            }
                        }
                    }
                    // Map support group
                    if (!empty($formDetails['groups'])) {
                        $supportGroupRepository = $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportGroup');

                        foreach ($formDetails['groups'] as $supportGroupId) {
                            $supportGroup = $supportGroupRepository->findOneById($supportGroupId);

                            if (!empty($supportGroup)) {
                                $userInstance->addSupportGroup($supportGroup);
                            }
                        }
                    }
                    // Map support privileges
                    if (!empty($formDetails['agentPrivilege'])) {
                        $supportPrivilegeRepository = $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportPrivilege');

                        foreach($formDetails['agentPrivilege'] as $supportPrivilegeId) {
                            $supportPrivilege = $supportPrivilegeRepository->findOneById($supportGroupId);

                            if (!empty($supportPrivilege)) {
                                $userInstance->addSupportPrivilege($supportPrivilege);
                            }
                        }
                    }

                    $entityManager->persist($userInstance);
                    $entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('Success ! Agent added successfully.'));
                    return $this->redirect($this->generateUrl('helpdesk_member_account_collection'));
                }
            } else {
                $this->addFlash('warning', $this->translator->trans('Error ! User with same email already exist.'));
            }
        }

        return $this->render('@UVDeskCoreFramework/Agents/createSupportAgent.html.twig', [
            'user' => $user,
            'errors' => json_encode([])
        ]);
    }
}
