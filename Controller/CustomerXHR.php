<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;

class CustomerXHR extends AbstractController
{
    private $userService;
    private $eventDispatcher;
    private $translator;
    private $entityManager;

    public function __construct(UserService $userService, EventDispatcherInterface $eventDispatcher, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    public function listCustomersXHR(Request $request, ContainerInterface $container)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_CUSTOMER')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $json = array();

        if ($request->isXmlHttpRequest()) {
            $repository = $this->entityManager->getRepository(User::class);
            $json =  $repository->getAllCustomer($request->query, $container);
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function removeCustomerXHR(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_CUSTOMER')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $json = array();
        if ($request->getMethod() == "DELETE") {
            $em = $this->entityManager;
            $id = $request->attributes->get('customerId');
            $user = $em->getRepository(User::class)->findOneBy(['id' => $id]);

            if ($user) {
                $this->userService->removeCustomer($user);
                // Trigger customer created event
                $event = new CoreWorkflowEvents\Customer\Delete();
                $event
                    ->setUser($user);

                $this->eventDispatcher->dispatch($event, 'uvdesk.automation.workflow.execute');

                $json['alertClass']   = 'success';
                $json['alertMessage'] = $this->translator->trans('Success ! Customer removed successfully.');
            } else {
                $json['alertClass']   = 'danger';
                $json['alertMessage'] = $this->translator->trans('Error ! Invalid customer id.');
                $json['statusCode']   = Response::HTTP_NOT_FOUND;
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
