<?php

namespace wmd\craftmonri\responses;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\Transaction;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use wmd\craftmonri\Monri;
use wmd\craftmonri\services\MonriService;

/**
 * Response for the offsite flow: on purchase a POST redirect to the hosted
 * form with the signed parameters; on completion a success (the return
 * controller has already completed the authorization).
 */
class CheckoutResponse implements RequestResponseInterface
{
    public const STATUS_REDIRECT = 'redirect';
    public const STATUS_SUCCESSFUL = 'successful';

    private ?array $params = null;

    public function __construct(private Transaction $transaction, private string $status = self::STATUS_REDIRECT)
    {
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    public function isProcessing(): bool
    {
        return false;
    }

    public function isRedirect(): bool
    {
        return $this->status === self::STATUS_REDIRECT;
    }

    public function getRedirectMethod(): string
    {
        return 'POST';
    }

    public function getRedirectUrl(): string
    {
        return (string)App::parseEnv(Monri::getInstance()->getSettings()->storeUrl);
    }

    public function getRedirectData(): array
    {
        return $this->getData();
    }

    public function getTransactionReference(): string
    {
        return (string)$this->transaction->hash;
    }

    public function getCode(): string
    {
        return '';
    }

    public function getMessage(): string
    {
        return '';
    }

    /**
     * The signed parameter set for the hosted form. Only ShopID,
     * ShoppingCartID and TotalAmount enter the signature.
     */
    public function getData(): array
    {
        if ($this->params !== null) {
            return $this->params;
        }

        $settings = Monri::getInstance()->getSettings();
        $service = Monri::getInstance()->monriService;
        $order = $this->transaction->getOrder();
        $amount = (float)$this->transaction->paymentAmount;
        $actionTrigger = Craft::$app->getConfig()->getGeneral()->actionTrigger;

        $params = [
            'ShopID' => $service->shopId(),
            'ShoppingCartID' => (string)$order->id,
            'Version' => MonriService::API_VERSION,
            'TotalAmount' => MonriService::formAmount($amount),
            'ReturnURL' => UrlHelper::siteUrl("{$actionTrigger}/monri/payment/return"),
            'ReturnErrorURL' => UrlHelper::siteUrl("{$actionTrigger}/monri/payment/error"),
            'CancelURL' => UrlHelper::siteUrl("{$actionTrigger}/monri/payment/cancel"),
            'ReturnMethod' => 'POST',
            'Lang' => strtoupper(substr(Craft::$app->getSites()->getCurrentSite()->language ?: 'hr', 0, 2)),
        ];

        if (!empty($order->email)) {
            $params['CustomerEmail'] = (string)$order->email;
        }

        $params += $this->customerParams($order, $settings->phoneField);

        $params['Signature'] = $service->calculateSignature([
            $service->shopId(),
            (string)$order->id,
            MonriService::signatureAmount($amount),
        ]);

        return $this->params = $params;
    }

    /**
     * Auto-submitting form, used when the site has no gatewayPostRedirectTemplate.
     */
    public function redirect(): void
    {
        $inputs = '';
        foreach ($this->getData() as $name => $value) {
            $inputs .= Html::hiddenInput($name, (string)$value);
        }

        $url = Html::encode($this->getRedirectUrl());
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Monri</title></head><body>'
            . '<form id="monri-redirect" action="' . $url . '" method="post">' . $inputs
            . '<noscript><button type="submit">' . Html::encode(Craft::t('monri', 'Continue to payment')) . '</button></noscript>'
            . '</form><script>document.getElementById("monri-redirect").submit();</script></body></html>';

        $response = Craft::$app->getResponse();
        $response->format = \yii\web\Response::FORMAT_RAW;
        $response->content = $html;
        $response->send();
        Craft::$app->end();
    }

    /**
     * Optional Customer* fields, sanitized to the form's character rules.
     */
    private function customerParams($order, string $phoneField): array
    {
        $billing = $order->getBillingAddress();
        if (!$billing) {
            return [];
        }

        $params = [];
        $name = static fn($v) => mb_substr(preg_replace('/[^\p{L}\p{N} \-]/u', '', (string)$v), 0, 50);

        $first = trim((string)$billing->firstName);
        $last = trim((string)$billing->lastName);
        if (($first === '' || $last === '') && !empty($billing->fullName)) {
            $parts = preg_split('/\s+/', trim((string)$billing->fullName)) ?: [];
            if (count($parts) > 1) {
                $last = $last ?: (string)array_pop($parts);
                $first = $first ?: implode(' ', $parts);
            } else {
                $first = $first ?: (string)($parts[0] ?? '');
            }
        }
        if ($first !== '') {
            $params['CustomerFirstName'] = $name($first);
        }
        if ($last !== '') {
            $params['CustomerLastName'] = $name($last);
        }

        $address = trim(trim((string)$billing->addressLine1) . ' ' . trim((string)$billing->addressLine2));
        if ($address !== '') {
            $params['CustomerAddress'] = mb_substr(preg_replace('/[^\p{L}\p{N} \-\.,\(\)]/u', '', $address), 0, 100);
        }
        if (!empty($billing->locality)) {
            $params['CustomerCity'] = mb_substr(preg_replace('/[^\p{L}\p{N} \-,]/u', '', (string)$billing->locality), 0, 50);
        }
        if (!empty($billing->postalCode)) {
            $params['CustomerZIP'] = mb_substr(preg_replace('/[^0-9\-]/', '', (string)$billing->postalCode), 0, 20);
        }
        if (!empty($billing->countryCode)) {
            $params['CustomerCountry'] = strtoupper(substr((string)$billing->countryCode, 0, 2));
        }

        if ($phoneField !== '') {
            try {
                $phone = preg_replace('/[^0-9]/', '', (string)$billing->getFieldValue($phoneField));
                if ($phone !== '') {
                    $params['CustomerPhone'] = mb_substr($phone, 0, 20);
                }
            } catch (\Throwable) {
                // The configured field does not exist on this address layout.
            }
        }

        return $params;
    }
}
