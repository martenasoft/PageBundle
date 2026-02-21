<?php

namespace MartenaSoft\PageBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MartenaSoft\CommonLibrary\Entity\Interfaces\AuthorInterface;
use MartenaSoft\CommonLibrary\Entity\Traits\AuthorTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\BodyTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\CreatedAtTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\DeletedAtTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\IsPreviewOnMainTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\LangTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\LevelTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\MenuTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\NameTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\PostgresIdTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\PreviewTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\PublicAtTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\SeoTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\SiteIdTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\SlugTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\StatusTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\TypeTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\UpdatedAtTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\UuidTrait;
use MartenaSoft\ImageBundle\Entity\Image;
use MartenaSoft\MenuBundle\Entity\Menu;
use MartenaSoft\PageBundle\Repository\PageRepository;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\UniqueConstraint(name: 'SLUG_LOCALE', fields: ['slug', 'locale'])]

#[ORM\HasLifecycleCallbacks]
class Page implements AuthorInterface
{
    use
        PostgresIdTrait,
        UuidTrait,
        SeoTrait,
        CreatedAtTrait,
        UpdatedAtTrait,
        DeletedAtTrait,
        StatusTrait,
        SlugTrait,
        NameTrait,
        TypeTrait,
        PreviewTrait,
        BodyTrait,
        PostgresIdTrait,
        PublicAtTrait,
        IsPreviewOnMainTrait,
        LangTrait,
        LevelTrait,
        SiteIdTrait,
        MenuTrait,
        AuthorTrait
        ;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\OneToOne(mappedBy: 'page', targetEntity: Menu::class)]
    private ?Menu $menu = null;

    private Collection $images;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $routeName = null;

    #[ORM\Column(nullable: true)]
    private ?array $routeParams = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): Page
    {
        $this->menu = $menu;
        return $this;
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->addPage($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        if ($this->images->removeElement($image)) {
            $image->removePage($this);
        }
        return $this;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(?string $routeName): Page
    {
        $this->routeName = $routeName;
        return $this;
    }

    public function getRouteParams(): ?array
    {
        return $this->routeParams;
    }

    public function setRouteParams(?array $routeParams): Page
    {
        $this->routeParams = $routeParams;
        return $this;
    }
}
