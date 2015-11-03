<?php

// this file should be located in /src/YourCompany/YourBundle/Model/PaymentGateway/RobokassaModel

namespace YourCompany\YourBundle\Model\PaymentGateway;

use Bobrovnikov\RobokassaBundle\Robokassa;

class RobokassaModel
{
    private $robokassa;

    public function __construct($login, $pass1, $pass2, $isActive)
    {
        $this->robokassa = new Robokassa($login, $pass1, $pass2, $isActive);
    }

    public function init()
    {
        return $this->robokassa;
    }
}
