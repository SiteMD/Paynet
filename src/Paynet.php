<?php

/**
 * @author Iulian Ceapa <info@sitemd.net>
 * @copyright © 2021-2022 SiteMD
 */

namespace Paynet;

use Paynet\PaynetResponse;
use Paynet\Exception\PaynetException;

/*
 * Этот код предназначен для использования специалистами
 * ответственными за интеграцию электронного магазина с модулем платежного шлюза.
 */

/**
 * E-Commerce Paynet.
 */
class Paynet
{
	const API_VERSION = 'v05';

	const PAYNET_URL_CLIENT_SERVER = 'https://paynet.md/acquiring/setecom';
	const PAYNET_URL_SERVER_SERVER = 'https://paynet.md/acquiring/getecom';
	const PAYNET_URL_API = 'https://paynet.md:4448';

	const PAYNET_TEST_URL_CLIENT_SERVER = 'https://test.paynet.md/acquiring/setecom';
	const PAYNET_TEST_URL_SERVER_SERVER = 'https://test.paynet.md/acquiring/getecom';
	const PAYNET_TEST_URL_API = 'https://api-merchant.test.paynet.md';

	private $merchant_code;
	private $merchant_secret_key;
	private $merchant_sale_area_code;
	private $merchant_user;
	private $merchant_password;

	public $mode = 0;
	public $externalId;
	public $lang = 'ru';
	public $currency = 498; // ISO 4217
	public $expiryDate; // ISO 8601
	public $urlSucces;
	public $urlCancel;

	public $services = array();
	public $customer = array();

	/**
	 * @param string $merchant_code Код продавца
	 * @param string $merchant_secret_key Секретный ключ продавца
	 * @param string $merchant_sale_area_code
	 * @param string $merchant_user Пользователь
	 * @param string $merchant_password Пароль пользователя
	 */
	public function __construct(string $merchant_code, string $merchant_secret_key, string $merchant_sale_area_code, string $merchant_user, string $merchant_password)
	{
		$this->merchant_code = $merchant_code;
		$this->merchant_secret_key = $merchant_secret_key;
		$this->merchant_sale_area_code = $merchant_sale_area_code;
		$this->merchant_user = $merchant_user;
		$this->merchant_password = $merchant_password;

		$this->expiryDate = $this->expiryDate();
	}

	/**
	 * Версия API PayNet.
	 *
	 * @return string
	 */
	public function version(): string
	{
		return self::API_VERSION;
	}

	/**
	 * Выбор типа подключения.
	 *
	 * @param integer $mode 0 - тест | 1 - реальный режим
	 * @return void
	 */
	public function setMode(int $mode): void
	{
		$this->mode = $mode;
	}

	/**
	 * Язык страницы Paynet.
	 *
	 * @param string $lang Язык способов оплаты: a) ro b) ru c) en
	 * @return void
	 */
	public function setLang(string $lang): void
	{
		$this->lang = $lang;
	}

	/**
	 * Уникальный идентификатор заказа.
	 *
	 * @param int $id Уникальный идентификатор заказа
	 * @return void
	 */
	public function setExternalID(int $id): void
	{
		if (is_numeric($id)) $this->externalId = (int)$id;
	}

	/**
	 * Список услуг включённых в платёж.
	 *
	 * Для отправки несколько продуктов необходимо создать многомерный массив.
	 * 
	 * @param string $name Наименование услуги
	 * @param string $description Описание услуги
	 * @param array $products Набор продуктов
	 * 
	 * Пример:
	 * ```
	 * $products = array(
	 * 	"Name" => "", // Наименовние продукта
	 * 	"Description" => "", // Расширенное описание продукта
	 * 	"UnitPrice" => 0, // Стоимость одной единицы продукта
	 * 	"UnitProduct" => 0, // Количество продуктов
	 * 	"Quantity" => 0, // Количество продуктов
	 * 	"Amount" => 0, // Стоимость продукта
	 * 	"Barcode" => 0, // Бар код продукта
	 * 	"Code" => "", // Код продукта
	 * 	"LineNo" => 0, // Порядковый номер продукта
	 * 	"GroupId" => "", // Идентификатор группы продукта
	 * 	"GroupName" => "", // Описание группы продукта
	 * );
	 * ```
	 * @return void
	 */
	public function setServices(string $name, string $description, array $products): void
	{
		// Проверка на многомерный массив
		if (count($products) == count($products, COUNT_RECURSIVE)) {
			$products = [$products];
		}

		$services = [
			'Name' => $name,
			'Description' => $description,
			'Products' => $products
		];
		array_push($this->services, $services);
	}

