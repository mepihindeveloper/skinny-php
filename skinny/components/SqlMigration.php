<?php
declare(strict_types = 1);

namespace skinny\components;

use DateTime;
use RuntimeException;
use skinny\Settings;

/**
 * Класс для работы с миграциями.
 * Класс предназначен для работы с миграциями в области создания, применение, отмены и просмотра историй.
 * При создании миграции формируется директория с двумя sql файлами: up.sql и down.sql. Up.sql предназначен для
 * хранения новых изменений в базе данных. Down.sql предназначен для отмены новых изменений в базе данных.
 *
 * @package skinny\components
 */
class SqlMigration
{
	
	/**
	 * @var Database|null Объект соединения с базой данных
	 */
	protected ?Database $database;
	/**
	 * @var array Массив настроек
	 */
	protected array $settings;
	
	/**
	 * Создает соединение с базой данных и получает конфигурацию миграций
	 */
	public function __construct()
	{
		$this->database = new Database();
		$this->settings = Settings::getInstance()->getSettings()['migration'];
		$this->init();
	}
	
	/**
	 * Инициализация сервиса миграций
	 */
	protected function init(): void
	{
		$this->database->connect();
		$sql = "
			SELECT EXISTS (
	            SELECT *
	            FROM information_schema.tables
	            WHERE
	                  table_schema = '{$this->settings['schema']}' AND
	                  table_name = '{$this->settings['table']}'
        	);
        ";
		
		if (!$this->database->queryOne($sql))
		{
			$this->createMigrationTable();
		}
	}
	
