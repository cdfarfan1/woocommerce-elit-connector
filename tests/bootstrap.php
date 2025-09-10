<?php
/**
 * PHPUnit bootstrap file for WooCommerce NewBytes Plugin
 *
 * @package NewBytes_Connector
 */

// Composer autoloader must be loaded before WP_PHPUNIT_DIR will be available
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once getenv( 'WP_PHPUNIT_DIR' ) . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load WooCommerce first
	require_once dirname( dirname( __FILE__ ) ) . '/wp-content/plugins/woocommerce/woocommerce.php';
	
	// Load our plugin
	require dirname( dirname( __FILE__ ) ) . '/woocommerce-newbytes.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require getenv( 'WP_PHPUNIT_DIR' ) . '/includes/bootstrap.php';

// Load WooCommerce testing framework
require_once dirname( dirname( __FILE__ ) ) . '/wp-content/plugins/woocommerce/tests/legacy/framework/class-wc-unit-test-case.php';

/**
 * Custom test case class for NewBytes plugin
 */
abstract class NB_Unit_Test_Case extends WC_Unit_Test_Case {

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Activate WooCommerce
		activate_plugin( 'woocommerce/woocommerce.php' );
		
		// Create admin user for testing
		$this->admin_user = $this->factory->user->create( array(
			'role' => 'administrator'
		) );
		
		// Create shop manager user for testing
		$this->shop_manager = $this->factory->user->create( array(
			'role' => 'shop_manager'
		) );
		
		// Create customer user for testing
		$this->customer = $this->factory->user->create( array(
			'role' => 'customer'
		) );
	}
	
	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		parent::tearDown();
		
		// Clean up any test data
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}nb_sync_logs" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}nb_error_logs" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}nb_security_logs" );
	}
	
	/**
	 * Helper method to create AJAX request
	 */
	protected function make_ajax_request( $action, $data = array(), $nonce_action = null ) {
		$_POST['action'] = $action;
		
		if ( $nonce_action ) {
			$_POST['nonce'] = wp_create_nonce( $nonce_action );
		}
		
		foreach ( $data as $key => $value ) {
			$_POST[$key] = $value;
		}
		
		try {
			ob_start();
			do_action( 'wp_ajax_' . $action );
			$response = ob_get_clean();
			return json_decode( $response, true );
		} catch ( WPDieException $e ) {
			return array( 'error' => $e->getMessage() );
		}
	}
	
	/**
	 * Helper method to simulate user login
	 */
	protected function login_as( $user_id ) {
		wp_set_current_user( $user_id );
	}
}