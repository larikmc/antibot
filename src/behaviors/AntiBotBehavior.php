<?php

namespace larikmc\AntiBot\behaviors; // Новое пространство имен

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use larikmc\AntiBot\components\AntiBotChecker; // Используем компонент

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
    public $excludedRoutes = [
        // Эти будут добавлены автоматически из конфигурации модуля
        // 'antibot/anti-bot/verify',
    ];

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
        $controller = $this->owner; // Получаем экземпляр контроллера, к которому прикреплено поведение

        /** @var AntiBotChecker $checker */
        $checker = Yii::$app->get($this->checkerComponentId); // Получаем компонент AntiBotChecker

        // Получаем текущий уникальный маршрут
        $currentRoute = $controller->uniqueId;

        // Добавляем маршруты модуля в исключения, если они не были добавлены явно
        $module = Yii::$app->getModule('antibot'); // Предполагаем, что модуль называется 'antibot'
        if ($module) {
            $moduleRoutes = [
                $module->id . '/anti-bot/verify',
            ];
            $this->excludedRoutes = array_merge($this->excludedRoutes, $moduleRoutes);
        }

        // Если текущий маршрут находится в списке исключений, пропускаем проверку на бота
        if (in_array($currentRoute, $this->excludedRoutes)) {
            return $event->isValid = true; // Продолжаем выполнение действия
        }

        // Если пользователь уже помечен как человек, пропускаем дальнейшую проверку на бота
        if ($checker->checkIfHuman()) {
            return $event->isValid = true;
        }

        // Выполняем основную проверку на бота через компонент
        if ($checker->checkIsBot()) {
            // Если определено, что это бот, сохраняем текущий URL
            Yii::$app->session->set('antibot_redirect_url', Yii::$app->request->url);

            // Перенаправляем на страницу подтверждения "Я не бот"
            // Важно: здесь мы используем Controller::redirect, поэтому нужно установить isValid = false
            $controller->redirect(['/' . $module->id . '/anti-bot/verify'])->send();
            return $event->isValid = false; // Прерываем выполнение действия
        }

        return $event->isValid = true; // Все проверки пройдены, продолжаем
    }
}