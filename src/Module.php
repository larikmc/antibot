<?php

namespace Larikmc\Antibot;

use Yii;

/**
 * Antibot module definition class.
 * This module contains the AntibotController and its views.
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'Larikmc\\Antibot\\controllers'; // Изменено на Larikmc

    /**
     * ID компонента AntibotChecker
     * @var string
     */
    public $checkerComponentId = 'AntibotChecker';

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
                    'class' => 'Larikmc\\Antibot\\components\\AntibotChecker', // Изменено на Larikmc
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
     * Возвращает экземпляр компонента AntibotChecker
     * @return \Larikmc\Antibot\components\AntibotChecker // Изменено на Larikmc
     */
    public function getChecker()
    {
        return Yii::$app->get($this->checkerComponentId);
    }
}