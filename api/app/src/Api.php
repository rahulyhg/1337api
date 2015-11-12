<?php 
namespace SlimBean;

use \RedBeanPHP\Facade as R;
use Psr\Log\LoggerInterface;

class Api {

	private $config;
	private $logger;

	public function __construct($config, LoggerInterface $logger) {
		$this->config 	= $config;		
		$this->logger 	= $logger;
	}

	/**
	  * API test method.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
	  */
	public function test($request, $response, $args) {
		$this->logger->info('Elijah says: hey mom, I\'m being logged!' );
		return $response->withJson(array("message" => "Hello, tested!"));
	}

	/**
	  * Returns API welcome message.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
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
	  * Returns all API edges dynamically from database table schema and properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
	  */
	public function edges($request, $response, $args) {

		// initialize edges array
		$edges = array();
		
		if (!empty($this->config['edges']['list'])) {

			// build $edges array and properties
			foreach ($this->config['edges']['list'] as $k => $edge) {
				if (!in_array($edge, $this->config['edges']['blacklist'])) {
					$edges[$edge] = array(
						'name' 			=> $edge,
						'title' 		=> getCaption('edges', $edge, $edge),
						'count' 		=> R::count($edge),
						'icon' 			=> getCaption('icon', $edge, $edge),
						'has_parent' 	=> false,
						'has_child' 	=> false,
					);
				}
			}

			// if hierarchy exists, iterates parent and child properties to $edges array
			if (!empty($this->getEdgesHierarchy())) {
				$hierarchy = $this->getEdgesHierarchy();

				// build hierarchy list - depth 1
				foreach ($edges as $edge => $obj) {
					if (array_key_exists($edge, $hierarchy)) {
						$edges[$edge]['has_parent'] = true;

						foreach ($hierarchy[$edge] as $y => $z) {
							$edges[$z]['has_child'] = true;
							$edges[$edge]['parent'][$z] = $edges[$z];
						}

						// build hierarchy list - depth 2
						if ($edges[$edge]['has_parent']) {
							foreach ($edges[$edge]['parent'] as $parentBean => $parentObj) {
								if (array_key_exists($parentBean, $hierarchy)) {
									$edges[$edge]['parent'][$parentBean]['has_parent'] = true;
									foreach ($hierarchy[$parentBean] as $y => $z) {
										$edges[$edge]['parent'][$parentBean]['parent'][$z] = $edges[$z];
									}
								}
							}
						}
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
	  * Retrieves a list of items from database edge and properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  * @param int $query['page'] Query String parameter for paginated results
	  *
	  * @return Psr\Http\Message\ResponseInterface
	  */
	public function retrieve($request, $response, $args) {

		// get query string parameters
		$args['query'] = $request->getQueryParams();

		// check if request is paginated or ALL
		if (!empty($args['query']['page'])) {
			if (is_numeric($args['query']['page'])) {
				// param page exists, let's get this page
				$limit = $this->config['api']['params']['pagination'];
				$items = R::findAll( $args['edge'], 'ORDER BY id DESC LIMIT '.(($args['query']['page']-1)*$limit).', '.$limit);
			}
			else {
				$errorMessage = getMessage('INVALID_REQUEST');
				throw new \Exception($errorMessage, 1);
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
					$payload[$item][$field] = $value;
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
	  * Counts items from database edge and properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
	  */
	public function count ($request, $response, $args) {

		// define response vars
		$count = R::count( $args['edge'] );
		$limit = $this->config['api']['params']['pagination'];

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
	  * Returns edge database table schema and properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
	  */
	public function schema ($request, $response, $args) {

		// get database schema
		$raw = R::getAssoc('SHOW FULL COLUMNS FROM '.$args['edge']);

		// if raw schema found
		if (!empty($raw)) {

			// define default schema array
			$schema = array(
				'bean' 					=> $args['edge'],
				'title' 				=> getCaption('edges', $args['edge'], $args['edge']),
				'icon' 					=> getCaption('icon', $args['edge'], $args['edge']),
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

						$schema['properties'][$field] = array(
							'type' 				=> 'integer',
							'title' 			=> getCaption('fields', $schema['bean'], $parent),
							'required'	 		=> true,
							'minLength'	 		=> 1,
							'enum' 				=> array(),
							'options' 			=> array(
								'enum_titles' 	=> array()
							)
						);

						$parentOptions = R::getAssoc( 'SELECT id, name FROM '.$parent );

						foreach ($parentOptions as $key => $value) {
							$schema['properties'][$field]['enum'][] = $key;
							$schema['properties'][$field]['options']['enum_titles'][] = $value;
						}
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
							'type'			=> $type,
							'format' 		=> $format,
							'title' 		=> getCaption('fields', $args['edge'], $field),
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

			// IF _UPLOADS MANY-TO-MANY RELATIONSHIP EXISTS
			if (in_array($args['edge'] .'_uploads', $this->config['edges']['list'])) {
			
				$schema['properties']['uploads_id'] = array(
					'title' 	=> 'Imagem',
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

			// build api response payload
			$payload = $schema;

			// output response payload
			return $response->withJson($payload);
		}
		else {
			$err = array('error' => true, 'message' => getMessage('SCHEMA_NOTFOUND'));
			return $response->withJson($err)->withStatus(404);
		}
	}

	/**
	  * Read entry from database and return properties related.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
	  */
	public function read ($request, $response, $args) {
		
		// load item
		$item = R::load( $args['edge'], $args['id'] );

		// if item retrieved
		if(!empty($item['id'])) {

			// foreach $item field, build response read array
			foreach ($item as $field => $value) {
				if (!in_array($field, $this->config['schema']['default']['blacklist'])) {

					// add to payload response
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
							throw new \Exception('Error Processing Request (Parent Relationship Broken with Parent ID: "'.$value.'" FROM PARENT TABLE: "'.$parentEdge.'" NOT FOUND)', 1);
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
			return $response->withJson($err)->withStatus(404);
		}
	}

	/**
	  * Verifies if an entry exists on database.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
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
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return string CSV file output
	  */
	public function export ($request, $response, $args) {

		// collect data
		$raw = R::findAll( $args['edge'] );

		if (!empty($raw)) {
			
			// export it all
			$data = R::exportAll( $raw, FALSE, array('NULL') );

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
			$err = array('error' => true, 'message' => getMessage('EDGE_NOTFOUND'));
			return $response->withJson($err)->withStatus(404);			
		}
	}

	/**
	  * Returns API coming soon message.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
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
	  * Inserts new item at database.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
	  */
	public function create ($request, $response, $args) {

		// get data from 'body' request payload
		$data = $request->getParsedBody();

		// if no data was sent, return bad request response
		if (empty($data)) {
			$err = array('error' => true, 'message' => getMessage('DATA_MISSING'));
			return $response->withJson($err)->withStatus(400);
		}

		// dispense 'edge'
		$item = R::dispense( $args['edge'] );

		// foreach $data request payload, build array to insert
		foreach ($data as $field => $value) {

			// IF field is an id, throw exception
			if ($field == 'id') {
				throw new \Exception("Error Processing Request (field `id` is not allowed when creating a resource)", 1);
			}

			// IF field defines uploads many-to-many relationship
			else if ($field == 'uploads_id' && in_array($args['edge'] .'_uploads', $this->config['edges']['list'])) {
				$upload = R::dispense( 'uploads' );
				$upload->id = $value;
				$item->sharedUploadList[] = $upload;
			}

			// IF field is a password, hash it up
			else if ($field == 'password') {
				$item[$field] = md5($value);
			}

			// ELSE field is literal, go on
			else {
				$item[$field] = $value;
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
				$errorMessage = getMessage('CREATE_FAIL');
				throw new \Exception($errorMessage, 1);
			}
		}
		catch(\Exception $e) {

			// rollback transaction
			R::rollback();
			throw $e;
		}
	}

	/**
	  * Updates existing item at database.
	  *
	  * @param Psr\Http\Message\ServerRequestInterface $request Request Object
	  * @param Psr\Http\Message\ResponseInterface $response Response Object
	  * @param array $args Wildcard arguments from Request URI
	  *
	  * @return Psr\Http\Message\ResponseInterface
	  */
	public function update ($request, $response, $args) {

		// get data from 'body' request payload
		$data = $request->getParsedBody();

		// if no data was sent, return bad request response
		if (empty($data)) {
			$err = array('error' => true, 'message' => getMessage('DATA_MISSING'));
			return $response->withJson($err)->withStatus(400);
		}

		// dispense 'edge'
		$item = R::load( $args['edge'], $args['id'] );

		// foreach $data request payload, build array to insert
		foreach ($data as $field => $value) {

			// IF field is an id and does not match, throw exception
			if ($field == 'id' && $value != $args['id']) {
				throw new \Exception("Error Processing Request (field `id` does not match with request)", 1);
			}

			// IF field defines uploads many-to-many relationship
			else if ($field == 'uploads_id' && in_array($req['edge'] .'_uploads', $this->config['edges']['list'])) {
				$upload = R::dispense( 'uploads' );
				$upload->id = $value;
				$item->sharedUploadList[] = $upload;
			}

			// IF field is a password, hash it up
			else if ($field == 'password') {
				$item[$field] = md5($value);
			}

			// ELSE field is literal, go on
			else {
				$item[$field] = $value;			
			}
		}

		// inject modified current time to array to update
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

			// rollback transaction
			R::rollback();
			throw $e;
		}
	}

	public function updatePassword ($request, $response, $args) {

		// get data from 'body' request payload
		$data = $request->getParsedBody();

		if ( empty($data) ) {
			$err = array('error' => true, 'message' => getMessage('DATA_MISSING'));
			return $response->withJson($err)->withStatus(400);
		}

		// dispense 'edge'
		$item = R::load( 'user', $args['id'] );

		// verify if password is valid
		if ( $item['password'] == md5($data['password']) ) {
			// verify if new password matches
			if ($data['new_password'] == $data['confirm_new_password']) {

				// build array to update
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
			else {
				$err = array('error' => true, 'message' => getMessage('PASSWORD_CONFIRM_FAIL'));
				return $response->withJson($err)->withStatus(400);
			}
		} 
		else {
			$err = array('error' => true, 'message' => getMessage('AUTH_PASS_FAIL'));
			return $response->withJson($err)->withStatus(400);
		}
	}

	public function destroy ($request, $response, $args) {

		// check relationships, if exists
		$hierarchyArr = R::getAll('
			SELECT TABLE_NAME as child, REFERENCED_TABLE_NAME as parent
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE REFERENCED_TABLE_NAME = "'.$args['edge'].'"
		');

		if( !empty($hierarchyArr) ) {

			foreach ($hierarchyArr as $k => $v) {
				
				$childs = R::getAll('
					SELECT * FROM '.$v['child'].' WHERE `'.$v['parent'].'_id` = '.$args['id'].'
				');
				if ( !empty($childs) ) {
					$err = array('error' => true, 'message' => getMessage('DESTROY_FAIL_CHILD_EXISTS'));
					return $response->withJson($err)->withStatus(400);
				}	
			}
		}

		// no relationship? let's go on:
		// dispense 'edge'
		$item = R::load( $args['edge'], $args['id'] );

		// let's start the delete transaction
		R::begin();
		try {

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

			// rollback transaction
			R::rollback();

			throw $e;
		}
	}

	public function upload ($request, $response, $args) {

		// get data from 'body' request payload
		$data = $request->getParsedBody();

		// check if data was sent
		if ( empty($data) ) {
				$err = array('error' => true, 'message' => getMessage('DATA_MISSING'));
				return $response->withJson($err)->withStatus(400);			
		}
		elseif ( empty($data['blob']) ) {
			$err = array('error' => true, 'message' => getMessage('UPLOAD_FAIL_BLOB_MISSING'));
			return $response->withJson($err)->withStatus(400);
		}
		elseif ( empty($data['filesize']) ) {
			$err = array('error' => true, 'message' => getMessage('UPLOAD_FAIL_FILESIZE_MISSING'));
			return $response->withJson($err)->withStatus(400);
		}
		elseif ( empty($data['filename']) ) {
			$err = array('error' => true, 'message' => getMessage('UPLOAD_FAIL_FILENAME_MISSING'));
			return $response->withJson($err)->withStatus(400);
		}

		// explode $data['blob']
			// TODO: this procedure can be done in one line if I use regex. verify correct expression.
			list($type, $data['blob']) = explode(';', $data['blob']);
			list(,$type) = explode(':', $type);
			list(,$data['blob']) = explode(',', $data['blob']);

			// decode blob data
			$content = base64_decode($data['blob']);

		// define path and new filename
			$dateTime 	= R::isoDateTime();
			$date 		= R::isoDate();
			$basepath 	= $this->config['api']['uploads']['basepath'];
			$datepath 	= str_replace('-', '/', $date) . '/';
			$fullpath 	= $basepath . $datepath;
			$hashname 	= md5($data['filename'].'-'.$dateTime) . '.' . end(explode('.', $data['filename']));

		// build insert upload array
		$upload = array(
			'path' 		=> $datepath . $hashname,
			'filename' 	=> $hashname,
			'type' 		=> $type,
			'size' 		=> $data['filesize'],
			'edge' 		=> $args['edge'],
			'created' 	=> R::isoDateTime(),
			'modified' 	=> R::isoDateTime(),
		);

		// write file
			// TODO: we should validate if the file was actually saved at disk.
			// if folder doesn't exist, mkdir 
			if (!file_exists($fullpath)) {
				mkdir( $fullpath, 0777, true );
			};
			file_put_contents($fullpath . $hashname, $content);
		
		// dispense uploads edge
		$item = R::dispense('uploads');

		foreach ($upload as $k => $v) {
			$item[$k] = $v;
		}		

		// let's start the insert transaction
		R::begin();
		try {
			// insert item, returns id if success
			R::store($item);
			$id = R::getInsertID();

			// if item was insert with success
			if( $id ) {

				// commit transaction
				R::commit();

				// build api response array
				$payload = array(
					'id' 		=> $id,
					'message' 	=> getMessage('UPLOAD_SUCCESS') . ' (id: '.$id.')',
				);
				
				//output response
				return $response->withJson($payload)->withStatus(201);
			}
			// else something happened, throw error
			else {
				$errorMessage = getMessage('UPLOAD_FAIL');
				throw new \Exception($errorMessage, 1);
			}
		}
		catch(\Exception $e) {
			// rollback transaction
			R::rollback();

			throw $e;
		}
	}

	private function getEdgesHierarchy() {

		// build hierarchy array, if exists		
		$hierarchy = R::getAssoc('
			SELECT TABLE_NAME as child, GROUP_CONCAT(REFERENCED_TABLE_NAME) as parent
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE REFERENCED_TABLE_NAME IS NOT NULL
			GROUP BY child
		');

		// if not empty hierarchy, iterate
		if (!empty($hierarchy)) {
			foreach ($hierarchy as $child => $parent) {
				$hierarchy[$child] = explode(',', $parent);
			}
		}
		
		return $hierarchy;
	}

}
/* .end Api.php */