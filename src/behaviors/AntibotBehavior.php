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
     * @var array|callable Список маршрутов (controller-id/action-id), которые нужно исключить из проверки.
     * Может быть массивом строк или callable-функцией, которая возвращает массив строк.
     * List of routes (controller-id/action-id) to be excluded from the check.
     * Can be an array of strings or a callable that returns an array of strings.
     */
    public $excludedRoutes = [];

    /**
     * @var array Список расширений файлов, которые следует игнорировать при проверке на бота.
     * Это полезно для статических файлов (CSS, JS, изображения), которые могут вызывать 404 ошибки.
     */
    public $excludedFileExtensions = [
        'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico',
        'woff', 'woff2', 'ttf', 'eot', 'map', 'json', 'txt', 'xml', 'html',
    ];

    /**
     * @var string Маршрут для перенаправления ботов на страницу верификации.
     * По умолчанию: 'antibot/antibot/verify'.
     */
    public $verifyRoute = 'antibot/antibot/verify';

    /**
     * @var array Список действий контроллера, к которым применяется поведение.
     * Если не задано, применяется ко всем действиям.
     * Пример: ['index', 'view']
     */
    public $only = [];

    /**
     * @var array Список действий контроллера, из которых поведение исключается.
     * Пример: ['create', 'update']
     */
    public $except = [];

    /**
     * @var bool Включить/выключить логирование запросов, которые были исключены из проверки.
     * Это полезно для отладки.
     */
    public $logExcludedRequests = false;

    /**
     * @var int HTTP-статус код для перенаправления ботов.
     * По умолчанию 302 (Found). Можно использовать 301 (Moved Permanently) или другие.
     */
    public $redirectStatusCode = 302;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Определяем базовые маршруты, которые всегда должны быть исключены.
        $alwaysExcludedRoutes = [
            'site/error', // Страница ошибки 404
        ];

        // Получаем модуль 'antibot' для определения маршрута верификации.
        $module = Yii::$app->getModule('antibot');
        if ($module) {
            // Формируем полный маршрут к действию верификации внутри модуля
            // Предполагается, что actionVerify находится в AntibotController,
            // а defaultRoute модуля указывает на этот контроллер.
            // Используем свойство verifyRoute, чтобы оно могло быть переопределено.
            $verifyRouteParts = explode('/', $this->verifyRoute);
            if (count($verifyRouteParts) === 3) { // Проверяем формат 'module/controller/action'
                $moduleVerifyRoute = $verifyRouteParts[0] . '/' . $verifyRouteParts[1] . '/' . $verifyRouteParts[2];
            } else {
                // Если verifyRoute задан некорректно, используем defaultRoute модуля
                $moduleVerifyRoute = $module->id . '/' . $module->defaultRoute . '/verify';
            }
            $alwaysExcludedRoutes[] = $moduleVerifyRoute;
        }

        // Если $excludedRoutes является callable, вызываем его для получения маршрутов.
        $userExcludedRoutes = is_callable($this->excludedRoutes)
            ? call_user_func($this->excludedRoutes)
            : $this->excludedRoutes;

        // Объединяем пользовательские исключенные маршруты с всегда исключенными,
        // предотвращая дублирование.
        $this->excludedRoutes = array_unique(array_merge($userExcludedRoutes, $alwaysExcludedRoutes));
    }

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
     * @param \yii\base\ActionEvent $event Событие действия.
     * @return bool Возвращает true, если действие должно быть выполнено, false для его остановки.
     */
    public function checkBot($event)
    {
        /** @var Controller $controller */
        $controller = $this->owner;

        /** @var AntibotChecker $checker */
        $checker = Yii::$app->get($this->checkerComponentId);

        // Получаем текущий уникальный ID маршрута
        $currentRoute = $controller->uniqueId;

        // --- Проверка на консольные запросы ---
        if (Yii::$app->request->isConsoleRequest) {
            if ($this->logExcludedRequests) {
                Yii::info("AntibotBehavior: Request excluded (Console Request) - Route: {$currentRoute}", __METHOD__);
            }
            return $event->isValid = true;
        }

        // --- Проверка на AJAX-запросы ---
        if (Yii::$app->request->isAjax) {
            if ($this->logExcludedRequests) {
                Yii::info("AntibotBehavior: Request excluded (AJAX Request) - Route: {$currentRoute}", __METHOD__);
            }
            return $event->isValid = true;
        }

        // --- Проверка на статические файлы по расширению ---
        $requestPath = Yii::$app->request->pathInfo;
        $pathParts = pathinfo($requestPath);
        if (isset($pathParts['extension']) && in_array(strtolower($pathParts['extension']), $this->excludedFileExtensions)) {
            if ($this->logExcludedRequests) {
                Yii::info("AntibotBehavior: Request excluded (Static File) - Path: {$requestPath}", __METHOD__);
            }
            return $event->isValid = true;
        }

        // Проверяем, применимо ли поведение к текущему действию контроллера (only/except)
        $actionId = $controller->action->id;
        if (!empty($this->only) && !in_array($actionId, $this->only)) {
            if ($this->logExcludedRequests) {
                Yii::info("AntibotBehavior: Request excluded (Not in 'only' list) - Action: {$actionId}", __METHOD__);
            }
            return $event->isValid = true;
        }
        if (!empty($this->except) && in_array($actionId, $this->except)) {
            if ($this->logExcludedRequests) {
                Yii::info("AntibotBehavior: Request excluded (In 'except' list) - Action: {$actionId}", __METHOD__);
            }
            return $event->isValid = true;
        }

        // Проверяем, если текущий маршрут находится в списке исключенных
        if (in_array($currentRoute, $this->excludedRoutes)) {
            if ($this->logExcludedRequests) {
                Yii::info("AntibotBehavior: Request excluded (Excluded Route) - Route: {$currentRoute}", __METHOD__);
            }
            return $event->isValid = true;
        }

        // Всегда вызываем checkIsBot, чтобы он мог решить, логировать ли трафик
        // (включая non_suspicious, если enableAllTrafficLog включен).
        $isBot = $checker->checkIsBot();

        // Если пользователь не является ботом ИЛИ он уже помечен как человек,
        // разрешаем выполнение действия.
        if (!$isBot || $checker->checkIfHuman()) {
            return $event->isValid = true;
        }

        // Если это бот (и не помечен как человек, иначе мы бы вышли выше),
        // перенаправляем на страницу верификации.
        Yii::$app->session->set('antibot_redirect_url', Yii::$app->request->url);
        // Используем настроенный verifyRoute и redirectStatusCode для перенаправления
        $controller->redirect(['/' . $this->verifyRoute], $this->redirectStatusCode)->send();
        return $event->isValid = false; // Останавливаем выполнение текущего действия
    }
}