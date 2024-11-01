<?php
namespace Transact\FrontEnd\Controllers\Post;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use Exception;

use Transact\FrontEnd\Controllers\Api\TransactApi;
require_once  plugin_dir_path(__FILE__) . 'transact-api.php';

use Transact\Utils\Config\Parser\ConfigParser;
require_once  plugin_dir_path(__FILE__) . '../../utils/transact-utils-config-parser.php';

use Transact\Models\transactTransactionsTable\transactSubscriptionTransactionsModel;
require_once  plugin_dir_path(__FILE__) . '../../models/transact-subscription-transactions-table.php';

use Transact\Models\transactTransactionsTable\transactTransactionsModel;
require_once  plugin_dir_path(__FILE__) . '../../models/transact-transactions-table.php';

use Transact\Utils\Settings\cpt\SettingsCpt;
require_once  plugin_dir_path(__FILE__) . '../../utils/transact-settings-cpt.php';

use Transact\FrontEnd\Controllers\Buttons\transactHandleButtons;
require_once  plugin_dir_path(__FILE__) . 'transact-handle-buttons.php';

use Transact\FrontEnd\Controllers\AccountMeta\AccountMetaManager;
require_once  plugin_dir_path(__FILE__) . '/account_meta.php';

use WP_Post;




/**
 * Class FrontEndPostExtension
 */
class FrontEndPostExtension
{
    const DISABLE = 4;
    const INLINE_COMMENTS_OUTPUT = true;
    const NO_TRANSACT_WARNING  = '<div class="no-transact-warning" id="no_transact">
    <h3>Transact payments script could not be loaded</h3>
    <p>Transact.io respects your privacy, does not display advertisements, and does not sell your data.</p>
    <p>To enable payment or login you will need to allow third party scripts from <strong>transact.io</strong>.</p>
    </div>';
    const NO_COOKIES_WARNING  = '<div class="no-cookies-warning" id="no_cookies_transact">
    <h3>Cookies are disabled</h3>
    <p>Transact.io respects your privacy, does not display advertisements, and does not sell your data.</p>
    <p>To enable purchase or subscriptions you will need to enable cookies.</p>
    </div>';

    /**
     * config controller
     * @var
     */
    protected $config;

    /**
     * Post id where this hook is called.
     *
     * @var int
     */
    protected $post_id;

    protected static $instance = NULL;

    public $get_content_priority = 999;
    public $rest_content_context = false;

