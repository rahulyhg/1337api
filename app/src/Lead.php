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
 * eApi custom Lead class.
 * Provides custom functions to public RESTful Lead API.
 * integrated with Slim Framework, RedBeanPHP and Monolog.
 *
 * @author  Elijah Hatem <elias.hatem@gmail.com>
 * @license MIT
 */
class Lead {

/** VARS - eApi\Lead Class Variables **/

	/**
	 * @var array 						$config 	Global settings values
	 */
	private $config;

	/**
	 * @var Psr\Log\LoggerInterface 	$logger 	Monolog Logger interface handler
	 */
	private $logger;

	/**
	  * eApi\Lead class construct function.
	  *
	  * @param array 									$config 	Global settings values
	  * @param Psr\Log\LoggerInterface 					$logger 	Monolog Logger interface handler
	  */
	public function __construct($config, LoggerInterface $logger) {
		$this->config 		= $config;
		$this->logger 		= $logger;
	}

/** PUBLIC - eApi\Lead Class Public Functions **/

	/**
	  * Inserts new lead at database.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function createLead ($request, $response, $args) {

		// get data from 'body' request payload
		$data = $request->getParsedBody();

		// public validation business rules

			// if no $data was sent, return bad request response
			if (empty($data)) 
			{
				$err = array('error' => true, 'message' => getMessage('LEAD_GENERIC_ERROR'), 'class' => 'error');
				$this->logger->notice($err['message'], array($args, $data));
				return $response->withJson($err)->withStatus(400);
			}

			// if $data is sent without mandatory fields
			if (empty($data['name']) 	||
				empty($data['email']) 	||				
				!in_array($data['flags'], ['fase1','fase2','fase3'])
				) 
			{
				$err = array('error' => true, 'message' => getMessage('LEAD_MISSING_FIELDS_ERROR'), 'class' => 'error');
				$this->logger->notice($err['message'], array($args, $data));
				return $response->withJson($err)->withStatus(400);
			}

		// dispense 'edge'
		$lead = R::dispense( 'leads' );

		// build array to insert
		$lead->name 		= $data['name'];
		$lead->email 		= $data['email'];
		$lead->newsletter 	= true;
		$lead->flags 		= $data['flags'];
		$lead->created 		= R::isoDateTime();
		$lead->modified 	= R::isoDateTime();

		// let's start the insert transaction
		R::begin();

		try {
			// insert item, returns id if success
			R::store($lead);
			$id = R::getInsertID();

			// if item was insert with success
			if ($id) {

				// commit transaction
				R::commit();

				// build api response array
				$payload = array(
					'title' 	=> getMessage('LEAD_CREATE_SUCCESS_TIT'),
					'message' 	=> getMessage('LEAD_CREATE_SUCCESS_TXT'),
					'class' 		=> 'success'
				);
				
				//output response
				// $this->logger->info($payload['message'], $args);
				return $response->withJson($payload)->withStatus(201);
			}

			// else something happened, throw error
			else {
				$err = getMessage('LEAD_CREATE_FAIL');
				throw new \Exception($err, 1);
			}
		}
		catch(\Exception $e) {

			R::rollback();
			
			// handle exceptions duplicate entry message to be user-friendly
			if (strpos($e->getMessage(), '1062 Duplicate entry')) {
				$err = array('error' => true, 'message' => getMessage('LEAD_DUPLICATE_EMAIL'), 'class' => 'warning');
				$this->logger->notice($err['message'], array($args, $data));
				return $response->withJson($err)->withStatus(400);
			}
			else {
				throw $e;
			}
		}
	}

/** PRIVATE - eApi\Lead Class Private Functions **/

	/* ./none */

}
/* .end Lead.php */