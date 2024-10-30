<?php

/*
Plugin Name: MobileMonkey X-Ray Installer
Plugin URI: https://mobilemonkey.com/help
Description: Install MobileMonkey X-Ray for website visitor contact detection on your WordPress site in minutes with this WordPress plugin.
Version: 1.1
Author: MobileMonkey, Inc.
Author URI: https://mobilemonkey.com
License: GPL2
*/
include dirname(__FILE__) . '/inc/settings_init.php';
function mmxray_hook_wp_head()
{
    $script_enabled = (bool)get_option('xray_enabled');
    if (!$script_enabled) {
        return;
    }

    $script = get_option('xray_script');
    if (!empty($script) && preg_match( '/https:\/\/(mm-uxrv\.com|[\w-]+\.mobilemonkey\.com)\/js\/[\w\-_]+\.js/', $script, $matches)) {
      $scriptUrl = $matches[0];
      if(!empty($scriptUrl) && wp_http_validate_url($scriptUrl)) {
        ?><script async="async" defer="defer" src="<?php echo esc_url($scriptUrl) ?>"></script>
<?php
      }
    }
}

add_action('wp_head', 'mmxray_hook_wp_head' );

function mmxray_generate_cache_warnings()
{
    $active_plugins = (array)get_option('active_plugins', array());

    $cache_plugins = [
        'wp-rocket/wp-rocket.php' => [
            'name' => 'WP Rocket',
            'purge_description' => '"Clear Cache"',
            'purge_url' => 'options-general.php?page=wprocket#dashboard'
        ],
        'w3-total-cache/w3-total-cache.php' => [
            'name' => 'W3 Total Cache',
            'purge_description' => '"empty all caches" function',
            'purge_url' => 'admin.php?page=w3tc_dashboard'
        ],
        'wp-optimize/wp-optimize.php' => [
            'name' => 'WP-Optimize',
            'purge_description' => '"Purge cache" function',
            'purge_url' => 'admin.php?page=wpo_cache'
        ],
        'wp-fastest-cache/wpFastestCache.php' => [
            'name' => 'WP Fastest Cache',
            'purge_description' => '"Clear All Cache" function on the "Delete Cache" tab on the page',
            'purge_url' => 'admin.php?page=wpfastestcacheoptions'
        ],
        'wp-super-cache/wp-cache.php' => [
            'name' => 'WP Super Cache',
            'purge_description' => '"Delete Cache" function',
            'purge_url' => 'options-general.php?page=wpsupercache&tab=contents'
        ],
    ];
    foreach ($active_plugins as $active_plugin) {
        if (array_key_exists($active_plugin, $cache_plugins)) {
            $info = $cache_plugins[$active_plugin];
            if (!empty($info)) {
                add_settings_error('xray_messages', $info['name'] . '-detected', $info['name'] . ' caching plugin detected - please use the ' . $info['purge_description'] . ' <a href="' . $info['purge_url'] . '">here</a> after making changes', 'warning');
            }
        }
    }
}

function mmxray_xray_options_page_html()
{
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    $has_no_save_errors = empty(get_settings_errors('xray_messages'));
    // add error/update messages
    mmxray_generate_cache_warnings();

    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if (isset($_GET['settings-updated']) && $has_no_save_errors) {
        // add settings saved message with the class of "updated"
        add_settings_error('xray_messages', 'xray_updated', 'Settings updated - please remember to clear all plugin caches for changes to take effect', 'updated');
    }

    // show error/update messages
    settings_errors('xray_messages');
    ?>
  <div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        // output security fields for the registered setting "xray"
        settings_fields('xray');
        // output setting sections and their fields
        // (sections are registered for "xray", each field is registered to a specific section)
        do_settings_sections('xray');
        // output save settings button
        submit_button('Save Settings');
        ?>
    </form>
  </div>
    <?php
}

