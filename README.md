#yii-littlesms

Расширение для Yii Framework по работе с API сервиса [LittleSMS.ru]

##Требования

- Yii Framework 1.1+
- PHP 5.3+ (анонимные функции)
- cURL (запросы к API)

##Установка

Загрузите yii-littlesms с github:

```bash
cd protected/extensions
git clone git://github.com/pavel-voronin/yii-littlesms.git
```

В ```main.php``` внесите следующие строки:

```php
'components' => array
(
    'sms' => array
    (
        'class'    => 'application.extensions.yii-littlesms.LittleSMS',
        'user'     => 'acc-efc322bb', // Основной или дополнительный аккаунт
        'apikey'   => 'ttUfFhg2',     // API-ключ аккаунта
        'testMode' => true            // Режим тестирования по умолчанию выключен, будьте внимательны
    )
)
```

##Использование

Расширение поддерживает все вызовы API LittleSMS. Последнюю редакцию документации по вызовам вы можете найти на [официальном сайте][1].

###Базовый формат вызова:

```php
Yii::app()->sms->messageSend
(
    array
    (
        'recipients' => array ( '+7(926)000-00-00', '89030000000' ),
        // Допустим вариант со строкой и разделителем — запятой
        // 'recipients' => '79260000000,7-903-000-00-00',
        'message' => 'Hello, World!'
    )
)
```

Имя вызова в ```camelCase``` в формате ```componentFunction``` (см. официальную документацию). Единственный аргумент — массив параметров вызова.

###Альтернативный формат вызова:

```php
Yii::app()->sms->messageSend ( '+7(926)000-00-00, 8-903-000-0000', 'Hello, World!', 'Santa Claus' );
```

Аргументы транслируются в параметры в соответствии с ключами ```required``` и ```optional``` в ```LittleSMS.calls()```.

В случае, если нужно передать редкий параметр, например lifetime в message/send, пользуйтесь базовым форматом.

##Changelog

### Версия 1.0

- Первая версия
- Протестирована работа компонентов ```user``` и ```message```. К тестированию и уточнению других в ```LittleSMS.calls()``` приглашаю всех желающих. 

[LittleSMS.ru]: http://littlesms.ru
[1]: http://littlesms.ru/doc