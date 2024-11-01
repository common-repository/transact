<?php
namespace Transact\FrontEnd\Controllers\Api;

use Transact\Utils\Settings\CurrencyUtils;
require_once  plugin_dir_path(__FILE__) . '../../utils/transact-currency-utils.php';

/**
 * Including transact io php library
 */
require_once  plugin_dir_path(__FILE__) . '/../../vendors/transact-io-php/transact-io.php';

/**
 * We need admin settings menu to rescue the settings
 */
require_once  plugin_dir_path(__FILE__) . '/../../admin/controllers/transact-admin-settings-menu.php';
use Transact\Admin\Settings\Menu\AdminSettingsMenuExtension;

/**
 * We need admin settings menu to rescue the settings
 */
require_once  plugin_dir_path(__FILE__) . '/../../admin/controllers/transact-admin-settings-post.php';
use Transact\Admin\Settings\Post\AdminSettingsPostExtension;

/**
 * Managing user account metadata
 */
require_once  plugin_dir_path(__FILE__) . '/account_meta.php';
use Transact\FrontEnd\Controllers\AccountMeta\AccountMetaManager;

/**
 * Class TransactApi
 */
class TransactApi
{
    /**
     * @var \TransactIoMsg
     */
    public $transact;

    /**
     * @var int
     */
    public $post_id;

    /**
     * All information to set on Transact Io Object
     */
    protected $recipient_id;
    protected $secret_id;
    protected $env;

    protected $price;     // price in tokens for the item
    protected $item_id;   // unique code for article (Item code)
    protected $sales_id;  // unique code for sale

    protected $article_title;
    protected $article_url;

    protected $method;
    protected $alg;

    protected $affiliate;

    public $get_search_engine_access;

    const ACCOUNT_ID_KEY = '{{account_id}}';
    const SUBSCRIPTION_ID_KEY = '{{subscription_id}}';

    /**
     * @param $post_id
     * @throws \Exception
     */
    function __construct($post_id)
    {
        $this->post_id = $post_id;
        $this->get_transact_information();
        $this->transact = new \TransactIoMsg();
    }

    function ends_with( $haystack, $needle ) {
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }

    /**
     * Set All info needed for Transact
     * We grab the information from Transact Settings (Dashboard)
     * and specific post.
     *
     */
    function get_transact_information()
    {
        $currency_utils = new CurrencyUtils();
        $settings_menu_dashboard = new AdminSettingsMenuExtension();
        $this->recipient_id = $settings_menu_dashboard->get_account_id();
        $this->secret_id    = $settings_menu_dashboard->get_secret();
        $this->get_search_engine_access = $settings_menu_dashboard->get_search_engine_access();
        $this->env          = $currency_utils->get_currency_from_options(); // TEST, PROD, or another currency code

        $settings_post = new AdminSettingsPostExtension($this->post_id);
        $this->price     = $settings_post->get_transact_price();

        $this->item_id   = $settings_post->get_transact_item_code();

        // creates a uniqid, with item_id as prefix and then md5 (32 characters)
        $this->sales_id  = md5(uniqid($this->item_id, true));

        $this->article_title = get_the_title($this->post_id);
        $this->article_url   = get_permalink($this->post_id);

        /**
         * todo: what are the options?
         */
        $this->method = 'CLOSE';
        $this->alg    = 'HS256';

    }

    /**
     * Set Affiliated reference
     *
     * @param int $affiliate_id
     */
    function set_affiliate($affiliate_id)
    {
        $this->affiliate = $affiliate_id;
    }

    /**
     * Gets token to set up on transact js library
     *
     * @return array return token json {"token":"xxx", "sales_id":"yyy"}
     */
    function get_token()
    {
        $valid = $this->init_sale_parameters($this->transact);
        if(!$valid) {
            return array();
        }

        $response = array(
            'token' => $this->transact->getToken(),
            'sales_id' => $this->sales_id
        );
        return $response;
    }

    /**
     * Gets subscription token to set up on transact js library
     *
     * @return string return token json {"token":"xxx"}
     */
    function get_subscription_token()
    {
        $valid = $this->init_sale_parameters($this->transact);
        if(!$valid) {
            return array();
        }

        $price = 1; // FIXME get price
        $period = 'MONTHLY'; // default to 'MONTHLY'.

        $response = array(
            'token' => $this->transact->getSubscriptionToken($price, $period)
        );

        return $response;
    }

