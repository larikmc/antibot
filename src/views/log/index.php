<?php

use larikmc\Antibot\models\Antibot; // ИЗМЕНЕНО: Используем модель из модуля
use larikmc\Antibot\models\AntibotSearch; // ИЗМЕНЕНО: Используем модель поиска из модуля
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var larikmc\Antibot\models\AntibotSearch $searchModel */ // ИЗМЕНЕНО: Тип модели поиска
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Антибот логи';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="antibot-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Удалить все', ['/antibot/log/delete-all'], [ // ИЗМЕНЕНО: Маршрут для действия delete-all
            'class' => 'btn btn-danger', // Изменено на danger для удаления
            'data' => [
                'confirm' => 'Вы уверены, что хотите удалить все записи логов?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'id',
            [
                'attribute' => 'date',
                'options' => ['width' => '50'],
                'content' => function($model){
                    return date('d.m.Y H:i:s', $model->date);
                }
            ],
            [
                'attribute' => 'ip',
                'options' => ['width' => '50'],
            ],
            'referer:ntext',
            'os',
            'agent:ntext',
            'page:ntext',
            [
                'attribute' => 'status',
                'options' => ['width' => '100'],
                'filter' => [
                    'empty_referer' => 'пустой реферер',
                    'empty_or_short_ua' => 'короткий user-agent', // ИЗМЕНЕНО: Исправлена опечатка
                    'suspicious_referer' => 'подозрительный реферер',
                    'good_bot' => 'good_bot',
                    'human_identified' => 'обнаружен человек',
                    'non_suspicious' => 'не бот',
                    'rate_limit_exceeded' => 'много запросов',
                ],
                'content' => function ($model) {
                    $ar = [
                        'empty_referer' => '<span class="badge bg-danger">пустой реферер</span>',
                        'empty_or_short_ua' => '<span class="badge bg-danger">короткий user-agent</span>', // ИЗМЕНЕНО: Исправлена опечатка
                        'suspicious_referer' => '<span class="badge bg-danger">подозрительный реферер</span>',
                        'good_bot' => '<span class="badge bg-success">good_bot</span>',
                        'human_identified' => '<span class="badge bg-success">обнаружен человек</span>',
                        'non_suspicious' => '<span class="badge bg-warning text-dark">не бот</span>',
                        'rate_limit_exceeded' => '<span class="badge bg-danger">много запросов</span>',
                    ];

                    // Проверяем, начинается ли статус с 'suspicious_headers:'
                    if (str_starts_with($model->status, 'suspicious_headers:')) {
                        return '<span class="badge bg-danger">' . Html::encode($model->status) . '</span>';
                    } elseif (isset($ar[$model->status])) {
                        return $ar[$model->status];
                    } else {
                        return Html::encode($model->status); // Используем Html::encode для безопасности
                    }
                }
            ],
            [
                'class' => ActionColumn::class,
                'urlCreator' => function ($action, $model, $key, $index, $column) {
                    return Url::toRoute(['/antibot/log/' . $action, 'id' => $model->id]);
                },
                'header' => '',
                'buttonOptions' => ['class' => 'btn btn-primary'],
                'template' => '<div class="btn-group">{delete}</div>',
                'options' => ['style' => 'width:50px'],
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>