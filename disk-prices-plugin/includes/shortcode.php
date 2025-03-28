<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register the disk prices shortcode
 */
add_shortcode('disk_prices', 'disk_prices_shortcode');

/**
 * Shortcode callback function
 */
function disk_prices_shortcode($atts) {
    // Enqueue required scripts and styles
    wp_enqueue_style('disk-prices-style');
    wp_enqueue_script('disk-prices-script');
    
    // Get filter options
    $capacities = disk_prices_get_filter_options('capacity');
    $technologies = disk_prices_get_filter_options('technology');
    $conditions = disk_prices_get_filter_options('condition_status');
    $warranties = disk_prices_get_filter_options('warranty');
    
    // Get current filter values
    $current_capacity = isset($_GET['capacity']) ? sanitize_text_field($_GET['capacity']) : '';
    $current_technology = isset($_GET['technology']) ? sanitize_text_field($_GET['technology']) : '';
    $current_condition = isset($_GET['condition']) ? sanitize_text_field($_GET['condition']) : '';
    $current_warranty = isset($_GET['warranty']) ? sanitize_text_field($_GET['warranty']) : '';
    $current_price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : '';
    $current_price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : '';
    
    ob_start();
    ?>
    <div class="disk-prices-container">
        <form id="disk-prices-filter" class="disk-prices-filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="capacity"><?php _e('Capacity', 'disk-prices'); ?></label>
                    <select name="capacity" id="capacity">
                        <option value=""><?php _e('All Capacities', 'disk-prices'); ?></option>
                        <?php foreach ($capacities as $capacity) : ?>
                            <option value="<?php echo esc_attr($capacity); ?>" <?php selected($current_capacity, $capacity); ?>>
                                <?php echo esc_html($capacity); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="technology"><?php _e('Technology', 'disk-prices'); ?></label>
                    <select name="technology" id="technology">
                        <option value=""><?php _e('All Technologies', 'disk-prices'); ?></option>
                        <?php foreach ($technologies as $technology) : ?>
                            <option value="<?php echo esc_attr($technology); ?>" <?php selected($current_technology, $technology); ?>>
                                <?php echo esc_html($technology); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="condition"><?php _e('Condition', 'disk-prices'); ?></label>
                    <select name="condition" id="condition">
                        <option value=""><?php _e('All Conditions', 'disk-prices'); ?></option>
                        <?php foreach ($conditions as $condition) : ?>
                            <option value="<?php echo esc_attr($condition); ?>" <?php selected($current_condition, $condition); ?>>
                                <?php echo esc_html($condition); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="warranty"><?php _e('Warranty', 'disk-prices'); ?></label>
                    <select name="warranty" id="warranty">
                        <option value=""><?php _e('All Warranties', 'disk-prices'); ?></option>
                        <?php foreach ($warranties as $warranty) : ?>
                            <option value="<?php echo esc_attr($warranty); ?>" <?php selected($current_warranty, $warranty); ?>>
                                <?php echo esc_html($warranty); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group price-range">
                    <label><?php _e('Price Range', 'disk-prices'); ?></label>
                    <div class="price-inputs">
                        <input type="number" name="price_min" id="price_min" placeholder="<?php _e('Min', 'disk-prices'); ?>" value="<?php echo esc_attr($current_price_min); ?>">
                        <span><?php _e('to', 'disk-prices'); ?></span>
                        <input type="number" name="price_max" id="price_max" placeholder="<?php _e('Max', 'disk-prices'); ?>" value="<?php echo esc_attr($current_price_max); ?>">
                    </div>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="disk-prices-submit"><?php _e('Apply Filters', 'disk-prices'); ?></button>
                </div>
            </div>
        </form>
        
        <div id="disk-prices-results" class="disk-prices-results">
            <?php
            // Get filtered results
            $results = disk_prices_get_filtered_results(array(
                'capacity' => $current_capacity,
                'technology' => $current_technology,
                'condition' => $current_condition,
                'warranty' => $current_warranty,
                'price_min' => $current_price_min,
                'price_max' => $current_price_max
            ));
            
            if (!empty($results)) :
            ?>
                <table class="disk-prices-table">
                    <thead>
                        <tr>
                            <th><?php _e('Capacity', 'disk-prices'); ?></th>
                            <th><?php _e('Technology', 'disk-prices'); ?></th>
                            <th><?php _e('Price', 'disk-prices'); ?></th>
                            <th><?php _e('Price/TB', 'disk-prices'); ?></th>
                            <th><?php _e('Warranty', 'disk-prices'); ?></th>
                            <th><?php _e('Condition', 'disk-prices'); ?></th>
                            <th><?php _e('Action', 'disk-prices'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $disk) : ?>
                            <tr>
                                <td><?php echo esc_html($disk->capacity); ?></td>
                                <td>
                                    <span class="technology-badge technology-<?php echo esc_attr(strtolower($disk->technology)); ?>">
                                        <?php echo esc_html($disk->technology); ?>
                                    </span>
                                </td>
                                <td class="price-cell"><?php echo esc_html(number_format($disk->price, 2)); ?></td>
                                <td class="price-cell"><?php echo esc_html(number_format($disk->price_per_tb, 2)); ?></td>
                                <td><?php echo esc_html($disk->warranty); ?></td>
                                <td><?php echo esc_html($disk->condition_status); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($disk->affiliate_link); ?>" class="disk-prices-buy-button" target="_blank" rel="nofollow">
                                        <?php _e('Buy Now', 'disk-prices'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="no-results"><?php _e('No disks found matching your criteria.', 'disk-prices'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * AJAX handler for filter updates
 */
add_action('wp_ajax_disk_prices_filter', 'disk_prices_ajax_filter');
add_action('wp_ajax_nopriv_disk_prices_filter', 'disk_prices_ajax_filter');

function disk_prices_ajax_filter() {
    check_ajax_referer('disk_prices_nonce', 'nonce');
    
    $args = array(
        'capacity' => isset($_POST['capacity']) ? sanitize_text_field($_POST['capacity']) : '',
        'technology' => isset($_POST['technology']) ? sanitize_text_field($_POST['technology']) : '',
        'condition' => isset($_POST['condition']) ? sanitize_text_field($_POST['condition']) : '',
        'warranty' => isset($_POST['warranty']) ? sanitize_text_field($_POST['warranty']) : '',
        'price_min' => isset($_POST['price_min']) ? floatval($_POST['price_min']) : '',
        'price_max' => isset($_POST['price_max']) ? floatval($_POST['price_max']) : ''
    );
    
    $results = disk_prices_get_filtered_results($args);
    
    ob_start();
    if (!empty($results)) :
    ?>
        <table class="disk-prices-table">
            <thead>
                <tr>
                    <th><?php _e('Capacity', 'disk-prices'); ?></th>
                    <th><?php _e('Technology', 'disk-prices'); ?></th>
                    <th><?php _e('Price', 'disk-prices'); ?></th>
                    <th><?php _e('Price/TB', 'disk-prices'); ?></th>
                    <th><?php _e('Warranty', 'disk-prices'); ?></th>
                    <th><?php _e('Condition', 'disk-prices'); ?></th>
                    <th><?php _e('Action', 'disk-prices'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $disk) : ?>
                    <tr>
                        <td><?php echo esc_html($disk->capacity); ?></td>
                        <td>
                            <span class="technology-badge technology-<?php echo esc_attr(strtolower($disk->technology)); ?>">
                                <?php echo esc_html($disk->technology); ?>
                            </span>
                        </td>
                        <td class="price-cell"><?php echo esc_html(number_format($disk->price, 2)); ?></td>
                        <td class="price-cell"><?php echo esc_html(number_format($disk->price_per_tb, 2)); ?></td>
                        <td><?php echo esc_html($disk->warranty); ?></td>
                        <td><?php echo esc_html($disk->condition_status); ?></td>
                        <td>
                            <a href="<?php echo esc_url($disk->affiliate_link); ?>" class="disk-prices-buy-button" target="_blank" rel="nofollow">
                                <?php _e('Buy Now', 'disk-prices'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p class="no-results"><?php _e('No disks found matching your criteria.', 'disk-prices'); ?></p>
    <?php endif;
    
    wp_send_json_success(array(
        'html' => ob_get_clean()
    ));
} 