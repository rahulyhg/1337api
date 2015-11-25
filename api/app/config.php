<?php
/**
 * SlimBean global settings configuration file.
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
		'host' => 'mysql:host=186.202.152.193; dbname=umstudiohomolo12',
		'user' => 'umstudiohomolo12',
		'pass' => 'studio0001',
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
		'debug' => TRUE,
		'list' 	=> array(
			'itemsPerPage' 	=> 5,
			'fields' 		=> array('id', 'name', 'created', 'modified')
		),
		'uploads' => array(
			'basepath' => '../uploads/',
		),
	],

	// $config EDGES (Default Values)
	'edges' => [
		'list'		=> array(),
		'blacklist' => array('images'),
		'relations' => array(),
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