<?php
declare(strict_types = 1);

namespace skinny;

use InvalidArgumentException;
use skinny\components\Database;
use skinny\components\Route;
use skinny\http\Headers;
use skinny\http\Request;
use skinny\http\Response;
use skinny\patterns\Registry;
use skinny\patterns\Singleton;

/**
 * Класс Skinny.
 * Реализует централизированное управления приложением.
 *
 * @package skinny
 */
class Skinny extends Singleton {
	
	/**
	 * @var Singleton|Settings Настройки приложения
	 */
	protected Settings $settings;
	/**
	 * @var Request|Singleton Управление запросами
	 */
	protected Request $request;
	/**
	 * @var Response|Singleton Управление ответами
	 */
	protected Response $response;
	/**
	 * @var Route[] Массив маршрутов
	 */
	protected array $routers;
	/**
	 * @var Database Управление операциями с базой данных
	 */
	protected Database $database;
	
	/**
	 * Конструктор класса Skinny.
	 */
	protected function __construct() {
		parent::__construct();
		
		$this->settings = Settings::getInstance();
		Registry::getInstance()->init($this->settings::getDefaultComponents());
		
		$this->request = Registry::getInstance()->get('request');
		$this->response = Registry::getInstance()->get('response');
		$this->database = new Database;
	}
	
	/**
	 * Возвращает класс управления настройками приложения
	 *
	 * @return Settings
	 */
	public function getSettings(): Settings {
		return $this->settings;
	}
	
	/**
	 * Возвращает базовые настройки приложения
	 *
	 * @return array
	 */
	public function getDefaultSettings(): array {
		return $this->settings::getDefaultSettings();
	}
	
	/**
	 * Возвращает класс управления запросами
	 *
	 * @return Request
	 */
	public function getRequest(): Request {
		return $this->request;
	}
	
	/**
	 * Возвращает класс управления ответами
	 *
	 * @return Response
	 */
	public function getResponse(): Response {
		return $this->response;
	}
	
	/**
	 * Возвращает класс управления заголовками запроса
	 *
	 * @return Headers
	 */
	public function getHeaders(): Headers {
		return $this->request->getHeaders();
	}
	
	/**
	 * Конфигрурует запрос типа GET
	 */
	public function get(): void {
		$this->mapRoutes($this->request::METHOD_GET, func_get_args());
	}
	
	/**
	 * Складирует запросы по типу в маршруты
	 *
	 * @param string $method Тип (метод) запроса
	 * @param array $arguments Аргументы запроса
	 */
	protected function mapRoutes(string $method, array $arguments): void {
		$this->routers[$method][] = (new Route($arguments[0], $arguments[1], $arguments[2]));
	}
	
	/**
	 * Конфигрурует запрос типа POST
	 */
	public function post(): void {
		$this->mapRoutes($this->request::METHOD_POST, func_get_args());
	}
	
	/**
	 * Конфигрурует запрос типа PUT
	 */
	public function put(): void {
		$this->mapRoutes($this->request::METHOD_PUT, func_get_args());
	}
	
	/**
	 * Конфигрурует запрос типа PATCH
	 */
	public function patch(): void {
		$this->mapRoutes($this->request::METHOD_PATCH, func_get_args());
	}
	
	/**
	 * Конфигрурует запрос типа DELETE
	 */
	public function delete(): void {
		$this->mapRoutes($this->request::METHOD_DELETE, func_get_args());
	}
	
	/**
	 * Конфигрурует запрос типа OPTIONS
	 */
	public function options(): void {
		$this->mapRoutes($this->request::METHOD_OPTIONS, func_get_args());
	}
	
	/**
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function run(): bool {
		foreach ($this->routers as $method => $routes) {
			if ($method !== $this->request->getRequestMethod()) {
				continue;
			}
			
			/** @var Route $route */
			foreach ($routes as $route) {
				if (!$route->match()) {
					continue;
				}
				
				echo call_user_func_array($route->getCallback(), $route->getParams());
				
				if ($this->response->getFormat() !== $this->response::FORMAT_HTML) {
					exit();
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	public function getDatabase(): Database {
		return $this->database;
	}
}