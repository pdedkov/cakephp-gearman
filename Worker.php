<?php
namespace Gearman;

use Config\Object as Base;

class Worker extends Base {
	const WORKLOAD_TEST = 'test worker';

	protected $_timeLimit = null;

	/**
	 * Время запуска worker-а
	 *
	 * @var int
	 */
	protected $_started = null;

	/**
	 * Бд, которую используют воркеры для работы
	 * @var object
	 */
	protected $_Db = null;

	/**
	 * Переданные в worker данные для обработки
	 * @var mixed
	 */
	protected $_in = null;

	/**
	 * Результат работы возвращаем назад
	 * @var mixed
	 */
	protected $_out = null;

	/**
	 *
	 * Воркер
	 * @var object
	 */
	protected $_Worker = null;

	/**
	 * Настройки по умолчанию
	 * @var array
	 */
	protected static $_defaults = [
		'servers' => [
			['host' => 'localhost', 'port' => 4730]
		],
		// кол-во циклов работы worker-а
		'count' => 1
	];

	/**
	 * Функция, которая выполняется
	 * @var string
	 */
	protected $_function = null;

	public function __construct($config = []) {
		parent::__construct(__NAMESPACE__, $config);

		set_time_limit(0);
		ini_set('memory_limit', '1024M');

		$this->_Worker = new \GearmanWorker();

		foreach ($this->_config['servers'] as $server) {
			$this->_Worker->addServer($server['host'], $server['port']);

		}

		// учитывая, что addServer всегда возвращает true, делаем дополнительный ping сервера для проверки его реакции
		if (!$this->_Worker->echo(self::WORKLOAD_TEST)) {
			throw new Exception('Не удалось соединиться с сервером');
		}

		$this->_Db = new Db($this->_config['Db']);

		// последний рестарт
		$this->_started = $this->_Db->lastRestart();

		if (!empty($this->_function)) {
			if (is_string($this->_function)) {
				$this->_Worker->addFunction($this->_function($this->_function), [$this, 'doJob']);
			} elseif (is_array($this->_function)) {
				foreach ($this->_function as $name => $callback) {
					$this->_Worker->addFunction($this->_function($name), is_array($callback) ? $callback : [$this, $callback]);
				}
			}
		}

		$this->_Worker->addFunction("restart_{$this->_started}", [$this, 'halt']);
	}

	/**
	 * Запускаем worker в работу
	 *
	 * @return bool
	 */
	public function work() {
		$count = 0;

		while($this->_Worker->work()) {
			if (
				($this->_Worker->returnCode() != GEARMAN_SUCCESS)
				|| ++$count >= (int)@$this->_config['count']
			) {
				break;
			}
		}

		return GEARMAN_SUCCESS;
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
	 * Работаем!
	 */
	public function doJob($Job) {
		// загружаем данные из задачи
		$this->_in = unserialize($Job->workload());
		if (!empty($this->_timeLimit)) {
			set_time_limit($this->_timeLimit);
		}

		return true;
	}

	/**
	 * Рестарт worker-а
	 *
	 * @return bool
	 */
	public function halt($job) {
		$job->sendComplete(true);
	}
}