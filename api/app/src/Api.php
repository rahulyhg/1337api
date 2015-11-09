<?php 
namespace SlimBean;

class Api {

	private $config;

    public function __construct($config)
    {
    	$this->config = $config;
    }

    public function test($request, $response, $args)
	{
		return $response->withJson(array("message" => "teste", "config" => $this->config));
	}

}
/* .end api.php */