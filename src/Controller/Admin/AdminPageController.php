<?php

namespace MartenaSoft\PageBundle\Controller\Admin;

use MartenaSoft\CommonLibrary\Dictionary\DictionaryMessage;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\CommonLibrary\Helper\StringHelper;
use MartenaSoft\PageBundle\Entity\Page;
use MartenaSoft\PageBundle\Form\Admin\PageType;
use MartenaSoft\PageBundle\Manager\AdminPageManager;
use MartenaSoft\PageBundle\Manager\AdminSectionManager;
use MartenaSoft\PageBundle\Manager\PageManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminPageController extends AbstractAdminController
{
    private const string LOG_PREFIX = 'AdminController';

    protected function getIndexRoute(): string
    {
        return 'app_page_items_admin';
    }

    #[Route('/admin/{_locale}/page-items/{parent?}', name: 'app_page_items_admin', priority: -11)]
    public function index(
        Request $request,
        AdminPageManager $adminPageManager,
        AdminSectionManager $adminSectionManager,
        #[MapEntity(mapping: ['parent' => 'uuid'])] ?Page $parent = null,
    ): Response
    {
        $activeSite = $request->attributes->get('active_site');
        $items = $adminPageManager->getItems(
            $activeSite,
            $request->getLocale(),
            $request->query->getInt('page', 1),
            $parent
        );

        $sectionMain = $adminSectionManager->getSection($activeSite, $request->getLocale(), null);
        return $this->render(sprintf('@Page/%s/admin/pages/index.html.twig', $activeSite->templatePath), [
            'pagination' => $items,
            'parent' => $parent,
            'sectionMain' => $sectionMain,
        ]);
    }

    #[Route(
        "/admin/{_locale}/create-page/{parentUuid}",
        name: 'app_page_create',
        defaults: ['_locale' => null], methods: ['GET', 'POST'])
    ]
    #[IsGranted('ROUTE_ACCESS')]
    public function create(
        Request $request,
        AdminPageManager $adminPageManager,
        LoggerInterface $logger,
        #[MapEntity(mapping: ['parentUuid' => 'uuid'])]
        Page $parent
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
            ->setType(DictionaryPage::PAGE_TYPE);

        $form = $this->createForm(PageType::class, $page, [
            'languages' => array_flip($activeSite->languages),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $adminPageManager->save($activeSite, $page);
                $this->addFlash('success', DictionaryMessage::PAGE_SAVED);
                $logger->notice(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVED, [
                    'page' => $page,
                ]);

                if ($request->request->getBoolean('returnToNewFormAfterSave')) {
                    return $this->redirectToRoute('app_page_create');
                }

                return $this->redirectToRoute('app_page_update', ['uuid' => $page->getUuid(), '_locale' => $locale]);

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

        return $this->render(sprintf('@Page/%s/admin/pages/form.html.twig', $activeSite->templatePath), [
            'form' => $form->createView(),
            'page' => $page,
            'parent' => $parent,
        ]);
    }

    #[Route(
        "/admin/{_locale}/edit-page/{uuid}",
        name: "app_page_update",
        defaults: ['_locale' => null],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROUTE_ACCESS', subject: 'page')]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Page $page,
        Request $request,
        AdminPageManager $adminPageManager,
        LoggerInterface $logger
    ): Response
    {

        $locale = $request->getLocale();
        $activeSite = $request->attributes->get('active_site');
        $form = $this->createForm(PageType::class, $page, [
            'languages' => array_flip($activeSite->languages),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $adminPageManager
                    ->setFiles($form->get('file')->getData())
                    ->update($activeSite, $page);
                $logger->notice(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVED, [
                    'page' => $page,
                ]);

                $this->addFlash('success', DictionaryMessage::PAGE_SAVED);

                if ($request->request->getBoolean('returnToNewFormAfterSave')) {
                    return $this->redirectToRoute('app_page_create');
                }
                return $this->redirectToRoute('app_page_update', ['uuid' => $page->getUuid(), '_locale' => $locale]);
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

        return $this->render(sprintf('@Page/%s/admin/pages/form.html.twig', $activeSite->templatePath), [
            'form' => $form->createView(),
            'page' => $page,
            'parent' => $page->getParent(),
        ]);
    }
}