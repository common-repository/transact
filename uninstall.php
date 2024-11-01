<?php

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_transient( SETTING_VALIDATION_TRANSIENT );

global $wpdb;
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

$table_name = $wpdb->prefix . 'transact_transactions';
$query  = "DROP TABLE {$table_name};";
dbDelta($query);

$table_name = $wpdb->prefix . 'transact_subscription_transactions';
$query  = "DROP TABLE {$table_name};";
dbDelta($query);

