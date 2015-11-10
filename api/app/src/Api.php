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

}
/* .end api.php */