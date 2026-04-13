<?php

namespace MartenaSoft\PageBundle\Manager;

use Knp\Component\Pager\Pagination\PaginationInterface;
use MartenaSoft\CommonLibrary\Dto\ActiveSiteDto;
use MartenaSoft\CommonLibrary\Manager\AdminManagerMoveInterface;
use MartenaSoft\PageBundle\Entity\Page;

class AdminMainPageManager extends AbstractAdminManager implements AdminManagerMoveInterface
{
    public function getItems(
        ActiveSiteDto $activeSite,
        string $locale,
        int $page = 1,
        ?bool $isShowSafeDeleted = false
    ): PaginationInterface
    {
        $queryBuilder = $this->pageRepository->getItemsQueryBuilder($locale);

        $queryBuilder
            ->andWhere("p.isOnMain = :isOnMain")
            ->setParameter('isOnMain', true)
        ;

        if (!$isShowSafeDeleted) {
            $queryBuilder
                ->andWhere("p.isDeleted=:isDeleted")
                ->setParameter('isDeleted', false)
            ;
        }

        return $this->pagination->paginate(
            $queryBuilder->getQuery(),
            $page,
            $activeSite->previewOnMainLimit,
            ['distinct' => false]
        );
    }

    public function up(ActiveSiteDto $activeSiteDto, Page $page): void
    {

    }

    public function down(ActiveSiteDto $activeSiteDto, Page $page): void
    {
    }
}