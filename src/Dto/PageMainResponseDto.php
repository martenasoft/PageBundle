<?php

namespace MartenaSoft\PageBundle\Dto;

use MartenaSoft\PageBundle\Entity\Page;

class PageMainResponseDto
{
    public function __construct(
        private ?Page $page = null,
        private ?array $itemsOnMain = null,
        private ?array $imagesConfig = null
    ) {
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function getItemsOnMain(): ?array
    {
        return $this->itemsOnMain;
    }

    public function getImagesConfig(): ?array
    {
        return $this->imagesConfig;
    }
}
