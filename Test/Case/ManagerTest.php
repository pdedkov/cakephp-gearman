<?php
namespace Gearman;

class ManagerTest extends \CakeTestCase {
	public function testShouldReload() {
		$Manager = new Manager();

		$this->assertTrue($Manager->reload());
	}
}