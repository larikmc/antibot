<?php

namespace Larikmc\AntiBot\components; // Изменено на Larikmc

use Yii;
use yii\base\Component;
use yii\web\Cookie;
// use backend\models\Antibot; // Убедитесь, что модель доступна или переместите ее в common/models

class AntiBotChecker extends Component
{
    public $goodBots = [];
    public $safeRefererDomains = [];
    public $maxRequests = 30;
    public $timeWindow = 60;

    /**
     * Метод для пометки пользователя как "не-бота" (человека).
     */
    public function markAsHuman()
    {
        $session = Yii::$app->session;
        $session->open();
        $session['not-bot'] = time();

        $cookie = new Cookie([
            'name' => 'is_human',
            'value' => 1,
            'expire' => time() + 86400 * 30, // Кука на 30 дней
            'httpOnly' => true,
        ]);
        Yii::$app->response->cookies->add($cookie);

        $this->saveAntibotLog(Yii::$app->request->userAgent, Yii::$app->request->referrer, 'marked_human');
    }

    /**
     * Проверяет, помечен ли пользователь как человек (по сессии или куке).
     */
    public function checkIfHuman(): bool
    {
        $session = Yii::$app->session;
        $session->open();
        return isset($session['not-bot']) || Yii::$app->request->cookies->has('is_human');
    }

    /**
     * Определяет, является ли User-Agent известным хорошим ботом.
     */
    public function isGoodBot(string $agent): bool
    {
        if (empty($agent)) {
            return false;
        }
        foreach ($this->goodBots as $bot) {
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
        $userAgent = Yii::$app->request->userAgent;
        $referer = Yii::$app->request->referrer;

        // 1. Проверка User-Agent: если это известный хороший бот, пропускаем
        if ($this->isGoodBot($userAgent)) {
            return false;
        }

        // 2. Более строгая проверка User-Agent (пустой или очень короткий UA)
        if (empty($userAgent) || strlen($userAgent) < 10) {
            $this->saveAntibotLog($userAgent, $referer, 'empty_or_short_ua');
            return true;
        }

        // 3. Проверка Referer
        if (empty($referer)) {
            $this->saveAntibotLog($userAgent, 'empty_referer', 'empty_referer');
            return true;
        } else {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $currentHost = Yii::$app->request->hostName;

            $isSafeReferer = false;
            foreach ($this->safeRefererDomains as $domain) {
                if (str_contains($refererHost, $domain)) {
                    $isSafeReferer = true;
                    break;
                }
            }

            if ($refererHost !== $currentHost && !$isSafeReferer) {
                $this->saveAntibotLog($userAgent, $referer, 'suspicious_referer');
                return true;
            }
        }

        // 4. Rate Limiting
        $ip = Yii::$app->request->userIP;
        $cache = Yii::$app->cache;
        $key = 'rate_limit_' . $ip;
        $requestCount = $cache->get($key);

        if ($requestCount === false) {
            $cache->set($key, 1, $this->timeWindow);
        } else {
            $requestCount++;
            $cache->set($key, $requestCount, $this->timeWindow);
        }

        if ($requestCount > $this->maxRequests) {
            $this->saveAntibotLog($userAgent, $referer, 'rate_limit_exceeded');
            return true;
        }

        return false; // Все проверки пройдены, не похоже на бота
    }

    /**
     * Сохранение логов активности бота
     */
    protected function saveAntibotLog($agent, $referer, $status)
    {
        // Здесь должна быть ваша модель Antibot.
        // Лучше всего, если эта модель будет либо в common/models, либо
        // вы создадите интерфейс в расширении, а реализацию (например, с Active Record)
        // оставите в своем приложении.
        $model = new \backend\models\Antibot(); // Убедитесь в корректном namespace для вашей модели Antibot!
        $model->date = time();
        $model->referer = $referer;
        $model->agent = $agent;
        $model->ip = Yii::$app->request->userIP;
        $model->page = Yii::$app->request->url;
        $model->status = $status;

        if (!$model->save()) {
            Yii::error('Failed to save antibot log: ' . json_encode($model->getErrors()), __METHOD__);
        }
    }
}