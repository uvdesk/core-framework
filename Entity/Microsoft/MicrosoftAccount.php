<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Entity\Microsoft;

use Doctrine\ORM\Mapping as ORM;
use Webkul\UVDesk\CoreFrameworkBundle\Repository\Microsoft\MicrosoftAccountRepository;

/**
 * @ORM\Entity(repositoryClass=MicrosoftAccountRepository::class)
 */
class MicrosoftAccount
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="text")
     */
    private $credentials;

    /**
     * @ORM\ManyToOne(targetEntity=MicrosoftApp::class)
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $microsoftApp;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCredentials(): ?string
    {
        return $this->credentials;
    }

    public function setCredentials(string $credentials): self
    {
        $this->credentials = $credentials;

        return $this;
    }

    public function getMicrosoftApp(): ?MicrosoftApp
    {
        return $this->microsoftApp;
    }

    public function setMicrosoftApp(?MicrosoftApp $microsoftApp): self
    {
        $this->microsoftApp = $microsoftApp;

        return $this;
    }
}
