<?php

namespace Larikmc\Antibot\controllers; // Изменено на Larikmc

use Yii;
use yii\web\Controller;
use yii\web\Response;
use Larikmc\Antibot\components\AntibotChecker; // Изменено на Larikmc

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

        if ($checker->checkIfHuman()) {
            return $this->goHome();
        }

        if (Yii::$app->request->isPost && Yii::$app->request->post('not_bot_button')) {
            $checker->markAsHuman();

            $redirectUrl = Yii::$app->session->get('Antibot_redirect_url', Yii::$app->homeUrl);
            Yii::$app->session->remove('Antibot_redirect_url');

            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'status' => 'success',
                'message' => 'User verified as human. Redirecting...',
                'redirect_url' => $redirectUrl,
            ];
        }

        return $this->render('verify');
    }
}