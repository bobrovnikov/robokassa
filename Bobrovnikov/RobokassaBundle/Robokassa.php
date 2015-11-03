<?php

namespace Bobrovnikov\RobokassaBundle;

/*
 * Robokassa payment processing class
 * Based on docs: http://robokassa.ru/en/Doc/En/Interface.aspx
 *
 * (c) Denis Bobrovnikov <denis.bobrovnikov@gmail.com>
 */

class Robokassa
{
    const ENDPOINT_TEST = 'http://test.robokassa.ru/Index.aspx';
    const ENDPOINT_PROD = 'https://merchant.roboxchange.com/Index.aspx';
    const CUSTOM_PARAMS_PREFIX = 'Shp_';

    private $request;
    private $login;
    private $pass1;
    private $pass2;
    private $endpointAction;
    private $params = array();
    private $customParams = array();

    /**
     * @param string $login
     * @param string $pass1
     * @param string $pass2
     * @param boolean $isActive
     */
    public function __construct($login, $pass1, $pass2, $isActive = false)
    {
        $this->request = $_REQUEST;
        $this->login = $login;
        $this->addParam('MerchantLogin', $login);
        $this->pass1 = $pass1;
        $this->pass2 = $pass2;
        $this->endpointAction = $isActive ? self::ENDPOINT_PROD : self::ENDPOINT_TEST;
    }

    /**
     * @param mixed $sum
     * @return $this
     */
    public function setOutSum($sum)
    {
        $this->addParam('OutSum', sprintf('%0.2f', $sum));

        return $this;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setInvId($id)
    {
        $this->addParam('InvId', $id);

        return $this;
    }

    /**
     * @param string $desc
     * @return $this
     */
    public function setDesc($desc)
    {
        $this->addParam('Desc', $desc);

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addParam($key, $value)
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addCustomParam($key, $value)
    {
        $this->customParams[self::CUSTOM_PARAMS_PREFIX . $key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getParam($key)
    {
        return $this->params[$key];
    }

    /**
     * @return string
     */
    public function getEndpointAction()
    {
        return $this->endpointAction;
    }

    /**
     * @return string
     */
    public function getEndpointQuery()
    {
        return $this->getEndpointAction() . '?' . http_build_query($this->getAllParams());
    }

    /**
     * @return array
     */
    public function getAllParams()
    {
        $this->addParam('SignatureValue', $this->getSignature());

        return array_merge($this->params, $this->customParams);
    }

    /**
     * @return boolean
     */
    public function isResultValid()
    {
        return $this->isSignatureValid($this->pass2);
    }

    /**
     * @return boolean
     */
    public function isSuccessValid()
    {
        return $this->isSignatureValid($this->pass1);
    }

    /**
     * @return string
     */
    public function getSuccessSignature()
    {
        $signatureArray = array(
            $this->request['OutSum'],
            $this->request['InvId'],
            $this->pass1,
        );
        foreach ($this->request as $key => $value) {
            if (strrpos($key, self::CUSTOM_PARAMS_PREFIX, -strlen($key)) !== false) {
                $signatureArray[] = $key . '=' . $value;
            }
        }

        return strtoupper(md5(join(':', $signatureArray)));
    }

    /**
     * @return string
     */
    private function getSignature()
    {
        $params = array(
            $this->getParam('MerchantLogin'),
            $this->getParam('OutSum'),
            $this->getParam('InvId'),
            $this->pass1,
        );

        if (count($this->customParams)) {
            uksort($this->customParams, 'strcasecmp'); // alphabetic case-insensitive order is important
            $params[] = urldecode(http_build_query($this->customParams, '', ':'));
        }

        return md5(join(':', $params));
    }

    /**
     * @param $pass
     * @return boolean
     */
    private function isSignatureValid($pass)
    {
        $signatureArray = array(
            $this->request['OutSum'],
            $this->request['InvId'],
            $pass,
        );
        foreach ($this->request as $key => $value) {
            if (strrpos($key, self::CUSTOM_PARAMS_PREFIX, -strlen($key)) !== false) {
                $signatureArray[] = $key . '=' . $value;
            }
        }
        $localSignature = md5(join(':', $signatureArray));

        return strtoupper($localSignature) === strtoupper($this->request['SignatureValue']);
    }
}
