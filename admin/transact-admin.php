<?php
namespace Transact\Admin;
use Transact\Admin\Settings\Menu\AdminSettingsMenuExtension;
use Transact\Admin\Settings\Post\AdminSettingsPostExtension;

require_once  plugin_dir_path(__FILE__) . '/controllers/transact-admin-settings-menu.php';
require_once  plugin_dir_path(__FILE__) . '/controllers/transact-admin-settings-post.php';

/**
 * Class DashboardExtension
 */
class DashboardExtension
{
    /**
     * All hooks to dashboard
     */
    public function hookToDashboard()
    {
        /**
         * Dashboard Menu Hook
         */
        (new AdminSettingsMenuExtension())->hookToDashboard();

        /**
         * Post Settings Hook
         */
        (new AdminSettingsPostExtension())->hookToDashboard();
    }
}

