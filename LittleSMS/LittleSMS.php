<?php

/**
 * Расширение для работы с сервисом LittleSMS.ru
 *
 * Реализовано на основе https://github.com/pycmam/littlesms
 * API сервиса: http://littlesms.ru/doc
 *
 * @author Павел Воронин <pavel.a.voronin@gmail.com>
 * @license MIT
 * @version 1.0
 */

/**
 * Copyright (c) 2012 Павел Воронин
 * 
 * Данная лицензия разрешает лицам, получившим копию данного программного
 * обеспечения и сопутствующей документации (в дальнейшем именуемыми
 * «Программное Обеспечение»), безвозмездно использовать Программное
 * Обеспечение без ограничений, включая неограниченное право на использование,
 * копирование, изменение, добавление, публикацию, распространение,
 * сублицензирование и/или продажу копий Программного Обеспечения, также как и
 * лицам, которым предоставляется данное Программное Обеспечение, при
 * соблюдении следующих условий:
 *
 * Указанное выше уведомление об авторском праве и данные условия должны быть
 * включены во все копии или значимые части данного Программного Обеспечения.
 *
 * ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО
 * ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ, ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ
 * ГАРАНТИЯМИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ
 * И ОТСУТСТВИЯ НАРУШЕНИЙ ПРАВ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ
 * НЕСУТ ОТВЕТСТВЕННОСТИ ПО ИСКАМ О ВОЗМЕЩЕНИИ УЩЕРБА, УБЫТКОВ ИЛИ ДРУГИХ
 * ТРЕБОВАНИЙ ПО ДЕЙСТВУЮЩИМ КОНТРАКТАМ, ДЕЛИКТАМ ИЛИ ИНОМУ, ВОЗНИКШИМ ИЗ,
 * ИМЕЮЩИМ ПРИЧИНОЙ ИЛИ СВЯЗАННЫМ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ
 * ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.
 */

/**
 * Класс, реализующий компонент для работы с LittleSMS.ru
 *
 * Для работы требуется PHP 5.3+ и cURL.
 *
 * Для использования компонента в приложении добавьте в config/main.php
 * следующие строки:
 *
 * 'components' => array
 * (
 *     'sms' => array
 *     (
 *         'class'    => 'application.extensions.LittleSMS.LittleSMS',
 *         'user'     => 'acc-efc322bb', // Основной или дополнительный аккаунт
 *         'apikey'   => 'ttUfFhg2',     // API-ключ аккаунта
 *         'testMode' => true            // Режим тестирования по умолчанию выключен, будьте внимательны
 *     )
 * )
 *
 * Теперь можете обращаться к API LittleSMS следующим образом:
 *
 * Yii::app()->sms->messageSend ( array ( '+79260000000', '89150000000' ), 'Hello!' );
 *
 * Список доступных API-вызовов: https://github.com/pavel-voronin/yii-littlesms
 */
class LittleSMS extends CApplicationComponent
{
	const REQUEST_SUCCESS = 'success';
	const REQUEST_ERROR = 'error';

	/**
	 * Логин основного или дополнительного аккаунта
	 * @var string
	 */
	public $user;

	/**
	 * API-ключ
	 * @var string
	 */
	public $apikey;

	/**
	 * Тестовый режим.
	 * @var boolean
	 */
	public $testMode = false;

	/**
	 * Использовать для соединения SSL (HTTPS)
	 * @var boolean
	 */
	public $useSSL = true;

	/**
	 * Api URL
	 * Меняйте на свой страх и риск!
	 * @var string
	 */
	public $url = 'littlesms.ru/api';

	/**
	 * Ответ сервера
	 * @var array
	 */
	protected $response;

	/**
	 * Инициализация компонента
	 *
	 * Если не найден cURL, будет брошено исключение.
	 */
	public function init ( )
	{
		if ( ! function_exists ( 'curl_init' ) )
			throw new CException ( 'Для работы расширения требуется cURL' );

		parent::init ( );

		Yii::trace ( 'Расширение инициализировано', 'LittleSMS' );
	}

