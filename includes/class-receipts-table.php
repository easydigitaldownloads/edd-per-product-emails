<?php
/**
 * Receipts Table Class
 * Based largely on existing code from the Easy Digital Downloads plugin
 * @since 1.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * EDD_Receipts_Table Class
 *
 * Renders the Receipts table on the Conditional Receipts page
 *
 * @since 1.0
 */
class EDD_Receipts_Table extends WP_List_Table {
	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 1.0
	 */
	public $per_page = 30;

	/**
	 *
	 * Total number of receipts
	 * @var string
	 * @since 1.0
	 */
	public $total_count;

	/**
	 * Active number of receipts
	 *
	 * @var string
	 * @since 1.0
	 */
	public $active_count;

	/**
	 * Inactive number of receipts
	 *
	 * @var string
	 * @since 1.0
	 */
	public $inactive_count;

	/**
	 * Get things started
	 *
	 * @access public
	 * @since 1.0
	 * @uses EDD_Receipts_Table::get_receipt_counts()
	 * @see WP_List_Table::__construct()
	 * @return void
	 */
	public function __construct() {
		global $status, $page;

		parent::__construct( array(
			'singular'  => edd_get_label_singular(),    // Singular name of the listed records
			'plural'    => edd_get_label_plural(),    	// Plural name of the listed records
			'ajax'      => false             			// Does this table support ajax?
		) );

		$this->get_receipt_counts();
	}

