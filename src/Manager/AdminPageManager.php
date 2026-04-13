<?php

namespace MartenaSoft\PageBundle\Manager;

use Knp\Component\Pager\Pagination\PaginationInterface;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\CommonLibrary\Dto\ActiveSiteDto;
use MartenaSoft\CommonLibrary\Manager\AdminManagerMoveInterface;
use MartenaSoft\CommonLibrary\Traits\AdminManagerTrait;
use MartenaSoft\PageBundle\Entity\Page;

class AdminPageManager extends AbstractAdminManager implements AdminManagerMoveInterface
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
        $this->moveItem(
            $this->getChildItems($activeSiteDto, $page),
            $page,
            $this->entityManager,
            $isUp
        );
    }

    public function getItems(
        ActiveSiteDto $activeSite,
        string $locale,
        int $page = 1,
        ?Page $parent = null,
        ?bool $isShowSafeDeleted = false
    ): PaginationInterface {
        $queryBuilder =
        $this
            ->pageRepository
            ->getQueryBuilderWithLanguage($locale)
            ->addOrderBy("p.position", "ASC")
            ->addOrderBy("p.createdAt", "DESC")
        ;

        if (!$isShowSafeDeleted) {
            $queryBuilder
                ->andWhere("p.isDeleted=:isDeleted")
                ->setParameter('isDeleted', false)
            ;
        }

        $queryBuilder
            ->andWhere("p.isOnMain = :isOnMain")
            ->andWhere("p.type = :type")
            ->setParameter('isOnMain', false)
            ->setParameter('type', DictionaryPage::PAGE_TYPE)
        ;

        return $this->pagination->paginate(
            $queryBuilder->getQuery(),
            $page,
            $activeSite->previewOnMainLimit,
            ['distinct' => false]
        );
    }

    public function save($activeSiteDto, $page): void
    {
        $page->setType(DictionaryPage::PAGE_TYPE);
        parent::save($activeSiteDto, $page);
    }
}