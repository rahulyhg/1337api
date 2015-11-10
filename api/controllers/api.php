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

/* ***************************************************************************************************
** POST FUNCTIONS ************************************************************************************
*************************************************************************************************** */ 



/* ***************************************************************************************************
** ./end *********************************************************************************************
*************************************************************************************************** */ 

?>