	/**
	 * Show the search field
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @param string $text Label for the search box
	 * @param string $input_id ID of the search box
	 *
	 * @return void
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
			return;

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) )
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		if ( ! empty( $_REQUEST['order'] ) )
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
			<?php submit_button( $text, 'button', false, false, array('ID' => 'search-submit') ); ?>
		</p>
	<?php
	}

	/**
	 * Retrieve the view types
	 *
	 * @access public
	 * @since 1.0
	 * @return array $views All the views available
	 */
	public function get_views() {
		$base           = admin_url('edit.php?post_type=download&page=edd-receipts');

		$current        = isset( $_GET['status'] ) ? $_GET['status'] : '';
		$total_count    = '&nbsp;<span class="count">(' . $this->total_count    . ')</span>';
		$active_count   = '&nbsp;<span class="count">(' . $this->active_count . ')</span>';
		$inactive_count = '&nbsp;<span class="count">(' . $this->inactive_count  . ')</span>';

		$views = array(
			'all'		=> sprintf( '<a href="%s"%s>%s</a>', esc_url( remove_query_arg( 'status', $base ) ), $current === 'all' || $current == '' ? ' class="current"' : '', __('All', 'edd-ppe') . $total_count ),
			'active'	=> sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'status', 'active', $base ) ), $current === 'active' ? ' class="current"' : '', __('Active', 'edd-ppe') . $active_count ),
			'inactive'	=> sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'status', 'inactive', $base ) ), $current === 'inactive' ? ' class="current"' : '', __('Inactive', 'edd-ppe') . $inactive_count ),
		);

		return $views;
	}

	/**
	 * Retrieve the table columns
	 *
	 * @access public
	 * @since 1.0
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'download'  => sprintf( __( '%s', 'edd-ppe' ), edd_get_label_singular() ),
			'status'  	=> __( 'Status', 'edd-ppe' ),
		);

		return $columns;
	}

	/**
	 * Retrieve the table's sortable columns
	 *
	 * @access public
	 * @since 1.0
	 * @return array Array of all the sortable columns
	 */
	public function get_sortable_columns() {
		return array(
			'ID'     => array( 'ID', true ),
			'download'   => array( 'download', false ),
		);
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @param array $item Contains all the data of the receipt
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ){
			default:
				return $item[ $column_name ];
		}
	}



	/**
	 * Render the Download Column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $item Contains all the data of the receipt
	 * @return string Data shown in the Name column
	 */
	function column_download( $item ) {
		$receipt     = get_post( $item['ID'] );
		$base         = admin_url( 'edit.php?post_type=download&page=edd-receipts&edd-action=edit_receipt&receipt=' . $item['ID'] );
		$row_actions  = array();

		$row_actions['edit'] = '<a href="' . esc_url( add_query_arg( array( 'edd-action' => 'edit_receipt', 'receipt' => $receipt->ID ) ) ) . '">' . __( 'Edit', 'edd-ppe' ) . '</a>';

		if( strtolower( $item['status'] ) == 'active' )
			$row_actions['deactivate'] = '<a href="' . esc_url( add_query_arg( array( 'edd-action' => 'deactivate_receipt', 'receipt' => $receipt->ID, 'edd-message' => false ) ) ) . '">' . __( 'Deactivate', 'edd-ppe' ) . '</a>';
		else
			$row_actions['activate'] = '<a href="' . esc_url( add_query_arg( array( 'edd-action' => 'activate_receipt', 'receipt' => $receipt->ID, 'edd-message' => false ) ) ) . '">' . __( 'Activate', 'edd-ppe' ) . '</a>';

		$row_actions['test'] = '<a href="' . wp_nonce_url( add_query_arg( array( 'edd-action' => 'send_test_email', 'receipt' => $receipt->ID, 'edd-message' => false ) ), 'edd-ppe-test-email' ) . '">' . __( 'Send Test Email', 'edd-ppe' ) . '</a>';

		$row_actions['delete'] = '<a href="' . wp_nonce_url( add_query_arg( array( 'edd-action' => 'delete_receipt', 'receipt' => $receipt->ID, 'edd-message' => false ) ), 'edd_receipt_nonce' ) . '">' . __( 'Delete', 'edd-ppe' ) . '</a>';

		$row_actions = apply_filters( 'edd_receipt_row_actions', $row_actions, $receipt );

		return $item['download'] . $this->row_actions( $row_actions );
	}

	/**
	 * Render the checkbox column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $item Contains all the data for the checkbox column
	 * @return string Displays a checkbox
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'receipt',
			/*$2%s*/ $item['ID']
		);
	}

	/**
	 * Retrieve the bulk actions
	 *
	 * @access public
	 * @since 1.0
	 * @return array $actions Array of the bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'edd-ppe' )
		);

		return $actions;
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 1.0
	 * @access public
	 */
	function no_items() {
		_e( 'No emails found.', 'edd-ppe' );
	}


	/**
	 * Process the bulk actions
	 * @access public
	 * @since 1.0
	 * @return void
	 */


	public function process_bulk_action() {
		$ids = isset( $_GET[ 'receipt' ] ) ? $_GET[ 'receipt' ] : false;

		if ( ! is_array( $ids ) )
			$ids = array( $ids );

		foreach ( $ids as $id ) {
			if ( 'delete' === $this->current_action() ) {
				edd_ppe_remove_receipt( $id );
			}
		}

	}

	/**
	 * Retrieve the receipt counts
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function get_receipt_counts() {
		$receipt_count  = wp_count_posts( 'edd_receipt' );
		$this->active_count   = $receipt_count->active;
		$this->inactive_count = $receipt_count->inactive;
		$this->total_count    = $receipt_count->active + $receipt_count->inactive;
	}

	/**
	 * Retrieve all the data for all the receipts
	 *
	 * @access public
	 * @since 1.0
	 * @return array $receipt_data Array of all the data for the receipt
	 */
	public function receipt_data() {
		$receipt_data = array();

		$per_page = $this->per_page;

		$mode = edd_is_test_mode() ? 'test' : 'live';

		$orderby 		= isset( $_GET['orderby'] )  ? $_GET['orderby']                  : 'ID';
		$order 			= isset( $_GET['order'] )    ? $_GET['order']                    : 'DESC';
		$order_inverse 	= $order == 'DESC'           ? 'ASC'                             : 'DESC';
		$status 		= isset( $_GET['status'] )   ? $_GET['status']                   : array( 'active', 'inactive' );
		$meta_key		= isset( $_GET['meta_key'] ) ? $_GET['meta_key']                 : null;
		$search         = isset( $_GET['s'] )        ? sanitize_text_field( $_GET['s'] ) : null;
		$order_class 	= strtolower( $order_inverse );

		$receipts = edd_ppe_get_receipts( array(
			'posts_per_page' => $per_page,
			'paged'          => isset( $_GET['paged'] ) ? $_GET['paged'] : 1,
			'orderby'        => $orderby,
			'order'          => $order,
			'post_status'    => $status,
			'meta_key'       => $meta_key,
			's'              => $search
		) );

		if ( $receipts ) {
			foreach ( $receipts as $receipt ) {

				$download = edd_ppe_get_receipt_download( $receipt->ID ) ? get_the_title( edd_ppe_get_receipt_download( $receipt->ID ) ) : '';
				$download_id = get_post_meta( $receipt->ID, '_edd_receipt_download', true);

				$receipt_data[] = array(
					'ID' 			=> $receipt->ID,
					'download'		=> '<a class="row-title" href="' . esc_url( add_query_arg( array( 'edd-action' => 'edit_receipt', 'receipt' => $receipt->ID ) ) ) . '">' . $download .'</a>',
					'status'		=> ucwords( $receipt->post_status ),
				);

			}
		}

		return $receipt_data;
	}

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since 1.0
	 * @uses EDD_Receipts_Table::get_columns()
	 * @uses EDD_Receipts_Table::get_sortable_columns()
	 * @uses EDD_Receipts_Table::process_bulk_action()
	 * @uses EDD_Receipts_Table::receipt_data()
	 * @uses WP_List_Table::get_pagenum()
	 * @uses WP_List_Table::set_pagination_args()
	 * @return void
	 */
	public function prepare_items() {
		$per_page = $this->per_page;

		$columns = $this->get_columns();

		$hidden = array();

		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$data = $this->receipt_data();

		$current_page = $this->get_pagenum();

		$status = isset( $_GET['status'] ) ? $_GET['status'] : 'any';

		switch( $status ) {
			case 'active':
				$total_items = $this->active_count;
				break;
			case 'inactive':
				$total_items = $this->inactive_count;
				break;
			case 'any':
				$total_items = $this->total_count;
				break;
		}

		$this->items = $data;

		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page )
			)
		);
	}
}
