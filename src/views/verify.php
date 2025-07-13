<?php
$this->title = 'Подтвердите, что вы не робот';
?>

<style>
    body {
        background-color: #e8e8e8;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        font-size: 18px;
    }

    .bg {
        position: absolute;
        left: 0;
        top: 0;
        background-image: url("/img/antibot/screen.jpg");
        background-repeat: no-repeat;
        background-position: center;
        -webkit-filter: blur(5px);
        -moz-filter: blur(5px);
        -o-filter: blur(5px);
        -ms-filter: blur(5px);
        filter: blur(5px);
        width: 100%;
        height: 100vh;
    }

    .bg-black {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background: rgba(0, 0, 0, .4);
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
</div>

<div class="bg"></div>
<div class="bg-black"></div>

<script>
    function getEl(el) {
        return document.querySelector(el)
    }

    let test = getEl('#test')

    test.addEventListener('click', function () {
        let token = getEl('meta[name="csrf-token"]').content

        let request = new XMLHttpRequest()
        let params = "_csrf-frontend=" + token

        request.open("POST", '/antibot/verify', true)
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
        request.addEventListener("readystatechange", () => {
            if (request.readyState === 4 && request.status === 200) {
                window.location.href = JSON.parse(request.responseText).redirect_url;
            }
        })
        request.send(params)
    })
</script>