<?php

namespace Transact\FrontEnd\Controllers\Buttons;

use Transact\Utils\Settings\CurrencyUtils;
require_once  plugin_dir_path(__FILE__) . '../../utils/transact-currency-utils.php';



use Transact\Models;
require_once  plugin_dir_path(__FILE__) . '../../models/transact-button-types.php';


/**
 * It will take care of print different buttons
 *
 * Class transactHandleButtons
 */
class transactHandleButtons
{
    /**
     * text to be included on the button
     */
    const DEFAULT_BUTTON_TEXT = 'Purchase with Transact for';
    const TOKENS_TEXT = 'cents';
    const TOKEN_TEXT = 'cent';
    const DEFAULT_SUBSCRIBE_TEXT = 'Subscribe / Login';
    const DEFAULT_SIGN_IN_TEXT = 'Sign-in or register';
    const DONATE_TEXT = 'Donate';
    const CTA_TEXT = 'Purchase with Transact or subscribe to read the full story';

    /**
     * @var int post_id
     */
    protected $post_id;

    /**
     * @var Transact\FrontEnd\Controllers\Api\TransactApi
     */
    protected $transact_api;

    protected $options;


    public function __construct($post_id, $transact_api)
    {
        $this->post_id = $post_id;
        $this->transact_api = $transact_api;
        $this->options = get_option( 'transact-settings' );
    }

    /**
     * Will check if user wants donation on this article, in that case will show donation placeholder otherwise
     * Will check if the user have a subscription and which kind of button want to use (given on post settings)
     *
     * @param null|int $number_of_words

     *
     * @return string
     */
    public function print_purchase_buttons( $number_of_words = null )
    {
        if($this->get_if_article_donation()) {
            return $this->print_donation_button($number_of_words);
        }

        $buttons = array();

        $button_type = intval($this->get_button_type());
        switch($button_type) {
            case (\Transact\Models\TransactPostButtonTypes::PURCHASE_AND_SUBSCRIPTION):
                array_push($buttons, $this->print_single_button($this->options, \Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE, $number_of_words));
                array_push($buttons, $this->print_single_button($this->options, \Transact\Models\TransactPostButtonTypes::ONLY_SUBSCRIBE));
                break;

            case (\Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE):
                array_push($buttons, $this->print_single_button($this->options, $button_type, $number_of_words));
                break;

            case (\Transact\Models\TransactPostButtonTypes::ONLY_SUBSCRIBE):
                array_push($buttons, $this->print_single_button($this->options, $button_type));
                break;
            case (\Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED):
                array_push($buttons, $this->print_single_button($this->options, $button_type));
                break;

            case (\Transact\Models\TransactPostButtonTypes::DISABLE):
                break;

            default:
                array_push($buttons, $this->print_single_button($this->options, \Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE, $number_of_words));
                break;
        }

        return $this->wrap_buttons($this->options, $buttons, false);
    }

    /**
     * Returning full donation button with price input.
     *
     * @param $number_of_words
     * @return string
     */
    public function print_donation_button($number_of_words = null) {
        $price = get_post_meta($this->post_id, 'transact_price', true );
        $button = $this->print_donate_button($this->options, $number_of_words);
        $input = '<span class="transact-price-input"><input type="number" step="0.01" min="0.01" name="donate" class="transact-donate_amount transact-price-input__field" ' .
            'id="donate_val" onchange="TransactWP.setDonateAmount()" value="' .
            ($price ? ($price / 100) : 0.01) .
            '"/></span>';
        $buttons = array($input . $button);

        return $this->wrap_buttons($this->options, $buttons, true);
    }

    public function is_sign_in_required_button() {
        $button_type = intval($this->get_button_type());

        if ($button_type === \Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED) {
            return TRUE;
        }
        return FALSE;
    }

    public function should_display_call_to_action()
    {
        //  with short code you can override this to false

        $button_type = intval($this->get_button_type());
        switch($button_type) {
            case (\Transact\Models\TransactPostButtonTypes::PURCHASE_AND_SUBSCRIPTION):
            case (\Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE):
            case (\Transact\Models\TransactPostButtonTypes::ONLY_SUBSCRIBE):
                return TRUE;
                break;
            case (\Transact\Models\TransactPostButtonTypes::DISABLE):
            case (\Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED):
                return FALSE;
            default:
                return TRUE;
        }

        return TRUE;
    }

