<?php
declare(strict_types = 1);

namespace skinny;

use skinny\components\Logger;
use skinny\http\Headers;
use skinny\http\Request;
use skinny\http\Response;
use skinny\patterns\Singleton;

/**
 * Класс управления настройками приложения
 *
 * @package skinny
 */
class Settings extends Singleton {
	
	protected array $settings = [];
	
	public function __construct() {
		parent::__construct();
		
		$this->settings = self::getDefaultSettings();
	}
	
	/**
	 * Возращает настройки базовые приложения
	 *
	 * @return array
	 */
	public static function getDefaultSettings(): array {
		return [
			'mode' => 'development',
			'debug' => true,
			'language' => 'ru',
			'database' => [
				'dbms' => 'pgsql',
				'host' => 'localhost',
				'dbname' => '',
				'user' => '',
				'password' => ''
			],
			'migration' => [
				'schema' => 'public',
				'table' => 'migration',
				'directory' => self::getRootPath() . '/migrations'
			],
			'defaultComponents' => self::getDefaultComponents()
		];
	}
	
	public static function getRootPath(): string {
		return (PHP_SAPI === 'cli' ? getenv('PWD') : $_SERVER['DOCUMENT_ROOT']);
	}
	
	/**
	 * Возвращает массив обязательных компонентов приложения
	 *
	 * @return string[]
	 */
	public static function getDefaultComponents(): array {
		return [
			'headers' => ['class' => Headers::class],
			'request' => ['class' => Request::class],
			'response' => ['class' => Response::class],
			'logger' => [
				'class' => Logger::class, 'path',
				'params' => [
					'path' => self::getRootPath() . '/logs'
				],
			],
		];
	}
	
	/**
	 * Получает настройки приложения
	 *
	 * @return array
	 */
	public function getSettings(): array {
		return $this->settings;
	}
	
	/**
	 * Устанавливает настройку
	 *
	 * @param array $settings Настройки [key => value]
	 */
	public function setSettings(array $settings): void {
		$this->settings = array_merge($this->settings, $settings);
	}
	
	public function getDatabaseSettings(): array {
		return $this->settings['database'];
	}
	
	public function setDatabaseSettings(array $params): void {
		$this->settings['database'] = $params;
	}
}