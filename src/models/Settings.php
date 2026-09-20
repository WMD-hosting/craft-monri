<?php

namespace wmd\craftmonri\models;

use craft\base\Model;

/**
 * Plugin settings. Secrets accept environment variables (`$MONRI_SECRET_KEY`).
 */
class Settings extends Model
{
    /** @var string Shop ID issued by Monri. */
    public string $storeId = '';

    /** @var string Secret key issued by Monri. */
    public string $secretKey = '';

    /** @var string Hosted form URL: formtest.wspay.biz for testing, form.wspay.biz in production. */
    public string $storeUrl = 'https://formtest.wspay.biz/authorization.aspx';

    /** @var string Transaction API base URL: test.wspay.biz for testing, secure.wspay.biz in production. */
    public string $storeUrlApis = 'https://test.wspay.biz/api/services/';

    /** @var string Handle of the address field holding the customer's phone number, if any. */
    public string $phoneField = '';

    /** @var string Where the customer lands after a successful payment when the order has no return URL. */
    public string $successUrl = '';

    /** @var string Where the customer lands after a failed or cancelled payment when the order has no cancel URL. */
    public string $failUrl = '';

    /** @var array Asset ID of the logo shown next to the payment method. */
    public array $logoId = [];

    public function rules(): array
    {
        return [
            [['storeId', 'secretKey', 'storeUrl', 'storeUrlApis'], 'required'],
        ];
    }
}
