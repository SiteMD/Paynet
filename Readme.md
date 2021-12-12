# API PayNet

## Установка

Установить библиотеку можно с помощью интерфейса командной строки при наличии Composer:

```bash
composer require paynet/paynet
```

## Использование

После установки потребуется подключить автозагрузчик Composer:

```php
require "vendor/autoload.php";
```

Создайте новый экземпляр класса Paynet.

```php
use Paynet\Paynet;

$paynet = new Paynet($merchant_code, $merchant_secret_key, $merchant_sale_area_code, $merchant_user, $merchant_password);
```

|                          | Тип    | Поле                     |
| :----------------------- | :----- | :----------------------- |
| $merchant_code           | string | PartnerID (MerchantCode) |
| $merchant_secret_key     | string | SecretKey                |
| $merchant_sale_area_code | string | SaleAreaCode             |
| $merchant_user           | string | User login               |
| $merchant_password       | string | Password                 |

## Доступные методы

### Версия API PayNet

```php
$paynet->version();
```

### Выбор типа подключения

Если не будет указан тип подключения, по умолчанию будет использоваться тестовый.

```php
$paynet->setPaymentMode();
```

| Параметр | Тип  | Описание       |
| :------- | :--- | :------------- |
| 0        | int  | тест           |
| 1        | int  | реальный режим |

### Язык страницы Paynet

Если не будет указан язык, по умолчанию будет использоваться 'ru'.

```php
$paynet->setLang("en");
```

| Параметр | Тип    | Описание |
| :------- | :----- | :------- |
| ru       | string | Русский  |
| ro       | string | Română   |
| en       | string | English  |

### Уникальный идентификатор заказа

```php
$paynet->setExternalID($id);
```

### Список услуг включённых в платёж

```php
$paynet->setServices($name, $description, $products);
```

| Параметр     | Тип    | Описание            |
| :----------- | :----- | :------------------ |
| $name        | string | Наименование услуги |
| $description | string | Описание услуги     |
| $products    | array  | Набор продуктов     |

В качестве параметра `$products` необходимо отправить массив с ниже указанными ключами.

| Ключ        | Тип    | Описание                         |
| :---------- | :----- | :------------------------------- |
| Name        | string | Наименовние продукта             |
| Description | string | Расширенное описание продукта    |
| UnitPrice   | int    | Стоимость одной единицы продукта |
| UnitProduct | int    | Количество продуктов             |
| Quantity    | int    | Количество продуктов             |
| Amount      | int    | Общая стоимость продукта         |
| Barcode     | int    | Бар код продукта                 |
| Code        | string | Код продукта                     |
| LineNo      | int    | Порядковый номер продукта        |
| GroupId     | string | Идентификатор группы продукта    |
| GroupName   | string | Описание группы продукта         |

Для отправки несколько продуктов необходимо создать многомерный массив `$products`.

### Информация о клиенте

```php
$paynet->setCustomer($code, $nameFirst, $nameLast, $phoneNumber, $email, $country, $city, $address);
```

| Ключ         | Тип    | Описание                  |
| :----------- | :----- | :------------------------ |
| $code        | string | Код клиента               |
| $nameFirst   | string | Имя клиента               |
| $nameLast    | string | Фамилия клиента           |
| $phoneNumber | string | Телефон клиента           |
| $email       | string | Электронный адрес клиента |
| $country     | string | Страна клиента            |
| $city        | string | Город клиента             |
| $address     | string | Адрес клиента             |

### Адрес для перенаправления при успешной оплаты

```php
$paynet->setLinkUrlSucces($url);
```

### Адрес для перенаправления при отклонение оплаты

```php
$paynet->setLinkUrlCancel($url);
```

### Инициализация оплаты

При успешной авторизации и отправки соответствующих полей будет возвращен объект содержащий форму для перенаправления к сервису Paynet.

```php
echo $paynet->initServerServerPayment()->data;
```

### Получение информации о зарегистрированном платеже

```php
$paynet->getStatusPayment($id);
```

Данный сервис метод предназначен для получения информации о платеже. Может использоваться в случае проблем связи на момент оплаты либо использовании информации об операци. Идентификатор `id` можно указать и через метод [`setExternalID($id)`](#уникальный-идентификатор-заказа).

## Дополнительные методы

### Аутентификация и получение токена

Можно использовать для проверки статуса подключении.

```php
$paynet->getToken();
```

## Пример

```php
use Paynet\Paynet;
// Подключение автозагрузчика
require "vendor/autoload.php";
// Укажите ваши данные
$merchant_code = "";
$merchant_secret_key = "";
$merchant_sale_area_code = "";
$merchant_user = "";
$merchant_password = "";
$paynet = new Paynet($merchant_code, $merchant_secret_key, $merchant_sale_area_code, $merchant_user, $merchant_password);
// Уникальный идентификатор заказа
$paynet->setExternalID(rand());
// Список услуг включённых в платёж
$name = "Service Name 1";
$description = "Service Name Decription 1";
$products = [
   array(
      "Name" => "Product 1",
      "Description" => "Description of product",
      "UnitPrice" => 11.12,
      "UnitProduct" => 2,
      "Amount" => 22.24,
      "Barcode" => 123456,
      "Code" => "Product-1",
      "LineNo" => 1,
      "GroupId" => "1",
      "GroupName" => "A group name of this product"
   ),
   array(
      "Name" => "Product 2",
      "Description" => "Description of product",
      "UnitPrice" => 11.12,
      "UnitProduct" => 1,
      "Amount" => 11.12,
      "Barcode" => 234567,
      "Code" => "Product-2",
      "LineNo" => 1,
      "GroupId" => "1",
      "GroupName" => "A group name of this product"
   )
];
$paynet->setServices($name, $description, $products);
// Информация о клиенте
$code = "Customer Code";
$nameFirst = "Payer first name";
$nameLast = "Payer last name";
$phoneNumber = "Payer phone number";
$email = "Payer email";
$country = "Payer country";
$city = "Payer city";
$address = "Payer address";
$paynet->setCustomer($code, $nameFirst, $nameLast, $phoneNumber, $email, $country, $city, $address);
// Инициализация оплаты
echo $paynet->initServerServerPayment()->data;
```
