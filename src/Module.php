<?php

namespace larikmc\AntiBot; // Новое пространство имен

use Yii;

/**
 * AntiBot module definition class.
 * This module contains the AntiBotController and its views.
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'larikmc\AntiBot\controllers';

    /**
     * ID компонента AntiBotChecker
     * @var string
     */
    public $checkerComponentId = 'antiBotChecker';

    /**
     * URL для микросервиса ML (если используется)
     * @var string|null
     */
    public $mlMicroserviceUrl = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Настраиваем компонент, если его еще нет
        if (!Yii::$app->has($this->checkerComponentId)) {
            Yii::$app->setComponents([
                $this->checkerComponentId => [
                    'class' => 'larikmc\AntiBot\components\AntiBotChecker',
                    // Здесь можно передать конфигурацию для компонента
                    'goodBots' => [
                        'YandexImages', 'YandexVideo', 'YandexWebmaster', 'YandexMedia', 'YandexBlogs', 'YandexDirect', 'YandexBot',
                        'YandexMetrika', 'YandexRCA', 'YandexAccessibilityBot', 'YandexRenderResourcesBot', 'YandexMobileBot',
                        'YaDirectFetcher', 'Googlebot', 'vkShare', 'VKRobotRB', 'WhatsApp', 'TelegramBot', 'Pinterestbot',
                        'OdklBot', 'Mediapartners-Google', 'Google-Read-Aloud', 'AdsBot-Google', 'Chrome-Lighthouse',
                        'bingbot', 'Mail.RU_Bot',
                    ],
                    'safeRefererDomains' => [
                        'google.com', 'yandex.ru', 'yandex.kz', 'yandex.ua', 'yandex.by', 'bing.com',
                        'mail.ru', 'yahoo.com', 'facebook.com', 'vk.com', 'twitter.com', 't.me', 'pinterest.com',
                        'ok.ru', 'linkedin.com', 'reddit.com',
                    ],
                    'maxRequests' => 30,
                    'timeWindow' => 60,
                    'mlMicroserviceUrl' => $this->mlMicroserviceUrl, // Передаем URL микросервиса
                ],
            ]);
        }
    }

    /**
     * Возвращает экземпляр компонента AntiBotChecker
     * @return \larikmc\AntiBot\components\AntiBotChecker
     */
    public function getChecker()
    {
        return Yii::$app->get($this->checkerComponentId);
    }
}