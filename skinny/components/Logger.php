<?php

declare(strict_types = 1);

namespace skinny\components;

use Psr\Log\AbstractLogger;
use RuntimeException;
use skinny\interfaces\DefaultComponentInterface;

/**
 * Класс логирования информации.
 *
 * @package skinny\components
 */
class Logger extends AbstractLogger implements DefaultComponentInterface {
	
	/**
	 * @var string|mixed Путь до директории логирования
	 */
	public string $path;
	
	/**
	 * @inheritDoc
	 */
	public function init(array $params = []): void {
		$this->path = empty($params) || !array_key_exists('path', $params) ? $this->path : $params['path'];
		
		if (!file_exists($this->path) && !mkdir($this->path) && !is_dir($this->path)) {
			throw new RuntimeException(
				sprintf('Ошибка создания директории. Директория "%s" не была создана', $this->path)
			);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function log($level, $message, array $context = []): void {
		$filename = "{$this->path}/log-" . gmdate('Ymd') . "-{$level}.txt";
		$data = '[Дата: ' . gmdate('Ymd') . ']' . " [Уровень: {$level}] {$message}" . PHP_EOL;
		file_put_contents($filename, $data, FILE_APPEND | LOCK_EX);
	}
}