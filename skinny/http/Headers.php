<?php
declare(strict_types = 1);

namespace skinny\http;

use InvalidArgumentException;
use skinny\interfaces\DefaultComponentInterface;
use skinny\interfaces\http\HeadersInterface;
use skinny\patterns\Singleton;

/**
 * Класс управления заголовками запроса
 *
 * @package skinny\http
 */
class Headers extends Singleton implements HeadersInterface, DefaultComponentInterface {
	
	/**
	 * @var array Заголовки
	 */
	private array $headers;
	
	/**
	 * @inheritDoc
	 */
	public function init(array $params = []): void {
		$this->headers = $this->getAllHeaders();
	}
	
	/**
	 * Получает все заголовки методами apache и nginx
	 *
	 * @return array
	 */
	private function getAllHeaders(): array {
		if (!function_exists('getallheaders')) {
			if (!is_array($_SERVER)) {
				return [];
			}
			
			$headers = [];
			
			foreach ($_SERVER as $name => $value) {
				if (strpos($name, 'HTTP_') === 0) {
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				}
			}
			
			return $headers;
		}
		
		return getallheaders() !== false ? getallheaders() : [];
	}
	
	/**
	 * Удаляет заголовок
	 *
	 * @param string $key Заголовок
	 *
	 * @return void
	 */
	public function remove(string $key): void {
		$this->getAll();
		
		unset($this->headers[$key]);
		header_remove($key);
	}
	
	/**
	 * Получает все заголовки
	 *
	 * @return array
	 */
	public function getAll(): array {
		$this->headers = !empty($this->headers) ? $this->headers : $this->getAllHeaders();
		
		return $this->headers;
	}
	
	/**
	 * Удаляет все заголовки
	 *
	 * @return void
	 */
	public function removeAll(): void {
		$this->headers = [];
		header_remove();
	}
	
	/**
	 * Получает значение заголовка
	 *
	 * @param string $key Заголовок
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 */
	public function get(string $key): string {
		if (!$this->has($key)) {
			throw new InvalidArgumentException("Заголоков {$key} отсутсвует.");
		}
		
		return $this->headers[$key];
	}
	
	/**
	 * Проверяет наличие заголовка. Проверка идет на наличие ключа и значения
	 *
	 * @param string $key Заголовок
	 *
	 * @return bool
	 */
	public function has(string $key): bool {
		$this->getAll();
		
		return isset($this->headers[$key]);
	}
	
	/**
	 * Устанавливает заголовок(и)
	 *
	 * @param array $params Заголовок(и) [key => value]
	 *
	 * @return void
	 */
	public function set(array $params): void {
		$this->getAll();
		
		foreach ($params as $header => $value) {
			$this->headers[$header] = $value;
		}
		
		$this->add($params);
	}
	
	/**
	 * Добавляет заголовок. Если заголовок уже существует, то он будет перезаписан.
	 *
	 * @param array $params Заголовки [key => value]
	 *
	 * @return void
	 */
	public function add(array $params): void {
		foreach ($params as $header => $value) {
			$headerExists = array_key_exists($header, $this->headers);
			$this->headers[$header] = $value;
			
			header("{$header}: {$value}", $headerExists);
		}
	}
}