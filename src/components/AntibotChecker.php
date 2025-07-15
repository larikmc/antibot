<?php

namespace larikmc\Antibot\components;

use Yii;
use yii\base\Component;
use yii\web\Cookie;
use yii\web\Request;
use larikmc\Antibot\models\Antibot; // Добавлено для явного импорта модели Antibot

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
        'PetalBot',              // Краулер Huawei (F-Search)
        'bingbot',               // Краулер Bing
        'Mail.RU_Bot',           // Краулер Mail.ru
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
        'ya.ru',                 // Яндекс (главная страница)

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
    public $maxRequests = 40;

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
     * @var bool Включить/выключить логирование "хороших" ботов.
     * Если true, боты из списка goodBots будут логироваться со статусом 'good_bot'.
     */
    public $enableGoodBotLog = true;


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
     * Определяет операционную систему посетителя на основе User-Agent.
     * @return string Название операционной системы.
     */
    private function getOsFromUserAgent(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        if (strpos($userAgent, 'Windows NT 10.0') !== false) {
            return 'Windows 10/11';
        }
        if (strpos($userAgent, 'Windows NT 6.3') !== false) {
            return 'Windows 8.1';
        }
        if (strpos($userAgent, 'Windows NT 6.2') !== false) {
            return 'Windows 8';
        }
        if (strpos($userAgent, 'Windows NT 6.1') !== false) {
            return 'Windows 7';
        }
        if (strpos($userAgent, 'Windows NT 6.0') !== false) {
            return 'Windows Vista';
        }
        if (strpos($userAgent, 'Windows NT 5.1') !== false || strpos($userAgent, 'Windows XP') !== false) {
            return 'Windows XP';
        }
        if (strpos($userAgent, 'Windows NT 5.0') !== false || strpos($userAgent, 'Windows 2000') !== false) {
            return 'Windows 2000';
        }
        if (strpos($userAgent, 'Macintosh') !== false || strpos($userAgent, 'Mac OS X') !== false) {
            return 'macOS / OS X';
        }
        if (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        }
        if (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false || strpos($userAgent, 'iPod') !== false) {
            return 'iOS';
        }
        if (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        }
        if (strpos($userAgent, 'CrOS') !== false) {
            return 'Chrome OS';
        }
        if (strpos($userAgent, 'BlackBerry') !== false) {
            return 'BlackBerry';
        }
        if (strpos($userAgent, 'Opera Mini') !== false) {
            return 'Opera Mini (Mobile)';
        }
        if (strpos($userAgent, 'webOS') !== false) {
            return 'webOS';
        }
        if (strpos($userAgent, 'FreeBSD') !== false) {
            return 'FreeBSD';
        }
        if (strpos($userAgent, 'OpenBSD') !== false) {
            return 'OpenBSD';
        }
        if (strpos($userAgent, 'NetBSD') !== false) {
            return 'NetBSD';
        }

        return 'Unknown OS';
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
            // Проверяем, включено ли логирование хороших ботов перед сохранением лога.
            if (isset($this->enableGoodBotLog) && $this->enableGoodBotLog) {
                $this->saveAntibotLog($userAgent, $referer, 'good_bot');
            }
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
            $ip = $this->getRealClientIp();
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
        if (isset($this->enableAllTrafficLog) && $this->enableAllTrafficLog) { // Проверка на существование свойства
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
        $ip = $this->getRealClientIp();
        $os = $this->getOsFromUserAgent(); // Получаем ОС посетителя

        // Используем класс Antibot из пространства имен модуля
        if (class_exists(Antibot::class)) {
            $model = new Antibot();
            $model->date = time();
            $model->referer = $referer;
            $model->agent = $agent;
            $model->ip = $ip;
            $model->page = Yii::$app->request->url;
            $model->status = $status;
            $model->os = $os; // Сохраняем ОС

            if (!$model->save()) {
                Yii::error('Failed to save antibot log: ' . json_encode($model->getErrors()), __METHOD__);
            }
        } else {
            // Если модель Antibot не найдена, логируем ошибку через Yii::error
            Yii::error("Antibot model (larikmc\\Antibot\\models\\Antibot) not found for logging. Please check the namespace or model availability. Log data: Type={$status}, IP=" . $ip . ", UA={$agent}, Ref={$referer}, URL=" . Yii::$app->request->url . ", OS={$os}", __METHOD__);
        }
    }
}