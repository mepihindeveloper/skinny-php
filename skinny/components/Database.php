<?php
/*
 * Copyright (c) 2020.
 *
 * Разработчик: Максим Епихин
 * Twitter: https://twitter.com/maximepihin
 */

declare(strict_types = 1);

namespace skinny\components;

use PDO;
use PDOException;
use PDOStatement;
use skinny\Settings;

/**
 * Касс-помощник для работы с базой данных.
 * Класс реализует подключение и управление запросами к базе данных.
 *
 * @package skinny\components
 */
class Database
{
	
	/**
	 * @var PDOStatement[] Список подготовленных запросов к базе данных
	 */
	public array $executeList = [];
	/**
	 * @var bool Статус активности транзакции
	 */
	public bool $isTransaction = false;
	/**
	 * @var PDO|null Соединение с базой данных
	 */
	private ?PDO $pdo;
	/**
	 * @var PDOStatement Подготовленный запрос к базе данных
	 */
	private PDOStatement $pdoStatement;
	
	/**
	 * Создает подключение к базе данных
	 *
	 * @param array $params
	 *
	 * @return void
	 *
	 */
	public function connect(array $params = []): void
	{
		$databaseConnectionParams = empty($params) ? Settings::getInstance()->getDatabaseSettings() : $params;
		
		$dsn = $databaseConnectionParams['dbms'] . ':';
		
		foreach (['host', 'dbname'] as $key)
		{
			$dsn .= "{$key}={$databaseConnectionParams[$key]};";
		}
		
		$charset = array_key_exists('charset', $databaseConnectionParams) ? strtoupper($databaseConnectionParams['charset']) : 'UTF8';
		
		$this->pdo = new PDO(
			$dsn,
			$databaseConnectionParams['user'],
			$databaseConnectionParams['password']
		);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		$this->pdo->exec("SET NAMES '{$charset}'");
	}
	
	/**
	 * Закрывает подключение к базе данных
	 */
	public function closeConnection(): void
	{
		$this->pdo = null;
	}
	
	/**
	 * Начинает транзакцию
	 */
	public function beginTransaction(): void
	{
		if (!$this->isTransaction)
		{
			$this->pdo->beginTransaction();
			$this->isTransaction = true;
		}
	}
	
	/**
	 * Выполняет транзакцию
	 *
	 * @throws PDOException
	 */
	public function commit(): void
	{
		try
		{
			if (!empty($this->executeList))
			{
				foreach ($this->executeList as $executeQuery)
				{
					$executeQuery->execute();
				}
			}
			
			$this->pdo->commit();
		} catch (PDOException $exception)
		{
			$this->pdo->rollBack();
			throw new PDOException(500, $exception->getMessage(), $exception->getCode());
		} finally
		{
			$this->isTransaction = false;
			$this->executeList = [];
		}
	}
	
	/**
	 * Возвращает массив, содержащий все строки результирующего набора
	 *
	 * @see https://www.php.net/manual/ru/pdostatement.fetchall.php PDOStatement::fetchAll
	 *
	 * @param string $query      Запрос
	 * @param array  $attributes Атрибуты
	 * @param int    $fetchStyle Определяет содержимое возвращаемого массива
	 *
	 * @return array
	 * @throws PDOException
	 */
	public function queryAll(string $query, array $attributes = [], $fetchStyle = PDO::FETCH_ASSOC): array
	{
		$this->execute($query, $attributes);
		
		return $this->pdoStatement->fetchAll($fetchStyle);
	}
	
	/**
	 * Выполняет запрос
	 *
	 * @param string $query      Запрос
	 * @param array  $attributes Атрибуты
	 *
	 * @return bool
	 * @throws PDOException
	 */
	public function execute(string $query, array $attributes = []): bool
	{
		$this->beforeQuery($query, $attributes);
		
		return $this->pdoStatement->execute();
	}
	
	/**
	 * Обработка запроса перед выполненениме
	 *
	 * @param string $query      Запрос
	 * @param array  $attributes Атрибуты запроса
	 *
	 * @return void
	 * @throws PDOException
	 */
	protected function beforeQuery(string $query, array $attributes = []): void
	{
		try
		{
			$this->pdoStatement = $this->pdo->prepare($query);
			
			if (!empty($attributes))
			{
				$bindedAttributes = $this->bindAttributes($attributes);
				
				foreach ($bindedAttributes as $bindedAttribute)
				{
					$attributesPart = explode("\x7F", $bindedAttribute);
					$this->pdoStatement->bindParam($attributesPart[0], $attributesPart[1]);
				}
			}
			
			if ($this->isTransaction)
			{
				$this->executeList[] = $this->pdoStatement;
			}
		} catch (PDOException $exception)
		{
			throw new PDOException(500, $exception->getMessage(), $exception->getCode());
		}
	}
	
	/**
	 * Назначает атрибуты
	 *
	 * @param array $attributes Атрибуты
	 *
	 * @return array
	 */
	protected function bindAttributes(array $attributes): array
	{
		$bindedAttributes = [];
		
		foreach ($attributes as $key => $value)
		{
			$bindedAttributes[] = ':' . $key . "\x7F" . $value;
		}
		
		return $bindedAttributes;
	}
	
	/**
	 * Возвращает строку результирующего набора
	 *
	 * @see https://www.php.net/manual/ru/pdostatement.fetch.php PDOStatement::fetch
	 *
	 * @param string $query      Запрос
	 * @param array  $attributes Атрибуты
	 * @param int    $fetchStyle Определяет содержимое возвращаемого массива
	 *
	 * @return mixed
	 * @throws PDOException
	 */
	public function queryRow(string $query, array $attributes = [], $fetchStyle = PDO::FETCH_ASSOC)
	{
		$this->execute($query, $attributes);
		
		return $this->pdoStatement->fetch($fetchStyle);
	}
	
	/**
	 * Возвращает колонку результирующего набора
	 *
	 * @see https://www.php.net/manual/ru/pdostatement.fetchcolumn.php PDOStatement::fetchColumn
	 *
	 * @param string $query      Запрос
	 * @param array  $attributes Атрибуты
	 *
	 * @return array
	 * @throws PDOException
	 */
	public function queryColumn(string $query, array $attributes = []): array
	{
		$this->execute($query, $attributes);
		$queryCells = $this->pdoStatement->fetchAll(PDO::FETCH_NUM);
		$cells = [];
		
		foreach ($queryCells as $queryCell)
		{
			$cells[] = $queryCell[0];
		}
		
		return $cells;
	}
	
	/**
	 * Возвращает единственную запись результирующего набора
	 *
	 * @param string $query      Запрос
	 * @param array  $attributes Атрибуты
	 *
	 * @return mixed
	 * @throws PDOException
	 */
	public function queryOne(string $query, array $attributes = [])
	{
		$this->execute($query, $attributes);
		
		return $this->pdoStatement->fetchColumn();
	}
	
	/**
	 * Возвращает ID последней вставленной строки или значение последовательности
	 *
	 * @see https://www.php.net/manual/ru/pdo.lastinsertid.php PDO::lastInsertId
	 *
	 * @return string
	 */
	public function getLastInsertId(): string
	{
		return $this->pdo->lastInsertId();
	}
}