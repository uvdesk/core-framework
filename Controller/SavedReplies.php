<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportTeam;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportGroup;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreFrameworkBundleEntities;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SavedReplies as CoreBundleSavedReplies;

class SavedReplies extends AbstractController
{
    const LIMIT = 10;
    const ROLE_REQUIRED = 'saved_replies';

    private $userService;
    private $translator;
    private $entityManager;

    public function __construct(UserService $userService, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->userService = $userService;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    public function loadSavedReplies(Request $request)
    {
        $savedReplyReferenceIds = $this->userService->getUserSavedReplyReferenceIds();

        return $this->render('@UVDeskCoreFramework//savedRepliesList.html.twig', [
            'savedReplyReferenceIds' => array_unique($savedReplyReferenceIds),
        ]);
    }

    public function updateSavedReplies(Request $request, ContainerInterface $container)
    {
        $errors = [];
        $templateId = $request->attributes->get('template');
        $repository = $this->entityManager->getRepository(CoreFrameworkBundleEntities\SavedReplies::class);
        $currentUser = $this->getUser()->getAgentInstance();
        $currentUserRole = $currentUser->getSupportRole()->getCode();

        $template = $repository->getSavedReply($templateId, $container);

        if (! empty($template)) {
            if (
                $template->getUser()->getId() != $currentUser->getId()
                && $currentUserRole == 'ROLE_AGENT'
            ) {
                $this->addFlash('warning',  $this->translator->trans('Error! You are not allowed to edit this saved reply.'));

                return $this->redirectToRoute('helpdesk_member_saved_replies');
            }
        }

        if (empty($templateId)) {
            $template = new CoreFrameworkBundleEntities\SavedReplies();
        } else {
            // @TODO: Refactor: We shouldn't be passing around the container.
            $template = $repository->getSavedReply($templateId, $container);

            if (empty($template)) {
                $this->noResultFound();
            }
        }

        if ($request->getMethod() == 'POST') {
            if (empty($request->request->get('message'))) {
                $this->addFlash('warning',  $this->translator->trans('Error! Saved reply body can not be blank'));

                return $this->render('@UVDeskCoreFramework//savedReplyForm.html.twig', [
                    'template' => $template,
                    'errors'   => json_encode($errors)
                ]);
            }

            $em = $this->entityManager;
            $template->setName($request->request->get('name'));

            // Groups
            $previousGroupIds = [];
            $groups = explode(',', $request->request->get('tempGroups'));

            if ($template->getSupportGroups()) {
                foreach ($template->getSupportGroups()->toArray() as $key => $group) {
                    $previousGroupIds[] = $group->getId();
                    if (
                        ! in_array($group->getId(), $groups)
                        && $this->getUser()->getAgentInstance()->getSupportRole()->getCode() != "ROLE_AGENT"
                    ) {
                        $template->removeSupportGroups($group);
                        $em->persist($template);
                    }
                }
            }

            foreach ($groups as $key => $groupId) {
                if ($groupId) {
                    $group = $em->getRepository(SupportGroup::class)->findOneBy(['id' => $groupId]);

                    if (
                        $group
                        && (empty($previousGroupIds)
                            || !in_array($groupId, $previousGroupIds))
                    ) {
                        $template->addSupportGroup($group);
                        $em->persist($template);
                    }
                }
            }

            // Teams
            $previousTeamIds = [];
            $teams = explode(',', $request->request->get('tempTeams'));

            if ($template->getSupportTeams()) {
                foreach ($template->getSupportTeams()->toArray() as $key => $team) {
                    $previousTeamIds[] = $team->getId();

                    if (
                        ! in_array($team->getId(), $teams)
                        && $this->getUser()->getAgentInstance()->getSupportRole()->getCode() != "ROLE_AGENT"
                    ) {
                        $template->removeSupportTeam($team);
                        $em->persist($template);
                    }
                }
            }

            foreach ($teams as $key => $teamId) {
                if ($teamId) {
                    $team = $em->getRepository(SupportTeam::class)->findOneBy(['id' => $teamId]);

                    if (
                        $team
                        && (empty($previousTeamIds)
                            || !in_array($teamId, $previousTeamIds))
                    ) {
                        $template->addSupportTeam($team);
                        $em->persist($template);
                    }
                }
            }

            $template->setMessage($request->request->get('message'));

            if (empty($template->getUser())) {
                $template->setUser($this->getUser()->getAgentInstance());
            }

            $em->persist($template);
            $em->flush();

            $this->addFlash('success', $request->attributes->get('template') ? $this->translator->trans('Success! Reply has been updated successfully.') : $this->translator->trans('Success! Reply has been added successfully.'));

            return $this->redirectToRoute('helpdesk_member_saved_replies');
        }

        return $this->render('@UVDeskCoreFramework//savedReplyForm.html.twig', array(
            'template' => $template,
            'errors'   => json_encode($errors)
        ));
    }

    public function savedRepliesXHR(Request $request, ContainerInterface $container)
    {
        if (! $request->isXmlHttpRequest()) {
            throw new \Exception(404);
        }

        $entityManager = $this->entityManager;
        $savedReplyRepository = $entityManager->getRepository(CoreBundleSavedReplies::class);

        if ($request->getMethod() == 'GET') {
            $responseContent = $savedReplyRepository->getSavedReplies($request->query, $container);
        } else {
            $savedReplyId = $request->attributes->get('template');

            if (null == $savedReplyId || $request->getMethod() != 'DELETE') {
                throw new \Exception(404);
            } else {
                $savedReply = $savedReplyRepository->findOneBy(['id' => $savedReplyId, 'user' => $this->getUser()->getAgentInstance()]);

                if (empty($savedReply)) {
                    $responseContent = [
                        'alertClass'   => 'danger',
                        'alertMessage' => $this->translator->trans('Errpr! You are not allowed to delete this saved reply.')
                    ];                    

                    return new Response(json_encode($responseContent), 200, ['Content-Type' => 'application/json']);
                }
            }

            $entityManager->remove($savedReply);
            $entityManager->flush();

            $responseContent = [
                'alertClass'   => 'success',
                'alertMessage' => $this->translator->trans('Success! Saved Reply has been deleted successfully.')
            ];
        }

        return new Response(json_encode($responseContent), 200, ['Content-Type' => 'application/json']);
    }

    private function getId($item)
    {
        return $item->getId();
    }
}