	/**
	 * Список вызовов API
	 *
	 * Формат:
	 *
	 * array
	 * (
	 *     ...
	 *     'component/function' => array
	 *     (
	 *         'required' => array ( 'paramName1', 'paramName2' ), // Обязательные параметры
	 *         // 'required' => 'paramName1',                      // Может не быть массивом
	 *         'optional' => array ( 'paramName3', 'paramName4' ), // Необязательные параметры
	 *         'fixed' => array ( 'paramName4' => 'value4' ),      // Фиксированные параметры
	 *         'returnKey' => 'keyName' // Ключ объекта response, значение которого возвращается по-умолчанию
	 *         'returnType' => 'float' // Тип, к которому приводится возвращаемое значение
	 *     )
	 *     ...
	 * )
	 *
	 * @return array
	 */
	protected function calls ( )
	{
		return array
		(
			'user/balance' => array ( 'returnKey' => 'balance', 'returnType' => 'float' ),

			'message/send' => array ( 'returnKey' => 'messages_id', 'required' => array ( 'recipients', 'message' ), 'optional' => array ( 'sender', 'type' ), 'fixed' => array ( 'test' => $this->testMode ) ),
			'message/price' => array ( 'returnKey' => 'price', 'returnType' => 'float', 'required' => array ( 'recipients', 'message' ) ),
			'message/status' => array ( 'returnKey' => 'messages', 'required' => 'messages_id', 'optional' => 'full' ),
			'message/list' => array ( 'returnKey' => 'list', 'optional' => array ( 'limit', 'offset', 'sort' ) ),

			// Я не уделял много времени методам ниже. Вы можете помочь, наведя здесь порядок.

			'sender/create' => array ( 'required' => array ( 'name', 'description' ), 'optional' => 'use_default' ),
			'sender/confirm' => array ( 'returnKey' => 'id', 'required' => array ( 'id', 'code' ) ),
			'sender/delete' => array ( 'returnKey' => 'count', 'required' => 'id' ),
			'sender/list' => array ( 'returnKey' => 'list', 'optional' => array ( 'limit', 'offset', 'sort' ) ),
			'sender/default' => array ( 'returnKey' => 'id', 'required' => 'id' ),

			'bulk/create' => array ( 'returnKey' => 'id' ),
			'bulk/send' => array ( 'returnKey' => 'id', 'required' => 'id' ),
			'bulk/update' => array ( 'returnKey' => 'id', 'required' => 'id' ),
			'bulk/delete' => array ( 'returnKey' => 'count', 'required' => 'id' ),
			'bulk/list' => array ( 'returnKey' => 'bulks', 'optional' => array ( 'limit', 'offset', 'sort' ) ),

			'task/create' => array ( 'returnKey' => 'id' ),
			'task/update' => array ( 'returnKey' => 'id', 'required' => 'id' ),
			'task/delete' => array ( 'returnKey' => 'count', 'required' => 'id' ),
			'task/list' => array ( 'returnKey' => 'tasks', 'optional' => array ( 'limit', 'offset', 'sort' ) ),

			'tag/create' => array ( 'returnKey' => 'id', 'required' => 'name' ),
			'tag/update' => array ( 'returnKey' => 'id', 'required' => array ( 'id', 'name' ) ),
			'tag/delete' => array ( 'returnKey' => 'count', 'required' => 'id' ),
			'tag/list' => array ( 'returnKey' => 'tags', 'optional' => array ( 'limit', 'offset', 'sort' ) ),

			'contact/create' => array ( 'returnKey' => 'id', 'required' => 'phone' ),
			'contact/update' => array ( 'returnKey' => 'id', 'required' => array ( 'id', 'phone' ) ),
			'contact/delete' => array ( 'returnKey' => 'count', 'required' => 'id' ),
			'contact/list' => array ( 'returnKey' => 'contacts', 'optional' => array ( 'limit', 'offset', 'sort' ) ),

			'blacklist/append' => array ( 'returnKey' => 'count', 'required' => 'phones', 'optional' => 'description' ),
			'blacklist/delete' => array ( 'returnKey' => 'count', 'required' => 'phones' ),
			'blacklist/list' => array ( 'returnKey' => 'list', 'optional' => array ( 'limit', 'offset', 'sort' ) ),

			// С регистрацией и платёжными системами я вообще не работал, дерзайте!

			'signup/request' => array ( ),
			'signup/confirm' => array ( ),
			'signup/finish' => array ( ),

			'payment/systems' => array ( ),
			'payment/create' => array ( ),
			'payment/delete' => array ( ),
			'payment/list' => array ( ),

			// Неизвестный вызов. В списке вызовов в документации есть, а описания нет.

			'inbox/messages' => array ( )
		);
	}

