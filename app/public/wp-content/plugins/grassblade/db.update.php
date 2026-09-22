<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GrassBlade_DB {
	public $db_version;

	function __construct() {
		if(is_admin())
		add_action( 'plugins_loaded', array($this, 'check_n_upgrade_db'));
	//	add_action("init", array($this, "upgrade_questions_db"));
	}
	function db_version() {
		if( empty($this->db_version) ) {
			global $wpdb;
			$this->db_version = $wpdb->get_var("SELECT @@GLOBAL.version");
		}
		return $this->db_version;
	}
	function db_version_no() {
		$version = explode("-", $this->db_version());
		return $version[0];
	}
	function db_type() {
		if(strpos($this->db_version(), "MariaDB"))
			return "mariadb";
		else
			return "mysql";
	}
	function get_version() {
		return get_option("grassblade_version");
	}
	function update_version() {
		update_option("grassblade_version", GRASSBLADE_VERSION);
	}
	function check_n_upgrade_db() {
		$current_db_version = $this->get_version();
		if(version_compare($current_db_version, GRASSBLADE_VERSION) < 0) {
			$this->upgrade_db( $current_db_version );
			$this->upgrade_scorm_db( $current_db_version );
			$this->upgrade_questions_db( $current_db_version );
			$this->update_version();
		}
	}
	function upgrade_db( $current_db_version = "" ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "grassblade_completions";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );


		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			content_id int(11) NOT NULL,
			user_id int(11) NOT NULL,
			status varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
			percentage float DEFAULT NULL,
			score float DEFAULT NULL,
			timespent int(11) DEFAULT NULL,
			statement text COLLATE utf8_unicode_ci,
			registration varchar(255) DEFAULT NULL,
			timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
			) $charset_collate";
		dbDelta($sql);
		$this->run_upgrade_query( $current_db_version );
	}
	function run_upgrade_query( $current_db_version = "" ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "grassblade_completions";

		if( version_compare($current_db_version, "3.3.6") < 0 ) {
			if( $this->db_type() == "mysql" && version_compare($this->db_version_no(), "5.7.8", ">=") || $this->db_type() == "mariadb" && version_compare($this->db_version_no(), "10.2.3", ">=") ) {
				$query = 'UPDATE '.$table_name.' SET registration = JSON_UNQUOTE(JSON_EXTRACT(statement, "$.context.registration"))';
				$wpdb->query($query);
			}
		}
	}
	function upgrade_scorm_db( $current_db_version = "" ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "grassblade_scorm_data";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			content_id int(11) NOT NULL,
			user_id int(11) NOT NULL,
			registration_id varchar(255) DEFAULT NULL,
			var_key varchar(255) NOT NULL,
			var_value text NOT NULL,
			timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_content_reg (content_id, user_id, registration_id(4))
			) $charset_collate";
		dbDelta($sql);
	}

	function upgrade_questions_db( $current_db_version = "" ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "grassblade_questions";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
				`ID` int(11) NOT NULL AUTO_INCREMENT,
				`post_id` int(11) NOT NULL DEFAULT '0',
				`question` text,
				`question_type` varchar(16) DEFAULT NULL,
				`answer_settings` text,
				`question_settings` text,
				`question_order` int(4) NOT NULL DEFAULT '0',
				`created` timestamp NULL,
				`updated` timestamp NULL,
				PRIMARY KEY (`ID`)
			) $charset_collate";
		dbDelta($sql);
	}
}
