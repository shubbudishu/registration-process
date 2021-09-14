<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_advanced
{
    public function __construct($version, $path, $utilities)
    {
        $this->version = $version;
        $this->path = $path;
        $this->utils = $utilities;
    }

    /**
     * Loads menu actions
     * @since 1.0
     */

    public function start()
    {
		///REGISTER THIS COMPONENT
		add_filter('admin2020_register_component', array($this,'register'));
		add_action('admin_enqueue_scripts', [$this, 'add_scripts'], 0);
		
		if(!$this->utils->enabled($this)){
			return;
		}
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		
		if($this->utils->is_locked($optionname)){
			return;
		}
		
		
		
		add_action('admin_head',array($this,'add_body_styles'),0);
		
		
		
    }
	
	
	/**
	 * Loads custom js and css
	 * @since 1.0
	 */
	
	public function start_front()
	{
		
		if(!$this->utils->enabled($this)){
			return;
		}
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		
		if($this->utils->is_locked($optionname)){
			return;
		}
		
		
		
		add_action('login_head',array($this,'add_body_styles_front'));
		
	}
	
	
	/**
	 * Register advanced component
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
		$data['title'] = __('Advanced','admin2020');
		$data['option_name'] = 'admin2020_admin_advanced';
		$data['description'] = __('Creates options for adding customm CSS and JS.','admin2020');
		return $data;
		
	}
	/**
	 * Returns settings for module
	 * @since 1.4
	 */
	 public function render_settings(){
		  
		  $info = $this->component_info();
		  $optionname = $info['option_name'];
		  
		  $dark_background = $this->utils->get_option($optionname,'dark-background');
		  $light_background = $this->utils->get_option($optionname,'light-background');
		  $customcss = $this->utils->get_option($optionname,'custom-css');
		  $customjs = $this->utils->get_option($optionname,'custom-js');
		  
		  $disabled_for = $this->utils->get_option($optionname,'disabled-for');
		  if($disabled_for == ""){
			$disabled_for = array();
		  }
		  ///GET ROLES
		  global $wp_roles;
		  ///GET USERS
		  $blogusers = get_users();
		  ?>
		  <div class="uk-grid" id="a2020_advanced_settings" uk-grid>
			  <!-- LOCKED FOR USERS / ROLES -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Advanced Disabled for','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("Custom CSS and JS will not load for the selected roles and users.",'admin2020') ?></div>
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
					  jQuery('#a2020_advanced_settings #a2020-role-types').tokenize2({
						  placeholder: '<?php _e('Select roles or users','admin2020') ?>'
					  });
					  jQuery(document).ready(function ($) {
						  $('#a2020_advanced_settings #a2020-role-types').on('tokenize:select', function(container){
							  $(this).tokenize2().trigger('tokenize:search', [$(this).tokenize2().input.val()]);
						  });
					  })
				  </script>
				  
			  </div>	
			  
			<div class="uk-width-1-1@ uk-width-1-3@m"></div>
			
			<!-- CUSTOM CSS -->
			<div class="uk-width-1-1@ uk-width-1-3@m">
			  <div class="uk-h5 "><?php _e('Custom CSS','admin2020')?></div>
			  <div class="uk-text-meta"><?php _e("CSS added here will be loaded on every admin page.",'admin2020') ?></div>
			</div>
			<div class="uk-width-1-1@ uk-width-2-3@m">
			  
			  <textarea class="a2020_setting" module-name="<?php echo $optionname?>" name="custom-css" id="custom-css" style="display: none;"><?php echo stripslashes($customcss)?></textarea>
			  
			  <div class="a2020_code_editor" id="a2020-css-editor"></div>
			  
			</div>	
			<script>
			  
			  jQuery(document).ready(function ($) {
				  
				  
				  let codeArea = new CodeFlask('#a2020-css-editor', {
					language: 'css',
					lineNumbers: true,
				  })
				  codeArea.updateCode(jQuery('#custom-css').val());
				  
				  codeArea.onUpdate((code) => {
					jQuery('#custom-css').val(code);
				  });
			  })
			</script> 
			
			<!-- CUSTOM JS -->
			<div class="uk-width-1-1@ uk-width-1-3@m">
			  <div class="uk-h5 "><?php _e('Custom JS','admin2020')?></div>
			  <div class="uk-text-meta"><?php _e("JS added here will be loaded on every admin page.",'admin2020') ?></div>
			</div>
			<div class="uk-width-1-1@ uk-width-2-3@m">
			  
			  <textarea class="a2020_setting" module-name="<?php echo $optionname?>" name="custom-js" id="custom-js" style="display: none;"><?php echo stripslashes($customjs)?></textarea>
			  
			  <div class="a2020_code_editor" id="a2020-js-editor"></div>
			  
			</div>	
			<script>
			  
			  jQuery(document).ready(function ($) {
				  
				  
				  let codeAreajs = new CodeFlask('#a2020-js-editor', {
					language: 'js',
					lineNumbers: true,
				  })
				  codeAreajs.updateCode(jQuery('#custom-js').val());
				  
				  codeAreajs.onUpdate((code) => {
					jQuery('#custom-js').val(code);
				  });
			  })
			</script> 
			  
			  	
		  </div>	
		  
		  <?php
	  }
	
	/**
	* Enqueue advanced 2020 scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin2020-settings'){
	  
				  ///CODE JAR FRAMEWORK
				  wp_enqueue_script('codeflask', $this->path . 'assets/js/codeflask/codeflask.min.js', array('jquery'));
				  
				  
			}
		} 
	  
	}
	
	/**
	* Adds custom css for custom background colors
	* @since 1.4
	*/
	
	public function add_body_styles(){
		
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$customcss = $this->utils->get_option($optionname,'custom-css');
		$customjs = $this->utils->get_option($optionname,'custom-js');
		
		
		if ($customcss != ""){
		  echo '<style type="text/css">';
		  echo stripslashes($customcss);
		  echo '</style>';
		}
		
		
		if ($customjs != ""){
		  echo '<script>';
		  echo stripslashes($customjs);
		  echo '</script>';
		}
	}
	
	
	/**
	* Adds custom css for custom background colors
	* @since 1.4
	*/
	
	public function add_body_styles_front(){
		
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$customcss = $this->utils->get_option($optionname,'custom-css');
		
		
		if ($customcss != ""){
		  echo '<style type="text/css">';
		  echo stripslashes($customcss);
		  echo '</style>';
		}
	}
	
	
}
