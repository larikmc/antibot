<?php
/* @var $this yii\web\View */
$this->title = 'Подтвердите, что вы не робот';

// Получаем CSRF-токен из Yii2
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
?>

<style>
    body {
        background-color: #e8e8e8;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        font-size: 18px;
        margin: 0; /* Убираем стандартные отступы body */
        overflow: hidden; /* Скрываем прокрутку, если фон больше экрана */
    }

    .bg {
        position: fixed; /* Используем fixed для корректного позиционирования фона */
        left: 0;
        top: 0;
        background-image: url("/img/antibot/screen.jpg"); /* Проверьте путь! */
        background-size: cover; /* Заполнение всего элемента */
        background-repeat: no-repeat;
        background-position: center;
        filter: blur(5px); /* Использование стандартного filter */
        width: 100%;
        height: 100vh;
        z-index: -2; /* Отправляем на задний план */
    }

    .bg-black {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background: rgba(0, 0, 0, .4);
        z-index: -1; /* Поверх фона, но под контентом */
    }

    #test {
        padding: 20px;
        background-color: #fff;
        margin: 10% auto 0 auto;
        max-width: 355px;
        border-radius: 20px;
        cursor: pointer;
        display: grid;
        grid-template-columns: 54px 1fr;
        gap: 10px;
        align-items: center;
        position: relative;
        z-index: 999;
        box-shadow: 0px 5px 20px 0px rgb(0 0 0 / 60%);
        transition: transform 0.2s ease-in-out; /* Анимация при клике */
    }

    #test:active {
        transform: scale(0.98); /* Небольшое сжатие при клике */
    }

    .box {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 2px solid #e8e8e8;
    }

    .title {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .loading-spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        animation: spin 1s linear infinite;
        display: none; /* Скрыт по умолчанию */
        position: absolute;
        right: 20px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (min-width: 992px) {
        #test {
            padding: 30px;
            gap: 30px;
        }

        .box {
            width: 50px;
            height: 50px;
        }
    }
</style>

<div id="test">
    <div class="box"></div>
    <div>
        <div class="title">Я не робот</div>
        <div class="desc">Нажмите, чтобы продолжить</div>
    </div>
    <div class="loading-spinner" id="spinner"></div> </div>

<div class="bg"></div>
<div class="bg-black"></div>

<script>
    function getEl(el) {
        return document.querySelector(el);
    }

    let testButton = getEl('#test');
    let spinner = getEl('#spinner');

    testButton.addEventListener('click', function() {
        testButton.style.pointerEvents = 'none'; // Отключаем повторные клики
        spinner.style.display = 'block'; // Показываем спиннер

        let csrfParamName = '<?php echo $csrfParam; ?>';
        let csrfTokenValue = '<?php echo $csrfToken; ?>';

        let formData = new URLSearchParams();
        formData.append(csrfParamName, csrfTokenValue);
        formData.append('not_bot_button', 1); // Передаем флаг кнопки, как ожидает PHP

        let request = new XMLHttpRequest();
        request.open("POST", '/antibot/verify', true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        request.onload = function() {
            spinner.style.display = 'none'; // Скрываем спиннер
            testButton.style.pointerEvents = 'auto'; // Включаем клики обратно

            if (request.status >= 200 && request.status < 400) {
                try {
                    let response = JSON.parse(request.responseText);
                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        alert('Ошибка: Не получен URL для перенаправления.');
                        console.error('Ошибка AJAX: ', response);
                    }
                } catch (e) {
                    alert('Ошибка парсинга ответа сервера.');
                    console.error('Ошибка парсинга JSON:', e);
                    console.error('Ответ сервера:', request.responseText);
                }
            } else {
                alert('Произошла ошибка при проверке. Код статуса: ' + request.status);
                console.error('Ошибка AJAX, статус: ' + request.status, request.responseText);
            }
        };

        request.onerror = function() {
            spinner.style.display = 'none'; // Скрываем спиннер
            testButton.style.pointerEvents = 'auto'; // Включаем клики обратно
            alert('Сетевая ошибка. Проверьте ваше соединение или попробуйте позже.');
            console.error('Сетевая ошибка AJAX.');
        };

        request.send(formData.toString());
    });
</script>