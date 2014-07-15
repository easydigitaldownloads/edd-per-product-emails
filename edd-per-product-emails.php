<?php
/*
Plugin Name: Easy Digital Downloads - Per Product Emails
Plugin URI: http://sumobi.com/shop/per-product-emails/
Description: Custom purchase confirmation emails for your products
Version: 1.0.3
Author: Andrew Munro, Sumobi
Author URI: http://sumobi.com/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'EDD_Per_Product_Emails' ) ) {

	class EDD_Per_Product_Emails {

		private static $instance;

		/**
		 * Main Instance
		 *
		 * Ensures that only one instance exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0
		 *
		 */
		public static function instance() {
			if ( ! isset ( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}


		/**
		 * Start your engines.
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		public function __construct() {
			$this->setup_globals();
			$this->includes();
			$this->setup_actions();
			$this->licensing();
		}


		/**
		 * Globals
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		private function setup_globals() {

			$this->version    = '1.0.3';

			// paths
			$this->file         = __FILE__;
			$this->basename     = apply_filters( 'edd_ppe_plugin_basenname', plugin_basename( $this->file ) );
			$this->plugin_dir   = apply_filters( 'edd_ppe_plugin_dir_path',  plugin_dir_path( $this->file ) );
			$this->plugin_url   = apply_filters( 'edd_ppe_plugin_dir_url',   plugin_dir_url ( $this->file ) );

			// includes
			$this->includes_dir = apply_filters( 'edd_ppe_includes_dir', trailingslashit( $this->plugin_dir . 'includes'  ) );
			$this->includes_url = apply_filters( 'edd_ppe_includes_url', trailingslashit( $this->plugin_url . 'includes'  ) );

		}

		/**
		 * Setup the default hooks and actions
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		private function setup_actions() {
			global $edd_options;

			add_action( 'init', array( $this, 'textdomain' ) );
			add_action( 'admin_menu', array( $this, 'add_submenu_page'), 10 );
			add_action( 'admin_print_styles', array( $this, 'admin_css'), 100 );

			do_action( 'edd_ppe_setup_actions' );
		}

		/**
		 * Licensing
		 *
		 * @since 1.0
		*/
		private function licensing() {
			// check if EDD_License class exists
			if ( class_exists( 'EDD_License' ) ) {
				$license = new EDD_License( __FILE__, 'Per Product Emails', $this->version, 'Andrew Munro' );
			}
		}

		/**
		 * Internationalization
		 *
		 * @since 1.0
		 */
		function textdomain() {
			load_plugin_textdomain( 'edd-ppe', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}


		/**
		 * Include required files.
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		private function includes() {

			require( $this->includes_dir . 'receipt-functions.php' );
			require( $this->includes_dir . 'email-functions.php' );

			do_action( 'edd_ppe_include_files' );

			if ( ! is_admin() )
				return;

			require( $this->includes_dir . 'receipt-actions.php' );
			require( $this->includes_dir . 'admin-notices.php' );
			require( $this->includes_dir . 'admin-settings.php' );
			require( $this->includes_dir . 'post-types.php' );

			do_action( 'edd_ppe_include_admin_files' );
		}


		/**
		 * Add submenu page
		 *
		 * @since 1.0
		*/
		function add_submenu_page() {
			add_submenu_page( 'edit.php?post_type=download', __( 'Per Product Emails', 'edd-ppe' ), __( 'Per Product Emails', 'edd-ppe' ), 'manage_shop_settings', 'edd-receipts', array( $this, 'admin_page') );
		}


		/**
		 * Receipts page
		 *
		 * @since 1.0
		*/
		function admin_page() {

			if ( isset( $_GET['edd-action'] ) && $_GET['edd-action'] == 'edit_receipt' ) {
				require_once $this->includes_dir . 'edit-receipt.php';
			} 
			elseif ( isset( $_GET['edd-action'] ) && $_GET['edd-action'] == 'add_receipt' ) {
				require_once $this->includes_dir . 'add-receipt.php';
			} 
			else {
				require_once $this->includes_dir . 'class-receipts-table.php';
				$receipts_table = new EDD_Receipts_Table();
				$receipts_table->prepare_items();
			?>

			<div class="wrap">
				<h2><?php _e( 'Per Product Emails', 'edd-ppe' ); ?><a href="<?php echo add_query_arg( array( 'edd-action' => 'add_receipt', 'edd-message' => false ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'edd-ppe' ); ?></a></h2>
				<?php do_action( 'edd_receipts_page_top' ); ?>
				<form id="edd-receipts-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=download&page=edd-receipts' ); ?>">
					<?php $receipts_table->search_box( __( 'Search', 'edd-ppe' ), 'edd-receipts' ); ?>

					<input type="hidden" name="post_type" value="download" />
					<input type="hidden" name="page" value="edd-receipts" />

					<?php $receipts_table->views() ?>
					<?php $receipts_table->display() ?>
				</form>
				<?php do_action( 'edd_receipts_page_bottom' ); ?>
			</div>
		<?php
			}
		}


		/**
		 * Subtle styling to override CSS added by WP. By default the WP CSS causes the TinyMCE buttons to stretch
		 *
		 * @since 1.0
		*/
		function admin_css() { 

			global $pagenow, $typenow;

			// only load CSS when we're adding or editing a purchase receipt
			if ( ! ( isset( $_GET['edd-action'] ) && ( 'edit_receipt' == $_GET['edd-action'] || 'add_receipt' == $_GET['edd-action'] ) && 'download' == $typenow && 'edit.php' == $pagenow ) )
				return;
			?>
			<style>.quicktags-toolbar input{width: auto;}</style>
		<?php }

	}
	
}


function edd_per_product_emails() {
	return EDD_Per_Product_Emails::instance();
}

edd_per_product_emails();