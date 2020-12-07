<?php
declare(strict_types = 1);

namespace skinny\components;

use InvalidArgumentException;
use skinny\http\Request;

/**
 * Класс конфигурирования маршрута
 *
 * @package skinny\components
 */
class Route {
	
	/**
	 * @var string Название (псевдоним) маршрута
	 */
	protected string $name;
	/**
	 * @var string Шаблон запроса к маршруту
	 */
	protected string $pattern;
	/**
	 * @var callable Функция обработки маршрута (исполнение маршрута)
	 */
	protected $callback;
	/**
	 * @var array Параметры запроса
	 */
	protected array $params = [];
	
	/**
	 * Конструктор класса маршрута.
	 *
	 * @param string $name Название (псевдоним) маршрута
	 * @param string $pattern Шаблон запроса к маршруту
	 * @param callable $callback Функция обработки маршрута (исполнение маршрута)
	 */
	public function __construct(string $name, string $pattern, callable $callback) {
		$this->setName($name);
		$this->setPattern($pattern);
		$this->setCallback($callback);
		$this->setParams();
	}
	
	/**
	 * Проверяет свопадение запроса с шаблоном
	 *
	 * @return bool
	 */
	public function match(): bool {
		return (bool)preg_match("#^{$this->pattern}$#", Request::getInstance()->getQueryString());
	}
	
	/**
	 * Возвращает название (псевдоним) маршрута
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}
	
	/**
	 * Устанавливает название (псевдоним) маршрута
	 *
	 * @param string $name Название (псевдоним) маршрута
	 */
	protected function setName(string $name): void {
		$this->name = $name;
	}
	
	/**
	 * Возвращает шаблон запроса к маршруту
	 *
	 * @return string
	 */
	public function getPattern(): string {
		return $this->pattern;
	}
	
	/**
	 * Устанавливает шаблон запроса к маршруту
	 *
	 * @param string $pattern Шаблон запроса к маршруту
	 */
	protected function setPattern(string $pattern): void {
		$this->pattern = $pattern;
	}
	
	/**
	 * Возвращает функцию обработки маршрута (исполнение маршрута)
	 *
	 * @return callable
	 */
	public function getCallback(): callable {
		return $this->callback;
	}
	
	/**
	 * Устанавливает функцию обработки маршрута (исполнение маршрута)
	 *
	 * @param callable $callback Функция обработки маршрута (исполнение маршрута)
	 *
	 * @throws InvalidArgumentException
	 */
	protected function setCallback(callable $callback): void {
		if (!is_callable($callback)) {
			throw new InvalidArgumentException('Ошибка аргумента выполняемой функции. Функция callback должна быть вызываема.');
		}
		
		$this->callback = $callback;
	}
	
	/**
	 * Возвращает параметры запроса
	 *
	 * @return array
	 */
	public function getParams(): array {
		return $this->params;
	}
	
	/**
	 * Устанавливает параметры запроса
	 */
	protected function setParams(): void {
		preg_match("#^{$this->pattern}$#", Request::getInstance()->getQueryString(), $matches);
		$this->params = array_filter($matches, static function($value, $key) {
			return !is_numeric($key) && !empty($value);
		}, ARRAY_FILTER_USE_BOTH);
	}
}