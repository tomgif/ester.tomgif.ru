<?php

namespace App\Entity;

use App\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Assert\NotBlank(message: 'Заголовок не должен быть пустым')]
    #[Assert\Length(max: 255, maxMessage: 'Заголовок не должен превышать 255 символов')]
    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    #[Assert\Length(max: 255, maxMessage: 'Описание не должен превышать 255 символов')]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $description;

    #[Assert\NotBlank(message: 'Символьный идентификатор не должен быть пустым')]
    #[Assert\Length(max: 50, maxMessage: 'Символьный идентификатор не должен превышать 50 символов')]
    #[ORM\Column(type: 'string', length: 50)]
    private $slug;

    #[ORM\Column(type: 'boolean')]
    private $is_page = false;

    #[ORM\Column(type: 'boolean')]
    private $is_published = false;

    #[Assert\NotBlank(message: 'Пост не должен быть пустым')]
    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private $updatedAt;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Comment::class, cascade: ['persist'], orphanRemoval: true)]
    private $comments;

    #[Pure] public function __construct()
    {
        $this->comments = new ArrayCollection;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatedTimestamps(): void
    {
        $dateTimeNow = new DateTimeImmutable('now');

        $this->setUpdatedAt($dateTimeNow);

        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt($dateTimeNow);
        }
    }

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $publishedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getIsPage(): ?bool
    {
        return $this->is_page;
    }

    public function setIsPage(bool $is_page): self
    {
        $this->is_page = $is_page;

        return $this;
    }

    public function getIsPublished(): ?bool
    {
        return $this->is_published;
    }

    public function setIsPublished(bool $is_published): self
    {
        $this->is_published = $is_published;

        if ($is_published) {
            $this->publishedAt = new DateTimeImmutable('now');
        }

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments->filter(function (Comment $comment) {
            return $comment->getParent() === null;
        });
    }
}
