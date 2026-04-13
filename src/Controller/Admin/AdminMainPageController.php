<?php

namespace MartenaSoft\PageBundle\Controller\Admin;

use MartenaSoft\CommonLibrary\Dictionary\DictionaryMessage;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\PageBundle\Entity\Page;
use MartenaSoft\PageBundle\Form\Admin\MainPageType;
use MartenaSoft\PageBundle\Manager\AdminMainPageManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminMainPageController extends AbstractAdminController
{
    private const string LOG_PREFIX = 'AdminMainPage';

    public function __construct(
        private readonly AdminMainPageManager $adminMainPageManager,
        private readonly LoggerInterface $logger
    )
    {
    }

    protected function getIndexRoute(): string
    {
        return 'app_main_page_items_admin';
    }


    #[Route('/admin/{_locale}/main-page-items', name: 'app_main_page_items_admin', priority: -11)]
    public function index(Request $request): Response
    {
        $activeSite = $request->attributes->get('active_site');
        $items = $this->adminMainPageManager->getItems(
            $activeSite,
            $request->getLocale(),
            $request->query->getInt('page', 1)
        );

        return $this->render(sprintf('@Page/%s/admin/main-page/index.html.twig', $activeSite->templatePath), [
            'pagination' => $items,
        ]);
    }

    #[Route(
        "/admin/{_locale}/create-main-page",
        name: 'app_main_page_create',
        defaults: ['_locale' => null],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROUTE_ACCESS')]
    public function create(Request $request): Response
    {
        $locale = $request->getLocale();
        $activeSite = $request->attributes->get('active_site');
        $page = new Page();
        $page
            ->setLang($locale)
            ->setRouteName('app_page_main')
            ->setIsOnMain(true)
            ->setType(DictionaryPage::PAGE_TYPE);

        $form = $this->createForm(MainPageType::class, $page, [
            'languages' => array_flip($activeSite->languages),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            try {
                if ($form->isValid()) {
                    $this->adminMainPageManager->save($activeSite, $page);
                    $this->addFlash('success', DictionaryMessage::PAGE_SAVED);
                    $this->logger->notice(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVED, [
                        'page' => $page,
                    ]);

                    if ($request->request->getBoolean('returnToNewFormAfterSave')) {
                        return $this->redirectToRoute('app_main_page_create');
                    }

                    return $this->redirectToRoute('app_main_page_update', ['uuid' => $page->getUuid(), '_locale' => $locale]);
                } else {
                    foreach ($form->getErrors() as $error) {
                        $this->addFlash('danger', $error->getMessage());
                    }
                }
            } catch (\Throwable $exception) {
                $this->logger->error(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVING_ERROR . ': {message}', [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);
                $this->addFlash('danger', DictionaryMessage::PAGE_SAVING_ERROR);
            }
        }

        return $this->render(sprintf('@Page/%s/admin/main-page/form.html.twig', $activeSite->templatePath), [
            'form' => $form->createView(),
            'page' => $page,
        ]);
    }

    #[Route(
        "/admin/{_locale}/edit-main-page/{uuid}",
        name: "app_main_page_update",
        defaults: ['_locale' => null],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROUTE_ACCESS', subject: 'page')]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Page $page,
        Request $request
    ): Response
    {
        $activeSite = $request->attributes->get('active_site');

        $form = $this->createForm(MainPageType::class, $page, [
            'languages' => array_flip($activeSite->languages),
        ]);
        $form->handleRequest($request);

        $route = ($request->request->getBoolean('returnToNewFormAfterSave')
            ? 'app_main_page_create'
            : 'app_main_page_update'
        );
        $params = ['uuid' => $page->getUuid(), '_locale' => $request->getLocale()];

        $this->setReturnType($request, [
            'route' => $route,
            'params' => $params,
            'title' => 'Return to main page',
        ]);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->adminMainPageManager->save($activeSite, $request->getLocale(), $page);
                $this->logger->notice(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVED, ['page' => $page]);
                $this->addFlash('success', DictionaryMessage::PAGE_SAVED);
                $this->removeReturnType($request);

                return $this->redirectToRoute($route, $params);
            } catch (\Throwable $exception) {

                $this->logger->error(self::LOG_PREFIX . DictionaryMessage::PAGE_SAVING_ERROR . ': {message}', [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);
                $this->addFlash('danger', DictionaryMessage::PAGE_SAVING_ERROR);
            }
        }
        return $this->render(sprintf('@Page/%s/admin/main-page/form.html.twig', $activeSite->templatePath), [
            'form' => $form->createView(),
            'page' => $page,
            'parent' => $page->getParent(),
        ]);
    }
}