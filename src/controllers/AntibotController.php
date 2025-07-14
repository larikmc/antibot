<?php

namespace larikmc\Antibot\controllers;

use Yii;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\Response;
use larikmc\Antibot\components\AntibotChecker;

class AntibotController extends Controller
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Действие для отображения страницы с кнопкой "Я не бот" и обработки её нажатия.
     */
    public function actionVerify()
    {
        /** @var AntibotChecker $checker */
        $checker = $this->module->getChecker();

        // Если пользователь уже помечен как человек (либо из сессии, либо из куки),
        // сразу перенаправляем его на главную страницу или туда, откуда он пришел.
        if ($checker->checkIfHuman()) {
            $redirectUrl = Yii::$app->session->get('antibot_redirect_url', Yii::$app->homeUrl);
            Yii::$app->session->remove('antibot_redirect_url');
            return $this->redirect($redirectUrl);
        }

        // Обработка POST-запроса, когда пользователь нажимает кнопку "Я не робот"
        if (Yii::$app->request->isPost && Yii::$app->request->post('not_bot_button')) {
            $checker->markAsHuman(); // Помечаем пользователя как человека
            $redirectUrl = Yii::$app->session->get('antibot_redirect_url', Yii::$app->homeUrl);
            Yii::$app->session->remove('antibot_redirect_url');

            // Отправляем JSON-ответ для AJAX-запроса
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'status' => 'success',
                'message' => 'User verified as human. Redirecting...',
                'redirect_url' => $redirectUrl,
            ];
        }

        // Рендерим представление с кнопкой "Я не бот"
        $this->layout = 'antibot';
        return $this->render('verify');
    }
}