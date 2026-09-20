<?php

namespace wmd\craftmonri;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\commerce\services\Gateways;
use craft\events\RegisterComponentTypesEvent;
use craft\web\twig\variables\CraftVariable;
use wmd\craftmonri\gateways\MonriGateway;
use wmd\craftmonri\models\Settings;
use wmd\craftmonri\services\MonriService;
use wmd\craftmonri\variables\MonriVariable;
use yii\base\Event;

/**
 * Monri gateway for Craft Commerce, WSPay form protocol.
 *
 * @method static Monri getInstance()
 * @method Settings getSettings()
 * @property-read MonriService $monriService
 */
class Monri extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => ['monriService' => MonriService::class],
        ];
    }

    public function init(): void
    {
        parent::init();

        Event::on(
            Gateways::class,
            Gateways::EVENT_REGISTER_GATEWAY_TYPES,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = MonriGateway::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function(Event $e) {
                /** @var CraftVariable $variable */
                $variable = $e->sender;
                $variable->set('monri', MonriVariable::class);
            }
        );
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('monri/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }
}
