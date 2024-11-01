<?php
namespace Transact\Utils\Settings;

/**
 * Class ConfigParser
 */
class CurrencyUtils
{
  static public function get_currency_symbol() {
    $selected_option = self::get_currency_from_options();

    switch($selected_option) {
        case 'GBP':
          return '£';
        case 'EUR':
          return '€';
        case 'JPY':
          return '¥';
        case 'CAD':
          return 'C$';
        default:
          return '$';
    }
  }

  static public function localize_currency_from_options($price) {
    $selected_option = self::get_currency_from_options();

    $symbol = self::get_currency_symbol();

    switch($selected_option) {
        case 'JPY':
          return $symbol . $price;
        default:
          return $symbol . ($price / 100);
    }
  }

  static public function get_currency_from_options() {
    $options = get_option('transact-settings');
    $selected_option =  'PROD'; // default
    if (isset($options['transact_currency'])) {
        $selected_option = $options['transact_currency'];
    }
    return $selected_option;
  }

}