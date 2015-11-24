<?php 
/**
 * SlimBean
 * @author  Elijah Hatem <elias.hatem@gmail.com>
 * @license MIT
 */
namespace SlimBean;

use \RedBeanPHP\Facade as R;
use Psr\Log\LoggerInterface;

/**
 * SlimBean core Api class.
 * Provides main default functions to RESTful API
 * integrated with Slim Framework, RedBeanPHP and Monolog.
 *
 * @author  Elijah Hatem <elias.hatem@gmail.com>
 * @license MIT
 */
class Api {

/** VARS - SlimBean\Api Class Variables **/

	/**
	 * @var array $config Global settings values. 
	 */
	private $config;

	/**
	 * @var Psr\Log\LoggerInterface $logger Logger handler. 
	 */
	private $logger;

	/**
	 * @var array $hierarchy Hierarchy private array, built from $this->getHierarchy function. 
	 */
	private $hierarchy;

	public function __construct($config, LoggerInterface $logger) {
		$this->config 		= $config;		
		$this->logger 		= $logger;
		
		$this->setHierarchy();
	}

/** PUBLIC - SlimBean\Api Class Public Functions **/

	/**
	  * Counts items from a database collection and properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function count ($request, $response, $args) {

		// define response vars
		$count = R::count( $args['edge'] );
		$limit = ( !empty($this->config['api']['list']['itemsPerPage']) ? $this->config['api']['list']['itemsPerPage'] : 10 );

		// build response payload
		$payload = array(
			'sum' 			=> $count,
			'pages' 		=> ceil($count/$limit),
			'itemsPerPage' 	=> $limit
		);

		// output response payload
		return $response->withJson($payload);
	}

	/**
	  * Inserts new item at database.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function create ($request, $response, $args) {

		// get data from 'body' request payload
		$data = $request->getParsedBody();

		// if no data was sent, return bad request response
		if (empty($data)) {
			$err = array('error' => true, 'message' => getMessage('MISSING_FORMDATA'));
			$this->logger->notice($err['message'], array($args, $data));
			return $response->withJson($err)->withStatus(400);
		}
		// if id was sent, return bad request response
		if (isset($data['id'])) {
			$err = array('error' => true, 'message' => getMessage('INVALID_ID_FORMDATA'));
			$this->logger->notice($err['message'], array($args, $data));
			return $response->withJson($err)->withStatus(400);
		}

		// dispense 'edge'
		$item = R::dispense( $args['edge'] );

		// foreach $data request payload, build array to insert
		foreach ($data as $field => $value) {

			// IF field is not array, just parse it
			if (!is_array($value)) {

				// IF field is a password, hash it up
				if ($field == 'password') {
					$value = md5($value);
				}

				// ADD to insert array
				$item[$field] = $value;

			}
			// ELSE is array and defines many-to-many relationship
			else {
				// validate if related edge is valid
				if (in_array($field, $this->config['edges']['list'])) {

					$related = R::dispense($field);

					foreach ($value as $xfield => $xvalue) {
						$related[$xfield] = $xvalue;
					}

					// inject created and modified current time
					$related['created'] 	= R::isoDateTime();
					$related['modified']	= R::isoDateTime();

					// inject at shared item list
					$item['shared' . ucfirst($field) . 'List'][] = $related;
				}
				else {
					$err = array('error' => true, 'message' => getMessage('CREATE_FAIL'));
					$this->logger->notice($err['message'], array($args, $data));
					return $response->withJson($err)->withStatus(400);
				}
			}
		}
		
		// inject created and modified current time
		$item['created'] 	= R::isoDateTime();
		$item['modified'] 	= R::isoDateTime();

		// let's start the insert transaction
		R::begin();

		try {
			// insert item, returns id if success
			R::store($item);
			$id = R::getInsertID();

			// if item was insert with success
			// TODO: when related tables inserted, the ID returned is not from the actual $item. Check it.
			if ($id) {

				// commit transaction
				R::commit();

				// build api response array
				$payload = array(
					'id' 		=> $id,
					'message' 	=> getMessage('CREATE_SUCCESS') . ' (id: '.$id.')',
				);
				
				//output response
				return $response->withJson($payload)->withStatus(201);
			}

			// else something happened, throw error
			else {
				$err = getMessage('CREATE_FAIL');
				throw new \Exception($err, 1);
			}
		}
		catch(\Exception $e) {
			R::rollback();
			throw $e;
		}
	}

	/**
	  * Deletes existing item from database.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function destroy ($request, $response, $args) {

		// dispense 'edge'
		$item = R::load( $args['edge'], $args['id'] );

		// if $item exists
		if (!empty($item['id'])) {

			// check if edge has one-to-many relationships
			if ($this->edgeHasChild($args['edge'])) {
				foreach ($this->hierarchy[$args['edge']] as $child) {
					$ownList = $item['own' . ucfirst($child) . 'List'];
					// check if there are children and return bad request response
					if (!empty($ownList)) {
						$err = array('error' => true, 'message' => getMessage('DESTROY_FAIL_CHILD_EXISTS'));
						$this->logger->notice($err['message'], $args);
						return $response->withJson($err)->withStatus(400);
					}
				}
			}

			// no childs? let's start the delete transaction
			R::begin();
			try {

				// TODO: If edge contains `_upload` field, we should unlink the files.
				// destroy item, commit if success
			    R::trash($item);
				R::commit();

				// build api response array
				$payload = array(
					'id' 		=> $args['id'],
					'message' 	=> getMessage('DESTROY_SUCCESS') . ' (id: '.$args['id'].')',
				);

				// output response
				return $response->withJson($payload);

			} catch (\Exception $e) {
				R::rollback();
				throw $e;
			}
		}
		else {
			$err = array('error' => true, 'message' => getMessage('NOT_FOUND'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(404);
		}
	}

	/**
	  * Returns all API edges dynamically from database collections table schema and properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function edges($request, $response, $args) {

		// initialize edges tree array
		$edges 	= array();

		// if not empty edges, build root
		if (!empty($this->config['edges']['list'])) {

			// build $edges array and properties
			foreach ($this->config['edges']['list'] as $k => $edge) {
				if (!in_array($edge, $this->config['edges']['blacklist'])) {
					$edges[$edge] = array(
						'name' 			=> $edge,
						'title' 		=> getCaption('edges', $edge, $edge),
						'count' 		=> R::count($edge),
						'icon' 			=> getCaption('icon', $edge, $edge),
						'has_parent' 	=> $this->edgeHasParent($edge),
						'has_child' 	=> $this->edgeHasChild($edge)
					);
				}
			}

			// if there's hierarchy, let's build our tree
			if (!empty($this->hierarchy)) {

				// define $root to $edges tree array
				$root = $edges;

				// append children to $edges tree root array
				foreach ($edges as $edge => $object) {
					$edges[$edge]['relations'] = $this->edgeAppendRelations($edge, $root, $edges);
					if (empty($edges[$edge]['relations'])) {
						unset($edges[$edge]['relations']);
					}
				}
			}
		}
		
		// build api response payload
		$payload = array(
			'edges' 	=> $edges,
		);

		// output response playload
		return $response->withJson($payload);
	}

	/**
	  * Verifies if an entry exists on database.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function exists ($request, $response, $args) {

		// check if item is retrieved from database
		$item = R::find( $args['edge'], ' id = '.$args['id'] );
		$exists = !empty($item) ? true : false;

		// build api response payload
		$payload = array(
			'edge' 		=> $args['edge'],
			'id' 		=> $args['id'],
			'exists' 	=> $exists
		);
		
		// output response payload
		return $response->withJson($payload);
	}

	/**
	  * Exports to CSV file all entries from a edge database table.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return string CSV file output
	  */
	public function export ($request, $response, $args) {

		// collect data
		$raw = R::findAll( $args['edge'] );
		$data = R::exportAll( $raw, FALSE, array('NULL') );

		if (!empty($data)) {

			// define csv properties
			$headings = array_keys($data[0]);
			$hashdate = str_replace(array(':','-',' '), '', R::isoDateTime());
			$filename = 'export-'.$args['edge'].'-'.$hashdate.'.csv';

			//outstream response
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename='. $filename);
			$outstream = fopen('php://output', 'w');

				// inject field keys to data as csv export table heading
				fputcsv($outstream, $headings);

				// foreach item, outstream fputcsv
				foreach ($data as $row) {
					fputcsv($outstream, $row);
				}

			// fclose
			fclose($outstream);
		}
		else {
			$err = array('error' => true, 'message' => getMessage('EXPORT_EMPTY'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(404);			
		}
	}

	/**
	  * Returns API welcome message.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function hi($request, $response, $args) {

		// build api response payload
		$payload = array(
			'message' => getMessage('HI')
		);
		// output response payload
		return $response->withJson($payload);
	}

	/**
	  * Read entry from database and return properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function read ($request, $response, $args) {
		
		// load item
		$item = R::load( $args['edge'], $args['id'] );

		// if item retrieved
		if (!empty($item['id'])) {

			// foreach $item field, build response read array
			foreach ($item as $field => $value) {
				if (!in_array($field, $this->config['schema']['default']['blacklist'])) {

					// IF field is a password, clean it up
					if ($field == 'password') {
						$value = '';
					}

					// ADD to payload response
					$read[$field] = $value;

					// IF field represents one-to-many relationship
					if (substr($field, -3, 3) == '_id') {
						$parentEdge = substr($field, 0, -3);
						$parent = R::load( $parentEdge, $value );

						// IF parent is retrieved
						if (!empty($parent['id'])) {
							// foreach $parent field, add to response payload array
							foreach ($parent as $parentField => $parentValue) {
								// add to payload response
								$read[$parentEdge][$value][$parentField] = $parentValue;
							}
						}
						else {
							throw new \Exception(getMessage('BROKEN_RELATIONSHIP', 1));
						}
					}
				}
			}

			// VERIFY IF _ MANY-TO-MANY RELATIONSHIP EXISTS
			$relateds = $this->isM2MRelated($args);
			if (!empty($relateds)) {
				foreach ($relateds as $k => $related) {
					$relatedList = $item['shared' . ucfirst($related) . 'List'];
					if (!empty($relatedList)) {
						$i = 0;
						foreach ($relatedList as $k => $relatedObj) {
							foreach ($relatedObj as $relatedField => $relatedValue) {
								$read[$related][$i][$relatedField] = $relatedValue;
							}
							$i++;
						}
					}
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
	  * Retrieves a list of items from database edge and properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  * @param int $query['page'] Query String parameter for paginated results
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function retrieve($request, $response, $args) {

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

		// check if list is not empty
		if (!empty($items)) {
			// list is not empty, let's foreach and build response array
			foreach ($items as $item => $content) {
				foreach ($content as $field => $value) {
					if (in_array($field, $this->config['api']['list_fields'])) {
						$payload[$item][$field] = $value;
					}
				}
			}
		}
		else {
			// list is empty, let's return empty array
			$payload = array();
		}

		// output response
		return $response->withJson($payload);
	}

	/**
	  * Returns edge database table schema and properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function schema ($request, $response, $args) {

		// Build default JSON hyper $schema
		$schema = $this->buildSchema($args['edge']);

		// IF Many-To-Many Relation exists
		$relateds = $this->isM2MRelated($args);
		if (!empty($relateds)) {
			foreach ($relateds as $k => $related) {
				// Build array JSON hyper $schema
				$schema['properties'][$related] = array(
					'edge' 			=> $related,
					'title' 		=> getCaption('fields', $args['edge'], $related),
					'type' 			=> 'array',
					'format' 		=> 'table',
					'uniqueItems' 	=> true,
					'items' 		=> $this->buildSchema($related),
				);
			}
		}

		// build api response payload
		$payload = $schema;

		// output response payload
		return $response->withJson($payload);
	}

	/**
	  * Returns API coming soon message.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function soon ($request, $response, $args) {

		// build api response payload
		$payload = array(
			'message' => getMessage('COMING_SOON')
		);

		// output response payload
		return $response->withJson($payload);
	}

	/**
	  * Updates existing item at database.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function update ($request, $response, $args) {

		// get data from 'body' request payload
		$data = $request->getParsedBody();

		// if no data was sent, return bad request response
		if (empty($data)) {
			$err = array('error' => true, 'message' => getMessage('MISSING_FORMDATA'));
			$this->logger->notice($err['message'], array($args, $data));			
			return $response->withJson($err)->withStatus(400);
		}
		if (isset($data['id']) && $data['id'] != $args['id']) {
			$err = array('error' => true, 'message' => getMessage('INVALID_ID_MATCH_FORMDATA'));
			$this->logger->notice($err['message'], array($args, $data));
			return $response->withJson($err)->withStatus(400);
		}

		// dispense 'edge'
		$item = R::load( $args['edge'], $args['id'] );

		// if item exists at database
		if (!empty($item['id'])) {

			// foreach $data request payload, build array to insert
			foreach ($data as $field => $value) {

				// IF field is not array, just parse it
				if (!is_array($value)) {

					// IF field is a password, hash it up
					if ($field == 'password') {
						$value = md5($value);
					}

					// ADD to update array
					$item[$field] = $value;

				}
				// ELSE is array and defines many-to-many relationship
				else {
					// validate if related edge is valid
					if (in_array($field, $this->config['edges']['list'])) {

						$related = R::dispense($field);

						foreach ($value as $xfield => $xvalue) {
							$related[$xfield] = $xvalue;
						}

						// inject modified current time
						$related['modified']	= R::isoDateTime();

						// inject at shared item list
						$item['shared' . ucfirst($field) . 'List'][] = $related;
					}
					else {
						$err = array('error' => true, 'message' => getMessage('UPDATE_FAIL'));
						$this->logger->notice($err['message'], array($args, $data));
						return $response->withJson($err)->withStatus(400);
					}
				}
			}

			// inject modified current time to update array
			$item['modified'] = R::isoDateTime();

			// let's start the update transaction
			R::begin();

			try {
				// update item
				R::store($item);
				
				// commit transaction
				R::commit();

				// build api response payload
				$payload = array(
					'id' 		=> $args['id'],
					'message' 	=> getMessage('UPDATE_SUCCESS') . ' (id: '.$args['id'].')',
				);

				//output response
				return $response->withJson($payload);
			}
			catch(\Exception $e) {
				R::rollback();
				throw $e;
			}
		}
		else {
			$err = array('error' => true, 'message' => getMessage('NOT_FOUND'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(404);
		}
	}

	/**
	  * Updates user password.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function updatePassword ($request, $response, $args) {

		// get data from 'body' request payload
		$data = $request->getParsedBody();

		// if required data was not sent, return bad request response
		if (empty($data['password']) || empty($data['new_password']) || empty($data['confirm_new_password'])) {
			$err = array('error' => true, 'message' => getMessage('MISSING_FORMDATA'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(400);
		}
		// if required data is not valid, return bad request response
		if ($data['new_password'] != $data['confirm_new_password']) {
			$err = array('error' => true, 'message' => getMessage('UPDATE_PASSWORD_CONFIRM_FAIL'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(400);
		}

		// dispense 'edge'
		$item = R::load( 'users', $args['id'] );

		// if current password is not valid, return bad request response
		if ($item['password'] != md5($data['password'])) {
			$err = array('error' => true, 'message' => getMessage('AUTH_PASS_FAIL'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(400);
		}

		// all set, let's build update array
		$item['password'] = md5($data['new_password']);
		$item['modified'] = R::isoDateTime();

		// let's start the update transaction
		R::begin();
		try {
			// update item, commit if success
			R::store( $item );
			// commit transaction
			R::commit();

			// build api response array
			$payload = array(
				'id' 		=> $args['id'],
				'message' 	=> getMessage('UPDATE_SUCCESS') . ' (id: '.$args['id'].')',
			);
			
			//output response
			return $response->withJson($payload);
		}
		catch(\Exception $e) {
			// rollback transaction
			R::rollback();
			throw $e;
		}
	}

	/**
	  * Auxiliar function to write uploaded files at storage.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface 	$request 	PSR 7 ServerRequestInterface Object
	  * @param Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  * @param array 									$args 		Associative array with current route's named placeholders
	  *
	  * @return Psr\Http\Message\ResponseInterface 		$response 	PSR 7 ResponseInterface Object
	  */
	public function upload ($request, $response, $args) {

		// get data from 'body' request payload
		$data = $request->getParsedBody();

		// check if data was sent
		if (empty($data)) {
			$err = array('error' => true, 'message' => getMessage('MISSING_FORMDATA'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(400);			
		}
		elseif (empty($data['blob'])) {
			$err = array('error' => true, 'message' => getMessage('UPLOAD_FAIL_BLOB_MISSING'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(400);
		}
		elseif ( empty($data['filename']) ) {
			$err = array('error' => true, 'message' => getMessage('UPLOAD_FAIL_FILENAME_MISSING'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(400);
		}

		// explode $data['blob']
		// TODO: this procedure could be done in one line if I use regex. verify correct expression.
		list($type, $data['blob']) = explode(';', $data['blob']);
		list(,$type) = explode(':', $type);
		list(,$data['blob']) = explode(',', $data['blob']);

		// decode blob data
		$content = base64_decode($data['blob']);

		// define path
		$datepath 	= str_replace('-', '/', R::isoDate()) . '/';
		$fullpath 	= $this->config['api']['uploads']['basepath'] . $datepath;

		// define hash unique filename
		$tmp  = explode(".", $data['filename']);
		$ext  = array_pop($tmp);
		$name = implode('_', $tmp);
		$hash = strtotime(R::isoDateTime());
		$filename = $name . '-' . $hash . '.' . $ext;

		// if folder doesn't exist, mkdir 
		if (!file_exists($fullpath)) {
			mkdir($fullpath, 0777, true);
		}

		// if folder is writable, put contents 
		if (is_writable($fullpath)) {
			file_put_contents($fullpath . $filename, $content);
		}

		// if it was written, success
		if (file_exists($fullpath . $filename)) {
		
			// build api response array
			$payload = array(
				'fullpath' 	=> $fullpath,
				'filename' 	=> $filename,
				'href' 		=> $fullpath . $filename,
				'message' 	=> getMessage('UPLOAD_SUCCESS'),
			);
		
			//output response
			return $response->withJson($payload)->withStatus(201);
		}		
		else {
			$err = array('error' => true, 'message' => getMessage('UPLOAD_FAIL'));
			$this->logger->notice($err['message'], $args);
			return $response->withJson($err)->withStatus(400);
		}
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
	public function test($request, $response, $args) {
		$this->logger->info('Elijah says: hey mom, I\'m being logged!' );
		return $response->withJson(array("message" => "Hello, tested!"));
	}

/** PRIVATE - SlimBean\Api Class Private Functions **/

	private function buildSchema ($edge) {

		// get database schema
		$raw = R::getAssoc( 'SHOW FULL COLUMNS FROM ' . $edge );

		// define default schema array
		$schema = array(
			'edge' 					=> $edge,
			'title' 				=> getCaption('edges', $edge, $edge),
			'icon' 					=> getCaption('icon', $edge, $edge),
			'type' 					=> 'object',
			'required' 				=> true,
			'additionalProperties' 	=> false,
			'properties' 			=> array(),
			'raw' 					=> $raw
		);

		// fill properties node into schema response array
		foreach ($schema['raw'] as $field => $properties) {

			// check if field is not at config blacklist
			if (!in_array($field, $this->config['schema']['default']['blacklist'])) {

				// check if field defines one-to-many relationship
				if (substr($field, -3, 3) == '_id') {
					$parent = substr($field, 0, -3);

					// define default JSON hyper schema for One-To-Many select box.
					$schema['properties'][$field] = array(
						'edge' 				=> $parent,
						'title' 			=> getCaption('fields', $schema['edge'], $parent),
						'type' 				=> 'integer',
						'required'	 		=> true,
						'minLength'	 		=> 1,
						'enum' 				=> array(),
						'options' 			=> array(
							'enum_titles' 		=> array(),
							'selectize_options' => array(
								'create' => false // TODO: for some reason this selectize_options for false "create" is not working. WTF?! 
							)
						)
					);

					$parentOptions = R::getAssoc( 'SELECT id, name FROM ' . $parent );
					foreach ($parentOptions as $enum => $enumTitle) {
						$schema['properties'][$field]['enum'][] = $enum;
						$schema['properties'][$field]['options']['enum_titles'][] = $enumTitle;
					}
				}

				// check if field defines _upload input
				else if (substr($field, -7, 7) == '_upload') {
					$upload = str_replace('_upload', '', $field);

					// define default JSON hyper schema for Upload input.
					$schema['properties'][$field] = array(
						'title' 	=> getCaption('fields', $schema['edge'], $upload),
						'type'		=> 'string',
						'format' 	=> 'url',
						'required'	=> true,
						'minLength' => 0,
						'maxLength' => 128,
			  			'options'	=> array(
			  				'upload' 	=> true,
			  			),
						'links' 	=> array(
							array(
								'rel' 	=> '',
								'href' 	=> '{{self}}',
							)
						)
					);
				}

				// else, field is literal and we can go on
				else {

					// prepare data
					$dbType 	= preg_split("/[()]+/", $schema['raw'][$field]['Type']);
					$type 		= $dbType[0];
					$format 	= $dbType[0];
					$maxLength 	= (!empty($dbType[1]) ? (int)$dbType[1] : '');
					$minLength 	= ($schema['raw'][$field]['Null'] == 'YES' ? 0 : 1);

					// converts db type to json-editor expected type
					if (array_key_exists($type, $this->config['schema']['default']['type'])) {
						$type = $this->config['schema']['default']['type'][$type];
					}

					// converts db format to json-editor expected format
					if (array_key_exists($format, $this->config['schema']['default']['format'])) {
						if ($format == 'varchar' && $maxLength > 256) {
							$format = 'textarea';
						}
						else {
							$format = $this->config['schema']['default']['format'][$format];
						}
					}

					// builds default properties array to json-editor
					$schema['properties'][$field] = array(
						'title' 		=> getCaption('fields', $edge, $field),
						'type'			=> $type,
						'format' 		=> $format,
						'required'	 	=> true,
						'minLength' 	=> $minLength,
						'maxLength'		=> $maxLength
					);

					// array merge to custom properties defined at config
					if (isset($this->config['schema']['custom']['fields'][$field])) {
						$schema['properties'][$field] = array_merge($schema['properties'][$field], $this->config['schema']['custom']['fields'][$field]);
					}

					// add '*' to field title if required.
					if ($schema['properties'][$field]['minLength'] > 0) {
						$schema['properties'][$field]['title'] = $schema['properties'][$field]['title'] . '*';
					}
				}
			}
		}
		return $schema;
	}

	private function edgeAppendRelations ($edge, $root, $edges) {
		$relations = array();

		if ($root[$edge]['has_parent']) {
			$parents = $this->edgeGetParents($edge);
			
			foreach ($parents as $parent) {
				if (!in_array($parent, $this->config['edges']['blacklist'])) {
					$relations[$parent] = $edges[$parent];
					$relations[$parent]['type'] = 'one-to-many';
					
					// let's go deeper - gettin' recursive
					if ($relations[$parent]['has_parent']) {
						$relations[$parent]['relations'] = $this->edgeAppendRelations($parent, $relations, $edges);
						if (empty($relations[$parent]['relations'])) {
							unset($relations[$parent]['relations']);
						}
					}
				}
			}
		}
		return $relations;
	}

	private function edgeGetParents ($edge) {
		$parents = array();

		if (!empty($this->hierarchy)) {
			foreach ($this->hierarchy as $parent => $children) {
				foreach ($children as $child) {
					if ($edge == $child) {
						array_push($parents, $parent);
					}
				}
			}
		}

		// return parents array
		return $parents;
	}

	private function edgeHasParent ($edge) {

		// return bool if found in multi-dimensional array
		foreach ($this->hierarchy as $values) {
			if (in_array($edge, $values)) {
				return true;
			}
		}
		return false;
	}

	private function edgeHasChild ($edge) {

		// return bool if found in array
		return ( array_key_exists($edge, $this->hierarchy) ? true : false );
	}

	/**
	  * Sets $hierarchy array private var with relationships between edges at database tables.
	  *
	  * @param string $edge Optional parameter to filter results by only one edge.
	  *
	  * @return array Hierarchy array with Parent Key and Children Values.
	  */
	private function setHierarchy() {

		// build hierarchy array, if exists
		$hierarchy = R::getAssoc('
			SELECT REFERENCED_TABLE_NAME as parent, GROUP_CONCAT(TABLE_NAME) as child
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE REFERENCED_TABLE_NAME IS NOT NULL
			GROUP BY parent'
		);

		// if not empty hierarchy, iterate
		if (!empty($hierarchy)) {
			foreach ($hierarchy as $parent => $child) {
				$hierarchy[$parent] = explode(',', $child);
			}
		}

		// and sets hierarchy array
		$this->hierarchy = $hierarchy;
	}

	private function isM2MRelated ($args) {
		$relateds = array();
		foreach ($this->config['edges']['relations'] as $k => $edge) {
			$relation = explode('_', $edge);

			// IF RELATION WAS FOUND FOR THIS EDGE
			if (in_array($args['edge'], $relation)) {
				// remove "self"
				unset($relation[array_search($args['edge'], $relation)]);
				// stringify related
				$related = array_pop($relation);
				// array push
				array_push($relateds, $related);
			}
		}
		return $relateds;
	}

}
/* .end Api.php */