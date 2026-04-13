<?php

namespace MartenaSoft\PageBundle\Controller;

use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\PageBundle\Manager\PageManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route(
        '/{_locale}',
        name: 'app_page_main',
        requirements: ['_locale' => '[a-z]{2}'],
        defaults: ['_locale' => null],
        methods: ['GET']
    )]
    public function main(Request $request, PageManager $pageManager): Response
    {
        $activeSite = $request->attributes->get('active_site');
        $pageMainResponseDto = $pageManager->getMainPage($activeSite, $request->getLocale());

        return $this->render(sprintf('@Page/%s/main.html.twig', $activeSite->templatePath), [
            'itemsOnMain' => $pageMainResponseDto->getItemsOnMain(),
            'page' => $pageMainResponseDto->getPages(),
            'imagesConfig' => $pageMainResponseDto->getImagesConfig()
        ]);
    }

    #[Route(
        '/{_locale}/{slug}',
        name: 'app_page_slug',
        requirements: ['_locale' => '[a-z]{2}', 'slug' => '[a-z0-9-_/]+'],
        methods: ['GET'],
        priority: -101
    )]
    public function show(
        Request $request,
        PageManager $pageManager,
        ?string $slug = null,
    ): Response {
        $activeSite = $request->attributes->get('active_site');
        $pageResponseDto = $pageManager->getPage(
            $activeSite,
            $request->getLocale(),
            $slug,
            $request->query->getInt('page', 1)
        );

        if (!$pageResponseDto) {
            throw new NotFoundHttpException();
        }

        return $this->render(sprintf('@Page/%s/page.html.twig', $activeSite->templatePath), [
            'items' => $pageResponseDto->getItems(),
            'page' => $pageResponseDto->getPage(),
        ]);
    }
}
