<?php

namespace MartenaSoft\PageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MartenaSoft\PageBundle\Entity\Menu;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    public function updateMenuAllMenu(int $siteId, $author, int $entityTYpe = 1): array
    {
        $result = ['truncated' => $this->getEntityManager()->getConnection()->executeStatement("TRUNCATE TABLE menu;")];

        $sql = "
        WITH RECURSIVE page_tree AS (
            SELECT
                p.id,
                p.slug,
                p.is_on_top_menu,
                p.is_on_left_menu,
                p.is_on_footer_menu,
                p.parent_id,
                1 AS level,
                p.slug::varchar AS full_path,
                jsonb_build_array(
                  jsonb_build_object(
                            'id', p.id,
                            'name', p.name,
                            'route_name', CASE WHEN p.route_name IS NOT NULL THEN p.route_name ELSE ''::varchar END
                        )
                ) AS parents

            FROM page p
            WHERE p.parent_id IS NULL AND p.is_deleted = FALSE
            UNION ALL
            SELECT
                c.id,
                c.slug,
                c.is_on_top_menu,
                c.is_on_left_menu,
                c.is_on_footer_menu,
                c.parent_id,
                c.level + 1 AS level,
                regexp_replace((pt.full_path || '/' || c.slug)::varchar, '^/+', '' ) AS full_path,
                pt.parents || jsonb_build_array(

                        jsonb_build_object(
                            'id', c.id,
                            'name', c.name,
                            'full_path', regexp_replace((pt.full_path || '/' || c.slug)::varchar, '^/+', ''),
                            'route_name', CASE WHEN c.route_name IS NOT NULL THEN c.route_name ELSE ''::varchar END
                        )
                )

            FROM page c JOIN page_tree pt ON pt.id = c.parent_id)
            INSERT INTO menu (page_id, entity_type, parents, slug, created_at, site_id, full_path, is_on_top_menu, is_on_left_menu, is_on_footer_menu, parent_id, level, author)
            SELECT id as entity_id, {$entityTYpe} as entity_type, parents, slug, now() as created_at, {$siteId} as site_id, full_path, is_on_top_menu, is_on_left_menu, is_on_footer_menu, parent_id, level, '{$author}' as author FROM page_tree
        ON CONFLICT (id) DO UPDATE SET slug = EXCLUDED.slug";


        $result['inserted'] = $this->getEntityManager()->getConnection()->executeStatement($sql);
        return $result;
    }

    public function getMenuItems(int $siteId, string $local): array
    {
        $sql = "WITH RECURSIVE menu_tree AS (
                SELECT
                    m.id,
                    m.page_id,
                    m.parent_id,
                    m.slug,
                    m.full_path,
                    m.is_on_top_menu,
                    m.is_on_left_menu,
                    m.is_on_footer_menu,
                    m.parents,
                    1 AS level
                FROM menu m
                WHERE m.parent_id IS NULL AND m.site_id = {$siteId}

                UNION ALL

                SELECT
                    c.id,
                    c.page_id,
                    c.parent_id,
                    c.slug,
                    c.full_path,
                    c.is_on_top_menu,
                    c.is_on_left_menu,
                    c.is_on_footer_menu,
                    c.parents,
                    mt.level + 1 AS level
                FROM menu c
                INNER JOIN menu_tree mt ON c.parent_id = mt.page_id
            )
            SELECT p.name, p.slug, p.route_name, p.route_params, p.type, p.title, mt.level, mt.full_path, mt.is_on_top_menu, mt.is_on_left_menu, mt.is_on_footer_menu
             FROM menu_tree mt LEFT JOIN page p ON mt.page_id = p.id WHERE p.lang='".$local."' AND p.is_deleted = FALSE ORDER BY mt.level, mt.page_id ;";

        return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
    }
}
