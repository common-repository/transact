<?php

namespace Transact\Admin\Settings\Shortcode;

use Transact\FrontEnd\Controllers\Shortcode\transactHandleShortcodeButtons;
require_once  plugin_dir_path(__FILE__) . '../../frontend/controllers/transact-handle-buttons-shortcode.php';

use Transact\FrontEnd\Controllers\Api\TransactApi;
require_once  plugin_dir_path(__FILE__) . '../../frontend/controllers/transact-api.php';

/**
 * It will handle shortcode logic
 *
 * Class transactShortcode
 */
class transactShortcode
{
    /**
     * @var string
     */
    protected $attributes;

    /**
     * @var array transact settings
     */
    protected $options;

    /**
     * @var int post id
     */
    protected $post_id;

    function __construct($attributes, $post_id)
    {
        $this->attributes = $attributes;
        $this->post_id = $post_id;
        $this->options = get_option( 'transact-settings' );
    }

    /**
     *
     */
    function print_shortcode()
    {
        $atts = shortcode_atts( array(
            'button_text' => '',
            'subscribe_text' => '',
            'button_type' => '1',
            'call_to_action' => '1',
            'display_promo' => '1',
        ), $this->attributes );
        $transact = new TransactApi($this->post_id);
        
        if($transact->is_premium()) {
            return '';
        }
        
        $button_handle = new transactHandleShortcodeButtons(
            $this->post_id,
            $transact,
            $atts['button_text'],
            $atts['subscribe_text'],
            $atts['button_type'],
            $atts['call_to_action'],
            $atts['display_promo']
        );

        return $button_handle->print_purchase_buttons();
    }
}