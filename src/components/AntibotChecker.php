<?php

namespace larikmc\Antibot\components; // Пространство имен с большой 'B' в Antibot

use Yii;
use yii\base\Component;
use yii\web\Cookie;
use yii\web\Request; // Добавлено для явной подсказки типа

// use backend\models\Antibot; // Раскомментируйте и убедитесь, что эта модель доступна,
// или скорректируйте пространство имен (например, common\models\Antibot),
// если она используется как во фронтенде, так и в бэкенде.

/**
 * AntibotChecker - Компонент для обнаружения и логирования подозрительных запросов.
 */
class AntibotChecker extends Component
{
    /**
     * @var array Список известных "хороших" ботов (User-Agent'ы или их части), которые не должны блокироваться.
     */
    public $goodBots = [
        // Yandex Bots
        'YandexImages',          // Краулер изображений Яндекса
        'YandexVideo',           // Краулер видео Яндекса
        'YandexWebmaster',       // Инструменты Яндекс.Вебмастера
        'YandexMedia',           // Медиа-краулер Яндекса
        'YandexBlogs',           // Краулер блогов Яндекса
        'YandexDirect',          // Краулер Яндекс.Директа
        'YandexBot',             // Основной краулер Яндекса
        'YandexMetrika',         // Краулер Яндекс.Метрики
        'YandexRCA',             // Робот для проверки доступности сайтов (Яндекс)
        'YandexAccessibilityBot',// Робот для проверки доступности (accessibility)
        'YandexRenderResourcesBot',// Робот для рендеринга ресурсов (Яндекс)
        'YandexMobileBot',       // Мобильный краулер Яндекса
        'YaDirectFetcher',       // Фетчер Яндекс.Директа

        // Google Bots
        'Googlebot',             // Основной краулер Google
        'Mediapartners-Google',  // Краулер Google AdSense
        'Google-Read-Aloud',     // Сервис Google "Читать вслух"
        'AdsBot-Google',         // Краулер Google Ads
        'Chrome-Lighthouse',     // Инструмент Google Lighthouse для аудита производительности
        'Chrome-Privacy-Preserving-Prefetch-Proxy', // Прокси Chrome для предварительной загрузки с сохранением конфиденциальности
        'GPTBot',                // Краулер OpenAI для обучения ИИ

        // Social Media Bots
        'vkShare',               // Краулер ВКонтакте для предпросмотра ссылок
        'VKRobotRB',             // Робот ВКонтакте
        'WhatsApp',              // Краулер WhatsApp для предпросмотра ссылок
        'TelegramBot',           // Краулер Telegram для предпросмотра ссылок
        'Pinterestbot',          // Краулер Pinterest для предпросмотра пинов
        'OdklBot',               // Краулер Одноклассников
        'Facebot',               // Краулер Facebook
        'Twitterbot',            // Краулер Twitter
        'LinkedInBot',           // Краулер LinkedIn
        'Discordbot',            // Краулер Discord
        'Slackbot',              // Краулер Slack
        'Embedly',               // Сервис Embedly для встраивания контента

        // Other Search Engine Bots
        'DuckDuckBot',           // Краулер DuckDuckGo
        'Baiduspider',           // Краулер Baidu
        'Slurp',                 // Краулер Yahoo!
        'Exabot',                // Краулер Exalead
        'SeznamBot',             // Краулер Seznam.cz
        'Applebot',              // Краулер Apple (Siri, Spotlight)
        'Bytespider',            // Краулер ByteDance (TikTok)
        'Sogou web spider',      // Краулер Sogou
        'PetalBot',              // Краулер Huawei (Petal Search)
        'bingbot',               // Краулер Bing
        'Mail.RU_Bot',           // Краулер Mail.ru

        // SEO & Monitoring Tools
//        'AhrefsBot',             // Краулер Ahrefs
//        'SemrushBot',            // Краулер Semrush
//        'MJ12bot',               // Краулер Majestic
//        'DotBot',                // Краулер Moz
//        'Crawlster',             // Краулер для сбора данных
//        'MegaIndex',             // Краулер MegaIndex
//        'Screaming Frog SEO Spider',// Инструмент Screaming Frog
//        'UptimeRobot',           // Сервис мониторинга доступности
//        'PingdomBot',            // Сервис мониторинга производительности
//        'StatusCake',            // Сервис мониторинга доступности
//        'NewRelicPinger',        // Агент New Relic для мониторинга
//        'Datadog/Synthetics',    // Агент Datadog для синтетических проверок
//        'Site24x7',              // Сервис мониторинга Site24x7
//        'ContentKing',           // Сервис аудита контента и SEO
//        'NetcraftSurveyAgent',   // Агент Netcraft для сбора данных о веб-серверах
//        'CensysBot',             // Краулер Censys для исследования безопасности
//        'ZoominfoBot',           // Краулер ZoomInfo для бизнес-данных
//        'Cliqzbot',              // Краулер Cliqz
//        'MauiBot',               // Краулер для сбора данных/аналитики
    ];

