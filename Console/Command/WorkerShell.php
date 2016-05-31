<?php
class WorkerShell extends AppShell {
	/**
	 * Запуск воркера
	 * @param string $worker имя воркера
	 */
	public function run() {
		try {
			return !$this->_class($this->args[0])->work();
		} catch (\Exception $e) {
			$this->out($e->getMessage());
		}

		return 1;
	}

	/**
	 * Перезапуск воркеров
	 */
	public function reload() {
		try {
			return !(int)(new Gearman\Manager())->reload();
		} catch (\Exception $e) {
			$this->out($e->getMessage());
		}

		return 1;
	}

	/**
	 * Генерация класса воркера
	 * @param string $worker имя воркера
	 * @return object
	 * @throws AppException
	 */
	protected function _class($worker) {
		if (strpos($worker, '\\') !== 0) {
			$class = '\Gearman\\' . ucfirst($worker) . '\Worker';
		} else {
			$class = $worker;
		}

		if (!class_exists($class)) {
			throw new \AppException('Недоступный воркер');
		}

		return new $class();
	}
}