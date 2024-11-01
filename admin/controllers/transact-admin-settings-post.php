<?php
namespace Transact\Admin\Settings\Post;

use Transact\Utils\Settings\cpt\SettingsCpt;
require_once  plugin_dir_path(__FILE__) . '../../utils/transact-settings-cpt.php';

use Transact\Admin\Settings\Shortcode\transactShortcode;
use Transact\Utils\Config\Parser\ConfigParser;

require_once  plugin_dir_path(__FILE__) . 'transact-shortcode.php';

use Transact\Utils\Settings\CurrencyUtils;
require_once  plugin_dir_path(__FILE__) . '../../utils/transact-currency-utils.php';


use Transact\Models;
require_once  plugin_dir_path(__FILE__) . '../../models/transact-button-types.php';

/**
 * Class AdminSettingsPostExtension
 */
class AdminSettingsPostExtension
{
    /**
     * @var Saves the post_id we are handling
     */
    protected $post_id;

    /**
     * Text for No redirect (donations)
     */
    const NO_REDIRECT_TEXT = 'No Redirect';
    const NO_REDIRECT_VALUE = '0';


    /**
     * All hooks to dashboard
     */
    public function hookToDashboard()
    {
        add_action( 'add_meta_boxes',     array($this, 'add_transact_metadata_post') );
        add_action( 'save_post',          array($this, 'save_meta_box') );
        add_shortcode( 'transact_button', array($this, 'transact_shortcode') );
    }

    /**
     * If this class is initiated outside dashboard, we set post_id
     * to ble able to consult metadata
     *
     * @param int|null $post_id
     */
    public function __construct($post_id = null)
    {
        $config = new ConfigParser();

        if ($post_id) {
            $this->post_id = $post_id;
        }

        add_action('admin_head', array($this, 'set_transact_style' ));
        add_action('enqueue_block_editor_assets', array($this, 'set_transact_premium_block' ));
    }

    /**
     * Including transact.io metabox on post
     */
    public function add_transact_metadata_post()
    {
        $enabled_by_default = array('post', 'page');
        $cpts_to_enable = SettingsCpt::get_cpts_enable_for_transact();
        add_meta_box('transact_metadata', 'transact.io', array($this,'transact_metadata_post_callback'), array_merge($enabled_by_default, $cpts_to_enable), 'advanced');
    }

    /**
     * Hook when saving post, to save/update values
     * @param $post_id
     */
    public function save_meta_box( $post_id )
    {
        /**
         * First, we check if setting have been set in the plugin
         */
        $transact_setting_transient = get_transient(SETTING_VALIDATION_TRANSIENT);
        if (!$transact_setting_transient) {
            return;
        }

        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */
        // Check if our nonce is set.
        if ( ! isset( $_POST['transact_inner_custom_box_nonce'] ) ) {
            return $post_id;
        }

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce(sanitize_text_field($_POST['transact_inner_custom_box_nonce']), 'transact_inner_custom_box' ) ) {
            return $post_id;
        }

        /*
         * If this is an autosave, our form has not been submitted,
         * so we don't want to do anything.
         */
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check the user's permissions.
        if (isset($_POST['post_type']) && 'page' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }

        /**
         * Create transact item code first time post is used
         */
        $transact_item_code = get_post_meta( $post_id, 'transact_item_code' );
        if (empty($transact_item_code)) {
            $transact_item_code = md5($post_id . time());
            update_post_meta( $post_id, 'transact_item_code', $transact_item_code );

        }

        /* OK, it's safe for us to save the data now. */

        // Sanitize the user input.
        if (isset($_POST['transact_price'])) {
            $price = sanitize_text_field( $_POST['transact_price'] );
            // Update the meta field.
            update_post_meta( $post_id, 'transact_price', $price * 100);
        }

        if (isset($_POST['transact_premium_content'])) {
            // ignore phpcs here because we must assume we trust the person publishing the post
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $content = htmlspecialchars( $_POST['transact_premium_content'] );
            update_post_meta( $post_id, 'transact_premium_content', $content );
        }

        if (isset($_POST['transact_display_button'])) {
            $display_button = sanitize_text_field( $_POST['transact_display_button'] );
            update_post_meta( $post_id, 'transact_display_button', $display_button );
        }

