<?php
	error_reporting(E_ALL);
	ini_set('error_reporting', E_ALL);

	require 'rb-p533.php';
	R::setup('mysql:host=186.202.152.116; dbname=umstudiohomolo8','umstudiohomolo8','studio0001');
	R::setAutoResolve( TRUE );



/* ***************************************************************************************************
** BEANS *********************************************************************************************
*************************************************************************************************** */ 




/* ***************************************************************************************************
** GET ROUTES ****************************************************************************************
*************************************************************************************************** */ 

if(!empty($_GET['endpoint'])){

	switch($_GET['endpoint']) {

		case 'hello_world':
			echo 'endpoint = hello_world';
			echo '<hr />';
			echo 'param = ' . $_GET['param'];
		break;

		case 'inspect':
			$inspect = json_encode(R::inspect($_GET['param']));
			print_r($inspect);
		break;

	}


}

/* hello word - create table
    $umspost = R::dispense( 'umspost' );
    $umspost->text = 'Hello World';

    $id = R::store( $umspost );          //Create or Update
    $umspost = R::load( 'umspost', $id );   //Retrieve
*/
?>	