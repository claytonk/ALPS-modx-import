<?php

// define constants from javascript
if (!defined('MODX_IMPORT_PATH')){
	define('MODX_IMPORT_PATH', $_POST['constant']['MODX_IMPORT_PATH']);
}
if (!defined('ABSPATH')){
	define('ABSPATH', $_POST['constant']['ABSPATH']);
}


// instantiate log array
if ($_POST['processed']){
	$processed = $_POST['processed'];
}else{
	$processed = array(
		'complete' => array(),
		'incomplete' => array()
	);
}


// include dependencies
require_once(ABSPATH . 'wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
include_once( MODX_IMPORT_PATH . 'admin/class-modx-import-post.php' );

$now = date('Y-m-d H:i:s');


// open csv log files
$csvComplete = fopen(MODX_IMPORT_PATH . 'admin/logs/completed.csv','a');
$csvIncomplete = fopen(MODX_IMPORT_PATH . 'admin/logs/incomplete.csv','a');


// process a single post from AJAX request
if ($_POST['process-single']){
	$output = process($_POST['importID'],$_POST['post']);
	if ($output['status'] && !in_array($_POST['importID'], $processed['complete'])){
		fputcsv($csvComplete, [$_POST['importID'],$output['result']['postid'],$output['result']['menuid'],$now]);
	}else{
		if (!in_array($_POST['importID'], $processed['incomplete'])){
			fputcsv($csvIncomplete, [$_POST['importID'],json_encode($output['result'])],',',"'");
		}
	}
}


// batch process import.json
if ($_POST['process-batch']){
	$batch = json_decode(file_get_contents( MODX_IMPORT_PATH . 'admin/logs/import.json' ),true );

	// log each iteration in case of timeout and for csv monitoring
	foreach($batch as $id => $p){
		$result = process($id,$p);
		if ($result['status']){
			fputcsv($csvComplete, [$id,$result['result']['postid'],$result['result']['menuid'],$now]);
		}
	}

	// check for incompletes that were waiting on local IDs
	if (!empty($processed['incomplete'])){
		foreach($processed['incomplete'] as $id => $values){
			$result = process($id,$batch[$id]);
			if ($result['status']){
				fputcsv($csvComplete, [$id,$result['result']['postid'],$result['result']['menuid'],$now]);
			}
		}
	}

	$output = array(
		'status' => true,
		'result' => array(
			'import-type' => 'batch',
			'resource-count' => count($processed['complete']),
			'processed-array' => $processed
		)
	);

}

// process log update request
if (!empty($_POST['updateLogs'])){
	$output = updateLogs($processed);
}else{
	fclose($csvComplete);
	fclose($csvIncomplete);
}

print json_encode($output);


// function to update logs
function updateLogs($processed){

	$csvComplete = fopen(MODX_IMPORT_PATH . 'admin/logs/completed.csv','w');
	$csvIncomplete = fopen(MODX_IMPORT_PATH . 'admin/logs/incomplete.csv','w');

	if (!empty($processed['complete'])){
		var_dump([$id,$post['postid'],$post['menuid'],$now]);
		foreach($processed['complete'] as $id => $post){
			fputcsv($csvComplete, [$id,$post['postid'],$post['menuid'],$now]);
		}
	}

	if (!empty($processed['incomplete'])){
		foreach($processed['incomplete'] as $id => $values){
			fputcsv($csvIncomplete, [$id,json_encode($values)],',',"'");
		}
	}

	fclose($csvComplete);
	fclose($csvIncomplete);

	return array(
		'complete' => $processed['complete'],
		'incomplete' => $processed['incomplete']
	);

}


// main import function to process resources
function process($id,$p){
	global $processed;

	$save = true;

	// set default content for any empty folder documents
	if (empty($p['post']['post_content'])){
		$p['post']['post_content'] = '<!-- wp:paragraph --><p>This post/page has no content. It was a parent document in the MODX resource tree used for displaying contextual navigation. Please add content or contextual links or remove this post/page.</p><!-- /wp:paragraph -->';
	}

	// try to get the new wp target id for redirection or set as incomplete if id doesn't yet exist
	if (!empty($p['post']['target_id'])){
		if (gettype($p['post']['target_id']) == 'array'){
			$file = array('file'=>$p['post']['target_id']);
			$media = new Import_Post($file);
			$p['post']['post_content'] = str_replace('[*url*]', $media->file->guid, $p['post']['post_content']);
		}else{
			if (!$target = $processed['complete'][$p['post']['target_id']]['postid']){
				$save = false;
				$processed['incomplete'][$id]['target'] = $p['post']['target_id'];
			}else{
				$tp = get_post($target);
				$p['post']['post_content'] = str_replace('[*url*]', $tp->guid, $p['post']['post_content']);
			}
		}
	}

	// try to get wp menu parent or set as incomplete
	if (!empty($p['post']['menu'])){
		$menu = explode('|',$p['post']['menu']);
		if ($menu[1] == 0 || !empty($processed['complete'][$menu[1]])){
			if (!empty($processed['complete'][$id]['menuid'])){
				$p['post']['menu'] = false;
			}else{
				$p['post']['menu'] = [$id,$processed['complete'][$menu[1]]['menuid']] ?: [$id,0];
			}
		}else{
			$save = false;
			$processed['incomplete'][$id]['menu'] = $menu[1];
		}
	}

	// try to get new wp parent id or set as incomplete
	if ($p['post']['post_parent'] > 0){
		if (!$parent = $processed['complete'][$p['post']['post_parent']]['postid']){
			$save = false;
			$processed['incomplete'][$id]['parent'] = $p['post']['post_parent'];
		}else{
			$p['post']['post_parent'] = $parent;
		}
	}

	// create post and save to completed log or save to incomplete array to rerun if post is waiting on local ID
	if ($save){
		$post = new Import_Post($p);
		if ($post->id){
			if (!$processed['complete'][$id]){
				$processed['complete'][$id] = array(
					'postid' => $post->id,
					'menuid' => $post->menuItem
				);
			}
			unset($processed['incomplete'][$id]);
			return array(
				'status' => true,
				'result' => $processed['complete'][$id]
			);
		}
	}else{
		return array(
			'status' => false,
			'result' => $processed['incomplete'][$id]
		);
	}
}

?>