<?php
namespace Transact\Utils\Settings\cpt;

/**
 * Class ConfigParser
 */
class SettingsCpt
{

    /**
     * Check Transact Settings (Dashboard) and checks custom post types with transact enabled.
     *
     * @return array
     */
    static public function get_cpts_enable_for_transact()
    {
        $result = array();
        $options = get_option('transact-settings');
        $cpt_options = isset($options['cpt']) ? $options['cpt'] : array();

        if (!empty($cpt_options)) {
            foreach ($cpt_options as $cpt_name => $value) {
                if ($value === 1) {
                    // option is saved as cpt_{custom_post_type_name}
                    array_push($result, substr($cpt_name, 4));
                }
            }
        }
        return $result;
    }

}