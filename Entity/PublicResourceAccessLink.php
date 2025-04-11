<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PublicResourceAccessLink
 * @ORM\Entity(repositoryClass=null)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="uv_public_resource_access_link")
 */
class PublicResourceAccessLink
{
     /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="resourceId", type="string", length=255)
     */
    private $resourceId;

    /**
     * @var string
     *
     * @ORM\Column(name="resourceType", type="string", length=255)
     */
    private $resourceType;

    /**
     * @var string
     *
     * @ORM\Column(name="uniqueResourceAccessId", type="string", length=64, unique=true)
     */
    private $uniqueResourceAccessId;

    /**
     * @var integer
     *
     * @ORM\Column(name="totalViews", type="integer")
     */
    private $totalViews;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiresAt", type="datetime")
     */
    private $expiresAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isExpired", type="boolean")
     */
    private $isExpired;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\UVDesk\CoreFrameworkBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;

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
     * Set resourceId
     *
     * @param string $resourceId
     *
     * @return PublicResourceAccessLink
     */
    public function setResourceId($resourceId)
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    /**
     * Get resourceId
     *
     * @return string
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * Set resourceType
     *
     * @param string $resourceType
     *
     * @return PublicResourceAccessLink
     */
    public function setResourceType($resourceType)
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    /**
     * Get resourceType
     *
     * @return string
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Set uniqueResourceAccessId
     *
     * @param string $uniqueResourceAccessId
     *
     * @return PublicResourceAccessLink
     */
    public function setUniqueResourceAccessId($uniqueResourceAccessId)
    {
        $this->uniqueResourceAccessId = $uniqueResourceAccessId;

        return $this;
    }

    /**
     * Get uniqueResourceAccessId
     *
     * @return string
     */
    public function getUniqueResourceAccessId()
    {
        return $this->uniqueResourceAccessId;
    }

    /**
     * Set totalViews
     *
     * @param integer $totalViews
     *
     * @return PublicResourceAccessLink
     */
    public function setTotalViews($totalViews)
    {
        $this->totalViews = $totalViews;

        return $this;
    }

    /**
     * Get totalViews
     *
     * @return integer
     */
    public function getTotalViews()
    {
        return $this->totalViews;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return PublicResourceAccessLink
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
     * Set expiresAt
     *
     * @param \DateTime $expiresAt
     *
     * @return PublicResourceAccessLink
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Get expiresAt
     *
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * Set isExpired
     *
     * @param boolean $isExpired
     *
     * @return PublicResourceAccessLink
     */
    public function setIsExpired($isExpired)
    {
        $this->isExpired = $isExpired;

        return $this;
    }

    /**
     * Get isExpired
     *
     * @return boolean
     */
    public function getIsExpired()
    {
        return $this->isExpired;
    }

    /**
     * Set user
     *
     * @param \Webkul\UVDesk\CoreFrameworkBundle\Entity\User $user
     *
     * @return User
     */
    public function setUser(\Webkul\UVDesk\CoreFrameworkBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Webkul\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}


