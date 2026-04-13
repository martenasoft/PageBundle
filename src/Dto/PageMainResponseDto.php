<?php

namespace MartenaSoft\PageBundle\Dto;

use MartenaSoft\PageBundle\Entity\Page;

class PageMainResponseDto
{
    public function __construct(
        private ?array $pages = null,
        private ?array $itemsOnMain = null,
        private ?array $imagesConfig = null
    ) {
    }

    public function getPages(): ?array
    {
        return $this->pages;
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
