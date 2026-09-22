<?php

namespace StellarWP\PluginFramework\Support;

use WC_Report_Sales_By_Date;

class WooCommerceMonitor
{
    const WC_REST_SYSTEM_STATUS_CONTROLLER = '\WC_REST_System_Status_V2_Controller';

    /**
     * Collect WooCommerce metrics for the given year.
     *
     * @param int  $year The year for which to retrieve stats.
     * @param bool $include_product_count True if product count needed. False otherwise.
     *
     * @return array{
     *       order_count: int,
     *       revenue: string,
     *       products?: int,
     *   } Returns an array containing the order count, number of products sold, and total revenue.
     */
    public function getWooCommerceStatsForYear($year, $include_product_count = false)
    {
        return $this->getWooCommerceStats(
            "$year-01-01",
            "$year-12-31",
            'YEAR(posts.post_date)',
            $include_product_count
        );
    }

    /**
     * Collect WooCommerce metrics for the given month.
     *
     * @param int  $year The year for which to retrieve stats.
     * @param int  $month The month for which to retrieve stats.
     * @param bool $include_product_count True if product count needed. False otherwise.
     *
     * @return array{
     *       order_count: int,
     *       revenue: string,
     *       products?: int,
     *   } Returns an array containing the order count, number of products sold, and total revenue.
     */
    public function getWooCommerceStatsForMonth($year, $month, $include_product_count = false)
    {
        $start_date = "$year-$month-01";

        return $this->getWooCommerceStats(
            $start_date,
            "$year-$month-" . (int) gmdate('t', (int) strtotime($start_date)),
            'YEAR(posts.post_date), MONTH(posts.post_date)',
            $include_product_count
        );
    }

    /**
     * Retrieves WooCommerce statistics based on a custom date range and grouping.
     * Utilizes WooCommerce reporting classes to fetch and aggregate sales data.
     *
     * @param string $start_date Start date of the reporting period in 'YYYY-MM-DD' format.
     * @param string $end_date End date of the reporting period in 'YYYY-MM-DD' format.
     * @param string $group_by_query SQL clause to define how data should be grouped (e.g., by year, month).
     * @param bool   $include_product_count True if product count needed. False otherwise.
     *
     * @return array{
     *      order_count: int,
     *      revenue: string,
     *      products?: int,
     *  } Returns an array containing the order count, number of products sold, and total revenue.
     */
    protected function getWooCommerceStats($start_date, $end_date, $group_by_query, $include_product_count = false)
    {
        require_once WP_PLUGIN_DIR . '/woocommerce/includes/admin/reports/class-wc-admin-report.php';
        require_once WP_PLUGIN_DIR . '/woocommerce/includes/admin/reports/class-wc-report-sales-by-date.php';

        $report = new WC_Report_Sales_By_Date();

        $report->start_date     = (int) $start_date;
        $report->end_date       = (int) $end_date;
        $report->group_by_query = $group_by_query;

        $report_data = $report->get_report_data();

        $stats = [
            'order_count' => isset($report_data->total_orders) ? $report_data->total_orders : 0,
            'revenue'     => isset($report_data->total_sales) ? $report_data->total_sales : ''
        ];

        if ($include_product_count) {
            $products = wc_get_products([
                'return'       => 'ids',
                'limit'        => - 1,
                'date_created' => sprintf('%s...%s', $start_date, $end_date),
            ]);
            $stats['products'] = is_countable($products) ? count($products) : 0;
        }

        return $stats;
    }

    /**
     * Total number of published products.
     *
     * @return int
     */
    public function getPublishedProductsCount()
    {
        return (int) wp_count_posts('product')->publish;
    }

    /**
     * Get database size according to the internal WooCommerce functionality.
     *
     * @return string
     */
    public function getDatabaseTotalSize()
    {
        if (! class_exists($this->getStatusControllerClassname())) {
            return '';
        }

        $controller_classname = $this->getStatusControllerClassname();

        $controller = new $controller_classname();
        if (! method_exists($controller, 'get_database_info')) {
            return '';
        }

        $database = $controller->get_database_info();
        if (empty($database['database_size']['data']) || empty($database['database_size']['index'])) {
            return '';
        }

        return sprintf(
            '%.2fMB',
            esc_html((string) ($database['database_size']['data'] + $database['database_size']['index']))
        );
    }

    /**
     *
     * @return string
     */
    protected function getStatusControllerClassname()
    {
        return self::WC_REST_SYSTEM_STATUS_CONTROLLER;
    }
}