    /**
     * @var array Список доменов, с которых реферер считается безопасным.
     */
    public $safeRefererDomains = [
        // Поисковые системы и их региональные версии
        'google.com',            // Google (основной)
        'yandex.ru',             // Яндекс (Россия)
        'yandex.kz',             // Яндекс (Казахстан)
        'yandex.ua',             // Яндекс (Украина)
        'yandex.by',             // Яндекс (Беларусь)
        'bing.com',              // Bing
        'mail.ru',               // Mail.ru
        'yahoo.com',             // Yahoo (основной)
        'duckduckgo.com',        // DuckDuckGo
        'baidu.com',             // Baidu
        'yahoo.co.jp',           // Yahoo Japan
        'ecosia.org',            // Ecosia
        'startpage.com',         // Startpage (ориентирован на конфиденциальность)
        'brave.com',             // Brave Search

        // Социальные сети и мессенджеры
        'facebook.com',          // Facebook
        'vk.com',                // ВКонтакте
        'twitter.com',           // Twitter/X
        't.me',                  // Telegram
        'pinterest.com',         // Pinterest
        'ok.ru',                 // Одноклассники
        'linkedin.com',          // LinkedIn
        'instagram.com',         // Instagram
        'tiktok.com',            // TikTok
        'snapchat.com',          // Snapchat
        'tumblr.com',            // Tumblr

        // Платформы для публикации контента и сообщества
        'reddit.com',            // Reddit
        'medium.com',            // Medium
        'quora.com',             // Quora
        'stackoverflow.com',     // Stack Overflow
        'github.com',            // GitHub
        'gitlab.com',            // GitLab
        'bitbucket.org',         // Bitbucket
        'wikipedia.org',         // Wikipedia
        'flickr.com',            // Flickr

        // Крупные торговые площадки
        'amazon.com',            // Amazon
        'ebay.com',              // eBay
        'aliexpress.com',        // AliExpress
        'shopify.com',           // Shopify

        // Прочие доверенные домены (например, сокращатели ссылок, если они используются)
        't.co',                  // Сокращатель ссылок Twitter
    ];

    /**
     * @var int Максимальное количество запросов за 'timeWindow' с одного IP.
     */
    public $maxRequests = 40; // <-- ИЗМЕНЕНО: Значение по умолчанию установлено на 40

    /**
     * @var int Временное окно в секундах, для которого отслеживается maxRequests.
     */
    public $timeWindow = 60;

    /**
     * @var bool Включить/выключить ограничение частоты запросов.
     */
    public $enableRateLimit = true;

    /**
     * @var bool Включить/выключить проверку на пустой User-Agent.
     */
    public $enableEmptyUaCheck = true;

    /**
     * @var bool Включить/выключить проверку реферера.
     */
    public $enableRefererCheck = true;

    /**
     * @var bool Включить/выключить логирование, когда пользователь помечается как человек.
     */
    public $enableHumanLog = true;

    /**
     * @var bool Включить/выключить логирование всех не-ботовых посещений.
     * Если true, все запросы, не идентифицированные как боты, будут логироваться со статусом 'non_suspicious'.
     */
    public $enableAllTrafficLog = false;