    public function should_display_promo()
    {
       //  with short code you can override this to false
       return TRUE;
    }
    /**
     * Get button type from post options
     *
     * @return mixed
     */
    public function get_button_type()
    {
        return get_post_meta( $this->post_id, 'transact_display_button' , true );
    }

    public function get_purchase_options()
    {
        $result = array();

        if ($this->get_if_article_donation()) {
            array_push($result, 'donate');
        } else {
            $button_type = intval($this->get_button_type());

            switch($button_type) {
                case (\Transact\Models\TransactPostButtonTypes::PURCHASE_AND_SUBSCRIPTION):
                    array_push($result, 'purchase', 'subscribe');
                    break;

                case (\Transact\Models\TransactPostButtonTypes::ONLY_SUBSCRIBE):
                    array_push($result, 'subscribe');
                    break;

                case (\Transact\Models\TransactPostButtonTypes::DISABLE):
                    array_push($result, 'free');
                    break;
                case (\Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED):
                    array_push($result, 'signin');
                    break;

                case (\Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE):
                default:
                    array_push($result, 'purchase');
                    break;
            }
        }

        return $result;
    }

    /**
     * Checks if donations are activated on transact settings and on post level.
     * @return bool
     */
    public function get_if_article_donation()
    {
        if (isset($this->options['donations']) && $this->options['donations']) {
            if (get_post_meta( $this->post_id, 'transact_donations' , true )) {
                return true;
            }
        }
        return false;
    }

    public function has_transact_button($content)
    {
        return false !== strpos( $content, 'class="transact-purchase_button"' );
    }

    /**
     * It prints a block containing supplied buttons along with background fade and any supporting text
     *
     * @param $options
     * @param $transact_api
     * @param $buttons array containing html for each button
     * @return string html table with supplied buttons
     */
    protected function wrap_buttons($options, $buttons, $is_donation) {
        $output = '<div class="transact-purchase_button">';
        $has_overlay = false;

        if(!$is_donation && $this->should_display_call_to_action()) {
            $has_overlay = true;

            $background_gradient_start = 130;
            $background_fade_amount_class = '';
            if(isset($options['text_fade_amount'])) {
                $background_fade_amount_class = ' fade-' . $options['text_fade_amount'];
                if($options['text_fade_amount'] === 'less') {
                    $background_gradient_start = 180;
                }
            }

            $background_fade_color_style = '';
            if(isset($options['page_background_color'])) {
                list($r, $g, $b) = sscanf($options['page_background_color'], "#%02x%02x%02x");
                $background_fade_color_style = "background:linear-gradient(to bottom, rgba($r,$g,$b,0), rgba($r,$g,$b,1) " .
                    $background_gradient_start .
                    "px, rgba($r,$g,$b,1))";
            }

            $cta_text = self::CTA_TEXT; // set default
            if (!empty($this->options['transact_call_to_action_text'])) {
                $cta_text = $this->options['transact_call_to_action_text'];
            }

            $output .= sprintf(
                '<div class="transact-fade%s" style="%s">' .
                '<h3 class="transact-cta">' . $cta_text . '</h3>',
                $background_fade_amount_class,
                $background_fade_color_style
            );
        } else {
            $output .= '<div class="transact-spacer">';
        }

        if ($this->should_display_promo()) {
            $output .= '<h4 class="transact_promo" id="transact_promo"></h4>';

        }

        $output .=  '<div class="transact_button_container">';
        for ($i = 0; $i < count($buttons); $i++) {
            $output .= '<div class="transact_buttons">' . $buttons[$i] . '</div>';
        }

        $output .= '</div>';

        $output .= '</div></div>';

        return $output;
    }

