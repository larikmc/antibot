# larikmc/yii2-antibot

Расширение Yii2 для обнаружения и блокировки ботов, а также логирования подозрительной активности и всего трафика.

## Содержание

* [Описание](#описание)
* [Установка](#установка)
    * [Применение миграций](#применение-миграций)
* [Конфигурация](#конфигурация)
    * [1. Настройка компонента `antibotChecker`](#1-настройка-компонента-antibotchecker)
    * [2. Настройка модуля `antibot` (для просмотра логов в админ-панели)](#2-настройка-модуля-antibot-для-просмотра-логов-в-админ-панели)
    * [3. Применение поведения `AntibotBehavior` к контроллерам](#3-применение-поведения-antibotbehavior-к-контроллерам)
    * [4. Создание страницы верификации](#4-создание-страницы-верификации)
* [Использование](#использование)
    * [Просмотр логов](#просмотр-логов)
* [Настройки `AntibotChecker`](#настройки-antibotchecker)
* [Настройки `AntibotBehavior`](#настройки-antibotbehavior)
* [Структура базы данных](#структура-базы-данных)
* [Лицензия](#лицензия)

## Описание

`yii2-antibot` предоставляет набор инструментов для защиты вашего веб-приложения на Yii2 от нежелательного трафика, спам-ботов и вредоносных запросов. Оно включает в себя компонент для проверки запросов, поведение для автоматического применения этих проверок к контроллерам и модуль для просмотра и управления логами активности ботов.

**Основные возможности:**

* Обнаружение известных "хороших" ботов (поисковые системы, социальные сети и т.д.).
* Проверка на пустые или подозрительно короткие User-Agent.
* Проверка Referer-заголовка на пустоту или подозрительные домены.
* Ограничение частоты запросов (rate limiting) по IP-адресу.
* Перенаправление подозрительных пользователей на страницу верификации ("Я не робот").
* Логирование всех подозрительных запросов.
* Опциональное логирование всего трафика (включая "человеческие" посещения).
* Модуль для удобного просмотра и управления логами через административную панель.
* Исключение статических файлов, AJAX-запросов и консольных команд из проверки.
* Определение и логирование операционной системы посетителя.

## Установка

Предпочтительный способ установки этого расширения — через [Composer](https://getcomposer.org/download/).

Выполните команду:

```bash
composer require "larikmc/yii2-antibot:*"
```

### Применение миграций

После установки расширения необходимо создать таблицу `antibot` в вашей базе данных для хранения логов.

Выполните команду миграции:

```bash
php yii migrate --migrationPath=@larikmc/Antibot/migrations
```

## Конфигурация

Для использования расширения необходимо настроить его в конфигурации вашего Yii2 приложения (обычно `frontend/config/main.php` и/или `backend/config/main.php`).

### 1. Настройка компонента `antibotChecker`

Откройте файл `frontend/config/main.php` (или `common/config/main.php`, если оно используется для всего приложения).

1. Добавьте в секцию `components` запись:

    ```php
    'components' => [
        // ... другие ваши компоненты ...
        'antibotChecker' => [
            'class' => 'larikmc\\Antibot\\components\\AntibotChecker',
            'enableRateLimit' => true,    // Включить ограничение частоты запросов
            'maxRequests' => 40,          // Максимальное количество запросов за timeWindow
            'timeWindow' => 60,           // Временное окно в секундах (60 секунд = 1 минута)
            'enableEmptyUaCheck' => true, // Включить проверку на пустой/короткий User-Agent
            'enableRefererCheck' => true, // Включить проверку Referer
            'enableHumanLog' => true,     // Логировать, когда пользователь помечается как человек
            'enableAllTrafficLog' => false, // Логировать весь трафик (true/false)
            'enableGoodBotLog' => true,   // Логировать "хороших" ботов
            // 'goodBots' => [...],          // Можно переопределить список хороших ботов
            // 'safeRefererDomains' => [...], // Можно переопределить список безопасных доменов
        ],
    ],
    ```

### 2. Настройка модуля `antibot` (для просмотра логов в админ-панели)

Откройте файл `backend/config/main.php`.

1. Добавьте в секцию `modules` запись:

    ```php
    'modules' => [
        // ... другие ваши модули ...
        'antibot' => [
            'class' => 'larikmc\\Antibot\\Module',
            'defaultRoute' => 'log', // Устанавливает LogController как контроллер по умолчанию
                                     // Доступ к логам будет по URL: /antibot/
            'checkerComponentId' => 'antibotChecker', // ID компонента AntibotChecker
        ],
    ],
    ```

### 3. Применение поведения `AntibotBehavior` к контроллерам

Прикрепите `AntibotBehavior` к вашим контроллерам. Обычно это делается в базовом контроллере (например, `frontend/controllers/SiteController.php` или `backend/controllers/SiteController.php`, или в `common/controllers/BaseController.php`, если у вас такой есть).

1. Откройте файл вашего контроллера (например, `frontend/controllers/SiteController.php`).
2. Добавьте `use larikmc\Antibot\behaviors\AntibotBehavior;` в начало файла.
3. Добавьте запись в метод `behaviors()`:

    ```php
    // frontend/controllers/SiteController.php или ваш базовый контроллер
    namespace frontend\controllers;

    use Yii;
    use yii\web\Controller;
    use larikmc\Antibot\behaviors\AntibotBehavior; // Импортируем поведение

    class SiteController extends Controller
    {
        /**
         * @inheritdoc
         */
        public function behaviors()
        {
            return [
                'antibot' => [
                    'class' => AntibotBehavior::class,
                    'checkerComponentId' => 'antibotChecker', // ID компонента AntibotChecker
                    'verifyRoute' => 'antibot/antibot/verify', // Маршрут к странице верификации (модуль/контроллер/действие)
                    'redirectStatusCode' => 302, // HTTP-статус код для перенаправления ботов

                    // Опции исключения:
                    'excludedRoutes' => [
                        // 'site/contact', // Пример: исключить страницу контактов
                        // 'api/v1/data',  // Пример: исключить API-маршруты
                        // Можно использовать callable для динамического исключения:
                        /*
                        function() {
                            if (Yii::$app->user->isGuest) {
                                return ['site/login']; // Исключить страницу входа для гостей
                            }
                            return [];
                        }
                        */
                    ],
                    'excludedFileExtensions' => [
                        'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico',
                        'woff', 'woff2', 'ttf', 'eot', 'map', 'json', 'txt', 'xml', 'html',
                    ],
                    'only' => [],   // Применять только к этим действиям (пусто = ко всем)
                    'except' => [], // Исключить из этих действий
                    'logExcludedRequests' => false, // Логировать запросы, исключенные из проверки (для отладки)
                ],
                // ... другие поведения ...
            ];
        }

        // ... ваши действия контроллера ...
    }
    ```

### 4. Создание страницы верификации

Вам понадобится создать представление для страницы верификации, на которую будут перенаправляться боты.

1. Убедитесь, что контроллер `larikmc/Antibot/controllers/AntibotController.php` содержит действие `actionVerify()`.
2. Создайте файл представления `larikmc/Antibot/views/antibot/verify.php` со следующим содержимым:

    ```php
    <?php
    use yii\helpers\Html;
    use yii\helpers\Url;

    /** @var yii\web\View $this */

    $this->title = 'Подтвердите, что вы не робот';
    ?>
    <div class="antibot-verify">
        <h1><?= Html::encode($this->title) ?></h1>

        <p>
            Пожалуйста, подтвердите, что вы не робот, чтобы продолжить использование сайта.
        </p>

        <?= Html::beginForm(['/' . Yii::$app->controller->module->id . '/' . Yii::$app->controller->id . '/verify'], 'post', ['id' => 'antibot-form']) ?>
            <?= Html::hiddenInput('not_bot_button', 1) ?>
            <?= Html::submitButton('Я не робот', ['class' => 'btn btn-primary']) ?>
        <?= Html::endForm() ?>

        <script>
            document.getElementById('antibot-form').addEventListener('submit', function(event) {
                event.preventDefault(); // Предотвращаем стандартную отправку формы

                const form = event.target;
                const formData = new FormData(form);

                // Получаем CSRF-токен для frontend-приложения Yii2
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfTokenMeta && csrfTokenMeta.content) {
                    // Имя поля CSRF-токена может отличаться в зависимости от вашей конфигурации CSRF.
                    // По умолчанию для frontend это _csrf-frontend, для backend - _csrf-backend.
                    // Убедитесь, что это соответствует вашему приложению.
                    formData.append('_csrf-frontend', csrfTokenMeta.content);
                }

                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        // Обработка HTTP ошибок (например, 404, 500)
                        return response.text().then(text => { throw new Error(text || response.statusText); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success' && data.redirect_url) {
                        window.location.href = data.redirect_url; // Перенаправляем пользователя
                    } else {
                        alert('Ошибка верификации: ' + (data.message || 'Неизвестная ошибка.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка сети или сервера: ' + error.message);
                });
            });
        </script>
    </div>
    ```

## Использование

После настройки, расширение будет автоматически проверять входящие запросы согласно вашим правилам.

### Просмотр логов

Для просмотра логов активности ботов перейдите в административную панель вашего приложения по маршруту:

`http://backend.your-domain.com/antibot/log/index`

(Если вы установили `defaultRoute` модуля на `log`, то достаточно `http://backend.your-domain.com/antibot/`)

## Настройки `AntibotChecker`

Вы можете настроить поведение `AntibotChecker` через его свойства в конфигурации компонента:

* `$goodBots` (array): Список подстрок User-Agent для известных "хороших" ботов.
* `$safeRefererDomains` (array): Список доменов, с которых реферер считается безопасным.
* `$maxRequests` (int): Максимальное количество запросов с одного IP в течение `$timeWindow`.
* `$timeWindow` (int): Временное окно в секундах для ограничения частоты запросов.
* `$enableRateLimit` (bool): Включить/выключить ограничение частоты запросов.
* `$enableEmptyUaCheck` (bool): Включить/выключить проверку на пустой/короткий User-Agent.
* `$enableRefererCheck` (bool): Включить/выключить проверку Referer.
* `$enableHumanLog` (bool): Включить/выключить логирование, когда пользователь помечается как человек (`human_identified`).
* `$enableAllTrafficLog` (bool): Включить/выключить логирование всех запросов, которые не были идентифицированы как боты (`non_suspicious`). По умолчанию `false`.
* `$enableGoodBotLog` (bool): Включить/выключить логирование "хороших" ботов (`good_bot`). По умолчанию `true`.

## Настройки `AntibotBehavior`

Поведение `AntibotBehavior` также имеет настраиваемые свойства:

* `$checkerComponentId` (string): ID компонента `AntibotChecker` в конфигурации приложения.
* `$excludedRoutes` (array|callable): Маршруты, которые полностью исключаются из проверки. Может быть массивом строк или callable-функцией.
* `$excludedFileExtensions` (array): Расширения файлов, запросы к которым будут игнорироваться (например, для статики).
* `$verifyRoute` (string): Маршрут, на который перенаправляется бот для верификации.
* `$only` (array): Список действий контроллера, к которым **только** применяется поведение.
* `$except` (array): Список действий контроллера, из которых поведение **исключается**.
* `$logExcludedRequests` (bool): Если `true`, в логи Yii (`Yii::info`) будут записываться сообщения о запросах, исключенных из проверки по правилам поведения. Полезно для отладки.
* `$redirectStatusCode` (int): HTTP-статус код, используемый при перенаправлении бота (например, 301, 302).

## Структура базы данных

Модуль использует одну таблицу для хранения логов:

```sql
CREATE TABLE `antibot` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `date` INT(11) NOT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `referer` TEXT,
    `page` TEXT NOT NULL,
    `status` VARCHAR(255) NOT NULL,
    `os` VARCHAR(255) DEFAULT NULL COMMENT 'Операционная система посетителя'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Лицензия

Этот проект лицензируется под лицензией MIT. Подробности см. в файле [LICENSE.md](https://github.com/larikmc/yii2-antibot/blob/main/LICENSE.m