	/**
	 * Информация о клиенте.
	 *
	 * @param string $code Код клиента 
	 * @param string $nameFirst Имя клиента
	 * @param string $nameLast Фамилия клиента
	 * @param string $phoneNumber Телефон клиента
	 * @param string $email Электронный адрес клиента 
	 * @param string $country Страна клиента
	 * @param string $city Город клиента
	 * @param string $address Адрес клиента
	 * @return void
	 */
	public function setCustomer(string $code, string $nameFirst, string $nameLast, string $phoneNumber, string $email, string $country, string $city, string $address): void
	{
		$customer = [
			'Code' => $code,
			'NameFirst' => $nameFirst,
			'NameLast' => $nameLast,
			'PhoneNumber' => $phoneNumber,
			'Email' => $email,
			'Country' => $country,
			'City' => $city,
			'Address' => $address
		];
		$this->customer = $customer;
	}

	/**
	 * Адрес для перенаправления при успешной оплаты.
	 *
	 * @param string $url Адрес
	 * @return void
	 */
	public function setUrlSucces($url): void
	{
		$this->urlSucces = $url;
	}

	/**
	 * Адрес для перенаправления при отклонение оплаты.
	 *
	 * @param string $url Адрес
	 * @return void
	 */
	public function setUrlCancel($url): void
	{
		$this->urlCancel = $url;
	}

	/**
	 * Срок действия операции в часах.
	 *
	 * @param int $addHours
	 * @return datetime
	 */
	protected function expiryDate(int $addHours = 4)
	{
		$date = new \DateTime('now', new \DateTimeZone('Europe/Chisinau'));
		$date->add(new \DateInterval('PT' . $addHours . 'H'));
		return $date->format('Y-m-d') . 'T' . $date->format('H:i:s');
	}

	/**
	 * Вызов API.
	 *
	 * @param string $path Доп. адрес
	 * @param string $method Метод подключения [POST|GET]
	 * @param array $params Параметры при отправке. пример: `?grant_type=password&username=username&password=password`
	 * @param array $headers Заголовок, пример: `Content-Type: application/json`
	 * @return object
	 */
	protected function callApi(string $path, string $method, array $params = array(), array $headers = array()): object
	{
		// URL подключения
		if ($this->mode === 0) {
			$url = self::PAYNET_TEST_URL_API;
		} else {
			$url = self::PAYNET_URL_API;
		}

		$paynetResponse = new PaynetResponse;
		$guzzleClient = new \GuzzleHttp\Client(['base_uri' => $url]);

		try {
			if (empty($headers)) {
				$response = $guzzleClient->request($method, $path, [
					'form_params' => $params
				]);
			} else {
				$typeSend = ($method == 'POST') ? 'json' : 'query';
				$response = $guzzleClient->request($method, $path, [
					'headers' => $headers,
					$typeSend => $params
				]);
			}

			if ($response->getStatusCode() == 200) {
				$paynetResponse->code = $response->getStatusCode();
				$paynetResponse->data = json_decode($response->getBody(), true);
			}
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			// Catch all 4XX errors 
			if ($e->hasResponse()) {
				$response = $e->getResponse();
				$paynetResponse->code = $response->getStatusCode();
				$error = json_decode($response->getBody(), true);
				$paynetResponse->message = $response->getReasonPhrase() . ', `' . $error['error'] . '`';
			}
		}

		return $paynetResponse;
	}

	/**
	 * Аутентификация и получение покена.
	 *
	 * @return object
	 */
	public function getToken(): object
	{
		$path = 'auth';
		$params = [
			'grant_type' => 'password',
			'username' => $this->merchant_user,
			'password' => $this->merchant_password
		];

		$api = $this->callApi($path, 'POST', $params);
		$paynetResponse = new PaynetResponse;

		if ($api->code == 200) {
			$paynetResponse->code = $api->code;
			$paynetResponse->data = [
				'Authorization' => $api->data['token_type'] . ' ' . $api->data['access_token'],
				'Content-Type' => 'application/json'
			];
		} else {
			throw new PaynetException('Ошибка получения токена, логин или пароль неверный.');
		}

		return $paynetResponse;
	}

