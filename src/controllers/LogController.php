<?php

namespace larikmc\Antibot\controllers; // Пространство имен контроллеров модуля

use Yii; // Добавлено, если используется Yii::$app
use larikmc\Antibot\models\Antibot; // Импорт модели из пространства имен модуля
use larikmc\Antibot\models\AntibotSearch; // Импорт модели поиска из пространства имен модуля
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException; // Оставлено, так как findModel может быть использован для delete
use yii\filters\VerbFilter;

/**
 * LogController implements the CRUD actions for Antibot model.
 * Этот контроллер реализует CRUD-действия для модели Antibot.
 * Оставлены только действия index, delete и deleteAll.
 */
class LogController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin'], // Убедитесь, что роль 'admin' настроена в вашем приложении
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'delete-all' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Deletes all Antibot models.
     * Удаляет все модели Antibot.
     * @return \yii\web\Response
     */
    public function actionDeleteAll()
    {
        Antibot::deleteAll(); // Используем модель Antibot из текущего пространства имен
        Yii::$app->session->setFlash('success', 'Все записи логов успешно удалены.');
        return $this->redirect(['index']);
    }

    /**
     * Lists all Antibot models.
     * Отображает список всех моделей Antibot.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new AntibotSearch(); // Используем модель поиска из текущего пространства имен
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Deletes an existing Antibot model.
     * Удаляет существующую модель Antibot.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * Если удаление успешно, браузер будет перенаправлен на страницу 'index'.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Запись лога успешно удалена.');

        return $this->redirect(['index']);
    }

    /**
     * Finds the Antibot model based on its primary key value.
     * Находит модель Antibot по ее первичному ключу.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * Если модель не найдена, будет выброшено исключение HTTP 404.
     * @param int $id ID
     * @return Antibot the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Antibot::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Запрошенная страница не существует.');
    }
}