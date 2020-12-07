<?php
declare(strict_types = 1);

namespace skinny\interfaces;

/**
 * Интерфейс классов, реализующих шаблон проектирования "Реестр"
 *
 * @package skinny\interfaces
 */
interface RegistryInterface {
	
	/**
	 * Инициализация реестра
	 *
	 * @param array $objects Компоненты
	 */
	public function init(array $objects): void;
	
	/**
	 * Возвращает компонент по ключу.
	 * В случае отсутствия пользовательского компонента.
	 * Если получения компонента ядра не удалось, то выдает ошибку
	 *
	 * @param string $name Название компонента
	 *
	 * @return mixed
	 */
	public function get(string $name);
	
	/**
	 * Возращает список всех компонентов регистра
	 *
	 * @return array
	 */
	public function getAll(): array;
}