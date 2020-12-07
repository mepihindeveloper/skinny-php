<?php
declare(strict_types = 1);

namespace skinny\http;

use HttpUrlException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Класс управления запросами.
 * Класс реализует управления запросами в области: получение метода запроса, получение данных запроса и прочие параметры
 * запроса
 *
 * @package skinny\http
 */
class Request extends Http {
	
	public const METHOD_HEAD = 'HEAD';
	public const METHOD_GET = 'GET';
	public const METHOD_POST = 'POST';
	public const METHOD_PUT = 'PUT';
	public const METHOD_PATCH = 'PATCH';
	public const METHOD_DELETE = 'DELETE';
	public const METHOD_OPTIONS = 'OPTIONS';
	
	/**
	 * Возвращает объект работы с заголовками
	 *
	 * @return Headers
	 */
	public function getHeaders(): Headers {
		return $this->headers;
	}
	
	/**
	 * Проверяет является ли запрос GET
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isGet(): bool {
		return $this->getRequestMethod() === self::METHOD_GET;
	}
	
	/**
	 * Получает метод запроса (GET, POST, HEAD, PUT, PATCH, DELETE)
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getRequestMethod(): string {
		if ($this->headers->has('X-Http-Method-Override')) {
			return strtoupper($this->headers->get('X-Http-Method-Override'));
		}
		
		return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
	}
	
	/**
	 * Проверяет является ли запрос POST
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isPost(): bool {
		return $this->getRequestMethod() === self::METHOD_POST;
	}
	
	/**
	 * Проверяет является ли запрос XHR
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 * @see isAjax()
	 *
	 */
	public function isXhr(): bool {
		return $this->isAjax();
	}
	
	/**
	 * Проверяет является ли запрос Ajax
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isAjax(): bool {
		return $this->headers->get('X-Requested-With') === 'XMLHttpRequest';
	}
	
	/**
	 * Проверяет является ли запрос PUT
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isPut(): bool {
		return $this->getRequestMethod() === self::METHOD_PUT;
	}
	
	/**
	 * Проверяет является ли запрос DELETE
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isDelete(): bool {
		return $this->getRequestMethod() === self::METHOD_DELETE;
	}
	
	/**
	 * Проверяет является ли запрос PATCH
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isPatch(): bool {
		return $this->getRequestMethod() === self::METHOD_PATCH;
	}
	
	/**
	 * Проверяет является ли запрос OPTIONS
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isOptions(): bool {
		return $this->getRequestMethod() === self::METHOD_OPTIONS;
	}
	
	/**
	 * Проверяет является ли запрос HEAD
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isHead(): bool {
		return $this->getRequestMethod() === self::METHOD_HEAD;
	}
	
	/**
	 * Получает параметр GET с заданным именем. Если имя не указано, Получает массив всех параметров GET
	 *
	 * @param string|null $key Ключ
	 *
	 * @return mixed
	 */
	public function get(string $key = null) {
		return is_null($key) ? $_GET : $_GET[$key];
	}
	
	/**
	 * Получает параметр POST с заданным именем. Если имя не указано, Получает массив всех параметров POST
	 *
	 * @param string|null $key Ключ
	 *
	 * @return mixed
	 */
	public function post(string $key = null) {
		return is_null($key) ? $_POST : $_POST[$key];
	}
	
	/**
	 * Получает имя хоста другого конца этого соединения. Заголовки игнорируются
	 *
	 * @return string|null
	 */
	public function getRemoteHost(): ?string {
		return $_SERVER['REMOTE_HOST'] ?? null;
	}
	
	/**
	 * Получает IP на другом конце этого соединения. Заголовки игнорируются
	 *
	 * @return string|null
	 */
	public function getRemoteIP(): ?string {
		return $_SERVER['REMOTE_ADDR'] ?? null;
	}
	
	/**
	 * Получает user agent
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getUserAgent(): string {
		return $this->headers->get('User-Agent');
	}
	
	/**
	 * Получает URL-реферер
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getReferrer(): string {
		return $this->headers->get('Referer');
	}
	
	/**
	 * Получает имя сервера
	 *
	 * @return string
	 */
	public function getServerName(): string {
		return $_SERVER['SERVER_NAME'];
	}
	
