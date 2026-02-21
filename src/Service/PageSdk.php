<?php

namespace MartenaSoft\PageBundle\Service;

use MartenaSoft\PageBundle\Repository\PageRepository;
use MartenaSoft\SdkBundle\Service\Interfaces\PageSdkInterface;

class PageSdk implements PageSdkInterface
{
    public function __construct(
        private PageRepository $pageRepository,
    )
    {

    }
    public function getCount(array $filter = []): int
    {
        return $this->pageRepository->getCount($filter);
    }
}
