<?php

namespace Larikmc\AntiBot; // Изменено на Larikmc

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
    public $controllerNamespace = 'Larikmc\\AntiBot\\controllers'; // Изменено на Larikmc

    /**
     * ID компонента AntiBotChecker
     * @var string
     */
    public $checkerComponentId = 'antiBotChecker';

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
                    'class' => 'Larikmc\\AntiBot\\components\\AntiBotChecker', // Изменено на Larikmc
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
                ],
            ]);
        }
    }

    /**
     * Возвращает экземпляр компонента AntiBotChecker
     * @return \Larikmc\AntiBot\components\AntiBotChecker // Изменено на Larikmc
     */
    public function getChecker()
    {
        return Yii::$app->get($this->checkerComponentId);
    }
}