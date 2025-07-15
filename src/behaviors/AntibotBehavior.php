<?php

namespace larikmc\Antibot\behaviors;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use larikmc\Antibot\components\AntibotChecker; // Импортируем компонент AntibotChecker

/**
 * AntibotBehavior attaches to controllers to perform bot checks.
 * AntibotBehavior прикрепляется к контроллерам для выполнения проверок на ботов.
 */
class AntibotBehavior extends Behavior
{
    /**
     * @var string ID компонента AntibotChecker, который будет использоваться.
     * ID of the AntibotChecker component to be used.
     */
    public $checkerComponentId = 'antibotChecker';

    /**
     * @var array Список маршрутов (controller-id/action-id), которые нужно исключить из проверки.
     * List of routes (controller-id/action-id) to be excluded from the check.
     */
    public $excludedRoutes = [];

    /**
     * @var array Список расширений файлов, которые следует игнорировать при проверке на бота.
     * Это полезно для статических файлов (CSS, JS, изображения), которые могут вызывать 404 ошибки.
     */
    public $excludedFileExtensions = [
        'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico',
        'woff', 'woff2', 'ttf', 'eot', 'map', 'json', 'txt', 'xml', 'html', // Добавлены распространенные типы
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        // Убедимся, что 'site/error' всегда исключен по умолчанию
        if (!in_array('site/error', $this->excludedRoutes)) {
            $this->excludedRoutes[] = 'site/error';
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            // Прикрепляемся к событию Controller::EVENT_BEFORE_ACTION,
            // чтобы выполнять проверку перед выполнением любого действия контроллера.
            // Attaches to the Controller::EVENT_BEFORE_ACTION event
            // to perform the check before any controller action is executed.
            Controller::EVENT_BEFORE_ACTION => 'checkBot',
        ];
    }

    /**
     * Метод, который будет вызываться перед каждым действием контроллера.
     * This method will be called before each controller action.
     * @param \yii\base\ActionEvent $event Событие действия.
     * @return bool Возвращает true, если действие должно быть выполнено, false для его остановки.
     * Returns true if the action should be executed, false to stop it.
     */
    public function checkBot($event)
    {
        /** @var Controller $controller */
        $controller = $this->owner; // Получаем экземпляр контроллера, к которому прикреплен Behavior

        /** @var AntibotChecker $checker */
        // Получаем экземпляр компонента AntibotChecker из приложения
        $checker = Yii::$app->get($this->checkerComponentId);

        // Получаем текущий уникальный ID маршрута (например, 'site/index', 'antibot/log/index')
        // Gets the current unique route ID (e.g., 'site/index', 'antibot/log/index')
        $currentRoute = $controller->uniqueId;

        // Получаем модуль 'antibot'
        // Gets the 'antibot' module
        $module = Yii::$app->getModule('antibot');
        if ($module) {
            // Формируем полный маршрут к действию верификации внутри модуля
            // Forms the full route to the verification action within the module
            $moduleVerifyRoute = $module->id . '/' . $module->defaultRoute . '/verify';

            // Добавляем этот конкретный маршрут в список исключенных маршрутов,
            // чтобы проверка не срабатывала на самой странице верификации.
            // Adds this specific route to the excluded routes list,
            // so the check does not trigger on the verification page itself.
            if (!in_array($moduleVerifyRoute, $this->excludedRoutes)) {
                $this->excludedRoutes[] = $moduleVerifyRoute;
            }
        }

        // Получаем путь запроса (например, 'css/print.css')
        $requestPath = Yii::$app->request->pathInfo;
        $pathParts = pathinfo($requestPath);

        // Если у пути есть расширение и оно находится в списке исключенных расширений,
        // то пропускаем проверку на бота. Это предотвращает логирование 404 для статики.
        if (isset($pathParts['extension']) && in_array(strtolower($pathParts['extension']), $this->excludedFileExtensions)) {
            return $event->isValid = true;
        }

        // Проверяем, если текущий маршрут находится в списке исключенных
        // Checks if the current route is in the excluded list
        if (in_array($currentRoute, $this->excludedRoutes)) {
            return $event->isValid = true; // Не проверять исключенные маршруты, разрешаем выполнение действия
        }

        // Всегда вызываем checkIsBot, чтобы он мог решить, логировать ли трафик
        // (включая non_suspicious, если enableAllTrafficLog включен).
        // Always call checkIsBot so it can decide whether to log traffic
        // (including non_suspicious, if enableAllTrafficLog is enabled).
        $isBot = $checker->checkIsBot();

        // Если пользователь не является ботом ИЛИ он уже помечен как человек,
        // разрешаем выполнение действия.
        // If the user is not a bot OR is already marked as human,
        // allow the action to proceed.
        if (!$isBot || $checker->checkIfHuman()) {
            return $event->isValid = true;
        }

        // Если это бот (и не помечен как человек, иначе мы бы вышли выше),
        // перенаправляем на страницу верификации.
        // If it's a bot (and not marked as human, otherwise we would have exited above),
        // redirect to the verification page.
        Yii::$app->session->set('antibot_redirect_url', Yii::$app->request->url);
        $controller->redirect(['/' . $module->id . '/' . $module->defaultRoute . '/verify'])->send();
        return $event->isValid = false; // Останавливаем выполнение текущего действия
    }
}