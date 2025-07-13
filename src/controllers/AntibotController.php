<?php

namespace Larikmc\Antibot\controllers;

use Yii;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\Response;
use Larikmc\Antibot\components\AntibotChecker;

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

            $redirectUrl = Yii::$app->session->get('antibot_redirect_url', Yii::$app->homeUrl);
            Yii::$app->session->remove('antibot_redirect_url');

            return Json::encode(['redirect_url' => $redirectUrl]);
        }

        //Рендерим представление с кнопкой "Я не бот"
        $this->layout = 'antibot';
        return $this->render('verify');
    }
}