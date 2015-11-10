<?php
use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;

/* ***************************************************************************************************
** GET FUNCTIONS *************************************************************************************
*************************************************************************************************** */ 

function api_export ($request, $response, $args) {

	// TODO: <b>Strict Standards</b>:  Declaration of Goodby\CSV\Export\Standard\CsvFileObject::fputcsv() should be compatible with SplFileObject::fputcsv($fields, $delimiter = NULL, $enclosure = NULL, $escape = NULL) in <b>/Volumes/DATA/Dev/umstudio/ums_redbean/api/vendor/goodby/csv/src/Goodby/CSV/Export/Standard/CsvFileObject.php</b> on line <b>84</b><br />

	// init Goodby\CSV\Export\ 
	if ( class_exists('Goodby\CSV\Export\Standard\ExporterConfig') ) {
		$exportConfig = new ExporterConfig();

		if ( class_exists('Goodby\CSV\Export\Standard\Exporter') ) {
			$exporter = new Exporter($exportConfig);
		}
		else {
			throw new Exception("CLASS NOT FOUND (Goodby\CSV\Export\Standard\Exporter)", 1);
		}
	}
	else {
		throw new Exception("CLASS NOT FOUND (Goodby\CSV\Export\Standard\ExporterConfig)", 1);
	}

	// collect data
	$raw = R::findAll( $args['edge'] );

	if ( !empty($raw) ) {

		// export it all
		$data = R::exportAll( $raw, FALSE, array('NULL') );

		// inject field keys to data as csv export table heading
		$keys = array_keys($data[0]);
		array_unshift($data, $keys);

		// define outstream
		$hashdate = str_replace(array(':','-',' '), '', R::isoDateTime());
		$filename = 'export-'.$args['edge'].'-'.$hashdate.'.csv';

		//outstream response
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename='. $filename);

		// TODO: how to export big tables? memory runs out.
		$outstream = $exporter->export('php://output', $data);
	}
	else {
		throw new Exception("Error Processing Request (edge raw data not found)", 1);
	}
};

function api_exists ($request, $response, $args) {

	// check if item is retrieved from database
	$item = R::find( $args['edge'], ' id = '.$args['id'] );
	$exists = !empty($item) ? true : false;

	// build api response payload
	$payload = array(
		'edge' 		=> $args['edge'],
		'id' 		=> $args['id'],
		'exists' 	=> $exists
	);
	
	// output response payload
	return $response->withJson($payload);
};

function api_soon ($request, $response, $args) {

	// build api response payload
	$payload = array(
		'message' => getMessage('COMING_SOON')
	);

	// output response payload
	return $response->withJson($payload);
};

/* ***************************************************************************************************
** POST FUNCTIONS ************************************************************************************
*************************************************************************************************** */ 

function api_create ($request, $response, $args) {
	global $api;

	// get data from 'body' request payload
	$data = $request->getParsedBody();

	if ( empty($data) ) {
		$err = array('error' => true, 'message' => getMessage('DATA_MISSING'));
		return $response->withJson($err)->withStatus(400);
	}

	// get raw data model from redbean describe
	$schema['raw'] = R::getAssoc('DESCRIBE ' . $args['edge']);

	if ( empty($schema['raw']) ) {
		throw new Exception("Error Processing Request (edge raw schema not found)", 1);
	}

	// dispense 'edge'
	$item = R::dispense( $args['edge'] );

	// foreach $req content, build array to insert
	foreach ($data as $field => $value) {

		// IF field is an id, throw exception
		if ( $field == 'id' ) {
			throw new Exception("Error Processing Request (field `id` is not allowed when creating a resource)", 1);
		}

		// IF field defines uploads many-to-many relationship
		else if ( $field == 'uploads_id' && in_array($args['edge'] .'_uploads', $api['edges']) ) {
			$upload = R::dispense( 'uploads' );
			$upload->id = $value;
			$item->sharedUploadList[] = $upload;
		}

		// IF field is a password, hash it up
		else if ( $field == 'password' ){
			$item[$field] = md5($value);
		}

		// ELSE field is literal, go on
		else {
			$item[$field] = $value;
		}
	}
	
	// inject created and modified current time to array to insert
	$item['created'] 	= R::isoDateTime();
	$item['modified'] 	= R::isoDateTime();

	// let's start the insert transaction
	R::begin();
	try {
		// insert item, returns id if success
		R::store($item);
		$id = R::getInsertID();

		// if item was insert with success
		if( $id ) {

			// commit transaction
			R::commit();

			// build api response array
			$payload = array(
				'id' 		=> $id,
				'message' 	=> getMessage('CREATE_SUCCESS') . ' (id: '.$id.')',
			);
			
			//output response
			return $response->withJson($payload)->withStatus(201);
		}
		// else something happened, throw error
		else {
			$errorMessage = getMessage('CREATE_FAIL');
			throw new Exception($errorMessage, 1);
		}
	}
	catch(Exception $e) {
		// rollback transaction
		R::rollback();

		throw $e;
	}
};

