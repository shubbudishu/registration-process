<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_menu_editor
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
		
		if(function_exists('is_network_admin')){ 
			if(is_network_admin()){
				return;
			}
		}
		
		
		add_action('admin_init', [$this, 'add_settings'], 0);
        add_action('admin_enqueue_scripts', [$this, 'add_styles'], 0);
		add_action('admin_enqueue_scripts', [$this, 'add_scripts'], 0);
		add_action('admin_menu', array( $this, 'add_menu_item'));
		add_filter('parent_file', array( $this, 'set_menu'),800);
		add_filter('parent_file', array( $this, 'apply_menu'),900);
		add_action('wp_ajax_a2020_save_menu_settings', array($this,'a2020_save_menu_settings'));
		add_action('wp_ajax_a2020_delete_menu_settings', array($this,'a2020_delete_menu_settings'));
		add_action('wp_ajax_a2020_export_menu', array($this,'a2020_export_menu'));
		add_action('wp_ajax_a2020_import_menu', array($this,'a2020_import_menu'));
		
		
		
    }
	
	/**
	 * Grabs unmodified menu 
	 * @since 1.4
	 */
	
	public function set_menu($parent_file){
		
		global $menu, $submenu;
		$this->menu = $this->sort_menu_settings($menu);
		$this->submenu = $this->sort_sub_menu_settings($this->menu,$submenu);
		
		return $parent_file;
		
	}
	
	/**
	 * Adds menu settings
	 * @since 1.4
	 */
	
	public function add_settings(){
		
		register_setting( 'admin2020_global_settings', 'admin2020_menu_settings' );
		
	}
	
	/**
	 * Register menu editor component
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
		$data['title'] = __('Menu Editor','admin2020');
		$data['option_name'] = 'admin2020_menu_editor';
		$data['description'] = __('Creates the menu editor and allows for rearanging, renaming and removal of menu items.','admin2020');
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
		  <div class="uk-grid" id="a2020_menu_editor_settings" uk-grid>
			  <!-- LOCKED FOR USERS / ROLES -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Menu Editor Disabled for','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("Admin 2020 menu editor will be disabled for any users or roles you select",'admin2020') ?></div>
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
					  jQuery('#a2020_menu_editor_settings #a2020-role-types').tokenize2({
						  placeholder: '<?php _e('Select roles or users','admin2020') ?>'
					  });
					  jQuery(document).ready(function ($) {
						  $('#a2020_menu_editor_settings #a2020-role-types').on('tokenize:select', function(container){
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
	* Adds menu editor page to settings
	* @since 1.4
	*/
	
	public function add_menu_item(){
		
		add_options_page( 'Menu Editor', __('Menu Editor (Admin 2020)','admin2020'), 'manage_options', 'admin-2020-menu-editor', array($this,'admin2020_menu_editor_page') );
		
	}
	
	/**
	* Creates menu editor page
	* @since 1.4
	*/
	
	public function admin2020_menu_editor_page(){
		
		?>
		<div class="uk-width-1-1 uk-margin-top" uk-sticky="offset:41;top:100;cls-active:uk-background-default uk-padding-small a2020_border_bottom">
			<div class="uk-container uk-container-small" >
				<?php $this->build_header()?>
			</div>
		</div>
		<div class="wrap" style="padding-top:0;">
			<div class="uk-container uk-container-small uk-margin-top">
				<p class="uk-text-meta">
					<?php _e("Edit each menu item's name, link, icon and visibility. Drag and drop to rearange the menu. Changes will take effect after page refresh.",'admin2020')?>
				</p>
				<p class="uk-text-meta">
					<span class="" uk-icon="icon:info;ratio:0.8"></span> 
					<?php _e("If you have admin 2020 menu component disabled, icons and label dividers won't change.",'admin2020')?>
				</p>
				<div class="uk-margin-top" uk-sortable="handle: .admin2020_drag_handle"><?php
					$this->build_editor();
				?></div><?php
				?>
			</div>
		</div>
		
		<a href="#" id="admin2020_download_settings" style="display: none;"?></a>
		<?php
		
		
	}
	
	/**
	 * Renders header
	 * @since 1.4
	 */
	
	public function build_header(){
		
		?>
		<div class="uk-grid-small uk-margin-bottom" uk-grid >
			<div class="uk-width-auto">
				<div class="uk-h3 uk-margin-remove-bottom"><?php _e('Menu Editor','admin2020')?></div>
			</div>
			
			<div class="uk-width-expand uk-text-right">
				<button class="uk-button uk-button-primary" type="button" onclick="a2020_save_menu_editor()"><?php _e('Save','admin2020')?></button>
				<button class="uk-button uk-button-default a2020_make_light a2020_make_square uk-margin-left" aria-expanded="false">
					<span uk-icon="settings"></span>
				</button>
				<div uk-dropdown="mode:click;pos:bottom-right">
					<ul class="uk-nav uk-dropdown-nav">
						<li><a onclick="a2020_export_menu()" href="#"><?php _e('Export Menu','admin2020')?></a></li>
						
						<li class="uk-margin-small-bottom"> 
							<div class="js-upload uk-form-custom" uk-form-custom="">
								<input accept=".json" type="file" single="" id="admin2020_export_menu" onchange="a2020_import_menu()">
								
								<a href="#" class="uk-link-muted"
								uk-tooltip="delay:300;title:<?php _e('Imports Admin 2020 menu from JSON')?>"><?php _e('Import Menu','admin2020')?></a>
							</div>
						</li>
						
						<li class="uk-nav-divider"></li>
						<li><a class="uk-text-danger" onclick="confirm_menu_reset()" href="#"><?php _e('Reset Menu','admin2020')?></a></li>
					</ul>
				</div>
			</div>
			
			<script>
			function confirm_menu_reset(){
				UIkit.modal.confirm('<?php _e('Are you sure you want to reset your menu back to default? There is no undo')?>').then(function() {
					a2020_delete_menu_settings();
				}, function () {
					
				});
			}
			</script>
		</div>
		<?php
	}
	
	/**
	 * Build Editor
	 * @since 1.4
	 */
	
	public function build_editor(){ 
		
		global $wp_roles;
		
		$blogusers = get_users();
		$this->users = $blogusers;
		$this->roles = $wp_roles->roles;
		
		
		if($this->menu && is_array($this->menu)){
			
			foreach($this->menu as $menu_item){
				
				if (strpos($menu_item[2],"separator") !== false  && !$menu_item[0]){
					///BUILD SEPARATOR
					$this->build_separator($menu_item);
				} else {
					///BUILD TOP LEVEL
					$this->build_top_level($this->menu,$menu_item,$this->submenu);
				}
				
			}
			
		}
		
		$this->build_icons();
		
	}
	
	/**
	 * Outputs a single top level separator
	 * @since 1.4
	 */
	 
	public function build_separator($current_menu_item){
		
			$disabled_for = array();
			$name = '';
			$menu_id = preg_replace("/[^A-Za-z0-9 ]/", '', $current_menu_item[2]);
			$a2020_options = get_option( 'admin2020_menu_settings' );
			$info = $this->component_info();
			$optionname = $info['option_name'];
			
			if(is_array($a2020_options)){
				
				if(isset($a2020_options[$current_menu_item[2]])){
					$optiongroup = $a2020_options[$current_menu_item[2]];
					
					if(isset($optiongroup['name'])){
						$name = $optiongroup['name'];
					}
					if(isset($optiongroup['hidden_for'])){
						$disabled_for = $optiongroup['hidden_for'];
					}
					
				}
			}
			
			if(!is_array($disabled_for)){
				$disabled_for = array();
			}
			
			?>
			<div class="uk-card uk-card-default uk-card-small uk-box-shadow-small a2020_border uk-margin-small-bottom a2020_menu_item" name="<?php echo $current_menu_item[2]?>" id="<?php echo $menu_id?>"style="padding:15px;">
				<input type="number" class="top_level_order" value="" style="display:none;">
				
				<ul uk-accordion="" class="uk-margin-remove uk-accordion">
					<li class="">
						<a class="uk-accordion-title uk-margin-remove uk-text-small" href="#">
						<span uk-icon="grid" class="admin2020_drag_handle uk-icon" style="margin-right: 15px; user-select: none;"></span>
							<?php _e('Separator','admin2020')?>								
						</a>
						<div class="uk-accordion-content uk-margin-top">
				
									
									<div class="uk-grid-small a2020_top_level_settings" uk-grid>
										
										<div class="uk-width-1-1@s uk-width-1-2@m">
											<div class="uk-text-meta uk-margin-small-bottom"><?php _e('Rename as','admin2020')?>:</div>
											<input class="uk-input menu_setting" name="name" type="text" value="<?php echo $name?>" placeholder="<?php _e('New name...','admin2020') ?>">
										</div>
										
										<div class="uk-width-1-1@s uk-width-1-2@m">
		
												<div class="uk-text-meta uk-margin-small-bottom "><?php _e('Hidden For Roles','admin2020')?>:</div>
				
												<div class="uk-width-1-1">
													<select class="menu_setting" name="hidden_for" id="a2020-role-types" name="disabled-for" module-name="<?php echo $optionname?>" multiple>
														
														<?php
														$sel = '';
														  
														if(in_array('Super Admin', $disabled_for)){
														  $sel = 'selected';
														}
														?>  
														<option value="Super Admin" <?php echo $sel?>><?php _e('Super Admin','admin2020') ?></option>
														<?php
														foreach ($this->roles as $role){
														  $rolename = $role['name'];
														  $sel = '';
														  
														  if(in_array($rolename, $disabled_for)){
															  $sel = 'selected';
														  }
														  ?>
														  <option value="<?php echo $rolename ?>" <?php echo $sel?>><?php echo $rolename ?></option>
														  <?php
														}
														  
														foreach ($this->users as $user){
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
														jQuery(document).ready(function ($) {
															  $('#<?php echo $menu_id?> #a2020-role-types').tokenize2({
																  placeholder: '<?php _e('Select roles or users','admin2020') ?>'
															  });
														  
															  $('#<?php echo $menu_id?> #a2020-role-types').on('tokenize:select', function(container){
																  $(this).tokenize2().trigger('tokenize:search', [$(this).tokenize2().input.val()]);
															  });	
														  })
													</script>
												</div>
											
										</div>
										
										
								</div>
			
						</div>
					</li>
				</ul>
			</div>
			<?php
		
	}
	
	
	
	
	/**
	 * Outputs a single top level menu item
	 * @since 1.4
	 */
	 
	public function build_top_level($master_menu,$current_menu_item,$master_sub_menu){
		
		
		$disabled_for;
		$menu_id = preg_replace("/[^A-Za-z0-9 ]/", '', $current_menu_item[2]);
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		
		$a2020_options = get_option( 'admin2020_menu_settings' );
		
		$name = '';
		$link = ''; 
		$icon = '';
		$disabled_for = array(); 
		$optiongroup = array();
		
		if(is_array($a2020_options)){
			
			if(isset($a2020_options[$current_menu_item[2]])){
				$optiongroup = $a2020_options[$current_menu_item[2]];
				
				if(isset($optiongroup['name'])){
					$name = $optiongroup['name'];
				}
				
				if(isset($optiongroup['link'])){
					$link = $optiongroup['link'];
				}
				if(isset($optiongroup['icon'])){
					$icon = $optiongroup['icon'];
				}
				if(isset($optiongroup['hidden_for'])){
					$disabled_for = $optiongroup['hidden_for'];
				}
				
			}
		}
		
		if(!is_array($disabled_for)){
			$disabled_for = array();
		}
		
		?>
		<div class="uk-card uk-card-default uk-card-small uk-box-shadow-small a2020_border uk-margin-small-bottom a2020_menu_item" name="<?php echo $current_menu_item[2]?>" id="<?php echo $menu_id?>"style="padding:15px;">
			<input type="number" class="top_level_order" value="" style="display:none;">
			
			<ul uk-accordion="" class="uk-margin-remove uk-accordion">
				<li class="">
					<a class="uk-accordion-title uk-margin-remove uk-text-small" href="#">
					<span uk-icon="grid" class="admin2020_drag_handle uk-icon" style="margin-right: 15px; user-select: none;"></span>
						<?php echo $current_menu_item[0]?>								
					</a>
					<div class="uk-accordion-content uk-margin-top">
				
						<ul uk-tab="" class="uk-tab">
							<li class="uk-active"><a href="#" aria-expanded="true"><?php _e('Settings','admin2020')?></a></li>
							<li><a href="#" aria-expanded="false"><?php _e('Sub Menu','admin2020')?></a></li>
						</ul>
			
						<ul class="uk-switcher uk-margin">
							
							<!--SETTINGS MENU TAB -->
							<li class="uk-active">
								
								<div class="uk-grid-small a2020_top_level_settings" uk-grid>
									
									<div class="uk-width-1-1@s uk-width-1-2@m">
										<div class="uk-text-meta uk-margin-small-bottom"><?php _e('Rename as','admin2020')?>:</div>
										<input class="uk-input menu_setting" name="name" type="text" value="<?php echo $name ?>" placeholder="<?php _e('New name...','admin2020') ?>">
									</div>
									
									<div class="uk-width-1-1@s uk-width-1-2@m">
	
											<div class="uk-text-meta uk-margin-small-bottom "><?php _e('Hidden For Roles','admin2020')?>:</div>
			
											<div class="uk-width-1-1">
												<select class="menu_setting" name="hidden_for" id="a2020-role-types" module-name="<?php echo $optionname?>" multiple>
													<?php
													$sel = '';
													  
													if(in_array('Super Admin', $disabled_for)){
													  $sel = 'selected';
													}
													?>  
													<option value="Super Admin" <?php echo $sel?>><?php _e('Super Admin','admin2020') ?></option>
													<?php
													foreach ($this->roles as $role){
													  $rolename = $role['name'];
													  $sel = '';
													  
													  if(in_array($rolename, $disabled_for)){
														  $sel = 'selected';
													  }
													  ?>
													  <option value="<?php echo $rolename ?>" <?php echo $sel?>><?php echo $rolename ?></option>
													  <?php
													}
													  
													foreach ($this->users as $user){
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
													jQuery(document).ready(function ($) {
														  $('#<?php echo $menu_id?> #a2020-role-types').tokenize2({
															  placeholder: '<?php _e('Select roles or users','admin2020') ?>'
														  });
													  
														  $('#<?php echo $menu_id?> #a2020-role-types').on('tokenize:select', function(container){
															  $(this).tokenize2().trigger('tokenize:search', [$(this).tokenize2().input.val()]);
														  });	
													  })
												</script>
											</div>
										
									</div>
									
									<div class="uk-width-1-1@s uk-width-1-2@m">
										<div class="uk-text-meta uk-margin-small-bottom"><?php _e('Change Link','admin2020')?>:</div>
										<input class="uk-input menu_setting" name="link" type="text" value="<?php echo $link ?>" placeholder="<?php _e('New Link...','admin2020') ?>">
									</div>
									
									<div class="uk-width-1-1@s uk-width-1-2@m">
										<div class="uk-text-meta uk-margin-small-bottom "><?php _e('Set custom icon','admin2020') ?>:</div>
										<button class="uk-button uk-button-default" type="button" onclick="open_icon_chooser(this)">
											<?php _e('Choose Icon','Admin2020') ?>									
										</button>
										<span class="uk-margin-left admin2020_icon_display uk-icon" uk-icon="<?php echo $icon ?>"></span>
										<input type="text" class="menu_setting a2020_icon_holder"  value="<?php echo $icon ?>" name="icon" hidden>
									</div>
								</div>
		
							</li>
							<!--SUBB MENU TAB -->
							<li uk-sortable="handle: .admin2020_sub_drag_handle">
								<?php
								///CHECK FOR SUBS
								$link = $current_menu_item[2];
								
								if(isset($master_sub_menu[$link]) && is_array($master_sub_menu[$link])){
									
								  foreach ($master_sub_menu[$link] as $sub_menu_item){
									  $this->build_sub_menu_item($sub_menu_item,$optiongroup);
								  }	
								  
								} else {
								  ?> <span class="uk-text-meta"><?php _e('No sub menu items','admin2020')?></span> <?php
								} ?>
							</li>
			
						</ul>
						<!--END OF SWITCHER -->
					</div>
				</li>
			</ul>
		</div>
		<?php
	}
	
	
	public function build_sub_menu_item($current_menu_item,$optiongroup){
		
		$name = '';
		$link = '';
		$disabled_for = array(); 
		$suboptiongroup = array();
		$info = $this->component_info();
		$optionname = $info['option_name'];
			
		if(isset($optiongroup['submenu'])){
			
			if(isset( $optiongroup['submenu'][$current_menu_item[2]])){
				
				$suboptiongroup = $optiongroup['submenu'][$current_menu_item[2]];
			
				if(isset($suboptiongroup['name'])){
					$name = $suboptiongroup['name'];
				}
				
				if(isset($suboptiongroup['link'])){
					$link = $suboptiongroup['link'];
				}
				if(isset($suboptiongroup['hidden_for'])){
					$disabled_for = $suboptiongroup['hidden_for'];
				}
			}
			
		}
		
		
		if(!is_array($disabled_for)){
			$disabled_for = array();
		}
		
		$menu_id = preg_replace("/[^A-Za-z0-9 ]/", '', $current_menu_item[2]);
		
		?>
		<div class="uk-card uk-card-default uk-card-small uk-box-shadow-small a2020_border uk-margin-small-bottom a2020_sub_menu_item" 
			name="<?php echo $current_menu_item[2]?>" 
			id="<?php echo $menu_id?>"style="padding:15px;">
			<input type="number" class="top_level_order" value="" style="display:none;">
			
			<ul uk-accordion="" class="uk-margin-remove uk-accordion">
				<li class="">
					<a class="uk-accordion-title uk-margin-remove uk-text-small" href="#">
					<span uk-icon="grid" class="admin2020_sub_drag_handle uk-icon" style="margin-right: 15px; user-select: none;"></span>
						<?php echo $current_menu_item[0]?>								
					</a>
					<div class="uk-accordion-content uk-margin-top">
			
								
								<div class="uk-grid-small" uk-grid>
									
									<div class="uk-width-1-1@s uk-width-1-2@m">
										<div class="uk-text-meta uk-margin-small-bottom"><?php _e('Rename as','admin2020')?>:</div>
										<input class="uk-input menu_setting" name="name" type="text" value="<?php echo $name?>" placeholder="<?php _e('New name...','admin2020') ?>">
									</div>
									
									<div class="uk-width-1-1@s uk-width-1-2@m">
	
											<div class="uk-text-meta uk-margin-small-bottom "><?php _e('Hidden For Roles','admin2020')?>:</div>
			
											<div class="uk-width-1-1">
												<select class="menu_setting" name="hidden_for" id="a2020-role-types" name="disabled-for" module-name="<?php echo $optionname?>" multiple>
													<?php
													$sel = '';
													  
													if(in_array('Super Admin', $disabled_for)){
													  $sel = 'selected';
													}
													?>  
													<option value="Super Admin" <?php echo $sel?>><?php _e('Super Admin','admin2020') ?></option>
													<?php
													foreach ($this->roles as $role){
													  $rolename = $role['name'];
													  $sel = '';
													  
													  if(in_array($rolename, $disabled_for)){
														  $sel = 'selected';
													  }
													  ?>
													  <option value="<?php echo $rolename ?>" <?php echo $sel?>><?php echo $rolename ?></option>
													  <?php
													}
													  
													foreach ($this->users as $user){
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
													jQuery(document).ready(function ($) {
														
														  $('#<?php echo $menu_id?> #a2020-role-types').tokenize2({
															  placeholder: '<?php _e('Select roles or users','admin2020') ?>'
														  });
													  
														  $('#<?php echo $menu_id?> #a2020-role-types').on('tokenize:select', function(container){
															  $(this).tokenize2().trigger('tokenize:search', [$(this).tokenize2().input.val()]);
														  });
													  })
												</script>
											</div>
										
									</div>
									
									<div class="uk-width-1-1@s uk-width-1-2@m">
										<div class="uk-text-meta uk-margin-small-bottom"><?php _e('Change Link','admin2020')?>:</div>
										<input class="uk-input menu_setting" name="link" type="text" value="<?php echo $link?>" placeholder="<?php _e('New Link...','admin2020') ?>">
									</div>
								</div>
		
						<!--END OF SWITCHER -->
					</div>
				</li>
			</ul>
		</div>
		<?php
	}
	
	
	
	/**
	 * Outputs a icon list modal
	 * @since 1.4
	 */
	 
	public function build_icons(){
		
		?>
		
		<div id="icon-list" uk-modal>
			<div class="uk-modal-dialog uk-margin-auto-vertical" style="width:70%;border-radius:4px">
				<div class="uk-padding-small a2020_border_bottom">
					<button class="uk-modal-close-default" type="button" uk-close></button>
					<h2 class="uk-h4 uk-margin-remove"><?php _e('Icons','admin2020')?></h2>
				</div>
				<div class="uk-grid-small uk-child-width-1-2@s uk-child-width-1-4@m uk-grid uk-height-large uk-overflow-auto uk-padding-small" uk-grid="" id="admin2020_icon_select">
					<div>

						<ul class="uk-list">

							<!-- App -->
							<li class="uk-text-bold"></span><?php _e('Use Default','admin2020') ?> <span class="uk-margin-small-right uk-icon" uk-icon="noicon"></li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="home"></span> home</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="sign-in"></span> sign-in</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="sign-out"></span> sign-out</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="user"></span> user</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="users"></span> users</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="lock"></span> lock</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="unlock"></span> unlock</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="settings"></span> settings</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="cog"></span> cog</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="nut"></span> nut</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="comment"></span> comment</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="commenting"></span> commenting</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="comments"></span> comments</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="hashtag"></span> hashtag</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="tag"></span> tag</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="cart"></span> cart</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="credit-card"></span> credit-card</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="mail"></span> mail</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="receiver"></span> receiver</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="print"></span> print</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="search"></span> search</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="location"></span> location</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="bookmark"></span> bookmark</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="code"></span> code</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="paint-bucket"></span> paint-bucket</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="camera"></span> camera</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="video-camera"></span> video-camera</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="bell"></span> bell</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="microphone"></span> microphone</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="bolt"></span> bolt</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="star"></span> star</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="heart"></span> heart</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="happy"></span> happy</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="lifesaver"></span> lifesaver</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="rss"></span> rss</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="social"></span> social</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="git-branch"></span> git-branch</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="git-fork"></span> git-fork</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="world"></span> world</li>

						</ul>

					</div>
					<div>

						<ul class="uk-list">

							<!-- App -->
							<li><span class="uk-margin-small-right uk-icon" uk-icon="calendar"></span> calendar</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="clock"></span> clock</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="history"></span> history</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="future"></span> future</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="pencil"></span> pencil</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="trash"></span> trash</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="move"></span> move</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="link"></span> link</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="question"></span> question</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="info"></span> info</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="warning"></span> warning</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="image"></span> image</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="thumbnails"></span> thumbnails</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="table"></span> table</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="list"></span> list</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="menu"></span> menu</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="grid"></span> grid</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="more"></span> more</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="more-vertical"></span> more-vertical</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="plus"></span> plus</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="plus-circle"></span> plus-circle</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="minus"></span> minus</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="minus-circle"></span> minus-circle</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="close"></span> close</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="check"></span> check</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="ban"></span> ban</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="refresh"></span> refresh</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="play"></span> play</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="play-circle"></span> play-circle</li>

							<!-- Devices -->
							<li><span class="uk-margin-small-right uk-icon" uk-icon="tv"></span> tv</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="desktop"></span> desktop</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="laptop"></span> laptop</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="tablet"></span> tablet</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="phone"></span> phone</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="tablet-landscape"></span> tablet-landscape</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="phone-landscape"></span> phone-landscape</li>


						</ul>

					</div>
					<div>

						<ul class="uk-list">

							<!-- Storage -->
							<li><span class="uk-margin-small-right uk-icon" uk-icon="file"></span> file</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="file-text"></span> file-text</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="file-pdf"></span> file-pdf</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="copy"></span> copy</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="file-edit"></span> file-edit</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="folder"></span> folder</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="album"></span> album</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="push"></span> push</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="pull"></span> pull</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="server"></span> server</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="database"></span> database</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="cloud-upload"></span> cloud-upload</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="cloud-download"></span> cloud-download</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="download"></span> download</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="upload"></span> upload</li>

							<!-- Direction -->
							<li><span class="uk-margin-small-right uk-icon" uk-icon="reply"></span> reply</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="forward"></span> forward</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="expand"></span> expand</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="shrink"></span> shrink</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="arrow-up"></span> arrow-up</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="arrow-down"></span> arrow-down</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="arrow-left"></span> arrow-left</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="arrow-right"></span> arrow-right</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="chevron-up"></span> chevron-up</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="chevron-down"></span> chevron-down</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="chevron-left"></span> chevron-left</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="chevron-right"></span> chevron-right</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="chevron-double-left"></span> chevron-double-left</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="chevron-double-right"></span> chevron-double-right</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="triangle-up"></span> triangle-up</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="triangle-down"></span> triangle-down</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="triangle-left"></span> triangle-left</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="triangle-right"></span> triangle-right</li>

						</ul>

					</div>
					<div>

						<ul class="uk-list">

							<!-- Editor -->
							<li><span class="uk-margin-small-right uk-icon" uk-icon="bold"></span> bold</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="italic"></span> italic</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="strikethrough"></span> strikethrough</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="quote-right"></span> quote-right</li>

							<!-- Brands -->
							<li><span class="uk-margin-small-right uk-icon" uk-icon="500px"></span> 500px</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="behance"></span> behance</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="dribbble"></span> dribbble</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="etsy"></span> etsy</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="facebook"></span> facebook</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="flickr"></span> flickr</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="foursquare"></span> foursquare</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="github"></span> github</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="github-alt"></span> github-alt</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="gitter"></span> gitter</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="google"></span> google</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="google-plus"></span> google-plus</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="instagram"></span> instagram</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="joomla"></span> joomla</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="linkedin"></span> linkedin</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="pagekit"></span> pagekit</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="pinterest"></span> pinterest</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="reddit"></span> reddit</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="soundcloud"></span> soundcloud</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="tripadvisor"></span> tripadvisor</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="tumblr"></span> tumblr</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="twitter"></span> twitter</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="uikit"></span> uikit</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="vimeo"></span> vimeo</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="whatsapp"></span> whatsapp</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="wordpress"></span> wordpress</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="xing"></span> xing</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="yelp"></span> yelp</li>
							<li><span class="uk-margin-small-right uk-icon" uk-icon="youtube"></span> youtube</li>
						</ul>

					</div>
				</div>
				<div class="uk-padding-small a2020_border_top a2020_border_top uk-text-right">
					<button class="uk-button uk-button-primary" type="button" id="icon_selected"><?php _e("Select","admin2020")?></button>
				</div>
			</div>
		</div>
		<?php
		
	}
	
	
	/**
	 * Applies menu settings
	 * @since 1.4
	 */
	
	public function apply_menu($parent_file){
		
		global $menu, $submenu;
		$tempmenu = array();
		$tempsub = array();
		$submenu = $this->sort_sub_menu_settings($menu,$submenu);
		
		if($menu && is_array($menu)){
			
			foreach($menu as $key=>$menu_item){
				
				if (strpos($menu_item[2],"separator") !== false  && !$menu_item[0]){
					///BUILD SEPARATOR
					$newitem = $this->apply_separator_settings($menu_item,$key);
					if($newitem){
						array_push($tempmenu,$newitem);
					}
				} else {
					///BUILD TOP LEVEL
					$newitem = $this->apply_top_level_settings($menu_item,$key);
					if($newitem){
						array_push($tempmenu,$newitem);
						
						if(isset($submenu[$newitem[2]])){
							
							$subitem = $this->apply_sub_level_settings($submenu[$newitem[2]], $newitem[2]); 
							
							if($subitem){
								$tempsub[$newitem[2]] = $this->apply_sub_level_settings($submenu[$newitem[2]], $newitem[2]); 
							}
							
						}
					}
				}
				
			}
			
		}
		
		$submenu = $tempsub;
		$menu = $this->utils->sort_array($tempmenu);
		
		return $parent_file;
		
	}
	
	
	
	
	/**
	 * Sorts Menu for settings
	 * @since 1.4
	 */
	 
	public function sort_menu_settings($themenu){
		
		$a2020_options = get_option( 'admin2020_menu_settings' );
		$tempmenu = array();
		
		foreach($themenu as $key=>$current_menu_item){ 
			
			$optiongroup = array();
			$order = $key;
			
			if(is_array($a2020_options)){
				
				if(isset($a2020_options[$current_menu_item[2]])){
					$optiongroup = $a2020_options[$current_menu_item[2]];
					
					if(isset($optiongroup['order'])){
						$order = $optiongroup['order'];
					} 
					
				}
			}
			
			$current_menu_item['order'] = $order;
			
			array_push($tempmenu, $current_menu_item);
		}
		
		
		
		return $this->utils->sort_array($tempmenu);
		
	}
	
	
	/**
	 * Sorts Sub Menu for settings
	 * @since 1.4
	 */
	 
	public function sort_sub_menu_settings($themenu,$thesubmenu){
		
		$a2020_options = get_option( 'admin2020_menu_settings' );
		$tempsubmenu = array();
		
		foreach($themenu as $current_menu_item){
			
			$optiongroup = array();
			$submenu_items = array();
			
			if(isset($thesubmenu[$current_menu_item[2]])){
			
				$submenuitems = $thesubmenu[$current_menu_item[2]];
				
				foreach($submenuitems as $key=>$subitem){ 
					
					$subitem['order'] = $key;
					
					if(is_array($a2020_options) && isset($a2020_options[$current_menu_item[2]]) && isset($a2020_options[$current_menu_item[2]]['submenu'])){ 
						
						$submenugroup = $a2020_options[$current_menu_item[2]]['submenu'];
						 
						if(isset($submenugroup[$subitem[2]])){
							
							$itemoptions = $submenugroup[$subitem[2]];
							
							if(isset($itemoptions['order'])){
								
								$subitem['order'] = $itemoptions['order'];
								
							} 
							
						}	
						
						
					} 
					
					array_push($submenu_items,$subitem);	
					
				}
				
				$submenu_items = $this->utils->sort_array($submenu_items);
				
				$tempsubmenu[$current_menu_item[2]] = $submenu_items;
			}	
			
		}
		
		return $tempsubmenu;
		
	}
	
	/**
	 * Applies separator menu item settings
	 * @since 1.4
	 */
	 
	public function apply_separator_settings($current_menu_item,$key){
		
		$a2020_options = get_option( 'admin2020_menu_settings' );
		
		$name = '';
		$disabled_for = array(); 
		$optiongroup = array();
		$order = $key;
		
		if(is_array($a2020_options)){
			
			if(isset($a2020_options[$current_menu_item[2]])){
				$optiongroup = $a2020_options[$current_menu_item[2]];
				
				if(isset($optiongroup['name'])){
					$name = $optiongroup['name'];
					
					if($name != ""){
						$current_menu_item['name'] = $name;
					}
				}
				
				if(isset($optiongroup['order'])){
					$order = $optiongroup['order'];
				} 
				
				if(isset($optiongroup['hidden_for'])){
					$disabled_for = $optiongroup['hidden_for'];
					
					if($this->is_hidden($disabled_for)){
						$current_menu_item['hidden'] = true;
					}
				}
				
			}
		}
		
		$current_menu_item['order'] = $order;
		
		
		if(isset($current_menu_item['hidden'])){
			
			if($current_menu_item['hidden'] == true){
				
				return false;
				
			} else {
				
				return $current_menu_item;
				
			}
			
		} else {
			
			return $current_menu_item;
			
		}
		
		
		
		
	}
	
	/**
	 * Applies top level menu item settings
	 * @since 1.4
	 */
	
	public function apply_top_level_settings($current_menu_item,$key){
		
		$a2020_options = get_option( 'admin2020_menu_settings' );
		
		$name = '';
		$link = '';
		$icon = '';
		$disabled_for = array(); 
		$optiongroup = array();
		$order = $key;
		
		if(is_array($a2020_options)){
			
			if(isset($a2020_options[$current_menu_item[2]])){
				$optiongroup = $a2020_options[$current_menu_item[2]];
				
				if(isset($optiongroup['name'])){
					$name = $optiongroup['name'];
					
					if($name != ""){
						$current_menu_item[0] = $name;
					}
				}
				
				if(isset($optiongroup['link'])){
					$link = $optiongroup['link'];
					
					if($link != ""){
						$current_menu_item[2] = $link;
						$current_menu_item['link'] = $link;
					}
				}
				
				if(isset($optiongroup['icon'])){
					$icon = $optiongroup['icon'];
					
					if($icon != ""){
						$current_menu_item['icon'] = $icon;
					}
				}
				
				if(isset($optiongroup['order'])){
					$order = $optiongroup['order'];
				} 
				
				if(isset($optiongroup['hidden_for'])){
					$disabled_for = $optiongroup['hidden_for'];
					
					if($this->is_hidden($disabled_for)){
						$current_menu_item['hidden'] = true;
					}
				}
				
			}
		}
		
		$current_menu_item['order'] = $order;
		
		if(isset($current_menu_item['hidden'])){
			
			if($current_menu_item['hidden'] == true){
				
				return false;
				
			} else {
				
				return $current_menu_item;
				
			}
			
		} else {
			
			return $current_menu_item;
			
		}
	}
	
	
	/**
	 * Applies top level menu item settings
	 * @since 1.4
	 */
	
	public function apply_sub_level_settings($subitems,$parentname){
		
		$a2020_options = get_option( 'admin2020_menu_settings' );
		if(!is_array($a2020_options)){
			return $subitems;
		}
		
		
		if(!isset($a2020_options[$parentname]['submenu'])){
			
			return $subitems;
			
		}
		
		$submenu_settings = $a2020_options[$parentname]['submenu'];
		
		
		$tempsub = array();
		
		foreach($subitems as $current_menu_item){
		
			$name = '';
			$link = '';
			$disabled_for = array(); 
			$optiongroup = array();
			
			///NO SETTINGS
			if(!isset($submenu_settings[$current_menu_item[2]])){
				array_push($tempsub,$current_menu_item); 
				continue;
			}
			
			
			$optiongroup = $submenu_settings[$current_menu_item[2]];
			
			if(isset($optiongroup['name'])){
				$name = $optiongroup['name'];
				
				if($name != ""){
					$current_menu_item[0] = $name;
				}
			}
			
			if(isset($optiongroup['link'])){
				$link = $optiongroup['link'];
				
				if($link != ""){
					$current_menu_item[2] = $link;
					$current_menu_item['link'] = $link;
				}
			}
			
			if(isset($optiongroup['hidden_for'])){
				$disabled_for = $optiongroup['hidden_for'];
				
				if($this->is_hidden($disabled_for)){
					$current_menu_item['hidden'] = true;
					continue;
				}
			}
			
			array_push($tempsub,$current_menu_item); 
			
		}
		//echo '<script>console.log("poo");</script>';
		//echo '<pre>' . print_r( $tempsub, true ) . '</pre>';
		//die();
		
		if(count($tempsub) < 1){
			return false;
		} else {
			return $tempsub;
		}
	}
	
	/**
	 * Checks if menu item is hidden
	 * @since 1.4
	 */
	
	
	public function is_hidden($disabled_for){
		
		if(!is_array($disabled_for)){
			return false;
		}
		
		$current_user = wp_get_current_user();
		$current_name = $current_user->display_name;
		$current_roles = $current_user->roles;
		$all_roles = wp_roles()->get_names();
		
		
		if(in_array($current_name, $disabled_for)){
			return true;
		}
		
		
		///MULTISITE SUPER ADMIN
		if(is_super_admin() && is_multisite()){
			if(in_array('Super Admin',$disabled_for)){
				return true;
			} else {
				return false;
			}
		}
		
		///NORMAL SUPER ADMIN
		if($current_user->ID === 1){
			if(in_array('Super Admin',$disabled_for)){
				return true;
			} else {
				return false;
			}
		}
		
		foreach ($current_roles as $role){
			
			$role_name = $all_roles[$role];
			
			if(in_array($role_name,$disabled_for)){
				return true;
			}
		}
		
	}
	
	
	/**
	* Save admin 2020 menu editor
	* @since 1.4
	*/
	
	public function a2020_save_menu_settings(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-menu-editor-security-nonce', 'security') > 0) {
			
			$options = $_POST['options'];
			$options =  $this->utils->clean_ajax_input($options);
			$a2020_options = get_option( 'admin2020_menu_settings' );
			
			if($options == "" || !is_array($options)){
				$message = __("No options supplied to save",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			if(is_array($options)){
				update_option( 'admin2020_menu_settings', $options);
				$returndata = array();
				$returndata['success'] = true;
				$returndata['message'] = __('Settings saved','admin2020');
				echo json_encode($returndata);
				die();
			} else {
				$message = __("Something went wrong",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			
			
		}
		die();	
		
	}
	
	/**
	* Resets menu editor options
	* @since 1.4
	*/
	
	public function a2020_delete_menu_settings(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-menu-editor-security-nonce', 'security') > 0) {
			
			update_option( 'admin2020_menu_settings', array());
			$a2020_options = get_option( 'admin2020_menu_settings' );
			
			if(!$a2020_options){
				$returndata = array();
				$returndata['success'] = true;
				$returndata['message'] = __('Settings reset','admin2020');
				echo json_encode($returndata);
				die();
			} else {
				$message = __("Something went wrong",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
		}
		die();	
		
	}
	
	
	
	/**
	* Export admin 2020 menu
	* @since 2.0.4
	*/
	
	public function a2020_export_menu(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-menu-editor-security-nonce', 'security') > 0) {
			
			$a2020_options = get_option( 'admin2020_menu_settings' );
			echo json_encode($a2020_options);
			
			
		}
		die();	
		
	}
	
	
	/**
	* Import admin 2020 menu
	* @since 2.0.4
	*/
	
	public function a2020_import_menu(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-menu-editor-security-nonce', 'security') > 0) {
			
			$new_options = $this->utils->clean_ajax_input($_POST['settings']); 
			
			if(is_array($new_options)){
				update_option( 'admin2020_menu_settings', $new_options);
			}
			
			echo __("Menu Imported","admin2020");
			
			
		}
		die();	
		
	}
	
	
}
