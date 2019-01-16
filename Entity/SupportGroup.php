<?php

namespace Webkul\UVDesk\CoreBundle\Entity;

/**
 * SupportGroup
 */
class SupportGroup
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
     * @var boolean
     */
    private $userView = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $users;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $admins;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $supportTeams;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->admins = new \Doctrine\Common\Collections\ArrayCollection();
        $this->supportTeams = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return SupportGroup
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
     * @return SupportGroup
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
     * @return SupportGroup
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
     * @return SupportGroup
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
     * Set userView
     *
     * @param boolean $userView
     *
     * @return SupportGroup
     */
    public function setUserView($userView)
    {
        $this->userView = $userView;

        return $this;
    }

    /**
     * Get userView
     *
     * @return boolean
     */
    public function getUserView()
    {
        return $this->userView;
    }

    /**
     * Add user
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\UserInstance $user
     *
     * @return SupportGroup
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
     * Add admin
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\UserInstance $admin
     *
     * @return SupportGroup
     */
    public function addAdmin(\Webkul\UVDesk\CoreBundle\Entity\UserInstance $admin)
    {
        $this->admins[] = $admin;

        return $this;
    }

    /**
     * Remove admin
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\UserInstance $admin
     */
    public function removeAdmin(\Webkul\UVDesk\CoreBundle\Entity\UserInstance $admin)
    {
        $this->admins->removeElement($admin);
    }

    /**
     * Get admins
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdmins()
    {
        return $this->admins;
    }

    /**
     * Add supportTeam
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportTeam $supportTeam
     *
     * @return SupportGroup
     */
    public function addSupportTeam(\Webkul\UVDesk\CoreBundle\Entity\SupportTeam $supportTeam)
    {
        $this->supportTeams[] = $supportTeam;

        return $this;
    }

    /**
     * Remove supportTeam
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportTeam $supportTeam
     */
    public function removeSupportTeam(\Webkul\UVDesk\CoreBundle\Entity\SupportTeam $supportTeam)
    {
        $this->supportTeams->removeElement($supportTeam);
    }

    /**
     * Get supportTeams
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSupportTeams()
    {
        return $this->supportTeams;
    }
    /**
     * @ORM\PrePersist
     */
    public function initializeTimestamp()
    {
        $this->createdAt = new \DateTime('now');
    }
}