	/**
	 * Обработчик вызовов API
	 *
	 * Базовое использование компонента (массив параметров):
	 *
	 * Yii::app()->sms->messageSend
	 * (
	 *     array
	 *     (
	 *         'recipients' => array ( '+7(926)000-00-00', '89030000000' ),
	 *         // Допустим вариант со строкой и разделителем — запятой
	 *         // 'recipients' => '79260000000,79030000000',
	 *         'message' => 'Hello, World!'
	 *     )
	 * )
	 *
	 * Также доступен вариант (аргументы вызова формируют массив параметров в соответствии с ключами required и optional в LittleSMS.call()):
	 *
	 * Yii::app()->sms->messageSend ( '+7(926)000-00-00, 8-903-000-0000', 'Hello, World!', 'Santa Claus', 0 );
	 *
	 * В случае, если нужно передать редкий параметр, например lifetime в message/send, пользуйтесь первым вариантом.
	 *
	 * @param string $call Имя вызова в формате camelCase (componentFunction)
	 * @param array $args Аргументы метода для преобразования в массив параметров вызова API
	 * @return mixed Ответ API на вызов, преобразованный в соответствии с ключами returnKey и returnType в LittleSMS.call()
	 */
	public function __call ( $call, $args )
	{
		$calls = $this->calls ( );

		// call красивый, как в API
		$callName = preg_replace_callback ( '#[A-Z]#', function ( $a ) { return '/' . mb_convert_case ( $a[0], MB_CASE_LOWER ); }, $call );

		if ( ! isset ( $calls[$callName] ) )
			throw new CException ( 'Неизвестный вызов' );

		$params = $calls[$callName];

		$params['required'] = isset ( $params['required'] ) ? (array) $params['required'] : array ( );
		$params['optional'] = isset ( $params['optional'] ) ? (array) $params['optional'] : array ( );
		$params['fixed'] = isset ( $params['fixed'] ) ? $params['fixed'] : array ( );

		$args = $this->populate ( $args, $params['required'], $params['optional'] );
		$args = array_merge ( $args, $params['fixed'] );

		$response = $this->request ( $callName, $args );

		if ( $response['status'] == self::REQUEST_SUCCESS )
			if ( isset ( $params['returnKey'] ) )
				if ( isset ( $response[$params['returnKey']] ) )
				{
					if ( isset ( $params['returnType'] ) )
						if ( ! settype ( $response[$params['returnKey']], $params['returnType'] ) )
							throw new CException ( 'Тип не поддерживается' );

					return $response[$params['returnKey']];
				}
				else
					throw new CException ( 'Ключ не найден' );

		return $response['status'] == self::REQUEST_SUCCESS;
	}

	/**
	 * Формирование массива параметров вызова из аргументов
	 * @param mixed $args Аргументы, переданные при вызове метода
	 * @param array $required Массив обязательных параметров
	 * @param array $optional Массив дополнительных параметров
	 * @return array Массив параметров вызова
	 */
	protected function populate ( $args, $required, $optional )
	{
		$allParams = array_merge ( $required, $optional );

		// Особый случай
		if ( count ( $args ) === 1 )
			// Проверка, массив ли первый аргумент...
			if ( is_array ( $args[0] ) )
				// ...и все ли ключи — строки
				if ( array_reduce ( array_keys ( $args[0] ), function ( $prev, $curr ) { return $prev && is_string ( $curr ); }, true ) )
				{
					foreach ( $required as $requiredParam )
						if ( ! isset ( $args[0][$requiredParam] ) )
							throw new CException ( 'Мало параметров' );

					return $args[0];
				}

		if ( count ( $args ) < count ( $required ) )
			throw new CException ( 'Мало параметров' );

		if ( count ( $args ) > count ( $allParams ) )
			throw new CException ( 'Много параметров' );

		$params = array ( );

		foreach ( $args as $id => $arg )
			$params[$allParams[$id]] = $arg;

		return $params;
	}

	/**
	 * Вызов API
	 * @param string $call Имя вызова
	 * @param array $params Параметры вызова
	 * @return array Ответ сервера
	 */
	protected function request ( $call, array $params = array ( ) )
	{
		$params = array_map ( function ( $val ) { return join ( ',', (array) $val ); }, $params );
		$params = array_merge ( $params, array ( 'user' => $this->user, 'apikey' => $this->apikey ) );

		$url = ( $this->useSSL ? 'https://' : 'http://' ) . $this->url . '/' . $call;
		$post = http_build_query ( $params, '', '&' );

		$ch = curl_init ( $url );

		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );

		Yii::trace ( 'Вызов: ' . $url . ':' . PHP_EOL . print_r ( $params, true ), 'LittleSMS' );

		$response = curl_exec ( $ch );

		curl_close ( $ch );

		return $this->response = json_decode ( $response, true );
	}

	/**
	 * Возвращает ответ сервера на последний запрос
	 * @return array
	 */
	public function getResponse ( )
	{
		return $this->response;
	}
}
