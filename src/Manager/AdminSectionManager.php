<?php

namespace MartenaSoft\PageBundle\Manager;

use Knp\Component\Pager\Pagination\PaginationInterface;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\CommonLibrary\Dto\ActiveSiteDto;
use MartenaSoft\CommonLibrary\Manager\AdminManagerMoveInterface;
use MartenaSoft\CommonLibrary\Traits\AdminManagerTrait;
use MartenaSoft\PageBundle\Dto\SectionResponseDto;
use MartenaSoft\PageBundle\Entity\Page;

class AdminSectionManager extends AbstractAdminManager implements AdminManagerMoveInterface
{
    use AdminManagerTrait;

    public function up(ActiveSiteDto $activeSiteDto, Page $page): void
    {
        $this->move($activeSiteDto, $page, true);
    }

    public function down(ActiveSiteDto $activeSiteDto, Page $page): void
    {
        $this->move($activeSiteDto, $page,  false);
    }

    private function move(ActiveSiteDto $activeSiteDto, Page $page, bool $isUp): void
    {
        $queryBuilder = $this->pageRepository->getItemsQueryBuilder($page->getLang(), $page->getParent());

        $queryBuilder
            ->andWhere("p.siteId=:activeSiteId")
            ->setParameter('activeSiteId', $activeSiteDto->id)
        ;

        $items = $queryBuilder->getQuery()->getResult();
        $this->moveItem($items, $page, $this->entityManager, $isUp);
    }

    public function getSection(ActiveSiteDto $activeSite, string $locale, ?string $uuid): ?Page
    {
        $queryBuilder = $this->pageRepository->getItemsQueryBuilder($locale);
        $queryBuilder

            ->andWhere("p.type=:type")
            ->andWhere("p.siteId=:activeSiteId")
            ->setParameter('type', DictionaryPage::SECTION_TYPE)
            ->setParameter('activeSiteId', $activeSite->id)
        ;

        if ($uuid === null) {
            $queryBuilder->andWhere("p.parent IS NULL");
        } else {
            $queryBuilder->andWhere("p.uuid=:uuid")
            ->setParameter('uuid', $uuid);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getItems(
        ActiveSiteDto $activeSiteDto,
        string $locale,
        int $page = 1,
        ?Page $parent = null,
        ?bool $isShowSafeDeleted = false
    ): SectionResponseDto {
        $queryBuilder = $this->pageRepository->getItemsQueryBuilder($locale, $parent);
        if (!$isShowSafeDeleted) {
            $queryBuilder
                ->andWhere("p.isDeleted=:isDeleted")
                ->setParameter('isDeleted', false)
            ;
        }

        $queryBuilder
            ->andWhere("p.isOnMain = :isOnMain")
            ->setParameter('isOnMain', false)
        ;

        $items = $this->pagination->paginate(
            $queryBuilder->getQuery(),
            $page,
            $activeSiteDto->previewOnMainLimit,
            ['distinct' => false]
        );

        $page = (!empty($parent) ? $this->getSection($activeSiteDto, $locale, $parent->getUuid()->toString()) : null);

        return new SectionResponseDto(
            page: $page,
            items: $items,
            activeSite: $activeSiteDto,
        );

    }

    public function save($activeSiteDto, $page): void
    {
        $page->setType(DictionaryPage::SECTION_TYPE);
        parent::save($activeSiteDto, $page);
    }
}
