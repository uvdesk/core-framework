<?php

namespace Webkul\UVDesk\CoreBundle\Entity;

/**
 * TicketRating
 */
class TicketRating
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $stars = 0;

    /**
     * @var string
     */
    private $feedback;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\Ticket
     */
    private $ticket;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\User
     */
    private $customer;


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
     * Set stars
     *
     * @param integer $stars
     *
     * @return TicketRating
     */
    public function setStars($stars)
    {
        $this->stars = $stars;

        return $this;
    }

    /**
     * Get stars
     *
     * @return integer
     */
    public function getStars()
    {
        return $this->stars;
    }

    /**
     * Set feedback
     *
     * @param string $feedback
     *
     * @return TicketRating
     */
    public function setFeedback($feedback)
    {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * Get feedback
     *
     * @return string
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return TicketRating
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
     * Set ticket
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\Ticket $ticket
     *
     * @return TicketRating
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
     * Set customer
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\User $customer
     *
     * @return TicketRating
     */
    public function setCustomer(\Webkul\UVDesk\CoreBundle\Entity\User $customer = null)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get customer
     *
     * @return \Webkul\UVDesk\CoreBundle\Entity\User
     */
    public function getCustomer()
    {
        return $this->customer;
    }
}

