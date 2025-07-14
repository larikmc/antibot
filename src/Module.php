<?php

namespace larikmc\Antibot; // Убедитесь, что пространство имен соответствует вашему вендору (larikmc)

use Yii;
use larikmc\Antibot\components\AntibotChecker; // Импортируем класс AntibotChecker

/**
 * Antibot module definition class.
 * Этот модуль содержит AntibotController и его представления.
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'larikmc\\Antibot\\controllers'; // Убедитесь, что пространство имен контроллеров соответствует

    /**
     * @var string The default route for this module.
     * Устанавливаем контроллер по умолчанию для модуля.
     * Если установлен 'antibot', то URL /antibot/ будет маршрутизироваться на AntibotController.
     * Если не установлен, то по умолчанию будет 'default'.
     */
    public $defaultRoute = 'antibot';

    /**
     * @var string ID компонента AntibotChecker, который будет использоваться модулем.
     * По умолчанию 'antibotChecker'.
     */
    public $checkerComponentId = 'antibotChecker';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // Настраиваем компонент AntibotChecker, если он еще не был настроен в конфигурации приложения.
        // Это позволяет модулю работать "из коробки" с дефолтными настройками,
        // но также позволяет переопределять их в main.php.
        if (!Yii::$app->has($this->checkerComponentId)) {
            Yii::$app->setComponents([
                $this->checkerComponentId => [
                    'class' => 'larikmc\\Antibot\\components\\AntibotChecker',
                    // Здесь больше НЕ указываются goodBots, safeRefererDomains и другие параметры.
                    // Они будут взяты из дефолтных значений, определенных в самом AntibotChecker.php,
                    // или переопределены в frontend/config/main.php.
                ],
            ]);
        }
    }

    /**
     * Возвращает экземпляр компонента AntibotChecker.
     *
     * @return AntibotChecker Компонент AntibotChecker.
     */
    public function getChecker(): AntibotChecker
    {
        return Yii::$app->get($this->checkerComponentId);
    }
}