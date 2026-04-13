<?php

namespace MartenaSoft\PageBundle\Controller\Admin;

use MartenaSoft\CommonLibrary\Dictionary\DictionaryMessage;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\PageBundle\Dto\SectionResponseDto;
use MartenaSoft\PageBundle\Entity\Page;
use MartenaSoft\PageBundle\Form\Admin\PageType;
use MartenaSoft\PageBundle\Form\Admin\SectionType;
use MartenaSoft\PageBundle\Manager\AdminSectionManager;
use MartenaSoft\PageBundle\Manager\PageManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminSectionController extends AbstractAdminController
{
    private const string LOG_PREFIX = 'AdminSectionController';

    protected function getIndexRoute(): string
    {
        return 'app_page_items_admin';
    }

    #[Route('/admin/{_locale}/section-items/{parent?}', name: 'app_page_section_items_admin', priority: -11)]
    public function index(
        Request $request,
        AdminSectionManager $adminSectionManager,
        #[MapEntity(mapping: ['parent' => 'uuid'])] ?Page $parent = null,
    ): Response {

        $activeSite = $request->attributes->get('active_site');
        $sectionResponseDto = $adminSectionManager->getItems(
            $activeSite,
            $request->getLocale(),
            $request->query->getInt('page', 1),
            $parent
        );

        return $this->render(sprintf('@Page/%s/admin/sections/index.html.twig', $activeSite->templatePath), [
            'pagination' => $sectionResponseDto->getItems(),
            'parent' => $parent,
            'page' => $sectionResponseDto->getPage()
        ]);
    }

    #[Route("/admin/{_locale}/create-section/{parentUuid?}", name: 'app_section_create', defaults: ['_locale' => null], methods: ['GET', 'POST'])]
    #[IsGranted('ROUTE_ACCESS')]
    public function create(
        Request $request,
        AdminSectionManager $adminSectionManager,
        LoggerInterface $logger,
        #[MapEntity(mapping: ['parentUuid' => 'uuid'])]
        ?Page $parent = null
    ): Response
    {
        $locale = $request->getLocale();
        $activeSite = $request->attributes->get('active_site');
        $page = new Page();
        $page
            ->setParent($parent)
            ->setLang($locale)
            ->setRouteName('app_page_slug')
            ->setIsOnMain(false)
            ->setType(DictionaryPage::SECTION_TYPE);

        $form = $this->createForm(SectionType::class, $page, [
            'languages' => array_flip($activeSite->languages),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $adminSectionManager->save($activeSite, $page);
                $this->addFlash('success', DictionaryMessage::PAGE_SAVED);
                $logger->notice(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVED, ['page' => $page]);

                if ($request->request->getBoolean('returnToNewFormAfterSave')) {
                    return $this->redirectToRoute('app_page_create');
                }

                return $this->redirectToRoute('app_section_update', ['uuid' => $page->getUuid(), '_locale' => $locale]);

            } catch (\Throwable $exception) {

                $logger->error(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVING_ERROR . ': {message}', [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);
                $this->addFlash('danger', DictionaryMessage::PAGE_SAVING_ERROR);
            }
        }

        $message = $this->getValidateMessages($form->getErrors());
        if (!empty($message)) {
            $this->addFlash('danger', $message);
        }

        return $this->render(sprintf('@Page/%s/admin/sections/form.html.twig', $activeSite->templatePath), [
            'form' => $form->createView(),
            'page' => $page,
            'parent' => $parent,
        ]);
    }

    #[Route(
        "/admin/{_locale}/edit-section/{uuid}",
        name: "app_section_update",
        defaults: ['_locale' => null],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROUTE_ACCESS', subject: 'page')]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Page $page,
        Request $request,
        AdminSectionManager $adminSectionManager,
        LoggerInterface $logger
    ): Response {
        $activeSite = $request->attributes->get('active_site');

        $form = $this->createForm(SectionType::class, $page, [
            'languages' => array_flip($activeSite->languages)
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $locale = $request->getLocale();
                $adminSectionManager->update($activeSite, $page);
                $logger->notice(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVED, [
                    'page' => $page,
                ]);

                $this->addFlash('success', DictionaryMessage::PAGE_SAVED);

                if ($request->request->getBoolean('returnToNewFormAfterSave')) {
                    return $this->redirectToRoute('app_page_create');
                }
                return $this->redirectToRoute('app_section_update', ['uuid' => $page->getUuid(), '_locale' => $locale]);
            } catch (\Throwable $exception) {
                $logger->error(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVING_ERROR. ': {message}', [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);
                $this->addFlash('danger', DictionaryMessage::PAGE_SAVING_ERROR);
            }
        }

        $message = $this->getValidateMessages($form->getErrors());
        if (!empty($message)) {
            $this->addFlash('danger', $message);
        }

        return $this->render(sprintf('@Page/%s/admin/sections/form.html.twig', $activeSite->templatePath), [
            'form' => $form->createView(),
            'page' => $page,
            'parent' => $page->getParent(),
        ]);
    }
}