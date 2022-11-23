<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Utils\Mailer;

use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;

abstract class BaseConfiguration
{
    CONST TOKEN_RANGE = '0123456789';

    CONST CONFIGURATION = <<<MAILER
[[ scheme ]]://[[ user ]]:[[ pass ]]@[[ host ]][[ port ]][[ options ]]
MAILER;

    CONST TEMPLATE = <<<MAILER
            [[ id ]]: [[ configuration ]]

MAILER;

    protected $id = null;
    protected $user = null;
    protected $pass = null;

    protected $useStrictMode = false;
    protected $disableEmailDelivery = false;

    public function __construct($id = null)
    {
        $this->setId($id ?: sprintf("mailer_%s", TokenGenerator::generateToken(4, self::TOKEN_RANGE)));
    }

    abstract public function castArray();
    abstract public static function getScheme();
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

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setPass($pass)
    {
        $this->pass = $pass;
    }

    public function getPass()
    {
        return $this->pass;
    }

    public function setUseStrictMode($useStrictMode)
    {
        $this->useStrictMode = $useStrictMode;
    }

    public function getUseStrictMode()
    {
        return $this->useStrictMode;
    }

    public function setDisableEmailDelivery($disableEmailDelivery)
    {
        $this->disableEmailDelivery = $disableEmailDelivery;
    }

    public function getDisableEmailDelivery()
    {
        return $this->disableEmailDelivery;
    }
}
