<?php

namespace App\Entity;

use App\Repository\SocialUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: SocialUserRepository::class)]
class SocialUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\Column(type: 'string', length: 50)]
    private $provider;

    #[ORM\Column(type: 'string', length: 100)]
    private $externalId;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'socialUsers')]
    private $user;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $old;

    public function __construct(UserInterface $user)
    {
        $this->setUser($user);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getOld(): UserInterface
    {
        return $this->old;
    }

    public function setOld($old): self
    {
        $this->old = $old;

        return $this;
    }

}
