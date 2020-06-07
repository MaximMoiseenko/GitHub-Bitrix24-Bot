# GitHub-Bitrix24-ImBot

������ ������������� [��� ��������� �������24](https://bitrix24.ru/~bot) ��� ���������� � GitHub.

��� ��������� [���-���� �� GitHub](https://developer.github.com/webhooks/) � ����������� �� �� ��� ������ �������24.


## ��������� � ���������
<a id="install"></a>

<ul>
<li>��� ������ ��� ���������� �������� ������ ���� �������� ����� ��������.</li>
<li>��� ���������� REST-�������� ��������� ������ cURL.</li>
<li>������������ ������������� �� ����� ���-������� ���������� �������� SSL-����������.</li>
</ul>

### ���������
1. ���������� � ��������� ������ � ������������ ������������� ��������� ��������� composer - `composer install`
2. ��������������� ����� ��������� ���������� �� ���� ������� �������24. ������ `bot.php` �� ������� ������� � URL ���������� � URL ������� ���������. ��������: https://you.domain.xx/bot.php
3. ��������, ���������� ���������� client_id � client_secret ��� ����������� OAuth 2.0 �� �������� ���������� ����������, ��������� � ��������� `C_REST_CLIENT_ID` � `C_REST_CLIENT_SECRET` � ����� `.settings.php`
4. � ���������� ����������� �� GitHub (https://github.com/<your>/<repository>/settings/hooks/) �������� � ��������� ���� ����������� � ��������. ������ `hook.php` �� ������� ���������� ������� ��� �������� `Payload URL`. ��������: https://you.domain.xx/hook.php
5. ���������� �������� ����� `Secret` �� �������� ���� ���������� ������� � ��������� `GITHUB_SECRET_TOKEN` � ����� `.settings.php`

������ ���������� ��� ���������� �������� � ��������� ������� ����������� ������� SDK ����� CRest �� ������ `bitrix-tools/crest`, ������� ����� ���� �������������� [��������� ������ � ����](https://github.com/bitrix-tools/crest) ���������.

��������� / ���������� �������� �� ����������� ����������� ��������� ���-����.


### �������� ###

��� ����������� ������ ��� ��������������� �����, �� ������ ������������ ��� � ����� ��������, �� ��������������� ������ ����� ���������� ����� ��������� �� ���.



## ������
<a id="links"></a>

GitHub Webhooks
https://developer.github.com/webhooks/

��� ��������� �������24
https://bitrix24.ru/~bot

Bitrix Rest manual
https://dev.1c-bitrix.ru/rest_help/

Bitrix Rest learning course
https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99

Bitrix IM bot platform learning course
https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=93
