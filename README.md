# GitHub-Bitrix24-ImBot

Пример использования [бот платформы Битрикс24](https://bitrix24.ru/~bot) для интеграции с GitHub.

Бот принимает [веб-хуки от GitHub](https://developer.github.com/webhooks/) и транслилует их на ваш портал Битрикс24.


## Установка и настройки
<a id="install"></a>

<ul>
<li>Ваш сервер для размещения скриптов должен быть доступен через интернет.</li>
<li>Для выполнения REST-запросов необходим модуль cURL.</li>
<li>Настоятельно рекомендуется на вашем веб-сервере установить валидный SSL-сертификат.</li>
</ul>

### Установка
1. Скачивание и установку пакета и зависимостей рекомендуется выполнить используя composer - `composer install`
2. Зарегистрируете новое локальное приложение на своём портале Битрикс24. Скрипт `bot.php` из примера укажите в URL приложения и URL скрипта установки. Например: https://you.domain.xx/bot.php
3. Значения, полученных параметров client_id и client_secret для авторизации OAuth 2.0 из карточки локального приложения, поместите в константы `C_REST_CLIENT_ID` и `C_REST_CLIENT_SECRET` в файле `.settings.php`
4. В настройках репозитария на GitHub (https://github.com/<your>/<repository>/settings/hooks/) включите и настройте типы уведомлений о событиях. Скрипт `hook.php` из примера необходимо указать как параметр `Payload URL`. Например: https://you.domain.xx/hook.php
5. Полученное значение ключа `Secret` из настроек хука необходимо указать в константе `GITHUB_SECRET_TOKEN` в файле `.settings.php`

Пример использует для выполнения запросов и продления токенов авторизации базовый SDK класс CRest из пакета `bitrix-tools/crest`, который имеет свои дополнительные [настройки работы с рест](https://github.com/bitrix-tools/crest) запросами.

Включение / отключение подписки на уведомления выполняются командами чат-бота.


### Внимание ###

Бот представлен только для ознакомительных целей, вы можете использовать его в своих проектах, но ответственность работу ваших приложений лежит полностью на вас.



## Ссылки
<a id="links"></a>

GitHub Webhooks
https://developer.github.com/webhooks/

Бот платформа Битрикс24
https://bitrix24.ru/~bot

Bitrix Rest manual
https://dev.1c-bitrix.ru/rest_help/

Bitrix Rest learning course
https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99

Bitrix IM bot platform learning course
https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=93
