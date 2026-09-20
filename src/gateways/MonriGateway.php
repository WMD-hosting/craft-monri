<?php

namespace wmd\craftmonri\gateways;

use Craft;
use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\payments\OffsitePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;
use craft\web\Response as WebResponse;
use craft\web\View;
use wmd\craftmonri\Monri;
use wmd\craftmonri\responses\CheckoutResponse;
use wmd\craftmonri\responses\RefundResponse;
use yii\base\NotSupportedException;

/**
 * Offsite gateway: the customer is sent to the hosted form with a signed POST,
 * the form returns to the plugin's return, cancel or error action, which
 * completes (captures) the authorization over the API.
 */
class MonriGateway extends BaseGateway
{
    public static function displayName(): string
    {
        return Craft::t('monri', 'Monri');
    }

    public function getPaymentFormHtml(array $params): ?string
    {
        $view = Craft::$app->getView();
        $previousMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $html = $view->renderTemplate('monri/form.twig', $params);
        $view->setTemplateMode($previousMode);

        return $html;
    }

    public function getPaymentFormModel(): BasePaymentForm
    {
        return new OffsitePaymentForm();
    }

    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        return new CheckoutResponse($transaction);
    }

    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        return new CheckoutResponse($transaction, CheckoutResponse::STATUS_SUCCESSFUL);
    }

    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        throw new NotSupportedException(Craft::t('commerce', 'Authorizing is not supported by this gateway'));
    }

    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        throw new NotSupportedException(Craft::t('commerce', 'Capturing is not supported by this gateway'));
    }

    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        throw new NotSupportedException(Craft::t('commerce', 'Authorizing is not supported by this gateway'));
    }

    /**
     * Refunds the whole completed amount; the API needs the identifiers the
     * form returned, which the successful purchase transaction stored.
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {
        $parent = $transaction->getParent();
        $snapshot = $parent ? (array)json_decode((string)$parent->response, true) : [];

        if (empty($snapshot['WsPayOrderId'])) {
            return new RefundResponse(['success' => false, 'ErrorMessage' => 'No gateway order id on the parent transaction.']);
        }

        $result = Monri::getInstance()->monriService->refund(
            (string)$snapshot['WsPayOrderId'],
            (string)($snapshot['STAN'] ?? ''),
            (string)($snapshot['ApprovalCode'] ?? ''),
            (string)($snapshot['Amount'] ?? $transaction->amount)
        );

        $ok = !empty($result['ActionSuccess']) && ($result['WsPayOrderId'] ?? null) == $snapshot['WsPayOrderId'];

        return new RefundResponse(['success' => $ok] + $result);
    }

    public function createPaymentSource(BasePaymentForm $sourceData, int $customerId): PaymentSource
    {
        throw new NotSupportedException(Craft::t('commerce', 'Payment sources are not supported by this gateway'));
    }

    public function deletePaymentSource(string $token): bool
    {
        throw new NotSupportedException(Craft::t('commerce', 'Payment sources are not supported by this gateway'));
    }

    public function processWebHook(): WebResponse
    {
        throw new NotSupportedException(Craft::t('commerce', 'Webhooks are not supported by this gateway'));
    }

    public function supportsAuthorize(): bool
    {
        return false;
    }

    public function supportsCapture(): bool
    {
        return false;
    }

    public function supportsCompleteAuthorize(): bool
    {
        return false;
    }

    public function supportsCompletePurchase(): bool
    {
        return true;
    }

    public function supportsPaymentSources(): bool
    {
        return false;
    }

    public function supportsPurchase(): bool
    {
        return true;
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function supportsPartialRefund(): bool
    {
        return false;
    }

    public function supportsPartialPayment(): bool
    {
        return false;
    }

    public function supportsWebhooks(): bool
    {
        return false;
    }
}
