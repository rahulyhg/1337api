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
			'get' 	=> array('hi', 'edges', 'search', 'list', 'read', 'count', 'schema', 'exists'),
			'post' 	=> array('create'),
			'put' 	=> array('update'),
			'del' 	=> array('destroy'),
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
			'name' => array(
				'title' 	=> 'Nome',
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
			'image' => array(
				'title' 	=> 'Imagem',
				'type'		=> 'string',
				'format' 	=> 'url',
  				'options'	=> array(
  					'upload' 	=> true,
  				),
				'links' 	=> array(
					array(
						'href' 	=> '{{self}}',
					),
				),
			),

		),
	);



?>