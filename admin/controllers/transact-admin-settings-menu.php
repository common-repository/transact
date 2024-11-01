<?php
namespace Transact\Admin\Settings\Menu;

use Transact\Utils\Config\Parser\ConfigParser;
require_once  plugin_dir_path(__FILE__) . '/../../utils/transact-utils-config-parser.php';

use Transact\Admin\Api\TransactApi;
require_once  plugin_dir_path(__FILE__) . '/transact-api.php';

use Transact\Utils\Settings\CurrencyUtils;
require_once  plugin_dir_path(__FILE__) . '../../utils/transact-currency-utils.php';


/**
 * Class AdminSettingsMenuExtension
 */
class AdminSettingsMenuExtension
{
    /**
     * All hooks to dashboard
     */
    public function hookToDashboard()
    {
        add_action( 'admin_menu', array( $this, 'add_transact_menu' ));
        add_action( 'admin_init', array( $this, 'register_transact_settings' ));
        add_action( 'admin_init', array( $this, 'hook_post_settings_and_validates'));

        // init the account valid transient
        $options = get_option('transact_validation');

        if (isset($options['account_valid'])) {
            set_transient( SETTING_VALIDATION_TRANSIENT, $options['account_valid'], 0);
        }
        if (isset($options['subscription_settings_valid'])) {
            set_transient( SETTING_VALIDATION_SUBSCRIPTION_TRANSIENT, $options['subscription_settings_valid'], 0);
        }
    }

    /**
     * Creates Transact.io Menu on Dashboard
     */
    public function add_transact_menu()
    {
        add_menu_page( 'transact.io', 'Transact.io', 'manage_options', 'transact-admin-page.php', array($this, 'transact_io_admin_callback'), 'dashicons-cart' );
    }

    /**
     * Callback for the Transact.io menu (one generating the thml output)
     */
    public function transact_io_admin_callback()
    {
        ob_start();
        require_once plugin_dir_path(__FILE__) . '/../views/transact-account-view.php';
        ob_end_flush();
    }

