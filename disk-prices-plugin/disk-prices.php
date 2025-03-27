<?php
/**
 * Plugin Name: Disk Prices Comparison
 * Description: A plugin that allows users to filter and compare disk prices with affiliate links.
 * Version: 1.0.0
 * Author: Robin Mathew
 * Text Domain: disk-prices
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('DISK_PRICES_VERSION', '1.0.0');
define('DISK_PRICES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DISK_PRICES_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once DISK_PRICES_PLUGIN_DIR . 'includes/database.php';
require_once DISK_PRICES_PLUGIN_DIR . 'includes/shortcode.php';

// Plugin activation hook
register_activation_hook(__FILE__, 'disk_prices_activate');
function disk_prices_activate() {
    // Create database table
    disk_prices_create_table();
    
    // Set default options
    add_option('disk_prices_settings', array(
        'items_per_page' => 10,
        'default_sort' => 'price_asc',
        'enable_ajax' => true
    ));
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'disk_prices_deactivate');
function disk_prices_deactivate() {
    flush_rewrite_rules();
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'disk_prices_enqueue_scripts');
function disk_prices_enqueue_scripts() {
    wp_enqueue_style(
        'disk-prices-style',
        DISK_PRICES_PLUGIN_URL . 'assets/css/style.css',
        array(),
        DISK_PRICES_VERSION
    );

    wp_enqueue_script(
        'disk-prices-script',
        DISK_PRICES_PLUGIN_URL . 'assets/js/script.js',
        array('jquery'),
        DISK_PRICES_VERSION,
        true
    );

    wp_localize_script('disk-prices-script', 'diskPricesAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('disk_prices_nonce')
    ));
}

// Add admin menu
add_action('admin_menu', 'disk_prices_admin_menu');
function disk_prices_admin_menu() {
    add_menu_page(
        __('Disk Prices', 'disk-prices'),
        __('Disk Prices', 'disk-prices'),
        'manage_options',
        'disk-prices',
        'disk_prices_admin_page',
        'dashicons-hd',
        30
    );
}

// Handle Excel file import
add_action('admin_init', 'disk_prices_handle_import');
function disk_prices_handle_import() {
    if (!isset($_POST['disk_prices_import']) || !isset($_FILES['disk_prices_file'])) {
        return;
    }

    if (!check_admin_referer('disk_prices_import', 'disk_prices_nonce')) {
        wp_die(__('Invalid nonce', 'disk-prices'));
    }

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'disk-prices'));
    }

    $file = $_FILES['disk_prices_file'];
    
    // Check file type
    $allowed_types = array(
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv'
    );
    
    if (!in_array($file['type'], $allowed_types)) {
        add_settings_error(
            'disk_prices_messages',
            'disk_prices_import_error',
            __('Invalid file type. Please upload an Excel or CSV file.', 'disk-prices'),
            'error'
        );
        return;
    }

    // Import data
    $result = disk_prices_import_data($file);
    
    if (is_wp_error($result)) {
        add_settings_error(
            'disk_prices_messages',
            'disk_prices_import_error',
            $result->get_error_message(),
            'error'
        );
    } else {
        add_settings_error(
            'disk_prices_messages',
            'disk_prices_import_success',
            __('Data imported successfully!', 'disk-prices'),
            'success'
        );
    }
}

// Admin page callback
function disk_prices_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Show settings errors
    settings_errors('disk_prices_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="disk-prices-admin-content">
            <h2><?php _e('Import Data', 'disk-prices'); ?></h2>
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('disk_prices_import', 'disk_prices_nonce'); ?>
                <p><?php _e('Upload your Excel file containing disk prices data. The file should have the following columns:', 'disk-prices'); ?></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><?php _e('Price per TB', 'disk-prices'); ?></li>
                    <li><?php _e('Price', 'disk-prices'); ?></li>
                    <li><?php _e('Capacity', 'disk-prices'); ?></li>
                    <li><?php _e('Warranty', 'disk-prices'); ?></li>
                    <li><?php _e('Form Factor', 'disk-prices'); ?></li>
                    <li><?php _e('Technology', 'disk-prices'); ?></li>
                    <li><?php _e('Condition', 'disk-prices'); ?></li>
                    <li><?php _e('Affiliate Link', 'disk-prices'); ?></li>
                </ul>
                <p>
                    <input type="file" name="disk_prices_file" accept=".xlsx,.xls,.csv" required>
                    <input type="submit" name="disk_prices_import" class="button button-primary" value="<?php _e('Import Data', 'disk-prices'); ?>">
                </p>
            </form>
            
            <h2><?php _e('Settings', 'disk-prices'); ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('disk_prices_settings');
                do_settings_sections('disk_prices_settings');
                submit_button();
                ?>
            </form>
        </div>
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'disk_prices_register_settings');
function disk_prices_register_settings() {
    register_setting('disk_prices_settings', 'disk_prices_settings');
    
    add_settings_section(
        'disk_prices_main_section',
        __('Main Settings', 'disk-prices'),
        'disk_prices_section_callback',
        'disk_prices_settings'
    );
    
    add_settings_field(
        'items_per_page',
        __('Items per Page', 'disk-prices'),
        'disk_prices_items_per_page_callback',
        'disk_prices_settings',
        'disk_prices_main_section'
    );
}

function disk_prices_section_callback() {
    echo '<p>' . esc_html__('Configure the main settings for the Disk Prices plugin.', 'disk-prices') . '</p>';
}

function disk_prices_items_per_page_callback() {
    $options = get_option('disk_prices_settings');
    $items_per_page = isset($options['items_per_page']) ? $options['items_per_page'] : 10;
    ?>
    <input type="number" name="disk_prices_settings[items_per_page]" value="<?php echo esc_attr($items_per_page); ?>" min="1" max="100">
    <?php
} 