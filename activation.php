<?php

global $wpdb;

$table_name = $wpdb->prefix . 'transact_transactions';
$sql        = "CREATE TABLE IF NOT EXISTS {$table_name} (
          `sales_id` varchar(32) NOT NULL,
          `post_id` bigint(20) NOT NULL,
          `timestamp` bigint(20) NOT NULL,
          PRIMARY KEY (`sales_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

$subscription_table = $wpdb->prefix . 'transact_subscription_transactions';
$sql        = "CREATE TABLE IF NOT EXISTS {$subscription_table} (
          `sales_id` varchar(32) NOT NULL,
          `expiration` bigint(20) NOT NULL,
          `timestamp` bigint(20) NOT NULL,
          PRIMARY KEY (`sales_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
dbDelta($sql);
