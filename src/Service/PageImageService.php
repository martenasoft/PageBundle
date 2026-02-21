<?php

namespace MartenaSoft\PageBundle\Service;

use MartenaSoft\CommonLibrary\Dictionary\ImageDictionary;
use MartenaSoft\SdkBundle\Service\ImageServiceSdk;
use MartenaSoft\SiteBundle\Dto\ActiveSiteDto;

readonly class PageImageService
{
    public function __construct(
        private ImageServiceSdk $imageServiceSdk
    )
    {

    }

    public function get(array $uuid, ActiveSiteDto $activeSiteDto): array
    {
        return $this->imageServiceSdk->getImages(
            type: ImageDictionary::TYPE_PAGE,
            uuid: $uuid,
            activeSiteDto: $activeSiteDto,
        );
    }
}
