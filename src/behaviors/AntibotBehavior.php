<?php

namespace larikmc\Antibot\behaviors;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use larikmc\Antibot\components\AntibotChecker;

/**
 * AntibotBehavior attaches to controllers to perform bot checks.
 */
class AntibotBehavior extends Behavior
{
    /**
     * @var string ID компонента AntibotChecker, который будет использоваться.
     */
    public $checkerComponentId = 'antibotChecker';

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

        /** @var AntibotChecker $checker */
        $checker = Yii::$app->get($this->checkerComponentId);

        $currentRoute = $controller->uniqueId;

        $module = Yii::$app->getModule('antibot');
        if ($module) {
            // Маршрут исключения и перенаправления
            $moduleRoutes = [
                $module->id . '/antibot/verify', // Оставлено antibot/verify, если контроллер не по умолчанию
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

            $controller->redirect(['/' . $module->id . '/antibot/verify'])->send(); // Оставлено antibot/verify
            return $event->isValid = false;
        }

        return $event->isValid = true;
    }
}