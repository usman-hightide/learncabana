<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_bulk_import {

	public $fields;
	public $grassblade_xapi_content;
	function __construct() {
		add_action('admin_menu', array($this, 'menu'), 1);

		add_action("admin_init", array($this, "scripts"));
		add_action("init", array($this, "csv_settings_process"));

		add_filter( 'grassblade_add_scripts_on_page', array($this, 'add_to_scripts') );
		add_action( 'wp_ajax_grassblade_all_settings', array($this, 'show_grassblade_all_settings') );
		add_action( 'wp_ajax_gb_folder_delete', array($this, 'gb_folder_delete') );
		add_action( 'manage_posts_extra_tablenav', array($this, "posts_extra_tablenav"), 10, 1);
	}
	function add_to_scripts($grassblade_add_scripts_on_page) {
		$grassblade_add_scripts_on_page[] = "grassblade-bulk-settings";
		$grassblade_add_scripts_on_page[] = "grassblade-bulk-import";
		return $grassblade_add_scripts_on_page;
	}
	function scripts() {
		global $pagenow;
		if( is_admin() && !empty($_GET["page"]) && in_array($_GET["page"], array("grassblade-bulk-import", "grassblade-bulk-settings")) ) {
			wp_enqueue_script(
				'grassblade-bulk-import',
				plugins_url( 'script.js', __FILE__),
				array('jquery'), null
			);
		}
	}
	function posts_extra_tablenav($which) {
		if(!empty($_GET["post_type"]) && $_GET["post_type"] == "gb_xapi_content") {
			?>
			<a href="<?php echo admin_url("edit.php?post_type=gb_xapi_content&page=grassblade-bulk-import"); ?>" class="button">Bulk Import</a>
			<a href="<?php echo admin_url("edit.php?post_type=gb_xapi_content&page=grassblade-bulk-settings"); ?>" class="button">Bulk Settings</a>
			<?php
		}
	}
	function menu() {
		add_submenu_page("edit.php?post_type=gb_xapi_content", __("Bulk Import", "grassblade"), __("Bulk Import", "grassblade"),'manage_options','grassblade-bulk-import', array($this, 'menupage'));
		add_submenu_page("edit.php?post_type=gb_xapi_content", __("Bulk Settings", "grassblade"), __("Bulk Settings", "grassblade"),'manage_options','grassblade-bulk-settings', array($this, 'bulk_settings_page'));
	}
	function menupage()
	{
		wp_enqueue_media();
		$this->grassblade_xapi_content = new grassblade_xapi_content();

		$processed = $this->process();
	   //must check that the user has the required capability
	    if (!current_user_can('manage_options'))
	    {
	      wp_die( __('You do not have sufficient permissions to access this page.') );
	    }

		?>
		<div>
		<h2><img alt="" style="top: 6px; position: relative;" src="<?php echo plugins_url('img/icon_30x30.png', dirname(dirname(__FILE__))); ?>"/>
		<?php _e("Bulk Import", "grassblade"); ?></h2>
		<style>
		.gb-content-selector, #field-xapi_content, #field-activity_id, #field-h5p_content {
			display: none !important;
		}
		</style>
		<div id="grassblade_bulk_import_form" class="grassblade_admin_wrap">
			<?php if(empty($processed)) { ?>
			<form method="post" enctype="multipart/form-data">
			<?php
			$this->form();
			echo $this->grassblade_xapi_content->form();
			echo '<input type="submit" name="publish" id="publish" class="button button-primary button-large" value="'.__("Process", "grassblade").'"/><br><br>';

			?>
			</form>
			<?php } else {
				echo "<b>Import Processed:</b> <br>";
				$i = 0;
				foreach ($processed as $upload_processed) {
					$i++;
					$post_id = $upload_processed["post"]->ID;
					$file = explode("wp-content", $upload_processed["processed"]["path"]);
					$file = "/wp-content".$file[1];

					if(!empty($upload_processed["processed"]["response"]) && $upload_processed["processed"]["response"] == "error") {
						echo "<br>".$i.". <b>Error:</b> ".$upload_processed["processed"]["info"]." <b>File:</b> ".$file;
						wp_delete_post($post_id);
					}
					else
					if(!empty($upload_processed["processed"]["error"])) {
						echo "<br>".$i.". <b>Error:</b> ".$upload_processed["processed"]["error"]." <b>File:</b> ".$file;
						wp_delete_post($post_id);
					}
					else {
						$name = empty($upload_processed["post"]->post_title)? (!empty($upload_processed["processed"]["title"])? $upload_processed["processed"]["title"]:$file):$upload_processed["post"]->post_title;
						echo "<br>".$i.". Success. <b>File:</b> ".$file." <b>xAPI Content:</b> <a href='".admin_url("post.php?action=edit&post=".$post_id)."' target='_blank'>".htmlentities( $name )."</a>";
					}
				}
			} ?>
		</div>

		</div>
		<?php
	}
	function process() {
	//	echo "<pre>";print_r($_REQUEST);echo "</pre>";
	//	echo "<pre>";print_r($_POST);echo "</pre>";
		if(!empty($_REQUEST['files'])) {
			$processed = array();

			foreach ($_REQUEST['files'] as $file) {
				$my_post = array(
				  'post_status'   	=> 'publish',
				  'post_author'   	=> 1,
				  'post_type'		=> 'gb_xapi_content',
				  'post_name'		=> basename($file, ".zip")
				);

				// Insert the post into the database
				$post_id = wp_insert_post( $my_post );
				$post = get_post($post_id);

				$file = realpath(stripslashes($file));
				$path = explode("wp-content", $file);
				$path = content_url().$path[1];
				$upload = array(
						"file" 	=> $file,
						"path"	=> $path,
						"type"	=> is_dir($file) ? "folder" : "application/zip",
					);
				$data =	array(
						"file"	=> $file,
						"url"	=> $path,
						"type"	=> is_dir($file) ? "folder" : "application/zip",
					);

				$upload_processed = $this->grassblade_xapi_content->process_upload($post, $data, $upload);
				$processed[$post_id] = array("post" => $post, "processed" => $upload_processed);
				$this->set_params($post);
			}

			return $processed;
		}
	}
	function set_params($post) {
		$post_id = $post->ID;
		$this->grassblade_xapi_content->define_fields();
		$data = $this->grassblade_xapi_content->get_params($post->ID);

		unset($_POST['src']);
		unset($_POST['activity_id']);
		unset($_POST['h5p_content']);
		unset($_POST['version']);
		unset($_POST['video']);

		foreach ( $this->grassblade_xapi_content->fields as $field ) {
			if(isset($_POST[$field['id']]))
			$data[$field['id']] = esc_attr( trim($_POST[$field['id']] ));

			if($field["type"] == "checkbox")
				$data[$field['id']] = !empty($_POST[$field['id']]);

			if($field["id"] == "activity_id" && $data[$field['id']] == "[GENERATE]")
				$data[$field['id']] = get_permalink($post_id);
		}
		$this->grassblade_xapi_content->set_params( $post->ID, $data);
	}
	/**
	* defines the fields used in the plugin
	*
	* @since
	* @return void
	*/
	function define_fields($params = array()) {
		add_filter('upload_dir', array($this->grassblade_xapi_content, 'grassblade_upload_dir'));
		$upload = wp_upload_dir();
		$path = $upload['path']."/import/";
		$items = glob($path . "*");
		$files = array();
		if($items) {
			foreach ($items as $item) {
				if(is_file($item) && strtolower(pathinfo($item, PATHINFO_EXTENSION)) == "zip") {
					$files[$item] = str_replace($path, "", $item) . " (zip)";
				} else if(is_dir($item)) {
					$files[$item] = str_replace($path, "", $item) . " (folder)";
				}
			}
		}
		$show_path = explode("wp-content", $path);
		$show_path = "/wp-content".$show_path[1];

		$this->fields = array(
			array( 'id' => 'files', 'label' => __("Files/Folders","grassblade") , 'type' => 'multi-checkbox', 'values' => $files , 'help' => __( sprintf("Select the files or folders to be imported. If you don't see your files, you can upload them to this folder via FTP: %s",$show_path),"grassblade")),
		);
	}
	function form() {
			global $post;

			$this->define_fields();//$data);
		?>
			<div id="grassblade_xapi_content_form"><table width="100%">
			<?php
				foreach ($this->fields as $field) {
					if($field["type"] == "html" && @$field["subtype"] == "field_group_start") {
						echo "<tr><td colspan='2'  class='grassblade_field_group'>";
						echo "<div class='grassblade_field_group_label'><div class='dashicons dashicons-arrow-down-alt2'></div><span>".$field["label"]."</span></div>";
						echo "<div class='grassblade_field_group_fields' style='".@$field["style"]."'><table width='100%'>";
						continue;
					}
					if($field["type"] == "html" && @$field["subtype"] == "field_group_end") {
						echo "</table></div></td></tr>";
						continue;
					}

					$value = '';//isset($data[$field['id']])? $data[$field['id']]:'';
					echo '<tr id="field-'.$field['id'].'"><td width="20%" valign="top"><label for="'.$field['id'].'">'.$field['label'].'</label></td><td width="100%">';
					switch ($field['type']) {
						case 'html' :
							echo $field["html"];
						break;
						case 'text' :
							echo '<input  style="width:80%" type="text"  id="'.$field['id'].'" name="'.$field['id'].'" value="'.$value.'" placeholder="'.$field['placeholder'].'"/>';
						break;
						case 'image-selector' :
							echo '<img class="gb_upload-src" src="'.$value.'"  id="'.$field['id'].'-src" style="max-width: 150px; max-height: 50px;"/>';
							echo '<input class="gb_upload-url" type="hidden"  id="'.$field['id'].'-url" name="'.$field['id'].'" value="'.$value.'"/>';
							echo '<input class="button button-secondary gb_upload_button" type="button"  id="'.$field['id'].'" value="'.$field['value'].'"  style="width: 100px;display:block"/>';
						break;
						case 'file' :
							echo '<input  style="width:80%" type="file"  id="'.$field['id'].'" name="'.$field['id'].'" value="'.$value.'" placeholder="'.$field['placeholder'].'"/>';
						break;
						case 'number' :
							echo '<input  style="width:80%" type="number" id="'.$field['id'].'" name="'.$field['id'].'" value="'.$value.'" placeholder="'.$field['placeholder'].'"/>';
						break;
						case 'textarea' :
							echo '<textarea   style="width:80%"  id="'.$field['id'].'" name="'.$field['id'].'" placeholder="'.$field['placeholder'].'">'.$value.'</textarea>';
						break;
						case 'checkbox' :
							$checked = !empty($value) ? ' checked=checked' : '';
							echo '<input type="checkbox" id="'.$field['id'].'" name="'.$field['id'].'" value="on"'.$checked.'>';
						break;
						case 'select' :
							echo '<select id="'.$field['id'].'" name="'.$field['id'].'">';
							foreach ($field['values'] as $k => $v) :
								$selected = ($value == $k && $value != '') ? ' selected="selected"' : '';
								echo '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
							endforeach;
							echo '</select>';
						break;
						case 'select-multiple':

							echo '<select id="'.$field['id'].'" name="'.$field['id'].'[]" multiple="multiple">';

							foreach ($field['values'] as $k => $v) :
								if(!is_array($value)) $value = (array) $value;
								$selected = (in_array($k, $value)) ? ' selected="selected"' : '';
								echo '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
							endforeach;
							echo '</select>';
						break;
						case 'multi-checkbox':
							echo '<b>Select:</a> <a href="#" onClick="jQuery(\'input#'.$field['id'].'\').prop(\'checked\', true); return false;">All</a> | <a href="#" onClick="jQuery(\'input#'.$field['id'].'\').prop(\'checked\', false); return false;">None</a><br>';
							foreach ($field['values'] as $k => $v) :
								if(!is_array($value)) $value = (array) $value;

								$checked = (in_array($k, $value)) ? ' checked="checked"' : '';
								echo '<input type="checkbox" id="'.$field['id'].'" name="'.$field['id'].'[]" value="'.$k.'" '.$checked.'> '.$v.'<br>';
							endforeach;
						break;
					}
					if(!empty($field['help'])) {
						echo '<br><small>'.$field['help'].'</small><br><br>';
						echo '</td></tr>';
					}
				}
				?>
				</table>
				<br>
			</div>
		<?php
	}
	function bulk_settings_page() {
		global $grassblade;

	   //must check that the user has the required capability
	    if (!current_user_can('manage_options'))
	    {
	      wp_die( __('You do not have sufficient permissions to access this page.') );
	    }
		$grassblade_xapi_content = new grassblade_xapi_content();
		$grassblade_xapi_content->define_fields();
		$fields = $grassblade_xapi_content->fields;
		?>
		<div>
		<h2><img style="top: 6px; position: relative;" src="<?php echo plugins_url('img/icon_30x30.png', dirname(dirname(__FILE__))); ?>"/>
		<?php _e("Bulk Settings", "grassblade"); ?></h2>
		<style>
		.gb-content-selector, #field-xapi_content, #field-activity_id, #field-h5p_content {
			display: none !important;
		}
		</style>
		<?php if(!empty($grassblade) && !empty($grassblade["bulk_import_message"])) { ?>
		<div class="grassblade_admin_wrap">
			<h2>
				<?php _e("Uploaded Settings"); ?>
			</h2>
			<?php echo $grassblade["bulk_import_message"]; ?>
		</div>
		<?php } ?>
		<div id="grassblade_bulk_settings_form" class="grassblade_admin_wrap grassblade_bulk_settings" style="max-width: 1200px;">
			<div class="grassblade_field_group default_open">
				<div class="grassblade_field_group_label"><div class="dashicons dashicons-arrow-down-alt2"></div><?php _e("Download xAPI Content Settings", "grassblade"); ?></div>
				<div class="grassblade_field_group_fields">
					<form method="post" enctype="multipart/form-data">
					<input type="submit" name="download" class="button button-primary button-large" value="<?php _e("Download", "grassblade"); ?>"/> <a href='<?php echo admin_url("admin-ajax.php?action=grassblade_all_settings"); ?>' target='_blank' style="vertical-align: bottom;text-decoration: none;">Show</a>
					</form>
				</div>
			</div>
			<div class="grassblade_field_group default_close">
				<div class="grassblade_field_group_label"><div class="dashicons dashicons-arrow-right-alt2"></div><?php _e("Upload xAPI Content Settings", "grassblade"); ?></div>
				<div class="grassblade_field_group_fields">
					<form method="post" enctype="multipart/form-data">
						<input type="file" name="upload_file" /><br><br>
						<input type="submit" name="upload" class="button button-primary button-large" value="<?php _e("Upload", "grassblade"); ?>"/>
					</form>
					<br>
					<div><b><?php _e("Instructions", "grassblade"); ?>:</b><br>
					<?php  _e("1. Download the settings file.<br>
					2. Make changes to the file, and remove the rows that have not been changed.<br>
					3. Upload the file.", "grassblade"); ?></div>
					<br>
					<div>
						<b><?php _e("Fields", "grassblade"); ?>:</b><br>

						<style>
						#grassblade_bulk_import_fields th {
							text-align: left !important;
							width: 30%;
						}
						#grassblade_bulk_import_fields td {
							text-align: left !important;
							width: 70%;
							border-top: 0px !important;
						}
						</style>
						<table id="grassblade_bulk_import_fields" class="grassblade_table">
							<tr>
								<th>1. post_id:</th><td>The xAPI Content ID</td>
							</tr>
							<tr>
								<th>2. title:</th><td>The title of xAPI Content</td>
							</tr>
							<?php
							$i = 3;
							foreach ($fields as $key => $field) {
								if($field["type"] == "html" || $field["type"] == "file" || in_array($field["id"], array("h5p_content")))
									continue;
									$field_id_text = ($i++).". ".$field["id"];
									echo "<tr>";
									if(in_array($field["id"], array("activity_id")))
									echo "<th>".$field_id_text.":</th><td>".$field["label"]."</td>";
									else if($field["type"] == "text")
									echo "<th>".$field_id_text.":</th><td>".$field["label"]." : ".$field["help"]."</td>";
									else if($field["type"] == "select") {
										$desc = $field["label"]." : Valid values are:<br>";
										foreach ($field["values"] as $key => $value) {
											if($key == "")
												$key = "leave blank";
											$desc .= "<b>".$key."</b> : ".$value."<br>";
										}
										echo "<th>".$field_id_text.":</th><td>".$desc."</td>";
									}
									else if($field["type"] == "checkbox")
									echo "<th>".$field_id_text.":</th><td>".$field["label"]." : ".__("Leave blank to disable, 1 to enable.", "grassblade")."</td>";
									else if($field["type"] == "image-selector")
									echo "<th>".$field_id_text.":</th><td>".$field["label"]." : ".__("Image URL", "grassblade")."</td>";
									else
									echo "<th>".$field_id_text.":</th><td>".$field["label"]."<br>"."<pre>".print_r($field, true)."</pre></td>";
									echo "</tr>";
							}
							?>
							<tr>
								<th><?php echo $i++; ?>. original_activity_id:</th><td><?php _e("Original Activity ID of xAPI Content as configured in the Authoring Tool. (optional)", "grassblade"); ?></td>
							</tr>
							<tr>
								<th><?php echo $i++; ?>. launch_path:</th><td><?php _e("Path of the Content Url. (optional)", "grassblade"); ?></td>
							</tr>
						</table>
					</div>
				</div>
			</div>

			<div class="grassblade_field_group default_close">
				<div class="grassblade_field_group_label"><div class="dashicons dashicons-arrow-right-alt2"></div><?php _e("Manage uploaded content folders", "grassblade"); echo " (".__("beta", "grassblade").")"; ?></div>
				<div class="grassblade_field_group_fields">
					<?php $this->show_folders(); ?>
				</div>
			</div>

		</div>
		<?php
	}
	function show_folders() {
		global $wpdb, $current_user;
		$disabled_users = defined("GB_DISABLE_MANAGE_FOLDERS")? explode(",", GB_DISABLE_MANAGE_FOLDERS):"";

		if(empty($current_user->ID) || !current_user_can("manage_options"))
			return;

		if(!empty($disabled_users) && in_array($current_user->ID, $disabled_users))
		{
			echo "You do not have permissions for this feature.";
			return;
		}

		$grassblade_xapi_content = new grassblade_xapi_content();
		add_filter('upload_dir', array($grassblade_xapi_content, 'grassblade_upload_dir'));
		$upload = wp_upload_dir();
		$upload_path = $upload["path"];
		$upload_url = $upload["url"];
		$folders = glob($upload_path."/*/");
		$folder_details = array();
		foreach ($folders as $key => $value) {
			$path = str_replace($upload_path, "", $value);
			$folder_details[$path] = array(
										"path" => $value,
										"contents"	=> array(),
										"revisions" 	=> array()
									);
		}
		$xapi_content_r = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'xapi_content' LIMIT 2000", ARRAY_A);
		$xapi_content_versions_r = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'xapi_content_versions' LIMIT 2000", ARRAY_A);
		$xapi_contents = $xapi_content_versions = array();

		foreach ($xapi_content_r as $key => $value) {
			$meta_value = maybe_unserialize($value["meta_value"]);
			if(empty($meta_value) || empty($meta_value["src"]) && empty($meta_value["video"]))
				continue;
			$meta_value["found"] = 0;
			if(!empty($meta_value["content_path"]) && strpos($meta_value["content_path"], $upload_path) !== false) {
				$content_path = rtrim(str_replace($upload_path, "", $meta_value["content_path"]), "/")."/";
				if(isset($folder_details[$content_path])) {
					$meta_value["found_by_content_path"] = 1;
					$meta_value["found"]++;
					if(!empty($meta_value["content_size"]))
						$folder_details[$content_path]["content_size"] = $meta_value["content_size"];
					$folder_details[$content_path]["contents"][$value["post_id"]] = $meta_value;
				}
			}
			if(!empty($meta_value["content_url"]) && strpos($meta_value["content_url"], $upload_url) !== false) {
				$content_path = rtrim(str_replace($upload_url, "", $meta_value["content_url"]), "/")."/";
				if(isset($folder_details[$content_path])) {
					$meta_value["found_by_content_url"] = 1;
					$meta_value["found"]++;
					if(!empty($meta_value["content_size"]))
						$folder_details[$content_path]["content_size"] = $meta_value["content_size"];
					$folder_details[$content_path]["contents"][$value["post_id"]] = $meta_value;
				}
			}

			$src = !empty($meta_value["src"])? $meta_value["src"]:$meta_value["video"];

			$src_path = "";
			if(strpos($src, $upload_url) !== false) {
				$src_path = str_replace($upload_url, "", $src);
			}
			else
			{
				$p = explode("wp-content", $src);
				if(!empty($p[1]))
					$src_path = str_replace($upload_url, "", WP_CONTENT_URL.$p[1]);
			}
			//echo "<Br>".$value["post_id"].": ".$src_path." :";
			if(!empty($src_path)) {
				foreach ($folder_details as $path => $folder) {
					if(strpos($src_path, $path) === 0) {
						//echo "Folder Path: ".$path;
						$meta_value["found_by_src"] = 1;
						$meta_value["found"]++;
						if(!empty($meta_value["content_size"]))
							$folder_details[$path]["content_size"] = $meta_value["content_size"];
						$folder_details[$path]["contents"][$value["post_id"]] = $meta_value;
					}
				}
			}

			$xapi_contents[$value["post_id"]] = $meta_value;
		}

		foreach ($xapi_content_versions_r as $key => $value) {
			$meta_value = maybe_unserialize($value["meta_value"]);
			if(empty($meta_value) || empty($meta_value["src"]) && empty($meta_value["video"]))
				continue;

			$meta_value["found"] = 0;
			if(!empty($meta_value["content_path"]) && strpos($meta_value["content_path"], $upload_path) !== false) {
				$content_path = rtrim(str_replace($upload_path, "", $meta_value["content_path"]), "/")."/";
				if(isset($folder_details[$content_path])) {
					$meta_value["found_by_path"] = 1;
					$meta_value["found"]++;
					if(!empty($meta_value["content_size"]))
						$folder_details[$content_path]["content_size"] = $meta_value["content_size"];
					$folder_details[$content_path]["revisions"][$value["post_id"]] = $meta_value;
				}
			}
			if(!empty($meta_value["content_url"]) && strpos($meta_value["content_url"], $upload_url) !== false) {
				$content_path = rtrim(str_replace($upload_url, "", $meta_value["content_url"]), "/")."/";
				if(isset($folder_details[$content_path])) {
					$meta_value["found_by_content_url"] = 1;
					$meta_value["found"]++;
					if(!empty($meta_value["content_size"]))
						$folder_details[$content_path]["content_size"] = $meta_value["content_size"];
					$folder_details[$content_path]["revisions"][$value["post_id"]] = $meta_value;
				}
			}

			$src = !empty($meta_value["src"])? $meta_value["src"]:$meta_value["video"];

			$src_path = "";
			if(strpos($src, $upload_url) !== false) {
				$src_path = str_replace($upload_url, "", $src);
			}
			else
			{
				$p = explode("wp-content", $src);
				if(!empty($p[1]))
					$src_path = str_replace($upload_url, "", WP_CONTENT_URL.$p[1]);
			}
			if(!empty($src_path)) {
				foreach ($folder_details as $path => $folder) {
					if(strpos($src_path, $path)) {
						$meta_value["found_by_src"] = 1;
						$meta_value["found"]++;
						if(!empty($meta_value["content_size"]))
							$folder_details[$path]["content_size"] = $meta_value["content_size"];

						$folder_details[$path]["revisions"][$value["post_id"]] = $meta_value;
					}
				}
			}

			$xapi_content_versions[$value["post_id"]] = maybe_unserialize($value["meta_value"]);
		}


		$contents = $revisions = $not_found = 0;
		$extra_folders = array();
		foreach ($folder_details as $path => $folder) {
			if(!empty($folder["contents"]) && count($folder["contents"]) >= 1 )
				$contents++;

			if(!empty($folder["revisions"]) && count($folder["revisions"]) >= 1 )
				$revisions++;
			if( empty($folder["contents"]) && empty($folder["revisions"]) ) {
				$deleted_content = explode("-", trim($path, "/"));
				if(!empty($deleted_content[0]) && is_numeric($deleted_content[0])) {
					$content = get_post($deleted_content[0]);
					if(empty($content))
					$folder["related_content"] = "Deleted ID? : ".$deleted_content[0];
					else
					$folder["related_content"] = "Related Content? : <a href='".get_edit_post_link($content->ID)."'>".$content->ID.". ".$content->post_title."</a>";

					if(!empty($xapi_contents[$deleted_content[0]]))
					$folder["xapi_content"] = $xapi_contents[$deleted_content[0]];

					if(!empty($xapi_content_versions[$deleted_content[0]]))
					$folder["xapi_content_versions"] = $xapi_content_versions[$deleted_content[0]];
				}
				$folder_details[$path] = $folder;
				$not_found++;
			}
		}

		$table = array();
		$sno = 1;
		foreach ($folder_details as $path => $folder) {
			$xapi_contents = $content_revisions = array();
			if(!empty($folder["contents"]))
			foreach ($folder["contents"] as $content_id => $value) {
				$content = get_post($content_id);
				$xapi_contents[] = empty($content->ID)? "Deleted? ".$content_id:"<a href='".get_edit_post_link($content->ID)."'>".$content->ID.". ".$content->post_title."</a>";
			}
			if(!empty($folder["revisions"]))
			foreach ($folder["revisions"] as $content_id => $value) {
				$content = get_post($content_id);
				$content_revisions[] = empty($content->ID)? "Deleted? ".$content_id:"<a href='".get_edit_post_link($content->ID)."'>".$content->ID.". ".$content->post_title." Revision: ".$value["version_no"]."</a>";
			}
			$related = empty($folder["related_content"])? "":$folder["related_content"];

			$size = !empty($folder["content_size"])? $folder["content_size"]:grassblade_xapi_content::get_size($folder["path"]);
			if(!empty($size))
			$size = grassblade_xapi_content::readable_size($size);


			$row = array(
				"S. No." => $sno++,
				"Folder Path" => $path,
				"Size" => $size,
				"xAPI Content(s)" => implode("<br>", $xapi_contents),
				"Revisions(s)" => implode("<br>", $content_revisions),
				"Related" => $related,
				"Action" => empty($xapi_contents)? "<div class='button delete_folder_button' data-path='".$path."'>Delete Folder</div>":"",
			);

			$table[] = $row;
		}
		echo "<div class='gb_warning'>Warning: Please make sure to take appropriate database and file backups.</div>";
		echo "<div class='gb_table_search_div'>".__("Search", "grassblade").": <input class='gb_table_search' data-tableid='grassblade_delete_folders_table' /></div>";
		wp_nonce_field( plugin_basename( __FILE__ ), 'gb_folder_delete');
		include_once(dirname(__FILE__)."/../nss_arraytotable.class.php");
		$ArrayToTable = new NSS_ArrayToTable($table, 'id="grassblade_delete_folders_table"');
		$ArrayToTable->show();
	}
	function gb_folder_delete() {
		global $current_user;
		$disabled_users = defined("GB_DISABLE_MANAGE_FOLDERS")? explode(",", GB_DISABLE_MANAGE_FOLDERS):"";

		if(empty($current_user->ID) || !current_user_can("manage_options") || !empty($disabled_users) && in_array($current_user->ID, $disabled_users))
			return;

		if ( !isset($_GET["nonce"]) || !wp_verify_nonce( $_GET["nonce"], plugin_basename( __FILE__ ) ) )
		return;

		$path = $_GET["path"];
		$grassblade_xapi_content = new grassblade_xapi_content();
		add_filter('upload_dir', array($grassblade_xapi_content, 'grassblade_upload_dir'));
		$upload = wp_upload_dir();
		$upload_path = $upload["path"];

		$full_path = $upload_path.$path;
		//echo $full_path;

		if(empty($path) || !is_dir($full_path)) {
			$response = array("status" => 0, "message" => "Invalid path.");
		}
		else {
			grassblade_xapi_content::delete_dir($full_path);
			if(is_dir($full_path))
			$response = array("status" => 0, "message" => "Delete Failed");
			else
			$response = array("status" => 1, "message" => "Deleted");
		}
		echo json_encode($response);
		exit();
	}
	function upload_mimes ( $existing_mimes=array() ) {
	    // add your extension to the mimes array as below
	    $existing_mimes['csv'] = 'text/csv';
	    return $existing_mimes;
	}
	function csv_settings_process() {
		if(!current_user_can("manage_options"))
			return;

		if(empty($_GET["page"]) || $_GET["page"] != "grassblade-bulk-settings")
			return;

		if(!empty($_POST["download"]))
			$this->download();
		else if(!empty($_POST["upload"])  && !empty($_FILES['upload_file']['name'])) {
			$this->upload();
		}
	}
	function get_all_settings() {
		$posts = get_posts(array(
			'post_type'      => 'gb_xapi_content',
			'post_status'    => array('publish', 'draft','private', 'future', 'pending'),
			'posts_per_page' => -1
		));
		$settings = array();
		$unset = array("file", "path", "type", "url", "content_path", "content_url");

		$grassblade_xapi_content = new grassblade_xapi_content();
		$grassblade_xapi_content->define_fields();
		$fields = $grassblade_xapi_content->fields;

		foreach ($fields as $key => $field) {
			if($field["type"] == "html" || $field["id"] == "xapi_content")
				unset($fields[$key]);
			else
			$fields[$key] = $field["id"];
		}
		$fields[] = "original_activity_id";
		$fields[] = "launch_path";

		foreach ($posts as $post) {
			$xapi_content = get_post_meta($post->ID, "xapi_content", true);
			$setting = array("post_id" => $post->ID, "title" => $post->post_title);
			foreach ($fields as $field) {
				$setting[$field] = isset($xapi_content[$field])? $xapi_content[$field]:"";
			}
			$settings[] = $setting;
		}
		$settings = apply_filters( 'grassblade_xapi_content_all_settings', $settings );
		return $settings;
	}
	function show_grassblade_all_settings() {
		if(!current_user_can("manage_options"))
			return;

		$all_settings = $this->get_all_settings();
		if(!empty($all_settings)) {
			foreach ($all_settings as $k => $row) {
				$all_settings[$k] = array_map("htmlentities", $row);
				$all_settings[$k]["post_id"] = "<a href='". get_edit_post_link($row["post_id"])."' target='_blank'>".$row["post_id"]."</a>";
			}
			grassblade_scripts("grassblade-bulk-settings");
			include_once(dirname(__FILE__)."/../nss_arraytotable.class.php");
			$ArrayToTable = new NSS_ArrayToTable($all_settings, 'id="grassblade_all_settings"');

			?>
			<!DOCTYPE html>
			<html>
			<head>
				<script type="text/javascript" src="<?php echo plugins_url("/js/jquery.min.js?ver=".GRASSBLADE_VERSION, dirname(dirname(__FILE__))); ?>"></script>
				<script type="text/javascript" src="<?php echo plugins_url("/js/script.js?ver=".GRASSBLADE_VERSION, dirname(dirname(__FILE__))); ?>"></script>
				<script type="text/javascript" src="<?php echo plugins_url("/js/hilitor.js?ver=".GRASSBLADE_VERSION, dirname(dirname(__FILE__))); ?>"></script>
				<link rel="stylesheet" type="text/css" href="<?php echo plugins_url("/css/styles.css?ver=".GRASSBLADE_VERSION, dirname(dirname(__FILE__))); ?>">
				<?php if(function_exists("grassblade_table_styles_set")) grassblade_table_styles_set(); ?>
			</head>
			<body id='grassblade_all_settings_body'>
				<div class='gb_table_search_div'>
					<div class="search_box"><?php _e("Search", "grassblade"); ?>: <input class='gb_table_search' data-tableid='grassblade_all_settings' /></div>
					<div><form method="post" enctype="multipart/form-data" action="<?php echo admin_url("edit.php?post_type=gb_xapi_content&page=grassblade-bulk-settings"); ?>">
						<input type="submit" name="download" class="button button-primary button-large" value="<?php _e("Download", "grassblade"); ?>"/>
					</form></div>
				</div>
				<?php $ArrayToTable->show(); ?>
			</body>
			</html>
			<?php
		}
		exit();
	}
	function download() {
		if(!current_user_can("manage_options"))
			return;

		require_once(dirname(dirname(__FILE__))."/parsecsv.lib.php");
		$csv = new parseCSV();
		$settings = $this->get_all_settings();
		$settings = apply_filters( 'grassblade_xapi_content_download_settings', $settings );
		$headers = (!empty($settings))? array_keys( reset( $settings ) ):array();
		$csv->output( 'grassblade_xapi_content_settings.csv', $settings, $headers );
		die();
	}
	function upload() {
		if(!current_user_can("manage_options"))
			return;

		if(strtolower( pathinfo($_FILES['upload_file']['name'], PATHINFO_EXTENSION) ) != "csv" || $_FILES["upload_file"]["type"] != "text/csv" && $_FILES["upload_file"]["type"] != "application/vnd.ms-excel")
		{
			update_option('grassblade_admin_errors', __('Upload Error: Invalid file format. Please upload a valid csv file', 'grassblade') );
			return;
		}
		require_once(dirname(dirname(__FILE__))."/parsecsv.lib.php");
		$csv = new parseCSV($_FILES['upload_file']['tmp_name']);
		if(empty($csv->data) || !is_array($csv->data))
		{
			update_option('grassblade_admin_errors', __('Upload Error: Empty csv file', 'grassblade') );
			return;
		}
		$grassblade_xapi_content = new grassblade_xapi_content();
		$grassblade_xapi_content->define_fields();
		$this->fields = $grassblade_xapi_content->fields;

		$processed = array();
		foreach ($csv->data as $key => $data) {
			$processed[] = $this->process_upload_data($data);
		}
		$i = 0;
		$message = "<table>";
		foreach ($processed as $key => $value) {
			$i++;
			$post_id = intval($value["data"]["post_id"]);
			$message .= "<tr><td style='width: 50px'>".$i."</td><td style='width: 130px'>post_id: <a href='".admin_url("post.php?action=edit&post=".$post_id)."' target='_blank'>".$post_id."</a></td><td> title: ".htmlentities( $value["data"]["title"] )."</td><td>";

			if(!empty($value["error"]))
			$message .= " <span style='color:red'>Error:".$value["error"]."</span>";
			else
			if(count($value["updated"]) <= 0)
			{
			$message .= " <span>".$value["message"]."</span>";
			}
			else
			{
			$message .= " <span style='color:green'>".$value["message"]."</span>";
			}
			$message .= "</td></tr>";
		}
		$message .= "</table>";

		global $grassblade;
		if(empty($grassblade) || !is_array($grassblade))
			$grassblade = array();

		$grassblade["bulk_import_message"] = $message;
	}
	function process_upload_data($data) {
		//Check Title
		$post_id = $data["post_id"];
		$post = get_post($post_id);
		$updated = array();

		if(empty($post->ID))
		{
			return array(
					"data"	=> $data,
					"error"	=> "Invalid or empty post id",
				);
		}
		if($post->post_type != "gb_xapi_content") {
			return array(
					"data"	=> $data,
					"post"	=> $post,
					"error"	=> "Invalid post type: ".$post->post_type,
				);
		}
		if($invalid = $this->validate_data($data)) {
			return array(
					"data"	=> $data,
					"error"	=> $invalid,
				);
		}
		$xapi_content = get_post_meta($post->ID, "xapi_content", true);

		if(empty($xapi_content)) {
			return array(
					"data"	=> $data,
					"post"	=> $post,
					"error"	=> "No xAPI Content data",
				);
		}

		foreach ($this->fields as $key => $value) {
			$_key = $value["id"];
			if(in_array($value["type"], array("text", "checkbox", "select") ) && !isset($xapi_content[$_key]) ) {
				$xapi_content[$_key] = '';
			}
		}
		foreach ($data as $key => $value) {
			if(isset($xapi_content[$key]) && $xapi_content[$key] != $data[$key]) {
				if($key == "activity_id")
                    update_post_meta($post->ID, "xapi_activity_id", $data[$key]);
				$updated[] = $key;
				$xapi_content[$key] = $data[$key];
			}
		}
		if(count($updated) > 0)
			update_post_meta($post->ID, "xapi_content", $xapi_content);

		if($data["title"] != $post->post_title) {
			$updated[] = "title";
			wp_update_post(array(
					"ID"	=> $post->ID,
					"post_title"	=> $data["title"],
				));
		}
		$message = (count($updated) > 0)? "Updated: ".implode(", ", $updated):" No change";
		return array(
				"data"	=> $data,
				"post"	=> $post,
				"updated"	=> $updated,
				"message"	=> $message,
			);
	}
	function validate_data($data) {
		foreach ($this->fields as $key => $field) {
			$field_id = $field["id"];
			if($field["type"] == "select")
			{
				$invalid = true;
				$desc = "";
				foreach ($field["values"] as $key => $value) {
					if($data[$field_id] == $key)
						$invalid = false;

						if($key == "")
							$key = "leave blank";
						$desc .= "<b>".$key."</b> : ".$value.", ";
				}
				if($invalid)
					return "Invalid <b>".$field_id."</b> value (".$data[$field_id]."). Valid values are: ".$desc;
			}
			if($field["type"] == "checkbox")
			{
				if($data[$field_id] != "" && $data[$field_id] != 1 && $data[$field_id] != "on" && $data[$field_id] != "off")
				{
					$desc = __("Leave blank to disable, 1 to enable.");
					return "Invalid <b>".$field_id."</b> value (".$data[$field_id].").".$desc;
				}
			}

		}
		return;
	}
}
new grassblade_bulk_import();
