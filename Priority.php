<?php
namespace Gearman;

class Priority extends \stdClass {
	const LOW = -1;
	const NORMAL = 0;
	const HIGH = 1;

	/**
	 * Текстовое представление приоритетов
	 *
	 * @var array
	 */
	protected static $_toString = [
		self::LOW => 'Low',
		self::NORMAL => 'Normal',
		self::HIGH => 'High'
	];

	public static function getAllConstants() {
		return (new \ReflectionClass(get_called_class()))->getConstants();
	}

	public static function isValid($value) {
		return in_array($value, array_values(self::getAllConstants()));
	}

	public static function toString($value) {
		return self::$_toString[$value];
	}

	public static function fromString($value) {
		foreach (array_flip(self::$_toString) as $val => $key) {
			if (strtolower($val) == strtolower($value)) {
				return $key;
			}
		}

		return $value;
	}
}