# GitHub-Bitrix24-ImBot

@bitrix/crest � ��������� PHPSDK ��� ������������� REST API �������24 � ���������, 
�������� ����������� ��� ����� �������

## ����������
1. [��������](#intro)
2. [��������� �������24](#bitrix24)
3. [��������� GitHub](#github)
4. [�������](#links)



##��������
<a id="intro"></a>

##��������� �������24
<a id="bitrix24"></a>

##��������� GitHub
<a id="github"></a>

<h2 id="introduction">��������</h2>

<ul>
<li>������� ���������� ������ cURL ��� ���������� REST-��������. �������, ��� �������� ������ cURL �� ����� �������.


<li>�� ����� ���-������� ������ ���� ���������� �������� SSL-����������.

<li>������� ���������� ������� SDK � ���� ������ CRest ��� ���������� �������� � ��������� ������� �����������. �������� ����� �� ����������� ������� ����������� ������, ��������� � ������������ � ���� settings.php � ���������� �� ����� �������, ������� ������ ��� ������ �� ������������.
<li>��� ������������� ������� � ������� SDK �� ������ ������� ����� ������� ���� checkserver.php, ������� ��������� ����������� �������� �������� ������� ��� ������ ������ CRest.
<li>���� � ������� ������������ ����� CRest � ��������� ���������� �� utf8, �� ���������� ������� 2 �������������� ��������:
<br/><br/>������� ����� �� ������ � �������� �� ��������� �� �����������.
<br/>� ����� settings.php �������� ��������� C_REST_CURRENT_ENCODING. ��������, ���� ������ � ��������� windows-1251 ��������� ��������� ������ ���:</li></ul>
   
```php
define('C_REST_CURRENT_ENCODING','windows-1251');
```

<h2 id="webhook">����� REST � �������������� ��������� �������</h2>

������� URL ������� � define C_REST_WEB_HOOK_URL � ����� settings.php:

```php
define('C_REST_WEB_HOOK_URL','https://xxx.bitrix24.ru/rest/1/douasdqdsxSWgc3mgc1/');
```

�������� ����� ������� � ���� index.php:

```php
require_once('src/crest.php');

// put an example below
echo '<PRE>';
print_r(CRest::call(
   'crm.lead.add',
   [
      'fields' =>[
      'TITLE' => '�������� ����',//���������*[string]
      'NAME' => '���',//���[string]
      'LAST_NAME' => '�������',//�������[string]
      ]
   ])
);

echo '</PRE>';
```

������� URL � ������� � �������� ������ �������� https://mydomain.xxx/index.php, ����� ������� ��������� ������ �������.


<h2 id="local">����� REST �� ���������� ����������</h2>

�������� ����� ������� � ���� index.php:

```php
require_once('src/crest.php');

// put an example below
echo '<PRE>';
print_r(CRest::call(
   'crm.lead.add',
   [
      'fields' =>[
      'TITLE' => '�������� ����',//���������*[string]
      'NAME' => '���',//���[string]
      'LAST_NAME' => '�������',//�������[string]
      ]
   ])
);

echo '</PRE>';
```

� �������� ���������� ���������� ������� URL ������ ���������� https://mydomain.xxx/index.php � URL ������� ��������� https://mydomain.xxx/install.php.
������� �������� ���������� client_id � client_secret ��� ����������� OAuth 2.0 � define C_REST_CLIENT_ID � C_REST_CLIENT_SECRET � ����� settings.php, ���� ��� �������� �� �������� ���������� ����������.

```php
require_once('src/crest.php');

// put an example below
echo '<PRE>';
print_r(CRest::call(
   'crm.lead.add',
   [
      'fields' =>[
      'TITLE' => '�������� ����',//���������*[string]
      'NAME' => '���',//���[string]
      'LAST_NAME' => '�������',//�������[string]
      ]
   ])
);

echo '</PRE>';
```

� ������ ��������� ���������� ������� ������ ������� ���� �� ��� ��������� ���������� � �������� ����� "��������������". ��� ����� ����� ��������� �������� install.php ����� ����, ��� �� �������� ���������� �������� C_REST_CLIENT_ID � C_REST_CLIENT_SECRET.
����� ��������� �� ������� ��������� ������ �������. ���� ������ ������������� ����������� �������� � ������ ����������� �������24, ���������� ������� � ��� �����������.


<h2 id="public">����� REST �� ��������� ����������</h2>

�������� ����� ������� � ���� index.php

```php
require_once('src/crest.php');

// put an example below
echo '<PRE>';
print_r(CRest::call(
   'crm.lead.add',
   [
      'fields' =>[
      'TITLE' => '�������� ����',//���������*[string]
      'NAME' => '���',//���[string]
      'LAST_NAME' => '�������',//�������[string]
      ]
   ])
);

echo '</PRE>';
```

�������� �������� ���������� � ����������� �������� ��� ��������� client_id � client_secret � ��� ���������� ����������.
������� �������� ���������� client_id � client_secret ��� ����������� OAuth 2.0 � define C_REST_CLIENT_ID � C_REST_CLIENT_SECRET � ����� settings.php.

```php
require_once('src/crest.php');

// put an example below
echo '<PRE>';
print_r(CRest::call(
   'crm.lead.add',
   [
      'fields' =>[
      'TITLE' => '�������� ����',//���������*[string]
      'NAME' => '���',//���[string]
      'LAST_NAME' => '�������',//�������[string]
      ]
   ])
);

echo '</PRE>';
```

� �������� ���������� �������� ������ � ������� URL ������ ���������� https://mydomain.xxx/index.php � URL ������� ��������� https://mydomain.xxx/install.php � �������� ������.
����� ���������� ������ �������� �������� ������ �, ����� �� ������ "���������� �� ����� �������24", ���������� ���� ���������� �� ����� ��������� ��� �������24.
����� ��������� �� ������� ��������� ������ ������� (� ������, ���� ������ ������������� ����������� �������� � ������ ����������� �������24, ���������� ������� � ��� �����������).
��� ��������� ��������� ���������� ���������� �������������� ����� CRest, ������������� ������ getSettingData/setSettingData, ������� ���������� ����������/����������� ������� ����������� � ��������� ����. ��� ������ �� ������������� ��� ������������ ���������� �� ���������� �������24 ������������.


##������
<a id="links"></a>

GitHub Webhooks
https://developer.github.com/webhooks/

Bitrix Rest manual
https://dev.1c-bitrix.ru/rest_help/

Bitrix Rest learning course
https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99

Bitrix IM bot platform learning course
https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=93
