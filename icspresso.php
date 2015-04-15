<?php

/*
Plugin Name: Icspresso
Description: Developer's ElasticSearch integration for WordPress
Author: Theo Savage
Version: 0.1
Author URI: http://hmn.md/
*/

namespace Icspresso;

require_once ( __DIR__ . '/icspresso-admin.php' );
include_dir( __DIR__ . '/lib/elasticsearch/src' );
include_dir( __DIR__ . '/classes' );

/**
 * Init ell Icspresso type classes on plugins_loaded hook
 */
function init_types() {

	Type_Manager::init_types();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init_types' );

/**
 * Get the list of Icspresso type classes by name
 *
 * @return array
 */
function get_type_class_names() {
	return apply_filters( 'icspresso_index_types', array(
		'post'      => __NAMESPACE__ . '\\Types\Post',
		'user'      => __NAMESPACE__ . '\\Types\User',
		'comment'   => __NAMESPACE__ . '\\Types\Comment',
		'term'      => __NAMESPACE__ . '\\Types\Term'
	) );
}

/**
 * Init elasticsearch for given connection and index/type args
 *
 * @param array $connection_args
 * @param array $index_creation_args
 * @return array|bool
 */
function init_elastic_search_index( $connection_args = array(), $index_creation_args = array() ) {

	$es = Wrapper::get_instance( $connection_args );

	$es->disable_logging();

	if ( ! $es->is_connection_available() ) {
		return false;
	}

	if ( ! $es->is_index_created() ) {

		return $es->create_index( $index_creation_args );
	}

	return false;
}

/**
 * Delete elasticsearch for given connection and index/type args
 *
 * @param array $connection_args
 * @param array $index_deletion_args
 * @return array|bool|\Exception
 */
function delete_elastic_search_index( $connection_args = array(), $index_deletion_args = array() ) {

	$es = Wrapper::get_instance( $connection_args );

	$es->disable_logging();

	if ( ! $es->is_connection_available() ) {
		return false;
	}

	if (  $es->is_index_created() ) {

		return $es->delete_index( $index_deletion_args );
	}

	return false;
}

/**
 * Recursively include all php files in a directory and subdirectories
 *
 * @param $dir
 * @param int $depth
 * @param int $max_scan_depth
 */
function include_dir( $dir, $depth = 0, $max_scan_depth = 5 ) {

	if ( $depth > $max_scan_depth ) {
		return;
	}

	// require all php files
	$scan = glob( $dir . '/*' );

	foreach ( $scan as $path ) {
		if ( preg_match( '/\.php$/', $path ) ) {
			require_once $path;
		} elseif ( is_dir( $path ) ) {
			include_dir( $path, $depth + 1, $max_scan_depth );
		}
	}
}

/**
 * Reindex all of the supplied types (fires immediately, does trigger a cron)
 *
 * @param $type_names
 */
function reindex_types( $type_names ) {

	foreach ( $type_names as $type_name ) {

		$type = Type_Manager::get_type( $type_name );

		if ( $type ) {
			$type->index_all();
		}
	}
}

/**
 * Resync all of the supplied types (adds missing entries. fires immediately, does trigger a cron)
 *
 * @param $type_names
 */
function resync_types( $type_names ) {

	foreach ( $type_names as $type_name ) {

		$type = Type_Manager::get_type( $type_name );

		if ( $type ) {

			$type->index_pending();
		}
	}
}

/**
 * Add a 10 minute schedule to WP Cron
 */
add_filter( 'cron_schedules', function( $intervals ) {

	$intervals['minutes_10'] = array('interval' => 10*60, 'display' => 'Once 10 minutes');

	return $intervals;

} );