    /**
     * Get instance to singleton.
     * Other plugins use this to get common singleton instance
     */
    public static function getInstance() {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    /**
     * All rest api endpoints
     */
    public function registerRestApi()
    {
        add_action( 'rest_api_init', function () {
            register_rest_route( 'transact/v1', '/token/(?P<post_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'request_token_rest'),
                'args' => array(
                    'post_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'mode' => array(
                        'default' => 'post',
                    )
                ),
                'permission_callback' => '__return_true'
            ) );
            register_rest_route( 'transact/v1', '/token/(?P<post_id>\d+)/(?P<affiliate_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'request_token_rest'),
                'args' => array(
                    'post_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'affiliate_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'mode' => array(
                        'default' => 'post',
                    )
                ),
                'permission_callback' => '__return_true'
            ) );
            register_rest_route( 'transact/v1', '/oauth/state', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_oauth_state'),
                'args' => array(
                    'post_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                ),
                'permission_callback' => '__return_true'
            ) );
            register_rest_route( 'transact/v1', '/subscription/(?P<post_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'request_token_rest'),
                'args' => array(
                    'post_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'mode' => array(
                        'default' => 'subscription',
                    )
                ),
                'permission_callback' => '__return_true'
            ) );
            register_rest_route( 'transact/v1', '/subscription/(?P<post_id>\d+)/(?P<affiliate_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'request_token_rest'),
                'args' => array(
                    'post_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'affiliate_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'mode' => array(
                        'default' => 'subscription',
                    )
                ),
                'permission_callback' => '__return_true'
            ) );
            register_rest_route( 'transact/v1', '/donation/(?P<post_id>\d+)/(?P<amount>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'request_donation_token_rest'),
                'args' => array(
                    'post_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'amount' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    )
                ),
                'permission_callback' => '__return_true'
            ) );
            register_rest_route( 'transact/v1', '/donation/(?P<post_id>\d+)/(?P<amount>\d+)/(?P<affiliate_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'request_donation_token_rest'),
                'args' => array(
                    'post_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'amount' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'affiliate_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    )
                ),
                'permission_callback' => '__return_true'
            ) );
            register_rest_route( 'transact/v1', '/verify', array(
                'methods' => 'POST',
                'callback' => array($this, 'purchase_verify_rest'),
                'args' => array(
                    'post_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    'token' => array()
                ),
                'permission_callback' => '__return_true'
            ) );
            register_rest_route( 'transact/v1', '/premium/(?P<post_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_premium_content_rest'),
                'args' => array(
                    'post_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    )
                ),
                'permission_callback' => function () {
                    return get_current_user_id();
                }
            ) );
            register_rest_route( 'transact/v1', '/comments_template/(?P<post_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_comment_template_rest'),
                'args' => array(
                    'post_id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    )
                ),
                'permission_callback' => function () {
                    return get_current_user_id();
                }
            ) );
        });
    }

    public function get_oauth_state( WP_REST_Request $request ) {

        $post_id = (int) $request->get_param( 'post_id' );
        $options = get_option('transact-settings');
        $account_id = $options['account_id'];
        $record = array(
            'client_id' => $account_id,
            'redirect' => get_permalink($post_id),
            'state' => wp_create_nonce('oauth'),
            'url' => $this->config->getOauthURL()
        );


        return new WP_REST_Response( $record, 200 );
    }

    // each post will have /oauthcallback suffix on it to know we should validate the oauth
    // then redirect user without their unique codes in the URL
    public function oauth_login_validate() {
        $this->post_id = get_the_ID();

        if (empty($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], "OAuthCode") === false) {
            return;
        }


        if (empty($_GET['code'])) {
            return;
        }
        $oauth_code = $_GET['code'];

        if (empty($_GET['state'])) {
            return;
        }
        $oauth_state = $_GET['state'];

        if (wp_verify_nonce($oauth_state, 'oauth') === FALSE) {
            return;
        }
        $options = get_option('transact-settings');
        $token_url = $this->config->getOauthTokenURL();
        $user_profile_url = $this->config->getOauthUserProfileURL();
        $transact = new TransactApi($this->post_id);

        $profile = $transact->get_user_profile_with_oauth_code($token_url, $user_profile_url,
            $options['account_id'], $options['secret_key'], $oauth_code);

        if (empty($profile)) {
            return;
        }

        $xsact_user_id = (int) $profile->id;
        $user_email = (string) $profile->email1;
        if (empty($profile->first_name)) {
            // use first part of email
            $pieces = explode("@", $user_email);
            $user_display_name = $pieces[0];
        } else {
            $user_display_name = $profile->first_name;
        }

        $accountMeta = new AccountMetaManager();

        // create or find the wp user account based on the transact account,
        // then log the user in.
        $username = 'xsact_' . $xsact_user_id;

        $wp_userid = $accountMeta->get_or_create_auto_user($username, $user_email, $user_display_name);
        $accountMeta->connect_xsact_id_meta($wp_userid, $xsact_user_id);

        wp_set_current_user ( $wp_userid );
        wp_set_auth_cookie  ( $wp_userid, true ); // second parameter true remembers user for 14 days.

        // redirect to remove oauth code and state from URL
        wp_redirect(get_permalink($this->post_id));
        exit;
    }

    public function request_token_rest( WP_REST_Request $request ) {
        $token = '';
        $mode = (string) $request->get_param( 'mode' );
        $post_id = (int) $request->get_param( 'post_id' );
        $affiliate_id = (int) $request->get_param( 'affiliate_id' );

        if (empty($post_id)) {
            return new WP_Error(400, 'Error: Missing post ID', array(
                'content' => 'Failed validation. missing post ID',
                'status' => 'ERROR',
            ));
        }

        $transact = new TransactApi($post_id);
        if (!empty($affiliate_id)) {
            $transact->set_affiliate($affiliate_id);
        }

        if ($mode == 'post') {
            if($transact->get_price() <= 0) {
                return new WP_Error(500, 'Price must be greater than zero.');
            }
            $token = $transact->get_token();

            // Create purchase record in DB so we can validate the purchase complete against the post id
            // This prevents people from reusing tokens on different posts!
            $tableModel = new transactTransactionsModel();
            $tableModel->create_transaction($post_id, $token['sales_id']);
        } elseif($mode == 'subscription') {
            $token = $transact->get_subscription_token();
        }

        if($token) {
            return new WP_REST_Response( $token, 200 );
        } else {
            return new WP_Error(500, 'Could not generate token');
        }
    }

    public function request_donation_token_rest( WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'post_id' );
        $affiliate_id = (int) $request->get_param( 'affiliate_id' );
        $amount = (int) $request->get_param( 'amount' );

        if (empty($post_id)) {
            return new WP_Error(400, 'Error: missing post ID', array(
                'content' => 'Failed validation. missing post ID',
                'status' => 'ERROR',
            ));
        }
        if (!$amount) {
            return new WP_Error(400, 'Invalid amount', array(
                'content' => 'Failed validation. Missing donation amount',
                'status' => 'ERROR',
            ));
        }
        if (!$this->check_if_post_is_under_donation($post_id)) {
            return new WP_Error(401);
        }

        $transact = new TransactApi($post_id);
        if (empty($affiliate_id)) {
            $affiliate_id = null;
        }
        $token = $transact->get_donation_token($amount, $affiliate_id);

        if($token) {
            return new WP_REST_Response( $token, 200 );
        } else {
            return new WP_Error(500, 'Could not generate token');
        }
    }

    public function purchase_verify_rest( WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'post_id' );
        $token = (string) $request->get_param( 'token' );
        $xsact_user_id = (int) $request->get_param( 'user_xsact_id' );
        $user_email = (string) $request->get_param( 'user_email' );
        $user_display_name = (string) $request->get_param( 'user_display_name' );
        $check_premium_only = (string) $request->get_param( 'check_premium' );

        if (empty($post_id)) {
            return new WP_Error(400, 'Error: missing post ID', array(
                'content' => 'Failed validation. Missing post ID',
                'status' => 'ERROR',
            ));
        }
        if (empty($token)) {
            return new WP_Error(400, 'Error: missing token', array(
                'content' => 'Invalid request. Missing purchase token',
                'status' => 'ERROR',
            ));
        }
        if (empty($xsact_user_id) || $xsact_user_id === 0) {
            return new WP_Error(400, 'Error: missing xsact user id', array(
                'content' => 'Invalid request. Missing xsact user id',
                'status' => 'ERROR',
            ));
        }

        try {
            $transact = new TransactApi($post_id);
            $accountMeta = new AccountMetaManager();

            if ($check_premium_only) {
                $decoded = $transact->decode_login_token($token);

                // [account_id]
                // [first_name]
                // [last_name]
                // [email1]
                if ($decoded->account_id !== $xsact_user_id) {
                    return new WP_Error(500, 'invalid token', array(
                        'status' => 'ERROR'
                    ));
                }
            } else {
                $decoded = $transact->decode_token($token);
            }

            $username = 'xsact_' . $xsact_user_id;

            $wp_userid = $accountMeta->get_or_create_auto_user($username, $user_email, $user_display_name);

            $accountMeta->connect_xsact_id_meta($wp_userid, $xsact_user_id);

            // If we are only checking premium, don't log the user in if they don't have premium
            // because that causes subsequent REST calls to fail. The javascript will refresh the user's browser if they have premium,
            // correctly logging the user in.
    
            $premium = $transact->is_premium($wp_userid);
            if (!$check_premium_only || $premium) {
                $cur_user = wp_get_current_user();
                if(is_wp_error($wp_userid)) {
                    return $wp_userid;
                } else {
                    if($cur_user->ID !== $wp_userid) {
                        add_filter ( 'auth_cookie_expiration',  array( $this, 'get_cookie_expire_callback' ));
    
                        wp_clear_auth_cookie();
                        wp_set_current_user ( $wp_userid );
                        wp_set_auth_cookie  ( $wp_userid, true ); // second parameter true remembers user for 14 days.
                    }
                }
            }
    
            if ($check_premium_only) {
                $response = array(
                    'is_premium' => $premium
                );
                return new WP_REST_Response( $response, 200 );
            }

            $response = $this->update_premium_state($accountMeta, $post_id, $decoded, $wp_userid);

            if(!$response) {
                return new WP_Error(400, 'Bad Token', array(
                    'status' => 'ERROR'
                ));
            }

            return new WP_REST_Response( $response, 200 );
        } catch (Exception $e) {
            return new WP_Error(500, $e->getMessage(), array(
                'status' => 'ERROR'
            ));
        }
    }

    private function update_premium_state(AccountMetaManager $accountMeta, int $post_id, $decoded, int $wp_userid) {
        /**
         * If the transaction is valid, create or find the wp user account based on the transact account,
         * then log the user in.
         */

        $subscription = 0;

        /**
         * If it is a subscription, create a subscription record
         */
        $subscription_success = false;
        if (isset($decoded->sub) && ($decoded->sub)) {
            $tableModel = new transactSubscriptionTransactionsModel();
            $tableModel->create_subscription($decoded->sub_expires, $decoded->uid, $decoded->iat);
            $subscription = 1;

            // Record the subscription in the user account meta
            $subscription_success = $accountMeta->add_subscription($post_id, $decoded->sub_expires, $decoded->uid, $decoded->iat * 1000, $wp_userid);
        /**
         * If it is an ala carte purchase, create a transaction record
         */
        }

        if (!$subscription_success && isset($post_id)) {
            $tableModel = new transactTransactionsModel();
            $success = $tableModel->validate_transaction($post_id, $decoded->uid, $decoded->iat);

            if(!$success) {
                return null;
            }

            $accountMeta->add_purchase($post_id, $decoded->uid, $decoded->iat * 1000, $wp_userid);
        }
        $response = array(
            'decoded' => $decoded,
            'subscription' => $subscription
        );

        return $response;
    }

    public function get_premium_content_rest( WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'post_id' );
        $this->rest_content_context = true;

        if (empty($post_id)) {
            return new WP_Error(400, 'Error: Missing post ID', array(
                'content' => 'Failed validation. missing post ID',
                'status' => 'ERROR',
            ));
        }

        $transact = new TransactApi($post_id);
        try {
            $premium = $transact->is_premium();

            $post_object = get_post($post_id);
            $has_premium = $this->post_has_premium($transact, $post_object);

            $result = array(
                'is_premium' => $premium,
                'has_premium' => $has_premium
            );
            if($premium) {
                $button_controller = new transactHandleButtons($post_id, $transact);

                global $post;
                // Must set the global $post object here as well because some plugins rely on it to add data in their the_content filters
                // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                $post = $post_object;
                $result_content = '';

                // Prioritize the premium content from metadata so older content won't change on its own
                if (isset($premium_from_meta) && $premium_from_meta !== '') {
                    $content = $premium_from_meta;
                    $content = apply_filters('the_content', $content);
                    $result_content = apply_filters('wpautop', $content);
                // If the premium content metadata is empty, then assume the premium content is embedded in blocks
                } elseif($post_object) {
                    $content = '';

                    if(!has_blocks($post_object)) {
                        $content = $post_object->post_content;
                        $content = apply_filters('the_content', $content);
                        $result_content = apply_filters('wpautop', $content);
                    } else {
                        $raw = apply_filters('the_content', $post_object->post_content);
                        $blocks = parse_blocks( $raw );

                        foreach ( $blocks as $block ) {
                            // Dont include preview content in premium output
                            if($block['blockName'] !== 'transact/preview-content') {
                                $content .= render_block( $block );
                            }
                        }
                        $result_content = $content;
                        // No wpautop on block parser
                    }

                }

                if($button_controller->get_if_article_donation()) {
                    $result_content = $result_content . $button_controller->print_donation_button();
                }

                $result['content'] = $result_content;
            }

            return new WP_REST_Response( $result, 200 );
        } catch (Exception $e) {
            return new WP_Error(500, 'Failed to check premium', array(
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ));
        }
        $this->rest_content_context = false;
    }

    public function get_comment_template_rest( WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'post_id' );

        if (empty($post_id)) {
            return new WP_Error(400, 'Error: Missing post ID', array(
                'content' => 'Failed validation. missing post ID',
                'status' => 'ERROR',
            ));
        }

        $transact = new TransactApi($post_id);
        try {
            $premium = $transact->is_premium();

            if($premium) {
                $result = array();
                if(!self::INLINE_COMMENTS_OUTPUT) {
                    remove_filter( 'comments_template', array($this, 'comments_slot') );
                }
                ob_start();

                // These globals are absolutely necessary as there is no post context inside an ajax call
                global $post, $id, $withcomments;
                // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                $withcomments = 1;
                // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                $post = get_post($post_id);
                // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                $id = $post->ID;
                setup_postdata($post);
                comments_template( '', false );
                $result['content'] = ob_get_clean();
                if(!self::INLINE_COMMENTS_OUTPUT) {
                    add_filter( 'comments_template', array($this, 'comments_slot') );
                }
                return new WP_REST_Response( $result, 200 );
            } else {
                return new WP_Error(402, 'Payment required');
            }
        } catch (Exception $e) {
            return new WP_Error(500, 'Failed to get comments', array(
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ));
        }
    }

    public function register_purchase() {
        if (!$this->check_scope()) {
            return;
        }

        // If the purchase redirect parameters are in the URL, we have to register the purchase to the user account.
        // We can't do this in the WP Init action hook because the post ID is not available yet.
        // However, auto-logging in the user has to be done in the Init because we need to send back the auth in the Headers.
        if(array_key_exists('status', $_GET) && $_GET['status'] === 'purchaseComplete' && array_key_exists('t', $_GET)) {
            $transact = new TransactApi($this->post_id);

            try {
                $user_info_str = (string) array_key_exists('userInfo', $_GET) ? $_GET['userInfo'] : array();
                $user_info = json_decode(urldecode($user_info_str), false);
            } catch(Exception $e) {
                return new WP_Error(500, 'userInfo parameter missing from transact response', array(
                    'status' => 'ERROR'
                ));
                return;
            }
            $token = $_GET['t'];

            try {
                $token = $_GET['t'];
                $decoded = $transact->decode_token($token);
                // var_dump($decoded);
                $xsact_user_id = $decoded->bid;
                if(!$xsact_user_id || $xsact_user_id !== $user_info->xsactId) {
                    return;
                }
                $user_email = $user_info->email;
                $username = 'xsact_' . $xsact_user_id;
                $user_display_name = $user_info->firstName . ' ' . $user_info->lastName;
                
                $accountMeta = new AccountMetaManager();
                $wp_userid = $accountMeta->get_or_create_auto_user($username, $user_email, $user_display_name);
                $accountMeta->connect_xsact_id_meta($wp_userid, $xsact_user_id);
        
                $cur_user = wp_get_current_user();
                if(is_wp_error($wp_userid)) {
                    return $wp_userid;
                } else {
                    if($cur_user->ID !== $wp_userid) {
                        add_filter ( 'auth_cookie_expiration',  array( $this, 'get_cookie_expire_callback' ));
    
                        wp_clear_auth_cookie();
                        wp_set_current_user ( $wp_userid );
                        wp_set_auth_cookie  ( $wp_userid, true ); // second parameter true remembers user for 14 days.
                    }
                }

                $premium = $transact->is_premium($wp_userid);

                if(!$premium) {
                    $this->update_premium_state($accountMeta, $this->post_id, $decoded, $wp_userid);
                }
            } catch (Exception $e) {
                return new WP_Error(500, $e->getMessage(), array(
                    'status' => 'ERROR'
                ));
            }

            if(isset($_SERVER['REQUEST_URI'])) {
                $current_url = home_url($_SERVER['REQUEST_URI']);
            } else {
                $current_url = home_url('');
            }
            
            // Now that we've recorded the purchase, remove the query parameters from the URL and redirect
            // The redirect is required to reload the purchase state, otherwise the premium content won't show right away
            $redirect = preg_replace('/((\?)|&)(status|t|userInfo)=[^&]*/m', '\2', $current_url);
            wp_redirect($redirect);
            exit;
        }
    }

    /**
     * All hooks to single_post template
     */
    public function hookSinglePost()
    {

        $this->config = new ConfigParser();

        add_filter( 'the_content', array($this, 'filter_pre_get_content' ), $this->get_content_priority);
        add_filter( 'render_block', array($this, 'filter_pre_render_block' ), $this->get_content_priority, 2);

        // NOTE try to keep these in order of action hooks
        // https://codex.wordpress.org/Plugin_API/Action_Reference
        add_action( 'template_redirect', array($this, 'register_purchase' ));
        add_action( 'wp_enqueue_scripts', array($this, 'load_js_xsact_library'));
        add_action( 'wp_enqueue_scripts', array($this, 'load_css_xsact_library'));

        add_action( 'wp_body_open', array($this, 'add_gtm_body_include') );
        add_action( 'wp_head', array($this, 'add_gtm_head_include') );
        add_action( 'wp_head', array($this, 'add_paywall_meta') );
        add_action( 'wp_head', array($this, 'add_wp_nonce') );


        add_action( 'template_redirect', array($this, 'oauth_login_validate') );

        wp_oembed_add_provider(
            $this->config->getPostdPostRegex(),
            $this->config->getPostdUrl() . 'services/oembed?url=',
            true
        );

        $transact = new TransactApi($this->post_id);

        /**
         * If this page is being accessed by a search crawler, create a user to load the premium content so that the premium content is indexed.
         */
        if ($transact->get_search_engine_access == 'seo_full_access'
            && $transact->is_search_engine()
        ) {

            add_action( 'after_setup_theme', array($this, 'init_search_crawler') );

        }

        if(!self::INLINE_COMMENTS_OUTPUT) {
            /**
             * Making sure comments are closed by default; open them with javascript
             */
            add_filter( 'comments_template', array($this, 'comments_slot') );
        } else {
            add_filter( 'comments_open', array($this, 'comments_open'), 10, 2);
        }

        if ( version_compare( $GLOBALS['wp_version'], '5.8-alpha-1', '<' ) ) {
            // WP version 5.8 depricates block_categories in favor of block_categories_all
            add_filter( 'block_categories', array($this, 'transact_block_categories'), 10, 2 );
        } else {
            add_filter( 'block_categories_all', array($this, 'transact_block_categories'), 10, 2 );
        }
    }

    public function add_gtm_head_include() {
        $options = get_option('transact-settings');

        if(!empty($options['googletagmanager_id'])) {
            echo "<!-- Google Tag Manager -->
                <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer','" . esc_textarea(trim($options['googletagmanager_id'])) . "');</script>
                <!-- End Google Tag Manager -->";
        }
    }
    public function add_paywall_meta() {

        $post_object = get_post($this->post_id);

        if (!has_block('transact/premium-content', $post_object)) {
            // no transact paid content.
            return;
        }

        $logo = get_theme_mod( 'custom_logo' );
        $image = wp_get_attachment_image_src( $logo , 'full' );
        if (empty($image) || empty($image[0])) {
            $image_url = 'https://transact.io/assets/images/transact_logo-stacked.png';
        } else {
            $image_url = $image[0];
            //$image_width = $image[1];
            //$image_height = $image[2];
        }


        $gmt = true; // UTC GMT time.
        $date_published = get_post_time('c', $gmt, $post_object);
        $date_modified = get_post_modified_time('c', $gmt, $post_object);

        $author_name = get_the_author_meta('nickname', $post_object->post_author);


        echo '<script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "CreativeWork",
            "mainEntityOfPage": {
              "@type": "WebPage",
              "@id": "'. esc_url(get_post_permalink($post_object, true)) .'"
            },
            "headline": "'.  esc_textarea($post_object->post_title) .'",
            "datePublished": "'. esc_textarea($date_published) .'",
            "dateModified": "' . esc_textarea($date_modified)  .'",
            "author": {
              "@type": "Person",
              "name": "'. esc_textarea($author_name) .'"
            },
            "publisher": {
               "name": "'.  esc_textarea(get_bloginfo('name')) .'",
               "@type": "Organization",
               "logo": {
                  "@type": "ImageObject",
                  "url": "'. esc_url($image_url) .'"
               }
            },
            "description": "'. esc_textarea(trim($post_object->post_excerpt)) .'",
            "isAccessibleForFree": "False",
            "hasPart":
              {
              "@type": "WebPageElement",
              "isAccessibleForFree": "False",
              "cssSelector" : ".wp-block-transact-premium-content"
              }
          }
      ';
    }


    public function add_gtm_body_include() {
        echo "<script>window.WP_REST_NONCE = '" . esc_html(wp_create_nonce( 'wp_rest' )) . "';</script>";

    }
    public function add_wp_nonce() {
        echo "<script>window.WP_REST_NONCE = '" . esc_html(wp_create_nonce( 'wp_rest' )) . "';</script>";
    }

    public function get_cookie_expire_callback($length) {
        // 15 days
        return 60 * 60 * 24 * 15;
    }

    public function init_search_crawler() {


        $accountMeta = new AccountMetaManager();
        $username = 'xsact_crawler_bot';

        $wp_userid = $accountMeta->get_or_create_auto_user($username, '', 'Crawler Bot');
        add_filter ( 'auth_cookie_expiration',  array( $this, 'get_cookie_expire_callback' ));

        wp_clear_auth_cookie();
        wp_set_current_user ( $wp_userid );
        wp_set_auth_cookie  ( $wp_userid, true ); // second parameter true remembers user for 14 days.
    }


    public function transact_block_categories( $categories, $post ) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug' => 'transact-blocks',
                    'title' => __( 'Transact Blocks', 'transact-blocks' ),
                ),
            )
        );
    }

    /**
     * Hooks into any post query, to remove any premium blocks so we don't get errant premium blocks anywhere.
     *
     * @param string $block_content the string content of the block
     * @param array $block the block info, we only care about blockName
     * @return string content of the block
     */
    public function filter_pre_render_block(string $block_content, array $block) {
        // Strip premium content when not in a REST call
        if($this->rest_content_context) {
            return $block_content;
        }

        // Strip premium blocks when user is not a premium purchaser.
        $transact = new TransactApi($this->post_id);
        $premium = $transact->is_premium();
        if(!$premium) {
            if($block['blockName'] === 'transact/premium-content') {
                return '';
            }
        }
        return $block_content;
    }

    /**
     * Hooks into content, if the user is premium for that content
     * it will show the premium content for it, otherwise the normal one adding the button to buy on transact.io.
     *
     * @param string $content
     * @return string
     */
    public function filter_pre_get_content($content)
    {
        $button_settings = intval(get_post_meta( $this->post_id, 'transact_display_button' , true ));
        /**
         * If it is not the scope, we return the normal content (could be used in a archive for instance)
         * Also if the transact button is disabled, don't do anything.
         */
        if (!$this->check_scope() || !is_main_query() || $button_settings === self::DISABLE) {
            return $content;
        }
        $gtm_data_layer = '';
        $transact = new TransactApi($this->post_id);

        $premium = $transact->is_premium();
        $post_object = get_post($this->post_id);
        $has_premium = $this->post_has_premium($transact, $post_object);

        $button_controller = new transactHandleButtons($this->post_id, $transact);
        if($has_premium || $button_controller->get_if_article_donation()) {
            if(!$premium) {

                if (!$button_controller->has_transact_button($content)) {
                    $options = get_option('transact-settings');
                    $count_words = false;
                    if (isset($options['show_count_words']) && $options['show_count_words']) {
                        $count_words = str_word_count(wp_strip_all_tags($content));
                    }

                    $content = $content . $button_controller->print_purchase_buttons($count_words);
                }

                $gtm_data_layer = "window.dataLayer.push({'postPremiumOptions': " . json_encode($button_controller->get_purchase_options()) . "})";
            } else {
                // If the post has old premium content, append it
                $premium_from_meta = $transact->get_premium_content();
                if (isset($premium_from_meta) && $premium_from_meta !== '') {
                    $content = $premium_from_meta;
                }

                $gtm_data_layer = "window.dataLayer.push({'postPremiumOptions': ['paid']})";
            }

            $content = '<div id="transact_content">' . $content . '</div>' . self::NO_TRANSACT_WARNING . self::NO_COOKIES_WARNING;
        } else {
            $gtm_data_layer = "window.dataLayer.push({'postPremiumOptions': ['free']})";
        }
        $gtm_data_layer = "<script>if (window.dataLayer !== undefined) {\n" . $gtm_data_layer . "\n}</script>\n";

        return $gtm_data_layer . $content;
    }

    /**
     * Loading Transact JS Library
     */
    public function load_js_xsact_library()
    {
        if (!$this->check_scope()) {
            return;
        }
        $options = get_option( 'transact-settings' );

        $redirect_page = '';
        $donation = 0;
        if ($this->check_if_post_is_under_donation($this->post_id)) {
            $redirect_page = $this->check_redirect_after_donation($this->post_id);
            $donation = 1;
        }

        $params = array(
            'transact_path' => $this->config->getJSLibrary(),
            'ajaxurl' => '/wp-json/transact/v1/',
            'post_id' => $this->post_id,
            'affiliate_id' => $this->get_affiliate(),
            'donation' => $donation,
            'price' => get_post_meta($this->post_id, 'transact_price', true ),
            'theme' => [],
            'purchase_url' => $this->config->getPurchaseUrl()
        );

        if(isset($options['background_color'])) {
            $params['theme']['background_color'] = $options['background_color'];
        }
        if(isset($options['text_color'])) {
            $params['theme']['text_color'] = $options['text_color'];
        }
        if (strlen($redirect_page) > 0) {
            $params['redirect_after_donation'] = $redirect_page;
        }

        /**
         * Loading transact scripts (callbacks)
         */
        wp_register_script( 'transact_callback',  FRONTEND_ASSETS_URL . 'transact_post.js', array('jquery'), TRANSACT_VERSION, true );
        wp_localize_script( 'transact_callback', 'transact_params', $params );
        wp_enqueue_script( 'transact_callback' );
    }

    /**
     * Get Affiliated reference from url if exists
     *
     * @return int|string
     */
    function get_affiliate()
    {
        if (!$affiliate = filter_input(INPUT_GET, "aff", FILTER_VALIDATE_INT)) {
            $affiliate = '';
        }
        return $affiliate;
    }

    /**
     * Loading Transact css Library
     */
    public function load_css_xsact_library()
    {
        if (!$this->check_scope()) {
            return;
        }

        /**
         * Loading external library (JS API)
         */
        wp_enqueue_style('transact-blocks', FRONTEND_ASSETS_URL . 'transact-blocks.style.css');
        wp_enqueue_style('transact', FRONTEND_ASSETS_URL . 'style.css');
    }

    /**
     * Checks if user has set $post_id as donation post
     *
     * @param $post_id
     * @return bool
     */
    public function check_if_post_is_under_donation($post_id)
    {
        $options = get_option( 'transact-settings' );
        if (isset($options['donations']) && $options['donations']) {
            if (get_post_meta( $post_id, 'transact_donations' , true )) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has set a redirect after donation.
     *
     * @param $post_id
     * @return string empty if not url
     */
    public function check_redirect_after_donation($post_id) {
        $redirect_url = '';
        $redirect_id = get_post_meta( $post_id, 'transact_redirect_after_donation' , true );
        if (is_numeric($redirect_id) && $redirect_id > 0) {
            $redirect_url = get_page_link($redirect_id);
        }
        return $redirect_url;
    }

    /**
     * First we check if the settings have been set on the Dashboard,
     * after:
     * We want the previous filters to work only on the proper scope
     * and that is single posts (singe_post templates) or CPT enabled or pages
     *
     * @return bool
     */
    public function check_scope()
    {
        /**
         * Setting the post id, is the only scope where I can get it.
         */
        $this->post_id = get_the_ID();
        $options = get_option('transact_validation');

        if (isset($options['account_valid']) && $options['account_valid'] &&
            get_post_meta( $this->post_id, 'transact_item_code', true ) &&
            ((is_single() && (get_post_type() === 'post')
                || (in_array(get_post_type(), SettingsCpt::get_cpts_enable_for_transact(), true))) ||
                get_post_type() === 'page'))
        {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Hook for comments_template
     * It injects our own (empty) comments anchor so we can inject the real comments html via ajax
     *
     * @return php_template
     */
    function comments_slot()
    {
        return dirname(__FILE__) . '/comments_slot.php';
    }

    /**
     * Hook for comments_open
     * If the post has premium content and the user is not premium, cannot post comments.
     *
     * @return bool
     */
    function comments_open($post_setting, $post_id) {
        $transact = new TransactApi($post_id);
        $premium = $transact->is_premium();

        $post_object = get_post($post_id);
        $has_premium = $this->post_has_premium($transact, $post_object);

        if (!$premium && $has_premium) {
            return false;
        } else {
            return $post_setting;
        }
    }

    /**
     * todo: not used yet, future development
     * Get Premium Comment setting for the post
     *
     * @return bool
     */
    function premium_comments_settings()
    {
        $premium_comments = get_post_meta( $this->post_id, 'transact_premium_comments', true );
        return ($premium_comments) ? true : false;
    }

    function post_has_premium(TransactApi $transact, WP_Post $post_object) {
        $premium_from_meta = $transact->get_premium_content();
        $button_settings = intval(get_post_meta( $post_object->ID, 'transact_display_button' , true ));

        if ($button_settings === self::DISABLE) {
            return false;
        }

        // QUESTION(karl)  Could this just be?
        // return has_block('transact/premium-content', $post_object);

        return (isset($premium_from_meta) && $premium_from_meta !== '') ||
            ($post_object && strpos($post_object->post_content, 'transact/premium-content') > -1);

    }

}

