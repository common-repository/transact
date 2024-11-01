<?php
namespace Transact\FrontEnd\Controllers\Cookie;

use Transact\Models\transactTransactionsTable\transactTransactionsModel;
require_once  plugin_dir_path(__FILE__) . '../../models/transact-transactions-table.php';

use Transact\Models\transactTransactionsTable\transactSubscriptionTransactionsModel;
require_once  plugin_dir_path(__FILE__) . '../../models/transact-subscription-transactions-table.php';


/**
 * Class CookieManager
 */
class CookieManager
{
    const COOKIE_PURCHASE_NAME = 'wp_transact_';

    const COOKIE_SUBSCRIPTION_NAME = 'wp_subscription_transact_';

    /**
     * @var array|null
     */
    protected $cookie;


    function __construct() {

        // safe to ignore input because validate_cookie is ONLY called
        // via transact-api  JS AJAX call.
        // phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE

        global $wpsc_cookies;

        if (isset($_COOKIE[self::COOKIE_PURCHASE_NAME])) {
            $this->cookie['purchases'] = json_decode(sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_PURCHASE_NAME])));
        }
        if (isset($_COOKIE[self::COOKIE_SUBSCRIPTION_NAME])) {
            $this->cookie['subscriptions'] = json_decode(sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_SUBSCRIPTION_NAME])));
        }

        // For integrating with WP Super Cache, so the cache is aware of purchase states
        if (
            isset( $wpsc_cookies ) &&
            is_array( $wpsc_cookies )
        ) {
            if(!in_array(self::COOKIE_PURCHASE_NAME, $wpsc_cookies)) {
                array_push($wpsc_cookies, self::COOKIE_PURCHASE_NAME);
            }
            if(!in_array(self::COOKIE_SUBSCRIPTION_NAME, $wpsc_cookies)) {
                array_push($wpsc_cookies, self::COOKIE_SUBSCRIPTION_NAME);
            }
        } else {
            $wpsc_cookies = array(self::COOKIE_PURCHASE_NAME, self::COOKIE_SUBSCRIPTION_NAME);
        }
    }

    /**
     * First check if the user is under any subscription plan (and is not expired). If yes, premium content will be shown.
     * If user is not subscribed, normal purchase check.
     *
     * @param $post_id
     * @return bool
     */
    function validate_cookie($post_id)
    {
        return ($this->validate_subscriptions() || $this->validate_purchase($post_id));
    }

    /**
     * Check if there is subscription on cookies and check its expiration date comparing with the DB entry
     * @return bool
     */
    function validate_subscriptions()
    {
        if (isset($this->cookie['subscriptions']) && (!empty($this->cookie['subscriptions']))) {
            /**
             * In theory should only have one, but it will create more on the cookie everytime the button is clicked
             * so may be a case with different subscriptions.
             */
            foreach ($this->cookie['subscriptions'] as $subscription) {
                $transactModel = new transactSubscriptionTransactionsModel();
                $transaction = $transactModel->get_subscription_by_sale_id($subscription->uid);
                if (!empty($transaction) && intval($transaction['expiration']) === intval($subscription->expiration)) {
                    // Condition for valid expiration
                    if ((time() * 1000) < $subscription->expiration) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Check if there is a purchase of that post_id on the cookies
     * @param $post_id
     * @return bool
     */
    function validate_purchase($post_id)
    {
        if(!empty($this->cookie['purchases'])) {
            foreach ($this->cookie['purchases'] as $cookie) {
                if (intval($cookie->id) === intval($post_id)) {
                    $transactModel = new transactTransactionsModel();
                    $transaction = $transactModel->get_transaction_by_sale_id($cookie->uid);
                    if (!empty($transaction) && intval($transaction['post_id']) === intval($post_id)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

}
