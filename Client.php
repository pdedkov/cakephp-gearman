<?php
namespace Gearman;

use Config\Object as Base;

class Client extends Base {
	const WORKLOAD_TEST = 'test client';

	/**
	 * Клиент
	 * @var object
	 */
	protected $_Client = null;

	/**
	 * Настройки по умолчанию
	 * @var array
	 */
	protected static $_defaults = [
		'server' => ['host' => 'localhost', 'port' => 4730],
		'background'	=> false,
		'priority'		=> Priority::NORMAL,
		'connect'		=> true
	];


	/**
	 * Общие данные обработки
	 * @var mixed
	 */
	protected $_data = null;

	protected $_connected = false;

	/**
	 * Данные постобработки
	 * @var mixed
	 */
	protected $_out = null;

	public function __construct($namespace = null, $config = []) {
		static::$_defaults = \Hash::merge(self::$_defaults, static::$_defaults);

		if ($namespace) {
			$config = $this->_config(__NAMESPACE__, $config);
		} else {
			$namespace = __NAMESPACE__;
		}
		parent::__construct($namespace, $config);

		// соединение
		if ($this->_config['connect']) {
			$this->_connect();
		}
	}

	/**
	 *  Подключение к серверу
	 *
	 * @param bool $reconnect пепеподключение, если нужно
	 * @throws Exception
	 * @return bool
	 */
	protected function _connect($reconnect = true) {
		// уже подключены
		if (!$reconnect && $this->_connected) {
			return $this->_connected;
		}

		// убиваем старое соедение
		unset($this->_Client);
		$this->_connected = false;

		// ну и всё заново
		$this->_Client = new \GearmanClient();

		$server = (!empty($this->_config['servers']) && empty($this->_config['server']))
			? $this->_config['servers'][0]
			: $this->_config['server']
		;
		$this->_Client->addServer($server['host'], $server['port']);

		// учитывая, что addServer всегда возвращает true, делаем дополнительный ping сервера для проверки его реакции
		if (!$this->_Client->ping(self::WORKLOAD_TEST)) {
			throw new Exception('Не удалось соединиться с сервером');
		}

		$this->_connected = true;

		if (is_callable([$this, 'done'])) {
			if (!$this->_Client->setCompleteCallback([$this, 'done'])) {
				throw new Exception('Не удалось установить обработчик');
			}
		}

		if (is_callable([$this, 'exception'])) {
			if (!$this->_Client->setExceptionCallback([$this, 'exception'])) {
				throw new Exception('Не удалось установить обработчик исключений');
			}
		}

		return $this->_connected;
	}

	/**
	 * magic для вызовов методово
	 * @param string $method название метода
	 * @param array $arguments список агрументов
	 * @return mixed
	 */
	public function __call($method, $arguments) {
		return call_user_func_array([$this->_Client, $method], $arguments);
	}

	/**
	 * Запуск задачи на выполнение
	 *
	 * @param bool $force
	 * @throws Exception
	 * @return mixed
	 */
	public function run($force = false) {
		if (!$this->_Client->runTasks()) {
			throw new Exception('Не удалось запустить выполнение задач: ' . $this->_Client->error());
		}

		return ($this->_config['background'] || $force) ? true : $this->_out;
	}

	/**
	 * Определяем название метода gearmand
	 *
	 * @param string $function имя функции
	 * @return имя метода
	 */
	protected function _function($function) {
		$class = get_class($this);

		return (new \ReflectionClass($class))->getNamespaceName() . '\\' . $function;
	}

	/**
	 * Заглушка для добавления задачи с нормальным приоритетом
	 * @param string $name функция
	 * @param string $workload данные
	 */
	public function addTaskNormal($name, $workload) {
		return $this->_Client->addTask($name, $workload);
	}

	public function addTaskNormalBackground($name, $workload) {
		return $this->_Client->addTaskBackGround($name, $workload);
	}

	/**
	 * Выбор метода для добавления задачи в gearman
	 *
	 * @param mixed $data данные
	 * @param bool $background в фоне или нет
	 * @return string метод
	 */
	protected function _method($data = null) {
		$priority = $this->_config['priority'];

		if (is_array($data) && array_key_exists('priority', $data)) {
			$priority = Priority::fromString($data['priority']);
		}

		$priority = Priority::toString($priority);

		$method = ($this->_config['background']) ?
			'addTask' . $priority . 'Background' :
			'addTask'  . $priority
		;

		return $method;
	}

	/**
	 * callback на выполение обработки
	 * @param \GearmanTask $Task
	 * @return int
	 */
	public function done(\GearmanTask $Task) {
		$this->_out = unserialize($Task->data());

		return GEARMAN_SUCCESS;
	}

	/**
	 * Обработка исключительных ситуаций
	 *
	 * @param \GearmanTask $Task
	 * @throws AppException
	 * @return int
	 */
	public function exception(\GearmanTask $Task) {
		$e = unserialize($Task->data());

		if (is_a($e, 'Exception')) {
			throw $e;
		}

		return GEARMAN_SUCCESS;
	}
}