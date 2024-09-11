<?php
/** 
 * Plugin Name:       Digital Humans - NevronixAI
 * Plugin URI:        https://www.nevronix.ai/
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

// Register activation hook to initialize settings.
register_activation_hook(__FILE__, 'custom_iframe_plugin_activate');
function custom_iframe_plugin_activate() {
    add_option('custom_iframe_plugin_settings', array(
        'api_url' => '',
        'iframe_width' => '400',
        'iframe_height' => '400',
        'show_after_seconds' => '10',
        'selected_items' => array() // Store selected pages, posts, dynamic pages, or 'home'
    ));
}

// Register deactivation hook to clean up settings.
register_deactivation_hook(__FILE__, 'custom_iframe_plugin_deactivate');
function custom_iframe_plugin_deactivate() {
    delete_option('custom_iframe_plugin_settings');
}

// Add a menu item in the admin dashboard.
add_action('admin_menu', 'custom_iframe_plugin_menu');
function custom_iframe_plugin_menu() {
    add_options_page('Digital Humans - NevronixAI Settings', 'Digital Humans - NevronixAI', 'manage_options', 'custom-iframe-plugin', 'custom_iframe_plugin_settings_page');
}

// Display the settings page.
function custom_iframe_plugin_settings_page() {
    if (isset($_POST['custom_iframe_plugin_settings']) && check_admin_referer('custom_iframe_plugin_update_options')) {
        $settings = array(
            'api_url' => isset($_POST['custom_iframe_plugin_settings']['api_url']) 
                ? sanitize_text_field(wp_unslash($_POST['custom_iframe_plugin_settings']['api_url'])) 
                : '',
            'iframe_width' => isset($_POST['custom_iframe_plugin_settings']['iframe_width']) 
                ? absint(wp_unslash($_POST['custom_iframe_plugin_settings']['iframe_width'])) 
                : 0,
            'iframe_height' => isset($_POST['custom_iframe_plugin_settings']['iframe_height']) 
                ? absint(wp_unslash($_POST['custom_iframe_plugin_settings']['iframe_height'])) 
                : 0,
            'show_after_seconds' => isset($_POST['custom_iframe_plugin_settings']['show_after_seconds']) 
                ? absint(wp_unslash($_POST['custom_iframe_plugin_settings']['show_after_seconds'])) 
                : 0,
            'selected_items' => isset($_POST['custom_iframe_plugin_settings']['selected_items']) && is_array($_POST['custom_iframe_plugin_settings']['selected_items']) 
                ? array_map('sanitize_text_field', wp_unslash($_POST['custom_iframe_plugin_settings']['selected_items'])) 
                : array()
        );


        update_option('custom_iframe_plugin_settings', $settings);

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $settings = get_option('custom_iframe_plugin_settings');
    ?>
    <div class="wrap">
        <div style="position: absolute; ">
            <img src="<?php echo esc_url( plugin_dir_url(__FILE__) . 'img/logo.png' ); ?>" alt="Plugin Logo" style="width: 200px;">
        </div>
    </br>
        </br>
            </br>
        <h1>NevronixAI Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('custom_iframe_plugin_update_options'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API URL</th>
                    <td><input type="text" name="custom_iframe_plugin_settings[api_url]" value="<?php echo esc_attr($settings['api_url']); ?>" size="100"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Width (px)</th>
                    <td><input type="text" name="custom_iframe_plugin_settings[iframe_width]" value="<?php echo esc_attr($settings['iframe_width']); ?>" size="5"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Height (px)</th>
                    <td><input type="text" name="custom_iframe_plugin_settings[iframe_height]" value="<?php echo esc_attr($settings['iframe_height']); ?>" size="5"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Show After (seconds)</th>
                    <td><input type="text" name="custom_iframe_plugin_settings[show_after_seconds]" value="<?php echo esc_attr($settings['show_after_seconds']); ?>" size="5"></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Select the pages where the plugin should be displayed. Hold Shift and click to select multiple pages.</th>
                    <td>
                        <select name="custom_iframe_plugin_settings[selected_items][]" multiple="multiple" size="15">
                            <?php
                            // Add option for the homepage
                            $selected_home = in_array('home', $settings['selected_items']) ? 'selected="selected"' : '';
                            echo '<option value="home" ' . esc_attr( $selected_home ) . '>Home Page</option>';

                            // Fetch all pages
                            $pages = get_pages();
                            foreach ($pages as $page) {
                                $selected = in_array($page->ID, $settings['selected_items']) ? 'selected="selected"' : '';
                                echo '<option value="' . esc_attr( $page->ID ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $page->post_title ) . ' (Page)</option>';
                            }

                            // Fetch all posts
                            $posts = get_posts(array('numberposts' => -1)); // No limit
                            foreach ($posts as $post) {
                                $selected = in_array($post->ID, $settings['selected_items']) ? 'selected="selected"' : '';
                                echo '<option value="' . esc_attr( $post->ID ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $post->post_title ) . ' (Post)</option>';
                            }
                            ?>
                        </select>
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

// Insert the iframe into the selected pages, posts, or homepage.
add_action('wp_footer', 'custom_iframe_plugin_insert_iframe');
function custom_iframe_plugin_insert_iframe() {
    $settings = get_option('custom_iframe_plugin_settings');

    // Check if the current page/post/homepage is selected for the iframe
    if (is_front_page() && in_array('home', $settings['selected_items']) ||
        (is_singular() && in_array(get_the_ID(), $settings['selected_items']))) {
        ?>
        <div id="iframeContainer" style="position: fixed; bottom: 0; right: 0; z-index: 99999; display: none;">
            <button id="closeButton" style="position: absolute; top: 10px; right: 10px; background: none; border: none; cursor: pointer; z-index: 100000;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#ffffff">
                    <path d="M18.36 6.64a1.5 1.5 0 0 0-2.12 0L12 9.88 7.76 5.64a1.5 1.5 0 0 0-2.12 2.12L9.88 12 5.64 16.24a1.5 1.5 0 0 0 2.12 2.12L12 14.12l4.24 4.24a1.5 1.5 0 0 0 2.12-2.12L14.12 12l4.24-4.24a1.5 1.5 0 0 0 0-2.12z"/>
                </svg>
            </button>
            <iframe id="nevronixFrame" src="<?php echo esc_url($settings['api_url']); ?>" referrerpolicy="unsafe-url" sandbox="allow-scripts allow-same-origin allow-modals allow-top-navigation allow-popups allow-presentation allow-popups-to-escape-sandbox allow-forms" allow="camera;microphone;autoplay;" style="position: absolute; bottom: 0; right: 0; width: <?php echo esc_attr($settings['iframe_width']); ?>px; height: <?php echo esc_attr($settings['iframe_height']); ?>px; border: none; max-width: 100%; border-radius: 25px; transition: transform 300ms ease-out, opacity 300ms ease-out; opacity: 0; transform: scale(0.8); transform-origin: bottom right;">
            </iframe>
        </div>
        <script>
            function showIframe() {
                var iframeContainer = document.getElementById('iframeContainer');
                var iframe = document.getElementById('nevronixFrame');
                var closeButton = document.getElementById('closeButton');

                if (window.innerWidth <= 768) {
                    iframeContainer.style.width = '100%';
                    iframeContainer.style.height = '100%';
                } else {
                    iframeContainer.style.width = '<?php echo esc_attr($settings['iframe_width']); ?>px';
                    iframeContainer.style.height = '<?php echo esc_attr($settings['iframe_height']); ?>px';
                }

                iframe.style.opacity = '1';
                iframe.style.transform = 'scale(1)';
                closeButton.style.display = 'block';
                iframeContainer.style.display = 'block';
            }

            function closeIframe() {
                var iframeContainer = document.getElementById('iframeContainer');
                iframeContainer.remove();
            }

            setTimeout(showIframe, <?php echo esc_js($settings['show_after_seconds'] * 1000); ?>);
            document.getElementById('closeButton').addEventListener('click', closeIframe);
        </script>
        <?php
    }
}
