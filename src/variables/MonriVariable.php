<?php

namespace wmd\craftmonri\variables;

use Craft;
use craft\elements\Asset;
use wmd\craftmonri\Monri;

/**
 * `craft.monri` in templates.
 */
class MonriVariable
{
    /**
     * The logo asset chosen in the plugin settings, for the payment method list.
     */
    public function getLogo(): ?Asset
    {
        $ids = Monri::getInstance()->getSettings()->logoId;

        if (empty($ids)) {
            return null;
        }

        return Craft::$app->getAssets()->getAssetById((int)$ids[0]);
    }
}