    /**
     * Метод для пометки пользователя как "не-бота" (человека).
     */
    public function markAsHuman()
    {
        $session = Yii::$app->session;
        // Убедитесь, что сессия открыта, прежде чем обращаться к ней
        if (!$session->isActive) {
            $session->open();
        }
        $session['not-bot'] = time(); // Сохраняем метку времени для потенциальных будущих проверок срока действия

        $cookie = new Cookie([
            'name' => 'is_human',
            'value' => 1,
            'expire' => time() + 86400 * 30, // Кука на 30 дней (86400 секунд в дне)
            'httpOnly' => true, // Важно для безопасности (предотвращает доступ через JS)
        ]);
        Yii::$app->response->cookies->add($cookie);

        // Логируем действие пометки как человека, если enableHumanLog включен
        if ($this->enableHumanLog) {
            /** @var Request $request */
            $request = Yii::$app->request;
            $this->saveAntibotLog($request->userAgent, $request->referrer, 'human_identified');
        }
    }

    /**
     * Проверяет, помечен ли пользователь как человек (по сессии или куке).
     * @return bool True, если пользователь помечен как человек.
     */
    public function checkIfHuman(): bool
    {
        $session = Yii::$app->session;
        // Убедитесь, что сессия открыта для проверки
        if (!$session->isActive) {
            $session->open();
        }
        return isset($session['not-bot']) || Yii::$app->request->cookies->has('is_human');
    }

