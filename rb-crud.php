<?php
/*
* Plugin Name:       RB CRUD
* Plugin URI:        https://robertbiswas.com
* Description:       Demonstrating CRUD operation in WordPress Database
* Version:           1.0
* Requires at least: 6.0.0
* Requires PHP:      7.4
* Author:            Robert Biswas
* Author URI:        https://robertbiswas.com
* License:           GPL v2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain:       rb-crud
*/

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class Rb_Wedevs_Crud {
	
	static $instance;
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->plugin_bootstrap();
		add_action('admin_menu', array( $this, 'crud_admin_page' ) );
		register_activation_hook( __FILE__, array( $this, 'init_crud_table' ) );
		add_action('admin_post_rb_crud_manage_record', array( $this, 'rb_crud_manage_record' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_management' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'rbcrud_admin_page_link' ) );
	}

	// Prevent object cloning
	private function __clone() {}


	// Define Constants and Includes necessary files
	public function plugin_bootstrap() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugin_data = get_plugin_data( __FILE__ );
		if ( ! defined( 'RB_CRUD_VERSION' ) ) {
			define( 'RB_CRUD_VERSION', $plugin_data['Version'] );
		}
		
		// Set plugin assets path
		if ( ! defined( 'RB_CRUD_ASSETS' ) ) {
			define( 'RB_CRUD_ASSETS', plugins_url( 'assets/', __FILE__ ) );
		}

		require_once plugin_dir_path(__FILE__) . 'includes/crud-list-table.php';
	}

	// Including Assets files
	public function admin_enqueue_management($screen){
		if ( 'toplevel_page_crud_admin' == $screen ){
			wp_enqueue_style( 'rbcrud-css', RB_CRUD_ASSETS . 'css/rbcrud.css', array(), RB_CRUD_VERSION );

			wp_enqueue_script('rbcrud-js', RB_CRUD_ASSETS . 'js/rbcrud.js', array( 'wp-i18n' ), RB_CRUD_VERSION, true);
			
			// i18n for Javascript
			wp_set_script_translations('rbcrud-js', 'rb-crud');

			$page_url = admin_url( 'admin.php?page=crud_admin' );
			wp_localize_script(
				'rbcrud-js', 
				'crudJsData',
				array('crudPageUrl' => $page_url)
			);
		}
	}

	// Table creation during Plugin Activation.
	public function init_crud_table() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$curd_table = $wpdb->prefix . 'rb_crud';
		$table_check = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $curd_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if(  $curd_table != $table_check ){
			$sql = "CREATE TABLE $curd_table (
				ID INT(11) NOT NULL AUTO_INCREMENT,
				name VARCHAR(250),
				email VARCHAR(250),
				PRIMARY KEY(ID)
			) $charset;";
	
			if ( ! function_exists( 'dbDelta' ) ){
				require_once( ABSPATH . "wp-admin/includes/upgrade.php" );
			}
	
			dbDelta($sql);
		}
		
	}

	// Constructing the Admin Page
	public function crud_admin_page() {
		add_menu_page(
			__('Crud Admin', 'rb-crud'),
			__('CRUD Page', 'rb-crud'),
			'manage_options',
			'crud_admin',
			array( $this, 'crud_admin_page_content' ),
			'dashicons-database',
			6
		);
	}

	/**
	 * Admin page
	 * 
	 * A form for Inserting and Editing Record.
	 * A List Table to show Records.
	 */
	public function crud_admin_page_content() {
		global $wpdb;
		$current_id = isset( $_GET['cid'] ) ? sanitize_key( $_GET['cid'] ) : 0;
		$url_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( $_GET['_wpnonce'] ) : null;
		if( wp_verify_nonce( $url_nonce, 'n_edit' ) ){
			if ( $current_id ){
				$row = $wpdb->get_row($wpdb->prepare("select * from {$wpdb->prefix}rb_crud where ID = %d", $current_id )); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}
		?>
		<div class="wrap">
			<?php
			printf('<h2 class="wp-heading-inline">%s </h2>', esc_attr__('WeDevs CRUD', 'rb-crud'));
			?>
			<div class="from-container <?php echo ( $current_id > 0 ) ? '' : 'hidden'; ?>">
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ) ; ?> " method="post">
					<?php wp_nonce_field('rbcrud', 'nonce'); ?>
					<input type="hidden" name="action" value="rb_crud_manage_record">
					<input type="hidden" name="id" value="<?php if( $current_id && $row->ID ) echo esc_attr($row->ID); ?>">
					<p>
					<label for="name"><?php esc_attr_e( 'Name', 'rb-crud' ); ?></label>
					<input type="text" name="name" id="name" value="<?php if( $current_id && $row->ID ) echo esc_attr($row->name); ?>" required>
					</p>
					<p>
					<label for="email"><?php esc_attr_e( 'Email', 'rb-crud' ); ?></label>
					<input type="email" name="email" id="email" value="<?php if( $current_id && $row->ID ) echo esc_attr($row->email); ?>"  required>
					</p>
					
					<?php
					if ( $current_id > 0 ) {
						?>
						<div class="button-list">
							<?php
							submit_button( esc_attr__( 'Update Record', 'rb-crud' ), 'primary', 'submit', false );
							printf( '<button type="reset" class="button-secondary" id="crud-cancle">%s</button>', esc_attr__('Discard', 'rb-crud' ));
							?>
						</div>
						<?php
					} else {
						?>
						<div class="button-list">
							<?php
							submit_button( esc_attr__( 'Add Record', 'rb-crud' ), 'primary', 'submit', false );
							printf( '<button type="reset" class="button-secondary" id="crud-cancle">%s</button>', esc_attr__('Cancle', 'rb-crud' ) );
							?>
						</div>
						<?php
					}
					?>
				</form>
			</div>

			<div class="crud-list-table">
				<div class="info">
					<span><?php printf( '<h3>%s</h3>', esc_attr__( 'CRUD Data Table', 'rb-crud' ) ); ?></span>
					<span><?php printf( '<button class="button-primary" id="crud-add-new">%s</button>', esc_attr__( 'Add new data', 'rb-crud' ) ); ?></span>
				</div>
				<?php
				
				global $wpdb;
				$crud_rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rb_crud ORDER BY ID DESC", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$crud_tbl = new Crud_List_Table($crud_rows);
				$crud_tbl->prepare_items();
				$crud_tbl->display();
				?>
			</div>
		</div>
		<?php
	}


	/**
	 * Manage CRUD of the table.
	 */
	public function rb_crud_manage_record() {
		global $wpdb;
		$crud_table_name = $wpdb->prefix . 'rb_crud';

		// Deleting Post
		if ( isset ( $_GET['delete'] ) && 'yes' ==  $_GET['delete'] && isset( $_GET['cid'] )) {
			$cid = sanitize_key( $_GET['cid'] );
			$url_nonce = isset( $_GET['n'] ) ? sanitize_key( $_GET['n'] ) : null;
			if ( wp_verify_nonce( $url_nonce, 'rbc_delete' ) ) {
				$wpdb->delete( $crud_table_name, array( 'ID' => $cid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				wp_safe_redirect( admin_url( 'admin.php?page=crud_admin' ) );
			}
		}

		// Insert and Update Record
		$current_id = sanitize_key( $_POST['id'] ?? 0 );
		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( $_POST['nonce'] ) : null;
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : null;
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : null;
		if( wp_verify_nonce( $nonce, 'rbcrud' ) ) {
			if ( $current_id > 0 ) {
				$wpdb->update( $crud_table_name, array( 'name' => $name, 'email' => $email ),  array( 'ID' => $current_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			} else {
				$wpdb->insert( $crud_table_name, array( 'name' => $name, 'email' => $email ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			
			$success_id = $wpdb->insert_id;
		}

		if( $success_id ){
			add_action( 'admin_notices', array( $this, 'sample_admin_notice__success' ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=crud_admin' ) );
	}

	//Adding "Admin Page" link to the Plugin page.
	public function rbcrud_admin_page_link($links){
		$setting_link = sprintf( "<a href='%s'>%s</a>", 'admin.php?page=crud_admin', __( 'Admin Page', 'rb-crud' ) );
		$links[] = $setting_link;
		return $links;
	}
}

Rb_Wedevs_Crud::get_instance();