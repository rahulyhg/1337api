<?php

	/* CONFIG DB - MySQL */
	$config['db'] = array(
		'host' => 'mysql:host=186.202.152.193; dbname=umstudiohomolo12',
		'user' => 'umstudiohomolo12',
		'pass' => 'studio0001',
	);

	/* CONFIG API - GLOBALS */
	$config['api'] = array(
		'debug' 	=> FALSE,
		'actions' 	=> array(
			'get' 	=> array('hi','edges','search','read', 'count', 'schema'),
			'post' 	=> array('create'),
			'put' 	=> array('update'),
			'del' 	=> array('destroy'),
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
			'title' => array(
				'title' 	=> 'Título',
			),
			'description' => array(
				'title' 	=> 'Descrição',
				'type'		=> 'string',
				'format'	=> 'html',
				'options' 	=> array(
					'wysiwyg' 	=> true,
					),
			),
			'email' => array(
				'title' 	=> 'E-Mail',
				'format' 	=> 'email',
			),
			'phone' => array(
				'title' 	=> 'Telefone',
				'format' 	=> 'tel',
			),
		),
	);



?>