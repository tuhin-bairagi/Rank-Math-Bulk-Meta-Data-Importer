<?php
/*
Plugin Name: Rank Math Bulk Meta Data Importer
Description: Import and update Meta Title and Meta Description fields for specific URLs using the Rank Math plugin via CSV upload.
Version: 1.0.0
Author: Tuhin Bairagi
Author URI:  https://tuhinbairagi.com/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RankMathCSVImporter {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_import_csv', [ $this, 'process_csv_import' ] );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Rank Math CSV Importer',
            'Rank Math CSV Importer',
            'manage_options',
            'rank-math-csv-importer',
            [ $this, 'render_admin_page' ]
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'tools_page_rank-math-csv-importer' ) {
            return;
        }

        wp_enqueue_script(
            'rank-math-csv-importer',
            plugin_dir_url( __FILE__ ) . 'assets/js/importer.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'rank-math-csv-importer-style',
            plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
            [],
            '1.0.0'
        );

        wp_localize_script('rank-math-csv-importer', 'importerAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rank_math_import_nonce')
        ]);
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Rank Math CSV Importer</h1>
            <form id="csv-import-form" enctype="multipart/form-data">
                <input type="file" name="csv_file" id="csv-file" accept=".csv" required>
                <button type="submit" class="button button-primary">Upload and Import</button>
            </form>
            <div id="progress-container" style="display:none;">
                <div id="progress-bar" style="width:0%;">0%</div>
            </div>
            <div id="import-log"></div>
        </div>
        <?php
    }

    public function process_csv_import() {
        check_ajax_referer( 'rank_math_import_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ] );
        }

        if ( empty( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( [ 'message' => 'File upload failed.' ] );
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $csv_data = array_map( 'str_getcsv', file( $file ) );

        if ( count( $csv_data ) < 2 ) {
            wp_send_json_error( [ 'message' => 'Invalid CSV format.' ] );
        }

        $header = array_map( 'trim', $csv_data[0] );
        if ( $header !== [ 'URLs', 'Meta Title', 'Meta Description' ] ) {
            wp_send_json_error( [ 'message' => 'CSV header format is incorrect.' ] );
        }

        unset( $csv_data[0] );

        $success_count = 0;
        $error_log = [];

        foreach ( $csv_data as $row ) {
            $url = trim( $row[0] );
            $meta_title = trim( $row[1] );
            $meta_description = trim( $row[2] );

            $post_id = url_to_postid( $url );

            if ( ! $post_id ) {
                $error_log[] = "URL not found: $url";
                continue;
            }

            update_post_meta( $post_id, 'rank_math_title', $meta_title );
            update_post_meta( $post_id, 'rank_math_description', $meta_description );
            $success_count++;
        }

        wp_send_json_success([
            'success_count' => $success_count,
            'errors' => $error_log
        ]);
    }
}

new RankMathCSVImporter();

// Create assets/js/importer.js
// Create assets/css/style.css