        if (isset($_POST['transact_donations'])) {
            update_post_meta( $post_id, 'transact_donations', 1 );
            // Make sure to always display the donation button
            update_post_meta( $post_id, 'transact_display_button', \Transact\Models\TransactPostButtonTypes::PURCHASE_AND_SUBSCRIPTION);
        } else {
            update_post_meta( $post_id, 'transact_donations', 0 );
        }

        if (isset($_POST['transact_redirect_after_donation'])) {
            $redirect_after_donation = sanitize_text_field( $_POST['transact_redirect_after_donation'] );
            update_post_meta( $post_id, 'transact_redirect_after_donation', $redirect_after_donation );
        }


        /**
         *
         *  todo: comments premium future development
        $premium_comments = (isset($_POST['transact_premium_comments'])) ? sanitize_text_field( $_POST['transact_premium_comments'] ) : 0;
        update_post_meta( $post_id, 'transact_premium_comments', $premium_comments );
         */
    }


    /**
     * Creating callback to include transact settings on post
     *
     * @param $post
     */
    public function transact_metadata_post_callback($post)
    {
        /**
         * First, we check if setting have been set in the plugin
         */
        $transact_setting_transient = get_transient(SETTING_VALIDATION_TRANSIENT);
        if (!$transact_setting_transient) {
            esc_html_e('Please, You need to activate your transact settings properly', 'transact');
            return;
        }

        $currency_utils = new CurrencyUtils();

        // Add an nonce field so we can check for it later.
        wp_nonce_field( 'transact_inner_custom_box', 'transact_inner_custom_box_nonce' );

        $value = array();
        // Use get_post_meta to retrieve an existing value from the database.
        $value[1] = $this->get_transact_price($post->ID);
        $value[2] = get_post_meta( $post->ID, 'transact_item_code', true );
        $value[3] = get_post_meta( $post->ID, 'transact_premium_content' , true );
        $value[4] = get_post_meta( $post->ID, 'transact_display_button' , true );
        $value[5] = get_post_meta( $post->ID, 'transact_donations' , true );
        $value[6] = get_post_meta( $post->ID, 'transact_redirect_after_donation' , true );

        /**
         *  todo: comments premium future development
         *
        $value[4] = get_post_meta( $post->ID, 'transact_premium_comments' , true ) ;
        $premium_comment_selected = ($value[4] == 1) ? 'checked' : '';
         **/


        /**
         * Premium Content
         */

        // Need the global current_screen to figure out whether gutenberg is disabled
        $current_screen = get_current_screen();
        if ( method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor() ) {
            // Gutenberg is active
            if(isset($value[3]) && $value[3] !== '') {
                _e('<h3>Legacy Premium Content</h3>', 'transact');
                ?>
                <div>
                    <strong>
                        Legacy premium content found. Clear this legacy content to use the transact premium blocks in the main editor for the premium content.
                    </strong>
                </div>
                <br />
                <?php
                wp_editor( htmlspecialchars_decode($value[3]), 'transact_premium_content');
            }
        } else {
            // Re-enable classic editor with a check if gutenberg is not enabled
            // ignore phpcs here because output is a constant value
            // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
            _e('<h3>Premium Content</h3>', 'transact');
            wp_editor( htmlspecialchars_decode($value[3]), 'transact_premium_content');
        }

        /**
         * Rest of the form price
         */
        ?>

        <?php

        /**
         *  * Piece of JS to manage the checkbox
         *  todo: comments premium future development
         *
        <script>
        // Handles checkbox for premium comments
        jQuery( document ).ready(function() {
        jQuery('#transact_premium_comments').click(function(){
        if( jQuery("#transact_premium_comments").is(':checked')) {
        jQuery("#transact_premium_comments").val(1);
        } else {
        jQuery("#transact_premium_comments").val(0);
        }
        });
        });
        </script>
         **/
        ?>
        <br xmlns="http://www.w3.org/1999/html"/>
        <script>
            function tinymce_content(id) {
                var content;
                var editor = tinyMCE.get(id);
                var textArea = jQuery('textarea#' + id);
                if (textArea.length>0 && textArea.is(':visible')) {
                    content = textArea.val();
                } else {
                    content = editor.getContent();
                }
                return content;
            }
            function check_transact_price(event) {
                console.log("check_transact_price", event);
                let price = document.getElementById('transact_price').value * 100;

                if (Number.isInteger(parseInt(price))) {
                    price = parseInt(price); // will set 0 if input is 0.1
                    console.log('check_transact_price IS valid', price)
                    document.getElementById('transact_price').value = (price / 100).toString();
                } else {
                    alert('price is not valid');
                    console.log('check_transact_price not valid')
                    document.getElementById('transact_price').value = '';
                }
            }
            jQuery('#publish').click(function(event) {
                var content = tinymce_content('transact_premium_content');
                if (content.length > 0) {
                    jQuery("#transact_price").prop('required',true);
                    document.getElementById('price_alert').style.display = 'block';
                } else {
                    jQuery("#transact_price").prop('required',false);
                    document.getElementById('price_alert').style.display = 'none';
                }
            });
        </script>

        <?php
            $is_donation = $value[5] == 1;
            $price_disabled = (
                   $value[4] == \Transact\Models\TransactPostButtonTypes::ONLY_SUBSCRIBE
                || $value[4] == \Transact\Models\TransactPostButtonTypes::DISABLE
                || $value[4] == \Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED
                ) ? 'disabled' : '';
        ?>

        <div class="transact_meta_fields">

            <aside class="help-block">
                <h3>About the Transact blocks</h3>
                <p>Any content outside of Transact blocks will be shown as normal,
                    and will be visible regardless of whether the post is purchased or not.</p>

                <h3>Transact Premium Block</h3>
                <p>Any content within this block only be displayed for users that have purchased the post.
                    The content will also not visible in post list previews.</p>

                <h3>Transact Preview Block</h3>
                <p>Content within this block will only be displayed for users that have <strong>not</strong> purchased the post.
                    The content will be removed when the post is purchased. This is useful for summaries or descriptions of the post
                    that may not make sense with the premium content visible.</p>

                <p>Do not nest Transact blocks within each other, as the outermost block type will take priority.</p>
            </aside>

            <?php
            /**
             * Check if donations is enable by the publisher to show donations options
             */
            $options = get_option('transact-settings');
            $donations_options = isset($options['donations']) ? $options['donations'] : 0;
            if ($donations_options) {
                $donations_selected = (($value[5] == 1) ? 'checked' : '');
                ?>

                <div class="transact_meta_label">
                    <label for="transact_donations"><?php esc_html_e( 'Article with Donations', 'transact' ); ?></label>
                </div>
                <div class="transact_meta_field">
                    <input type="checkbox" id="transact_donations" name="transact_donations" value="1" <?php echo esc_attr($donations_selected);?>>
                </div>

                <script>
                    jQuery("#transact_donations").change(function() {
                        if(this.checked) {
                            jQuery("#transact_display_button").attr('disabled', 'disabled');
                            jQuery("#transact_redirect_after_donation").removeAttr('disabled');
                            jQuery("#price_label__paid").css('display', 'none');
                            jQuery("#price_label__donation").css('display', 'block');
                        } else {
                            jQuery("#transact_display_button").removeAttr('disabled');
                            jQuery("#transact_redirect_after_donation").attr('disabled', 'disabled');

                            if(jQuery("#transact_display_button").val() != <?php echo esc_attr(\Transact\Models\TransactPostButtonTypes::ONLY_SUBSCRIBE); ?> ||
                            jQuery("#transact_display_button").val() != <?php echo esc_attr(\Transact\Models\TransactPostButtonTypes::DISABLE); ?>) {
                                // Enable price box if the purchase type is not subscription only
                                jQuery("#price_label__paid").css('display', 'block');
                                jQuery("#price_label__donation").css('display', 'none');
                            }
                        }
                    });
                </script>

                <?php

                /**
                 * If Donations are on, client will select which page the user will be redirected after donation (if it is wanted by publisher)
                 * We get ALL pages information
                 */
                $args = array(
                    'sort_order' => 'asc',
                    'sort_column' => 'post_title',
                    'post_type' => 'page',
                    'post_status' => 'publish'
                );
                $donation_disabled = $value[5] != 1 ? 'disabled' : '';
                $selected_noredirect = ($value[4] == self::NO_REDIRECT_VALUE) ? 'selected' : '';

                $pages = get_pages($args);
                ?>

                <div class="transact_meta_label">
                    <label for="transact_redirect_after_donation"><?php esc_html_e( 'Redirect After Donation', 'transact' ); ?></label>
                </div>
                <div class="transact_meta_field">
                    <select id="transact_redirect_after_donation" name="transact_redirect_after_donation" <?php echo esc_attr($donation_disabled); ?>>
                        <option <?php echo esc_attr($selected_noredirect);?> value="<?php echo esc_attr(self::NO_REDIRECT_VALUE);?>">
                            <?php esc_html_e(self::NO_REDIRECT_TEXT, 'transact');?>
                        </option>
                        <?php foreach ($pages as $page): ?>
                            <?php $selected = ($value[6] == $page->ID) ? 'selected' : ''; ?>
                            <option <?php echo esc_attr($selected); ?> value="<?php echo esc_attr($page->ID); ?>">
                                <?php echo esc_html($page->post_title); ?>
                            </option>
                        <?php endforeach;?>
                    </select>
                </div>
            <?php
            }

            /**
             * Check if subscription is enable by the publisher to show button options
             * And if donation is not selected on transact settings and post, otherwise does not make sense
             * to choose kind of button
             */
            $subscription_options = isset($options['subscription']) ? $options['subscription'] : 0;
            if ($subscription_options) {
                if(!$value[4] && $options['default_purchase_type']) {
                    switch($options['default_purchase_type']) {
                        case 'subscribe_purchase':
                            $value[4] = \Transact\Models\TransactPostButtonTypes::PURCHASE_AND_SUBSCRIPTION;
                            break;
                        case 'purchase_only':
                            $value[4] = \Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE;
                            break;
                        case 'subscribe_only':
                            $value[4] = \Transact\Models\TransactPostButtonTypes::ONLY_SUBSCRIBE;
                            break;
                        case 'disable_transact':
                            $value[4] = \Transact\Models\TransactPostButtonTypes::DISABLE;
                            break;
                        case 'sign_in_required':
                            $value[4] = \Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED;
                            break;
                    }
                }

                $selected_purchased_and_subscription = ($value[4] == \Transact\Models\TransactPostButtonTypes::PURCHASE_AND_SUBSCRIPTION) ? 'selected' : '';
                $selected_purchased = ($value[4] == \Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE) ? 'selected' : '';
                $selected_subscription = ($value[4] == \Transact\Models\TransactPostButtonTypes::ONLY_SUBSCRIBE) ? 'selected' : '';
                $selected_disable = ($value[4] == \Transact\Models\TransactPostButtonTypes::DISABLE) ? 'selected' : '';
                $selected_sign_in = ($value[4] == \Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED) ? 'selected' : '';
                $purchase_disabled = $donations_options && $value[5] == 1 ? 'disabled' : '';
                ?>

                <div class="transact_meta_label">
                    <label for="transact_display_button"><?php esc_html_e( 'Display Button', 'transact' ); ?></label>
                </div>
                <div class="transact_meta_field">
                    <select id="transact_display_button" name="transact_display_button" <?php echo esc_attr($purchase_disabled); ?>>
                        <option <?php echo esc_attr($selected_purchased_and_subscription);?> value="<?php echo esc_attr(\Transact\Models\TransactPostButtonTypes::PURCHASE_AND_SUBSCRIPTION);?>">
                            <?php esc_html_e('Display Purchase and Subscribe Button', 'transact');?>
                        </option>
                        <option <?php echo esc_attr($selected_purchased);?> value="<?php echo esc_attr(\Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE);?>">
                            <?php esc_html_e('Display Only Purchase Button', 'transact');?>
                        </option>
                        <option <?php echo esc_attr($selected_subscription);?> value="<?php echo esc_attr(\Transact\Models\TransactPostButtonTypes::ONLY_SUBSCRIBE);?>">
                            <?php esc_html_e('Display Only Subscribe Button', 'transact');?>
                        </option>
                        <option <?php echo esc_attr($selected_sign_in);?> value="<?php echo esc_attr(\Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED);?>">
                            <?php esc_html_e('Signed in user required. No payment.', 'transact');?>
                        </option>
                        <option <?php echo esc_attr($selected_disable);?> value="<?php echo esc_attr(\Transact\Models\TransactPostButtonTypes::DISABLE);?>">
                            <?php esc_html_e('Disable Transact on this Post', 'transact');?>
                        </option>
                    </select>
                </div>

                <script>
                    // check if we should display buttons
                    function check_transact_display_button() {
                        const value = jQuery('#transact_display_button').val();
                        if(value == <?php echo esc_attr(\Transact\Models\TransactPostButtonTypes::ONLY_SUBSCRIBE); ?>
                                || value == <?php echo esc_attr(\Transact\Models\TransactPostButtonTypes::DISABLE); ?>
                                || value == <?php echo esc_attr(\Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED); ?>) {
                            jQuery("#transact_price").attr('disabled', 'disabled');
                            jQuery("#transact_price_label").css('display', 'none');
                            jQuery("#transact_price_field").css('display', 'none');
                        } else if(!jQuery("#transact_donations").prop('checked')) {
                            // Enable price box if post is not subscription only and donations is not checked.
                            jQuery("#transact_price").removeAttr('disabled');
                            jQuery("#transact_price_label").css('display', 'block');
                            jQuery("#transact_price_field").css('display', 'block');
                        }
                    }
                    jQuery("#transact_display_button").change(function() {
                        check_transact_display_button();
                    });

                    jQuery(document).ready(function() {
                        console.log('ready');
                        check_transact_display_button(); // initi
                    });

                </script>
            <?php
            }
            ?>
            <div id="transact_price_label" class="transact_meta_label" >
                <label id="price_label__paid" style="<?php echo $is_donation ? esc_attr( 'display: none;', 'transact' ) : '' ?>" for="transact_price">
                    <?php esc_html_e( 'Premium Price (cents)', 'transact' ); ?>
                </label>
                <label id="price_label__donation" style="<?php echo $is_donation ? '' : esc_attr( 'display: none;', 'transact' ) ?>" for="transact_price">
                    <?php esc_html_e( 'Default Donation (cents)', 'transact' ); ?>
                </label>
            </div>
            <div id="transact_price_field" class="transact_meta_field">
                <span class="transact-price-input">
                    <span class="currency_symbol"><?php echo esc_html($currency_utils->get_currency_symbol()); ?></span>
                    <input type="number" min="0.01" step="0.01" id="transact_price" class="transact-price-input__field"
                        onchange="check_transact_price()"
                        name="transact_price" value="<?php echo esc_attr( $value[1] ) / 100; ?>"  <?php echo esc_attr($price_disabled); ?> />
                </span>
                <span id="price_alert" style="color: red; display: none;"><?php esc_html_e('You need to set a price!', 'transact');?></span>
            </div>

            <div class="transact_meta_label">
                <label for="transact_item_code"><?php esc_html_e( 'Item Code', 'transact' ); ?></label>
            </div>
            <div class="transact_meta_field">
                <input readonly type="text" size="35" id="transact_item_code" name="transact_item_code" value="<?php echo esc_attr( $value[2] ); ?>" />
            </div>

            <?php
            /**
             *  todo: comments premium future development
             *
            <label for="transact_premium_comments">
            <?php _e( 'Premium comments', 'transact' ); ?>
            </label>
            <input type="checkbox" id="transact_premium_comments" name="transact_premium_comments" value="<?php echo esc_attr( $value[4] ); ?>" <?php echo $premium_comment_selected; ?>/>
            <br/>
            */
        ?>
        </div>
        <?php
    }

    public function set_transact_style()
    {
        wp_enqueue_style('transact', FRONTEND_ASSETS_URL . 'style.css');
        wp_enqueue_style(
            'transact-admin',
            FRONTEND_ASSETS_URL . 'admin_style.css'
        );
    }

    /**
     * Get Transact item code
     * @return int
     */
    public function set_transact_premium_block()
    {
        wp_enqueue_style(
            'transact-premium-block',
            FRONTEND_ASSETS_URL . 'transact-blocks.editor.css'
        );

        wp_enqueue_script(
            'transact-premium-block',
            FRONTEND_ASSETS_URL . 'transact-blocks.js',
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
            true
        );
    }

    /**
     * Get Transact price
     * @return int
     */
    public function get_transact_price($post_id = 0)
    {
      $price = get_post_meta( $post_id ? $post_id : $this->post_id, 'transact_price', true );

      if (empty($price) || $price < 1) {
        $options = get_option('transact-settings');
        if (isset($options['transact_default_price'])) {
            $price = $options['transact_default_price'];
        }

      }
      return $price;
    }

    /**
     * Get Transact item code
     * @return int
     */
    public function get_transact_item_code()
    {
        return get_post_meta( $this->post_id, 'transact_item_code', true );
    }

    /**
     * Creating shortcode to show the button on the editor.
     *
     * @param string $atts coming from shortcode can be "id" and "text"
     * @return string $button button html
     */
    public function transact_shortcode( $atts )
    {
        global $post;
        $shortcode = new transactShortcode($atts, $post->ID);
        return $shortcode->print_shortcode();
    }

}

