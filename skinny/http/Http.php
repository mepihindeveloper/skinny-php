<?php

declare(strict_types = 1);

namespace skinny\http;

use skinny\interfaces\DefaultComponentInterface;
use skinny\patterns\Singleton;

/**
 * Базовый класс для работы с Http методами
 *
 * @package skinny\http
 */
class Http extends Singleton implements DefaultComponentInterface {
	
	/**
	 * @var Headers
	 */
	protected Headers $headers;
	
	/**
	 * @inheritDoc
	 */
	public function init(array $params = []): void {
		$this->headers = Headers::getInstance();
	}
}