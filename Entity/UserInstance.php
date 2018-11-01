<?php

namespace Webkul\UVDesk\CoreBundle\Entity;

/**
 * UserInstance
 */
class UserInstance
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $skypeId;

    /**
     * @var string
     */
    private $contactNumber;

    /**
     * @var string
     */
    private $designation;

    /**
     * @var string
     */
    private $signature;

    /**
     * @var string
     */
    private $profileImagePath;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @var boolean
     */
    private $isActive = false;

    /**
     * @var boolean
     */
    private $isVerified = false;

    /**
     * @var boolean
     */
    private $isStarred = false;

    /**
     * @var string
     */
    private $ticketAccessLevel;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userSavedReplies;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userSavedFilters;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\SupportRole
     */
    private $supportRole;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $supportPrivileges;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $supportTeams;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $supportGroups;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $leadSupportTeams;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $adminSupportGroups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userSavedReplies = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userSavedFilters = new \Doctrine\Common\Collections\ArrayCollection();
        $this->supportPrivileges = new \Doctrine\Common\Collections\ArrayCollection();
        $this->supportTeams = new \Doctrine\Common\Collections\ArrayCollection();
        $this->supportGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->leadSupportTeams = new \Doctrine\Common\Collections\ArrayCollection();
        $this->adminSupportGroups = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set source
     *
     * @param string $source
     *
     * @return UserInstance
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set skypeId
     *
     * @param string $skypeId
     *
     * @return UserInstance
     */
    public function setSkypeId($skypeId)
    {
        $this->skypeId = $skypeId;

        return $this;
    }

    /**
     * Get skypeId
     *
     * @return string
     */
    public function getSkypeId()
    {
        return $this->skypeId;
    }

    /**
     * Set contactNumber
     *
     * @param string $contactNumber
     *
     * @return UserInstance
     */
    public function setContactNumber($contactNumber)
    {
        $this->contactNumber = $contactNumber;

        return $this;
    }

    /**
     * Get contactNumber
     *
     * @return string
     */
    public function getContactNumber()
    {
        return $this->contactNumber;
    }

    /**
     * Set designation
     *
     * @param string $designation
     *
     * @return UserInstance
     */
    public function setDesignation($designation)
    {
        $this->designation = $designation;

        return $this;
    }

    /**
     * Get designation
     *
     * @return string
     */
    public function getDesignation()
    {
        return $this->designation;
    }

    /**
     * Set signature
     *
     * @param string $signature
     *
     * @return UserInstance
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Set profileImagePath
     *
     * @param string $profileImagePath
     *
     * @return UserInstance
     */
    public function setProfileImagePath($profileImagePath)
    {
        $this->profileImagePath = $profileImagePath;

        return $this;
    }

    /**
     * Get profileImagePath
     *
     * @return string
     */
    public function getProfileImagePath()
    {
        return $this->profileImagePath;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return UserInstance
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return UserInstance
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return UserInstance
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
     * Set isVerified
     *
     * @param boolean $isVerified
     *
     * @return UserInstance
     */
    public function setIsVerified($isVerified)
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * Get isVerified
     *
     * @return boolean
     */
    public function getIsVerified()
    {
        return $this->isVerified;
    }

    /**
     * Set isStarred
     *
     * @param boolean $isStarred
     *
     * @return UserInstance
     */
    public function setIsStarred($isStarred)
    {
        $this->isStarred = $isStarred;

        return $this;
    }

    /**
     * Get isStarred
     *
     * @return boolean
     */
    public function getIsStarred()
    {
        return $this->isStarred;
    }

    /**
     * Set ticketAccessLevel
     *
     * @param string $ticketAccessLevel
     *
     * @return UserInstance
     */
    public function setTicketAccessLevel($ticketAccessLevel)
    {
        $this->ticketAccessLevel = $ticketAccessLevel;

        return $this;
    }

    /**
     * Get ticketAccessLevel
     *
     * @return string
     */
    public function getTicketAccessLevel()
    {
        return $this->ticketAccessLevel;
    }

    /**
     * Add userSavedReply
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\EmailTemplates $userSavedReply
     *
     * @return UserInstance
     */
    public function addUserSavedReply(\Webkul\UVDesk\CoreBundle\Entity\EmailTemplates $userSavedReply)
    {
        $this->userSavedReplies[] = $userSavedReply;

        return $this;
    }

    /**
     * Remove userSavedReply
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\EmailTemplates $userSavedReply
     */
    public function removeUserSavedReply(\Webkul\UVDesk\CoreBundle\Entity\EmailTemplates $userSavedReply)
    {
        $this->userSavedReplies->removeElement($userSavedReply);
    }

    /**
     * Get userSavedReplies
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserSavedReplies()
    {
        return $this->userSavedReplies;
    }

    /**
     * Add userSavedFilter
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SavedFilters $userSavedFilter
     *
     * @return UserInstance
     */
    public function addUserSavedFilter(\Webkul\UVDesk\CoreBundle\Entity\SavedFilters $userSavedFilter)
    {
        $this->userSavedFilters[] = $userSavedFilter;

        return $this;
    }

    /**
     * Remove userSavedFilter
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SavedFilters $userSavedFilter
     */
    public function removeUserSavedFilter(\Webkul\UVDesk\CoreBundle\Entity\SavedFilters $userSavedFilter)
    {
        $this->userSavedFilters->removeElement($userSavedFilter);
    }

    /**
     * Get userSavedFilters
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserSavedFilters()
    {
        return $this->userSavedFilters;
    }

    /**
     * Set user
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\User $user
     *
     * @return UserInstance
     */
    public function setUser(\Webkul\UVDesk\CoreBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Webkul\UVDesk\CoreBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set supportRole
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportRole $supportRole
     *
     * @return UserInstance
     */
    public function setSupportRole(\Webkul\UVDesk\CoreBundle\Entity\SupportRole $supportRole = null)
    {
        $this->supportRole = $supportRole;

        return $this;
    }

    /**
     * Get supportRole
     *
     * @return \Webkul\UVDesk\CoreBundle\Entity\SupportRole
     */
    public function getSupportRole()
    {
        return $this->supportRole;
    }

    /**
     * Add supportPrivilege
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportPrivilege $supportPrivilege
     *
     * @return UserInstance
     */
    public function addSupportPrivilege(\Webkul\UVDesk\CoreBundle\Entity\SupportPrivilege $supportPrivilege)
    {
        $this->supportPrivileges[] = $supportPrivilege;

        return $this;
    }

    /**
     * Remove supportPrivilege
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportPrivilege $supportPrivilege
     */
    public function removeSupportPrivilege(\Webkul\UVDesk\CoreBundle\Entity\SupportPrivilege $supportPrivilege)
    {
        $this->supportPrivileges->removeElement($supportPrivilege);
    }

    /**
     * Get supportPrivileges
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSupportPrivileges()
    {
        return $this->supportPrivileges;
    }

    /**
     * Add supportTeam
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportTeam $supportTeam
     *
     * @return UserInstance
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
     * Add supportGroup
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportGroup $supportGroup
     *
     * @return UserInstance
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
     * Add leadSupportTeam
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportTeam $leadSupportTeam
     *
     * @return UserInstance
     */
    public function addLeadSupportTeam(\Webkul\UVDesk\CoreBundle\Entity\SupportTeam $leadSupportTeam)
    {
        $this->leadSupportTeams[] = $leadSupportTeam;

        return $this;
    }

    /**
     * Remove leadSupportTeam
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportTeam $leadSupportTeam
     */
    public function removeLeadSupportTeam(\Webkul\UVDesk\CoreBundle\Entity\SupportTeam $leadSupportTeam)
    {
        $this->leadSupportTeams->removeElement($leadSupportTeam);
    }

    /**
     * Get leadSupportTeams
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLeadSupportTeams()
    {
        return $this->leadSupportTeams;
    }

    /**
     * Add adminSupportGroup
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportGroup $adminSupportGroup
     *
     * @return UserInstance
     */
    public function addAdminSupportGroup(\Webkul\UVDesk\CoreBundle\Entity\SupportGroup $adminSupportGroup)
    {
        $this->adminSupportGroups[] = $adminSupportGroup;

        return $this;
    }

    /**
     * Remove adminSupportGroup
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportGroup $adminSupportGroup
     */
    public function removeAdminSupportGroup(\Webkul\UVDesk\CoreBundle\Entity\SupportGroup $adminSupportGroup)
    {
        $this->adminSupportGroups->removeElement($adminSupportGroup);
    }

    /**
     * Get adminSupportGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdminSupportGroups()
    {
        return $this->adminSupportGroups;
    }
    /**
     * @ORM\PrePersist
     *
     * Initialize timestamps for createdAt and updatedAt fields when persisting the UserInstance to database
     * the first time.
     *
     * @return UserInstance
    */
    public function initializeUserTimestamp()
    {
        $this->createdAt = $this->updatedAt = new \DateTime('now');

        return $this;
    }

    /**
     * @ORM\PreUpdate
     *
     * Updates the updatedAt field when persisting the UserInstance to database.
     *
     * @return UserInstance
    */
    public function updateUserTimestamp()
    {
        $this->updatedAt = new \DateTime('now');

        return $this;
    }

    /**
     * Get user partial data
     *
     * @return array
     */
    public function getPartialDetails()
    {
        return [
            'id' => $this->getUser()->getId(),
            'email' => $this->getUser()->getEmail(),
            'name' => $this->getUser()->getFullName(),
            'firstName' => $this->getUser()->getFirstName(),
            'lastName' => $this->getUser()->getLastName(),
            'contactNumber' => $this->getContactNumber(),
            'thumbnail' => $this->getProfileImagePath(),
        ];
    }
}

