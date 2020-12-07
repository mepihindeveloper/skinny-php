<?php

namespace skinny\interfaces;

/**
 * Интерфейс для компонентов по-умолчанию.
 * Компоненты по-умолчанию объявляются в секции defaultComponents настройках проекта.
 *
 * @package skinny\interfaces
 */
interface DefaultComponentInterface {
	
	/**
	 * Базовый метод инициализации компонента
	 *
	 * @param array $params Параметры метода
	 */
	public function init(array $params = []): void;
}