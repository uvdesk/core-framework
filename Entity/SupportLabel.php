<?php

namespace Webkul\UVDesk\CoreBundle\Entity;

/**
 * SupportLabel
 */
class SupportLabel
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
    private $colorCode;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\User
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
     * @return SupportLabel
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
     * Set colorCode
     *
     * @param string $colorCode
     *
     * @return SupportLabel
     */
    public function setColorCode($colorCode)
    {
        $this->colorCode = $colorCode;

        return $this;
    }

    /**
     * Get colorCode
     *
     * @return string
     */
    public function getColorCode()
    {
        return $this->colorCode;
    }

    /**
     * Set user
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\User $user
     *
     * @return SupportLabel
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
}

