<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $scholarship = null;

    #[ORM\Column(length: 255, options:['default'=>"en cours"])]
    private ?string $status = null;

    #[ORM\OneToOne(inversedBy: 'candidature', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $User = null;

    #[ORM\OneToOne(inversedBy: 'candidature', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formation $Formation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScholarship(): ?string
    {
        return $this->scholarship;
    }

    public function setScholarship(string $scholarship): static
    {
        $this->scholarship = $scholarship;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(User $User): static
    {
        $this->User = $User;

        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->Formation;
    }

    public function setFormation(Formation $Formation): static
    {
        $this->Formation = $Formation;

        return $this;
    }
}