function api_update ($request, $response, $args) {
	global $api;

	// get data from 'body' request payload
	$data = $request->getParsedBody();

	if ( empty($data) ) {
		$err = array('error' => true, 'message' => getMessage('DATA_MISSING'));
		return $response->withJson($err)->withStatus(400);
	}

	// get raw data model from redbean describe
	$schema['raw'] = R::getAssoc('DESCRIBE ' . $args['edge']);

	if ( empty($schema['raw']) ) {
		throw new Exception("Error Processing Request (edge raw schema not found)", 1);
	}

	// dispense 'edge'
	$item = R::load( $args['edge'], $args['id'] );

	// foreach $req content, build array to insert
	foreach ($data as $field => $value) {

		// IF field is an id, throw exception
		if ( $field == 'id' && $value != $args['id'] ) {
			throw new Exception("Error Processing Request (field `id` does not match with request)", 1);
		}

		// IF field defines uploads many-to-many relationship
		else if ( $field == 'uploads_id' && in_array($req['edge'] .'_uploads', $api['edges']) ) {
			$upload = R::dispense( 'uploads' );
			$upload->id = $value;
			$item->sharedUploadList[] = $upload;
		}

		// IF field is a password, hash it up
		else if ( $field == 'password' ){
			$item[$field] = md5($value);

		}

		// ELSE field is literal, go on
		else {
			$item[$field] = $value;			
		}
	}

	// inject modified current time to array to update
	$item['modified'] = R::isoDateTime();

	// let's start the update transaction
	R::begin();
	try {

		// update item, returns id if success
		R::store($item);
		// commit transaction
		R::commit();

		// build api response array
		$payload = array(
			'id' 		=> $args['id'],
			'message' 	=> getMessage('UPDATE_SUCCESS') . ' (id: '.$args['id'].')',
		);

		//output response
		return $response->withJson($payload);
	}
	catch(Exception $e) {
		// rollback transaction
		R::rollback();

		throw $e;
	}
};

function api_updatePassword ($request, $response, $args) {

	// get data from 'body' request payload
	$data = $request->getParsedBody();

	if ( empty($data) ) {
		$err = array('error' => true, 'message' => getMessage('DATA_MISSING'));
		return $response->withJson($err)->withStatus(400);
	}

	// dispense 'edge'
	$item = R::load( 'user', $args['id'] );

	// verify if password is valid
	if ( $item['password'] == md5($data['password']) ) {
		// verify if new password matches
		if ($data['new_password'] == $data['confirm_new_password']) {

			// build array to update
			$item['password'] = md5($data['new_password']);
			$item['modified'] = R::isoDateTime();

			// let's start the update transaction
			R::begin();
			try {
		
				// update item, commit if success
				R::store( $item );
				// commit transaction
				R::commit();

				// build api response array
				$payload = array(
					'id' 		=> $args['id'],
					'message' 	=> getMessage('UPDATE_SUCCESS') . ' (id: '.$args['id'].')',
				);
				
				//output response
				return $response->withJson($payload);
			}
			catch(Exception $e) {
				// rollback transaction
				R::rollback();
				throw $e;
			}
		} 
		else {
			$err = array('error' => true, 'message' => getMessage('PASSWORD_CONFIRM_FAIL'));
			return $response->withJson($err)->withStatus(400);
		}
	} 
	else {
		$err = array('error' => true, 'message' => getMessage('AUTH_PASS_FAIL'));
		return $response->withJson($err)->withStatus(400);
	}
};

