<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\ReCaptchaService;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;

class Authentication extends AbstractController
{
    private $userService;
    private $recaptchaService;
    private $authenticationUtils;
    private $eventDispatcher;
    private $translator;

    public function __construct(UserService $userService, AuthenticationUtils $authenticationUtils, EventDispatcherInterface $eventDispatcher, TranslatorInterface $translator, ReCaptchaService $recaptchaService)
    {
        $this->userService = $userService;
        $this->recaptchaService = $recaptchaService;
        $this->authenticationUtils = $authenticationUtils;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
    }

    public function login(Request $request)
    {
        if (null == $this->userService->getSessionUser()) {
            return $this->render('@UVDeskCoreFramework//login.html.twig', [
                'last_username' => $this->authenticationUtils->getLastUsername(),
                'error' => $this->authenticationUtils->getLastAuthenticationError(),
            ]);
        }
        
        return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
    }

    public function logout(Request $request)
    {
        return;
    }

    public function forgotPassword(Request $request)
    {   
        $entityManager = $this->getDoctrine()->getManager();
        $recaptchaDetails = $this->recaptchaService->getRecaptchaDetails();
        if ($request->getMethod() == 'POST') {
            if ($recaptchaDetails && $recaptchaDetails->getIsActive() == true  && $this->recaptchaService->getReCaptchaResponse($request->request->get('g-recaptcha-response'))
            ) {
                $this->addFlash('warning', $this->translator->trans("Warning ! Please select correct CAPTCHA !"));
            } else {
                $user = new User();
                $form = $this->createFormBuilder($user,['csrf_protection' => false])
                        ->add('email',EmailType::class)
                        ->getForm();

                $form->submit(['email' => $request->request->get('forgot_password_form')['email']]);
                $form->handleRequest($request);
                
                if ($form->isValid()) {
                    $repository = $this->getDoctrine()->getRepository('UVDeskCoreFrameworkBundle:User');
                    $user = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User')->findOneByEmail($form->getData()->getEmail());

                    if (!empty($user)) {
                        // Trigger agent forgot password event
                        $event = new GenericEvent(CoreWorkflowEvents\UserForgotPassword::getId(), [
                            'entity' => $user,
                        ]);
                            
                        $this->eventDispatcher->dispatch('uvdesk.automation.workflow.execute', $event);
                        $this->addFlash('success', $this->translator->trans('Please check your mail for password update'));

                        return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));

                    } else {
                        $this->addFlash('warning', $this->translator->trans('This email address is not registered with us'));
                    }
                }
            }
        }
            
        return $this->render("@UVDeskCoreFramework//forgotPassword.html.twig");
    }

    public function updateCredentials($email, $verificationCode, Request $request, UserPasswordEncoderInterface $encoder)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User')->findOneByEmail($email);

        if (empty($user) || $user->getVerificationCode() != $verificationCode) {
            $this->addFlash('success', $this->translator->trans('You have already update password using this link if you wish to change password again click on forget password link here from login page'));

            return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));
        }
        
        if ($request->getMethod() == 'POST') {
            $updatedCredentials = $request->request->all();

            if ($updatedCredentials['password'] === $updatedCredentials['confirmPassword']) {
                $user->setPassword($encoder->encodePassword($user, $updatedCredentials['password']));
                $user->setVerificationCode(TokenGenerator::generateToken());

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', $this->translator->trans('Your password has been successfully updated. Login using updated password'));

                return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));
            } else {
                $this->addFlash('success', $this->translator->trans('Please try again, The passwords do not match'));
            }
        }

        return $this->render("@UVDeskCoreFramework//resetPassword.html.twig");
    }
}
