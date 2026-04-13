<?php

namespace MartenaSoft\PageBundle\Manager;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryMessage;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\CommonLibrary\Entity\Interfaces\AuthorInterface;
use MartenaSoft\CommonLibrary\Helper\StringHelper;
use MartenaSoft\PageBundle\Dto\PageMainResponseDto;
use MartenaSoft\PageBundle\Dto\PageResponseDto;
use MartenaSoft\PageBundle\Entity\Page;
use MartenaSoft\PageBundle\Event\SavedPageEvent;
use MartenaSoft\PageBundle\Repository\PageRepository;
use MartenaSoft\CommonLibrary\Dto\ActiveSiteDto;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class PageManager
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private PageRepository $pageRepository,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private SluggerInterface $slugger,
        private PaginatorInterface $pagination,
    )
    {
    }

    public function getMainPage(ActiveSiteDto $activeSiteDto, string $locale): PageMainResponseDto
    {
        return new PageMainResponseDto(
            pages: $this
                ->pageRepository
                ->getItemsQueryBuilder($locale, isOnMain: true)
                ->getQuery()
                ->getResult(),
            itemsOnMain: $this->pageRepository->getItemsOnMainPageQueryBuilder(
                $locale,
                $activeSiteDto->previewOnMainLimit
            )->getQuery()->getResult(),
            imagesConfig: $this->parameterBag->get('image'),
        );
    }
//
//    public function getItems(
//        ActiveSiteDto $activeSite,
//        string $locale,
//        int $page = 1,
//        ?Page $parent = null,
//        ?bool $isShowSafeDeleted = false
//    ): PaginationInterface
//    {
//        $queryBuilder = $this->pageRepository->getItemsQueryBuilder($locale, $parent);
//        if (!$isShowSafeDeleted) {
//            $queryBuilder
//                ->andWhere("p.isDeleted=:isDeleted")
//                ->setParameter('isDeleted', false);
//            ;
//        }
//
//        return $this->pagination->paginate(
//            $queryBuilder->getQuery(),
//            $page,
//            $activeSite->previewOnMainLimit,
//            ['distinct' => false]
//        );
//    }

//    public function getItemsInBasketQueryBuilder(ActiveSiteDto $activeSiteDto, int $page = 1): PaginationInterface
//    {
//        $queryBuilder = $this->pageRepository->getItemsInBasketQueryBuilder($activeSiteDto);
//        return $this->pagination->paginate(
//            $queryBuilder->getQuery(),
//            $page,
//            $activeSiteDto->previewOnMainLimit,
//            ['distinct' => false]
//        );
//    }

    public function getPage(ActiveSiteDto $activeSite, string $locale, ?string $slug = null, int $page = 1): ?PageResponseDto
    {
        $slug = StringHelper::getSlugFromPath($slug);
        $pageItem = $this->pageRepository->getOneBySlugQueryBuilder($activeSite, $slug, $locale)->getQuery()->getOneOrNullResult();

        if (!$pageItem) {
            return null;
        }
        $query = (!$page? null : $this->pageRepository->getItemsQueryBuilder($locale, $pageItem)->getQuery());
        $items = (!$query? null : $this->pagination->paginate($query, $page, $activeSite->previewOnMainLimit, ['distinct' => false]));
        return new PageResponseDto($pageItem, $items, $activeSite);
    }

//    public function create(
//        AuthorInterface $author,
//        ActiveSiteDto $activeSiteDto,
//        string $locale,
//        Page $page,
//        ?Page $parent
//    ): void
//    {
//        $page->setSiteId($activeSiteDto->id);
//        $slug_ = $this->slugger->slug($page->getName())->lower()->toString();
//
//        $routeName = $page->isOnMain() ? 'app_page_main' :  'app_page_slug';
//
//        if ($page->getType() === DictionaryPage::SECTION_TYPE) {
//            $routeName = 'app_section_slug';
//        }
//
//        if ($parent !== null) {
//            $page->setParent($parent);
//            $page->setIsOnMain(false);
//        } elseif ($page->isOnMain()) {
//            $slug_ = '/';
//        }
//
//        $page->setSlug($slug_);
//        $page->setLang($locale);
//        $page->setRouteName($routeName);
//
//        $this->pageRepository->save($page);
//        $this->eventDispatcher->dispatch(new SavedPageEvent($page, $activeSiteDto));
//
//        $this->logger->notice(
//            DictionaryMessage::PAGE_SAVED, [
//                'page' => $page,
//            ]
//        );
//    }

//    public function update(Page $page, ActiveSiteDto $activeSiteDto): void
//    {
//        if ($page->getSlug() !== '/') {
//            $slug_ = $this->slugger->slug($page->getName())->lower()->toString();
//            $page->setSlug($slug_);
//        }
//
//        $this->pageRepository->save($page);
//        $this->eventDispatcher->dispatch(new SavedPageEvent($page, $activeSiteDto));
//    }
//
//    public function delete(string $uuid, bool $isSafeDeleted = false): void
//    {
//        $uuid = $this->pageRepository->collectTreeUuids($uuid);
//        $this->pageRepository->deleteSafeByUuids($uuid);
//    }
}