    /**
     * Определяет, является ли User-Agent известным хорошим ботом.
     * @param string $agent User-Agent строка.
     * @return bool True, если это хороший бот, false в противном случае.
     */
    public function isGoodBot(string $agent): bool
    {
        if (empty($agent)) {
            return false;
        }
        foreach ($this->goodBots as $bot) {
            // Поиск подстроки без учета регистра
            if (stripos($agent, $bot) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Вспомогательная функция для получения реального IP-адреса клиента.
     * Используется как обходной путь для старых версий Yii2, не поддерживающих trustedProxies.
     * @return string IP-адрес клиента.
     */
    protected function getRealClientIp()
    {
        // Порядок заголовков здесь важен!
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // X-Forwarded-For может содержать несколько IP через запятую (клиент, прокси1, прокси2)
            // Нам нужен первый (самый левый)
            $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) { // Часто используется Nginx
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) { // Для Cloudflare
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'UNKNOWN'; // Если IP не найден
        }

        // ВНИМАНИЕ: Вручную реализованная проверка доверенных прокси здесь не выполняется.
        // Если вы используете эту функцию, убедитесь, что ваш прокси-сервер надежен
        // и не позволяет подделывать заголовки IP.

        return $ip;
    }

    /**
     * Основной метод проверки на бота.
     * @return bool True, если это бот, false в противном случае.
     */
    public function checkIsBot(): bool
    {
        /** @var Request $request */
        $request = Yii::$app->request;
        $userAgent = $request->userAgent;
        $referer = $request->referrer;

        // 1. Проверка User-Agent: если это известный хороший бот, пропускаем сразу
        if ($this->isGoodBot($userAgent)) {
            $this->saveAntibotLog($userAgent, $referer, 'good_bot');
            return false; // Не бот, он легитимен
        }

        // 2. Более строгая проверка User-Agent (пустой или очень короткий UA)
        // Эта проверка срабатывает, если good_bot не сработал.
        // Проверка осуществляется ТОЛЬКО если enableEmptyUaCheck установлен в true
        if ($this->enableEmptyUaCheck && (empty($userAgent) || strlen($userAgent) < 10)) {
            $this->saveAntibotLog($userAgent, $referer, 'empty_or_short_ua');
            return true;
        }

        // 3. Проверка Referer
        // Проверка осуществляется ТОЛЬКО если enableRefererCheck установлен в true
        if ($this->enableRefererCheck) {
            if (empty($referer)) {
                // Логируем со статусом 'empty_referer', передавая фактический реферер как пустую строку
                $this->saveAntibotLog($userAgent, '', 'empty_referer');
                return true;
            } else {
                $refererHost = parse_url($referer, PHP_URL_HOST);
                $currentHost = Yii::$app->request->hostName;

                $isSafeReferer = false;
                // Проверяем, является ли хост реферера одним из явно безопасных доменов
                foreach ($this->safeRefererDomains as $domain) {
                    // Используем str_contains для простой проверки подстроки
                    // (например, 'google.com' в 'www.google.com')
                    if (str_contains($refererHost, $domain)) {
                        $isSafeReferer = true;
                        break;
                    }
                }

                // Если реферер не с текущего хоста и не с безопасного домена
                if ($refererHost !== $currentHost && !$isSafeReferer) {
                    $this->saveAntibotLog($userAgent, $referer, 'suspicious_referer');
                    return true;
                }
            }
        }

        // 4. Ограничение частоты запросов на основе IP
        // Проверка осуществляется ТОЛЬКО если enableRateLimit установлен в true
        if ($this->enableRateLimit) {
            // Теперь используем нашу кастомную функцию для получения IP клиента
            $ip = $this->getRealClientIp(); // ИЗМЕНЕНО
            $cache = Yii::$app->cache; // Предполагается, что компонент 'cache' настроен

            $key = 'antibot_rate_limit_' . $ip; // Уникальный ключ кэша для IP
            $requestCount = $cache->get($key);

            if ($requestCount === false) {
                // Первый запрос в окне
                $cache->set($key, 1, $this->timeWindow);
            } else {
                // Увеличиваем счетчик и обновляем кэш
                $requestCount++;
                $cache->set($key, $requestCount, $this->timeWindow);
            }

            if ($requestCount > $this->maxRequests) {
                $this->saveAntibotLog($userAgent, $referer, 'rate_limit_exceeded');
                return true;
            }
        }

        // Если все проверки пройдены, запрос не определяется как бот по этим правилам
        // Логируем как "неподозрительное" посещение, если опция включена
        if ($this->enableAllTrafficLog) {
            $this->saveAntibotLog($userAgent, $referer, 'non_suspicious');
        }
        return false;
    }

    /**
     * Сохранение логов активности бота в базу данных.
     *
     * @param string $agent User-Agent запроса.
     * @param string|null $referer Реферер запроса.
     * @param string $status Статус события (например, 'good_bot', 'rate_limit_exceeded', 'empty_referer', 'human_identified', 'non_suspicious').
     */
    protected function saveAntibotLog($agent, $referer, $status)
    {
        // ВАЖНО: Убедитесь, что ваша модель Antibot правильно настроена и доступна.
        // Если она находится в другом пространстве имен или модуле, скорректируйте оператор 'use'
        // и инициализацию (например, new \common\models\Antibot();).
        // Если вы видите ошибку класса, проверьте путь к вашей модели `Antibot`.

        // Пример: Использование модели из `backend\models`
        // Добавьте `use backend\models\Antibot;` в начале файла,
        // если эта модель доступна в этом пространстве имен.

        $ip = $this->getRealClientIp(); // Используем кастомную функцию для получения IP

        // Проверяем, существует ли класс модели, чтобы предотвратить фатальные ошибки,
        // если модель не настроена или путь к ней неверен.
        if (class_exists('backend\models\Antibot')) { // Измените на ваше реальное пространство имен, если оно другое
            $model = new \backend\models\Antibot(); // Измените на ваше реальное пространство имен
            $model->date = time();
            $model->referer = $referer;
            $model->agent = $agent;
            $model->ip = $ip; // Используем IP из кастомной функции
            $model->page = Yii::$app->request->url;
            $model->status = $status;

            if (!$model->save()) {
                Yii::error('Failed to save antibot log: ' . json_encode($model->getErrors()), __METHOD__);
            }
        } else {
            // Если модель Antibot не найдена, логируем ошибку через Yii::error
            Yii::error("Antibot model (backend\\models\\Antibot) not found for logging. Please check the namespace or model availability. Log data: Type={$status}, IP=" . $ip . ", UA={$agent}, Ref={$referer}, URL=" . Yii::$app->request->url, __METHOD__);
        }
    }
}