<?php

namespace MartenaSoft\PageBundle\Repository;

use MartenaSoft\PageBundle\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use MartenaSoft\CommonLibrary\Dto\ActiveSiteDto;

class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function getCount(array $filter = []): int
    {
        return $this->count($filter);
    }

    public function getItemsInBasketQueryBuilder(ActiveSiteDto $activeSiteDto): QueryBuilder
    {
        return $this
            ->createQueryBuilder('p')
            ->andWhere('p.isDeleted = :isDeleted')
            ->andWhere('p.siteId = :siteId')
            ->setParameter('isDeleted', true)
            ->setParameter('siteId', $activeSiteDto->id);
    }

    public function getOneBySlugQueryBuilder(
        ActiveSiteDto $activeSiteDto,
        string $slug,
        string $locale,
        string $alias = 'p'
    ): QueryBuilder
    {

        $queryBuilder = $this->getQueryBuilderWithLanguage($locale, $alias);
        $queryBuilder->andWhere("{$alias}.slug=:slug");
        $queryBuilder->andWhere("{$alias}.siteId=:activeSiteId");
        $queryBuilder
            ->setParameter('slug', $slug)
            ->setParameter('activeSiteId', $activeSiteDto->id);

        return $queryBuilder;
    }

    public function getItemsQueryBuilder(
        string $locale,
        ?Page $parent = null,
        ?int $limit = null,
        string $alias = 'p',
        ?bool $isOnMain = null,
        bool $isOrder = true
    ): QueryBuilder
    {
        $queryBuilder =
            $this->getQueryBuilderWithLanguage($locale, $alias);

        if ($isOrder) {
            $queryBuilder
                ->addOrderBy("{$alias}.position", "ASC")
                ->addOrderBy("{$alias}.createdAt", "DESC");
        }

        if ($isOnMain !== null) {
            $queryBuilder
                ->andWhere("{$alias}.isOnMain=:isOnMain")
                ->setParameter("isOnMain", $isOnMain);
        }

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
            ->setParameter('lang', $locale);
    }

    public function getItemsOnMainPageQueryBuilder(string $locale, ?int $limit = null, string $alias = 'p'): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilderWithLanguage($locale, $alias);
        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        $queryBuilder
            ->andWhere("{$alias}.isPreviewOnMain=:isPreviewOnMain")
            ->setParameter("isPreviewOnMain", true);
        return $queryBuilder;
    }

    public function save(Page $page, $isFlush = true): void
    {
        $this->getEntityManager()->persist($page);
        if ($isFlush) {
            $this->getEntityManager()->flush();
        }
    }

    public function collectTreeUuids(string $rootUuid): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
WITH RECURSIVE tree AS (
    SELECT id, uuid
    FROM page
    WHERE uuid = :rootUuid AND is_deleted = FALSE

  UNION ALL

    SELECT p.id, p.uuid
    FROM page p
    INNER JOIN tree t ON p.parent_id = t.id
)
SELECT uuid
FROM tree
SQL;

        $rows = $conn->fetchFirstColumn($sql, ['rootUuid' => $rootUuid]);

        $uuids = array_values(array_unique(array_filter($rows, static fn($v) => is_string($v) && $v !== '')));

        return $uuids;
    }

    public function applyTreeDeletionByUuids(array $uuids, bool $hardDelete = false): int
    {
        $uuids = array_values(array_unique(array_filter($uuids, static fn($v) => is_string($v) && $v !== '')));

        if ($uuids === []) {
            return 0;
        }

        $conn = $this->getEntityManager()->getConnection();

        if ($hardDelete) {
            $sql = 'DELETE FROM page WHERE uuid = ANY(:uuids)';
        } else {
            // Если у тебя в таблице имена колонок другие — поправь тут
            $sql = 'UPDATE page SET is_deleted = TRUE, deleted_at = NOW() WHERE uuid = ANY(:uuids)';
        }

        return $conn->executeStatement($sql, ['uuids' => $uuids], ['uuids' => \Doctrine\DBAL\ArrayParameterType::STRING]);
    }

    public function deleteSafeByUuids(array $uuids = []): void
    {
        if (empty($uuids)) {
            return;
        }

        $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->update(Page::class, 'p')
            ->set('p.isDeleted', ':isDeleted')
            ->set('p.deletedAt', ':cureTime')
            ->where('p.uuid IN (:uuids)')
            ->setParameter('isDeleted', true)
            ->setParameter('cureTime', new \DateTimeImmutable())
            ->setParameter('uuids', $uuids)
            ->getQuery()
            ->execute()
            ;
    }
}
