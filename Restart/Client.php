<?php
namespace Gearman\Restart;

use Gearman\Client as Base;
use Gearman\Exception;
use Gearman\Priority;

class Client extends Base {
	public function add($task = null, $background = true, $data = []) {
		$method = $this->_method(Priority::PRIORITY_HIGH, $background);

		if (!$this->{$method}($task, serialize($data))) {
			throw new Exception('Не удалось поставить задачу на перезагрузку');
		}

		return true;
	}

	public function run() {
		parent::run();

		return true;
	}
}