<?php

namespace larikmc\Antibot\components;

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
        'YandexImages', 'YandexVideo', 'YandexWebmaster', 'YandexMedia', 'YandexBlogs', 'YandexDirect', 'YandexBot',
        'YandexMetrika', 'YandexRCA', 'YandexAccessibilityBot', 'YandexRenderResourcesBot', 'YandexMobileBot',
        'YaDirectFetcher', 'Googlebot', 'vkShare', 'VKRobotRB', 'WhatsApp', 'TelegramBot', 'Pinterestbot',
        'OdklBot', 'Mediapartners-Google', 'Google-Read-Aloud', 'AdsBot-Google', 'Chrome-Lighthouse',
        'bingbot', 'Mail.RU_Bot',
        // Добавленные боты:
        'DuckDuckBot', 'Baiduspider', 'Slurp', 'Facebot', 'Twitterbot', 'LinkedInBot', 'AhrefsBot', 'SemrushBot',
        'MJ12bot', 'DotBot', 'Exabot', 'SeznamBot', 'Applebot', 'Discordbot', 'Slackbot', 'Embedly',
        'Bytespider', 'MauiBot', 'Sogou web spider', 'PetalBot', 'CensysBot', 'ZoominfoBot', 'Cliqzbot',
        'MauiBot', 'Crawlster', 'MegaIndex', 'Screaming Frog SEO Spider', 'UptimeRobot', 'PingdomBot',
        'StatusCake', 'NewRelicPinger', 'Datadog/Synthetics', 'Site24x7', 'ContentKing', 'NetcraftSurveyAgent',
    ];

    /**
     * @var array Список доменов, с которых реферер считается безопасным.
     */
    public $safeRefererDomains = [
        'google.com', 'yandex.ru', 'yandex.kz', 'yandex.ua', 'yandex.by', 'bing.com',
        'mail.ru', 'yahoo.com', 'facebook.com', 'vk.com', 'twitter.com', 't.me', 'pinterest.com',
        'ok.ru', 'linkedin.com', 'reddit.com',
        // Добавленные домены:
        'duckduckgo.com', 'baidu.com', 'yahoo.co.jp', 'ecosia.org', 'startpage.com', 'brave.com',
        't.co', 'instagram.com', 'tiktok.com', 'snapchat.com', 'tumblr.com', 'flickr.com',
        'medium.com', 'quora.com', 'stackoverflow.com', 'github.com', 'gitlab.com', 'bitbucket.org',
        'wikipedia.org', 'amazon.com', 'ebay.com', 'aliexpress.com', 'shopify.com',
    ];

    /**
     * @var int Максимальное количество запросов за 'timeWindow' с одного IP.
     */
    public $maxRequests = 20;

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
     * Метод для пометки пользователя как "не-бота" (человека) в сессии и куках.
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

        // Логируем действие пометки как человека
        /** @var Request $request */
        $request = Yii::$app->request;
        $this->saveAntibotLog($request->userAgent, $request->referrer, 'marked_human');
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
            $ip = Yii::$app->request->userIP;
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
        return false;
    }

    /**
     * Сохранение логов активности бота в базу данных.
     *
     * @param string $agent User-Agent запроса.
     * @param string|null $referer Реферер запроса.
     * @param string $status Статус события (например, 'good_bot', 'rate_limit_exceeded', 'empty_referer').
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

        // Проверяем, существует ли класс модели, чтобы предотвратить фатальные ошибки,
        // если модель не настроена или путь к ней неверен.
        if (class_exists('backend\models\Antibot')) { // Измените на ваше реальное пространство имен, если оно другое
            $model = new \backend\models\Antibot(); // Измените на ваше реальное пространство имен
            $model->date = time();
            $model->referer = $referer;
            $model->agent = $agent;
            $model->ip = Yii::$app->request->userIP;
            $model->page = Yii::$app->request->url;
            $model->status = $status;

            if (!$model->save()) {
                Yii::error('Failed to save antibot log: ' . json_encode($model->getErrors()), __METHOD__);
            }
        } else {
            // Если модель Antibot не найдена, логируем ошибку через Yii::error
            Yii::error("Antibot model (backend\\models\\Antibot) not found for logging. Please check the namespace or model availability. Log data: Type={$status}, IP=" . Yii::$app->request->userIP . ", UA={$agent}, Ref={$referer}, URL=" . Yii::$app->request->url, __METHOD__);
        }
    }
}