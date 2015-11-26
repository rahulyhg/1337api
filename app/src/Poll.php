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
	  * Read Poll entry from database and return properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function readPoll ($request, $response, $args) {
		
		// set fixed edges and load item
		$args['edge'] = 'polls';
		$args['child'] = 'foodtrucks'; 
		$item = R::load( $args['edge'], $args['id'] );

		// if item retrieved
		if (!empty($item['id'])) {

			// foreach $item field, build response read array
			foreach ($item as $field => $value) {
				if (!in_array($field, $this->config['api']['read']['blacklist'])) {
					// ADD to payload response
					$read[$field] = $value;
				}
			}

			// ADD _ many-to-one options
			$childList = $item['own' . ucfirst($args['child']) . 'List'];
			if (!empty($childList)) {
				$i = 0;
				foreach ($childList as $childObj) {
					foreach ($childObj as $childField => $childValue) {
						if (!in_array($childField, $this->config['api']['read']['blacklist']) && $childField != $args['edge'] . '_id' ) {
							// TODO: And if the child item has related fields? We should get recursive here.
							$read[$args['child']][$i][$childField] = $childValue;
						}
					}
					$i++;
				}
			}

			// build api response payload
			$payload = $read;

			// output response payload
			return $response->withJson($payload);
		}
		else {
			$err = array('error' => true, 'message' => getMessage('NOT_FOUND'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(404);
		}
	}

	/**
	  * Retrieves a list of Polls from database edge and properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 		PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 		PSR 7 ResponseInterface Object
	  * @param array 									$args 			Associative array with current route's named placeholders
	  * @param int 										$query['page'] 	Query String parameter for paginated results
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function retrievePolls ($request, $response, $args) {

		// set fixed edge
		$args['edge'] = 'polls';

		// get query string parameters
		$args['query'] = $request->getQueryParams();

		// check if request is paginated or ALL
		if (!empty($args['query']['page'])) {
			if (is_numeric($args['query']['page'])) {
				// param page exists, let's get this page
				$limit = $this->config['api']['list']['itemsPerPage'];
				$items = R::findAll( $args['edge'], 'ORDER BY id DESC LIMIT '.(($args['query']['page']-1)*$limit).', '.$limit);
			}
			else {
				$err = array('error' => true, 'message' => getMessage('INVALID_REQUEST'));
				$this->logger->notice($err['message'], $args);
				return $response->withJson($err)->withStatus(400);
			}
		} 
		else {
			// param page doesn't exist, let's get all
			$items = R::findAll( $args['edge'], 'ORDER BY id DESC' );
		}

		// initialize $retrieve array
		$retrieve = array();

		// check if list is not empty
		if (!empty($items)) {
			// let's foreach and build response array
			foreach ($items as $item => $content) {
				foreach ($content as $field => $value) {
					if (in_array($field, $this->config['api']['list']['fields'])) {
						$retrieve[$item][$field] = $value;
					}
				}
			}
		}

		// build api response payload
		$payload = $retrieve;

		// output response
		return $response->withJson($payload);
	}

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