    /**
     * Registering Settings on transact account settings
     */
    public function register_transact_settings()
    {
        // API Transact Settings
        register_setting(
            'transact-settings',
            'transact-settings'
        );


        // API Transact Settings
        add_settings_section(
            'api_keys',
            __( 'API keys', 'transact' ),
            // safely ignore the phpcs check because it is a constant value
            // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
            function() { _e('Log in into a publisher <a href="https://transact.io/signup?which=publisher" target="_blank"> transact.io</a> account to find your credentials.','transact'); },
            'transact-settings'
        );

        // Adding Account ID field
        add_settings_field(
            'api_id',
            __( 'Account ID', 'transact' ),
            array($this, 'account_id_settings_callback'),
            'transact-settings',
            'api_keys',
            array('account_id')
        );

        // Adding Secret key field
        add_settings_field(
            'secret_key',
            __( 'Secret Key', 'transact' ),
            array($this, 'account_id_settings_callback'),
            'transact-settings',
            'api_keys',
            array('secret_key')
        );

        /*
         * Subscriptions Manager
         */
        add_settings_section(
            'subscriptions',
            __( 'Manage Subscriptions', 'transact' ),
            function() { esc_html_e('Click on the checkbox to activate subscriptions on your site. They must be activated on transact.io','transact'); },
            'transact-settings'
        );

        add_settings_field(
            'enable_subscriptions',
            __( 'Enable Transact Subscriptions', 'transact' ),
            array($this, 'subscriptions_callback'),
            'transact-settings',
            'subscriptions',
            array('subscriptions')
        );

        /*
         * Button Styles Manager
         */
        add_settings_section(
            'transact_general', // ID
            'General Settings', // Title
            function() { esc_html_e('','transact'); },
            'transact-settings'
        );

        // Adding Currency key field
        add_settings_field(
            'transact_currency',
            'Primary Currency',
            array( $this, 'display_currency_callback' ),
            'transact-settings',
            'transact_general',
            array(
                'id' => 'transact_currency',
                'default_val' => 'PROD') // Default value
        );

        add_settings_field(
            'display_cents_dollars',
            'Price Display Format',
            array( $this, 'display_cents_dollars_callback' ),
            'transact-settings',
            'transact_general',
            array(
                'id' => 'transact_purchase_button_text',
                'default_val' => 'cents') // Default value
        );

        add_settings_field(
            'transact_search_engine_access',
            'Search Engine Access',
            array( $this, 'display_search_engine_callback' ),
            'transact-settings',
            'transact_general',
            array(
                'id' => 'transact_search_engine_access',
                'default_val' => 'seo_no_access') // Default value
        );



        add_settings_field(
            'default_purchase_type',
            'Default Post Purchase Type',
            array( $this, 'default_purchase_type_callback' ),
            'transact-settings',
            'transact_general',
            array(
                'id' => 'transact_default_purchase_type',
                'default_val' => 'subscribe_purchase') // Default value
        );

        /*
         * Post Types Manager
         */
        add_settings_section(
            'post_types',
            __( 'Post Types', 'transact' ),
            function() { esc_html_e('Enable Transact for Custom Post Types. By default Transact is available for posts and pages only.','transact'); },
            'transact-settings'
        );

        add_settings_field(
            'custom_post_types',
            __( 'Custom Post Types', 'transact' ),
            array($this, 'custom_post_types_callback'),
            'transact-settings',
            'post_types',
            array('custom_post_types')
        );

        /*
         * Button Styles Manager
         */
        add_settings_section(
            'xct_button_style', // ID
            'Purchase Button Settings', // Title
            function() { esc_html_e('You can customize the visual appearance of the Purchase on Transact button here.','transact'); },
            'transact-settings'
        );

        add_settings_field(
            'text_color', // ID
            'Text Color', // Title
            array( $this, 'color_input_callback' ), // Callback
            'transact-settings',
            'xct_button_style', // user section,
            array('text_color') // Default value
        );

        add_settings_field(
            'background_color',
            'Button Background Color',
            array( $this, 'color_input_callback' ),
            'transact-settings',
            'xct_button_style',
            array('background_color', '#19a5ae') // Default value
        );

        add_settings_field(
            'text_fade_color',
            'Page Fade Color',
            array( $this, 'color_input_callback' ),
            'transact-settings',
            'xct_button_style',
            array('page_background_color') // Default value
        );

        add_settings_field(
            'text_fade_amount',
            'Preview Text Fade Amount',
            array( $this, 'text_fade_callback' ),
            'transact-settings',
            'xct_button_style',
            array(
                'id' => 'text_fade_amount',
                'default_val' => 'regular') // Default value
        );

        add_settings_field(
            'transact_default_price',
            'Default price in cents',
            array( $this, 'button_text_callback' ),
            'transact-settings',
            'xct_button_style',
            array(
                'id' => 'transact_default_price',
                'default_val' => '2') // Default value
        );

        add_settings_field(
            'transact_call_to_action_text',
            'Call to action header',
            array( $this, 'textarea_callback' ),
            'transact-settings',
            'xct_button_style',
            array(
                'id' => 'transact_call_to_action_text',
                'default_val' => '<p style="text-align: center;">PURCHASE WITH TRANSACT OR SUBSCRIBE TO READ THE FULL STORY</p>') // Default value
        );


        add_settings_field(
            'transact_purchase_button_text',
            'Purchase Button Text',
            array( $this, 'button_text_callback' ),
            'transact-settings',
            'xct_button_style',
            array(
                'id' => 'transact_purchase_button_text',
                'default_val' => 'PURCHASE WITH TRANSACT FOR') // Default value
        );

        add_settings_field(
            'transact_subscribe_button_text',
            'Subscribe Button Text',
            array( $this, 'button_text_callback' ),
            'transact-settings',
            'xct_button_style',
            array(
                'id' => 'transact_subscribe_button_text',
                'default_val' => 'SUBSCRIBE / LOGIN') // Default value
        );

        add_settings_field(
            'show_count_words',
            'Show Words Count',
            array( $this, 'words_count_input_callback' ),
            'transact-settings',
            'xct_button_style',
            array(false) // Default value
        );


        /*
         * Donations Manager
         */
        add_settings_section(
            'donations',
            __( 'Manage Donations', 'transact' ),
            function() { esc_html_e('Click on the checkbox to activate Donations on your site.', 'transact'); },
            'transact-settings'
        );

        add_settings_field(
            'enable_donations',
            __( 'Enable Donations', 'transact' ),
            array($this, 'donations_callback'),
            'transact-settings',
            'donations',
            array('donations')
        );


        // Transact Google Tag Manager Settings
        add_settings_section(
            'tag_manager',
            __( 'Google Tag Manager Integration', 'transact' ),
            // safely ignore the phpcs check because it is a constant value
            // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
            function() { _e('Integrate Transact with Google Tag Manager to see analytics on visitors and conversion rates.','transact'); },
            'transact-settings'
        );

        // GTM Analytics ID field
        add_settings_field(
            'googletagmanager_id',
            __( 'Google Tag Manager ID', 'transact' ),
            array($this, 'gtm_id_input_callback'),
            'transact-settings',
            'tag_manager'
        );

    }

