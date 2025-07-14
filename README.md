# `larikmc/Antibot`

[![Latest Stable Version](https://poser.pugx.org/larikmc/antibot/v/stable)](https://packagist.org/packages/larikmc/antibot)
[![Total Downloads](https://poser.pugx.org/larikmc/antibot/downloads)](https://packagist.org/packages/larikmc/antibot)
[![License](https://poser.pugx.org/larikmc/antibot/license)](https://packagist.org/packages/larikmc/antibot)

Модуль `larikmc/Antibot` для Yii2 предназначен для обнаружения и блокировки нежелательных ботов, перенаправляя их на страницу верификации "Я не робот".

---

## Установка

Предпочтительный способ установки этого расширения — через [composer](http://getcomposer.org/download/).

Выполните команду:

```bash
composer require larikmc/antibot
```

Добавление модуля Antibot в main.php
---

Откройте файл frontend/config/main.php (или common/config/main.php, если оно используется для всего приложения). 
1. Добавьте в секцию modules запись:

```php
'modules' => [
    // ... другие ваши модули ...
    'antibot' => [
        'class' => 'larikmc\\Antibot\\Module',        
    ],   
],
```

2. Добавьте в секцию components запись:

```php
'components' => [
    // ... другие компоненты приложения ...

    'antibotChecker' => [
        'class' => 'larikmc\\Antibot\\components\\AntibotChecker',
        'maxRequests' => 30, // Максимальное количество запросов, разрешенных в течение 'timeWindow'
        'timeWindow' => 60, // Временное окно в секундах (например, 60 секунд)
        // Дополнительные необязательные параметры:
        // 'goodBots' => [
        //     'YandexBot', 'Googlebot', 'Bingbot', 'Mail.RU_Bot', 'vkShare', 'WhatsApp', 'TelegramBot',
        // ],
        // 'safeRefererDomains' => [
        //     'google.com', 'yandex.ru', 'bing.com', 'mail.ru', 'facebook.com', 'vk.com',
        // ],
        // 'enableRateLimit' => false, // Включить/отключить проверку ограничения частоты запросов (по умолчанию true)
        // 'enableEmptyUaCheck' => false, // Отключить проверку пустого User-Agent (по умолчанию true)
        // 'enableRefererCheck' => false, // Отключить проверку реферера (по умолчанию true)        
        // 'enableGoodBotLog' => false, // Отключить логирование "Хороших" ботов (по умолчанию true)
        // 'enableHumanLog' => false, // Отключить логирование "человека" (по умолчанию true)
    ],

    // ... остальные компоненты ...
],
```

2. Добавьте в секцию components['urlManager']['rules'] запись:

```php
'rules' => [         
    'antibot/verify' => 'antibot/antibot/verify',
    // ... остальные правила маршрутизации ...
 ],
```

Прикрепление поведения AntibotBehavior к контроллерам
---
Чтобы модуль Antibot начал защищать ваши страницы, вам нужно прикрепить AntibotBehavior к контроллерам, которые вы хотите защитить. Обычно это делается в базовом контроллере вашего фронтенд-приложения (например, frontend/controllers/AppController.php или frontend/controllers/SiteController.php), чтобы защита применялась ко всем действиям по умолчанию.

Что нужно добавить
В вашем базовом контроллере (например, frontend/controllers/AppController.php), добавьте или измените метод behaviors() следующим образом:

```php
// frontend/controllers/AppController.php (или любой базовый контроллер)
namespace frontend\controllers;

use yii\web\Controller;
use larikmc\Antibot\behaviors\AntibotBehavior; // Важно: импортируйте класс

class AppController extends Controller // Предполагается, что это ваш базовый контроллер
{
    // ...

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['antibot'] = [
            'class' => AntibotBehavior::class,          
            'excludedRoutes' => [
                // Добавьте любые другие маршруты, которые не должны быть защищены:               
                // 'site/login',        // Страница входа
                // 'site/signup',       // Страница регистрации 
            ],
        ];

        return $behaviors;
    }

    // ...
}
```

Прикрепление поведения к дочерним контроллерам (например, SiteController)
---

Прикрепление поведения к дочерним контроллерам (например, SiteController)
Если у вас есть контроллеры, которые наследуются от базового контроллера (например, AppController), к которому вы уже прикрепили AntibotBehavior, и эти дочерние контроллеры имеют свой собственный метод behaviors(), вам необходимо убедиться, что они правильно наследуют поведения родителя.

Проблема: Если дочерний контроллер просто переопределяет метод behaviors() без вызова родительского метода, он полностью игнорирует все поведения, прикрепленные в родительском классе (включая AntibotBehavior).

Как правильно реализовать behaviors() в дочернем контроллере
Чтобы AntibotBehavior (и любые другие поведения из AppController) также применялся к SiteController, вам нужно вызвать parent::behaviors() и объединить полученные поведения с теми, которые специфичны для SiteController.

Откройте файл вашего дочернего контроллера (например, frontend/controllers/SiteController.php) и измените метод behaviors() следующим образом:

```php
// frontend/controllers/SiteController.php
namespace frontend\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
// use larikmc\Antibot\behaviors\AntibotBehavior; // Этот импорт не нужен, если AppController уже его делает

class SiteController extends AppController // SiteController расширяет AppController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        // Получаем все поведения, определенные в родительском классе (AppController).
        // Это КЛЮЧЕВОЙ ШАГ, который гарантирует, что AntibotBehavior будет унаследован.
        $behaviors = parent::behaviors();

        // Теперь вы можете добавить или переопределить поведения, специфичные
        // для SiteController, объединяя их с теми, что пришли от родителя.

        // Пример: AccessControl
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'only' => ['logout', 'signup'],
            'rules' => [
                [
                    'actions' => ['signup'],
                    'allow' => true,
                    'roles' => ['?'],
                ],
                [
                    'actions' => ['logout'],
                    'allow' => true,
                    'roles' => ['@'],
                ],
            ],
        ];

        // Пример: VerbFilter
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'logout' => ['post'],
            ],
        ];       

        return $behaviors;
    }

    // ... остальной код SiteController ...
}
```