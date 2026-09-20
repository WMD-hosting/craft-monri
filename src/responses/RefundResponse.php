<?php

namespace wmd\craftmonri\responses;

use craft\commerce\base\RequestResponseInterface;

/**
 * Result of a refund call; `success` is decided by the gateway from the API reply.
 */
class RefundResponse implements RequestResponseInterface
{
    public function __construct(private array $data)
    {
    }

    public function isSuccessful(): bool
    {
        return !empty($this->data['success']);
    }

    public function isProcessing(): bool
    {
        return false;
    }

    public function isRedirect(): bool
    {
        return false;
    }

    public function getRedirectMethod(): string
    {
        return '';
    }

    public function getRedirectData(): array
    {
        return [];
    }

    public function getRedirectUrl(): string
    {
        return '';
    }

    public function getTransactionReference(): string
    {
        return (string)($this->data['WsPayOrderId'] ?? '');
    }

    public function getCode(): string
    {
        return (string)($this->data['ErrorCode'] ?? '');
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMessage(): string
    {
        return (string)($this->data['ErrorMessage'] ?? '');
    }

    public function redirect(): void
    {
    }
}
