<?php
	error_reporting(E_ALL);
	ini_set('error_reporting', E_ALL);

	echo '<pre>';
	echo 'Startin up! <br />';

	require 'rb-p533.php';
	echo 'rb-p533.php lib loaded. <br />';

	R::setup(
		'mysql:host=186.202.152.116; dbname=umstudiohomolo8',
		'umstudiohomolo8', 
		'studio0001' 
	);
	R::setAutoResolve( TRUE );

    R::debug( TRUE );
	echo 'debug mode TRUE. <br />';

    $isConnected = R::testConnection();
    print_r($isConnected);


/* hello word - create table
    $umspost = R::dispense( 'umspost' );
    $umspost->text = 'Hello World';

    $id = R::store( $umspost );          //Create or Update
    $umspost = R::load( 'umspost', $id );   //Retrieve
*/
?>	