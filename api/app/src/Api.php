<?php 
namespace SlimBean;

class Api {

	private $config;
	private $caption;

	public function __construct($config, $caption)
	{
		$this->config = $config;
		$this->caption = $caption;
	}

	public function test($request, $response, $args)
	{
		return $response->withJson(array("message" => "teste", "config" => $this->config));
	}

	public function hi($request, $response, $args) 
	{
		if( empty($this->caption['messages']) ) {

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




}
/* .end api.php */