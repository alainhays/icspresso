<?php

class HMES_User_Type extends HMES_Base_Type {

	var $name             = 'user';
	var $index_hooks      = array( 'user_register', 'profile_update' );
	var $delete_hooks     = array( 'deleted_user' );
	var $mappable_hooks   = array(
		'added_user_meta'   => 'update_user_meta_callback',
		'updated_user_meta' => 'update_user_meta_callback',
		'deleted_user_meta' => 'update_user_meta_callback'
	);

	/**
	 * Called when user meta is added/deleted/updated
	 *
	 * @param $meta_id
	 * @param $user_id
	 */
	function update_user_meta_callback( $meta_id, $user_id ) {

		$this->index_callback( $user_id );
	}

	/**
	 * Queue the indexing of an item - called when a user is modified or added to the database
	 *
	 * @param $item
	 * @param array $args
	 */
	function index_callback( $item, $args = array()  ) {

		$user = get_userdata( $item );

		if ( ! $user ) {
			return;
		}

		$this->queue_action( 'index_item', $item );
	}

	/**
	 * Queue the deletion of an item - called when a user is deleted from the database
	 *
	 * @param $user_id
	 * @param array $args
	 */
	function delete_callback( $user_id, $args = array()  ) {

		$this->queue_action( 'delete_item', $user_id );
	}

	/**
	 * arse an item for indexing, accepts user ID or user object
	 *
	 * @param $item
	 * @param array $args
	 * @return array|bool
	 */
	function parse_item_for_index( $item, $args = array() ) {

		//get a valid user object as array (populate if only id is supplied)
		if ( is_numeric( $item ) ) {
			$item = (array) get_userdata( $item );
		} else {
			$item = (array) $item;
		}

		if ( empty( $item['ID'] ) ) {
			return false;
		}

		$item['meta'] = get_metadata( 'user', (int) $item['ID'], '', true );

		foreach ( $item['meta'] as $meta_key => $meta_array ) {
			$item['meta'][$meta_key] = reset( $meta_array );
		}

		return $item;
	}

	/**
	 * Get paginated users for use by index_all base class method
	 *
	 * @param $page
	 * @param $per_page
	 * @return array
	 */
	function get_items( $page, $per_page ) {

		$posts = get_users( array(
			'offset' => ( $page > 0 ) ? $per_page * ( $page -1 ) : 0,
			'number' => $per_page
		) );

		return $posts;
	}

}