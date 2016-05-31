<?php
namespace Gearman;

use Config\Object as Base;

class Manager extends Base {
	public static $_defaults = [
		'Db'	=> [
			'keys' => ['restart' => 'date_restart']
		],
		'server' => [
			'host'	=> '127.0.0.1',
			'port'	=> 4730,
			'timeout' => 5
		]
	];

	protected $_Db = null;

	public function __construct($config = []) {
		parent::__construct(__NAMESPACE__, $config);

		$this->_Db = new Db($this->_config['Db']);
	}

	public function reload() {
		$last = $this->_Db->lastRestart();

		$this->_Db->setRestart();

		$Client = new Restart\Client();

		// проверяем кол-во зкапущенных worker-ов
		$workers = $this->getWorkers();

		$count = (int)@$workers["restart_{$last}"][3];
		for ($i = 0; $i < $count; $i++) {
			$Client->add("restart_{$last}");
		}

		return $Client->run();
	}

	/**
	 * Получение списка worker-ов
	 *
	 * @return array массив доступных worker-ов с их загрузкой и тд
	 */
	public function getWorkers() {
		// получаем статистику по worker-ам
		\App::uses('CakeSocket', 'Network');

		$Socket = new \CakeSocket($this->_config['server']);

		$Socket->connect();

		$workers = array();

		// делаем 3 замера с интервалом в 2 секунды для получение точного результата
		for ($i = 0; $i <= 3; $i++) {
			$Socket->write("status\n");
			$content = $Socket->read(50000);

			$answers = explode("\n", trim($content));

			foreach ($answers as $string) {
				$temp = explode("\t", $string);
				$title = trim($temp[0]);
				if (strpos($title, 'restart') !== false || strpos($title, '.') !== false) {
					continue;
				}

				if (!empty($workers[$title])) {
					// тут нас интересует только макс. значение доступных worker-ов
					$workers[$title][3] = (intval($workers[$title][3]) < intval($temp[3])) ? $temp[3] : $workers[$title][3];
				} else {
					$workers[$title] = $temp;
				}
			}
			sleep(2);
		}

		$Socket->disconnect();

		return $workers;
	}
}