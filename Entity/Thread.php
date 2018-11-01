<?php

namespace Webkul\UVDesk\CoreBundle\Entity;

/**
 * Thread
 */
class Thread
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
    private $messageId;

    /**
     * @var string
     */
    private $threadType;

    /**
     * @var string
     */
    private $createdBy;

    /**
     * @var array
     */
    private $cc;

    /**
     * @var array
     */
    private $bcc;

    /**
     * @var array
     */
    private $replyTo;

    /**
     * @var string
     */
    private $deliveryStatus;

    /**
     * @var boolean
     */
    private $isLocked = false;

    /**
     * @var boolean
     */
    private $isBookmarked = false;

    /**
     * @var string
     */
    private $message;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @var \DateTime
     */
    private $agentViewedAt;

    /**
     * @var \DateTime
     */
    private $customerViewedAt;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\Ticket
     */
    private $ticket;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\Attachment
     */
    private $attachments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->attachments = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Thread
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
     * Set messageId
     *
     * @param string $messageId
     *
     * @return Thread
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Get messageId
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set threadType
     *
     * @param string $threadType
     *
     * @return Thread
     */
    public function setThreadType($threadType)
    {
        $this->threadType = $threadType;

        return $this;
    }

    /**
     * Get threadType
     *
     * @return string
     */
    public function getThreadType()
    {
        return $this->threadType;
    }

    /**
     * Set createdBy
     *
     * @param string $createdBy
     *
     * @return Thread
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set cc
     *
     * @param array $cc
     *
     * @return Thread
     */
    public function setCc($cc)
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * Get cc
     *
     * @return array
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Set bcc
     *
     * @param array $bcc
     *
     * @return Thread
     */
    public function setBcc($bcc)
    {
        $this->bcc = $bcc;

        return $this;
    }

    /**
     * Get bcc
     *
     * @return array
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * Set replyTo
     *
     * @param array $replyTo
     *
     * @return Thread
     */
    public function setReplyTo($replyTo)
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * Get replyTo
     *
     * @return array
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * Set deliveryStatus
     *
     * @param string $deliveryStatus
     *
     * @return Thread
     */
    public function setDeliveryStatus($deliveryStatus)
    {
        $this->deliveryStatus = $deliveryStatus;

        return $this;
    }

    /**
     * Get deliveryStatus
     *
     * @return string
     */
    public function getDeliveryStatus()
    {
        return $this->deliveryStatus;
    }

    /**
     * Set isLocked
     *
     * @param boolean $isLocked
     *
     * @return Thread
     */
    public function setIsLocked($isLocked)
    {
        $this->isLocked = $isLocked;

        return $this;
    }

    /**
     * Get isLocked
     *
     * @return boolean
     */
    public function getIsLocked()
    {
        return $this->isLocked;
    }

    /**
     * Set isBookmarked
     *
     * @param boolean $isBookmarked
     *
     * @return Thread
     */
    public function setIsBookmarked($isBookmarked)
    {
        $this->isBookmarked = $isBookmarked;

        return $this;
    }

    /**
     * Get isBookmarked
     *
     * @return boolean
     */
    public function getIsBookmarked()
    {
        return $this->isBookmarked;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return Thread
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Thread
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
     * @return Thread
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
     * Set agentViewedAt
     *
     * @param \DateTime $agentViewedAt
     *
     * @return Thread
     */
    public function setAgentViewedAt($agentViewedAt)
    {
        $this->agentViewedAt = $agentViewedAt;

        return $this;
    }

    /**
     * Get agentViewedAt
     *
     * @return \DateTime
     */
    public function getAgentViewedAt()
    {
        return $this->agentViewedAt;
    }

    /**
     * Set customerViewedAt
     *
     * @param \DateTime $customerViewedAt
     *
     * @return Thread
     */
    public function setCustomerViewedAt($customerViewedAt)
    {
        $this->customerViewedAt = $customerViewedAt;

        return $this;
    }

    /**
     * Get customerViewedAt
     *
     * @return \DateTime
     */
    public function getCustomerViewedAt()
    {
        return $this->customerViewedAt;
    }

    /**
     * Set ticket
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\Ticket $ticket
     *
     * @return Thread
     */
    public function setTicket(\Webkul\UVDesk\CoreBundle\Entity\Ticket $ticket = null)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * Get ticket
     *
     * @return \Webkul\UVDesk\CoreBundle\Entity\Ticket
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * Set user
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\User $user
     *
     * @return Thread
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
     * Add attachments
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\Attachment $attachments
     * @return Thread
     */
    public function addAttachment(\Webkul\UVDesk\CoreBundle\Entity\Attachment $attachments)
    {
        $this->attachments[] = $attachments;

        return $this;
    }

    /**
     * Remove attachments
     *
     * @param \Webkul\TicketBundle\Entity\Attachment $attachments
     */
    public function removeAttachment(\Webkul\UVDesk\CoreBundle\Entity\Attachment $attachments)
    {
        $this->attachments->removeElement($attachments);
    }

    /**
     * Get attachments
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAttachments()
    {
        return $this->attachments;
    }
}