    public function subscriptions_callback($arg) {
        $options = get_option('transact-settings');
        $subscription_options = isset($options['subscription']) ? $options['subscription'] : 0;

        $subscription_selected = ($subscription_options) ? 'checked' : '';
        $checkbox_value = ($subscription_options) ? 1 : 0;

        ?>
            <script>
                // Handles checkbox for subscription
                function setValue(id) {
                    if( jQuery(id).is(':checked')) {
                        jQuery(id).val(1);
                    } else {
                        jQuery(id).val(0);
                    }
                }
            </script>

            <input <?php echo esc_html($subscription_selected); ?>
                id="subscription"
                type="checkbox"
                onclick="setValue(subscription)"
                name="transact-settings[subscription]"
                value="<?php echo esc_attr($checkbox_value); ?>"
            />
        <?php
    }

    public function donations_callback($arg) {
        $options = get_option('transact-settings');
        $donations_options = isset($options['donations']) ? $options['donations'] : 0;

        $donations_selected = ($donations_options) ? 'checked' : '';
        $checkbox_value = ($donations_options) ? 1 : 0;

        ?>
        <script>
            // Handles checkbox for subscription
            function setValue(id) {
                if( jQuery(id).is(':checked')) {
                    jQuery(id).val(1);
                } else {
                    jQuery(id).val(0);
                }
            }
        </script>

        <input <?php echo esc_html($donations_selected); ?>
            id="donations"
            type="checkbox"
            onclick="setValue(donations)"
            name="transact-settings[donations]"
            value="<?php echo esc_attr($checkbox_value); ?>"
            />
        <?php
    }


    /**
     * CPT Settings callback
     * It will show all visible cpt and make the user select the ones they want transact on.
     *
     * @param $arg
     */
    public function custom_post_types_callback($arg)
    {
        $public_post_types = get_post_types(array('public' => true));

        /*
         * Wordpress will include by default post, page, attachment
         * as Transact will have by default post and page, we avoid them
         */
        unset($public_post_types['post']);
        unset($public_post_types['page']);
        unset($public_post_types['attachment']);

        /**
         * if the installation has not custom post type.
         */
        if (empty($public_post_types)) {
            ?>
                <div><i>Your site does not use custom post types.</i></div>
            <?php
        } else {
            $options = get_option('transact-settings');
            $cpt_options = isset($options['cpt']) ? $options['cpt'] : array();
            ?>
            <table>
                <tr>
                    <?php
                        // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
                        foreach ($public_post_types as $key => $cpt): ?>
                        <?php
                        $cpt_selected = '';
                        $checkbox_value = 0;

                        if ($cpt_options) {
                            $cpt_selected = ( (isset($cpt_options['cpt_' . $key])) && ($cpt_options['cpt_' . $key] === 1) ) ? 'checked' : '';
                            $checkbox_value = ( $cpt_selected === 'checked') ? 1 : 0;
                        }
                        ?>
                        <td>
                            <input <?php echo esc_html($cpt_selected); ?>
                                type="checkbox"
                                onclick="setValue(cpt_<?php echo esc_attr($key);?>)"
                                id="cpt_<?php echo esc_attr($key);?>"
                                name="transact-settings[cpt][cpt_<?php echo esc_attr($key);?>]"
                                value="<?php echo esc_attr($checkbox_value); ?>" /><?php echo esc_html($key);?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </table>
            <?php
        }
    }

