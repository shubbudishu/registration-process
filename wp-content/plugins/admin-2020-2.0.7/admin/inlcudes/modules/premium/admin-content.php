<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_content
{
    public function __construct($version, $path, $utilities)
    {
        $this->version = $version;
        $this->path = $path;
        $this->utils = $utilities;
		$this->media_date = '';
		$this->attachment_size = '';
		$this->folders = new Admin_2020_module_admin_folders($this->version,$this->path,$this->utils);
    }

    /**
     * Loads menu actions
     * @since 1.0
     */

    public function start()
    {
		///REGISTER THIS COMPONENT
		add_filter('admin2020_register_component', array($this,'register'));
		
		if(!$this->utils->enabled($this)){
			return;
		}
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		
		if($this->utils->is_locked($optionname)){
			return;
		}
		
		add_action('admin_menu', array( $this, 'add_menu_item'));
        add_action('admin_enqueue_scripts', [$this, 'add_styles'], 0);
		add_action('admin_enqueue_scripts', [$this, 'add_scripts'], 0);
		add_action('wp_ajax_a2020_fetch_more_media', array($this,'a2020_fetch_more_media'));
		add_action('wp_ajax_a2020_fetch_attachment_modal', array($this,'a2020_fetch_attachment_modal'));
		add_action('wp_ajax_a2020_save_post', array($this,'a2020_save_post'));
		add_action('wp_ajax_a2020_save_attachment', array($this,'a2020_save_attachment'));
		add_action('wp_ajax_a2020_delete_item', array($this,'a2020_delete_item'));
		add_action('wp_ajax_a2020_duplicate_post', array($this,'a2020_duplicate_post'));
		add_action('wp_ajax_a2020_search_content', array($this,'a2020_search_content'));
		add_action('wp_ajax_admin2020_set_content_folder_query', array($this,'admin2020_set_content_folder_query'));
		add_action('wp_ajax_a2020_add_batch_rename_item', array($this,'a2020_add_batch_rename_item'));
		add_action('wp_ajax_a2020_process_batch_rename', array($this,'a2020_process_batch_rename'));
		add_action('wp_ajax_a2020_process_upload', array($this,'a2020_process_upload'));
		add_action('wp_ajax_a2020_upload_edited_image', array($this,'a2020_upload_edited_image'));
		add_action('wp_ajax_a2020_upload_edited_image_as_copy', array($this,'a2020_upload_edited_image_as_copy'));
		
    }
	
	
	/**
	 * Register admin bar component
	 * @since 1.4
	 * @variable $components (array) array of registered admin 2020 components
	 */
	public function register($components){
		
		array_push($components,$this);
		return $components;
		
	}
	
	
	
	/**
	 * Returns component info for settings page
	 * @since 1.4
	 */
	public function component_info(){
		
		$data = array();
		$data['title'] = __('Content','admin2020');
		$data['option_name'] = 'admin2020_admin_content';
		$data['description'] = __('Creates the content page where you can manage all of your assets, posts and pages from one place.','admin2020');
		return $data;
		
	}
	/**
	 * Returns settings for module
	 * @since 1.4
	 */
	 public function render_settings(){
		  
		  wp_enqueue_media();
		  
		  $info = $this->component_info();
		  $optionname = $info['option_name'];
		  
		  $disabled_for = $this->utils->get_option($optionname,'disabled-for');
		  $post_types_enabled = $this->utils->get_option($optionname,'post-types-content');
		  if($disabled_for == ""){
			  $disabled_for = array();
		  }
		  if($post_types_enabled == ""){
			  $post_types_enabled = array();
		  }
		  ///GET ROLES
		  global $wp_roles;
		  ///GET USERS
		  $blogusers = get_users();
		  ///GET POST TYPES
		  $args = array('public'   => true);
		  $output = 'objects'; 
		  $post_types = get_post_types( $args, $output );
		  ?>
		  <div class="uk-grid" id="a2020_content_settings" uk-grid>
			  <!-- LOCKED FOR USERS / ROLES -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Admin 2020 content page disabled for','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("Admin 2020 Content Page will be disabled for any users or roles you select",'admin2020') ?></div>
			  </div>
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  
				  
				  <select class="a2020_setting" id="a2020-role-types" name="disabled-for" module-name="<?php echo $optionname?>" multiple>
					  
					<?php
					$sel = '';
					  
					if(in_array('Super Admin', $disabled_for)){
					  $sel = 'selected';
					}
					?>  
					<option value="Super Admin" <?php echo $sel?>><?php _e('Super Admin','admin2020') ?></option>
					  
					<?php
					foreach ($wp_roles->roles as $role){
					  $rolename = $role['name'];
					  $sel = '';
					  
					  if(in_array($rolename, $disabled_for)){
						  $sel = 'selected';
					  }
					  ?>
					  <option value="<?php echo $rolename ?>" <?php echo $sel?>><?php echo $rolename ?></option>
					  <?php
					}
					  
					foreach ($blogusers as $user){
						$username = $user->display_name;
						$sel = '';
						
						if(in_array($username, $disabled_for)){
							$sel = 'selected';
						}
						?>
						<option value="<?php echo $username ?>" <?php echo $sel?>><?php echo $username ?></option>
						<?php
					}
					?>
				  </select>
				  
				  <script>
					  jQuery('#a2020_content_settings #a2020-role-types').tokenize2({
						  placeholder: '<?php _e('Select roles or users','admin2020') ?>'
					  });
					  jQuery(document).ready(function ($) {
						  $('#a2020_content_settings #a2020-role-types').on('tokenize:select', function(container){
							  $(this).tokenize2().trigger('tokenize:search', [$(this).tokenize2().input.val()]);
						  });
					  })
				  </script>
				  
			  </div>	
			  <div class="uk-width-1-1@ uk-width-1-3@m">
			  </div>	
			  <!-- POST TYPES AVAILABLE IN CONTENT PAGE -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Post Types available in Search','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("The global search will only search the selected post types.",'admin2020') ?></div>
			  </div>
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  
				  
				  <select class="a2020_setting" id="a2020-post-types" name="post-types-content" module-name="<?php echo $optionname?>" multiple>
					  <?php
					  foreach ($post_types as $type){
						  $name = $type->name;
						  $label = $type->label;
						  $sel = '';
						  
						  if(in_array($name, $post_types_enabled)){
							  $sel = 'selected';
						  }
						  ?>
						  <option value="<?php echo $name ?>" <?php echo $sel?>><?php echo $label ?></option>
						  <?php
					  }
					  ?>
				  </select>
				  
				  <script>
					  jQuery('#a2020_content_settings #a2020-post-types').tokenize2({
						  placeholder: '<?php _e('Select Post Types','admin2020') ?>'
					  });
					  jQuery(document).ready(function ($) {
						  $('#a2020_content_settings #a2020-post-types').on('tokenize:select', function(container){
							  $(this).tokenize2().trigger('tokenize:search', [$(this).tokenize2().input.val()]);
						  });
					  })
				  </script>
				  
			  </div>	
		  </div>	
		  
		  <?php
	  }
    /**
     * Adds admin bar styles
     * @since 1.0
     */

    public function add_styles()
    {
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin_2020_content'){
				
				wp_enqueue_editor();
				wp_enqueue_media();
		
		        wp_register_style(
		            'admin2020_admin_content',
		            $this->path . 'assets/css/modules/admin-content.css',
		            array(),
		            $this->version
		        );
		        wp_enqueue_style('admin2020_admin_content');
				
				///FILEPOND IMAGE PREVIEW
				wp_register_style(
					'admin2020_filepond_preview',
					$this->path . 'assets/css/filepond/filepond-image-preview.css',
					array(),
					$this->version
				);
				wp_enqueue_style('admin2020_filepond_preview');
				///FILEPOND 
				wp_register_style(
					'admin2020_filepond',
					$this->path . 'assets/css/filepond/filepond.css',
					array(),
					$this->version
				);
				wp_enqueue_style('admin2020_filepond');
				
				wp_register_style(
					'admin2020_image_editor',
					$this->path . 'assets/css/tui-image-editor/tui-style.css',
					array(),
					$this->version
				);
				wp_enqueue_style('admin2020_image_editor');
				
				
					
			}
		}
    }
	
	/**
	* Enqueue Admin Bar 2020 scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin_2020_content'){
				
				$types = get_allowed_mime_types();
				$temparay = array();
				foreach($types as $type){
					array_push($temparay,$type);
				}
			
				
				////FILEPOND PLUGINS
				wp_enqueue_script('a2020_filepond_encode', $this->path . 'assets/js/filepond/filepond-file-encode.min.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_filepond_preview', $this->path . 'assets/js/filepond/filepond-image-preview.min.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_filepond_orientation', $this->path . 'assets/js/filepond/filepond-orientation.min.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_filepond_validate', $this->path . 'assets/js/filepond/filepond-validate-size.min.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_filepond_file_types', $this->path . 'assets/js/filepond/filepond-file-types.min.js', array('jquery'),$this->version);
				////FILEPOND
				wp_enqueue_script('a2020_filepond', $this->path . 'assets/js/filepond/filepond.min.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_filepond_jquery', $this->path . 'assets/js/filepond/filepond-jquery.min.js', array('jquery'),$this->version);
				///CONTENT JS
				wp_enqueue_script('admin-content-js', $this->path . 'assets/js/admin2020/admin-content.min.js', array('a2020_filepond_jquery'),$this->version);
				wp_localize_script('admin-content-js', 'admin2020_admin_content_ajax', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'security' => wp_create_nonce('admin2020-admin-content-security-nonce'),
					'current_page' => 1,
					'a2020_allowed_types' => json_encode($temparay)
				));
				
				///IMAGE EDITOR
				$scripts = array('fabric','code-snippet','color-picker','filesaver','tui-image-editor','night-theme');
				foreach($scripts as $script){
					wp_enqueue_script('a2020_'.$script, $this->path . 'assets/js/tui-image-editor/' . $script . '.min.js', array('jquery'),$this->version);
				}
			
			}
		}
	  
	}
	
	
	
	/**
	* Adds media menu item
	* @since 1.4
	*/
	
	public function add_menu_item() {
		
		add_menu_page( '2020_content', __('Content',"admin2020"), 'read', 'admin_2020_content', array($this,'build_media_page'),'dashicons-database', 4 );
		return;
	
	}
	
	/**
	* Build media page
	* @since 1.4
	*/
	
	public function build_media_page(){
		
		$listview = $this->utils->get_user_preference('content_list_view');
		$folder_view = $this->utils->get_user_preference('content_folder_view');
		$class = '';
		$folder_class = '';
		
		if($listview == 'list'){
			$class = 'a2020_list_view';
		}
		
		if($folder_view == 'hidden'){
			$folder_class = 'hidden';
		}
		
		$folders_info = $this->folders->component_info();
		$folder_module = $folders_info['option_name']; 		
		?>
		<div class="wrap">
			<div class="uk-width-1-1" uk-filter="target: #a2020_media_items;" id="a2020_content_filter">
				<?php $this->build_header() ?>
				<?php $this->build_post_type_nav() ?>
				<div class=" uk-grid-divider" uk-grid>
					
					<?php if(!$this->utils->is_locked($folder_module) && $this->utils->enabled($this->folders)){ ?>
					<div class="uk-width-1-1@s uk-width-medium@m"  id="folder_panel" <?php echo $folder_class?>>
						<div uk-sticky="media: 640;offset:100">
							<?php $this->folders->build_folder_panel() ?>
						</div>
					</div>
					<?php } ?>
					<div class="uk-width-1-1@s uk-width-expand@m">
						<?php $this->build_view_filters() ?>
						<div class="uk-grid-medium <?php echo $class?>"  id="a2020_media_items" uk-grid>
							<?php $this->build_media($this->build_media_query()) ?>
						</div>
						<div class="load-more uk-margin-top uk-text-center">
							<hr>
							<button class="uk-button uk-button-default uk-width-medium" onclick="a2020_load_more()" type="button"><?php _e('Load More','admin2020')?></button>
						</div>
					</div>
				</div>
			</div>
			
			<div class="admin2020loaderwrap" id="admincontentloader" style="display: none;">
				<div class="admin2020loader"></div>
			</div>
			<?php $this->build_media_bacth() ?>
			<?php $this->build_media_modal() ?>
			<?php $this->build_batch_rename_modal() ?>
			<?php $this->build_upload_modal() ?>
			<?php $this->build_edit_modal() ?>
			
		</div>
		<?php
	}
	
	/**
	* Build wrap for image editing
	* @since 1.4
	*/
	
	public function build_edit_modal(){
		
		?>
		<div class="admin2020_image_edit_wrap">
			<div class="admin2020_image_edit_header uk-padding-small uk-position-small uk-position-top-right" style="z-index:9">
				<a href="#" uk-icon="icon: close" onclick="jQuery('.admin2020_image_edit_wrap').hide();" style="float:right"></a>
			</div>
			<div id="admin2020_image_edit_area"></div>
		</div>
		<?php
		
	}
	
	/**
	* Build batch modal
	* @since 1.4
	*/
	
	public function build_batch_rename_modal(){
		
		?>
		
		<div id="batch-rename" class="uk-flex-top" uk-modal>
		  <div class="uk-modal-dialog uk-modal-body uk-margin-auto-vertical">

			  <button class="uk-modal-close-default" type="button" uk-close></button>

			  <div class="uk-h4"><?php _e("Batch Rename",'admin2020')?>
		  		<span uk-icon="info"></span>
				  <div uk-dropdown="pos:bottom-justify">
					  <p class="uk-text-meta"><?php _e('Batch rename only currently works with media files and attachments, any selected posts will be ignored.','admin2020')?></p>
				  </div>
		  	  </div>

			  <form class="uk-form-stacked uk-grid-small" uk-grid>


				<div class="uk-width-1-1">
					<label class="uk-form-label" for="form-stacked-select"><?php _e('Attribute to rename','admin2020')?></label>
					<div class="uk-form-controls">
						<select class="uk-select" id="form-stacked-select">
							<option value="name"><?php _e('Name','admin2020')?></option>
							<option value="alt"><?php _e('Alt Tag','admin2020')?></option>
						</select>
					</div>
				</div>

				<div class="uk-width-1-1">
				  <div class=""><?php _e('New Name','admin2020')?></div>
				</div>

				<div class="uk-width-1-3">
					<div class="uk-form-controls">
						<select class="uk-select" id="batch_name_chooser">
							<option value="filename"><?php _e('Original Filename','admin2020')?></option>
							<option value="text"><?php _e('Text','admin2020')?></option>
							<option value="date"><?php _e('Date Uploaded','admin2020')?></option>
							<option value="original_alt"><?php _e('Original Alt','admin2020')?></option>
							<option value="extension"><?php _e('File Extension','admin2020')?></option>
							<option value="sequence"><?php _e('Sequence Number','admin2020')?></option>
							<option value="meta"><?php _e('Meta Value','admin2020')?></option>
						</select>
					</div>
				</div>

				<div class="uk-width-1-3">
					<button class="uk-button uk-button-default" type="button" onclick="add_batch_rename_item()"><?php _e('Add','admin2020')?></button>
				</div>

				<div class="uk-width-1-1">
				  <hr style="margin: 30px 0;">
				</div>

				<div class="uk-width-1-1" id="batch_rename_builder" uk-sortable="handle: .rename_drag">

				</div>



				<div class="uk-width-1-1">
				  <hr style="margin: 30px 0 0 0;">
				</div>

				<div class="uk-width-2-3 uk-flex uk-flex-middle" >
				  <span><?php _e('Preview','admin2020')?>: </span>
				  <span class="uk-text-bold" id="batch_rename_preview"></span>
				</div>

				<div class="uk-width-1-3 uk-flex uk-flex-right">
				  <button class="uk-button uk-button-primary" type="button" onclick="batch_rename_process();"><?php _e('Rename','admin2020') ?></button>
				</div>

			</form>

		  </div>
	  </div>
		<?php
		
		
	}
	
	/**
	* Builds batch options
	* @since 1.4
	*/
	
	public function build_media_bacth(){
		?>
		<div class="a2020_bulk_actions uk-padding-small uk-background-default a2020_border_top uk-text-right">
			
			<button class="uk-button uk-button-secondary uk-margin-right" 
			onclick="jQuery('.a2020_selected').removeClass('a2020_selected');jQuery('.a2020_bulk_actions').hide();"><?php _e('Deselect All','admin2020')?></button>
			<button class="uk-button uk-button-primary uk-margin-small-right" uk-toggle="target:#batch-rename"><?php _e('Batch Rename','admin2020') ?></button>
			<button class="uk-button uk-button-danger" onclick="a2020_delete_multiple()"><?php _e('Delete','admin2020') ?></button>
			
		</div>
		<?php
	}
	
	/**
	* Build post type navigation 
	* @since 1.4
	*/
	
	public function build_post_type_nav(){
		
		$args = array('public'   => true);
		$output = 'objects'; 
		$post_types = get_post_types( $args, $output );
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$post_types_enabled = $this->utils->get_option($optionname,'post-types-content');
		$temp = array();
		
		if($post_types_enabled && is_array($post_types_enabled)){
			foreach($post_types_enabled as $posttype){
				$type_object = get_post_type_object($posttype);
				array_push($temp,$type_object);
			}
			$post_types = $temp;
		} 
		
		if(count($post_types) < 2){
			return;
		}
		?>
		<ul uk-tab>
			<li class="uk-active" uk-filter-control="group:types"><a href="#"><?php _e('All','admin2020') ?></a></li>
			<?php foreach($post_types as $posttype) { ?>
			<li><a href="#" uk-filter-control="group:types;filter:[post_type='<?php echo $posttype->name ?>']"><?php echo $posttype->label ?></a></li>
			<?php } ?>
		</ul>
		
		<?php
	}
	
	/**
	* Build upload modal
	* @since 1.4
	*/
	 
	public function build_upload_modal(){
		
		$maxupload = $this->utils->formatBytes(wp_max_upload_size());
		$maxupload = str_replace(" ", "", $maxupload);
		?>
			
		
		<div id="a2020_upload_modal" uk-modal>
			<div class="uk-modal-dialog uk-modal-body uk-padding-remove" style="">
				
				
				
				<div class="uk-padding a2020-border-bottom" style="padding-top:15px;padding-bottom:15px;">
					<div class="uk-h4 uk-margin-remove-bottom"><?php _e('Upload','admin2020')?></div>
					<button class="uk-modal-close-default" type="button" uk-close></button>
				</div>
				
				<div class="uk-padding">
					
					<input type="file" 
					class="filepond"
					name="filepond" 
					multiple 
					id="a2020_file_upload"
					data-allow-reorder="true"
					data-max-file-size="<?php echo $maxupload?>"
					data-max-files="30">
					
				</div>
			</div>
		</div>
		
		
		<?php
	}
	
	/**
	* Build media modal
	* @since 1.4
	*/
	
	public function build_media_modal(){
		?>
			
		<div id="admin2020MediaViewer" class="uk-flex-top" uk-modal >
		
			<div class="uk-modal-dialog uk-margin-auto-vertical uk-padding-remove uk-box-shadow-large" >
			
				<div id="admin2020MediaViewer_content">
				
				</div>
			
			</div>
		</div>
		
		
		<?php
	}
	/**
	* Build media page head
	* @since 1.4
	*/
	
	public function build_header(){
		
		$args = array('public'   => true);
		$output = 'objects'; 
		$post_types = get_post_types( $args, $output );
		
		?>
		<div class=" uk-margin-bottom" uk-grid>
			<div class="uk-width-expand">
				<div class="uk-h2"><?php _e('Content','admin2020') ?></div>
			</div>
			<div class="uk-width-auto">
				
				
				<button class="uk-button uk-button-primary"><?php _e('New','admin2020') ?></button>
				<div uk-dropdown="offset:0;pos:bottom-justify;">
					<ul class="uk-nav uk-dropdown-nav">
						<?php foreach ($post_types as $type){ 
							
							$nicename = $type->labels->singular_name;
							$type = $type->name;
							$link = 'post-new.php?post_type='.$type;
							
							?>	
							<li><a href="<?php echo $link?>"><?php echo $nicename?></a></li>
						<?php } ?>
					</ul>
				</div>
			</div>
		</div>
		<?php
		
	}
	
	/**
	* Build media view filters
	* @since 1.4
	*/
	
	public function build_view_filters(){
		
		$attachment_size = $this->utils->get_user_preference('attachment_size');
		
		if($attachment_size == false || $attachment_size == ""){
			$this->attachment_size = 100;
		} else {
			$this->attachment_size = $attachment_size;
		}
		
		$folders_info = $this->folders->component_info();
		$folder_module = $folders_info['option_name']; 	
		?>
		
		<div class=" uk-margin-bottom" uk-grid>
			<div class="uk-width-expand">
				<?php if(!$this->utils->is_locked($folder_module) && $this->utils->enabled($this->folders)){ ?>
				<button id="a2020_folder_toggle" uk-tooltip="title:<?php _e('Toggle Folders','admin2020')?>;delay:500" class="uk-button uk-button-default a2020_make_light a2020_make_square">
					<span class="uk-icon" uk-icon="folder"></span>
				</button>
				<?php } ?>
				<button id="a2020_list_view" uk-tooltip="title:<?php _e('Toggle List View','admin2020')?>;delay:500" class="uk-button uk-button-default a2020_make_light a2020_make_square">
					<span class="uk-icon" uk-icon="list"></span>
				</button>
			</div>
			
			<div class="uk-width-auto">
				<button uk-tooltip="title:<?php _e('Search','admin2020')?>;delay:500" class="uk-button uk-button-default a2020_make_light a2020_make_square">
					<span class="uk-icon" uk-icon="search"></span>
				</button>
				<div uk-drop="pos:left-center;mode:click">
					<input class="uk-input" id="a2020_search_content" type="text" placeholder="<?php _e('Search Content','admin2020')?>" autofocus>
				</div>
				
				<button uk-tooltip="title:<?php _e('Filters','admin2020')?>;delay:500" class="uk-button uk-button-default a2020_make_light a2020_make_square">
					<span class="dashicons dashicons-filter uk-icon" style="font-family: dashicons;line-height: inherit"></span>
				</button>
				
				<div uk-dropdown="mode:click" style="width: 400px">
					
					<ul uk-accordion>
						<li >
							<a class="uk-accordion-title uk-h6 uk-margin-remove-bottom" href="#"><?php _e('Month','admin2020') ?></a>
							<div class="uk-accordion-content">
								<ul class="uk-nav uk-nav-default">
									<li uk-filter-control="group: month" class="uk-active" ><a href="#"><?php _e('All','admin2020')?></a></li>
									<?php
									for($m=1; $m<=12; ++$m){
										$month = date('F', mktime(0, 0, 0, $m, 1));
										?><li uk-filter-control="filter: [data-month='<?php echo $month ?>'];group: month"><a href="#"><?php echo $month?></a></li><?php	  
									}	  
								    ?>
								</ul>
							</div>
						</li>
						<li >
							<a class="uk-accordion-title uk-h6 uk-margin-remove-bottom" href="#"><?php _e('Year','admin2020') ?></a>
							<div class="uk-accordion-content">
								<ul class="uk-nav uk-nav-default">
									<li uk-filter-control="group: year" class="uk-active" ><a href="#"><?php _e('All','admin2020')?></a></li>
									<?php
									$today = date("Y-m-d");
									for($m=0; $m<=8; ++$m){
									
										$year = date('Y', strtotime($today." - ".$m." years"));
										?><li uk-filter-control="filter: [data-year='<?php echo $year ?>'];group: year"><a href="#"><?php echo $year?></a></li><?php
										
									} ?>
								</ul>
							</div>
						</li>
						<li >
							<a class="uk-accordion-title uk-h6 uk-margin-remove-bottom" href="#"><?php _e('Users','admin2020') ?></a>
							<div class="uk-accordion-content">
								<ul class="uk-nav uk-nav-default">
									<li uk-filter-control="group: users" class="uk-active" ><a href="#"><?php _e('All','admin2020')?></a></li>
									<?php
									$blogusers = get_users();
									foreach($blogusers as $user){
										$username = $user->display_name;
										?><li uk-filter-control="filter: [data-username='<?php echo $username ?>'];group: users"><a href="#"><?php echo $username?></a></li><?php
									} ?>
								</ul>
							</div>
						</li>
						
					</ul>
					
				</div>
				
				<button uk-tooltip="delay:300;title:<?php _e('Upload files','admin2020')?>" uk-toggle="target:#a2020_upload_modal" class="uk-button uk-button-default a2020_make_light a2020_make_square">
					<span uk-icon="cloud-upload"></span>
				</button>
				
				<button uk-tooltip="title:<?php _e('Settings','admin2020')?>;delay:500" class="uk-button uk-button-default a2020_make_light a2020_make_square">
					<span class="uk-icon" uk-icon="settings"></span>
				</button>
				<div class="uk-width-medium" uk-dropdown="pos:bottom-left;mode:click">
					
					<ul class="uk-nav">
						<li class="uk-nav-header uk-margin-small-bottom" style="text-transform:none"><?php _e('Thumnbnail size','admin2020')?></li>
						<li>
							<div class="uk-grid-small" uk-grid>
								<div class="uk-width-auto">
									<span uk-icon="icon:image;ratio:0.7"></span>
								</div>
								<div class="uk-width-small">
									<input class="uk-range" id="a2020_atachment_size" type="range" value="<?php echo $attachment_size?>" min="75" max="250" step="5">
								</div>
								<div class="uk-width-auto">
									<span uk-icon="icon:image"></span>
								</div>
							</div>
						</li>
					</ul>
					
				</div>
			</div>
		</div>
		<?php
		
	}
	
	/**
	* Builds media
	* @since 1.4
	*/
	
	public function build_media($attachments){
		
		if($this->attachment_size > 1){
			
		} else {
			$attachment_size = $this->utils->get_user_preference('attachment_size');
			
			if($attachment_size == false || $attachment_size == ""){
				$this->attachment_size = 100;
			} else {
				$this->attachment_size = $attachment_size;
			}
		}
		
		?>
		
			
		<?php if (count($attachments) < 1) { ?>
		
			<p class="uk-text-bold"><?php _e('No content found','admin2020') ?></p>
			
		<?php } else { ?>	
			
			<?php foreach ( $attachments as $attachment ) {
				
				$this->build_date_break($attachment);
				
				echo $this->build_single_attachment($attachment);
				
			} ?>
		
		<?php } ?>	
			
		<?php
		
	}
	
	/**
	* Builds date divider  
	* @since 1.4
	*/
	
	public function build_date_break($attachment){
		
		$current = $this->media_date;
		$postdate = $attachment->post_date;
		
		if (date('d/m/Y',strtotime($postdate)) == date('d/m/Y')){
			$stamp = __('Today','admin2020');
		} else {
			$stamp = human_time_diff( date('U',strtotime($postdate)), current_time('timestamp') )  . ' ' . __('ago','admin2020');
		}
		
		if ($stamp != $current){ ?>
			<div admin2020_file_size_order="" class="uk-width-1-1 uk-text-meta uk-text-bold"><?php echo $stamp ?></div>
		<?php }
		
		$this->media_date = $stamp;
		
	}
	
	/**
	* Builds media query 
	* @since 1.4
	*/
	
	public function build_media_query(){
		
		$args = array('public'   => true);
		$output = 'objects'; 
		$post_types = get_post_types( $args, $output );
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$post_types_enabled = $this->utils->get_option($optionname,'post-types-content');
		$types = array();
		
		if($post_types_enabled && is_array($post_types_enabled)){
			
			$types = $post_types_enabled;
			
		} else { 
		
			foreach($post_types as $posttype){
				array_push($types,$posttype->name);
			}
		}
		
		$args = array(
		  'post_type' => $types,
		  'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'inherit'),
		  'posts_per_page' => 70,
		  'orderby' => 'date',
		  'order'   => 'DESC',
		);
		wp_reset_query();
		$attachments = new WP_Query($args);
		$post_ids = $attachments->get_posts();
		
		return $post_ids;
		
	}
	
	/**
	* Builds media card
	* @since 1.4
	*/
	
	public function build_single_attachment($attachment){
		
		ob_start();
		
		//ATTACHMENT INFO
		$attachment_id = $attachment->ID;
		$post_type = $attachment->post_type;
		
		if($post_type != 'attachment'){
			echo $this->build_single_post($attachment);
			return;
		}
		
		$mime_type = $attachment->post_mime_type;
		$name = $attachment->post_title;
		$caption = $attachment->post_excerpt;
		
		try {
		   $filesize = filesize( get_attached_file( $attachment_id ) );
		} catch (Throwable $e) {
		   $filesize = "NA";
		}
		
		$formatted_size = $this->utils->formatBytes($filesize);
		$alt_text = get_post_meta($attachment_id , '_wp_attachment_image_alt', true);
		$guid = $attachment->guid;
		//AUTHOR
		$author = $attachment->post_author;
		$user = get_user_by('ID',$author);
		$display_name = $user->display_name;
		//DATES
		$attachment_date = $attachment->post_date;
		$uploadedon = date('Y-m-d',strtotime($attachment_date));
		$post_month = date('F',strtotime($attachment_date));
		$post_year = date('Y',strtotime($attachment_date));
		$folderid = get_post_meta( $attachment_id, 'admin2020_folder', true );
		
		if($this->attachment_size == ""){
			$attachment_size = $this->utils->get_user_preference('attachment_size');
			
			if($attachment_size == false || $attachment_size == ""){
				$this->attachment_size = 100;
			} else {
				$this->attachment_size = $attachment_size;
			}
		}
		
		?>
		<div class="uk-width-auto attachment_wrap" post_type="<?php echo $post_type?>" 
			attachment_id="<?php echo $attachment_id?>"
			data-month='<?php echo $post_month?>'
			data-year='<?php echo $post_year?>'
			data-username='<?php echo $display_name?>'
			a2020_folder='<?php echo $folderid?>'
			id="file_<?php echo $attachment_id?>"
			draggable="true"
			>
			
			<div class="uk-background-default a2020_attachment uk-box-shadow-small" 
			onclick='a2020_select_media_item(this, event)'
			style="height: <?php echo $this->attachment_size.'px' ?>;">
			
				<div class="a2020_image_wrap" style="display:inline;">
					
					<?php if (strpos($mime_type, 'image') !== false) {
					
						//URLS
						$attachment_url = wp_get_attachment_url($attachment_id);
						$attachment_info = wp_get_attachment_image_src($attachment_id,'medium');
						$small_src = $attachment_info[0];
					    
						?>
						<img class="uk-image" style="height: 100%" src="<?php echo $small_src ?>">
						
					<?php } else if (strpos($mime_type, 'video') !== false) { ?>
					
						<video src="<?php echo $guid?>" controls uk-video="autoplay: false" style="height:100%"></video>
						
					<?php } else if (strpos($mime_type, 'zip') !== false) { ?>
						
							<div class="uk-flex uk-flex-center uk-flex-column uk-flex-middle uk-height-1-1 uk-width-small">
								<span class="dashicons dashicons-media-archive" style="display: contents;font-size: 40px;"></span>
								<div class="uk-text-center" style="padding:10px;max-width:100%;overflow: hidden;"><?php echo $name?></div>
							</div>		
						
					<?php } else if (strpos($mime_type, 'pdf') !== false) { ?>
						
							<div class="uk-flex uk-flex-center uk-flex-column uk-flex-middle uk-height-1-1 uk-width-small">
								<span uk-icon="icon:file-pdf;ratio:2"></span>
								<div class="uk-text-center" style="padding:10px;max-width:100%;overflow: hidden;"><?php echo $name?></div>
							</div>	
					
					<?php } else if (strpos($mime_type, 'application') !== false) { ?>
					
						<div class="uk-flex uk-flex-center uk-flex-column uk-flex-middle uk-height-1-1 uk-width-small">
							<span uk-icon="icon:file-text;ratio:2"></span>
							<div class="uk-text-center" style="padding:10px;max-width:100%;overflow: hidden;"><?php echo $name?></div>
						</div>
						
					<?php } else if (strpos($mime_type, 'csv') !== false) { ?>
						
						<div class="uk-flex uk-flex-center uk-flex-column uk-flex-middle uk-height-1-1 uk-width-small">
							<span class="dashicons dashicons-media-spreadsheet" style="display: contents;font-size: 40px;"></span>
							<div class="uk-text-center" style="padding:10px;max-width:100%;overflow: hidden;"><?php echo $name?></div>
						</div>	
					
					<?php } else if (strpos($mime_type, 'audio') !== false) { ?>
					
						<div class="uk-flex uk-flex-center uk-flex-column uk-flex-middle uk-height-1-1 uk-width-small">
							<span uk-icon="icon:microphone;ratio:2"></span>
							<div class="uk-text-center" style="padding:5px;max-width:100%;overflow: hidden;"><?php echo $name?></div>
						</div>
					
					<?php } ?>
					</div>
					
					<div class="attachment_meta_list">
						<div class="uk-text-meta uk-text-emphasis uk-text-bold attachment_author"><?php echo $name?></div>
						<div class="uk-text-meta"">
							<?php echo $formatted_size ?>
						</div>
						<button class="uk-button uk-button-link" onclick='a2020_fetch_attachment_modal(<?php echo $attachment_id?>)'><?php _e('View details','admin2020') ?></button>
					</div>
				
				
			</div>
			<div class="attachment_meta" uk-dropdown="delay-show:300" style="padding:10px;max-width: 150px" >
				<div class="uk-text-meta uk-text-emphasis uk-text-bold attachment_author"><?php echo $name?></div>
				<div class="uk-text-meta"">
					<?php echo $formatted_size ?>
				</div>
				<button class="uk-button uk-button-link" onclick='a2020_fetch_attachment_modal(<?php echo $attachment_id?>)'><?php _e('View details','admin2020') ?></button>
			</div>
			
		</div>
		
		<?php
		
		return ob_get_clean();
	}
	
	/**
	* Builds post card
	* @since 1.4
	*/
	
	public function build_single_post($attachment){
		
		ob_start();
		
		//POST INFO
		$attachment_id = $attachment->ID;
		$post_type = $attachment->post_type;
		$post_status = $attachment->post_status;
		$name = $attachment->post_title;
		$caption = get_the_excerpt($attachment);
		//AUTHOR
		$author = $attachment->post_author;
		$user = get_user_by('ID',$author);
		$display_name = $user->display_name;
		//DATES
		$attachment_date = $attachment->post_date;
		$postedon = date('Y-m-d',strtotime($attachment_date));
		$post_month = date('F',strtotime($attachment_date));
		$post_year = date('Y',strtotime($attachment_date));
		
		$folderid = get_post_meta( $attachment_id, 'admin2020_folder', true );
		
		if($this->attachment_size == ""){
			$attachment_size = $this->utils->get_user_preference('attachment_size');
			
			if($attachment_size == false || $attachment_size == ""){
				$this->attachment_size = 100;
			} else {
				$this->attachment_size = $attachment_size;
			}
		}
		
		?>
		<div class="uk-width-auto attachment_wrap" 
			post_type="<?php echo $post_type?>" 
			attachment_id="<?php echo $attachment_id?>"
			data-month='<?php echo $post_month?>'
			data-year='<?php echo $post_year?>'
			data-username='<?php echo $display_name?>'
			a2020_folder='<?php echo $folderid?>'
			id="file_<?php echo $attachment_id?>"
			draggable="true"
			>
		
			<div class="uk-background-default a2020_attachment uk-box-shadow-small uk-overflow-hidden" 
			post_type="<?php echo $post_type?>"
			onclick='a2020_select_media_item(this, event)'
			style="max-width:250px;height: <?php echo $this->attachment_size.'px' ?>;">
				
				<div class="uk-padding-small a2020_post_details" >
					<div class="post_titles">
						<div class="uk-text-bold""><?php echo $name?></div>
						<div class="uk-text-meta uk-margin-small-bottom"><?php echo __('By','admin2020').' '.$display_name?></div>
						<button class="uk-button uk-button-link list_view_details" onclick='a2020_fetch_attachment_modal(<?php echo $attachment_id?>)'><?php _e('View details','admin2020') ?></button>
					</div>
					<div class="post_statuses">
						<div class="uk-label uk-margin-small-bottom"><?php echo $post_type?></div>
						<div class="uk-label uk-margin-small-bottom <?php echo $post_status?>"><?php echo $post_status?></div>
					</div>
					<div class="post_description">
						<div class="uk-text-meta uk-margin-small-bottom"><?php echo $caption?></div>
					</div>
				</div>
				
			</div>
			
			<div class="attachment_meta" uk-dropdown="delay-show:300" style="padding:10px;max-width: 150px" >
				<button class="uk-button uk-button-link" onclick='a2020_fetch_attachment_modal(<?php echo $attachment_id?>)'><?php _e('View details','admin2020') ?></button>
			</div>
		</div>
		
		<?php
		
		return ob_get_clean();
	}
	
	
	/**
	* Loads more media from jquery
	* @since 1.4
	*/
	
	public function a2020_fetch_more_media(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			$page = $this->utils->clean_ajax_input($_POST['page']);
			$term = $this->utils->clean_ajax_input($_POST['term']);
			$folder_id = $this->utils->clean_ajax_input($_POST['folder_id']);
			
			
			$args = array('public'   => true);
			$output = 'objects'; 
			$post_types = get_post_types( $args, $output );
			$types = array();
			$info = $this->component_info();
			$optionname = $info['option_name'];
			$post_types_enabled = $this->utils->get_option($optionname,'post-types-content');
			
			if($post_types_enabled && is_array($post_types_enabled)){
				
				$types = $post_types_enabled;
				
			} else { 
			
				foreach($post_types as $posttype){
					array_push($types,$posttype->name);
				}
			}
			
			$args = array(
			  'post_type' => $types,
			  'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'inherit'),
			  'posts_per_page' => 70,
			  'paged' => $page + 1,
			  'orderby' => 'date',
			  'order'   => 'DESC',
			  's' => $term,
			);
			
			if ($folder_id == 'uncategorised'){
				
				$args['meta_query'] = array(
					array(
						'key' => 'admin2020_folder',
						'compare' => 'NOT EXISTS'
					)
				);
				
			} else if($folder_id != ""){
				
				$args['meta_query'] = array(
					array(
						'key' => 'admin2020_folder',
						'value' => $folder_id,
						'compare' => '='
					)
				);
				
			}
			
			wp_reset_query();
			$attachments = new WP_Query($args);
			
			if($attachments->have_posts()){
				$post_ids = $attachments->get_posts();
				echo $this->build_media($post_ids);
			} else {
				echo '';
			}
			
		}
		die();
		
	}
	
	
	/**
	* Searches Content
	* @since 1.4
	*/
	
	public function a2020_search_content(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			$term = $this->utils->clean_ajax_input($_POST['term']);
			
			$args = array('public'   => true);
			$output = 'objects'; 
			$post_types = get_post_types( $args, $output );
			$info = $this->component_info();
			$optionname = $info['option_name'];
			$post_types_enabled = $this->utils->get_option($optionname,'post-types-content');
			
			if($post_types_enabled && is_array($post_types_enabled)){
				
				$types = $post_types_enabled;
				
			} else { 
			
				foreach($post_types as $posttype){
					array_push($types,$posttype->name);
				}
			}
			
			$args = array(
			  'post_type' => $types,
			  'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'inherit'),
			  'posts_per_page' => 70,
			  's' => $term,
			  'orderby' => 'date',
			  'order'   => 'DESC',
			);
			wp_reset_query();
			$attachments = new WP_Query($args);
			
			$post_ids = $attachments->get_posts();
			echo $this->build_media($post_ids);
			
		}
		die();
		
	}
	
	
	/**
	* Requery items based on folder id
	* @since 1.4
	*/
	
	public function admin2020_set_content_folder_query(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			$term = $this->utils->clean_ajax_input($_POST['term']);
			$folder_id = $this->utils->clean_ajax_input($_POST['folder_id']);
			
			$args = array('public'   => true);
			$output = 'objects'; 
			$post_types = get_post_types( $args, $output );
			$info = $this->component_info();
			$optionname = $info['option_name'];
			$post_types_enabled = $this->utils->get_option($optionname,'post-types-content');
			$types = array();
			
			if($post_types_enabled && is_array($post_types_enabled)){
				
				$types = $post_types_enabled;
				
			} else { 
			
				foreach($post_types as $posttype){
					array_push($types,$posttype->name);
				}
			}
			
			$args = array(
			  'post_type' => $types,
			  'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'inherit'),
			  'posts_per_page' => 70,
			  's' => $term,
			  'orderby' => 'date',
			  'order'   => 'DESC',
			);
			
			if ($folder_id == 'uncategorised'){
				
				$args['meta_query'] = array(
					array(
						'key' => 'admin2020_folder',
						'compare' => 'NOT EXISTS'
					)
				);
				
			} else if($folder_id != ""){
				
				$args['meta_query'] = array(
					array(
						'key' => 'admin2020_folder',
						'value' => $folder_id,
						'compare' => '='
					)
				);
				
			}
			
			wp_reset_query();
			$attachments = new WP_Query($args);
			
			$post_ids = $attachments->get_posts();
			echo $this->build_media($post_ids);
			
		}
		die();
		
	}
	/**
	* Loads post modal
	* @since 1.4
	*/
	public function a2020_fetch_post_modal($attachment){
		
		$thepost = $attachment;
		$attachment_id = $thepost->ID;
		$post_url = get_the_permalink($attachment_id);
		$post_title = get_the_title($attachment_id);
		$date_format = get_option('date_format');
		$posted_date = get_the_date($date_format,$attachment_id);
		$author_id = $thepost->post_author;
		$user_info = get_userdata($author_id);
		$username = $user_info->user_login;
		$meta_string = $posted_date.', '.$username;
		$post_type = get_post_type($thepost);
		
		$edit_url = get_edit_post_link($attachment_id);
		
		$categories = get_categories( array(
			'orderby' => 'name',
			'order'   => 'ASC',
			'hide_empty' => false,
		) );
		
		$post_cats = wp_get_post_categories($attachment_id);
		
		
		?>
		
		<span style="display:none" id="admin2020_viewer_currentid"><?php echo $attachment_id?></span>
		<button class="uk-modal-close-default" type="button" uk-close></button>
	
		<div class="uk-padding" style="max-width: 100%;">
	
		  <iframe class="uk-box-shadow-medium" src="<?php echo $post_url?>" id="admin2020_post_preview" width="100%" height="300" style="border:none;border-radius:4px;">
		  </iframe>
		</div>
	
		<div class="uk-padding" style="padding-bottom:0;">
	
	
		  <div class="uk-grid-small" uk-grid>
	
			<div class="uk-h4 uk-width-expand uk-text-bold uk-margin-remove">
			  <?php echo $post_title?>
			</div>
	
			<?php if(current_user_can( 'edit_posts' , $attachment_id)){ ?>
			  <div class="uk-width-auto"><a href="<?php echo $edit_url?>" id="admin2020_edit_post" uk-icon="icon: file-edit" uk-tooltip="<?php _e('Edit','admin2020')?>"></a></div>
			<?php } ?>
			<div class="uk-width-auto"><a href="#" id="admin2020_duplicate_post" uk-icon="icon: copy" uk-tooltip="<?php _e('Duplicate','admin2020')?>" onclick="a2020_duplicate_post(<?php echo $attachment_id?>)"></a></div>
			<div class="uk-width-auto"><a href="<?php echo $post_url?>" target="_blank" id="admin2020_view_post" uk-icon="icon: link" uk-tooltip="<?php _e('View','admin2020')?>"></a></div>
	
	
			<div class="uk-width-auto">
				<a href="#" uk-icon="icon:chevron-left" onclick="switchinfo('left',<?php echo $attachment_id?>)"></a>
			</div>
	
			<div class="uk-width-auto">
				<a href="#" uk-icon="icon:chevron-right" onclick="switchinfo('right',<?php echo $attachment_id?>)"></a>
			</div>
	
			<div class="uk-width-1-1">
			  <span class="uk-text-meta"><span uk-icon="file-edit" class="uk-margin-small-right"></span><?php echo esc_html($username)?></span>
			  <span class="uk-text-meta uk-margin-left"><span uk-icon="calendar" class="uk-margin-small-right"></span><?php echo esc_html(get_the_date(get_option('date_format'),$attachment_id))?></span>
			</div>
		  </div>
	
	
		  <ul uk-switcher="connect: .post_preview_switcher" class="uk-width-1-1 uk-subnav uk-subnav-pill" id="admin2020_post_switcher">
			  <li><a href="#"><?php _e("Content",'admin2020')?></a></li>
			  <li><a href="#"><?php _e("Settings",'admin2020')?></a></li>
			  <?php if($post_type != 'page') { ?>
			  <li><a href="#"><?php _e("Categories",'admin2020')?></a></li>
			  <?php } ?>
		  </ul>
	
		</div>
	
		<ul class="uk-switcher uk-margin post_preview_switcher uk-padding" style="padding-top:0;max-height:300px;min-height: 300px;overflow:auto;padding-bottom:0;">
	
	
	
		  <li><!-- CONTENT -->
			<form class="uk-form-stacked uk-margin-top">
			  <div uk-grid class="uk-grid-small">
				<div class="uk-width-1-1">
					<label class="uk-form-label" for="form-stacked-text"><?php _e('Title','admin2020')?></label>
					<div class="uk-form-controls">
						<input class="uk-input" id="admin2020_viewer_title" type="text" placeholder="Title..." value="<?php echo esc_html($post_title)?>">
					</div>
				</div>
			  </div>
	
			  <textarea id="post_preview_editor" style="width:100%"><?php echo $thepost->post_content?></textarea>
	
			</form>
	
	
	
	
		  </li><!-- END OF CONTENT -->
	
		  <li><!-- SETTINGS -->
	
			<?php
			$all_statuses = get_post_statuses($attachment_id);
			$current_stats = get_post_status($attachment_id);
			$image = get_the_post_thumbnail_url($attachment_id);
			$imageid = get_post_thumbnail_id($attachment_id);
			?>
			<form class="uk-form-stacked uk-margin-top">
	
			  <div uk-grid class="uk-grid-small">
				<div class="uk-width-1-1">
					<label class="uk-form-label" for="form-stacked-text"><?php _e('Status','admin2020')?></label>
					<div class="uk-form-controls">
					  <select class="uk-select" id='admin2020_post_status'>
						<?php
						foreach ($all_statuses as $key => $status){
						  $nice_name = $status;
						  $selec = '';
	
						  if($key == $current_stats){
							$selec = 'selected';
						  }
	
						  ?><option value="<?php echo $key?>" <?php echo $selec?>><?php echo $nice_name?></option><?php
						}
						?>
					  </select>
					</div>
				</div>
	
				<div class="uk-width-1-1">
	
				  <span class="uk-form-label uk-margin-small-bottom"><?php _e('Featured Image','admin2020')?></span>
	
				  <div class="uk-background-muted" id="admin2020_post_image_select" style="position:relative;">
	
	
					<div onclick="admin2020_set_featured_image()" style="min-height:150px;position:relative;cursor: pointer">
					  <span class="uk-position-center"uk-icon="icon:image;ratio:3" style="z-index:1"></span>
					  <img data-src="<?php echo $image?>" data-id="<?php echo $imageid?>" width="1800" height="1200" id="admin2020_post_image" uk-img style="z-index:2;position:relative">
					</div>
	
					<span class="uk-icon-button uk-position-small uk-position-top-right"uk-icon="icon:close;" style="z-index:4" onclick="jQuery('#admin2020_post_image').attr('data-src','');jQuery('#admin2020_post_image').attr('data-id','');"></span>
				  </div>
	
				</div>
	
			  </div>
	
			</form>
	
		  </li><!-- END OF SETTINGS -->
	
		  <?php if($post_type != 'page') { ?>
		  <li><!-- CATEGORIES -->
			<div class="uk-text-emphasis uk-margin-small-bottom uk-margin-top"><?php _e('Categories','admin2020') ?></div>
			<form class="uk-form uk-form-small uk-child-width-1-1 uk-grid-collapse admin2020_categories" uk-grid>
			  <?php
			  foreach ($categories as $category){
				$id = $category->term_id;
				$name = $category->name;
				$checked = '';
	
				if(in_array($id,$post_cats)){
				  $checked = 'checked';
				}
				?>
				<label><input class="uk-checkbox uk-margin-small-right" type="checkbox" <?php echo $checked?> value="<?php echo $id ?>"><?php echo $name ?></label>
				<?php
			  }
			  ?>
			</form>
	
		  </li><!-- END OF CATEGORIES -->
		<?php } ?>
	
	
		</ul>
	
	
		<div class="uk-padding uk-background-default" style="position:relative;padding-top:15px;padding-bottom:15px;border-top:1px solid rgba(162,162,162,0.2)">
	
		 
	
		  <?php if(current_user_can( 'edit_post' , $attachment_id)){ ?>
			<button class="uk-button uk-button-primary uk-align-right uk-margin-remove" type="button" id="admin2020_save_post" onclick="admin2020_save_post()"><?php _e('Save','admin2020')?></button>
		  <?php } ?>
		  <?php if(current_user_can( 'delete_post' , $attachment_id)){ ?>
			<button class="uk-button uk-button-danger" type="button" onclick="a2020_delete_item(<?php echo $attachment_id?>)"><?php _e('Delete','admin2020') ?></button>
		  <?php } ?>
		</div>
		
		<?php
		
		
	}	
	
	
	/**
	* Loads attachment modal
	* @since 1.4
	*/
	public function a2020_fetch_attachment_modal(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			$attachment_id = $this->utils->clean_ajax_input($_POST['attachmentid']);
			
			$attachment = get_post($attachment_id);
			$post_type = $attachment->post_type;
			
			if($post_type != 'attachment'){
				$this->a2020_fetch_post_modal($attachment);
				die();
			}
			
			$attchmenttitle = get_the_title($attachment_id);
			$attachment_url = wp_get_attachment_url($attachment_id);
		
			$postdate = $attachment->post_date;
			$attachmenttype = $attachment->post_mime_type;
			$pieces = explode("/", $attachmenttype);
			$maintype = $pieces[0];
		
			$alt_text = get_post_meta($attachment_id , '_wp_attachment_image_alt', true);
		
			$filesize = filesize( get_attached_file( $attachment_id ) );
		
			$attachmentinfo = wp_get_attachment_image_src($attachment_id, 'full');
			$width = $attachmentinfo[1];
			$height = $attachmentinfo[2];
		
			if (strpos($attachmenttype, 'image') !== false || strpos($attachmenttype, 'video') !== false ) {
			  $dimensions = $width."x".$height;
			} else {
			  $dimensions = false;
			}
			
			$filesize = $this->utils->formatBytes($filesize);
		
			$userid = $attachment->post_author;
			$user = get_user_by('ID',$userid);
		
			?>
		
			<span style="display:none" id="admin2020_viewer_currentid"><?php echo $attachment_id?></span>

			<button class="uk-modal-close-default" type="button" uk-close></button>

			<div class="uk-padding" style="">


			  <?php
			  if (strpos($attachmenttype, 'image') !== false) {

				?><img id="admin2020imgViewer" src="<?php echo $attachment_url?>" class="uk-image uk-box-shadow-medium uk-align-center uk-margin-remove-bottom" style="border-radius:4px;max-height:350px;"></><?php

			  } else if (strpos($attachmenttype, 'video') !== false) {

				?><video id="admin2020videoViewer" src="<?php echo $attachment_url?>" playsinline controls uk-video="autoplay: false" class="uk-align-center uk-margin-remove-bottom" style="border-radius:4px;max-height:350px;"></video><?php
				
			  } else if (strpos($attachmenttype, 'zip') !== false) {
				
				?><div id="admin2020docViewer" class="uk-flex uk-flex-center uk-flex-middle uk-align-center uk-margin-remove-bottom" style="width:100%;height:200px;float:left;">
					<span class="dashicons dashicons-media-archive" style="display: contents;font-size: 60px;"></span>
				</div><?php		
				
			  } else if (strpos($attachmenttype, 'csv') !== false) {
				
				?><div id="admin2020docViewer" class="uk-flex uk-flex-center uk-flex-middle uk-align-center uk-margin-remove-bottom" style="width:100%;height:200px;float:left;">
				    <span class="dashicons dashicons-media-spreadsheet" style="display: contents;font-size: 60px;"></span>
				</div><?php		
				
			  } else if (strpos($attachmenttype, 'pdf') !== false) {
				
				?><div id="admin2020docViewer" class="uk-flex uk-flex-center uk-flex-middle uk-align-center uk-margin-remove-bottom" style="width:100%;height:200px;float:left;">
				  <span uk-icon="icon: file-pdf;ratio:4"></span>
				</div><?php	

			  } else if (strpos($attachmenttype, 'application') !== false) {

				?><div id="admin2020docViewer" class="uk-flex uk-flex-center uk-flex-middle uk-align-center uk-margin-remove-bottom" style="width:100%;height:200px;float:left;">
				  <span uk-icon="icon: file-text;ratio:4"></span>
				</div><?php

			  } else if (strpos($attachmenttype, 'audio') !== false) {

				?><video id="admin2020audioViewer" src="<?php echo $attachment_url?>" playsinline controls uk-video="autoplay: false" class="uk-align-center uk-margin-remove-bottom" style="border-radius:4px;max-height:350px;"></video><?php

			  }
			  ?>

			</div>

			<div class="uk-padding uk-padding-remove-vertical">

			  <div class="uk-grid-small" uk-grid>

				<div class="uk-h4 uk-width-expand uk-text-bold uk-margin-remove">
				  <?php echo $attchmenttitle?>
				</div>

				<div class="uk-width-auto">
					<a href="#" uk-icon="icon:chevron-left" onclick="switchinfo('left',<?php echo $attachment_id?>)"></a>
				</div>

				<div class="uk-width-auto">
					<a href="#" uk-icon="icon:chevron-right" onclick="switchinfo('right',<?php echo $attachment_id?>)"></a>
				</div>

				<?php if (strpos($attachmenttype, 'image') !== false){ ?>
				<div uk-lightbox class="uk-width-auto">
					<a style="float:right" class="uk-link-muted" href="<?php echo $attachment_url?>" ><span uk-icon="expand"></span></a>
				</div>
				<?php } ?>

				<div class="uk-width-1-1">
				  <span class="uk-text-meta"><span uk-icon="cloud-upload" class="uk-margin-small-right"></span><?php echo esc_html($user->display_name)?></span>
				  <span class="uk-text-meta uk-margin-left"><span uk-icon="calendar" class="uk-margin-small-right"></span><?php echo esc_html(get_the_date(get_option('date_format'),$attachment_id))?></span>
				  <span class="uk-text-meta uk-margin-left"><span uk-icon="database" class="uk-margin-small-right"></span><?php echo $filesize?></span>
				</div>
			  </div>


			  <ul uk-switcher="connect: .media-modal-tabs" class="uk-width-1-1 uk-subnav uk-subnav-pill">
				  <li><a href="#"><?php _e("Attributes",'admin2020')?></a></li>
				  <li><a href="#"><?php _e("Meta",'admin2020')?></a></li>
				  <?php
				  if (strpos($attachmenttype, 'image') !== false) {
				   ?>
					<li><a href="#" onclick="a2020_edit_image('<?php echo $attachment_url?>','<?php echo $attchmenttitle?>')" ><?php _e("Edit","admin2020") ?> </a></li>
				  <?php }?>
			  </ul>

			</div>

			  <ul class="uk-switcher uk-margin uk-padding media-modal-tabs" style="min-height: 250px;max-height:250px;overflow:auto;padding-top:0;">

				  <li><!-- SETTINGS -->


					<form class="uk-form-stacked" >
					  <div uk-grid class="uk-grid-small">



						<div class="uk-width-1-2">
							<label class="uk-form-label" for="form-stacked-text"><?php _e('Title','admin2020')?></label>
							<div class="uk-form-controls">
								<input class="uk-input" id="admin2020_viewer_input_title" type="text" placeholder="Title..." value="<?php echo esc_html($attchmenttitle)?>">
							</div>
						</div>

						<div class="uk-width-1-2">
							<label class="uk-form-label" for="form-stacked-text"><?php _e('Alt Text','admin2020')?></label>
							<div class="uk-form-controls">
								<input class="uk-input" id="admin2020_viewer_altText" type="text" placeholder="Alt Text" value="<?php echo esc_html($alt_text)?>">
							</div>
						</div>

						<div class="uk-width-1-1">
							<label class="uk-form-label" for="form-stacked-text"><?php _e('Caption','admin2020')?></label>
							<div class="uk-form-controls">
								<textarea class="uk-input" style="height:60px;" rows="2" id="admin2020_viewer_caption" type="text" placeholder="Caption..."><?php echo esc_html($attachment->post_excerpt);?></textarea>
							</div>
						</div>

						<div class="uk-width-1-1">
							<label class="uk-form-label" for="form-stacked-text"><?php _e('Description','admin2020')?></label>
							<div class="uk-form-controls">
								<textarea class="uk-input" style="height:60px;" rows="2" id="admin2020_viewer_description" type="text" placeholder="Description..."><?php echo esc_html($attachment->post_content)?></textarea>
							</div>
						</div>

						<div class="uk-width-1-1">
							<label class="uk-form-label" for="form-stacked-text">URL</label>
							<div class="uk-form-controls">
							  <div class="uk-inline uk-width-1-1" onclick="copythis(this)" style="cursor:pointer">
								<span class="uk-form-icon" uk-icon="icon:copy"></span>
								<input class="uk-input" id="admin2020_viewer_fullLink" value="<?php echo $attachment_url?>">
							  </div>
							  <span class="uk-text-success" id="linkcopied" style="display:none;float:left;margin-top:15px"><?php _e('Link copied to clipboard','admin2020')?></span>
							</div>
						</div>

					  </div>



					</form>
				  </li><!-- END OF SETTINGS -->

				  <li><!-- META -->

					<div id="admin2020MainMeta" class="" >


					  <table class="uk-table uk-table-small  uk-table-justify">
						<tbody>
							<tr>
								<td><?php _e('File Type',"admin2020")?>:</td>
								<td><?php echo $maintype?></td>
							</tr>
							<tr>
								<td><?php _e('Uploaded',"admin2020")?>:</td>
								<td><?php echo get_the_date(get_option('date_format'),$attachment_id)?></td>
							</tr>
							<tr>
								<td><?php _e('Size',"admin2020")?>:</td>
								<td><?php echo $filesize?></td>
							</tr>
							<?php
							if ($dimensions != false){ ?>
							  <tr>
								  <td><?php _e('Dimensions',"admin2020")?>:</td>
								  <td><?php echo $dimensions?></td>
							  </tr>
							<?php }?>
						</tbody>
					</table>

					</div>

				  </li><!-- END OF META -->


				  <li><!-- EDIT -->


				  </li><!-- END OF EDIT -->

			  </ul>

			  <div class="uk-padding uk-background-default" style="position:relative;padding-top:15px;padding-bottom:15px;border-top:1px solid rgba(162,162,162,0.2)">

				

				<?php if(current_user_can( 'edit_post' , $attachment_id)){ ?>
				  <button class="uk-button uk-button-primary uk-align-right uk-margin-remove" type="button" onclick="a2020_save_attachment(<?php echo $attachment_id?>)"><?php _e('Save','admin2020')?></button>
				<?php } ?>
				<?php if(current_user_can( 'delete_post' , $attachment_id)){ ?>
				  <button class="uk-button uk-button-danger" type="button" onclick="a2020_delete_item(<?php echo $attachment_id?>)"><?php _e('Delete','admin2020') ?></button>
				<?php } ?>
			  </div>
		
		
		
		
		
			<?php
			
		}
		die();
		
	}
	
	
	
	/**
	* Saves Post from content page
	* @since 1.4
	*/
	
	public function a2020_save_post(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			$title = $this->utils->clean_ajax_input($_POST['title']);
			$content = $this->utils->clean_ajax_input($_POST['content']);
			$postid = $this->utils->clean_ajax_input($_POST['postid']);
			$categories = $this->utils->clean_ajax_input($_POST['categories']);
			$status = $this->utils->clean_ajax_input($_POST['status']);
			$image = $this->utils->clean_ajax_input($_POST['image']);
			
			$my_post = array(
			  'ID'           => $postid,
			  'post_title'   => $title,
			  'post_content' => $content,
			  'post_category' => $categories,
			  'post_status' => $status
			);
			
			$newpost = wp_update_post( $my_post );
			
			if(!$newpost){
				$message = __("Unable to save item",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			if(!$image){
				delete_post_meta( $postid, '_thumbnail_id' );
			} else {
				set_post_thumbnail( $postid, $image );
			}
			
			$returndata = array();
			$returndata['message'] = __('Item Updated','admin2020');
			$returndata['html'] = $this->build_single_post(get_post($postid));
			
			echo json_encode($returndata);
			
		}
		die();
		
	}
	
	
	/**
	* Saves attachment from content page
	* @since 1.4
	*/
	
	public function a2020_save_attachment(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			
			$title = $this->utils->clean_ajax_input($_POST['title']);
			$imgalt = $this->utils->clean_ajax_input($_POST['imgalt']);
			$caption = $this->utils->clean_ajax_input($_POST['caption']);
			$description = $this->utils->clean_ajax_input($_POST['description']);
			$imgid = $this->utils->clean_ajax_input($_POST['imgid']);
			
			$attachment = array(
			'ID' => strip_tags($imgid),
			'post_title' => strip_tags($title),
			'post_content' => strip_tags($description),
			'post_excerpt' => strip_tags($caption),
			);
			update_post_meta( $imgid, '_wp_attachment_image_alt', $imgalt);
			
			$status = wp_update_post( $attachment);
			
			if(!$status){
				$message = __("Unable to save attachment",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			$returndata = array();
			$returndata['message'] = __('Attachment Saved','admin2020');
			$returndata['html'] = $this->build_single_attachment(get_post($imgid));
					  
			echo json_encode($returndata);		  
		}
		die();
	
	}	
	
	/**
	* Deletes items
	* @since 1.4
	*/
	
	public function a2020_delete_item(){ 
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			$post_id = $this->utils->clean_ajax_input($_POST['post_id']);
			
			
			if(is_array($post_id)){
				
				foreach($post_id as $an_id){
					
					if(current_user_can( 'delete_post' , $an_id)){
						
						$post_type = get_post_type($an_id);
						
						if($post_type == "attachment"){
							
							$status = wp_delete_attachment($an_id);
							
						} else {
						
							$status = wp_delete_post($an_id);
							
						}
						
					}
					
				}
				
				$returndata = array();
				$returndata['message'] = __('Items Deleted','admin2020');
				echo json_encode($returndata);
				
			} else {
				
				if(current_user_can( 'delete_post' , $post_id)){
					
					$post_type = get_post_type($post_id);
					
					if($post_type == "attachment"){
						
						$status = wp_delete_attachment($post_id);
						
					} else {
					
						$status = wp_delete_post($post_id);
						
					}
					
					if(!$status){
						$message = __("Unable to delete item",'admin2020');
						echo $this->utils->ajax_error_message($message);
						die();
					} else {
						$returndata = array();
						$returndata['message'] = __('Item Deleted','admin2020');
						echo json_encode($returndata);
					}
					
				} else {
					$message = __("insufficient privileges to delete item",'admin2020');
					echo $this->utils->ajax_error_message($message);
					die();
				}
				
			}
			
		}
		die();
		
	}
	
	/**
	* Duplicates post
	* @since 1.4
	*/
	
	public function a2020_duplicate_post(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			global $wpdb;
			$post_id = $this->utils->clean_ajax_input($_POST['postid']);
			$post = get_post( $post_id );
			
			$current_user = wp_get_current_user();
			$new_post_author = $current_user->ID;
			
			$args = array(
			'comment_status' => $post->comment_status, 
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_post_author,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_name'      => $post->post_name,
			'post_parent'    => $post->post_parent,
			'post_password'  => $post->post_password,
			'post_status'    => 'draft',
			'post_title'     => $post->post_title.' (copy)',
			'post_type'      => $post->post_type,
			'to_ping'        => $post->to_ping,
			'menu_order'     => $post->menu_order
			);
			
			$new_post_id = wp_insert_post( $args );
			
			$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
			foreach ($taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
			wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
			}
			
			$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
			if (count($post_meta_infos)!=0) {
			$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
			foreach ($post_meta_infos as $meta_info) {
			  $meta_key = $meta_info->meta_key;
			  if( $meta_key == '_wp_old_slug' ) continue;
			  $meta_value = addslashes($meta_info->meta_value);
			  $sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
			}
			$sql_query.= implode(" UNION ALL ", $sql_query_sel);
			$wpdb->query($sql_query);
			}
			$postobject = get_post($new_post_id);
			
			$returndata = array();
			$returndata['message'] = __('Post duplicated','admin2020');
			$returndata['html'] = $this->build_single_post($postobject);
			$returndata['newid'] = $new_post_id;
					  
			echo json_encode($returndata);		
			
		}
		die();	
	}
	
	
	/**
	* Adds batch rename components
	* @since 1.4
	*/
	
	public function a2020_add_batch_rename_item(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			$itemtoadd = $this->utils->clean_ajax_input($_POST['itemtoadd']);
			
			if($itemtoadd == 'filename'){
			ob_start();
			?>
			<div class="uk-grid-small uk-child-width-1- rename_item uk-flex uk-flex-middle" uk-grid>
			  <div class="uk-width-2-5">
				<span name="<?php echo $itemtoadd?>" class="batch_rename_option">
				  <span uk-icon="grid" class="uk-margin-small-right rename_drag"></span>
				  <?php _e('Filename','admin2020')?>:
				</span>
			  </div>
			
			  <div class="uk-width-expand">
				<input class="uk-input" placeholder="<?php _e("Current Filename","admin2020")?>" disabled>
			  </div>
			
			  <div class="uk-text-right uk-flex uk-flex-middle uk-flex-right uk-width-auto">
				<a href="#" onclick="jQuery(this).parent().parent().remove();build_batch_rename_preview()"><span uk-icon="minus-circle"></span></a>
			  </div>
			
			</div>
			<?php
			}
			
			if($itemtoadd == 'text'){
			ob_start();
			?>
			<div class="uk-grid-small rename_item uk-flex uk-flex-middle" uk-grid>
			  <div class="uk-width-2-5">
				<span name="<?php echo $itemtoadd?>" class="batch_rename_option">
				  <span uk-icon="grid" class="uk-margin-small-right rename_drag"></span>
				  <?php _e('Text','admin2020')?>:
				</span>
			  </div>
			
			  <div class="uk-width-expand">
				<input onkeyup="build_batch_rename_preview()" class="uk-input" placeholder="<?php _e("New text","admin2020")?>">
			  </div>
			
			  <div class="uk-text-right uk-flex uk-flex-middle uk-flex-right uk-width-auto">
				<a href="#" onclick="jQuery(this).parent().parent().remove();build_batch_rename_preview()"><span uk-icon="minus-circle"></span></a>
			  </div>
			
			</div>
			<?php
			}
			
			if($itemtoadd == 'date'){
			ob_start();
			?>
			<div class="uk-grid-small rename_item uk-flex uk-flex-middle" uk-grid>
			  <div class="uk-width-2-5">
				<span name="<?php echo $itemtoadd?>" class="batch_rename_option">
				  <span uk-icon="grid" class="uk-margin-small-right rename_drag"></span>
				  <?php _e('Date Uploaded','admin2020')?>:
				</span>
			  </div>
			
			  <div class="uk-width-expand">
				<input class="uk-input" onkeyup="build_batch_rename_preview()" placeholder="<?php _e("Format","admin2020")?>">
			  </div>
			
			  <div class="uk-text-right uk-flex uk-flex-middle uk-flex-right uk-width-auto">
				<a href="#" onclick="jQuery(this).parent().parent().remove();build_batch_rename_preview()"><span uk-icon="minus-circle"></span></a>
			  </div>
			
			</div>
			<?php
			}
			
			
			if($itemtoadd == 'original_alt'){
			ob_start();
			?>
			<div class="uk-grid-small rename_item uk-flex uk-flex-middle" uk-grid>
			  <div class="uk-width-2-5">
				<span name="<?php echo $itemtoadd?>" class="batch_rename_option">
				  <span uk-icon="grid" class="uk-margin-small-right rename_drag"></span>
				  <?php _e('Alt','admin2020')?>:
				</span>
			  </div>
			
			  <div class="uk-width-expand">
				<input class="uk-input" placeholder="<?php _e("Current Alt","admin2020")?>" disabled>
			  </div>
			
			  <div class="uk-text-right uk-flex uk-flex-middle uk-flex-right uk-width-auto">
				<a href="#" onclick="jQuery(this).parent().parent().remove();build_batch_rename_preview()"><span uk-icon="minus-circle"></span></a>
			  </div>
			
			</div>
			<?php
			}
			
			if($itemtoadd == 'extension'){
			ob_start();
			?>
			<div class="uk-grid-small rename_item uk-flex uk-flex-middle" uk-grid>
			  <div class="uk-width-2-5">
				<span name="<?php echo $itemtoadd?>" class="batch_rename_option">
				  <span uk-icon="grid" class="uk-margin-small-right rename_drag"></span>
				  <?php _e('Extension','admin2020')?>:
				</span>
			  </div>
			
			  <div class="uk-width-expand">
				<input class="uk-input" placeholder="<?php _e("Current Extension","admin2020")?>" disabled>
			  </div>
			
			  <div class="uk-text-right uk-flex uk-flex-middle uk-flex-right uk-width-auto">
				<a href="#" onclick="jQuery(this).parent().parent().remove();build_batch_rename_preview()"><span uk-icon="minus-circle"></span></a>
			  </div>
			
			</div>
			<?php
			}
			
			if($itemtoadd == 'sequence'){
			ob_start();
			?>
			<div class="uk-grid-small rename_item uk-flex uk-flex-middle" uk-grid>
			  <div class="uk-width-2-5">
				<span name="<?php echo $itemtoadd?>" class="batch_rename_option">
				  <span uk-icon="grid" class="uk-margin-small-right rename_drag"></span>
				  <?php _e('Sequence start num','admin2020')?>:
				</span>
			  </div>
			
			  <div class="uk-width-expand">
				<input class="uk-input" placeholder="<?php _e("Start Number","admin2020")?>" value="0">
			  </div>
			
			  <div class="uk-text-right uk-flex uk-flex-middle uk-flex-right uk-width-auto">
				<a href="#" onclick="jQuery(this).parent().parent().remove();build_batch_rename_preview()"><span uk-icon="minus-circle"></span></a>
			  </div>
			
			</div>
			<?php
			}
			
			if($itemtoadd == 'meta'){
			ob_start();
			?>
			<div class="uk-grid-small rename_item uk-flex uk-flex-middle" uk-grid>
			  <div class="uk-width-2-5">
				<span name="<?php echo $itemtoadd?>" class="batch_rename_option">
				  <span uk-icon="grid" class="uk-margin-small-right rename_drag"></span>
				  <?php _e('Meta key','admin2020')?>:
				</span>
			  </div>
			
			  <div class="uk-width-expand">
				<input class="uk-input" placeholder="<?php _e("Meta Key","admin2020")?>" value="">
			  </div>
			
			  <div class="uk-text-right uk-flex uk-flex-middle uk-flex-right uk-width-auto">
				<a href="#" onclick="jQuery(this).parent().parent().remove();build_batch_rename_preview()"><span uk-icon="minus-circle"></span></a>
			  </div>
			
			</div>
			<?php
			}
			
			echo ob_get_clean();
			
		}
		die();	
	}
	
	/**
	* Processes batch rename
	* @since 1.4
	*/
	
	public function a2020_process_batch_rename(){
		
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			$attachments = $this->utils->clean_ajax_input($_POST['ids']);
			$name_structure = $this->utils->clean_ajax_input($_POST['structure']);
			$name_values = $this->utils->clean_ajax_input($_POST['values']);
			$attrtibute =$this->utils->clean_ajax_input($_POST['item_to_rename']);
			
			$returndata = array();
			
			if (count($attachments) < 1){
				$returndata['error'] = __('No attachments selected','admin2020');
				echo json_encode($returndata);
				die();
			}
			
			$sequence_number = 0;
			
			foreach ($attachments as $attachment_id){
				
				
				
				$attachment = get_post($attachment_id);
				$posttype = $attachment->post_type;
				
				if($posttype != 'attachment'){
					continue;
				}
				
				$current_title = get_the_title($attachment_id);
				$post_date = get_the_date($attachment_id);
				$alt_text = get_post_meta($attachment_id , '_wp_attachment_image_alt', true);
				$attachment_url = wp_get_attachment_url($attachment_id);
				$filetype = wp_check_filetype($attachment_url);
				$extension = $filetype['ext'];
				
				$newname = "";
				$counter = 0;
				$test = "";
				
				foreach ($name_structure as $structure){
				
				  $structure = wp_strip_all_tags($structure);
				  $the_value = wp_strip_all_tags($name_values[$counter]);
				
				  if($structure == 'filename'){
					$newname = $newname . $current_title;
				  }
				  if($structure == 'text'){
					$newname = $newname . $the_value;
				  }
				  if($structure == 'date'){
				
					if(date($the_value)){
					  $newname = $newname . get_the_date($the_value,$attachment_id);
					} else {
					  $returndata['error'] = __('Invalid Date Format','admin2020');
					  echo json_encode($returndata);
					  die();
					}
				
				  }
				  if($structure == 'original_alt'){
					$newname = $newname . $alt_text;
				  }
				  if($structure == 'extension'){
					$newname = $newname . $extension;
				  }
				  if($structure == 'sequence'){
					$start_number = $the_value;
					if(!is_numeric($start_number)){
					  $start_number = 0;
					}
					$newname = $newname . ($sequence_number + $start_number);
				  }
				  if($structure == 'meta'){
					$meta_item = get_post_meta( $attachment_id, $the_value, true );
					if($meta_item){
					  $newname = $newname . $meta_item;
					}
				  }
				
				  $counter = $counter + 1;
				
				
				}
				
				$sequence_number = $sequence_number + 1;
				
				if($attrtibute == "name"){
				
				  $my_post = array(
					  'ID'           => $attachment_id,
					  'post_title'   => $newname,
				  );
				  wp_update_post( $my_post );
				
				}
				
				if($attrtibute == "alt"){
				
				  update_post_meta( $attachment_id, '_wp_attachment_image_alt', $newname );
				
				}
			
			}
			$returndata['message'] = __('Attachments successfully renamed','admin2020');
			echo json_encode($returndata);
		

		
		}
		die();
		
	}
	
	/**
	* Processes file upload
	* @since 1.4
	*/
	
	public function a2020_process_upload(){
	
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
			
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
	
			  foreach ($_FILES as $file){
	
				$uploadedfile = $file;
				$upload_overrides = array(
				  'test_form' => false
				);
	
	
				$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
				
				// IF ERROR
				if (is_wp_error($movefile)) {
					http_response_code(400);
					$returndata['error'] = __('Failed to upload file','admin2020');
					echo json_encode($returndata);
					die();
				}
				////ADD Attachment
	
				$wp_upload_dir = wp_upload_dir();
				$withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $uploadedfile['name']);
	
					$attachment = array(
						"guid" => $movefile['url'],
						"post_mime_type" => $movefile['type'],
						"post_title" => $withoutExt,
						"post_content" => "",
						"post_status" => "published",
					);
	
					$id = wp_insert_attachment( $attachment, $movefile['file'],0);
	
					$attach_data = wp_generate_attachment_metadata( $id, $movefile['file'] );
					wp_update_attachment_metadata( $id, $attach_data );
	
				////END ATTACHMENT
	
	
			  }
			  //echo $this->build_media();
			  http_response_code(200);
			  $returndata['message'] = __('Files succesfully uploaded','admin2020');
			  $returndata['html'] = $this->build_single_attachment(get_post($id));
			  echo json_encode($returndata);
			
		}
		die();
	
	}
	
	/**
	* Processes file upload from image editor
	* @since 1.4
	*/
	public function a2020_upload_edited_image() {
		
		  if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
	
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
			$current_imageid = $this->utils->clean_ajax_input($_POST['attachmentid']);
			$new_file =  $_FILES['ammended_image'];
	
			$upload_overrides = array(
			  'test_form' => false
			);
	
	
			$movefile = wp_handle_upload( $new_file, $upload_overrides );
			////ADD Attachment
			if (is_wp_error($movefile)) {
				$message = __("Unable to save attachment",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
	
			$status = update_attached_file($current_imageid,$movefile['file']);
			////ADD Attachment
			if (!$status) {
				$message = __("Unable to save attachment",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
	
			$attach_data = wp_generate_attachment_metadata( $current_imageid, $movefile['file'] );
			$status = wp_update_attachment_metadata( $current_imageid, $attach_data );
			
			if (!$status) {
				$message = __("Unable to save attachment",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			$returndata = array();
			$returndata['message'] = __('Image Saved','admin2020');
			$returndata['html'] = $this->build_single_attachment(get_post($current_imageid));
	
			////END ATTACHMENT
			echo json_encode($returndata);
		  }
		  die();
	}
	
	/**
	* Processes file upload from image editor and saves as copy
	* @since 1.4
	*/
	public function a2020_upload_edited_image_as_copy() {
		
		  if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-content-security-nonce', 'security') > 0) {
	
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
			$current_imageid = $this->utils->clean_ajax_input($_POST['attachmentid']);
			$new_file =  $_FILES['ammended_image'];
			$filename =  $this->utils->clean_ajax_input($_POST['file_name']);
			$withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename);
	
			$currentfolder = get_post_meta($current_imageid , 'admin2020_folder', true);
	
			$upload_overrides = array(
			  'test_form' => false
			);
	
	
			$movefile = wp_handle_upload( $new_file, $upload_overrides );
			////ADD Attachment
			if (is_wp_error($movefile)) {
				http_response_code(400);
				$returndata['error'] = __('Failed to upload file','admin2020');
				echo json_encode($returndata);
				die();
			}
	
			$attachment = array(
			  "guid" => $movefile['url'],
			  "post_mime_type" => $movefile['type'],
			  "post_title" => $withoutExt,
			  "post_content" => "",
			  "post_status" => "published",
			);
	
			$id = wp_insert_attachment( $attachment, $movefile['file'],0);
	
			$attach_data = wp_generate_attachment_metadata( $id, $movefile['file'] );
			wp_update_attachment_metadata( $id, $attach_data );
			update_post_meta($id, "admin2020_folder",$currentfolder);
	
			////END ATTACHMENT
			$returndata['message'] = __('File saved as copy','admin2020');
		    $returndata['html'] = $this->build_single_attachment(get_post($id));
		    echo json_encode($returndata);
			
			
		  }
		  die();
	}
	
	
}
