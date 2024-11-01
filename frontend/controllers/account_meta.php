<?php
namespace Transact\FrontEnd\Controllers\AccountMeta;

use WP_User;

use Transact\Utils\Config\Parser\ConfigParser;
require_once  plugin_dir_path(__FILE__) . '../../utils/transact-utils-config-parser.php';

use Transact\FrontEnd\Controllers\Api\TransactApi;
require_once  plugin_dir_path(__FILE__) . 'transact-api.php';

use Transact\Models\Buttons;
use Transact\FrontEnd\Controllers\Buttons\transactHandleButtons;
require_once  plugin_dir_path(__FILE__) . 'transact-handle-buttons.php';


/**
 * Class AccountMetaManager
 */
class AccountMetaManager
{
    const SUBSCRIPTION_INFO_META = 'xsact_subscription_info';
    const PURCHASE_INFO_META = 'xsact_purchase_info_';
    const XSACT_USER_ID_META = 'xsact_user_id';

    /**
     * @var number|null
     */
    protected $xsact_user_id;
    protected $subscription_info_meta = NULL;

    function __construct() {

    }

    /**
     * Get or create the automatically generated wordpress user based on a given username and email
     *
     * @param $username Wordpress username
     * @param $email
     * @param $user_display_name
     * @return int the WP_User ID
     */
    function get_or_create_auto_user($username, $email, $user_display_name)
    {
        $existing_user = get_user_by( 'login', $username );

        if(!$existing_user) {
            $existing_user = get_user_by( 'email', $email );
        }

        if(!$existing_user) {
            $bytes = random_bytes(16);
            $wp_userid = wp_insert_user(
                array(
                    'user_login' => $username,
                    'user_pass' => bin2hex($bytes),
                    'user_email' => $email,
                    'display_name' => $user_display_name,
                    'nickname' => $user_display_name
                )
            );

        } else {
            $wp_userid = $existing_user->ID;

            if ($existing_user->display_name !== $user_display_name) {
                wp_update_user(
                    array(
                        'ID' => $wp_userid,
                        'display_name' => $user_display_name,
                        'nickname' => $user_display_name
                    )
                );
            }
        }

        return $wp_userid;
    }

