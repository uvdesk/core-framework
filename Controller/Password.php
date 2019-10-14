<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;

class Password extends AbstractController
{   
    protected $userService;
    protected $eventDispatcher;
    protected $passwordEncoder;

    public function __construct(UserService $userService, EventDispatcherInterface $eventDispatcher, UserPasswordEncoderInterface $passwordEncoder) {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function forgotPassword(Request $request)
    {   
        $session = $request->getSession();
        $entityManager = $this->getDoctrine()->getManager();
            
        if ($request->getMethod() == 'POST') {
            $user = new User();
            $form = $this->createFormBuilder($user,['csrf_protection' => false])
                    ->add('email',EmailType::class)
                    ->getForm();

            $form->submit(['email' => $request->request->get('forgot_password_form')['email']]);
            $form->handleRequest($request);
            
            if ($form->isValid()) {
                $repository = $this->getDoctrine()->getRepository('UVDeskCoreFrameworkBundle:User');
                $user = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User')->findOneBy(array('email' => $form->getData()->getEmail()));
            
                if ($user) {
                    $session->getFlashBag()->set('success','Please check your mail for password update.');
                    
                    // Trigger agent forgot password event
                    $event = new GenericEvent(CoreWorkflowEvents\User\ForgotPassword::getId(), [
                        'entity' => $user,
                    ]);
                    $this->eventDispatcher->dispatch('uvdesk.automation.workflow.execute', $event);
                } else {
                    $session->getFlashBag()->set('warning', 'This Email address is not registered with us.');
                }
            }
        }
            
        return $this->render("@UVDeskCoreFramework//forgotPassword.html.twig");
    }

    public function resetPassword(Request $request, $email, $verificationCode)
    {   
        $session = $request->getSession();
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User')->findOneByEmail($email);

        if (empty($user) || $user->getVerificationCode() != $verificationCode) {
            $session->getFlashBag()->set('warning', 'Invalid Credentials.');
            
            return $this->render("@UVDeskCoreFramework//resetPassword.html.twig");
        }

        if ($request->getMethod() == 'POST') {
            $updatedCredentials = $request->request->all();

            if ($updatedCredentials['password'] === $updatedCredentials['confirmPassword']) {
                $user->setPassword($this->passwordEncoder->encodePassword($user, $updatedCredentials['password']));
                $user->setVerificationCode(TokenGenerator::generateToken());

                $entityManager->persist($user);
                $entityManager->flush();

                $session->getFlashBag()->set('success', 'Your password has been updated successfully.');
            } else {
                $session->getFlashBag()->set(
                    'warning',
                    $this->get('translator')->trans('Password don\'t match.')
                );
            }
        }

        return $this->render("@UVDeskCoreFramework//resetPassword.html.twig");
    }
}
