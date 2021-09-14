<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_pages
{
    public function __construct($version, $path, $utilities)
    {
        $this->version = $version;
        $this->path = $path;
        $this->utils = $utilities;
    }

    /**
     * Loads menu editor actions
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
		
		add_action('init', array($this,'a2020_create_admin_pages') );
		add_action('admin_init', [$this, 'add_settings'], 0);
        add_action('admin_enqueue_scripts', [$this, 'add_styles'], 0);
		add_action('admin_enqueue_scripts', [$this, 'add_scripts'], 0);
		add_action('admin_menu', array( $this, 'add_custom_menu_items'));
		
		
		
    }
	
	/**
	 * Loads menu editor actions
	 * @since 1.0
	 */

	public function start_front(){
		
		if(!$this->utils->enabled($this)){
			return;
		}
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		
		if($this->utils->is_locked($optionname)){
			return;
		}
		
		add_action('init', array($this,'a2020_create_admin_pages') );
		
	}
	
	/**
	 * Register admin pages component
	 * @since 1.4
	 * @variable $components (array) array of registered admin 2020 components
	 */
	public function register($components){ 
		
		array_push($components,$this);
		return $components;
		
	}
	
	/**
	 * Adds menu settings
	 * @since 1.4
	 */
	
	public function add_settings(){
		
		register_setting( 'admin2020_global_settings', 'admin2020_menu_settings' );
		
	}
	
	
	
	/**
	 * Returns component info for settings page
	 * @since 1.4
	 */
	public function component_info(){
		
		$data = array();
		$data['title'] = __('Admin Pages','admin2020');
		$data['option_name'] = 'admin2020_admin_pages';
		$data['description'] = __('Creates the admin pages editor, allowing you to create new top level admin pages and their content','admin2020');
		return $data;
		
	}
	/**
	 * Returns settings for module
	 * @since 1.4
	 */
	 public function render_settings(){
		  
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
		  <div class="uk-grid" id="a2020_admin_pages_settings" uk-grid>
			  <!-- LOCKED FOR USERS / ROLES -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Admin Pages Disabled for','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("Admin 2020 admin pages will be disabled for any users or roles you select",'admin2020') ?></div>
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
					  jQuery('#a2020_admin_pages_settings #a2020-role-types').tokenize2({
						  placeholder: '<?php _e('Select roles or users','admin2020') ?>'
					  });
					  jQuery(document).ready(function ($) {
						  $('#a2020_admin_pages_settings #a2020-role-types').on('tokenize:select', function(container){
							  $(this).tokenize2().trigger('tokenize:search', [$(this).tokenize2().input.val()]);
						  });
					  })
				  </script>
				  
			  </div>	
			  
			  	
		  </div>	
		  
		  <?php
	  }
    /**
     * Adds menu editor styles
     * @since 1.0
     */

    public function add_styles()
    {
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin-2020-menu-editor'){
				
		        wp_register_style(
		            'admin2020_menu_editor',
		            $this->path . 'assets/css/modules/admin-menu-editor.css',
		            array(),
		            $this->version
		        );
		        wp_enqueue_style('admin2020_menu_editor');
				
				//TOKENIZE
				wp_register_style('tokenize-css', $this->path . 'assets/css/tokenize/tokenize2.min.css', array());
				wp_enqueue_style('tokenize-css');
			}
			
		}
		
    }
	
	/**
	* Enqueue menu editor scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin-2020-menu-editor'){
			
				///MENU EDITOR
				wp_enqueue_script('admin-menu-editor-js', $this->path . 'assets/js/admin2020/admin-menu-editor.min.js', array('jquery'));
				wp_localize_script('admin-menu-editor-js', 'admin2020_admin_menu_editor_ajax', array(
				  'ajax_url' => admin_url('admin-ajax.php'),
				  'security' => wp_create_nonce('admin2020-admin-menu-editor-security-nonce'),
				));
				
				///TOKENIZE
				wp_enqueue_script('tokenize', $this->path . 'assets/js/tokenize/tokenize2.min.js', array('jquery'));
				
				
			}
			
		}
	  
	}
	
	
	/**
	* Creates custom admin pages post type
	* @since 2.0.3
	*/
	public function a2020_create_admin_pages(){
	
		 $labels = array(
		  'name'               => _x( 'Admin Page', 'post type general name', 'admin2020' ),
		  'singular_name'      => _x( 'Admin Page', 'post type singular name', 'admin2020' ),
		  'menu_name'          => _x( 'Admin Pages', 'admin menu', 'admin2020' ),
		  'name_admin_bar'     => _x( 'Admin Page', 'add new on admin bar', 'admin2020' ),
		  'add_new'            => _x( 'Add New', 'folder', 'admin2020' ),
		  'add_new_item'       => __( 'Add New Admin Page', 'admin2020' ),
		  'new_item'           => __( 'New Admin Page', 'admin2020' ),
		  'edit_item'          => __( 'Edit Admin Page', 'admin2020' ),
		  'view_item'          => __( 'View Admin Page', 'admin2020' ),
		  'all_items'          => __( 'All Admin Pages', 'admin2020' ),
		  'search_items'       => __( 'Search Admin Pages', 'admin2020' ),
		  'not_found'          => __( 'No Admin Pages found.', 'admin2020' ),
		  'not_found_in_trash' => __( 'No Admin Pages found in Trash.', 'admin2020' )
		);
		 $args = array(
		  'labels'             => $labels,
		  'description'        => __( 'Description.', 'Add New Admin Page' ),
		  'public'             => false,
		  'publicly_queryable' => false,
		  'show_ui'            => true,
		  'show_in_menu'       => true,
		  'query_var'          => false,
		  'has_archive'        => false,
		  'hierarchical'       => false,
		  'supports' 		   => array('editor','title'),
		  'show_in_rest' 	   => true,
		  'rewrite'            => [ 'slug' => 'admin-page' ],
		);
		register_post_type( 'admin2020adminpage', $args );
	}
	
	
	
	/**
	* Adds custom admin pages to the menu
	* @since 1.4
	*/
	
	public function add_custom_menu_items() {
		
		
		$args = array(
		  'numberposts' => -1,
		  'post_status' => 'publish',
		  'post_type'   => 'admin2020adminpage'
		);
			 
		$adminppages = get_posts( $args );
		
		if(!$adminppages || count($adminppages) < 1){
			return;
		} 
		
		foreach($adminppages as $page) {
			
			$title = get_the_title($page);
			$lc_title = strtolower($title);
			$slug = str_replace(' ', '-', $lc_title);
			$theid = $page->ID;
			
			add_menu_page( 'a2020-'.$slug , $title, 'read', 'a2020-'.urlencode($slug) , function() use ( $theid ) { 
				   $this->handle_custom_page_content( $theid ); });
			
		}
		return;
		
		
	
	}
	
	
	public function handle_custom_page_content($theid){
		
		do_action('wp_enqueue_style');
		?>
		
		<link rel="stylesheet" href="<?php echo includes_url() ?>css/dist/block-library/style.min.css" media="all">
		<div class="wrap">
			<div class="uk-h2"><?php echo get_the_title($theid) ?></div>
			<div class="entry-content">
					<?php echo get_the_content(null, false, $theid )?>
			</div>
		</div>
		<?php
		
		wp_footer();
		
	}


	
	
}
