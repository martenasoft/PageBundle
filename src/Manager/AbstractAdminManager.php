<?php

namespace MartenaSoft\PageBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryMessage;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\CommonLibrary\Dto\ActiveSiteDto;
use MartenaSoft\CommonLibrary\Event\ImageUploadEvent;
use MartenaSoft\CommonLibrary\Event\MoveItemEvent;
use MartenaSoft\PageBundle\Entity\Page;
use MartenaSoft\PageBundle\Event\SavedPageEvent;
use MartenaSoft\PageBundle\Repository\PageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class AbstractAdminManager
{
    private array $files = [];
    public function __construct(
        protected PageRepository $pageRepository,
        protected EntityManagerInterface $entityManager,
        protected ParameterBagInterface $parameterBag,
        protected EventDispatcherInterface $eventDispatcher,
        protected LoggerInterface $logger,
        protected SluggerInterface $slugger,
        protected PaginatorInterface $pagination,
    ) {

    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(array $files): self
    {
        $this->files = $files;
        return $this;
    }

    public function save(
        ActiveSiteDto $activeSiteDto,
        Page $page
    ): void {
        $page->setSiteId($activeSiteDto->id);
        $slug_ = $this->slugger->slug($page->getName())->lower()->toString();
        $page->setSlug($slug_);
        $parent = $page->getParent();

        if (empty($parent?->getParentType()) && !empty($parent)) {
            $items = $this->getChildItems($activeSiteDto, $page, 1);
            if (count($items) === 0) {
                $parent->setParentType($page->getType());
            }
        }

        if (empty($page->getPosition())) {
            $queryBuilder = $this->pageRepository->getItemsQueryBuilder($page->getLang(), $parent, isOrder: false);

            $queryBuilder
                ->select('count(p.id) as count')
                ->andWhere("p.siteId=:activeSiteId")
                ->setParameter('activeSiteId', $activeSiteDto->id)
            ;
            $count = $queryBuilder->getQuery()->getOneOrNullResult()['count'] ?? 1;
            $page->setPosition($count + 1);
        }

        try {

            $this->entityManager->beginTransaction();
            $this->pageRepository->save($page);
            $this->entityManager->commit();

            if (!$page->isOnMain()) {
                $this->eventDispatcher->dispatch(new SavedPageEvent($page, $activeSiteDto));
            }

            $this->eventDispatcher->dispatch(new MoveItemEvent(
                $page,
                $activeSiteDto,
                true,
                $this
            ));

            $files = $this->getFiles();

            if (!empty($files)) {

                $this->eventDispatcher->dispatch(new ImageUploadEvent(
                    $files,
                    $activeSiteDto,
                    $page->getUuid()->toString(),
                ));
            }

        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }

        $this->logger->notice(
            AdminMainPageManager::class . ' ' .
            DictionaryMessage::PAGE_SAVED, [
                'page' => $page,
            ]
        );
    }

    public function getItemsInBasketQueryBuilder(ActiveSiteDto $activeSiteDto, int $page = 1): PaginationInterface
    {
        $queryBuilder = $this->pageRepository->getItemsInBasketQueryBuilder($activeSiteDto);
        return $this->pagination->paginate(
            $queryBuilder->getQuery(),
            $page,
            $activeSiteDto->previewOnMainLimit,
            ['distinct' => false]
        );
    }

    public function update(ActiveSiteDto $activeSiteDto, Page $page): void
    {
        if ($page->getSlug() !== '/') {
            $slug_ = $this->slugger->slug($page->getName())->lower()->toString();
            $page->setSlug($slug_);
        }

        $this->save($activeSiteDto, $page);
        $this->eventDispatcher->dispatch(new SavedPageEvent($page, $activeSiteDto));
    }

    public function delete(string $uuid, bool $isSafeDeleted = false): void
    {
        $uuid = $this->pageRepository->collectTreeUuids($uuid);
        $this->pageRepository->deleteSafeByUuids($uuid);
    }

    protected function getChildItems(ActiveSiteDto $activeSiteDto, Page $page, ?int $limit = null): array
    {
        $queryBuilder = $this->pageRepository->getItemsQueryBuilder($page->getLang(), $page->getParent());

        $queryBuilder
            ->andWhere("p.siteId=:activeSiteId")
            ->setParameter('activeSiteId', $activeSiteDto->id)
        ;

        if (!empty($limit)) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}