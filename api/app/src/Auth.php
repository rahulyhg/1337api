<?php 
namespace SlimBean;

use \RedBeanPHP\Facade as R;
use \Firebase\JWT\JWT;

class Auth {

	private $api;
	private $config;
	private $caption;

	public function __construct($api, $config, $caption)
	{
		$this->api = $api;
		$this->config = $config;
		$this->caption = $caption;
	}



}
/* .end api.php */