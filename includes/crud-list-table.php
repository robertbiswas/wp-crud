<?php
/**
 * Creating the Record List Table
 * 
 * By extending WordPress Class WP_List_Table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( "WP_List_Table" ) ) {
	require_once ABSPATH . "wp-admin/includes/class-wp-list-table.php";
}

class Crud_List_Table extends WP_List_Table {

	private $_items;

	function __construct($data) {
		parent::__construct();
		$this->_items = $data;
	}

	// 	Gets a list of columns.
	function get_columns() {
		return [
			'name' => __( 'Name', 'rb-crud' ),
			'email' => __( 'Email', 'rb-crud' )
		];
	}

	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	// Prepares the list of items for displaying.
	function prepare_items() {
		$per_page = 5;
		$current_page = $this->get_pagenum();
		$total_items = count( $this->_items );
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page
			)
		);
		$data = array_slice( $this->_items, ($current_page - 1) * $per_page, $per_page );
		$this->items = $data;
		$this->_column_headers = array( $this->get_columns(), [], [] );
	}
	
	function column_name( $item ){
		$nonce = wp_create_nonce('rbc_delete');
		$edit_link = wp_nonce_url( admin_url( 'admin.php?page=crud_admin&cid=' ) . $item['ID'], 'n_edit' );
		$actions = array(
			'edit' => sprintf( '<a href="%s">%s</a>', $edit_link , __( 'Edit', 'rb-crud' ) ),
			'delete' => sprintf( '<a class="rbcrud-delete-link" href="%s?cid=%d&n=%s&action=%s&delete=%s">%s</a>', admin_url( 'admin-post.php' ), $item['ID'], $nonce, 'rb_crud_manage_record', 'yes', __( 'Delete', 'rb-crud' ) )
		);
		return sprintf( '%s %s', $item['name'], $this->row_actions($actions) );
	}
}