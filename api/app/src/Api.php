<?php 
namespace SlimBean;

use \RedBeanPHP\Facade as R;

class Api {

	private $api;
	private $config;
	private $caption;

	public function __construct($api, $config, $caption)
	{
		$this->api = $api;
		$this->config = $config;
		$this->caption = $caption;
	}

	public function test($request, $response, $args) {
		return $response->withJson(array("message" => "teste", "config" => $this->config));
	}

	public function hi($request, $response, $args) {
		if( !empty($this->caption['messages']) ) {

			// build api response payload
			$payload = array(
				'message' => getMessage('HI')
			);
			
			// output response payload
			return $response->withJson($payload);
		} 
		else {
			$errorMessage = 'Arquivo de mensagens não encontrado.';
			throw new \Exception($errorMessage, 1);
		}
	}

	public function edges($request, $response, $args) {

		// build edges list
		if ( !empty($this->api['edges']) ) {
			$edges = array();
			foreach ($this->api['edges'] as $k => $edge) {
				if( !in_array($edge, $this->config['api']['edges']['blacklist']) ) {

					$edges[$edge] = array(
						'name' 			=> $edge,
						'title' 		=> getCaption('edges', $edge, $edge),
						'count' 		=> R::count($edge),
						'icon' 			=> getCaption('icon', $edge, $edge),
						'has_parent' 	=> false,
						'has_child' 	=> false,
					);

				};
			};
		}
		else {
			$errorMessage = getMessage('EDGES_FAIL');
			throw new \Exception($errorMessage, 1);
		}

		// build hierarchy array, if exists		
		$hierarchyArr = R::getAll('
			SELECT TABLE_NAME as child, REFERENCED_TABLE_NAME as parent
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE REFERENCED_TABLE_NAME IS NOT NULL
		');

		if( !empty($hierarchyArr) ) {
			foreach ($hierarchyArr as $k => $v) {
				if(empty($hierarchy[$v['child']])){
					$hierarchy[$v['child']] = array();
				} 
				array_push($hierarchy[$v['child']], $v['parent']);
			}
		}
		else{
			$hierarchy = array();
		}

		// if not empty hierarchy, build depth
		if( !empty($hierarchy) ) {

			// build hierarchy list - depth 1
			foreach ($edges as $edge => $obj) {
				if( array_key_exists($edge, $hierarchy) ){
					$edges[$edge]['has_parent'] = true;

					foreach ($hierarchy[$edge] as $y => $z) {
						$edges[$z]['has_child'] = true;
						$edges[$edge]['parent'][$z] = $edges[$z];
					}

					// build hierarchy list - depth 2
					if( $edges[$edge]['has_parent'] ) {
						foreach ($edges[$edge]['parent'] as $parentBean => $parentObj) {
							if(array_key_exists($parentBean, $hierarchy)){
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

		// build api response payload
		$payload = array(
			'edges' 	=> $edges,
			'actions' 	=> $this->config['api']['actions']
		);

		// output response playload
		return $response->withJson($payload);
	}

	public function retrieve($request, $response, $args) {

		$args['query'] = $request->getQueryParams();

		// check if request is paginated or ALL
		if( !empty($args['query']['page']) ){
			if ( is_numeric($args['query']['page']) ) {
				// param page exists, let's get this page 
				$limit = $this->config['api']['params']['pagination'];
				$items = R::findAll( $args['edge'], 'ORDER BY id DESC LIMIT '.(($args['query']['page']-1)*$limit).', '.$limit);
			}
			else {
				$errorMessage = getMessage('INVALID_REQUEST');
				throw new Exception($errorMessage, 1);
			}
		} 
		else {
			// param page doesn't exist, let's get all
			$items = R::findAll( $args['edge'], 'ORDER BY id DESC' );
		}

		// check if list is not empty
		if( !empty($items) ){
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

	public function schema ($request, $response, $args) {

		// read database schema
		$schema['raw'] = R::getAssoc('SHOW FULL COLUMNS FROM '.$args['edge']);

		// if raw schema found
		if( !empty($schema['raw']) ) {

			// define schema response payload array
			$payload = array(
				'bean' 					=> $args['edge'],
				'title' 				=> getCaption('edges', $args['edge'], $args['edge']),
				'icon' 					=> getCaption('icon', $args['edge'], $args['edge']),
				'type' 					=> 'object',
				'required' 				=> true,
				'additionalProperties' 	=> false,
				'properties' 			=> array()
			);

			// fill properties node into schema response array
			foreach ($schema['raw'] as $field => $properties) {

				// check if field is not at config blacklist
				if( !in_array($field, $this->config['schema']['default']['blacklist']) ){

					// check if field defines one-to-many relationship
					if( substr($field, -3, 3) == '_id' ){
						$parent = substr($field, 0, -3);

						$payload['properties'][$field] = array(
							'type' 				=> 'integer',
							'title' 			=> getCaption('fields', $payload['bean'], $parent),
							'required'	 		=> true,
							'minLength'	 		=> 1,
							'enum' 				=> array(),
							'options' 			=> array(
								'enum_titles' 	=> array()
							)
						);

						$parentOptions = R::getAssoc( 'SELECT id, name FROM '.$parent );

						foreach ($parentOptions as $key => $value) {
							$payload['properties'][$field]['enum'][] = $key;
							$payload['properties'][$field]['options']['enum_titles'][] = $value;
						}

					}
					else {
					// else, field is literal and we can go on

						// prepare data
						$dbType 	= preg_split("/[()]+/", $schema['raw'][$field]['Type']);
						$type 		= $dbType[0];
						$format 	= $dbType[0];
						$maxLength 	= (!empty($dbType[1]) ? (int)$dbType[1] : '');
						$minLength 	= ($schema['raw'][$field]['Null'] == 'YES' ? 0 : 1);

						// converts db type to json-editor expected type
						if( array_key_exists($type, $this->config['schema']['default']['type']) ) {
							$type = $this->config['schema']['default']['type'][$type];
						}

						// converts db type to json-editor expected format
						if( array_key_exists($format, $this->config['schema']['default']['format']) ) {

							if($format == 'varchar' && $maxLength > 256){
								$format = 'textarea';
							}
							else {
								$format = $this->config['schema']['default']['format'][$format];
							}
						}

						// builds default properties array to json-editor
						$payload['properties'][$field] = array(
							'type'			=> $type,
							'format' 		=> $format,
							'title' 		=> getCaption('fields', $args['edge'], $field),
							'required'	 	=> true,
							'minLength' 	=> $minLength,
							'maxLength'		=> $maxLength
						);

						// array merge to custom properties defined at config
						if( isset($this->config['schema']['custom']['fields'][$field]) ) {
							$payload['properties'][$field] = array_merge($payload['properties'][$field], $this->config['schema']['custom']['fields'][$field]);
						}

						// add '*' to field title if required.
						if( $payload['properties'][$field]['minLength'] > 0 ) {
							$payload['properties'][$field]['title'] = $payload['properties'][$field]['title'] . '*';
						}
					}
				}

				// ADD RAW STRUCTURE NODE TO SCHEMA
				// IF FIELD DEFINES ONE-TO-MANY RELATIONSHIP
				if( substr($field, -3, 3) == '_id' ) {
					$parentBean = substr($field, 0, -3);
					$parent = R::getAssoc('DESCRIBE '. $parentBean);

					foreach ($parent as $field => $properties) {
						$payload['structure'][$parentBean] = array(
							'field' 		=> $field,
							'properties' 	=> $properties,
						);
					}
				}
				// ELSE, FIELD DEFINES
				else {
					$payload['structure'][$field] = array(
						'field' 		=> $field,
						'properties' 	=> $properties,
					);
				}
			}

			// IF _UPLOADS MANY-TO-MANY RELATIONSHIP EXISTS
			if( in_array($args['edge'] .'_uploads', $this->api['edges']) ) {
			
				$payload['properties']['uploads_id'] = array(
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

			// output response payload
			return $response->withJson($payload);
		}
		else {
			throw new Exception("Error Processing Request (edge raw schema not found)", 1);
		}
	}


}
/* .end api.php */