    /**
     * It prints a single button, either subscription or purchase
     *
     * @param $options
     * @param $transact_api
     * @param $button_type button type to print subscription or purchase
     * @param null|int $number_of_words number of words it they need to be set on the button
     * @return string html button
     */
    protected function print_single_button($options, $button_type, $number_of_words = NULL)
    {
        $button_text = $this->get_button_text($button_type);
        $button_text .= ($number_of_words) ? " ($number_of_words words)" : '';

        $button_background_color_style = (isset($options['background_color']) ? 'background-color:' . esc_attr($options['background_color']) . ';' : '');
        $button_text_color_style = (isset($options['text_color']) ? 'color:' . esc_attr($options['text_color']) . ';' : '');


        $onclick = '';
        $extra_id = '';
        switch ($button_type) {
            case (\Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE):
                $onclick = 'TransactWP.doPurchase()';
                $extra_id = 'single';
                break;
            case (\Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED):

                $onclick = 'TransactWP.doOauth(' . $this->post_id .')';
                $extra_id = 'oauth';
                break;
            default:
                $onclick = 'TransactWP.doSubscription()';
                $extra_id = 'subscription';
                break;
        }
        $button = '';
        $button .= sprintf(
            '<button disabled class="transact-purchase_button__loading" style="%s" id="button_purchase_%s_loading"><i class="transact-icon"></i> Please Wait</button>',
            $button_background_color_style . $button_text_color_style,
            $extra_id,
            $onclick,
            $button_text
        );
        $button .= sprintf(
            '<button class="transact-purchase_button__btn" style="%s" id="button_purchase_%s" onclick="%s">%s</button>',
            $button_background_color_style . $button_text_color_style,
            $extra_id,
            $onclick,
            $button_text
        );

        return $button;
    }

    /**
     * Taking care or printing only button with transact styling taken from settings
     *
     * @param $options
     * @param $number_of_words
     * @return string
     */
    protected function print_donate_button($options, $number_of_words = NULL)
    {
        $button_text = self::DONATE_TEXT;
        $button_text .= ($number_of_words) ? " ($number_of_words words)" : '';

        $button_background_color_style = (isset($options['background_color']) ? 'background-color:' . esc_attr($options['background_color']) . ';' : '');
        $button_text_color_style = (isset($options['text_color']) ? 'color:' . esc_attr($options['text_color']) . ';' : '');

        $onclick = 'TransactWP.doDonate()';
        $extra_id = 'donation';

        $button = '';
        $button .= sprintf(
            '<button disabled class="transact-purchase_button__loading" style="%s" id="button_purchase_%s_loading"><i class="transact-icon"></i> Please Wait</button>',
            $button_background_color_style . $button_text_color_style,
            $extra_id,
            $onclick,
            $button_text
        );
        $button .= sprintf(
            '<button class="transact-purchase_button__btn" style="%s" id="button_purchase_%s" onclick="%s">%s</button>',
            $button_background_color_style . $button_text_color_style,
            $extra_id,
            $onclick,
            $button_text
        );

        return $button;
    }

    /**
     * Getting button text depending button type
     * @param $button_type
     * @return string|void
     */
    protected function get_button_text($button_type)
    {
        $options = get_option('transact-settings');
        $display_cents_dollars = isset($options['display_cents_dollars']) ? $options['display_cents_dollars'] : 'cents';

        if ($button_type === \Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE) {
            $price = get_post_meta($this->post_id, 'transact_price', true );
            if ($price === 1) {
                $token_text = __(self::TOKEN_TEXT, 'transact');
            } else {
                $token_text = __(self::TOKENS_TEXT, 'transact');
            }
            $button_text = __(self::DEFAULT_BUTTON_TEXT, 'transact') . ' '.  $price . ' ' . $token_text;
        } else if ($button_type === \Transact\Models\TransactPostButtonTypes::SIGN_IN_REQUIRED) {
            $button_text = __(self::DEFAULT_SIGN_IN_TEXT, 'transact');
        } else {
            $button_text = __(self::DEFAULT_SUBSCRIBE_TEXT, 'transact');
        }

        // button text now has default value

        if (!empty($this->options['transact_purchase_button_text'])
            && strlen($this->options['transact_purchase_button_text']) > 2
            && $button_type === \Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE) {

                $price = get_post_meta($this->post_id, 'transact_price', true );

                if ($display_cents_dollars === 'cents') {
                    // 'tokens' plural
                    $token_text = __(self::TOKENS_TEXT, 'transact');
                    if ($price === 1) {  // singular
                        $token_text = __(self::TOKEN_TEXT, 'transact');
                    }

                    $button_text = $this->options['transact_purchase_button_text'] . ' '.  $price . ' ' . $token_text;
                } else {
                    $currency_util = new CurrencyUtils();

                    $button_text = $this->options['transact_purchase_button_text'] . ' ' . $currency_util->localize_currency_from_options($price);
                }

        } else if (!empty($this->options['transact_subscribe_button_text'])
            && strlen($this->options['transact_subscribe_button_text']) > 2
            && $button_type !== \Transact\Models\TransactPostButtonTypes::ONLY_PURCHASE) {

                $button_text = $this->options['transact_subscribe_button_text'] . ' ';
        }

        return $button_text;
    }

}