function mmxray_xray_options_page()
{
    add_menu_page('MobileMonkey X-Ray', 'MobileMonkey', 'manage_options', 'xray', 'mmxray_xray_options_page_html', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzQiIGhlaWdodD0iMzUiIHZpZXdCb3g9IjAgMCAzNCAzNSIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTI3LjkxMjQgMTguMTg3MUMyNy45MTI0IDE4LjE4NzEgMzAuNTQ0NiAxNC41NDU0IDMxLjc1OTUgMTguNTkxN0MzMi4zNjY5IDIwLjYxNDkgMzEuMzUwNSAyMi4zODMyIDMwLjE2NCAyNC4wODQ3QzI5Ljc4MzMgMjQuNjI4OSAyOS4wMzgyIDI0Ljk5NTEgMjkuMTk0MSAyNS44ODk0QzMyLjE2NDQgMjUuMDY1OSAzNC4zMzQ5IDIxLjE0MyAzMy41ODE3IDE3LjM3NzhDMzIuOTc0MyAxNC4zNDMgMzEuMzU0NSAxMS45MTUyIDI3LjMwNSAxNC4xNDA3QzI2LjkxNjMgMTEuNTc5NCAyNi4yMTc3IDguODIzOCAyMy42NjA1IDcuNDY0MjJDMjEuMzkwNyA2LjI1ODQgMTkuMTIzIDQuNDk4MjMgMTcuNTg2MiAyLjQwNjI1QzE3LjEwNDMgMS43NDg3MiAxNi43NzYzIDEuNTk2OTggMTYuMzYxMyAyLjg0NzMxQzE1LjkyNTkgNC4xNTgzMyAxNS4yNTU3IDMuMjUxOTUgMTQuNTQ5MSAyLjgxMDg5QzEzLjQxMTIgMi4xMDA3NSAxMy4wNDY3IDEuMTkyMzQgMTIuNzI2OCAwLjM4MzA2OEMxMi43MjY4IC0wLjAyMTU2OSAxMS44MjU4IC0wLjIyMzg4OCAxMS4xMDcgMC4zODMwNjhDMTAuNjE3IDAuNzk3ODIxIDguNjc3MzQgMi42MDg1NyAxMC4yOTcxIDUuMjM4NzFDOS40NTY4NyA1LjM2NDE1IDguOTUyNyA1LjE5NDIgOC40NzQ4NiA0LjgzNDA4QzguMTYzMDUgNC41OTkzOSA3LjY2NDk2IDMuNjIwMTcgNy40NjI0OSAzLjYyMDE3QzYuNjUyNTkgMy42MjAxNyA2Ljc5ODM3IDUuMzk2NTIgNi44NTUwNyA1Ljg0NTY3QzcuMDU3NTQgNy40NjQyMiA4LjkzODUzIDguOTM1MDcgOS4wODIyOSA5LjA4Mjc3QzkuMDgyMjkgOS4wODI3NyA2LjA0NTE3IDExLjEwNiA1LjQzNzc0IDEzLjczNjFDNS40Mzc3NCAxMy43MzYxIDUuMDE2NiAxMy4yODY5IDQuMjIyODkgMTMuMTI5MUMyLjE5ODE1IDEyLjcyNDUgMS4xMjA5OCAxMy43MjggMC41NzgzNDkgMTUuMzU0NkMtMC4wMjkwNzU0IDE3LjE3NTUgLTAuMzQwODg3IDE5LjM2MjYgMC41NzgzNDkgMjIuMDMxMkMwLjgxOTI5NCAyMi43MzMyIDEuOTk1NjcgMjQuMjU2NyAzLjIxMDUyIDI0LjQ1OUMxLjM4ODI1IDIxLjIyMTkgMS4zODgyNSAyMC40MTI2IDEuNTkwNzIgMTguNzk0MUMxLjc2Njg4IDE3LjM4NzkgMi41Nzg4IDE1Ljk2MTYgMy42MTU0NyAxNS45NjE2QzQuNDI1MzcgMTUuOTYxNiA0LjU1NDk1IDE2LjQ3NzUgNC44MzAzMiAxNy4zNzc4QzUuMDIyNjcgMTguMDA5MSA1LjY0MDIyIDE5LjQwMSA2LjI0NzY0IDIwLjAwOEw1LjIzNTI3IDIxLjgyODhDNS4yMzUyNyAyMS44Mjg4IDQuNDI1MzcgMjEuNjI2NSAzLjgxNzk0IDIwLjgxNzJDMy41MTgyOCAyMC40MTg3IDMuMjYxMTQgMTguOTcyMSAyLjgwNTU3IDE5LjQwMUMyLjQwMDYyIDE5LjYwMzMgMi41MTE5OCAyMS43ODg0IDMuNDMxMjIgMjMuMDY3QzMuODEzOSAyMy41OTkxIDQuNjI3ODQgMjQuMDU0MyA0LjYyNzg0IDI0LjA1NDNDNC4wMjA0MiAyNy4wODkxIDQuMjg3NjkgMjguNTM3NyA3LjI2MDAyIDI5LjUxNjlDMTEuNTEyIDI5LjkyMTYgMTMuMDMyNiAyOS44NTY4IDE1LjU2MTUgMjguOTFDMTcuMTgxMyAyOC4zMDMgMTkuMDAzNiAyNi42ODQ1IDE5LjQwODUgMjYuMDc3NUMxOS40MDg1IDI2LjI3OTggMTkuNDA4NSAyNy4wODkxIDE3LjU4NjIgMjguNzA3N0MxNi4wODc5IDMwLjAzODkgMTIuMTkyMyAzMC40OTYyIDEwLjQ5OTYgMzAuMzI2MkM5LjA3NDE5IDMwLjE4NDYgOS4yODQ3NiAzMC4xMjM5IDcuNTcxODMgMzAuMjc1NkM2LjY1MjU5IDMwLjkzMzIgNy4wNTc1NCAzMS45NDQ4IDcuNDc2NjYgMzIuNTk2MkM4Ljc5NDc4IDM0LjY0MTcgMTEuMjQwNyAzNC43MTA1IDEzLjQ2OTkgMzQuOTI2OUMxOS40MDg1IDM1LjUwNTYgMjQuMjI1NCAzMi42MzA2IDI1LjY4NTIgMjYuODg4OEMyNS45NTg2IDI1LjgxMDUgMjYuMzQ5MyAyNS4xNDg5IDI3LjMwNSAyNC44NjU2QzMwLjE0MTcgMjQuMDI2IDMxLjEwMTQgMTkuNDIxMiAyOS45MzcyIDIwLjAxQzI5LjA3NjcgMjIuNDU2IDI2Ljk4MTEgMjMuMzc0NiAyNi40OTUxIDIzLjA0NDhDMjUuOTUwNSAyMi42NzQ1IDI2LjE0ODkgMjEuNzA1NCAyNi4yOTI2IDIxLjIyMzlDMjYuNjc3MyAxOS45MzcyIDI3LjMwNSAxOC45OTg0IDI3LjMwNSAxOC45OTg0TDI3LjkxMjQgMTguMTg3MVpNMjMuMjIxMSAxOS4xNjgzQzIyLjQ1NTcgMjAuMzU4IDIyLjM4MDggMjEuMzcxNiAyMi41ODMzIDIyLjY5NDhDMjMuNjk4OSAyOS45NzAxIDE5LjgxMzQgMzQuMTcwMyAxMi41MjQ0IDMzLjU2MzNDMTEuNzU5IDMzLjUwMDYgOS45NjUwOCAzMi44MjQ4IDkuMjg0NzYgMzIuMzQ5NEM4Ljg2MTU5IDMyLjA1NCA4LjY3NzM0IDMxLjk0NDggOC42NzczNCAzMS4zMzc4QzguNjc3MzQgMzEuMzM3OCAxMC43MDIxIDMxLjU1NDMgMTEuMTA3IDMxLjU0MDFDMTYuNzc2MyAzMS4zMzc4IDE5LjgxMzQgMjkuOTIxNiAyMC42MjMzIDI1LjI2ODNDMjAuNzk5NSAyNC4yNTI2IDIwLjIxODQgMjIuODQwNCAyMC4yMTg0IDIyLjg0MDRDMjAuMjE4NCAyMi44NDA0IDIxLjI5OTYgMjEuNzM5OCAyMS4yMzA4IDIxLjIyMTlDMjAuMjE4NCAyMS4wMTk2IDE5LjI2MjcgMjEuNDYwNiAxOC44MDExIDIxLjgyODhDMTguNTQ2IDIyLjAzMzIgMTguMjc4NyAyMi41MjA4IDE4LjU5ODYgMjIuODQwNEMxOC43NTA1IDIyLjk5MjIgMTguODAxMSAyMy4wNDI3IDE5LjIwNiAyMy4wNDI3QzE5LjIwNiAyMy4wNDI3IDE5LjQyMjcgMjIuOTk0MiAxOS40MDg1IDIzLjA0MjdDMTguMTkzNyAyNy4wODkxIDE1Ljc2NCAyNy40OTM4IDEzLjMzNDMgMjguMTAwN0MxMi4wNDg1IDI4LjQyMjQgMTAuMTQ5MyAyOC4zMjkzIDkuMDgyMjkgMjguMzAzQzUuNjQ2MjkgMjguMjIwMSA1LjA2NTE5IDI2LjUzODggNi4wNDUxNyAyMy4yNDUxQzYuMjUxNjkgMjIuNTQ5MSA3LjI3NjIxIDIwLjU4ODYgNy42NjQ5NiAyMC4wMDhDOC4wNjk5MSAxOS40MDEgNy4zNzEzOCAxOS41NDQ3IDYuNjk5MTYgMTguMTUwN0M1LjY0MDIyIDE1Ljk2MTYgNi4xNjA1OCAxMy44MTcgNy44Njc0NCAxMi41MjIyQzkuNTg2NDUgMTEuMjE5MyAxMS4xNTU2IDExLjQ3MDEgMTIuNzI2OCAxMy4zMzE1QzEzLjYzNTkgMTQuNDA5OCAxMy41MTg1IDEzLjk2NDcgMTQuMTQ0MiAxMy4xMjkxQzE2LjU2NzggOS44OTAwMiAxOC44OTYyIDkuMDE2IDIxLjc1NTIgMTAuOTMyQzI0LjI0MzYgMTIuNTk3IDI1LjAyMTEgMTYuMzcwMyAyMy4yMjExIDE5LjE2ODNaIiBmaWxsPSIjMzk1MkZBIi8+CjxwYXRoIGQ9Ik0xNy41MzgxIDE3Ljc0ODFDMTcuNDc5NCAxOC41NTMzIDE3LjM2NiAxOS40NzM4IDE2LjE5MTYgMTkuNDk0MUMxNS4yNDYxIDE5LjUxMjMgMTQuOTg2OSAxOC43NjE3IDE0Ljk0ODQgMTcuOTg2OEMxNC45MDE5IDE3LjA1NjEgMTUuMTgzMyAxNi4xMTU0IDE2LjIwOTggMTYuMDIyM0MxNy4xNjc2IDE1LjkzNTMgMTcuNTA1NyAxNi43NjI4IDE3LjUzODEgMTcuNzQ4MVoiIGZpbGw9IiMzOTUyRkEiLz4KPHBhdGggZD0iTTguNDY2NDMgMTguMDE3MUM4LjQ5NDc4IDE3LjI0ODMgOC42NTA2OCAxNi40MTY4IDkuNTc1OTkgMTYuNDY5NEMxMC40MzI1IDE2LjUxOCAxMC42NjEzIDE3LjMyNTIgMTAuNjQ5MSAxOC4xMDQxQzEwLjYzNyAxOC44ODkxIDEwLjMzNTMgMTkuNjgyMiA5LjQ5NzAzIDE5LjY2QzguNjE4MjkgMTkuNjM1NyA4LjQ2ODQ2IDE4Ljc4OCA4LjQ2NjQzIDE4LjAxNzFaIiBmaWxsPSIjMzk1MkZBIi8+CjxwYXRoIGQ9Ik0xMy43MzkzIDIyLjIzMzRDMTMuNTM2OCAyMy4yNDUgMTIuMTI3NiAyMi44NTY2IDExLjMwOTYgMjIuODQwNEMxMS43MTQ1IDIxLjIyMTggMTMuNzM5MyAyMS4yMjE4IDEzLjczOTMgMjIuMjMzNFoiIGZpbGw9IiMzOTUyRkEiLz4KPHBhdGggZD0iTTEwLjQ5OTMgMjIuODQwNUM5LjQ4Njk0IDIyLjYzODIgOS41MTUyOSAyMi42NDAyIDkuMjg0NDcgMjIuNjM4MkM4Ljg5NTcxIDIyLjYzNDEgOC41NDM0MSAyMi42MTc5IDguNDc0NTcgMjIuMTQwNUM4LjQyNTk3IDIxLjc5NjUgOC40NzQ1NyAyMS40MjQzIDkuMjg0NDcgMjEuNDI0M0M5LjY2MzA5IDIxLjQyNDMgMTAuMjk2OCAyMi4yMzM1IDEwLjQ5OTMgMjIuODQwNVoiIGZpbGw9IiMzOTUyRkEiLz4KPC9zdmc+Cg==');
}

add_action('admin_menu', 'mmxray_xray_options_page' );
