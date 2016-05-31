<?php
namespace Gearman;

use Db\Redis\Instance as Base;

class Db extends Base {
	protected static $_defaults = [
		'server' => [
			'prefix'	=> 'gearman'
		]
	];

	public function __construct($config = []) {
		parent::__construct(null, $config);
	}
	
	/**
	 * timestamp послдеднего restart-а
	 *
	 * @return int
	 */
	public function lastRestart() {
		return (int)$this->get($this->_key($this->_config['keys']['restart']));
	}

	/**
	 * Ставим timestamp послдеднего restart-а
	 *
	 * @return int
	 */
	public function setRestart() {
		return (int)$this->set($this->_key($this->_config['keys']['restart']), time());
	}

	/**
	 * проверяем необходимость рестарта
	 *
	 * @param int $time время запуска worker-а
	 *
	 * @return bool
	 */
	public function needRestart($time) {
		$date = $this->lastRestart();

		return ($date > $time);
	}
}