<?php
/** 
 * Plugin Name:       Digital Humans - NevronixAI
 * Plugin URI:        https://nevronix.ai/
 * Description:       Leading Digital Human Platform that Puts a Face to AI. Powered by the NevronixAI team.
 * Version:           1.0
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// NevronixAI plugin version
define( 'NEVRONIXAI_PLUGIN_VERSION', '1.0' );

// Register activation hook to initialize settings.
register_activation_hook(__FILE__, 'nevronixai_plugin_activate');
function nevronixai_plugin_activate() {
    add_option('nevronixai_plugin_settings', array(
        'api_url' => '',
        'iframe_width' => '517',
        'iframe_height' => '517',
        'show_after_seconds' => '5',
        'selected_items' => array() // Store selected pages, posts, dynamic pages, or 'home'
    ));
}

// Register deactivation hook to clean up settings.
register_deactivation_hook(__FILE__, 'nevronixai_plugin_deactivate');
function nevronixai_plugin_deactivate() {
    delete_option('nevronixai_plugin_settings');
}

// Add a menu item in the admin dashboard.
add_action('admin_menu', 'nevronixai_plugin_menu');
function nevronixai_plugin_menu() {
    add_options_page('Digital Humans - NevronixAI Settings', 'Digital Humans - NevronixAI', 'manage_options', 'nevronixai-plugin', 'nevronixai_plugin_settings_page');
}

// Enqueue admin styles for the settings page
add_action('admin_enqueue_scripts', 'nevronixai_plugin_admin_enqueue_styles');
function nevronixai_plugin_admin_enqueue_styles($hook) {
    // Check if we're on the plugin's settings page
    if ($hook != 'settings_page_nevronixai-plugin') {
        return;
    }

    // Enqueue the style
    wp_register_style('nevronixai-plugin-admin-style', false, array(), NEVRONIXAI_PLUGIN_VERSION);
    wp_enqueue_style('nevronixai-plugin-admin-style');

    $custom_css = '
        .nevronix-logo-container {
            position: absolute;
        }
        .nevronix-logo {
            width: 200px;
        }
    ';
    wp_add_inline_style('nevronixai-plugin-admin-style', $custom_css);
}

// Display the settings page.
function nevronixai_plugin_settings_page() {
    if (isset($_POST['nevronixai_plugin_settings']) && check_admin_referer('nevronixai_plugin_update_options')) {
        $settings = array(
            'api_url' => isset($_POST['nevronixai_plugin_settings']['api_url']) 
                ? sanitize_text_field(wp_unslash($_POST['nevronixai_plugin_settings']['api_url'])) 
                : '',
            'iframe_width' => isset($_POST['nevronixai_plugin_settings']['iframe_width']) 
                ? absint(wp_unslash($_POST['nevronixai_plugin_settings']['iframe_width'])) 
                : 0,
            'iframe_height' => isset($_POST['nevronixai_plugin_settings']['iframe_height']) 
                ? absint(wp_unslash($_POST['nevronixai_plugin_settings']['iframe_height'])) 
                : 0,
            'show_after_seconds' => isset($_POST['nevronixai_plugin_settings']['show_after_seconds']) 
                ? absint(wp_unslash($_POST['nevronixai_plugin_settings']['show_after_seconds'])) 
                : 0,
            'selected_items' => isset($_POST['nevronixai_plugin_settings']['selected_items']) && is_array($_POST['nevronixai_plugin_settings']['selected_items']) 
                ? array_map('sanitize_text_field', wp_unslash($_POST['nevronixai_plugin_settings']['selected_items'])) 
                : array()
        );

        update_option('nevronixai_plugin_settings', $settings);

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $settings = get_option('nevronixai_plugin_settings');
    ?>
    <div class="wrap">
        <div class="nevronix-logo-container">
            <img src="<?php echo esc_url( plugin_dir_url(__FILE__) . 'img/logo.png' ); ?>" alt="Plugin Logo" class="nevronix-logo">
        </div>
        <br><br><br>
        <h1>NevronixAI Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('nevronixai_plugin_update_options'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API URL</th>
                    <td><input type="text" name="nevronixai_plugin_settings[api_url]" value="<?php echo esc_attr($settings['api_url']); ?>" size="100"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Width (px)</th>
                    <td><input type="text" name="nevronixai_plugin_settings[iframe_width]" value="<?php echo esc_attr($settings['iframe_width']); ?>" size="5"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Height (px)</th>
                    <td><input type="text" name="nevronixai_plugin_settings[iframe_height]" value="<?php echo esc_attr($settings['iframe_height']); ?>" size="5"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Show After (seconds)</th>
                    <td><input type="text" name="nevronixai_plugin_settings[show_after_seconds]" value="<?php echo esc_attr($settings['show_after_seconds']); ?>" size="5"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Select the pages where the plugin should be displayed:</th>
                    <td>
                        <?php
                        // Add option for the homepage
                        $selected_home = in_array('home', $settings['selected_items']) ? 'checked' : '';
                        echo '<label><input type="checkbox" name="nevronixai_plugin_settings[selected_items][]" value="home" ' . esc_attr($selected_home) . '> Home Page</label><br>';

                        // Fetch all pages
                        $pages = get_pages();
                        foreach ($pages as $page) {
                            $selected = in_array($page->ID, $settings['selected_items']) ? 'checked' : '';
                            echo '<label><input type="checkbox" name="nevronixai_plugin_settings[selected_items][]" value="' . esc_attr($page->ID) . '" ' . esc_attr($selected) . '> ' . esc_html($page->post_title) . ' (Page)</label><br>';
                        }

                        // Fetch all posts
                        $posts = get_posts(array('numberposts' => -1)); // No limit
                        foreach ($posts as $post) {
                            $selected = in_array($post->ID, $settings['selected_items']) ? 'checked' : '';
                            echo '<label><input type="checkbox" name="nevronixai_plugin_settings[selected_items][]" value="' . esc_attr($post->ID) . '" ' . esc_attr($selected) . '> ' . esc_html($post->post_title) . ' (Post)</label><br>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}

// Enqueue JavaScript and CSS on the front end
add_action('wp_enqueue_scripts', 'nevronixai_plugin_enqueue_scripts');
function nevronixai_plugin_enqueue_scripts() {
    $settings = get_option('nevronixai_plugin_settings');

    // Check if the current page/post/homepage is selected for the iframe
    $display_iframe = false;
    if (is_front_page() && in_array('home', $settings['selected_items']) ||
        (is_singular() && in_array(get_the_ID(), $settings['selected_items']))) {
        $display_iframe = true;
    }

    if ($display_iframe) {
        // Enqueue CSS
        wp_register_style('nevronixai-plugin-style', false, array(), NEVRONIXAI_PLUGIN_VERSION);
        wp_enqueue_style('nevronixai-plugin-style');

        $custom_css = '
            #iframeContainer {
                position: fixed;
                bottom: 0;
                right: 0;
                z-index: 99999;
                display: none;
            }
            #closeButton {
                position: absolute;
                top: 10px;
                right: 10px;
                background: none;
                border: none;
                cursor: pointer;
                z-index: 100000;
            }
            #closeButton svg {
                fill: #ffffff;
            }
            #nevronixFrame {
                position: absolute;
                bottom: 0;
                right: 0;
                width: ' . esc_attr($settings['iframe_width']) . 'px;
                height: ' . esc_attr($settings['iframe_height']) . 'px;
                border: none;
                max-width: 100%;
                border-radius: 25px;
                transition: transform 300ms ease-out, opacity 300ms ease-out;
                opacity: 0;
                transform: scale(0.8);
                transform-origin: bottom right;
            }
        ';
        wp_add_inline_style('nevronixai-plugin-style', $custom_css);

        // Enqueue JavaScript
        wp_register_script('nevronixai-plugin-script', '', array(), NEVRONIXAI_PLUGIN_VERSION, true);
        wp_enqueue_script('nevronixai-plugin-script');
        $custom_js = '
            function showIframe() {
                var iframeContainer = document.getElementById("iframeContainer");
                var iframe = document.getElementById("nevronixFrame");
                var closeButton = document.getElementById("closeButton");

                if (window.innerWidth <= 768) {
                    iframeContainer.style.width = "100%";
                    iframeContainer.style.height = "100%";
                    iframe.style.width = "100%";
                    iframe.style.height = "100%";
                } else {
                    iframeContainer.style.width = "' . esc_attr($settings['iframe_width']) . 'px";
                    iframeContainer.style.height = "' . esc_attr($settings['iframe_height']) . 'px";
                    iframe.style.width = "' . esc_attr($settings['iframe_width']) . 'px";
                    iframe.style.height = "' . esc_attr($settings['iframe_height']) . 'px";
                }

                iframe.style.opacity = "1";
                iframe.style.transform = "scale(1)";
                closeButton.style.display = "block";
                iframeContainer.style.display = "block";
            }

            function closeIframe() {
                var iframeContainer = document.getElementById("iframeContainer");
                iframeContainer.remove();
            }

            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(showIframe, ' . esc_js($settings['show_after_seconds'] * 1000) . ');
                document.getElementById("closeButton").addEventListener("click", closeIframe);
            });
        ';
        wp_add_inline_script('nevronixai-plugin-script', $custom_js);
    }
}

// Insert the iframe container into the footer
add_action('wp_footer', 'nevronixai_plugin_insert_iframe');
function nevronixai_plugin_insert_iframe() {
    $settings = get_option('nevronixai_plugin_settings');

    // Check if the current page/post/homepage is selected for the iframe
    if (is_front_page() && in_array('home', $settings['selected_items']) ||
        (is_singular() && in_array(get_the_ID(), $settings['selected_items']))) {
        ?>
        <div id="iframeContainer">
            <button id="closeButton">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                    <path d="M18.36 6.64a1.5 1.5 0 0 0-2.12 0L12 9.88 7.76 5.64a1.5 1.5 0 0 0-2.12 2.12L9.88 12 5.64 16.24a1.5 1.5 0 0 0 2.12 2.12L12 14.12l4.24 4.24a1.5 1.5 0 0 0 2.12-2.12L14.12 12l4.24-4.24a1.5 1.5 0 0 0 0-2.12z"/>
                </svg>
            </button>
            <iframe id="nevronixFrame" src="<?php echo esc_url($settings['api_url']); ?>" referrerpolicy="unsafe-url" sandbox="allow-scripts allow-same-origin allow-modals allow-top-navigation allow-popups allow-presentation allow-popups-to-escape-sandbox allow-forms" allow="camera; microphone; autoplay;"></iframe>
        </div>
        <?php
    }
}