	/**
	 * Получает тип контента запроса
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getContentType(): string {
		return $_SERVER['CONTENT_TYPE'] ?? $this->headers->get('Content-Type');
	}
	
	/**
	 * Получает часть хоста текущего запроса URL
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function getHostName() {
		return parse_url($this->getHostInfo(), PHP_URL_HOST);
	}
	
	/**
	 * Получает схему и часть хоста текущего запроса URL. Возвращенный URL не имеет конечной косой черты.
	 *
	 * @return string|null
	 * @throws InvalidArgumentException
	 */
	public function getHostInfo(): ?string {
		$isSecure = $this->isSecureConnection();
		$protocol = $isSecure ? 'https' : 'http';
		$hostInfo = null;
		
		if ($this->headers->has('X-Forwarded-Host')) {
			$hostInfo = "{$protocol}://" . trim(explode(',', $this->headers->get('X-Forwarded-Host'))[0]);
		} else if ($this->headers->has('Host')) {
			$hostInfo = "{$protocol}://" . $this->headers->get('Host');
		} else if (isset($_SERVER['SERVER_NAME'])) {
			$hostInfo = "{$protocol}://" . $_SERVER['SERVER_NAME'];
			$port = $isSecure ? $this->getSecurePort() : $this->getPort();
			
			if (($port !== 80 && !$isSecure) || ($port !== 443 && $isSecure)) {
				$hostInfo .= ":{$port}";
			}
		}
		
		return $hostInfo;
	}
	
	/**
	 * Проверяет наличие протокола защищенного соединения
	 *
	 * @return bool
	 */
	public function isSecureConnection(): bool {
		return (isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] === 1));
	}
	
	/**
	 * Получает порт защищенного соединения
	 *
	 * @return int
	 */
	public function getSecurePort(): int {
		$serverPort = $this->getServerPort();
		
		return !$this->isSecureConnection() && $serverPort !== null ? $serverPort : 443;
	}
	
	/**
	 * Получает порт соединения
	 *
	 * @return int|null
	 */
	public function getServerPort(): ?int {
		return $_SERVER['SERVER_PORT'] ?? null;
	}
	
	/**
	 * Получает порт, используемый для небезопасных запросов.
	 * По умолчанию 80, или порт, указанный сервером, если текущий запрос небезопасен
	 *
	 * @return int
	 */
	public function getPort(): int {
		$serverPort = $this->getServerPort();
		
		return !$this->isSecureConnection() && $serverPort !== null ? $serverPort : 80;
	}
	
	/**
	 * Получает относительный URL-адрес сценария входа
	 *
	 * @return string
	 *
	 * @throws HttpUrlException
	 */
	public function getScriptUrl(): string {
		$scriptFile = $this->getScriptFile();
		$scriptName = basename($scriptFile);
		$scriptUrl = '';
		
		if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
			$scriptUrl = $_SERVER['SCRIPT_NAME'];
		} else if (isset($_SERVER['PHP_SELF'])) {
			if (basename($_SERVER['PHP_SELF']) === $scriptName) {
				$scriptUrl = $_SERVER['PHP_SELF'];
			} else if (($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
				$scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
			}
		} else if (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
			$scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
		} else if (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
			$scriptUrl = str_replace([$_SERVER['DOCUMENT_ROOT'], '\\'], ['', '/'], $scriptFile);
		} else {
			throw new HttpUrlException('Невозможно определить URL сценария входа.');
		}
		
		return $scriptUrl;
	}
	
	/**
	 * Получает относительный URL-адрес сценария входа
	 *
	 * @return string
	 *
	 * @throws RuntimeException
	 */
	public function getScriptFile(): string {
		if (isset($_SERVER['SCRIPT_FILENAME'])) {
			return $_SERVER['SCRIPT_FILENAME'];
		}
		
		throw new RuntimeException('Невозможно определить путь к файлу сценария входа');
	}
	
	/**
	 * Полуачет часть URL запроса, которая находится после знака вопроса
	 *
	 * @return string
	 */
	public function getQueryString(): string {
		return $_SERVER['QUERY_STRING'] ?? '';
	}
}