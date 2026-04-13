<?php

namespace MartenaSoft\PageBundle\EventListener;

use MartenaSoft\PageBundle\Event\SavedPageEvent;
use MartenaSoft\PageBundle\Repository\MenuRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class SavePageListener
{
    public function __construct(
        private readonly MenuRepository $menuRepository,
        private readonly LoggerInterface $logger
    )
    {

    }
    #[AsEventListener]
    public function onSavePage(SavedPageEvent $event): void
    {
        $page = $event->getPage();

        $result = $this->menuRepository->updateMenuAllMenu(
            siteId: $page->getSiteId(),
            author: $page->getAuthor(),
        );
        $this->logger->notice('Updated menu after page save', $result);
    }
}
