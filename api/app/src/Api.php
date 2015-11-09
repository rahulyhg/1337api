<?php 
namespace SlimBean;

class Api {

    public function __construct()
    {

    }

    public function test($request, $response, $args)
	{
		return $response->withJson(array("message" => "teste"));
	}

}
/* .end api.php */