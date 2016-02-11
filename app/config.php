<?php
/**
 * eApi global settings configuration file.
 *
 * @author  Elijah Hatem <elias.hatem@gmail.com>
 * @license MIT
 */

/* ***************************************************************************************************
** PHP CONSTANTS *************************************************************************************
*************************************************************************************************** */ 
date_default_timezone_set('America/Sao_Paulo');

/* ***************************************************************************************************
** CONFIG ARRAY **************************************************************************************
*************************************************************************************************** */ 
return array(

	// $config DB (MySQL Database)
	'db' => [
		'host' => 'mysql:host=179.188.16.36; dbname=umstudiohomolo19',
		'user' => 'umstudiohomolo19',
		'pass' => 'n0v0g2oi6',
	],

	// $config AUTH (JWT - JSON Web Token)
	'auth' => [
		'jwt' => array(
			'issuer' 	=> $_SERVER['HTTP_HOST'],
			'expire' 	=> 3600,	
			'key' 		=> 'VYFBH3XEduP724aIhZESyk3Ru+h3sxI5w0nbRwnYrrbymjvnf3ZCWkqJO26V4AhYQAtQk02dHO1wbi4Xjs9QUA==',
						// the key is generated by using: base64_encode(openssl_random_pseudo_bytes(64));
		)
	],

	// $config LOCALE
	'locale' => [
		'code' 		=> 'pt_BR',
		'basepath' 	=> __DIR__ . '/locale',
	],

	// $config API (Global Values)
	'api' => [
		'debug' 	=> FALSE,
		'edges' 	=> array(
			'blacklist' => array('images')
		),
		'read' 	=> array(
			'blacklist' 	=> array('modified'), 		
		),
		'list' 	=> array(
			'itemsPerPage' 	=> 15,
			'fields' 		=> array('id', 'name', 'created', 'modified')
		),
		'uploads' => array(
			'basePath' => 'uploads/',
		),
	],

	// $config SCHEMA (Form Builder)
	'schema' => [
		'default' => array(
			'blacklist' 	=> array('id','created','modified'),
			'type' 			=> array(
				'date' 		=> 'string',
				'int' 		=> 'integer',
				'text' 		=> 'string',
				'tinyint' 	=> 'boolean',
				'varchar' 	=> 'string',
			),
			'format' 		=> array(
				'varchar' 	=> 'string',
				'int' 		=> 'number',
				'date' 		=> 'date',
				'text' 		=> 'textarea',
				'tinyint' 	=> 'checkbox',
			),
		),
		'custom' => array(
			'fields' => array(
				'description' => array(
					'type'		=> 'string',
					'format'	=> 'html',
					'options' 	=> array(
						'wysiwyg' 	=> true,
						),
				),
				'email' => array(
					'format' 	=> 'email',
				),
				'phone' => array(
					'format' 	=> 'tel',
				),
				'password' => array(
					'type' 		=> 'string',
					'format' 	=> 'password',
				),
			),
		)
	],

	// $config SLIM (Router)
	'slim' => [
		'settings' => array(
			// monolog settings
			'logger' => array('name' => 'app','path' => __DIR__ . '/../logs/api.log'),
		),
	]
	
);

?>