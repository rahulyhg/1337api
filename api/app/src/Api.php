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


}
/* .end api.php */