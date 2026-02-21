<?php

namespace MartenaSoft\PageBundle\Dto;

use Knp\Bundle\PaginatorBundle\Pagination\SlidingPaginationInterface;
use MartenaSoft\PageBundle\Entity\Page;
use MartenaSoft\SiteBundle\Dto\ActiveSiteDto;

class PageResponseDto
{
    public function __construct(
        private ?Page $page = null,
        private ?SlidingPaginationInterface $items = null,
        private ?ActiveSiteDto $activeSite = null
    ) {
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function getItems(): ?SlidingPaginationInterface
    {
        return $this->items;
    }

    public function getActiveSite(): ?ActiveSiteDto
    {
        return $this->activeSite;
    }
}
