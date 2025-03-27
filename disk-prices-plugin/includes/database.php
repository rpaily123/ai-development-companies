<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Create the disk prices table
 */
function disk_prices_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'disk_prices';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        price_per_tb decimal(10,2) NOT NULL,
        price decimal(10,2) NOT NULL,
        capacity varchar(20) NOT NULL,
        warranty varchar(50) NOT NULL,
        form_factor varchar(50) NOT NULL,
        technology varchar(20) NOT NULL,
        condition_status varchar(20) NOT NULL,
        affiliate_link text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Import data from Excel file
 */
function disk_prices_import_data($file) {
    // Check if PhpSpreadsheet is installed
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        // Try to include PhpSpreadsheet
        if (file_exists(DISK_PRICES_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once DISK_PRICES_PLUGIN_DIR . 'vendor/autoload.php';
        } else {
            return new WP_Error('missing_dependency', __('PhpSpreadsheet library is not installed. Please install it using Composer.', 'disk-prices'));
        }
    }

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Remove header row
        array_shift($rows);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'disk_prices';
        
        // Clear existing data
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        // Prepare insert statement
        $insert_query = "INSERT INTO $table_name (price_per_tb, price, capacity, warranty, form_factor, technology, condition_status, affiliate_link) VALUES ";
        $values = array();
        $batch_size = 100;
        $total_rows = count($rows);
        $processed_rows = 0;
        
        foreach ($rows as $row) {
            if (empty($row[0])) continue; // Skip empty rows
            
            // Clean and validate data
            $price_per_tb = is_numeric($row[0]) ? floatval($row[0]) : 0;
            $price = is_numeric($row[1]) ? floatval($row[1]) : 0;
            $capacity = sanitize_text_field($row[2]);
            $warranty = sanitize_text_field($row[3]);
            $form_factor = sanitize_text_field($row[4]);
            $technology = sanitize_text_field($row[5]);
            $condition = sanitize_text_field($row[6]);
            $affiliate_link = esc_url_raw($row[7]);
            
            // Skip invalid rows
            if ($price <= 0 || empty($capacity) || empty($affiliate_link)) {
                continue;
            }
            
            $values[] = $wpdb->prepare(
                "(%f, %f, %s, %s, %s, %s, %s, %s)",
                $price_per_tb,
                $price,
                $capacity,
                $warranty,
                $form_factor,
                $technology,
                $condition,
                $affiliate_link
            );
            
            // Insert in batches to avoid memory issues
            if (count($values) >= $batch_size) {
                $insert_query .= implode(', ', $values);
                $wpdb->query($insert_query);
                $values = array();
                $processed_rows += $batch_size;
                
                // Update progress
                if (function_exists('wp_update_plugins')) {
                    wp_update_plugins();
                }
            }
        }
        
        // Insert remaining values
        if (!empty($values)) {
            $insert_query .= implode(', ', $values);
            $wpdb->query($insert_query);
            $processed_rows += count($values);
        }
        
        // Log import results
        error_log(sprintf(
            'Disk Prices Import: Processed %d rows out of %d total rows',
            $processed_rows,
            $total_rows
        ));
        
        return true;
    } catch (Exception $e) {
        error_log('Disk Prices Import Error: ' . $e->getMessage());
        return new WP_Error('import_error', $e->getMessage());
    }
}

/**
 * Get filtered disk prices
 */
function disk_prices_get_filtered_results($args = array()) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'disk_prices';
    
    $defaults = array(
        'capacity' => '',
        'technology' => '',
        'price_min' => '',
        'price_max' => '',
        'condition' => '',
        'warranty' => '',
        'orderby' => 'price',
        'order' => 'ASC',
        'limit' => 10,
        'offset' => 0
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $where = array('1=1');
    $values = array();
    
    if (!empty($args['capacity'])) {
        $where[] = 'capacity = %s';
        $values[] = $args['capacity'];
    }
    
    if (!empty($args['technology'])) {
        $where[] = 'technology = %s';
        $values[] = $args['technology'];
    }
    
    if (!empty($args['price_min'])) {
        $where[] = 'price >= %f';
        $values[] = floatval($args['price_min']);
    }
    
    if (!empty($args['price_max'])) {
        $where[] = 'price <= %f';
        $values[] = floatval($args['price_max']);
    }
    
    if (!empty($args['condition'])) {
        $where[] = 'condition_status = %s';
        $values[] = $args['condition'];
    }
    
    if (!empty($args['warranty'])) {
        $where[] = 'warranty = %s';
        $values[] = $args['warranty'];
    }
    
    $where_clause = implode(' AND ', $where);
    $order_clause = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
    
    $query = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE $where_clause ORDER BY $order_clause LIMIT %d OFFSET %d",
        array_merge($values, array($args['limit'], $args['offset']))
    );
    
    return $wpdb->get_results($query);
}

/**
 * Get unique values for filters
 */
function disk_prices_get_filter_options($field) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'disk_prices';
    
    $allowed_fields = array('capacity', 'technology', 'condition_status', 'warranty');
    if (!in_array($field, $allowed_fields)) {
        return array();
    }
    
    $query = $wpdb->prepare(
        "SELECT DISTINCT $field FROM $table_name ORDER BY $field ASC"
    );
    
    return $wpdb->get_col($query);
} 