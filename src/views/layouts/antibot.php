<?php

/** @var \yii\web\View $this */
/** @var string $content */


use frontend\assets\AppAsset;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="57x57" href="/img/favi/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/img/favi/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/img/favi/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/img/favi/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/img/favi/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/img/favi/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/img/favi/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/img/favi/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/favi/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="/img/favi/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favi/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/img/favi/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favi/favicon-16x16.png">
    <link rel="manifest" href="/img/favi/manifest.json">
    <meta name="msapplication-TileColor" content="#dc3545">
    <meta name="msapplication-TileImage" content="/img/favi/ms-icon-144x144.png">
    <meta name="theme-color" content="#dc3545">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="content">
    <?= $content ?>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
