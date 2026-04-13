<?php

namespace MartenaSoft\PageBundle\Event;

use MartenaSoft\CommonLibrary\Dto\ActiveSiteDto;
use MartenaSoft\PageBundle\Entity\Page;
use Symfony\Contracts\EventDispatcher\Event;

class SavedPageEvent extends Event
{
    public const string NAME = 'saved_page.event';

    public function __construct(private Page $page, private ActiveSiteDto $activeSiteDto)
    {
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function getActiveSiteDto(): ActiveSiteDto
    {
        return $this->activeSiteDto;
    }
}