	/**
	 * Создает таблицу миграций
	 */
	protected function createMigrationTable(): void
	{
		$sql = "
			CREATE TABLE IF NOT EXISTS {$this->settings['schema']}.{$this->settings['table']} (
            	\"name\" varchar(180) COLLATE \"default\" NOT NULL,
	            \"apply_time\" int4,
	            CONSTRAINT {$this->settings['table']}_pkey PRIMARY KEY (\"name\")
	        ) WITH (OIDS=FALSE)
        ";
		
		if (!$this->database->execute($sql))
		{
			exit (Console::writeError('Ошибка создания таблицы миграции'));
		}
		
		$sql = "ALTER TABLE {$this->settings['schema']}.{$this->settings['table']}";
		
		if (!$this->database->execute($sql))
		{
			exit (Console::writeError('Ошибка при смене владельца таблицы миграции'));
		}
		
		Console::writeLine('Таблица миграции была успешно создана.', Console::FG_GREEN);
	}
	
	/**
	 * Завершает соединение с базой данных
	 */
	public function __destruct()
	{
		$this->database->closeConnection();
		$this->database = null;
	}
	
	/**
	 * Создает новую миграцию
	 *
	 * @param string $name Название миграции
	 */
	public function create(string $name): void
	{
		if (!preg_match('/^[\w]+$/', $name))
		{
			throw new RuntimeException('Имя миграции должно содержать только буквы, цифры и символы подчеркивания.');
		}
		
		$migrationName = $this->generateMigrationName($name);
		$migrationPath = "{$this->settings['directory']}/{$migrationName}";
		
		if (!mkdir($migrationPath) && !is_dir($migrationPath))
		{
			throw new RuntimeException(sprintf('Ошибка создания директории. Директория "%s" не была создана', $migrationPath));
		}
		
		file_put_contents($migrationPath . '/up.sql', '');
		file_put_contents($migrationPath . '/down.sql', '');
		
		Console::writeLine("Миграция была успешно создана. \n", Console::FG_GREEN);
	}
	
	/**
	 * Генерирует название файла миграции.
	 * Генерируемое название состоит из следующих частей: префикса <b>m</b>, даты по Гринвичу в формате Ymd_His и
	 * пользовательского названия.
	 *
	 * @param string $name Пользовательское название миграции
	 *
	 * @return string Имя файла миграции
	 */
	protected function generateMigrationName(string $name): string
	{
		return 'm' . gmdate('Ymd_His') . "_{$name}";
	}
	
	/**
	 * Выводит на экран список примененных миграций
	 *
	 * @param int $limit Ограничение длины списка (null - полный список)
	 */
	public function history(int $limit = 0): void
	{
		$migrationsList = $this->getMigrationHistory($limit);
		
		if (empty($migrationsList))
		{
			Console::writeLine('История мираций пуста.');
			exit();
		}
		
		foreach ($migrationsList as $history)
		{
			Console::writeLine('Миграция ' . $history['name'] . ' от ' . date('Y-m-d H:i:s', $history['apply_time']));
		}
	}
	
	/**
	 * Возвращает список примененных миграций
	 *
	 * @param int $limit Ограничение длины списка миграций (null - полный список)
	 *
	 * @return array Список примененных миграций
	 */
	protected function getMigrationHistory(int $limit = 0): array
	{
		$limitSql = $limit === 0 ? '' : "LIMIT {$limit}";
		$sql = "
			SELECT name, apply_time
			FROM {$this->settings['table']}
			ORDER BY apply_time DESC, \"name\" DESC {$limitSql}
		";
		
		return $this->database->queryAll($sql);
	}
	
	/**
	 * Выводит на экран список  непримененных миграций
	 */
	public function new(): void
	{
		$migrationsList = $this->getUnappliedMigrationList();
		
		foreach ($migrationsList as $migration)
		{
			Console::writeLine('Имеется непримененная миграция  ' . $migration['name'] . ' от ' . $migration['date_time']);
		}
	}
	
	/**
	 * Возвращает список непримененных миграций
	 *
	 * @return array Список непримененных миграций
	 */
	protected function getUnappliedMigrationList(): array
	{
		$migrationsAppliedList = $this->getMigrationHistory();
		$migrationsAppliedListNormalizes = [];
		
		foreach ($migrationsAppliedList as $migration)
		{
			$migrationsAppliedListNormalizes[$migration['name']] = true;
		}
		
		$migrationsUnapplied = [];
		$directoryList = glob("{$this->settings['folder']}/m*_*_*");
		
		foreach ($directoryList as $directory)
		{
			if (!is_dir($directory))
			{
				continue;
			}
			
			$directoryParts = explode('/', $directory);
			preg_match('/^(m(\d{8}_?\d{6})\D.*?)$/is', end($directoryParts), $matches);
			$migrationName = $matches[1];
			
			if (!isset($migrationsAppliedListNormalizes[$migrationName]))
			{
				$migrationDateTime = DateTime::createFromFormat('Ymd_His', $matches[2])->format('Y-m-d H:i:s');
				$migrationsUnapplied[] = [
					'path' => $directory,
					'name' => $migrationName,
					'date_time' => $migrationDateTime
				];
			}
		}
		
		ksort($migrationsUnapplied);
		
		return array_values($migrationsUnapplied);
	}
	
	/**
	 * Применяет указанное количество миграций
	 *
	 * @param string $count Количество применяемых миграция (null - применить все)
	 */
	public function up(string $count = ''): void
	{
		$migrationsUnappliedList = $this->getUnappliedMigrationList();
		$migrationsCountToApply = empty($count) ? count($migrationsUnappliedList) : (int)$count;
		
		for ($migrationIndex = 0; $migrationIndex < $migrationsCountToApply; $migrationIndex++)
		{
			$migration = $migrationsUnappliedList[$migrationIndex];
			$migrationBody = file_get_contents("{$migration['path']}/up.sql");
			
			$this->database->beginTransaction();
			$this->database->execute($migrationBody);
			$this->database->commit();
			
			$this->addMigrationHistory($migration['name']);
			sleep(1);
		}
	}
	
	/**
	 * Добавляет запись в список примененных миграций
	 *
	 * @param string $name Наименованеи миграции
	 */
	protected function addMigrationHistory(string $name): void
	{
		$sql = "INSERT INTO {$this->settings['table']} (name, apply_time) VALUES(:name, :apply_time)";
		$this->database->execute($sql, ['name' => $name, 'apply_time' => time()]);
		
		Console::writeLine("Миграция {$name} была успешно применена.", Console::FG_GREEN);
	}
	
	/**
	 * Отменяет указанное количество миграций
	 *
	 * @param string $count Количество отменяемых миграция (null - отменить все)
	 */
	public function down(string $count = ''): void
	{
		$migrationsApplied = $this->getMigrationHistory();
		$migrationsDownCount = empty($count) ? count($migrationsApplied) : (int)$count;
		
		for ($migrationIndex = 0; $migrationIndex < $migrationsDownCount; $migrationIndex++)
		{
			$migration = $migrationsApplied[$migrationIndex];
			$migrationBody = file_get_contents("{$this->settings['folder']}/{$migration['name']}/down.sql");
			
			$this->database->beginTransaction();
			$this->database->execute($migrationBody);
			$this->database->commit();
			
			$this->removeMigrationHistory($migration['name']);
			sleep(1);
		}
	}
	
	/**
	 * Удаляет миграцию из списка примененных миграций
	 *
	 * @param string $name Наименование миграции
	 */
	public function removeMigrationHistory(string $name): void
	{
		$sql = "DELETE FROM {$this->settings['table']} WHERE \"name\" = :name";
		$this->database->execute($sql, ['name' => $name]);
		
		Console::writeLine("Миграция {$name} была успешно отменена.", Console::FG_GREEN);
	}
}