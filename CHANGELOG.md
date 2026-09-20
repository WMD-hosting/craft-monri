# Release Notes for Monri

## 1.0.0 - 2026-09-20

First public release, after two years in production on Croatian stores as the WSPay gateway.

- Hosted card form (WSPay form protocol) for Craft Commerce 5 with signed POST redirect and verified return.
- Authorization completed over the transaction API on return, voided when the form reports failure.
- Refund from the order's Transactions tab.
- Settings accept environment variables for the shop ID and secret key.
- Croatian translations for the control panel.
