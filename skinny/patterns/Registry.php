<?php
declare(strict_types = 1);

namespace skinny\patterns;

use RuntimeException;
use skinny\interfaces\RegistryInterface;

/**
 * Класс, реализуйщий шаблон проектирования "Реестр" (Registry).
 * Имеется основное свойство - массив объектов пользовательских компонентов.
 *
 * @package skinny\patterns
 */
class Registry extends Singleton implements RegistryInterface
{
	
	/**
	 * @var array Пользовательские компоненты
	 */
	protected array $objects = [];
	
	/**
	 * @inheritDoc
	 */
	public function init(array $objects): void
	{
		foreach ($objects as $name => $value)
		{
			$this->objects[$name] = is_subclass_of($value['class'], Singleton::class) ?
				$value['class']::getInstance() : new $value['class'];
			
			if (array_key_exists('params', $value))
			{
				foreach ($value['params'] as $property => $propertyValue)
				{
					$this->objects[$name]->$property = $propertyValue;
				}
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function get(string $name)
	{
		if (!array_key_exists($name, $this->objects))
		{
			throw new RuntimeException("Ошибка получения компонента из регистра. Отсутствует компонент {$name}.");
		}
		
		return $this->objects[$name];
	}
	
	/**
	 * @inheritDoc
	 */
	public function getAll(): array
	{
		return $this->objects;
	}
}