<?php
declare(strict_types = 1);

namespace skinny\interfaces\http;

use InvalidArgumentException;

/**
 * Интерфейс для классов, управляющие заголовками
 *
 * @package skinny\interfaces\http
 */
interface HeadersInterface {
	
	/**
	 * Удаляет заголовок
	 *
	 * @param string $key Заголовок
	 *
	 * @return void
	 */
	public function remove(string $key): void;
	
	/**
	 * Получает все заголовки
	 *
	 * @return array
	 */
	public function getAll(): array;
	
	/**
	 * Удаляет все заголовки
	 *
	 * @return void
	 */
	public function removeAll(): void;
	
	/**
	 * Получает значение заголовка
	 *
	 * @param string $key Заголовок
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 */
	public function get(string $key): string;
	
	/**
	 * Проверяет наличие заголовка. Проверка идет на наличие ключа и значения
	 *
	 * @param string $key Заголовок
	 *
	 * @return bool
	 */
	public function has(string $key): bool;
	
	/**
	 * Устанавливает заголовок(и)
	 *
	 * @param array $params Заголовок(и) [key => value]
	 *
	 * @return void
	 */
	public function set(array $params): void;
	
	/**
	 * Добавляет заголовок. Если заголовок уже существует, то он будет перезаписан.
	 *
	 * @param array $params Заголовки [key => value]
	 *
	 * @return void
	 */
	public function add(array $params): void;
}