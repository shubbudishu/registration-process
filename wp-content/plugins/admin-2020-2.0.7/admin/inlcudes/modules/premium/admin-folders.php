<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_folders
{
    public function __construct($version, $path, $utilities)
    {
        $this->version = $version;
        $this->path = $path;
        $this->utils = $utilities;
		$this->view = 'panel';
		$this->folder_id = '';
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
		
		add_action( 'wp_enqueue_media', array($this,'add_styles'),99 );
		add_action( 'wp_enqueue_media', array($this,'add_scripts'),99 );
		add_action( 'wp_enqueue_media', array($this,'add_media_template'),99 );
		add_filter( 'wp_prepare_attachment_for_js', array($this,'pull_meta_to_attachments'), 10, 3 );
		add_action( 'init', array($this,'a2020_create_folders_cpt') );
		
		///FOLDER AJAX
		add_action('wp_ajax_admin2020_create_folder', array($this,'admin2020_create_folder'));
		add_action('wp_ajax_admin2020_delete_folder', array($this,'admin2020_delete_folder'));
		add_action('wp_ajax_admin2020_rename_folder', array($this,'admin2020_rename_folder'));
		add_action('wp_ajax_admin2020_move_to_folder', array($this,'admin2020_move_to_folder'));
		add_action('wp_ajax_admin2020_move_folder_into_folder', array($this,'admin2020_move_folder_into_folder'));
		add_action('wp_ajax_admin2020_refresh_all_folders', array($this,'admin2020_refresh_all_folders'));
		add_filter('ajax_query_attachments_args', array($this,'legacy_media_filter'));
		
		
    }
	
	/**
	 * Filters media by folder
	 * @since 1.4
	 */
	public function legacy_media_filter($args){
		
		if(isset($_REQUEST['query']['folder_id'])){
			
			$folderid = $_REQUEST['query']['folder_id'];
			
			if ($folderid == ""){ 
				
			} else if ($folderid == "uncategorised"){
				
				$args['meta_query'] = array(
					array(
						'key' => 'admin2020_folder',
						'compare' => 'NOT EXISTS'
					)
				);
				
			} else {
		
				$args['meta_query'] = array(
					array(
						'key' => 'admin2020_folder',
						'value' => $folderid,
						'compare' => '='
					)
				);
				
			}
			
		}
		
		return $args;
		
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
		$data['title'] = __('Folders','admin2020');
		$data['option_name'] = 'admin2020_admin_folders';
		$data['description'] = __('Creates the folder system for the content page and media page / modals.','admin2020');
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
		  if($disabled_for == ""){
			  $disabled_for = array();
		  }
		  ///GET ROLES
		  global $wp_roles;
		  ///GET USERS
		  $blogusers = get_users();
		  ?>
		  <div class="uk-grid" id="a2020_folder_settings" uk-grid>
			  <!-- LOCKED FOR USERS / ROLES -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Folders Disabled for','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("Folders will be disabled for any users or roles you select",'admin2020') ?></div>
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
					  jQuery('#a2020_folder_settings #a2020-role-types').tokenize2({
						  placeholder: '<?php _e('Select roles or users','admin2020') ?>'
					  });
					  jQuery(document).ready(function ($) {
						  $('#a2020_folder_settings #a2020-role-types').on('tokenize:select', function(container){
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
		
        wp_register_style(
            'admin2020_admin_folders',
            $this->path . 'assets/css/modules/admin-folders.css',
            array(),
            $this->version
        );
        wp_enqueue_style('admin2020_admin_folders');
    }
	
	/**
	* Enqueue Admin Bar 2020 scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
	  
	  ///CHECK FOR CURRENT SCREEN 
	  if(function_exists('get_current_screen')){
		  $screen = get_current_screen();
		  $theid = $screen->id;
	  } else {
		  $theid = 'toplevel_page_admin_2020_content';
	  }
	  
	  wp_enqueue_script('admin-theme-folders', $this->path . 'assets/js/admin2020/admin-folders.min.js', array('jquery'));
	  wp_localize_script('admin-theme-folders', 'admin2020_admin_folder_ajax', array(
		  'ajax_url' => admin_url('admin-ajax.php'),
		  'security' => wp_create_nonce('admin2020-admin-folder-security-nonce'),
		  'screen' => $theid,
	  ));
	  
	}
	
	/**
	* Adds media override template
	* @since 1.4
	*/
	public function add_media_template(){
		add_action( 'admin_footer', array($this,'build_media_template') );
	}
	/**
	* Creates custom folder post type
	* @since 1.4
	*/
	public function a2020_create_folders_cpt(){
	
		 $labels = array(
		  'name'               => _x( 'Folder', 'post type general name', 'admin2020' ),
		  'singular_name'      => _x( 'folder', 'post type singular name', 'admin2020' ),
		  'menu_name'          => _x( 'Folders', 'admin menu', 'admin2020' ),
		  'name_admin_bar'     => _x( 'Folder', 'add new on admin bar', 'admin2020' ),
		  'add_new'            => _x( 'Add New', 'folder', 'admin2020' ),
		  'add_new_item'       => __( 'Add New Folder', 'admin2020' ),
		  'new_item'           => __( 'New Folder', 'admin2020' ),
		  'edit_item'          => __( 'Edit Folder', 'admin2020' ),
		  'view_item'          => __( 'View Folder', 'admin2020' ),
		  'all_items'          => __( 'All Folders', 'admin2020' ),
		  'search_items'       => __( 'Search Folders', 'admin2020' ),
		  'not_found'          => __( 'No Folders found.', 'admin2020' ),
		  'not_found_in_trash' => __( 'No Folders found in Trash.', 'admin2020' )
		);
		 $args = array(
		  'labels'             => $labels,
		  'description'        => __( 'Description.', 'Add New Folder' ),
		  'public'             => false,
		  'publicly_queryable' => false,
		  'show_ui'            => false,
		  'show_in_menu'       => false,
		  'query_var'          => false,
		  'has_archive'        => false,
		  'hierarchical'       => false,
		);
		register_post_type( 'admin2020folders', $args );
	}
	
	/**
	* Adds folder id to default wp media views
	* @since 1.4
	*/
	public function pull_meta_to_attachments(  $response, $attachment, $meta ) {
		  $mimetype = get_post_mime_type($attachment->ID);
		  $pieces = explode("/", $mimetype);
		  $type = $pieces[0];
		  $folderid = get_post_meta( $attachment->ID, 'admin2020_folder', true );
		  $response[ 'properties' ]['folderid'] = $folderid;
		  $response[ 'folderid' ] = $folderid;
			 
		  return $response;
	}
	  
	  
	/**
	* Builds media template
	* @since 1.4
	*/
	public function build_media_template(){
		?>
		<!-- BUILD FOLDERS IN MODAL -->
			<script type="text/html" id="tmpl-media-frame_custom"> 
			  
			  <div class="uk-grid-collapse uk-height-1-1 a2020_legacy_filter" uk-grid uk-filter="target: .attachments">			
				<div class="uk-width-auto uk-position-relative">
					<div class="admin2020-folder-modal" id="admin2020_settings_column" style="width:270px">
						  <div class="a2020-folder-title uk-h4"><?php _e('Folders','admin2020')?></div>	
						  <div class="a2020_modal_folders">
							<?php
							$this->build_folder_panel('media');
							?>
						</div>
					</div>
				</div>
				
				<div class="uk-width-expand uk-position-relative">
				
				  <div class="media-frame-title" id="media-frame-title"></div>
				  <h2 class="media-frame-menu-heading"><?php _ex( 'Actions', 'media modal menu actions' ); ?></h2>
				  <button type="button" class="button button-link media-frame-menu-toggle" aria-expanded="false">
					  <?php _ex( 'Menu', 'media modal menu' ); ?>
					  <span class="dashicons dashicons-arrow-down" aria-hidden="true"></span>
				  </button>
				  <div class="media-frame-menu"></div>
				  
					<div class="media-frame-tab-panel">
						<div class="media-frame-router"></div>
						<div class="media-frame-content"></div>
					</div>
				</div>
				
			  </div>
			  
			  <div class="media-frame-toolbar"></div>
			  <div class="media-frame-uploader"></div>
		  </script>
		<script>
		  jQuery(document).ready( function($) {
			  
			  window.setInterval(function(){
				  a2020_add_drag();
			  }, 1000);
			 
		
			  if( typeof wp.media.view.Attachment != 'undefined' ){
				  //console.log(wp.media.view);
				  //wp.media.view.Attachment.prototype.template = wp.media.template( 'attachment_custom' );
				  wp.media.view.MediaFrame.prototype.template = wp.media.template( 'media-frame_custom' );
		
				  wp.media.view.Attachment.Library = wp.media.view.Attachment.Library.extend({
					className: function () { return 'attachment legacy_attachment folder' + this.model.get( 'folderid' ) },
					//folderName: function () { return 'attachment ' + this.model.get( 'folderid' ); },
					//attr: 'blue',
				  });
		
				  wp.media.view.Modal.prototype.on('open', function() {
				  //MODAL OPEN
					  //refreshFolderCountModal();
				  });
		
		
			  }
		  });
		  
		  function a2020_add_drag(){
			  jQuery('.attachment').attr('draggable','true');
		  }
		</script>
			  <?php
	}
	
	/**
	* Creates folder panel
	* @since 1.4
	*/
		
	public function build_folder_panel($view = null){
	
		$this->view = $view;
		?>
		  <div class="uk-grid-small" uk-grid>
		
		
			<?php
			echo $this->get_add_new_folder();
			echo $this->get_default_folders($this->view) ?>
		
			<div class="uk-width-1-1"><hr></div>
		
			<div id="admin2020folderswrap">
		
			  <?php $this->get_user_folders($this->view)?>
		
			</div>
		
		  </div>
		
		<?php
	
	}

	/**
	* Gets default folders
	* @since 1.4
	*/
		
	public function get_default_folders($view = null){ 
	
		$attachment_count = wp_count_attachments();
		$total = 0;
		
		
		foreach($attachment_count as $count){
		  $total += $count;
		}
		
		$args = array('public'   => true);
		$output = 'objects';  
		$post_types = get_post_types( $args, $output );
		$selected_post_types = array();
		
		foreach($post_types as $posttype){
			array_push($selected_post_types,$posttype->name);
		}
		
		if($view == 'media'){
			$selected_post_types = array('attachment');
		}
	
		$total = 0;
	    foreach($selected_post_types as $type){
		  
		  $allposts = wp_count_posts($type);
		  $total += $allposts->publish;
		  $total += $allposts->future;
		  $total += $allposts->draft;
		  $total += $allposts->inherit;
		  $total += $allposts->pending;
		  $total += $allposts->private;
		}
		
		$filter_string = "[a2020_folder='']";
		$onclick = 'admin2020_set_content_folder_query';
		  
		if($view == 'media'){
			$filter_string = ".folder";
			$onclick = 'admin2020_set_folder_query';
		} 
		if ($view != 'toplevel_page_admin_2020_content' && $view != null){
			$filter_string = ".folder";
			$onclick = 'admin2020_set_folder_query';
		}
		
		
			
		
		
		
		?>
		
		<div class="admin2020allFolders uk-width-1-1" >
		
		  <!-- ALL -->		
		  <div class="uk-grid-small" uk-grid>
		
			<div class="uk-width-expand">
				
			  <a folder-id=""  onclick="<?php echo $onclick ?>('')" uk-filter-control="group: folders" href="#" class="admin2020folderTitle uk-link-text uk-text-bold">
				<span class="uk-icon-button uk-margin-small-right a2020_folder_icon" style="width:25px;height:25px;" uk-icon="icon:folder;ratio:0.8"></span>
				<?php _e('All','admin2020') ?>
			  </a>
			  
			</div>
		
			<div class="uk-width-auto uk-flex uk-flex-middle uk-flex-right">
			  <span class="uk-icon-button uk-text-meta" style="width:25px;height:25px;background:rgba(197,197,197,0.2);border-radius: 4px;"><?php echo number_format($total)?></span>
			</div>
		
		  </div>
		  
		  <!-- UNCATEGORISED -->
		  <div class="uk-grid-small" uk-grid>
		
			<div class="uk-width-expand">
		
			  <div class="uk-width-1-1">
				  
				<a folder-id="uncategorised" 
					onclick="<?php echo $onclick ?>('uncategorised')" 
					uk-filter-control="filter: <?php echo $filter_string ?>;group: folders" 
					href="#" 
					class="admin2020folderTitle uk-width-1-1 uk-link-text uk-text-bold">
					
				  <span class="uk-icon-button uk-margin-small-right a2020_folder_icon" style="width:25px;height:25px;" uk-icon="icon:folder;ratio:0.8"></span>
				  <?php _e('Uncategorised','admin2020') ?>
				</a>
				
			  </div>
		
			</div>
		  </div>
		
		</div>
		<?php
	
	
	}
	
	/**
	* Add new folder panel
	* @since 1.4
	*/
		
	public function get_add_new_folder(){
		?>
		<div class="uk-margin-bottom uk-width-1-1">
		
		  <button class="uk-button uk-button-default uk-width-1-1 a2020_make_light" uk-toggle="target: .admin2020createfolder"><?php _e('New Folder','admin2020') ?></button>
		
		  <div  class="uk-flex-top admin2020createfolder"  uk-modal>
			<div class="uk-modal-dialog uk-modal-body uk-margin-auto-vertical">
			  <div class="uk-h4"><?php _e('New Folder','admin2020')?></div>
			  <input type="text" class="uk-input uk-margin-bottom" id="foldername" placeholder="<?php _e('Folder Name','admin2020')?>">
			  <span class="uk-text-meta"><?php _e('Colour Tag','admin2020')?></span>
		
			  <div class="uk-margin uk-child-width-auto" id="admin2020_foldertag">
				<input class="uk-radio" type="radio" name="color_tag" checked value="#0c5cef" style="border-color:#0c5cef;background-color:#0c5cef">
				<input class="uk-radio" type="radio" name="color_tag" value="#32d296" style="border-color:#32d296;background-color:#32d296">
				<input class="uk-radio" type="radio" name="color_tag" value="#faa05a" style="border-color:#faa05a;background-color:#faa05a">
				<input class="uk-radio" type="radio" name="color_tag" value="#f0506e" style="border-color:#f0506e;background-color:#f0506e">
				<input class="uk-radio" type="radio" name="color_tag" value="#ff9ff3" style="border-color:#ff9ff3;background-color:#ff9ff3">
			  </div>
		
			  <button class="uk-button uk-button-primary uk-width-1-1" onclick="admin2020newfolder()" type="button"><?php _e('Create','admin2020') ?></button>
			</div>
		
		  </div>
		</div>
		<?php
	}
	
	/**
	* Builds single folder template
	* @since 1.4
	*/	
		
	public function foldertemplate($folder,$folders,$view = null){
	
		$foldercolor = get_post_meta($folder->ID, "color_tag",true);
		$top_level = get_post_meta($folder->ID, "parent_folder",true);
		$folder_id = $folder->ID;
		$the_class = '';
		$post_type = 'attachment';
		$ondrop = 'admin2020mediadrop';
		$onclick = 'admin2020_set_content_folder_query('.$folder->ID.')';
		
			
		$args = array('public'   => true);
		$output = 'objects'; 
		$post_types = get_post_types( $args, $output );
		$types = array();
		
		foreach($post_types as $posttype){
			array_push($types,$posttype->name);
		}
		
		if($view == 'media'){
			$types = array('attachment');
			$onclick = 'admin2020_set_folder_query('.$folder->ID.')';
		}
		if ($view != 'toplevel_page_admin_2020_content' && $view != null){
			$types = array('attachment');
			$onclick = 'admin2020_set_folder_query('.$folder->ID.')';
		}
		
		$ondrop = 'admin2020postdrop';
		
		$this->post_status = array('publish', 'pending', 'draft', 'future', 'private','inherit');
		
		$args = array(
		  'post_type' => $types,
		  'post_status' => $this->post_status,
		  'posts_per_page' => -1,
		  'fields' => 'ids',
		  'meta_query' => array(
		   array(
				   'key' => 'admin2020_folder',
				   'value' => $folder_id,
				   'compare' => '=',
			   )
		   )
		);
		
		$theattachments = get_posts( $args );
		
		if($theattachments){
		  $folder_count = number_format(count($theattachments));
		} else {
		  $folder_count = 0;
		}
		
		if(!$foldercolor){
		  $foldercolor = '#1e87f0';
		}
		
		if(!$top_level){
		  $the_class = 'admin2020_top_level_folder';
		}
		
		$count = 0;
		foreach($folders as $sub_folder){
		
		  $parent_folder = get_post_meta($sub_folder->ID, "parent_folder",true);
		
		  if($parent_folder == $folder_id){
			$count = $count + 1;
		  }
		
		}
		if($view == 'media'){
			$filter_string = ".folder".$folder->ID;
		} else if ($view != 'toplevel_page_admin_2020_content' && $view != null) {
			$filter_string = ".folder".$folder->ID;
		} else	{
			$filter_string = "[a2020_folder='".$folder->ID."']";
		} 
		
		ob_start();
		
		?>
		<div class="admin2020folder <?php echo $the_class.' '.$view?>" folder-id="<?php echo $folder->ID?>" 
			ondrop="<?php echo $ondrop?>(event)" 
			ondragover="admin2020mediaAllowDrop(event)" 
			ondragleave="admin2020mediaDropOut(event)"
		  	draggable="true" ondragstart="admin2020folderdrag(event)" id="folder<?php echo $folder->ID?>"
			uk-tooltip="delay:1000;title: <?php _e('Double click to edit','admin2020')?>" 
			>
			  
		  <div class="uk-grid-small" ondblclick="admin2020_edit_folder(<?php echo $folder->ID?>)" uk-grid>
		
			<div class="uk-width-auto uk-flex uk-flex-middle">
			  <span class="uk-icon-button a2020_folder_icon" style="width:25px;height:25px;" uk-icon="icon:folder;ratio:0.8"></span>
			</div>
		
			<div class="uk-width-auto uk-flex uk-flex-middle">
			  <span class="folder_tag" style="width:10px;height:10px;border-radius: 50%;background-color:<?php echo $foldercolor?>" value="<?php echo $foldercolor?>"></span>
			</div>
		
			<div class="uk-width-expand uk-flex uk-flex-middle">
			  <a 
			  class="uk-link-text folder_title uk-text-bold" 
			  href="#" 
			  folder-id="<?php echo $folder->ID?>"
			  onclick="<?php echo $onclick?>" 
			  uk-filter-control="filter: <?php echo $filter_string?>;group: folders"><?php echo $folder->post_title ?></a>
			</div>
		
			<div class="uk-width-auto uk-flex uk-flex-right uk-flex-middle">
			  <span class="uk-icon-button uk-text-meta" style="width:25px;height:25px;background:rgba(197,197,197,0.2)"><?php echo $folder_count?></span>
			</div>
		
		
			<div class="uk-width-auto uk-flex uk-flex-right uk-flex-middle">
			  <?php if($count > 0) { ?>
				<span class="folder_icon"  onclick="jQuery(this).parent().parent().parent().toggleClass('sub_open');" uk-icon="chevron-down"></span>
			  <?php } else { ?>
				<span class="folder_icon"  style="width:20px;height:20px;"></span>
			  <?php } ?>
			</div>
		
		  </div>
		
		  <?php
		
		
		  if($count > 0){
			?>
			<div class="admin_folders_sub">
			  <?php
				foreach($folders as $sub_folder){
		
				  $parent_folder = get_post_meta($sub_folder->ID, "parent_folder",true);
		
				  if($parent_folder == $folder_id){
		
					echo $this->foldertemplate($sub_folder,$folders,$view);
		
				  }
		
				}
			  ?>
			</div>
		  <?php
		  }
		  ?>
		</div>
		
		<?php
		
		return ob_get_clean();
	
	}
		
	/**
	* Gets custom folders
	* @since 1.4
	*/		
	public function get_user_folders($view = null){
	
		$args = array(
		  'numberposts' => -1,
		  'post_type'   => 'admin2020folders',
		  'orderby' => 'title',
		  'order'   => 'ASC',
		);
		
		$folders = get_posts( $args );
		
		if (count($folders) < 1){
		  return;
		}
		
		foreach ($folders as $folder){
		
		  $parent_folder = get_post_meta($folder->ID, "parent_folder",true);
		  if(!$parent_folder){
			echo $this->foldertemplate($folder,$folders,$view);
		  }
		  continue;
		
		}
		
		?>
		<div class="admin2020folder set_as_top" folder-id="false" ondrop="admin2020postdrop(event)" ondragover="admin2020mediaAllowDrop(event)" ondragleave="admin2020mediaDropOut(event)"
		  draggable="true" ondragstart="admin2020folderdrag(event)" id="folderfalse">
		</div>
		
		<div  class="uk-flex-top" id="admin2020_edit_folder"  uk-modal>
		  <div class="uk-modal-dialog uk-modal-body uk-margin-auto-vertical">
			<div class="uk-h4"><?php _e('Edit Folder','admin2020')?></div>
			<input type="text" class="uk-input uk-margin-bottom" id="foldername_update" placeholder="<?php _e('Folder Name','admin2020')?>">
			<span class="uk-text-meta"><?php _e('Colour Tag','admin2020')?></span>
		
			<div class="uk-margin uk-child-width-auto" id="admin2020_folder_tag_update">
			  <input class="uk-radio" type="radio" name="color_tag" value="#0c5cef" style="border-color:#0c5cef;background-color:#0c5cef">
			  <input class="uk-radio" type="radio" name="color_tag" value="#32d296" style="border-color:#32d296;background-color:#32d296">
			  <input class="uk-radio" type="radio" name="color_tag" value="#faa05a" style="border-color:#faa05a;background-color:#faa05a">
			  <input class="uk-radio" type="radio" name="color_tag" value="#f0506e" style="border-color:#f0506e;background-color:#f0506e">
			  <input class="uk-radio" type="radio" name="color_tag" value="#ff9ff3" style="border-color:#ff9ff3;background-color:#ff9ff3">
			</div>
			<div class="uk-grid-small" uk-grid>
			  <div class="uk-width-1-1 uk-margin-small-bottom">
			  </div>
			  <div class="uk-width-1-2 ">
				<button class="uk-button uk-button-danger" id="delete_the_folder" type="button"><?php _e('Delete','admin2020') ?></button>
			  </div>
			  <div class="uk-width-1-2 uk-flex uk-flex-right">
				<button class="uk-button uk-button-primary" id="update_the_folder" type="button"><?php _e('Save','admin2020') ?></button>
			  </div>
			</div>
		  </div>
		
		</div>
		
		<?php
	
	}
		
	/**
	* Creates folder from front end
	* @since 1.4
	*/	
		
	public function admin2020_create_folder() {
	  if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
	
		  $foldername = wp_strip_all_tags($_POST['title']);
		  $foldertag = wp_strip_all_tags($_POST['foldertag']);
	
		  $my_post = array(
			  'post_title'    => $foldername,
			  'post_status'   => 'publish',
			  'post_type'     => 'admin2020folders'
		  );
	
		  // Insert the post into the database.
		  $thefolder = wp_insert_post( $my_post );
		  update_post_meta($thefolder,"color_tag",$foldertag);
		  //update_post_meta($thefolder,"parent_folder",161);
	
		  echo $this->build_individual_folder_stack($thefolder);
	
	  }
	  die();
	}
		
	/**
	* Renames Folder
	* @since 1.4
	*/	
		
	public function admin2020_rename_folder() {
	  if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
	
		  $foldername = $_POST['title'];
		  $folderid = $_POST['folderid'];
		  $foldertag = $_POST['foldertag'];
	
		  $my_post = array(
			  'post_title'    => $foldername,
			  'post_status'   => 'publish',
			  'ID'            => $folderid,
		  );
	
		  // Insert the post into the database.
		  $thefolder = wp_update_post( $my_post );
	
		  if(!$thefolder){
			$returndata = array();
			$returndata['error'] = __('Something went wrong','admin2020');
			echo json_encode($returndata);
			die();
		  }
	
		  update_post_meta($folderid,"color_tag",$foldertag);
	
		  $returndata = array();
		  $returndata['message'] = __('Folder succesfully renamed','admin2020');
		  $returndata['html'] = $this->build_individual_folder_stack($thefolder);
		  echo json_encode($returndata);
	
	  }
	  die();
	}
		
	/**
	* Deletes folder and any sub folders
	* @since 1.4
	*/	
	public function admin2020_delete_folder() {
	  if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
	
		  $folderid = $_POST['folderid'];
		  $status = wp_delete_post($folderid);
	
		  if(!$status){
			$returndata = array();
			$returndata['error'] = __('Something went wrong','admin2020');
			echo json_encode($returndata);
			die();
		  }
	
		  $args = array(
			'post_type' => 'admin2020folders',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(
			 array(
					 'key' => 'parent_folder',
					 'value' => $folderid,
					 'compare' => '=',
				 )
			 )
		  );
	
		  $thechildren = get_posts($args);
	
		  foreach($thechildren as $child){
	
			wp_delete_post($child);
	
		  }
	
		  $returndata = array();
		  $returndata['message'] = __('Folder succesfully deleted','admin2020');
		  echo json_encode($returndata);
	
	  }
	  die();
	}
		
	
	/**
	* Moves folder into or out of folders
	* @since 1.4
	*/		
		
	public function admin2020_move_to_folder() {
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
	
			$attachmentids = $_POST['theids'];
			$folderid = $_POST['folderid'];
			$screen = $_POST['screen'];
	
			foreach ($attachmentids as $attachmentid){
	
			  //wp_delete_attachment($attachmentid);
			  update_post_meta($attachmentid, "admin2020_folder",$folderid);
	
			}
			
			$returndata = array();
			$returndata['message'] = __('Items Moved to folder');
			$returndata['html'] =  $this->build_individual_folder_stack('',$screen);
			echo json_encode($returndata);
		}
		die();
	}
		
	/**
	* Refreshes all folders
	* @since 1.4
	*/		
	public function admin2020_refresh_all_folders(){
	  if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
	
		$page_id = $_POST['page_id'];
	
	
		echo $this->get_user_folders($page_id);
	
	  }
	  die();
	}
	
	/**
	* Moves folder into another
	* @since 1.4
	*/	
	public function admin2020_move_folder_into_folder() {
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
	
			$destination_id = $_POST['destination_id'];
			$origin_folder_id = $_POST['origin_id'];
			$page_id = $_POST['page_id'];
	
			if($destination_id == 'false'){
			  $destination_id = "";
			}
	
			$current_value = get_post_meta( $origin_folder_id, "parent_folder", true );
	
			if($current_value == $destination_id){
			  $senddata = array();
			  $senddata['error'] = __('Folder is already there','admin2020');
			  echo json_encode($senddata);
			  die();
			}
	
	
			if(!$origin_folder_id){
			  $senddata = array();
			  $senddata['error'] = __('No source or destination provided','admin2020');
			  echo json_encode($senddata);
			  die();
			}
	
	
	
			$success = update_post_meta($origin_folder_id, "parent_folder",$destination_id);
	
			if(!$success){
			  $senddata = array();
			  $senddata['error'] = __('Something went wrong','admin2020');
			  echo json_encode($senddata);
			  die();
			}
	
			if($destination_id == ""){
			  $destination_id = $origin_folder_id;
			}
	
			$senddata = array();
			$senddata['message'] = __('Folder Moved','admin2020');
			$senddata['html'] = $this->build_individual_folder_stack($destination_id,$page_id);
	
	
			echo json_encode($senddata);
		}
		die();
	}
	
	/**
	* Builds individual folder stack
	* @since 1.4
	*/	
	public function build_individual_folder_stack($folderid, $screen = null){
	
	  $args = array(
		'numberposts' => -1,
		'post_type'   => 'admin2020folders',
		'orderby' => 'title',
		'order'   => 'ASC',
	  );
	
	  $folders = get_posts( $args );
	  $folder = get_post($folderid);
	
	  if($folderid == "" || $folderid == null){
	
		$data = "";
	
		foreach($folders as $folder){
		  $parent_folder = get_post_meta($folder->ID, "parent_folder",true);
		  if(!$parent_folder){
			$data = $data . $this->foldertemplate($folder,$folders,$screen);
		  }
		}
	
		return $data;
	
	  } else {
	
		return $this->foldertemplate($folder,$folders,$screen);
	
	  }
	
	}
	
	
}
