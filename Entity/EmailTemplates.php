<?php

namespace Webkul\UVDesk\CoreBundle\Entity;

/**
 * EmailTemplates
 */
class EmailTemplates
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
     * @var string
     */
    private $templateType;

    /**
     * @var boolean
     */
    private $isPredefined = true;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\UserInstance
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
     * Set name
     *
     * @param string $name
     *
     * @return EmailTemplates
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
     *
     * @return EmailTemplates
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
     *
     * @return EmailTemplates
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
     * Set templateType
     *
     * @param string $templateType
     *
     * @return EmailTemplates
     */
    public function setTemplateType($templateType)
    {
        $this->templateType = $templateType;

        return $this;
    }

    /**
     * Get templateType
     *
     * @return string
     */
    public function getTemplateType()
    {
        return $this->templateType;
    }

    /**
     * Set isPredefined
     *
     * @param boolean $isPredefined
     *
     * @return EmailTemplates
     */
    public function setIsPredefined($isPredefined)
    {
        $this->isPredefined = $isPredefined;

        return $this;
    }

    /**
     * Get isPredefined
     *
     * @return boolean
     */
    public function getIsPredefined()
    {
        return $this->isPredefined;
    }

    /**
     * Set user
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\UserInstance $user
     *
     * @return EmailTemplates
     */
    public function setUser(\Webkul\UVDesk\CoreBundle\Entity\UserInstance $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Webkul\UVDesk\CoreBundle\Entity\UserInstance
     */
    public function getUser()
    {
        return $this->user;
    }
}

