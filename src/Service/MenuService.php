<?php

namespace MartenaSoft\PageBundle\Service;

use MartenaSoft\PageBundle\Repository\MenuRepository;

class MenuService
{
    private array $menuItems = [];
    public function __construct(
        private readonly MenuRepository $menuRepository
    ) {
    }

    public function getMenu(int $siteId, string $local): array
    {
        if (!empty($this->menuItems)) {
            return $this->menuItems;
        }

        $result = $this->menuRepository->getMenuItems($siteId, $local);

        if (empty($result)) {
            return [];
        }

        foreach ($result as $item) {

            $isOnMenu = false;

            if (!empty($item['is_on_top_menu'])) {
                $this->menuItems['top'][] = $item;
                $isOnMenu = true;
            }
            if (!empty($item['is_on_left_menu'])) {
                $this->menuItems['left'][] = $item;
                $isOnMenu = true;
            }
            if (!empty($item['is_on_footer_menu'])) {
                $this->menuItems['footer'][] = $item;
                $isOnMenu = true;
            }

            if (!$isOnMenu) {
                $this->menuItems['other'][] = $item;
            }
        }

        return $this->menuItems;
    }
}
