<?php

namespace Larikmc\AntiBot\behaviors; // Изменено на Larikmc

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use Larikmc\AntiBot\components\AntiBotChecker; // Изменено на Larikmc

/**
 * AntiBotBehavior attaches to controllers to perform bot checks.
 */
class AntiBotBehavior extends Behavior
{
    /**
     * @var string ID компонента AntiBotChecker, который будет использоваться.
     */
    public $checkerComponentId = 'antiBotChecker';

    /**
     * @var array Список маршрутов (controller-id/action-id), которые нужно исключить из проверки.
     */
    public $excludedRoutes = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'checkBot',
        ];
    }

    /**
     * Метод, который будет вызываться перед каждым действием контроллера.
     * @param \yii\base\ActionEvent $event
     * @return bool
     */
    public function checkBot($event)
    {
        /** @var Controller $controller */
        $controller = $this->owner;

        /** @var AntiBotChecker $checker */
        $checker = Yii::$app->get($this->checkerComponentId);

        $currentRoute = $controller->uniqueId;

        $module = Yii::$app->getModule('antibot');
        if ($module) {
            $moduleRoutes = [
                $module->id . '/anti-bot/verify',
            ];
            $this->excludedRoutes = array_merge($this->excludedRoutes, $moduleRoutes);
        }

        if (in_array($currentRoute, $this->excludedRoutes)) {
            return $event->isValid = true;
        }

        if ($checker->checkIfHuman()) {
            return $event->isValid = true;
        }

        if ($checker->checkIsBot()) {
            Yii::$app->session->set('antibot_redirect_url', Yii::$app->request->url);

            $controller->redirect(['/' . $module->id . '/anti-bot/verify'])->send();
            return $event->isValid = false;
        }

        return $event->isValid = true;
    }
}