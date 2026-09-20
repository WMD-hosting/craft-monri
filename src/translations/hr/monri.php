<?php

/**
 * Croatian control-panel strings.
 */
return [
    'Monri' => 'Monri',
    'Continue to payment' => 'Nastavi na plaćanje',
    'You will be redirected to Monri to complete the payment.' => 'Bit ćete preusmjereni na Monri za dovršetak plaćanja.',
    'Shop' => 'Trgovina',
    'Shop ID' => 'Shop ID',
    'Secret key' => 'Tajni ključ',
    'Issued with the shop. Prefer an environment variable, e.g. $MONRI_SECRET_KEY.' => 'Izdaje se uz trgovinu. Preporuka je varijabla okoline, npr. $MONRI_SECRET_KEY.',
    'Payment form URL' => 'URL forme za plaćanje',
    'https://formtest.wspay.biz/authorization.aspx for testing, https://form.wspay.biz/authorization.aspx for production.' => 'https://formtest.wspay.biz/authorization.aspx za testiranje, https://form.wspay.biz/authorization.aspx za produkciju.',
    'Transaction API URL' => 'URL transakcijskog API-ja',
    'https://test.wspay.biz/api/services/ for testing, https://secure.wspay.biz/api/services/ for production. Used for completion, void and refund.' => 'https://test.wspay.biz/api/services/ za testiranje, https://secure.wspay.biz/api/services/ za produkciju. Koristi se za completion, void i povrat.',
    'Storefront' => 'Web trgovina',
    'Phone field handle' => 'Handle polja za telefon',
    'Handle of the custom field on addresses that holds the phone number, sent as CustomerPhone. Leave empty to skip.' => 'Handle prilagođenog polja na adresama s brojem telefona, šalje se kao CustomerPhone. Prazno preskače.',
    'Logo' => 'Logo',
    'Shown next to the payment method; available in templates as craft.monri.logo.' => 'Prikazuje se uz način plaćanja; u predlošcima dostupan kao craft.monri.logo.',
    'Fallback success URL' => 'Rezervni URL uspjeha',
    'Used when the order has no return URL.' => 'Koristi se kad narudžba nema return URL.',
    'Fallback cancel URL' => 'Rezervni URL otkaza',
    'Used when the order has no cancel URL.' => 'Koristi se kad narudžba nema cancel URL.',
    'Return URLs' => 'Povratni URL-ovi',
    'The plugin sends these with every payment, so nothing has to be configured on the Monri side. Listed for reference.' => 'Plugin ih šalje uz svako plaćanje, pa na Monri strani nema što podešavati. Navedeni radi uvida.',
    'Return URL' => 'Return URL',
    'Cancel URL' => 'Cancel URL',
    'Error URL' => 'Error URL',
];
