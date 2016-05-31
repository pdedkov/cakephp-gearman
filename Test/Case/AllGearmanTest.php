<?php
/**
 * AllGearmanTest class
 *
 * Собираем все тесты из lib/gearman в один
 */
class AllGearmanTest extends PHPUnit_Framework_TestSuite {

	/**
	 * 	Метод сборки
	 */
	public static function suite() {
		$suite = new CakeTestSuite('All gearman tests');

		$suite->addTestFile(__DIR__ . DS . 'ClientTest.php');
		$suite->addTestFile(__DIR__ . DS . 'WorkerTest.php');
		$suite->addTestFile(__DIR__ . DS . 'ManagerTest.php');

		$suite->addTestFile(__DIR__ . DS . 'Restart' . DS . 'ClientTest.php');

		return $suite;
	}
}