<?php
error_reporting(E_ALL);

$_SERVER['REQUEST_SCHEME'] = 'http';
$_SERVER['HTTP_HOST']      = 'localhost';
$_SERVER['REMOTE_ADDR']    = '';

require_once "../../../wp-load.php";
require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once  "./vendor/autoload.php";


/**
 * Get data from database
 */
function get_data($data = array()): array {
	global $wpdb;
	$table = NASHAAT_DB_TABLE;

	$default_data = array(
		'where'    => '1=1',
		'orderby'  => 'id',
		'offset'   => 0,
		'per_page' => 1,
		'order'    => 'DESC',
	);

	$data = array_merge($default_data, $data);

	list(
		'where'    => $where,
		'orderby'  => $orderby,
		'offset'   => $offset,
		'per_page' => $per_page,
		'order'    => $order
	) = $data;


	$results = $wpdb->get_results(
		"SELECT * FROM {$table} WHERE {$where}
		ORDER BY {$orderby} {$order}
		LIMIT $offset, {$per_page};",
		ARRAY_A
	);

	if (!$results) {
		return  array();
	}
	// Loop through results and unserialize data
	foreach ($results as $key => $result) {
		$results[$key]['user_data'] = maybe_unserialize($result['user_data']);
		$results[$key]['log_info']  = maybe_unserialize($result['log_info']);
	}

	if (sizeof($results) === 1) {
		return $results[0];
	}
	return $results;
}

/**
 * Make sure that the snapshot is the same as the data
 *
 * @param array $data Data to create html from database
 * @param string $file_name File name of the snapshot
 * @param array $replace_map Array of strings to replace in the snapshot
 */
function assert_snapshot(array $data, string $file_name, array $replace_map = array()) {
	list(
		'context'  => $context,
		'log_info' => $log_info
	) = $data;

	// Check if the snapshot exists
	$file_path = __DIR__ . "/snapshots/{$file_name}.html";
	if (!file_exists($file_path)) {
		throw new Exception("Snapshot file {$file_path} does not exist");
	}
	$file_content = file_get_contents($file_path);

	$html = apply_filters(
		"render_log_info_$context",
		$log_info,
		$data['event'],
		$data,
		NashaatRenderLogInfo::class
	);

	if (count($replace_map) > 0) {
		// First update the keys in the replace map to include @
		$replace_map_with_vars = array();
		foreach ($replace_map as $key => $value) {
			$replace_map_with_vars["@{$key}"] = $value;
		}
		$file_content = strtr($file_content, $replace_map_with_vars);
	}

	// Search for @keys in the snapshot and replace them with the values
	// from the data
	$file_content = preg_replace_callback(
		'/@([a-z_0-9.]+)/',
		function($matches) use ($data) {

			// . represents a nested array
			$path = explode('.', $matches[1]);

			// Build the path to the value
			$replce = $data['log_info'];
			foreach ($path as $key) {
				if (!isset($replce[$key])) {
					return $matches[0];
				}
				$replce = $replce[$key];
			}

			if (!empty($replce)) {
				return strtr($matches[0], array(
						$matches[0] => $replce,
					));
			}

			return $matches[0];
		},
		$file_content
	);

	expect($html)->toBe($file_content);
}