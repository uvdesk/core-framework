<?php

namespace Webkul\UVDesk\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EmailTemplatesCompany
 */
class SavedReplies
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
    private $subject;

    /**
     * @var string
     */
    private $message;

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
     * @return Savedreplies
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
     * Set subject
     *
     * @param string $subject
     * @return Savedreplies
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Savedreplies
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
     * @var integer
     */
    private $templateId;

    /**
     * Set templateId
     *
     * @param integer $templateId
     * @return Savedreplies
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;

        return $this;
    }

    /**
     * Get templateId
     *
     * @return integer 
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }
    /**
     * @var \Webkul\UserBundle\Entity\UserData
     */
    private $user;


    /**
     * Set user
     *
     * @return Savedreplies
     */
    public function setUser(\Webkul\UVDesk\CoreBundle\Entity\UserInstance $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Webkul\UserBundle\Entity\UserData 
     */
    public function getUser()
    {
        return $this->user;
    }
    /**
     * @var boolean
     */
    private $isPredefind;


    /**
     * Set isPredefind
     *
     * @param boolean $isPredefind
     * @return Savedreplies
     */
    public function setIsPredefind($isPredefind)
    {
        $this->isPredefind = $isPredefind;

        return $this;
    }

    /**
     * Get isPredefind
     *
     * @return boolean 
     */
    public function getIsPredefind()
    {
        return $this->isPredefind;
    }
    /**
     * @var string
     */
    private $messageInline;


    /**
     * Set messageInline
     *
     * @param string $messageInline
     * @return Savedreplies
     */
    public function setMessageInline($messageInline)
    {
        $this->messageInline = $messageInline;

        return $this;
    }

    /**
     * Get messageInline
     *
     * @return string 
     */
    public function getMessageInline()
    {
        return $this->messageInline;
    }
    /**
     * @var string
     */
    private $templateFor;


    /**
     * Set templateFor
     *
     * @param string $templateFor
     * @return Savedreplies
     */
    public function setTemplateFor($templateFor)
    {
        $this->templateFor = $templateFor;

        return $this;
    }

    /**
     * Get templateFor
     *
     * @return string 
     */
    public function getTemplateFor()
    {
        return $this->templateFor;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $groups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add groups
     *
     * @return Savedreplies
     */
    public function addSupportGroup(\Webkul\UVDesk\CoreBundle\Entity\SupportGroup $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \Webkul\UserBundle\Entity\UserGroup $groups
     */
    public function removeSupportGroups(\Webkul\UVDesk\CoreBundle\Entity\SupportGroup $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSupportGroups()
    {
        return $this->groups;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $teams;


    /**
     * Add teams
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportTeam $teams
     * @return EmailTemplatesCompany
     */
    public function addSupportTeam(\Webkul\UVDesk\CoreBundle\Entity\SupportTeam $teams)
    {
        $this->teams[] = $teams;

        return $this;
    }

    /**
     * Remove teams
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\SupportTeam $teams
     */
    public function removeSupportTeam(\Webkul\UVDesk\CoreBundle\Entity\SupportTeam $teams)
    {
        $this->teams->removeElement($teams);
    }

    /**
     * Get teams
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSupportTeams()
    {
        return $this->teams;
    }
}
