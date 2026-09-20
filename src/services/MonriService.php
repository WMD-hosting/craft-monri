<?php

namespace wmd\craftmonri\services;

use Craft;
use craft\helpers\App;
use GuzzleHttp\Exception\GuzzleException;
use wmd\craftmonri\Monri;
use yii\base\Component;

/**
 * Signatures and the transaction API (completion, void, refund) of the WSPay
 * form protocol. The signature strings follow the WSPay integration manual
 * exactly and are proven on live stores; do not "tidy" them.
 */
class MonriService extends Component
{
    public const API_VERSION = '2.0';

    private string $shopId = '';
    private string $secretKey = '';
    private string $apiUrl = '';

    public function init(): void
    {
        parent::init();

        $settings = Monri::getInstance()->getSettings();
        $this->shopId = (string)App::parseEnv($settings->storeId);
        $this->secretKey = (string)App::parseEnv($settings->secretKey);
        $this->apiUrl = rtrim((string)App::parseEnv($settings->storeUrlApis), '/');
    }

    public function shopId(): string
    {
        return $this->shopId;
    }

    /**
     * Amount as the protocol wants it in signatures: no separators, two decimals ("10,50" → "1050").
     */
    public static function signatureAmount(float|string $amount): string
    {
        return number_format((float)str_replace(',', '.', (string)$amount), 2, '', '');
    }

    /**
     * Amount as the hosted form wants it: comma decimal separator, no thousands separator.
     */
    public static function formAmount(float|string $amount): string
    {
        return number_format((float)str_replace(',', '.', (string)$amount), 2, ',', '');
    }

    /**
     * sha512 of every value followed by the secret key.
     * Form request: ShopID, ShoppingCartID, TotalAmount.
     * Form return: ShopID, ShoppingCartID, Success, ApprovalCode.
     */
    public function calculateSignature(array $values): string
    {
        $string = '';
        foreach ($values as $value) {
            $string .= $value . $this->secretKey;
        }

        return hash('sha512', $string);
    }

    /**
     * Signature of the transaction API calls (completion, void, refund).
     */
    public function apiSignature(string $wsPayOrderId, string $stan, string $approvalCode, string $amount): string
    {
        $string = '';
        foreach ([$this->shopId . $wsPayOrderId, $stan, $approvalCode, $amount] as $value) {
            $string .= $value . $this->secretKey;
        }

        return hash('sha512', $string . $wsPayOrderId);
    }

    public function completion(string $wsPayOrderId, string $stan, string $approvalCode, string $amount): array
    {
        return $this->request('/completion', $wsPayOrderId, $stan, $approvalCode, $amount);
    }

    public function void(string $wsPayOrderId, string $stan, string $approvalCode, string $amount): array
    {
        return $this->request('/void', $wsPayOrderId, $stan, $approvalCode, $amount);
    }

    public function refund(string $wsPayOrderId, string $stan, string $approvalCode, string $amount): array
    {
        return $this->request('/refund', $wsPayOrderId, $stan, $approvalCode, $amount);
    }

    /**
     * JSON POST to the transaction API; the reply as an array, empty on failure (logged).
     */
    private function request(string $endpoint, string $wsPayOrderId, string $stan, string $approvalCode, string $amount): array
    {
        $amount = self::signatureAmount($amount);
        $payload = [
            'Version' => self::API_VERSION,
            'WsPayOrderId' => $wsPayOrderId,
            'ShopID' => $this->shopId,
            'ApprovalCode' => $approvalCode,
            'STAN' => $stan,
            'Amount' => $amount,
            'Signature' => $this->apiSignature($wsPayOrderId, $stan, $approvalCode, $amount),
        ];

        try {
            $response = Craft::createGuzzleClient(['timeout' => 20, 'connect_timeout' => 10])->post($this->apiUrl . $endpoint, [
                'json' => $payload,
                'headers' => ['Accept' => 'application/json'],
            ]);
            $decoded = json_decode((string)$response->getBody(), true);
        } catch (GuzzleException $e) {
            Craft::error("Monri {$endpoint} request failed: " . $e->getMessage(), __METHOD__);
            return [];
        }

        if (!is_array($decoded)) {
            Craft::error("Monri {$endpoint} returned unreadable JSON.", __METHOD__);
            return [];
        }

        return $decoded;
    }
}