    /**
     * Get donation token
     *
     * @param $price
     * @param string $affiliate_id
     * @throws \Exception
     */
    function get_donation_token($price, $affiliate_id = null)
    {
        $valid = $this->init_sale_parameters($this->transact);
        if(!$valid || $price <= 0) {
            return array();
        }
        $this->transact->setPrice($price);
        $this->transact->setDonation(TRUE);

        if ($affiliate_id) {
            $this->transact->setAffiliate($affiliate_id);
        }

        $response = array(
            'token' => $this->transact->getToken()
        );
        return $response;
    }

    function decode_token($token)
    {
        $this->transact->setSecret($this->secret_id);
        $this->transact->setAlg($this->alg);
        return $this->transact->decodeToken($token);
    }

    function decode_login_token($token)
    {
        $this->transact->setSecret($this->secret_id);
        $this->transact->setAlg($this->alg);
        return $this->transact->decodeLoginToken($token);
    }

    /**
     * Function to get premium content from transact_premium_content metadata.
     *
     * @return string
     */
    function get_premium_content()
    {
        $premium_content = get_post_meta( $this->post_id, 'transact_premium_content' , true ) ;
        // wpautop emulates normal wp editor behaviour (adding <p> automatically)
        return wpautop(htmlspecialchars_decode(do_shortcode($premium_content)));
    }

    /**
     * Init library with sales parameter for a given article
     *
     * @param \TransactIoMsg $transact
     */
    function init_sale_parameters($transact)
    {
        if($this->price < 0 || $this->price > 100000) {
            return false;
        }

        $transact->setSecret($this->secret_id);
        $transact->setAlg($this->alg);

        // Required: set ID of who gets paid
        $transact->setRecipient($this->recipient_id);

        // Required for posts:  Set the price of the sale. Not needed for subscriptions (validation occurs in transact-single-post).
        if ($this->price > 0) {
            $transact->setPrice($this->price);
        }

        // Required:  Set PROD to use USD,  TEST for testing money, or any currency code (USD/GBP)
        $transact->setClass($this->env);

        // Required:  set URL associated with this puchase
        // User should be able to return to this URL
        $transact->setURL($this->article_url);

        // Recommended: Title for customer to read for the purchase
        $transact->setTitle($this->article_title);

        $transact->setMethod($this->method); // Optional: by default close the popup


        // Unique code for seller to set to what they want
        //  This could be a code for the item your selling
        $transact->setItem($this->item_id);

        // Optional Unique ID of this sale
        $transact->setUid($this->sales_id);

        // Set Affiliated if exists
        $transact->setAffiliate($this->affiliate);

        return true;
    }

    /**
     * It checks if a user has purchased the article
     * it checks user cookie against Transaction table DB
     *
     * @return bool
     */
    public function is_premium($user_id = 0)
    {
        // Admins with edit post permissions can always view premium
        if(current_user_can( 'edit_posts' )) {
            return true;
        }

        if ($this->get_search_engine_access == 'seo_full_access'
           && $this->is_search_engine()) {

               return true;
           }

        $account_meta = new AccountMetaManager();
        $result = $account_meta->validate_access($this->post_id, $user_id);

        return $result;
    }

    public function get_price()
    {
        return $this->price;
    }

