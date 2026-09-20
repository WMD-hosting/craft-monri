<?php

namespace wmd\craftmonri\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use wmd\craftmonri\Monri;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Where the hosted form sends the customer back.
 *
 * Return URL: actions/monri/payment/return  (authorized: completed over the API, or voided)
 * Cancel URL: actions/monri/payment/cancel
 * Error URL:  actions/monri/payment/error
 */
class PaymentController extends Controller
{
    protected array|int|bool $allowAnonymous = true;
    public $enableCsrfValidation = false;

    /**
     * The form authorized the card. Complete the authorization over the API
     * and record the purchase; if the form reports failure, void it.
     */
    public function actionReturn(): Response
    {
        $request = Craft::$app->getRequest();
        $service = Monri::getInstance()->monriService;

        $orderId = (string)$request->getRequiredParam('ShoppingCartID');
        $success = (string)$request->getRequiredParam('Success');
        $approvalCode = (string)$request->getRequiredParam('ApprovalCode');
        $signature = (string)$request->getRequiredParam('Signature');
        $wsPayOrderId = (string)$request->getRequiredParam('WsPayOrderId');
        $stan = (string)$request->getRequiredParam('STAN');
        $amount = (string)$request->getRequiredParam('Amount');

        $order = $this->order($orderId);
        $this->verify($service->calculateSignature([$service->shopId(), $orderId, $success, $approvalCode]), $signature);

        $params = $request->getIsPost() ? $request->getBodyParams() : $request->getQueryParams();

        if ($success !== '1') {
            $void = $service->void($wsPayOrderId, $stan, $approvalCode, $amount);
            $this->record($order, $params + $void, TransactionRecord::STATUS_FAILED, $approvalCode);

            return $this->redirect($this->cancelUrl($order));
        }

        $completion = $service->completion($wsPayOrderId, $stan, $approvalCode, $amount);
        $completed = !empty($completion['ActionSuccess']) && ($completion['WsPayOrderId'] ?? null) == $wsPayOrderId;

        if (!$completed) {
            Craft::error("Monri completion failed for order {$order->id}: " . json_encode($completion), __METHOD__);
            $this->record($order, $params + $completion, TransactionRecord::STATUS_FAILED, $approvalCode);

            return $this->redirect($this->cancelUrl($order));
        }

        $this->record($order, $params + $completion, TransactionRecord::STATUS_SUCCESS, $approvalCode);

        return $this->redirect($this->returnUrl($order));
    }

    /**
     * The customer cancelled on the hosted form.
     */
    public function actionCancel(): Response
    {
        return $this->failed();
    }

    /**
     * The hosted form reported an error.
     */
    public function actionError(): Response
    {
        return $this->failed();
    }

    private function failed(): Response
    {
        $request = Craft::$app->getRequest();
        $service = Monri::getInstance()->monriService;

        $orderId = (string)$request->getRequiredParam('ShoppingCartID');
        $success = (string)$request->getRequiredParam('Success');
        $approvalCode = (string)$request->getRequiredParam('ApprovalCode');
        $signature = (string)$request->getRequiredParam('Signature');

        $order = $this->order($orderId);
        $this->verify($service->calculateSignature([$service->shopId(), $orderId, $success, $approvalCode]), $signature);

        $params = $request->getIsPost() ? $request->getBodyParams() : $request->getQueryParams();
        $this->record($order, $params, TransactionRecord::STATUS_FAILED, $approvalCode);

        return $this->redirect($this->cancelUrl($order));
    }

    private function order(string $id): Order
    {
        $order = ctype_digit($id) ? Order::find()->id((int)$id)->one() : null;

        if (!$order) {
            throw new NotFoundHttpException('Order not found.');
        }

        return $order;
    }

    private function verify(string $expected, string $given): void
    {
        if ($given === '' || !hash_equals($expected, $given)) {
            throw new BadRequestHttpException('Monri signature does not match.');
        }
    }

    /**
     * Close the pending purchase transaction Commerce opened before the
     * redirect with a child transaction carrying the gateway's answer.
     */
    private function record(Order $order, array $response, string $status, string $reference): void
    {
        $transactions = Commerce::getInstance()->getTransactions();
        $parent = $order->getLastTransaction();

        $transaction = $transactions->createTransaction($order, $parent);
        $transaction->type = TransactionRecord::TYPE_PURCHASE;
        $transaction->status = $status;
        $transaction->response = $response;
        $transaction->reference = $reference !== '' ? $reference : (string)($parent?->reference ?? '');
        $transaction->message = (string)($response['ErrorMessage'] ?? $status);

        if ($parent) {
            $transaction->amount = $parent->amount;
            $transaction->paymentAmount = $parent->paymentAmount;
            $transaction->currency = $parent->currency;
            $transaction->paymentCurrency = $parent->paymentCurrency;
        }

        $transactions->saveTransaction($transaction);
        $order->updateOrderPaidInformation();
    }

    private function returnUrl(Order $order): string
    {
        $url = (string)$order->returnUrl;

        if ($url === '' || str_contains($url, '/actions/') || str_contains($url, 'sprig-core/components/render')) {
            $url = (string)App::parseEnv(Monri::getInstance()->getSettings()->successUrl);
        }

        return $url !== '' ? $url : UrlHelper::siteUrl();
    }

    private function cancelUrl(Order $order): string
    {
        $url = (string)$order->cancelUrl;

        if ($url === '') {
            $url = (string)App::parseEnv(Monri::getInstance()->getSettings()->failUrl);
        }

        return $url !== '' ? $url : UrlHelper::siteUrl();
    }
}
