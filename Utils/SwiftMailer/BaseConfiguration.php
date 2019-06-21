<?php

namespace Webkul\UVDesk\CoreBundle\Utils\SwiftMailer;

use Webkul\UVDesk\CoreBundle\Utils\TokenGenerator;

abstract class BaseConfiguration
{
    CONST TOKEN_RANGE = '0123456789';

    protected $id;
    protected $username;
    protected $password;
    protected $senderAddress;
    protected $deliveryAddresses = [];
    protected $deliveryStatus = false;

    public function __construct($id = null)
    {
        $this->setId($id ?: sprintf("mailer_%s", TokenGenerator::generateToken(4, self::TOKEN_RANGE)));
    }

    abstract public function castArray();
    abstract public static function getTransportCode();
    abstract public static function getTransportName();
    abstract public function getWritableConfigurations();
    abstract public function initializeParams(array $params);
    abstract public function resolveTransportConfigurations(array $params = []);

    protected function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setSenderAddress($senderAddress)
    {
        $this->senderAddress = $senderAddress;
    }

    public function getSenderAddress()
    {
        return $this->senderAddress;
    }

    public function setDeliveryAddresses(array $deliveryAddresses = [])
    {
        $this->deliveryAddresses = $deliveryAddresses;
    }

    public function getDeliveryAddresses()
    {
        return $this->deliveryAddresses;
    }

    public function setDeliveryStatus(bool $deliveryStatus = true)
    {
        $this->deliveryStatus = $deliveryStatus;
    }

    public function getDeliveryStatus()
    {
        return $this->deliveryStatus;
    }
}
