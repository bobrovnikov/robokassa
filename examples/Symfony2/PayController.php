<?php

// this file should be located in /src/YourCompany/YourBundle/Controller

namespace YourCompany\YourBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PayController extends Controller
{
    public function payAction(Request $request)
    {
        // ... init form ...

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $formData = $form->getData();

            switch ($formData['gateway']) {
                case 'robokassa':
                    $robokassa = $this->get('model.robokassa')->init()
                        ->setOutSum($formData['amount'])
                        ->setInvId($formData['invoice'])
                        ->setDesc('Payment for hosting services')
                        ->addParam('IncCurrLabel', $formData['method'])
                        ->addParam('Culture', 'ru')
                        ->addParam('Encoding', 'utf-8')
                        ->addParam('Email', $formData['user_email'])
                        ->addCustomParam('creditId', $formData['credit'])
                        ;

                    if (isset($freeDomain)) {
                        $robokassa->addCustomParam('freeDomain', $freeDomain);
                    }

                    return $this->redirect($robokassa->getEndpointQuery());
                    break;
            }
        }

        // ... variables for template
    }

    // this controller is only accessed by Robokassa, not by user
    public function robokassaResultAction(Request $request)
    {
        $request = $request->isMethod('POST') ? $request->request : $request->query;

        $robokassa = $this->get('model.robokassa')->init();
        if (!$robokassa->isResultValid()) {
            return new Response(sprintf('`%s` is a bad signature', $request->get('SignatureValue')));
        }

        $this->get('model.admin')->getAdmin()->setPaid($request->get('InvId'));

        return new Response(sprintf('OK%s', $request->get('InvId')));
    }

    // notify user of success
    public function robokassaSuccessAction(Request $request)
    {
        $robokassa = $this->get('model.robokassa')->init();

        if (!$robokassa->isSuccessValid()) {
            return new Response(sprintf(
                'Got bad signature `%s`, expected `%s`',
                $request->get('SignatureValue'),
                $robokassa->getSuccessSignature()
            ));
        }

        $this->get('model.finance')->updateCustomerBalance();

        return $this->selectRedirectByType(
            $request->getSession()->getFlashBag(),
            $request->get($robokassa::CUSTOM_PARAMS_PREFIX . 'creditId'),
            $request->get($robokassa::CUSTOM_PARAMS_PREFIX . 'orderId'),
            $request->get($robokassa::CUSTOM_PARAMS_PREFIX . 'type'),
            $request->get($robokassa::CUSTOM_PARAMS_PREFIX . 'serviceId')
        );
    }

    // warn about failure, try payment again
    public function robokassaFailAction(Request $request)
    {
        $robokassa = $this->get('model.robokassa')->init();

        $params = [
            'status' => Constants::MERCHANT_STATUS_CANCELLED,
        ];

        foreach ($request->request->all() as $key => $value) {
            if (!in_array($key, [
                'InvId',
                'OutSum',
                'Culture',
            ])) {
                $normalizedKey = str_replace($robokassa::CUSTOM_PARAMS_PREFIX, '', $key);
                $params[$normalizedKey] = $value;
            }
        }

        return $this->redirect($this->generateUrl('pay', $params));
    }
}
