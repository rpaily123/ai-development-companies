# Disk Prices Comparison WordPress Plugin

A WordPress plugin that allows users to filter and compare disk prices with affiliate links.

## Features

- Import disk prices from Excel/CSV files
- Filter disks by:
  - Capacity
  - Technology (HDD, SSD, SAS)
  - Price range
  - Condition
  - Warranty
- Real-time AJAX filtering
- Responsive design
- Affiliate link integration
- Price per TB calculation

## Installation

1. Upload the `disk-prices-plugin` folder to the `/wp-content/plugins/` directory
2. Install Composer if you haven't already (https://getcomposer.org/)
3. Navigate to the plugin directory in your terminal:
   ```bash
   cd wp-content/plugins/disk-prices-plugin
   ```
4. Install dependencies:
   ```bash
   composer install
   ```
5. Activate the plugin through the 'Plugins' menu in WordPress
6. Go to the 'Disk Prices' menu in WordPress admin
7. Upload your Excel file with disk prices data

## Excel File Format

Your Excel file should have the following columns in order:

1. Price per TB
2. Price
3. Capacity
4. Warranty
5. Form Factor
6. Technology
7. Condition
8. Affiliate Link

## Usage

1. Add the shortcode `[disk_prices]` to any page or post where you want to display the disk prices comparison
2. Users can use the filters to find the best disk for their needs
3. Click the "Buy Now" button to go to the affiliate link

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Composer (for dependency management)
- PhpSpreadsheet library (installed via Composer)

## Support

For support, please create an issue in the plugin's repository or contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Created by Robin Mathew 