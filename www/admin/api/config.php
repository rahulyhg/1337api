<?php

	/* CONFIG LOCALE - MySQL */
	date_default_timezone_set('America/Sao_Paulo');

	use Sinergi\Dictionary\Dictionary;
	$config['language'] = array(
		'locale' 	 => 'pt_BR',
		'base_dir' 	 => __DIR__ . '/locale',
	);
	$caption = new Dictionary($config['language']['locale'], $config['language']['base_dir'] );

	/* CONFIG DB - MySQL */
	$config['db'] = array(
		'host' => 'mysql:host=186.202.152.193; dbname=umstudiohomolo12',
		'user' => 'umstudiohomolo12',
		'pass' => 'studio0001',
	);

	$config['auth'] = array(
		'jwtKey' 	=> 'VYFBH3XEduP724aIhZESyk3Ru+h3sxI5w0nbRwnYrrbymjvnf3ZCWkqJO26V4AhYQAtQk02dHO1wbi4Xjs9QUA==',
		'jwtIssuer' => $_SERVER['HTTP_HOST'],
	);

	/* CONFIG API - GLOBALS */
	$config['api'] = array(
		'debug' 	=> FALSE,
		'actions' 	=> array(
			'get' 	=> array('hi', 'edges', 'search', 'list', 'read', 'count', 'schema', 'exists', 'export'),
			'post' 	=> array('create', 'update','updatePassword', 'upload', 'destroy'),
		),
		'edges'		=> array(
			'blacklist' 	=> array('uploads','page_uploads'),
			),
		'params' => array(
			'pagination' => 5,
		),
		'messages'	=> array(
			'hi' 		=> 'Hi Elijah, your API is UP!',
			'forbidden' => 'elijah says: NO.'
		),
	);

	/* CONFIG FORM BUILDER - GLOBALS */
	$config['schema']['default'] = array(
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
	);

	/* CONFIG API - FORMAT */

	$config['schema']['custom'] = array(
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
	);

?>