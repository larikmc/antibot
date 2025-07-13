<?php

namespace larikmc\AntiBot\controllers; // Новое пространство имен

use Yii;
use yii\web\Controller;
use yii\web\Response;
use larikmc\AntiBot\components\AntiBotChecker; // Используем компонент

class AntiBotController extends Controller
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        // Отключаем CSRF-валидацию для этого контроллера, если вы используете AJAX
        // или перенаправляете сюда без CSRF-токена в URL.
        // Но лучше оставлять включенным и передавать токен через JS, как вы делали ранее.
        // $this->enableCsrfValidation = false;
    }

    /**
     * Действие для отображения страницы с кнопкой "Я не бот" и обработки её нажатия.
     */
    public function actionVerify()
    {
        /** @var AntiBotChecker $checker */
        $checker = $this->module->getChecker(); // Получаем компонент через модуль

        // Если пользователь уже помечен как "не бот", перенаправляем его на главную
        if ($checker->checkIfHuman()) {
            return $this->goHome();
        }

        if (Yii::$app->request->isPost && Yii::$app->request->post('not_bot_button')) {
            $checker->markAsHuman();

            $redirectUrl = Yii::$app->session->get('antibot_redirect_url', Yii::$app->homeUrl);
            Yii::$app->session->remove('antibot_redirect_url');

            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'status' => 'success',
                'message' => 'User verified as human. Redirecting...',
                'redirect_url' => $redirectUrl,
            ];
        }

        return $this->render('verify');
    }

    // Здесь больше не нужны методы checkIsBot, isGoodBot, saveAntibotLog и т.д.
    // Они будут в компоненте AntiBotChecker.
}