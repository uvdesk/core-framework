<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;

class Authentication extends Controller
{
    public function login(Request $request)
    {
        if (null == $this->get('user.service')->getSessionUser()) {
            return $this->render('@UVDeskCoreFramework//login.html.twig', [
                'last_username' => $this->get('security.authentication_utils')->getLastUsername(),
                'error' => $this->get('security.authentication_utils')->getLastAuthenticationError(),
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
        if (null != $this->get('user.service')->getSessionUser()) {
            return new Response('How did you land here? :/', 404);
        }

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
                $user = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User')->findOneByEmail($form->getData()->getEmail());
            
                if (!empty($user)) {
                    // Trigger agent forgot password event
                    $event = new GenericEvent(CoreWorkflowEvents\UserForgotPassword::getId(), [
                        'entity' => $user,
                    ]);
                        
                    $this->get('event_dispatcher')->dispatch('uvdesk.automation.workflow.execute', $event);
                    $request->getSession()->getFlashBag()->set('success', 'Please check your mail for password update.');
                } else {
                    $request->getSession()->getFlashBag()->set('warning', 'This email address is not registered with us.');
                }
            }
        }
            
        return $this->render("@UVDeskCoreFramework//forgotPassword.html.twig");
    }

    public function updateCredentials($email, $verificationCode, Request $request, UserPasswordEncoderInterface $encoder)
    {
        if (empty($email) || empty($verificationCode)) {
            return new Response('How did you land here? :/', 404);
        } else {
            $entityManager = $this->getDoctrine()->getManager();
            $user = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User')->findOneByEmail($email);
    
            if (empty($user) || $user->getVerificationCode() != $verificationCode) {
                return new Response('How did you land here? :/', 404);
            }
        }

        if ($request->getMethod() == 'POST') {
            $updatedCredentials = $request->request->all();

            if ($updatedCredentials['password'] === $updatedCredentials['confirmPassword']) {
                $user->setPassword($encoder->encodePassword($user, $updatedCredentials['password']));
                $user->setVerificationCode(TokenGenerator::generateToken());

                $entityManager->persist($user);
                $entityManager->flush();

                $request->getSession()->getFlashBag()->set('success', 'Your password has been updated successfully.');
            } else {
                $request->getSession()->getFlashBag()->set('warning', "Please try again. The passwords do not match.");
            }
        }

        return $this->render("@UVDeskCoreFramework//resetPassword.html.twig");
    }
}