    /**
     * First check if the user is under any subscription plan (and is not expired). If yes, premium content will be shown.
     * If user is not subscribed, normal purchase check.
     *
     * @param $post_id
     * @return bool
     */
    function validate_access($post_id, $user_id = 0)
    {
        if ($user_id === 0) {
            $cur_user = wp_get_current_user();
            $user_id = $cur_user->ID;
        }
        if ($this->validate_subscription($post_id, $user_id)) {
            // user has subscription
            return TRUE;
        }

        if ($this->validate_purchase($post_id, $user_id)) {
            // purchase valid
            return TRUE;
        }

        if ($user_id) {
            // signed in
            $buttons = new transactHandleButtons($post_id, null);
            if ($buttons->is_sign_in_required_button()) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Check if there is subscription on cookies and check its expiration date comparing with the account meta
     * @return bool
     */
    function validate_subscription($post_id, $user_id, $subscription_info = null)
    {
        $cur_user = wp_get_current_user();
        if ($cur_user) {
            if(is_null($this->subscription_info_meta)) {
                $this->subscription_info_meta = get_user_meta($user_id, self::SUBSCRIPTION_INFO_META, true);
            }

            if(!$subscription_info) {
                $subscription_info = $this->subscription_info_meta;
            }

            if ($subscription_info) {
                // if the subscription is expired, check to see if they have renewed on xsact
                if (time() * 1000 > $subscription_info['expires']) {
                    $valid = $this->refresh_subscription($user_id, $subscription_info, $post_id);
                    return $valid;
                }

                if (time() * 1000 - $subscription_info['validated'] < 60 * 60 * 1 * 1000) { // Check once an hour
                    return true;
                } else {
                    // if the subscription needs to be re-validated, check to see if they still have a subscription on xsact
                    $valid = $this->refresh_subscription($user_id, $subscription_info, $post_id);

                    return $valid;
                }
            }
        }
        return false;
    }

    /**
     * Phone home to xsact and see if a subscription is valid. If it is, update the subscription validated meta
     * @return bool
     */
    function refresh_subscription($user_id, $subscription_info, $post_id) {
        $xsact_id = $this->xsact_user_id ? $this->xsact_user_id : get_user_meta($user_id, self::XSACT_USER_ID_META, true);
        $options = get_option('transact-settings');
        $publisher_id = $options['account_id'];

        $subscription_id = $xsact_id . '_' . $publisher_id;
        // Check with api
        $validate_url = (new ConfigParser())->checkIfValidUserSubscription();
        $valid = (new TransactApi($post_id))->check_if_valid_user_subscription($validate_url, $publisher_id, $subscription_id);

        if ($valid) {
            $update_subscription_info = array(
                'expires' => (gettype($valid) === 'boolean' ? $subscription_info['expires'] : $valid),
                'sale_id' => $subscription_info['sale_id'],
                'timestamp' => $subscription_info['timestamp'],
                'validated' => time() * 1000
            );
            update_user_meta(
                $user_id,
                self::SUBSCRIPTION_INFO_META,
                $update_subscription_info
            );
            $this->subscription_info_meta = $update_subscription_info;
        } elseif (
            // If valid is false, delete the subscription.
            // Also delete the subscription if the call failed
            // and the subscription is expired, or validated a long time ago
            $valid === false ||
            ($valid === null && (time() * 1000 - $subscription_info['expires'] > 0)) ||
            ($valid === null && (time() * 1000 - $subscription_info['validated'] < 60 * 60 * 24 * 1000))
        ) {
            delete_user_meta(
                $user_id,
                self::SUBSCRIPTION_INFO_META
            );
            $this->subscription_info_meta = null;
        }

        return $valid;
    }

    /**
     * Check if there is a purchase of that post_id recorded on the user account
     * @param $post_id
     * @return bool
     */
    function validate_purchase($post_id, $user_id)
    {
        $meta_key = self::PURCHASE_INFO_META . $post_id;

        $purchase_info = get_user_meta($user_id, $meta_key, true);
        if ($purchase_info) {
            return true;
        }
        return false;
    }

    /**
     * Add record of a subscription on the user account
     * @param $post_id
     * @return bool
     */
    function add_subscription($post_id, $expiration, $sales_id, $timestamp, $user_id = 0)
    {
        if(!$user_id) {
            $cur_user = wp_get_current_user();
            $user_id = $cur_user->ID;
        }

        $new_subscription_info = array(
            'expires' => $expiration,
            'sale_id' => $sales_id,
            'timestamp' => $timestamp,
            'validated' => 0
        );

        if($this->validate_subscription($post_id, $user_id, $new_subscription_info)) {
            $new_subscription_info['validated'] = $timestamp;

            $subscription_info = get_user_meta($user_id, self::SUBSCRIPTION_INFO_META, true);
            if ($subscription_info) {
                update_user_meta(
                    $user_id,
                    self::SUBSCRIPTION_INFO_META,
                    $new_subscription_info
                );
            } else {
                add_user_meta(
                    $user_id,
                    self::SUBSCRIPTION_INFO_META,
                    $new_subscription_info
                );
            }
            $this->subscription_info_meta = $new_subscription_info;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Add record of a purchase on the user account
     * @param $post_id
     * @return bool
     */
    function add_purchase($post_id, $sales_id, $timestamp, $user_id = 0)
    {
        if(!$user_id) {
            $cur_user = wp_get_current_user();
            $user_id = $cur_user->ID;
        }
        $subscription_info = get_user_meta($user_id, self::SUBSCRIPTION_INFO_META, true);

        // Do not register a purchase if the user has a subscription
        if(!$subscription_info) {
            $new_purchase_info = array(
                'sale_id' => $sales_id,
                'timestamp' => $timestamp
            );
            $meta_key = self::PURCHASE_INFO_META . $post_id;

            $purchase_info = get_user_meta($user_id, $meta_key, true);
            if ($purchase_info) {
                update_user_meta(
                    $user_id,
                    $meta_key,
                    $new_purchase_info
                );
            } else {
                add_user_meta(
                    $user_id,
                    $meta_key,
                    $new_purchase_info
                );
            }
        }
    }

    /**
     * Add metadata for tracking the transact account connected to this account
     * @param $post_id
     * @return bool
     */
    function connect_xsact_id_meta($wp_userid, $xsact_id) {
        $this->xsact_user_id = intval($xsact_id);

        update_user_meta(
            $wp_userid,
            self::XSACT_USER_ID_META,
            intval($xsact_id)
        );
    }

}
