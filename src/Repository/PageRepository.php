<?php

namespace MartenaSoft\PageBundle\Repository;

use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\PageBundle\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use MartenaSoft\SiteBundle\Dto\ActiveSiteDto;


class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function getOneBySlugQueryBuilder(
        ActiveSiteDto $activeSiteDto,
        string $slug,
        string $locale,
        string $alias = 'p'
    ): QueryBuilder {

        $queryBuilder = $this->getQueryBuilderWithLanguage($locale, $alias);
        $queryBuilder->andWhere("{$alias}.slug=:slug");

        $queryBuilder->andWhere("{$alias}.siteId=:activeSiteId");
        $queryBuilder
            ->setParameter('slug', $slug)
            ->setParameter('activeSiteId', $activeSiteDto->id)
        ;

        return $queryBuilder;
    }

    public function getMainPage(ActiveSiteDto $activeSiteDto, string $language): ?Page
    {
        return $this->findOneBy(['siteId' => $activeSiteDto->id, 'lang' => $language, 'slug' => '/']);
    }

    public function getItemsQueryBuilder(string $locale, ?Page $parent = null, ?int $limit = null, string $alias = 'p'): QueryBuilder
    {
        $queryBuilder =
            $this
            ->getQueryBuilderWithLanguage($locale, $alias)
          //  ->addOrderBy('p.position', "ASC")
            ->addOrderBy("{$alias}.createdAt", "DESC")

        ;

        if ($parent !== null) {
            $queryBuilder
                ->andWhere("{$alias}.parent=:parent")
                ->setParameter("parent", $parent);
        } else {
            $queryBuilder
                ->andWhere("{$alias}.parent IS NULL");
        }

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder;
    }

    public function getQueryBuilderWithLanguage(string $locale, string $alias = 'p', ?QueryBuilder $queryBuilder = null): QueryBuilder
    {
        $queryBuilder ??= $this->createQueryBuilder($alias);
        return $queryBuilder
            ->leftJoin("{$alias}.menu", "menu")
            ->addSelect("menu")
            ->andWhere("{$alias}.lang=:lang")
            ->setParameter('lang', $locale)
            ;
    }

    public function getItemsOnMainPageQueryBuilder(string $locale, ?int $limit = null, string $alias = 'p'): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilderWithLanguage($locale, $alias);
        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        $queryBuilder
            ->andWhere("{$alias}.isPreviewOnMain=:isPreviewOnMain")
            ->setParameter("isPreviewOnMain", true)
        ;
        return $queryBuilder;
    }

    public function save(Page $page, $isFlush = true): void
    {
        $this->getEntityManager()->persist($page);
        if ($isFlush) {
            $this->getEntityManager()->flush();
        }
    }
}
