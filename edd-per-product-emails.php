<?php
/**
Plugin Name: Easy Digital Downloads - Per Product Emails
Plugin URI: https://easydigitaldownloads.com/downloads/per-product-emails/
Description: Custom purchase confirmation emails for your products
Version: 1.1.7
Author: Easy Digital Downloads
Author URI: https://easydigitaldownloads.com
License: GPL-2.0+
License URI: http://www.opensource.org/licenses/gpl-license.php
Text Domain: edd-ppe
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'EDD_Per_Product_Emails' ) ) {

	class EDD_Per_Product_Emails {

		/**
		 * Holds the instance
		 *
		 * Ensures that only one instance exists in memory at any one
		 * time and it also prevents needing to define globals all over the place.
		 *
		 * TL;DR This is a static property property that holds the singleton instance.
		 *
		 * @var object
		 * @static
		 * @since 1.0
		 */
		private static $instance;

		/**
		 * Plugin Version
		 */
		private $version = '1.1.7';

		/**
		 * Plugin Title
		 */
		public $title = 'EDD Per Product Emails';

		/**
		* @var EDD_PPE_Software_Licensing
		*/
		public static $edd_software_licensing;

		/**
		 * Main Instance
		 *
		 * Ensures that only one instance exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0
		 *
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_Per_Product_Emails ) ) {
				self::$instance = new EDD_Per_Product_Emails;
				self::$instance->setup_globals();
				self::$instance->includes();
				self::$instance->setup_actions();
				self::$instance->licensing();
				self::$instance->load_textdomain();

				// Set up integrations
				self::$edd_software_licensing = new EDD_PPE_Software_Licensing();
			}

			return self::$instance;
		}

		/**
		 * Constructor Function
		 *
		 * @since 1.0
		 * @access private
		 */
		private function __construct() {
			self::$instance = $this;
		}

		/**
		 * Globals
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		private function setup_globals() {

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

			add_action( 'admin_menu', array( $this, 'add_submenu_page'), 10 );
			add_action( 'admin_print_styles', array( $this, 'admin_css'), 100 );

			add_filter( 'plugin_row_meta', array( $this, 'plugin_meta' ), 10, 2 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ), 10, 2 );

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
		 * Loads the plugin language files
		 *
		 * @access public
		 * @since 1.0.4
		 * @return void
		 */
		public function load_textdomain() {

			// Set filter for plugin's languages directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'edd_ppe_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale        = apply_filters( 'plugin_locale',  get_locale(), 'edd-ppe' );
			$mofile        = sprintf( '%1$s-%2$s.mo', 'edd-ppe', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-ppe/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-ppe/ folder
				load_textdomain( 'edd-ppe', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-ppe/languages/ folder
				load_textdomain( 'edd-ppe', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'edd-ppe', false, $lang_dir );
			}
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

			//Include Integration files
			require( $this->includes_dir . 'integrations/plugin-software-licenses.php' );

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
		 * Plugin settings link
		 *
		 * @since 1.0
		*/
		public function settings_link( $links ) {
			$plugin_links = array(
				'<a href="' . admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions' ) . '">' . __( 'Settings', 'edd-ppe' ) . '</a>',
			);

			return array_merge( $plugin_links, $links );
		}

		/**
		 * Modify plugin metalinks
		 *
		 * @access      public
		 * @since       1.0.3
		 * @param       array $links The current links array
		 * @param       string $file A specific plugin table entry
		 * @return      array $links The modified links array
		 */
		public function plugin_meta( $links, $file ) {
		    if ( $file == plugin_basename( __FILE__ ) ) {
		        $plugins_link = array(
		            '<a title="'. __( 'View more plugins for Easy Digital Downloads by Sumobi', 'edd-ppe' ) .'" href="https://easydigitaldownloads.com/blog/author/andrewmunro/?ref=166" target="_blank">' . __( 'Author\'s EDD plugins', 'edd-ppe' ) . '</a>'
		        );

		        $links = array_merge( $links, $plugins_link );
		    }

		    return $links;
		}

		/**
		 * Add submenu page
		 *
		 * @since 1.0
		*/
		public function add_submenu_page() {
			add_submenu_page( 'edit.php?post_type=download', __( 'Per Product Emails', 'edd-ppe' ), __( 'Per Product Emails', 'edd-ppe' ), edd_ppe_capability_type(), 'edd-receipts', array( $this, 'admin_page') );
		}

		/**
		 * Receipts page
		 *
		 * @since 1.0
		*/
		public function admin_page() {

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
				<h2><?php _e( 'Per Product Emails', 'edd-ppe' ); ?><a href="<?php echo esc_url( add_query_arg( array( 'edd-action' => 'add_receipt', 'edd-message' => false ) ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'edd-ppe' ); ?></a></h2>
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
		public function admin_css() {

			global $pagenow, $typenow;

			// only load CSS when we're adding or editing a purchase receipt
			if ( ! ( isset( $_GET['edd-action'] ) && ( 'edit_receipt' == $_GET['edd-action'] || 'add_receipt' == $_GET['edd-action'] ) && 'download' == $typenow && 'edit.php' == $pagenow ) )
				return;
			?>
			<style>.quicktags-toolbar input{width: auto;}</style>
			<?php
		}

	}

	/**
	 * Loads a single instance
	 *
	 * This follows the PHP singleton design pattern.
	 *
	 * Use this function like you would a global variable, except without needing
	 * to declare the global.
	 *
	 * @example <?php $edd_per_product_emails = edd_per_product_emails(); ?>
	 *
	 * @since 1.0
	 *
	 * @see EDD_Per_Product_Emails::get_instance()
	 *
	 * @return object Returns an instance of the EDD_Per_Product_Emails class
	 */
	function edd_per_product_emails() {

	    if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {

	        if ( ! class_exists( 'EDD_Extension_Activation' ) ) {
	            require_once 'includes/class-activation.php';
	        }

	        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
	        $activation = $activation->run();
	        return EDD_Per_Product_Emails::get_instance();

	    } else {
	        return EDD_Per_Product_Emails::get_instance();
	    }
	}
	add_action( 'plugins_loaded', 'edd_per_product_emails', apply_filters( 'edd_ppe_action_priority', 10 ) );

}
