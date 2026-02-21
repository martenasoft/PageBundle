<?php

namespace MartenaSoft\PageBundle\Controller;

use MartenaSoft\CommonLibrary\Dictionary\DictionaryMessage;
use MartenaSoft\PageBundle\Entity\Page;
use MartenaSoft\PageBundle\Form\PageType;
use MartenaSoft\PageBundle\Manager\PageManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminController extends AbstractController
{
    private const string LOG_PREFIX = 'AdminController';

    #[Route('/{_locale}/admin/index-pages/{parent?}', name: 'app_page_items_admin', priority: -11)]
    public function index(
        Request $request,
        PageManager $pageManager,
        #[MapEntity(mapping: ['parent' => 'uuid'])] ?Page $parent = null,
    ): Response {

        $activeSite = $request->attributes->get('active_site');
        $items = $pageManager->getItems(
            $activeSite,
            $request->getLocale(),
            $request->query->getInt('page', 1),
            $parent
        );

        if (empty($parent) && $items->getTotalItemCount() === 0) {
            return $this->redirectToRoute('app_page_main_create');
        }

        return $this->render(sprintf('@Page/%s/admin-items.html.twig', $activeSite->templatePath), [
            'pagination' => $items,
            'parent' => $parent,
        ]);
    }

    #[Route("/{_locale}/admin/create-page/{parentUuid?}", name: 'app_page_create', defaults: ['_locale' => null], methods: ['GET', 'POST'])]
    #[Route("/{_locale}/admin/create-main-page", name: 'app_page_main_create', defaults: ['_locale' => null], methods: ['GET', 'POST'])]
    #[IsGranted('ROUTE_ACCESS')]
    public function createPage(
        Request $request,
        PageManager $pageManager,
        LoggerInterface $logger,
        #[MapEntity(mapping: ['parentUuid' => 'uuid'])]
        ?Page $parent = null
    ): Response {
        $activeSite = $request->attributes->get('active_site');
        if ($parent === null && ($mainPage = $pageManager->gasMainPage($activeSite, $request->getLocale())) !== null) {
            return $this->redirectToRoute('app_page_update', [
                'uuid' => $mainPage->getUuid(),
                '_locale' => $request->getLocale()
            ]);
        }

        $page = new Page();
        $route = $request->attributes->get('_route');

        $form = $this->createForm(PageType::class, $page, [
            'languages' => array_flip($activeSite->languages),
            'isMainPage' => $parent === null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $locale = $request->getLocale();
            try {
                $pageManager->create($this->getUser(), $activeSite, $locale, $route, $page, $parent);
                $this->addFlash('success', DictionaryMessage::PAGE_SAVED);
                $logger->notice(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVED, [
                    'page' => $page,
                ]);

                if ($request->request->getBoolean('returnToNewFormAfterSave')) {
                    return $this->redirectToRoute('app_page_create');
                }

                return $this->redirectToRoute('app_page_update', ['uuid' => $page->getUuid(), '_locale' => $locale]);
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


        return $this->render(sprintf('@Page/%s/form.html.twig', $activeSite->templatePath), [
            'form' => $form->createView(),
            'page' => $page,
            'parent' => $parent,
        ]);
    }

    #[Route(
        "/{_locale}/admin/edit-page/{uuid}",
        name: "app_page_update",
        defaults: ['_locale' => null],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROUTE_ACCESS', subject: 'page')]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Page $page,
        Request $request,
        PageManager $pageManager,
        LoggerInterface $logger
    ): Response {
        $activeSite = $request->attributes->get('active_site');

        $form = $this->createForm(PageType::class, $page, [
            'languages' => array_flip($activeSite->languages),
            'isMainPage' => $page->getParent() === null && $page->getSlug() === '/',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $locale = $request->getLocale();
                $pageManager->update($page);
                $logger->notice(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVED, [
                    'page' => $page,
                ]);

                $this->addFlash('success', DictionaryMessage::PAGE_SAVED);

                if ($request->request->getBoolean('returnToNewFormAfterSave')) {
                    return $this->redirectToRoute('app_page_create');
                }
                return $this->redirectToRoute('app_page_update', ['uuid' => $page->getUuid(), '_locale' => $locale]);
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
        return $this->render(sprintf('@Page/%s/form.html.twig', $activeSite->templatePath), [
            'form' => $form->createView(),
            'page' => $page,
            'parent' => $page->getParent(),
        ]);
    }

    #[Route('/{_locale}/admin/delete-page-safe/{id}', name: 'app_delete_page_safe', methods: ['GET'])]
    #[Route('/{_locale}/admin/delete-page/{id}', name: 'app_delete_page_sage', methods: ['GET'])]
    public function delete(Page $page): Response
    {

    }
}
