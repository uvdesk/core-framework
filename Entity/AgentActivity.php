<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;

/**
 * @ORM\Entity(repositoryClass="Webkul\UVDesk\CoreFrameworkBundle\Repository\AgentActivityRepository")
 */

class AgentActivity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\UVDesk\CoreFrameworkBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $agentId;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ticketId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $agentName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customerName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $threadType;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgentId(): ?User
    {
        return $this->agentId;
    }

    public function setAgentId(?User $agentId): self
    {
        $this->agentId = $agentId;

        return $this;
    }

    public function getTicketId(): ?Ticket
    {
        return $this->ticketId;
    }

    public function setTicketId(?Ticket $ticketId): self
    {
        $this->ticketId = $ticketId;

        return $this;
    }

    public function getAgentName(): ?string
    {
        return $this->agentName;
    }

    public function setAgentName(?string $agentName): self
    {
        $this->agentName = $agentName;

        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(?string $customerName): self
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getThreadType(): ?string
    {
        return $this->threadType;
    }

    public function setThreadType(?string $threadType): self
    {
        $this->threadType = $threadType;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
