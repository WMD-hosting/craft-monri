# Monri for Craft Commerce

[Monri](https://monri.com/) card payments as a Craft Commerce gateway, over
the WSPay form protocol Monri runs since acquiring WSPay: hosted card form
with installments, authorization completed from the return, void on failure,
refund from the control panel, Croatian control-panel translations.

## Requirements

Craft CMS 5.0+, Craft Commerce 5.0+, PHP 8.2+, and a Monri (WSPay) shop
account with a test shop for staging.

## Installation

```sh
composer require wmd/craft-monri
php craft plugin/install monri
```

Then **Commerce → System Settings → Gateways → New gateway**, type **Monri**.

## Settings

**Settings → Plugins → Monri**

| Setting | Notes |
|---|---|
| Shop ID, Secret key | issued with the shop; environment variables are supported |
| Payment form URL | `https://formtest.wspay.biz/authorization.aspx` while testing, `https://form.wspay.biz/authorization.aspx` in production |
| Transaction API URL | `https://test.wspay.biz/api/services/` while testing, `https://secure.wspay.biz/api/services/` in production |
| Phone field handle | address custom field sent as `CustomerPhone`, optional |
| Logo | shown next to the payment method, `craft.monri.logo` in templates |
| Fallback success / cancel URL | used when the order carries no return or cancel URL |

The return, cancel and error URLs travel with every payment request, so
nothing has to be configured on the Monri side.

## How a payment runs

1. The customer picks the Monri gateway and submits the Commerce payment form
   (`commerce/payments/pay`). The plugin posts the signed parameter set (shop,
   cart id, amount, customer data) to the hosted form. If the site sets
   Commerce's `gatewayPostRedirectTemplate`, that template renders the form.
2. The form authorizes the card and returns to the plugin. The signature is
   verified, the authorization is **completed** over the transaction API (or
   **voided** when the form reports failure), a purchase transaction is
   recorded on the order and the customer is sent to the order's return or
   cancel URL.
3. Refund from the order's Transactions tab, for the full completed amount.

Installments are configured on the shop in the Monri merchant portal; the
hosted form offers them to the customer and returns the chosen plan with the
result.

## Testing

Use the test shop credentials with the test URLs above; test cards are listed
in the WSPay integration manual.

## License

MIT. Developed by [WMD](https://wmd.hr).
