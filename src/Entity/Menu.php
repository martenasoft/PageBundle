<?php

namespace MartenaSoft\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MartenaSoft\CommonLibrary\Entity\Interfaces\AuthorInterface;
use MartenaSoft\CommonLibrary\Entity\Traits\AuthorTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\CreatedAtTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\DeletedAtTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\IsDeletedTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\MenuTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\PostgresIdTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\SiteIdTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\SlugTrait;
use MartenaSoft\CommonLibrary\Entity\Traits\UpdatedAtTrait;
use MartenaSoft\PageBundle\Repository\MenuRepository;
use MartenaSoft\PageBundle\Entity\Page;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu implements AuthorInterface
{
    use
        PostgresIdTrait,
        CreatedAtTrait,
        UpdatedAtTrait,
        IsDeletedTrait,
        DeletedAtTrait,
        SlugTrait,
        SiteIdTrait,
        MenuTrait,
        AuthorTrait
        ;

    #[ORM\Column(nullable: true)]
    private ?array $parents = null;

    #[ORM\OneToOne(targetEntity: Page::class, inversedBy: 'menu')]
    #[ORM\JoinColumn(
        name: 'page_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Page $page = null;

    #[ORM\Column(nullable: true)]
    private ?int $entityType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fullPath = null;

    #[ORM\Column(nullable: true)]
    private ?int $parentId = null;

    #[ORM\Column(nullable: true)]
    private ?int $level = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParents(): ?array
    {
        return $this->parents;
    }

    public function setParents(?array $parents): static
    {
        $this->parents = $parents;

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): self
    {
        $this->page = $page;
        return $this;
    }

    public function getEntityType(): ?int
    {
        return $this->entityType;
    }

    public function setEntityType(?int $entityType): self
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getFullPath(): ?string
    {
        return $this->fullPath;
    }

    public function setFullPath(?string $fullPath): self
    {
        $this->fullPath = $fullPath;
        return $this;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(?int $parentId): self
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): Menu
    {
        $this->level = $level;
        return $this;
    }
}
