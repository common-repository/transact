<?php
/*
 * Plugin Name: transact.io
 * Description: Integrates transact.io services into WP
 * Version: 6.0.0
 * Author: transact.io
 * Author URI: https://transact.io
 * Plugin URI: https://wordpress.org/plugins/transact/
 */
define('TRANSACT_VERSION', '6.0.0'); // Cache busting

/**
 * Requires
 */
use Transact\Admin\DashboardExtension;
require_once( plugin_dir_path(__FILE__) . 'admin/transact-admin.php' );

use Transact\FrontEnd\Controllers\Post\FrontEndPostExtension;
require_once( plugin_dir_path(__FILE__) . 'frontend/controllers/transact-single-post.php' );

/**
 * Define all constants
 */
define('TRANSACT_PATH', dirname( __FILE__ ));
define('CONFIG_PATH', dirname( __FILE__ ) . '/config.ini');
define('VENDORS_PATH', dirname( __FILE__ ) . '/vendors/');
define('VENDORS_URL', plugins_url('/vendors/transact-io-php/', __FILE__));
define('FRONTEND_ASSETS_URL', plugins_url('/frontend/assets/', __FILE__));

/**
 * Transient that holds validation status
 */
define('SETTING_VALIDATION_TRANSIENT', 'setting_validation_transient');
define('SETTING_VALIDATION_SUBSCRIPTION_TRANSIENT', 'setting_validation_subscription_transient');


/**
 * Hooking functionality to Dashboard
 */
(new DashboardExtension())->hookToDashboard();

// Get singleton instance
$TransactPaymentsFrontendPostExt = FrontEndPostExtension::getInstance();

// Hooking functionality to Single Post Frontend
$TransactPaymentsFrontendPostExt->registerRestApi();
$TransactPaymentsFrontendPostExt->hookSinglePost();


/**
 * On Activation: Create table
 */
register_activation_hook(__FILE__, 'transact_activation');
function transact_activation()
{
    require_once plugin_dir_path(__FILE__) . 'activation.php';
}


add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'transact_add_action_links' );

function transact_add_action_links ( $links ) {
    $mylinks = array(
    '<a href="' . admin_url( 'admin.php?page=transact-admin-page.php' ) . '">Settings</a>',
    );
    return array_merge( $links, $mylinks );
}