    /**
     * Determine if the client is a search engine.
     *
     * @return bool
     */
    public function is_search_engine()
    {
        $key_group = 'transact-group';
        $is_search_engine = false; // default
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders
        if (empty($_SERVER['REMOTE_ADDR'])) {
            return;
        }

        // ignore phpcs here because this function is also called to fetch
        // content from AJAX javascript call.
        // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);

        // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders
        $ip_addr = sanitize_text_field($_SERVER['REMOTE_ADDR']);

        $key = $user_agent .'-'. $ip_addr;

        // use WP caching since DNS lookups can be slow and cause this function
        // to have high latency
        $cached_data = wp_cache_get($key, $key_group);
        if ($cached_data !== false && isset($cached_data['is_search_engine'])) {
            // cache hit
            return $cached_data['is_search_engine'];
        }


        // andy google user agent
        // https://developers.google.com/search/docs/advanced/crawling/overview-google-crawlers
        if (stripos($user_agent, 'google') !== false) {
            // only do reverse DNS if we think its a search engine since it can be slow.
            $hostname = gethostbyaddr($ip_addr);
            // https://developers.google.com/search/docs/advanced/crawling/verifying-googlebot
            if ($this->ends_with($hostname,'googlebot.com') || $this->ends_with($hostname,'google.com')) {
                $is_search_engine = true;
            }
        } elseif (stripos($user_agent, 'bingbot/') !== false) {
            // https://blogs.bing.com/webmaster/2012/08/31/how-to-verify-that-bingbot-is-bingbot/
            $hostname = gethostbyaddr($ip_addr);

            if ($this->ends_with($hostname, 'search.msn.com')) {
                $is_search_engine = true;
            }
        } elseif (stripos($user_agent, 'yanndex') !== false) {
            $hostname = gethostbyaddr($ip_addr);
            // https://yandex.com/support/webmaster/robot-workings/check-yandex-robots.html
            if ($this->ends_with($hostname,'yandex.ru') || $this->ends_with($hostname,'yandex.net')) {
                $is_search_engine = true;
            }
        } elseif (stripos($user_agent, 'duckduckbot') !== false) {
            $hostname = gethostbyaddr($ip_addr);
            // https://help.duckduckgo.com/duckduckgo-help-pages/results/duckduckbot/
            if ($this->ends_with($hostname,'.duckduckgo.com')) {
                $is_search_engine = true;
            }
        }

        $cache_data = array(
            'is_search_engine' => $is_search_engine,
        );

        wp_cache_set($key, $cache_data, $key_group, 60);
        return $is_search_engine;
    }

    /** check_if_valid_user_subscription
     * @ret
     */
    public function check_if_valid_user_subscription($validate_url, $account_id, $subscription_id) {
        $url = str_replace(self::ACCOUNT_ID_KEY, $account_id, $validate_url);
        $url = str_replace(self::SUBSCRIPTION_ID_KEY, $subscription_id, $url);

        $this->transact->setSecret($this->secret_id);
        $this->transact->setAlg($this->alg);

        $secret = $this->transact->getSubscriptionValidationToken($account_id);

        $referer_uri = get_permalink($this->post_id);
        if (is_wp_error( $referer_uri )) {
            return null; // Null is error state
        }

        // for now don't want to add more dependencies so ignore.
        //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
        $ret = wp_remote_get($url, array(
            'headers' => array(
                'Secret' => $secret,
                'Referer' => $referer_uri,
            )
        ));

        if (is_wp_error( $ret )) {
            return null; // Null is error state
        }

        $code = $ret['response']['code'];

        if ($code == 404 || $code == 402) {
            return false; // False removes the subscription
        }

        $body = json_decode($ret['body']);
        if (empty($body)) {
            return null;
        }

        if ($body->expires > time() * 1000) {
            return $body->expires;
        } else {
            return false;
        }
    }

    public function get_oauth_access_token_with_code($oauth_token_url, $account_id,
                                                     $client_secret, $oauth_code) {


        $oauth_token_data = array(
            'method'      => 'POST',
            // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
            'timeout'     => 10,
            'body' => array(
                'code' => $oauth_code,
                'client_id' => $account_id,
                'client_secret' => $client_secret,
                'grant_type' => 'authorization_code',
                )
        );

        $ret = wp_remote_post($oauth_token_url,  $oauth_token_data);

        if (is_wp_error( $ret )) {
            return null; // Null is error state
        }

        $code = $ret['response']['code'];

        $body = $ret['body'];
        if ($code != 200) {
            return null;
        }

        $body = json_decode($body);
        if (empty($body)) {
            return null;
        }

        return $body;
    }


    public function get_user_profile_with_oauth_code($oauth_token_url,
                                                     $user_profile_url,
                                                     $account_id,
                                                     $client_secret,
                                                     $oauth_code) {

        $token_response = $this->get_oauth_access_token_with_code(
           $oauth_token_url, $account_id, $client_secret, $oauth_code);

        if (empty($token_response)) {
           return null;
        }

        $access_token = $token_response->access_token;
        $bearer_token_header = 'Authorization: Bearer ' . $access_token;
        $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
        ));

        $ret = wp_remote_get($user_profile_url, $args);

        if (is_wp_error( $ret )) {
            return null; // Null is error state
        }
        $code = $ret['response']['code'];
        $body = $ret['body'];
        if ($code != 200) {
            return null;
        }

        $profile = json_decode($body);
        if (empty($profile)) {
            return null;
        }
        return $profile;
    }

}
