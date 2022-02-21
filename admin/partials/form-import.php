<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/claytonk
 * @since      1.0.0
 *
 * @package    Modx_Import
 * @subpackage Modx_Import/admin/partials
 */

// wrapping function to determine where in the process we are and route activity to proper processor
function getStep(){
	include_once( MODX_IMPORT_PATH . 'admin/class-modx-import-request.php' );

	if (!file_exists(MODX_IMPORT_PATH . 'admin/logs')) {
		mkdir(MODX_IMPORT_PATH . 'admin/logs', 0777, true);
	}

	// check to see if partial import exists and remove completed imports from manifest
	if ($manifest = json_decode(file_get_contents( MODX_IMPORT_PATH . 'admin/logs/manifest.json' ),true )){
		$new = false;
		$finished = array();
		if (file_exists(MODX_IMPORT_PATH . 'admin/logs/completed.csv') && $csv = fopen(MODX_IMPORT_PATH . 'admin/logs/completed.csv','r')){
			while (($r = fgetcsv($csv, 1000, ",")) !== FALSE) {
				$finished[$r[0]] = array(
					'postid' => $r[1],
					'menuid' => $r[2]
				);
			}
		}
		if (!empty($finished)){
			foreach($finished as $old => $new){
				unset($manifest['resources'][$old]);
			}
			$json = fopen(MODX_IMPORT_PATH.'admin/logs/manifest.json', 'w');
			fwrite($json, json_encode($manifest));
			fclose($json);
		}
		if (!empty($manifest['resources']) && $_POST['step'] != 'manifest'){
			$new = false;
			$process = false;
			$output = str_replace('[*site*]', $manifest['site'], file_get_contents( MODX_IMPORT_PATH . 'admin/partials/form-partial.html' ));
		}else{
			$new = true;
			ftruncate($csv, 0);
		}
		fclose($csv);
	}else{
		$manifest = array(
			'site' => '',
			'resources' => array()
		);
		$new = true;
	}

	// output partial form if manifest exists and resources remain
	if ($_POST['step'] == 'manifest'){
		$_POST['resources'] = $manifest['resources'];
		$new = true;
		$process = true;
	}

	// output initial form if no request exists
	if ($new && empty($_POST['step'])){
		$output = file_get_contents( MODX_IMPORT_PATH . 'admin/partials/form-request.html' );
	}

	// make remote request
	if ($new && ((!empty($_POST['username']) && !empty($_POST['password'])) || !empty($_POST['token']))){
		$params = $_POST;
		$request = new Import_Request($_POST['site'],$params);
		$output = $request->reply;
		if ($_POST['step'] != 'manifest'){
			$complete = fopen(MODX_IMPORT_PATH . 'admin/logs/completed.csv','w');
			fclose($complete);
		}
		if ($_POST['step'] == 2 || $_POST['step'] == 'manifest'){
			$manifest['token'] = $_POST['token'];
			$manifest['site'] = $_POST['site'];
			$manifest['resources'] = $_POST['resources'];
			$json = fopen(MODX_IMPORT_PATH.'admin/logs/manifest.json', 'w');
			fwrite($json, json_encode($manifest));
			fclose($json);
			if ($output){
				$import = fopen(MODX_IMPORT_PATH.'admin/logs/import.json', 'w');
				fwrite($import,$output);
				fclose($import);
				$process = true;
				$output = '';
			}
		}
	}

	// output monitor and import options if all is good to go
	if ($process && !empty($manifest['resources'])){
		$output.= '<script>var constant = { "MODX_IMPORT_PATH" : "'.MODX_IMPORT_PATH.'", "ABSPATH" : "'.ABSPATH.'"}</script>';
		$output.= file_get_contents( MODX_IMPORT_PATH . 'admin/partials/monitor.html' );
	}

	return $output;
}



?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="modx-import">

	<h1>Import Content, Media and Navigation from MODX ALPS Site</h1>

	<?php $results = getStep(); print $results; ?>

</div>