	/**
	 * Инициализация оплаты.
	 *
	 * @return string
	 */
	public function initPayment(): string
	{
		if (empty($this->externalId)) throw new PaynetException('Не указан идентификатор заказа.');
		if (empty($this->services)) throw new PaynetException('Не указан список услуг включённых в платёж.');
		if (empty($this->customer)) throw new PaynetException('Не указан информация о клиенте.');

		// Расчет общей суммы
		foreach ($this->services as $key => $value) {
			$amount = 0;
			foreach ($this->services[$key]['Products'] as $item) {
				$amount += $item['Amount'];
			}
			$this->services[$key]['Amount'] = $amount * 100;
		}

		$path = 'api/Payments/Send';
		$params = [
			'MerchantCode' => $this->merchant_code,
			'SaleAreaCode' => $this->merchant_sale_area_code,
			'Invoice' => $this->externalId,
			'Currency' => $this->currency,
			'Customer' => $this->customer,
			'Services' => $this->services,
			'ExpiryDate' => $this->expiryDate,
			'SignVersion' => self::API_VERSION
		];

		$token = $this->getToken();

		$api = $this->callApi($path, 'POST', $params, $token->data);
		$form = '';
		if ($api->code == 200) {
			$form = '<style>#paynet{text-align:center;position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);display:flex;flex-direction:column}#paynet svg{height:42.5px}#paynet div{font:15px Arial;margin: 0.75rem 0;}#paynet button{font:15px Arial;line-height:1.5;color:#fff;display:inline-block;padding:0.438rem 0.75rem;background:#52616D;border:unset;border-radius:5px}#paynet button:hover{cursor:pointer}</style>';
			// URL подключения
			if ($this->mode === 0) {
				$form .= '<form id="paynet" method="POST" action="' . self::PAYNET_TEST_URL_SERVER_SERVER . '">';
			} else {
				$form .= '<form id="paynet" method="POST" action="' . self::PAYNET_URL_SERVER_SERVER . '">';
			}
			$form .= '<input type="hidden" name="operation" value="' . $api->data['PaymentId'] . '"/>' .
				'<input type="hidden" name="LinkUrlSucces" value="' . $this->urlSucces . '"/>' .
				'<input type="hidden" name="LinkUrlCancel" value="' . $this->urlCancel . '"/>' .
				'<input type="hidden" name="ExpiryDate" value="' . $this->expiryDate . '"/>' .
				'<input type="hidden" name="Signature" value="' . $api->data['Signature'] . '"/>' .
				'<input type="hidden" name="Lang" value="' . $this->lang . '"/>' .
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 243.19 62.43"><defs><style>.a{fill:#ff6665;}.b{fill:#52616d;}</style></defs><path class="a" d="M5.11,31.38a3.51,3.51,0,0,1,.46-1.91,1.78,1.78,0,0,1,1.61-.73,1.79,1.79,0,0,1,1.56.73,9.8,9.8,0,0,1,.95,1.87L10,35.07a19.74,19.74,0,0,1,6.47-4.95,18.62,18.62,0,0,1,8.21-1.82,18.62,18.62,0,0,1,7.52,1.52A19.26,19.26,0,0,1,42.42,40.11a18.91,18.91,0,0,1,1.52,7.56,18.63,18.63,0,0,1-1.52,7.5,19.67,19.67,0,0,1-4.13,6.16,19.35,19.35,0,0,1-6.12,4.16A18.79,18.79,0,0,1,24.65,67a18.7,18.7,0,0,1-7.93-1.7,20,20,0,0,1-6.34-4.62V77.51a2.8,2.8,0,0,1-.71,1.93,2.43,2.43,0,0,1-1.89.79,2.57,2.57,0,0,1-1.9-.79,2.64,2.64,0,0,1-.77-1.93Zm5.47,16.29a13.45,13.45,0,0,0,1.1,5.41,14.13,14.13,0,0,0,7.47,7.5,14.09,14.09,0,0,0,11,0,13.87,13.87,0,0,0,4.44-3,14.24,14.24,0,0,0,3-4.46,13.78,13.78,0,0,0,1.07-5.41,14.25,14.25,0,0,0-1.07-5.49,14.08,14.08,0,0,0-3-4.5,14.2,14.2,0,0,0-15.45-3,14.12,14.12,0,0,0-8.57,13Z" transform="translate(-5.11 -17.88)"/><path class="a" d="M51.24,47.67A19.27,19.27,0,0,1,56.92,34a19.67,19.67,0,0,1,6.16-4.13,18.63,18.63,0,0,1,7.5-1.52,18.82,18.82,0,0,1,8.15,1.78,19.78,19.78,0,0,1,6.44,4.82l.29-3.56a8.32,8.32,0,0,1,1-1.87A1.79,1.79,0,0,1,88,28.74a1.76,1.76,0,0,1,1.62.73A3.6,3.6,0,0,1,90,31.38V63.73a2.73,2.73,0,0,1-.75,2,2.54,2.54,0,0,1-1.93.79,2.39,2.39,0,0,1-1.86-.79,2.75,2.75,0,0,1-.73-2V60.4a21.19,21.19,0,0,1-6.35,4.79A17.43,17.43,0,0,1,70.58,67a18.8,18.8,0,0,1-7.5-1.52A19.53,19.53,0,0,1,52.76,55.17,18.8,18.8,0,0,1,51.24,47.67Zm5.27,0a13.62,13.62,0,0,0,1.09,5.41,14.35,14.35,0,0,0,3,4.46,14,14,0,0,0,4.48,3,14.09,14.09,0,0,0,11,0,13.87,13.87,0,0,0,4.44-3,14.24,14.24,0,0,0,3-4.46,13.78,13.78,0,0,0,1.07-5.41,14.25,14.25,0,0,0-1.07-5.49,14.08,14.08,0,0,0-3-4.5,14.2,14.2,0,0,0-15.45-3,14,14,0,0,0-4.48,3,14.13,14.13,0,0,0-4.09,10Z" transform="translate(-5.11 -17.88)"/><path class="a" d="M114.89,58l12.6-27.57A2.6,2.6,0,0,1,128.93,29a2.66,2.66,0,0,1,3.45,1.5,2.68,2.68,0,0,1-.06,2.06L117.4,64.74l-.69,1.66a67.36,67.36,0,0,1-3.24,6.31A19.12,19.12,0,0,1,110,77.06a11.75,11.75,0,0,1-4.21,2.5,17.07,17.07,0,0,1-5.52.75h-.2a2.69,2.69,0,0,1-1.87-.71,2.41,2.41,0,0,1-.77-1.85,2.45,2.45,0,0,1,.79-1.86,2.69,2.69,0,0,1,1.89-.73h.32a11.48,11.48,0,0,0,4-.47A7.48,7.48,0,0,0,107.33,73a12.78,12.78,0,0,0,2.22-3q1-1.78,2-4.18l.61-1.5L97.49,32.55a2.52,2.52,0,0,1-.08-2.06A2.73,2.73,0,0,1,100.86,29a2.55,2.55,0,0,1,1.46,1.44Z" transform="translate(-5.11 -17.88)"/><path class="b" d="M140.18,31.38a3.51,3.51,0,0,1,.47-1.91,1.76,1.76,0,0,1,1.6-.73,2,2,0,0,1,1.64.75,6.57,6.57,0,0,1,1,1.93l.33,4.94a18.34,18.34,0,0,1,2.55-3.26A16.49,16.49,0,0,1,151,30.55a15.28,15.28,0,0,1,8.12-2.25,15.28,15.28,0,0,1,6.77,1.44,14,14,0,0,1,5,3.95,17.68,17.68,0,0,1,3.06,5.88A24,24,0,0,1,175,46.78V63.89a2.56,2.56,0,0,1-.73,1.88,2.46,2.46,0,0,1-1.86.75,2.66,2.66,0,0,1-1.89-.75,2.5,2.5,0,0,1-.79-1.88v-17a17.6,17.6,0,0,0-.75-5.19,13,13,0,0,0-2.17-4.25,10,10,0,0,0-3.54-2.88,10.84,10.84,0,0,0-4.85-1,11.54,11.54,0,0,0-5.12,1.15,13.17,13.17,0,0,0-4.1,3.08,14.81,14.81,0,0,0-3.71,9.83v16.1a2.78,2.78,0,0,1-.71,2,2.39,2.39,0,0,1-1.88.79,2.56,2.56,0,0,1-1.91-.79,2.7,2.7,0,0,1-.77-2Z" transform="translate(-5.11 -17.88)"/><path class="b" d="M189.76,50.07a13.11,13.11,0,0,0,1.5,4.64,13.37,13.37,0,0,0,3,3.69,13.93,13.93,0,0,0,4.11,2.45,13.66,13.66,0,0,0,4.91.89,14.37,14.37,0,0,0,5.63-1.13,11.2,11.2,0,0,0,4.38-3.21,2.82,2.82,0,0,1,2.64-.57,2.31,2.31,0,0,1,1.21,1.06,2.68,2.68,0,0,1,.55,1.74,2.4,2.4,0,0,1-1,1.83,18,18,0,0,1-5.49,3.95,18.19,18.19,0,0,1-7.93,1.6,18.75,18.75,0,0,1-7.52-1.52,19.59,19.59,0,0,1-10.3-10.32,19.41,19.41,0,0,1,0-15.06A19.84,19.84,0,0,1,189.6,34a19.41,19.41,0,0,1,6.14-4.15,18.58,18.58,0,0,1,7.52-1.52,19,19,0,0,1,7.5,1.5,19.25,19.25,0,0,1,6.14,4.11A19.92,19.92,0,0,1,221.06,40a19.06,19.06,0,0,1,1.58,7.48,2.47,2.47,0,0,1-2.6,2.56Zm27.44-4.46a12.76,12.76,0,0,0-1.47-4.79,14.28,14.28,0,0,0-17.5-6.32A13.94,13.94,0,0,0,194,37a13.09,13.09,0,0,0-3,3.81,13.43,13.43,0,0,0-1.44,4.77Z" transform="translate(-5.11 -17.88)"/><path class="b" d="M239.5,28.7h6.16a2.63,2.63,0,0,1,2.64,2.64,2.53,2.53,0,0,1-.77,1.88,2.6,2.6,0,0,1-1.87.75H239.5v29.8a2.64,2.64,0,0,1-.77,1.94,2.64,2.64,0,0,1-3.73,0,2.64,2.64,0,0,1-.77-1.94V34h-4.66a2.62,2.62,0,0,1-1.91-.77,2.54,2.54,0,0,1-.77-1.86,2.58,2.58,0,0,1,.77-1.87,2.62,2.62,0,0,1,1.91-.77h4.66V20.51a2.53,2.53,0,0,1,.77-1.88,2.62,2.62,0,0,1,1.91-.75,2.49,2.49,0,0,1,1.86.75,2.58,2.58,0,0,1,.73,1.88Z" transform="translate(-5.11 -17.88)"/></svg>' .
				'<div>Invoice: ' . $this->externalId . '</div>' .
				'<button>Continue payment</button>' .
				'<div>redirect after 3 sec.</div>' .
				'</form>' .
				'<script nonce="app">setTimeout(function(){document.getElementById("paynet").submit();}, 3000);</script>';
		}
		return $form;
	}

	/**
	 * Получение информации о зарегистрированном платеже.
	 * 
	 * Данный сервис метод предназначен для получения информации о платеже. Может использоваться в 
	 * случае проблем связи на момент оплаты либо использовании информации об операци.
	 *
	 * @param int $id Уникальный идентификатор заказа
	 * @return object
	 */
	public function getStatus(int $id): object
	{
		$path = 'api/Payments';
		$params = ['ExternalID' => $id];

		$token = $this->getToken();
		$paynetResponse = new PaynetResponse();

		$api = $this->callApi($path, 'GET', $params, $token->data);

		$paynetResponse->code = $api->code;
		$paynetResponse->message = $api->message;
		$paynetResponse->data = $api->data;

		return $paynetResponse;
	}
}
