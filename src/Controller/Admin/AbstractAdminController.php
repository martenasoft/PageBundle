<?php

namespace MartenaSoft\PageBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryMessage;
use MartenaSoft\CommonLibrary\Event\MoveItemEvent;
use MartenaSoft\CommonLibrary\Traits\AdminControllerTrait;
use MartenaSoft\PageBundle\Entity\Page;
use MartenaSoft\PageBundle\Manager\AdminPageManager;
use MartenaSoft\PageBundle\Manager\PageManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class AbstractAdminController extends AbstractController
{
    use AdminControllerTrait;
    #[Route('/admin/{_locale}/page-up/{uuid}', name: 'app_page_up', priority: -11)]
    public function up(
        EventDispatcherInterface $eventDispatcher,
        Request $request,
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Page $page,
        AdminPageManager $adminPageManager
    ): Response {
        $eventDispatcher->dispatch(new MoveItemEvent(
            $page,
            $request->attributes->get('active_site'),
            true,
            $adminPageManager
        ));
        return $this->redirectToRoute($this->getIndexRoute());
    }

    #[Route('/admin/{_locale}/page-down/{uuid}', name: 'app_page_down', priority: -11)]
    public function down(
        EventDispatcherInterface $eventDispatcher,
        Request $request,
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Page $page,
        AdminPageManager $adminPageManager
    ): Response {
        $eventDispatcher->dispatch(new MoveItemEvent(
            $page,
            $request->attributes->get('active_site'),
            false,
            $adminPageManager
        ));
        return $this->redirectToRoute($this->getIndexRoute());
    }

    #[Route('/admin/{_locale}/delete-page-safe/{uuid}', name: 'app_delete_page_safe', methods: ['GET'])]
    #[Route('/admin/{_locale}/delete-page/{uuid}', name: 'app_delete_page', methods: ['GET'])]
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Page $page,
        EntityManagerInterface $entityManager,
        AdminPageManager $adminPageManager,
        LoggerInterface $logger
    ): Response {
        $parent = $page->getParent()?->getUuid() ?? null;

        $adminPageManager->delete($page->getUuid());

        try {
            $route = $request->attributes->get('_route');

            if ($route === 'app_delete_page_safe') {
                $page->setIsDeleted(true);
                $page->setDeletedAt(new \DateTime('now'));
                $pathToBasket = sprintf(DictionaryMessage::BASKET_MESSAGE_AFTER_DELETED, $this->generateUrl('app_basket_page'));
                $this->addFlash('warning', $pathToBasket);
            } else {
                $entityManager->remove($page);
            }
            $entityManager->flush();
            $this->addFlash('success', DictionaryMessage::PAGE_DELETED);
        } catch (\Throwable $exception) {
            $logger->error(get_class($this) . ' ' . DictionaryMessage::PAGE_DELETED_ERROR. ': {message}', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->addFlash('danger', DictionaryMessage::PAGE_DELETED_ERROR);
        }

        return $this->redirectToRoute('app_page_items_admin', ['parent' => $parent]);
    }


    #[Route('/admin/{_locale}/basket-page', name: 'app_basket_page', methods: ['GET'], priority: 60)]
    public function basket(
        Request $request,
        AdminPageManager $adminPageManager,
    ): Response {

        $activeSite = $request->attributes->get('active_site');
        $items = $adminPageManager->getItemsInBasketQueryBuilder(
            $activeSite,
            $request->query->getInt('page', 1),
        );

        return $this->render(sprintf('@Page/%s/admin-basket-items.html.twig', $activeSite->templatePath), [
            'pagination' => $items,
        ]);
    }

    #[Route('/admin/{_locale}/restore-from-basket-page/{uuid}', name: 'app_restore_from_basket_page', methods: ['GET'])]
    public function restoreFromBasket(
        Request $request,
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Page $page,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        $parent = $page->getParent()?->getUuid() ?? null;
        try {
            $route = $request->attributes->get('_route');

            $page->setIsDeleted(false);
            $page->setUpdatedAt(new \DateTime('now'));
            $page->setDeletedAt(null);
            $entityManager->flush();
            $this->addFlash('success', DictionaryMessage::PAGE_RESTORED_FROM_BASKET);
        } catch (\Throwable $exception) {
            $logger->error(get_class($this) . ' ' . DictionaryMessage::PAGE_RESTORED_FROM_BASKET_ERROR. ': {message}', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->addFlash('danger', DictionaryMessage::PAGE_RESTORED_FROM_BASKET_ERROR);
        }

        return $this->redirectToRoute('app_page_items_admin', ['parent' => $parent]);
    }

    protected function getValidateMessages(mixed $errors): string
    {
        $message = '';

        if (!$errors instanceof FormErrorIterator) {
            return $message;
        }

        /** @var FormError $error */

        foreach ($errors as $error) {
            $message .= '<li>' . $error->getCause()->getPropertyPath() .' '. $error->getMessage() . '</li>';
        }

        if (!empty($message)) {
            $message = '<ul>' . $message . '</ul>';
        }

        return $message;
    }

    protected abstract function getIndexRoute(): string;
}