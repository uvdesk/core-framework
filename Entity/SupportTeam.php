<?php

namespace Webkul\UVDesk\CoreBundle\Entity;

/**
 * SupportTeam
 */
class SupportTeam
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var boolean
     */
    private $isActive = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $users;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $leads;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $supportGroups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->leads = new \Doctrine\Common\Collections\ArrayCollection();
        $this->supportGroups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return SupportTeam
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return SupportTeam
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return SupportTeam
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return SupportTeam
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Add user
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\UserInstance $user
     *
     * @return SupportTeam
     */
    public function addUser(\Webkul\UVDesk\CoreBundle\Entity\UserInstance $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\UserInstance $user
     */
    public function removeUser(\Webkul\UVDesk\CoreBundle\Entity\UserInstance $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add lead
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\UserInstance $lead
     *
     * @return SupportTeam
     */
    public function addLead(\Webkul\UVDesk\CoreBundle\Entity\UserInstance $lead)
    {
        $this->leads[] = $lead;

        return $this;
    }

    /**
     * Remove lead
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\UserInstance $lead
     */
    public function removeLead(\Webkul\UVDesk\CoreBundle\Entity\UserInstance $lead)
    {
        $this->leads->removeElement($lead);
    }

    /**
     * Get leads
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * Add supportGroup
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportGroup $supportGroup
     *
     * @return SupportTeam
     */
    public function addSupportGroup(\Webkul\UVDesk\CoreBundle\Entity\SupportGroup $supportGroup)
    {
        $this->supportGroups[] = $supportGroup;

        return $this;
    }

    /**
     * Remove supportGroup
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportGroup $supportGroup
     */
    public function removeSupportGroup(\Webkul\UVDesk\CoreBundle\Entity\SupportGroup $supportGroup)
    {
        $this->supportGroups->removeElement($supportGroup);
    }

    /**
     * Get supportGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSupportGroups()
    {
        return $this->supportGroups;
    }
    /**
     * @ORM\PrePersist
     */
    public function initializeTimestamp()
    {
        $this->createdAt = new \DateTime('now');
    }
}