    /**
     * Individual settings callback to be created
     * @param $arg
     */
    public function account_id_settings_callback($arg)
    {
        $arg = current($arg);
        $options = get_option('transact-settings');

        echo "<input name='transact-settings[". esc_attr($arg)
            ."]' type='text' value='". esc_attr($options[$arg])
            ."' style='width: 300px'/>";

        wp_nonce_field( 'transact-settings', 'transact-settings-nonce' );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function color_input_callback($args)
    {
        $field = $args[0];
        if(empty($args[1])) {
            $default_color = '#ffffff';
        } else {
            $default_color = $args[1];
        }
        $options = get_option('transact-settings');

        printf(
            '<input type="color" id="%s" name="transact-settings[%s]" value="%s" />',
            $field,
            $field,
            isset( $options[$field] ) ? esc_attr( $options[$field]) : esc_attr($default_color)
        );
    }

    public function text_fade_callback() {
        $purchase_type_options = array('regular', 'less', 'disable');
        $option_labels = array(
            'regular' => 'Regular fade amount',
            'less' => 'Less fade',
            'disable' => 'Disable fade');
        $options = get_option('transact-settings');
        $selected_option = isset($options['text_fade_amount']) ? $options['text_fade_amount'] : 'regular';
        ?>
        <script>
            // Sets the real setting field
            function setTextFadeValue(val) {
                jQuery('#text_fade_amount').val(val);
            }
        </script>
        <input
            id="text_fade_amount"
            type="hidden"
            name="transact-settings[text_fade_amount]"
            value="<?php echo esc_attr($selected_option); ?>"
            />

        <?php
        for($i = 0; $i < count($purchase_type_options); $i++) {
            $i_option = $purchase_type_options[$i];
            $field_id = "text_fade_amount_" . $i_option;
            $is_checked = $i_option === $selected_option ? 'checked' : '';

            ?>
                <input <?php echo esc_attr($is_checked); ?>
                    id="<?php echo esc_html($field_id); ?>"
                    type="radio"
                    onclick="setTextFadeValue('<?php echo esc_attr($i_option); ?>')"
                    name="uf-text_fade_amount"
                    value="<?php echo esc_attr($i_option); ?>"
                />
                <label for="<?php echo esc_html($field_id); ?>"><?php echo esc_html($option_labels[$i_option]); ?></label>
                <br />
            <?php
        }
    }

    public function button_text_callback($args) {

        $field = $args['id'];
        $default_text = $args['default_val'];
        $options = get_option('transact-settings');

        printf(
            '<input type="text" id="%s" name="transact-settings[%s]" value="%s" style="width: 500px" />',
            $field,
            $field,
            isset( $options[$field] ) ? esc_attr( $options[$field]) : esc_attr($default_text)
        );
    }

    public function textarea_callback($args) {
        $field = $args['id'];
        $default_text = $args['default_val'];
        $options = get_option('transact-settings');
        $editor_options = array(
            'textarea_name' => "transact-settings[" . $field . "]"
        );

        wp_editor( isset( $options[$field] ) ? wp_kses_post($options[$field]) : wp_kses_post($default_text), $field, $editor_options);
    }

    public function words_count_input_callback($args)
    {
        $options = get_option('transact-settings');
        $words_count_options = isset($options['show_count_words']) ? $options['show_count_words'] : 0;

        $words_count_selected = ($words_count_options) ? 'checked' : '';
        $checkbox_value = ($words_count_options) ? 1 : 0;

        ?>
        <script>
            // Handles checkbox for subscription
            function setValue(id) {
                if( jQuery(id).is(':checked')) {
                    jQuery(id).val(1);
                } else {
                    jQuery(id).val(0);
                }
            }
        </script>

        <input <?php echo esc_html($words_count_selected); ?>
            id="show_count_words"
            type="checkbox"
            onclick="setValue(show_count_words)"
            name="transact-settings[show_count_words]"
            value="<?php echo esc_attr($checkbox_value); ?>"
            />
        <?php
    }

    public function display_cents_dollars_callback($args)
    {
        $cents_dollars_options = array('cents', 'dollars');
        $option_labels = array('cents' => '123 Cents', 'dollars' => '$1.23');
        $options = get_option('transact-settings');
        $selected_option = isset($options['display_cents_dollars']) ? $options['display_cents_dollars'] : 'cents';
        ?>
        <script>
            // Sets the real setting field
            function setButtonFormatValue(val) {
                jQuery('#display_cents_dollars').val(val);
            }
        </script>
        <input
            id="display_cents_dollars"
            type="hidden"
            name="transact-settings[display_cents_dollars]"
            value="<?php echo esc_attr($selected_option); ?>"
            />

        <?php
        for($i = 0; $i < count($cents_dollars_options); $i++) {
            $i_option = $cents_dollars_options[$i];
            $field_id = "display_cents_dollars_" . $i_option;
            $is_checked = $i_option === $selected_option ? 'checked' : '';

            ?>
                <input <?php echo esc_attr($is_checked); ?>
                    id="<?php echo esc_html($field_id); ?>"
                    type="radio"
                    onclick="setButtonFormatValue('<?php echo esc_attr($i_option); ?>')"
                    name="uf-display_cents_dollars"
                    value="<?php echo esc_attr($i_option); ?>"
                />
                <label for="<?php echo esc_html($field_id); ?>"><?php echo esc_html($option_labels[$i_option]); ?></label>
                <br />
            <?php
        }
    }

    public function display_search_engine_callback($args)
    {
        $seo_options = array('seo_no_access', 'seo_full_access');
        $option_labels = array(
            'seo_no_access' => 'No Access',
            'seo_full_access' => 'Full free access for search engines so they can index them. '
                                .'Bing, DuckDuckGo, Google and Yandex are supported');
        $options = get_option('transact-settings');
        $selected_option =  'seo_no_access'; // default
        if (!empty($options['transact_search_engine_access'])) {
            $selected_option = $options['transact_search_engine_access'];
        }
        ?>
        <script>
            // Sets the real setting field
            function setButtonSearchEnginesValue(val) {
                jQuery('#transact_search_engine_access').val(val);
            }
        </script>
        <input
            id="transact_search_engine_access"
            type="hidden"
            name="transact-settings[transact_search_engine_access]"
            value="<?php echo esc_attr($selected_option); ?>"
            />

        <?php
        for($i = 0; $i < count($seo_options); $i++) {
            $i_option = $seo_options[$i];
            $field_id = "search_engine_access_" . $i_option;
            $is_checked = $i_option === $selected_option ? 'checked' : '';

            ?>
                <input <?php echo esc_attr($is_checked); ?>
                    id="<?php echo esc_html($field_id); ?>"
                    type="radio"
                    onclick="setButtonSearchEnginesValue('<?php echo esc_attr($i_option); ?>')"
                    name="uf-display_seo_options"
                    value="<?php echo esc_attr($i_option); ?>"
                />
                <label for="<?php echo esc_html($field_id); ?>"><?php echo esc_html($option_labels[$i_option]); ?></label>
                <br />
            <?php
        }
    }

    public function display_currency_callback($args)
    {
        $currency_options = array('PROD', 'GBP', 'EUR', 'JPY', 'CAD');
        $option_labels = array(
            'PROD' => 'USD',
            'GBP' => 'GBP',
            'EUR' => 'EUR',
            'JPY' => 'JPY',
            'CAD' => 'CAD'
        );
        $currency_utils = new CurrencyUtils();
        $selected_option = $currency_utils->get_currency_from_options();
        ?>
        <script>
            // Sets the real setting field
            function setCurrencyValue(val) {
                jQuery('#transact_currency').val(val);
            }
        </script>
        <input
            id="transact_currency"
            type="hidden"
            name="transact-settings[transact_currency]"
            value="<?php echo esc_attr($selected_option); ?>"
            />

        <?php
        for($i = 0; $i < count($currency_options); $i++) {
            $i_option = $currency_options[$i];
            $field_id = "transact_currency_" . $i_option;
            $is_checked = $i_option === $selected_option ? 'checked' : '';

            ?>
                <input <?php echo esc_attr($is_checked); ?>
                    id="<?php echo esc_html($field_id); ?>"
                    type="radio"
                    onclick="setCurrencyValue('<?php echo esc_attr($i_option); ?>')"
                    name="uf-transact_currency"
                    value="<?php echo esc_attr($i_option); ?>"
                />
                <label for="<?php echo esc_html($field_id); ?>"><?php echo esc_html($option_labels[$i_option]); ?></label>
                <br />
            <?php
        }
    }

    public function default_purchase_type_callback($args)
    {
        $purchase_type_options = array('subscribe_purchase', 'purchase_only', 'subscribe_only', 'disable_transact');
        $option_labels = array(
            'subscribe_purchase' => 'Display Purchase and Subscribe Button',
            'purchase_only' => 'Display Only Purchase Button',
            'subscribe_only' => 'Display Only Subscribe Button',
            'disable_transact' => 'Disable Transact by Default');
        $options = get_option('transact-settings');
        $selected_option = isset($options['default_purchase_type']) ? $options['default_purchase_type'] : 'subscribe_purchase';
        ?>
        <script>
            // Sets the real setting field
            function setPurchaseTypeValue(val) {
                jQuery('#default_purchase_type').val(val);
            }
        </script>
        <input
            id="default_purchase_type"
            type="hidden"
            name="transact-settings[default_purchase_type]"
            value="<?php echo esc_attr($selected_option); ?>"
            />

        <?php
        for($i = 0; $i < count($purchase_type_options); $i++) {
            $i_option = $purchase_type_options[$i];
            $field_id = "default_purchase_type_" . $i_option;
            $is_checked = $i_option === $selected_option ? 'checked' : '';

            ?>
                <input <?php echo esc_attr($is_checked); ?>
                    id="<?php echo esc_html($field_id); ?>"
                    type="radio"
                    onclick="setPurchaseTypeValue('<?php echo esc_attr($i_option); ?>')"
                    name="uf-default_purchase_type"
                    value="<?php echo esc_attr($i_option); ?>"
                />
                <label for="<?php echo esc_html($field_id); ?>"><?php echo esc_html($option_labels[$i_option]); ?></label>
                <br />
            <?php
        }
    }

    /**
     * Individual settings callback to be created
     * @param $arg
     */
    public function gtm_id_input_callback()
    {
        $options = get_option('transact-settings');
        $current_gtm_id = isset($options['googletagmanager_id']) ? $options['googletagmanager_id'] : '';

        echo "<input placeholder=\"GTM-1ABCD23\" name='transact-settings[googletagmanager_id]' type='text' value='"
            .esc_attr($current_gtm_id)
            ."' style='width: 300px'/>";
    }

    /**
     * Gets Account ID from Settings
     *
     * @return string
     */
    public function get_account_id()
    {
        $options = get_option('transact-settings');
        return $options['account_id'];
    }

    /**
     * Gets Secret from Settings
     * @return string
     */
    public function get_secret()
    {
        $options = get_option('transact-settings');
        return $options['secret_key'];
    }

    /**
     * Gets css settings from Settings
     * @return string
     */
    public function get_button_style()
    {
        $options = get_option('transact-settings');
        return array(
            'text_color' => (isset($options['text_color']) ? $options['text_color'] : ''),
            'background_color' => (isset($options['background_color']) ? $options['background_color'] : ''),
        );
    }

     /**
     * Gets Search Engine Acces from Settings
     * @return string
     */
    public function get_search_engine_access()
    {
        $options = get_option('transact-settings');
        if (!empty($options['transact_search_engine_access'])) {
            return $options['transact_search_engine_access'];
        }
        return 'seo_no_access'; // default if not set
    }

    /**
     * Hook on Settings page when POST
     * We check if the credentials are good, in that case we set a flag to know it in the future
     * In case they are wrong, we set the flag to false and show a message to the publisher
     *
     * We check if subscription is activated on transact.io
     * If is not, we unset from our options and tell to the user.
     *
     */
    public function hook_post_settings_and_validates()
    {


        if (isset($_POST['option_page']) && isset($_POST['transact-settings'])
            && isset($_POST['transact-settings-nonce'])
            && wp_verify_nonce(sanitize_text_field($_POST['transact-settings-nonce']), 'transact-settings')
            && isset($_POST['transact-settings']['account_id'])
            && isset($_POST['transact-settings']['secret_key'])
            && ($_POST['option_page'] === 'transact-settings'))  {

            $account_id = filter_var(sanitize_text_field(
                $_POST['transact-settings']['account_id']),
                FILTER_SANITIZE_NUMBER_INT);
            $secret_key = sanitize_text_field($_POST['transact-settings']['secret_key']);

            if ($this->settings_user_validates($account_id, $secret_key)) {
                $subscription = 0; // init var
                if (isset($_POST['transact-settings']['subscription'])) {

                    $subscription = filter_var(sanitize_text_field(
                        $_POST['transact-settings']['subscription']),
                        FILTER_SANITIZE_NUMBER_INT);
                }

                $this->settings_subscription_validates($account_id, $subscription);

            }

        }
    }

    /**
     * Authenticate publisher against transact and set transient depends on result
     *
     * @param $post_options
     * @return bool
     */
    protected function settings_user_validates($account_id, $secret_key)
    {
        $validate_url = (new ConfigParser())->getValidationUrl();
        $response = (new TransactApi())->validates($validate_url, $account_id, $secret_key);
        if ($response) {
            set_transient( SETTING_VALIDATION_TRANSIENT, 1, 0);
            $this->set_transact_account_valid(1);
        } else {
            set_transient( SETTING_VALIDATION_TRANSIENT, 0, 0);
            $this->set_transact_account_valid(0);
        }
        return $response;
    }

    /**
     * Authenticate publisher subscription against transact, if failure, set subscription to 0 and set transient depends on result
     *
     * @param $post_options
     * @return bool
     */
    protected function settings_subscription_validates($account_id, $subscription)
    {
        if ($subscription)  {
            $validate_url = (new ConfigParser())->getValidationSubscriptionUrl();
            $response = (new TransactApi())->subscriptionValidates($validate_url, $account_id);
            if ($response) {
                set_transient( SETTING_VALIDATION_SUBSCRIPTION_TRANSIENT, 1, 0);
                $this->set_subscription_settings_valid(1);
            } else {
                set_transient( SETTING_VALIDATION_SUBSCRIPTION_TRANSIENT, 0, 0);
                $this->set_subscription_settings_valid(0);
            }
            return $response;
        } else {
            // set default
            set_transient( SETTING_VALIDATION_SUBSCRIPTION_TRANSIENT, 1, 0);
            $this->set_subscription_settings_valid(1);
        }
    }

    protected function set_transact_account_valid($state)
    {
        $options = get_option('transact_validation');
        $options['account_valid'] = $state;

        update_option('transact_validation', $options);
    }

    protected function set_subscription_settings_valid($state) {
        $options = get_option('transact_validation');
        $options['subscription_settings_valid'] = $state;
        update_option('transact_validation', $options);
    }

}

