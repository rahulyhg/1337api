<?php 
/**
 * eApi
 * @author  Elijah Hatem <elias.hatem@gmail.com>
 * @license MIT
 */
namespace eApi;

use \RedBeanPHP\Facade as R;
use Psr\Log\LoggerInterface;

/**
 * eApi custom Poll class.
 * Provides custom functions to public RESTful Poll API.
 * integrated with Slim Framework, RedBeanPHP and Monolog.
 *
 * @author  Elijah Hatem <elias.hatem@gmail.com>
 * @license MIT
 */
class Poll {

/** VARS - eApi\Poll Class Variables **/

	/**
	 * @var array 						$config 	Global settings values
	 */
	private $config;

	/**
	 * @var Psr\Log\LoggerInterface 	$logger 	Monolog Logger interface handler
	 */
	private $logger;

	/**
	  * eApi\Poll class construct function.
	  *
	  * @param array 									$config 	Global settings values
	  * @param array 									$edges 		API Edges list private array
	  * @param Psr\Log\LoggerInterface 					$logger 	Monolog Logger interface handler
	  */
	public function __construct($config, LoggerInterface $logger) {
		$this->config 		= $config;
		$this->logger 		= $logger;
	}

/** PUBLIC - eApi\Poll Class Public Functions **/

	/**
	  * API test method.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function test ($request, $response, $args) {

		// build api response payload
		$payload = array(
			'message' => 'Hello, tested!'
		);

		// output response payload
		$this->logger->info($payload['message'], $args);
		return $response->withJson($payload);
	}

/** PRIVATE - eApi\Poll Class Private Functions **/

	/* ./none */

}
/* .end Api.php */