function api_destroy ($request, $response, $args) {

	// check relationships, if exists
	$hierarchyArr = R::getAll('
		SELECT TABLE_NAME as child, REFERENCED_TABLE_NAME as parent
		FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
		WHERE REFERENCED_TABLE_NAME = "'.$args['edge'].'"
	');

	if( !empty($hierarchyArr) ) {

		foreach ($hierarchyArr as $k => $v) {
			
			$childs = R::getAll('
				SELECT * FROM '.$v['child'].' WHERE `'.$v['parent'].'_id` = '.$args['id'].'
			');
			if ( !empty($childs) ) {
				$err = array('error' => true, 'message' => getMessage('DESTROY_FAIL_CHILD_EXISTS'));
				return $response->withJson($err)->withStatus(400);
			}	
		}
	}

	// no relationship? let's go on:
	// dispense 'edge'
	$item = R::load( $args['edge'], $args['id'] );

	// let's start the delete transaction
	R::begin();
	try {

		// destroy item, commit if success
	    R::trash($item);
		R::commit();

		// build api response array
		$payload = array(
			'id' 		=> $args['id'],
			'message' 	=> getMessage('DESTROY_SUCCESS') . ' (id: '.$args['id'].')',
		);

		// output response
		return $response->withJson($payload);

	} catch (Exception $e) {

		// rollback transaction
		R::rollback();

		throw $e;
	}
};

function api_upload ($request, $response, $args) {
	global $config;

	// get data from 'body' request payload
	$data = $request->getParsedBody();

	// check if data was sent
	if ( empty($data) ) {
			$err = array('error' => true, 'message' => getMessage('DATA_MISSING'));
			return $response->withJson($err)->withStatus(400);			
	}
	elseif ( empty($data['blob']) ) {
		$err = array('error' => true, 'message' => getMessage('UPLOAD_FAIL_BLOB_MISSING'));
		return $response->withJson($err)->withStatus(400);
	}
	elseif ( empty($data['filesize']) ) {
		$err = array('error' => true, 'message' => getMessage('UPLOAD_FAIL_FILESIZE_MISSING'));
		return $response->withJson($err)->withStatus(400);
	}
	elseif ( empty($data['filename']) ) {
		$err = array('error' => true, 'message' => getMessage('UPLOAD_FAIL_FILENAME_MISSING'));
		return $response->withJson($err)->withStatus(400);
	}

	// explode $data['blob']
		// TODO: this procedure can be done in one line if I use regex. verify correct expression.
		list($type, $data['blob']) = explode(';', $data['blob']);
		list(,$type) = explode(':', $type);
		list(,$data['blob']) = explode(',', $data['blob']);

		// decode blob data
		$content = base64_decode($data['blob']);

	// define path and new filename
		$dateTime 	= R::isoDateTime();
		$date 		= R::isoDate();
		$basepath 	= $config['api']['uploads']['basepath'];
		$datepath 	= str_replace('-', '/', $date) . '/';
		$fullpath 	= $basepath . $datepath;
		$hashname 	= md5($data['filename'].'-'.$dateTime) . '.' . end(explode('.', $data['filename']));

	// build insert upload array
	$upload = array(
		'path' 		=> $datepath . $hashname,
		'filename' 	=> $hashname,
		'type' 		=> $type,
		'size' 		=> $data['filesize'],
		'edge' 		=> $args['edge'],
		'created' 	=> R::isoDateTime(),
		'modified' 	=> R::isoDateTime(),
	);

	// write file
		// TODO: we should validate if the file was actually saved at disk.
		// if folder doesn't exist, mkdir 
		if (!file_exists($fullpath)) {
			mkdir( $fullpath, 0777, true );
		};
		file_put_contents($fullpath . $hashname, $content);
	
	// dispense uploads edge
	$item = R::dispense('uploads');

	foreach ($upload as $k => $v) {
		$item[$k] = $v;
	}		

	// let's start the insert transaction
	R::begin();
	try {
		// insert item, returns id if success
		R::store($item);
		$id = R::getInsertID();

		// if item was insert with success
		if( $id ) {

			// commit transaction
			R::commit();

			// build api response array
			$payload = array(
				'id' 		=> $id,
				'message' 	=> getMessage('UPLOAD_SUCCESS') . ' (id: '.$id.')',
			);
			
			//output response
			return $response->withJson($payload)->withStatus(201);
		}
		// else something happened, throw error
		else {
			$errorMessage = getMessage('UPLOAD_FAIL');
			throw new Exception($errorMessage, 1);
		}
	}
	catch(Exception $e) {
		// rollback transaction
		R::rollback();

		throw $e;
	}
};

/* ***************************************************************************************************
** ./end *********************************************************************************************
*************************************************************************************************** */ 

?>