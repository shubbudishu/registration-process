<?php
if (!defined('ABSPATH')) {
	exit();
}

class Admin_2020_module_google_analytics
{
	public function __construct($version, $path, $utilities)
	{
		$this->version = $version;
		$this->path = $path;
		$this->utils = $utilities;
		$this->ga_data = '';
		$this->start_date = '';
		$this->end_date = '';
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
		
		
		add_action('admin_enqueue_scripts', [$this, 'add_styles'], 0);
		add_action('admin_enqueue_scripts', [$this, 'add_scripts'], 0);
		add_filter('admin2020_register_dash_card', array($this,'register_default_cards'));
		
		
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
		$data['title'] = __('Analytics','admin2020');
		$data['option_name'] = 'admin2020_google_analytics';
		$data['description'] = __('Creates the google analytics cards for the overview page.','admin2020');
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
		  <div class="uk-grid" id="a2020_analytics_settings" uk-grid>
			  <!-- LOCKED FOR USERS / ROLES -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Google Analytics Disabled for','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("Google Analytics will be disabled for any users or roles you select",'admin2020') ?></div>
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
					  jQuery('#a2020_analytics_settings #a2020-role-types').tokenize2({
						  placeholder: '<?php _e('Select roles or users','admin2020') ?>'
					  });
					  jQuery(document).ready(function ($) {
						  $('#a2020_analytics_settings #a2020-role-types').on('tokenize:select', function(container){
							  $(this).tokenize2().trigger('tokenize:search', [$(this).tokenize2().input.val()]);
						  });
					  })
				  </script>
				  
				 
				  
			  </div>	
			  <div class="uk-width-1-1@ uk-width-1-3@m">
			  </div>
			  
			  <!-- GOOGLE ANALYTICS -->
			<div class="uk-width-1-1@ uk-width-1-3@m">
				<div class="uk-h5 "><?php _e('Analytics account','admin2020')?></div>
				<div class="uk-text-meta"><?php _e("Select the google analytics account you wish to use for displaying data on the overview page",'admin2020') ?></div>
			</div>
			<div class="uk-width-1-1@ uk-width-2-3@m">
				
				<?php
				$google_icon = esc_url($this->path.'/assets/img/ga_btn_light.png');
				$google_icon_hover = esc_url($this->path.'/assets/img/ga_btn_dark.png');
				?>
				
				<a class="admin2020_google_sign_in" href="javascript:gauthWindow('https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=583702447211-6qiibg31fdkiug7r41qobqi1c1js1jps.apps.googleusercontent.com&redirect_uri=https://admintwentytwenty.com/analytics/view.php&scope=https://www.googleapis.com/auth/analytics.readonly&access_type=offline&approval_prompt=force');">
		
					<img class="admin2020_icon_no_hover" width='191' src="<?php echo $google_icon?>">
					<img class="admin2020_icon_hover" width='191' src="<?php echo $google_icon_hover?>">
		
				</a>
				
				<p><a class="uk-margin-top uk-text-warning" href="#" onclick="a2020_remove_analytics('<?php echo $optionname ?>')"><?php _e('Remove Account','admin2020')?></a></p>
				
			</div>		
			
			<script type="text/javascript">
			
			function gauthWindow (url) {
			
				var newWindow = window.open(url, 'name', 'height=600,width=450');
				
				if (window.focus) {
					newWindow.focus();
				}
				
				window.onmessage = function (e) {
				
					if (e.origin == 'https://admintwentytwenty.com'  && e.data) {
						try{
						
							var analyticsdata = JSON.parse(e.data);
							
							if (analyticsdata.code && analyticsdata.view){
								newWindow.close();
								admin2020_set_google_data(analyticsdata.view,analyticsdata.code,'<?php echo $optionname ?>');
							}
						
						} catch(err){
						///ERROR
						}
					}
				}
			}
			
			</script>
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
		
			if($_GET['page'] == 'admin_2020_overview'){
			
				wp_register_style(
					'admin2020_google_analytics',
					$this->path . 'assets/css/modules/admin-analytics.css',
					array(),
					$this->version
				);
				wp_enqueue_style('admin2020_google_analytics');
				
			}
		}
	}
	
	/**
	* Enqueue Admin Bar 2020 scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin_2020_overview'){
			
				
				// ADMIN 2020 ANALYTICS
				wp_enqueue_script('admin2020-analytics', $this->path . 'assets/js/admin2020/admin-analytics.min.js', array('jquery'));
				wp_localize_script('admin2020-analytics', 'admin2020_admin_analytics_ajax', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'security' => wp_create_nonce('admin2020-admin-analytics-security-nonce'),
				));
			
			}
		}
	  
	}
	
	/**
	* Registers analytics cards
	* @since 1.4
	*/
	
	public function register_default_cards($dashitems){
		
		if(!is_array($dashitems)){
			$dashitems = array();
		}
		
		$admin2020_cards = array(
			array('total_page_views',__('Total Page Views','admin2020'),'Analytics'),
			array('total_user_visits',__('Total Site Users','admin2020'),'Analytics'),
			array('average_page_speed',__('Average Page Speed','admin2020'),'Analytics'),
			array('visits_by_device',__('Visits By Device','admin2020'),'Analytics'),
			array('bounce_rate',__('Bounce Rate','admin2020'),'Analytics'),
			array('visits_by_country',__('Visits By Country','admin2020'),'Analytics'),
			array('visits_by_page',__('Visits By Page','admin2020'),'Analytics'),
			array('visits_by_source',__('Traffic Sources','admin2020'),'Analytics'),
			array('average_session_duration',__('Session Duration','admin2020'),'Analytics'),
		);
		
		foreach ($admin2020_cards as $card){
		  $function = $card[0];
		  $name = $card[1];
		  $category = $card[2];
		  array_push($dashitems,array($this,$function,$name,$category));
		}
	
		return $dashitems;
	}
	
	/**
	* Fetches Analytics data
	* @since 1.4
	*/
	
	public function admin2020_get_analytics_request($startdate = null,$enddate = null){
		
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
	
		$a2020_options = get_option( 'admin2020_settings' );
		
		if(!isset($a2020_options['modules'][$optionname]['view_id']) || !isset($a2020_options['modules'][$optionname]['refresh_token'])){
			$returndata = false;
			return $returndata;
		}
		
		$view = $a2020_options['modules'][$optionname]['view_id'];
		$code = $a2020_options['modules'][$optionname]['refresh_token'];
		
		if($view == "" || $code == ""){
			$returndata = false;
			return $returndata;
		}
		
		
		$remote = wp_remote_get( 'https://admintwentytwenty.com/analytics/fetch.php?code='.$code.'&view='.$view.'&sd='.$startdate.'&ed='.$enddate, array(
			'timeout' => 10,
			'headers' => array(
			'Accept' => 'application/json'
			) )
		);
		
		if ( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && ! empty( $remote['body'] ) ) {
		
			$remote = json_decode( $remote['body'] );
			return $remote;
		
		}  else {
			
			$returndata = false;
			return $returndata;
		
		}
	
	}
	
	
	
	/**
	* Gets analytics data if it doesn't exist, returns it if it does exist
	* @since 1.4
	*/
	public function get_analytics_data($startdate,$enddate){
		
		if(is_object($this->ga_data) && $this->start_date == $startdate && $this->end_date == $enddate){
			
			return $this->ga_data;
			
		} else {
			
			$this->ga_data = $this->admin2020_get_analytics_request($startdate,$enddate);
			$this->start_date = $startdate;
			$this->end_date = $enddate;
			
			return $this->ga_data;
		}
	}
	
	/**
	* Creates total page views card
	* @since 1.4
	*/
	
	public function total_page_views($startdate = null, $enddate = null){
	
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$data = $this->get_analytics_data($startdate,$enddate);
		
		if(is_object($data)){
			
			$generic_data = $data->generic;
			$total_page_views = $generic_data->totals->pageviews;
			$total_page_views_comparison = $generic_data->totals_comparison->pageviews;
			$timeline_page_views = $generic_data->timeline->data->pageviews;
			$date_timeline = $generic_data->timeline->dates;
			
			if($total_page_views == 0 || $total_page_views_comparison == 0){
			  $change = 0;
			} else {
			  $change = $total_page_views / $total_page_views_comparison * 100 - 100;
			}
			
			
			if($change > 0){
				$positive = '<span class="uk-icon-button a2020_up"  uk-icon="icon:chevron-up"></span>';
			} else {
				$positive = '<span class="uk-icon-button a2020_down" uk-icon="chevron-down"></span>';
			}
			
			$chart_data = array();
			$chart_data['label'] = __('Page Views','admin2020');
			$chart_data['data'] = $timeline_page_views;
			$chart_data['backgroundColor'] = 'rgb(12 92 239)';
			$chart_data['borderColor'] = 'rgb(12 92 239)';
			$chart_data['pointBackgroundColor'] = 'rgb(12 92 239)';
			$chart_data['pointBorderColor'] = '#fff';
			$chart_data['gradient'] = 'true';
			$chart_data['gradient_start'] = 'rgb(12, 92, 239,0.2)';
			$chart_data['gradient_end'] = 'rgba(12, 92, 239,0)';
			
		}
		
		$earlier = new DateTime($startdate);
		$later = new DateTime($enddate);
		$days = $later->diff($earlier)->format("%a");
		
		?>
				  
		  <div class="uk-card-body">
			  <?php if (!$data) { ?>
			  	<p><?php _e('Connect analytics account to view site statistics','admin2020')?></p>
			  <?php } else { ?>
			  <div class="uk-grid-small" uk-grid>
				  
				  <div class="uk-width-auto">
					<div class="uk-h2 uk-margin-remove-bottom uk-margin-right">
						<span class="uk-margin-small-right"><?php echo number_format($total_page_views) ?></span>
						<?php echo $positive ?>
				  		<span class="uk-text-meta uk-text-bold"><?php echo number_format($change,2) ?>%</span>
				  	</div>
					  
					  <div class="uk-text-meta"><?php echo __('vs previous','admin2020') . ' ' . $days . ' ' . __('days','admin2020') . ' (' . number_format($total_page_views_comparison) . ')'?></div>
				  </div>
			  </div>
			  <?php } ?>
		  </div>
		  
		  <?php if (is_object($data)) { ?>
		  <div class="">
			  <canvas id="total_page_views_chart"></canvas>
		  </div>
		  <script>
			  jQuery(document).ready(function ($) {
				  a2020_new_chart('total_page_views_chart', 'line', <?php echo json_encode($date_timeline) ?>, <?php echo json_encode($chart_data) ?>);
			  })
		  </script>
		  <?php } ?>
		<?php
	}
	
	/**
	* Creates total site users card
	* @since 1.4
	*/
	
	public function total_user_visits($startdate = null, $enddate = null){
	
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$data = $this->get_analytics_data($startdate,$enddate);
		
		if(is_object($data)){
			
			$generic_data = $data->generic;
			$total_page_views = $generic_data->totals->users;
			$total_page_views_comparison = $generic_data->totals_comparison->users;
			$timeline_page_views = $generic_data->timeline->data->users;
			$date_timeline = $generic_data->timeline->dates;
			
			
			if($total_page_views == 0 || $total_page_views_comparison == 0){
			  $change = 0;
			} else {
			  $change = $total_page_views / $total_page_views_comparison * 100 - 100;
			}
			
			if($change > 0){
				$positive = '<span class="uk-icon-button a2020_up"  uk-icon="icon:chevron-up"></span>';
			} else {
				$positive = '<span class="uk-icon-button a2020_down" uk-icon="chevron-down"></span>';
			}
			
			$chart_data = array();
			$chart_data['label'] = __('Site Users','admin2020');
			$chart_data['data'] = $timeline_page_views;
			$chart_data['backgroundColor'] = 'rgb(250 160 90)';
			$chart_data['borderColor'] = 'rgb(250 160 90)';
			$chart_data['pointBackgroundColor'] = 'rgb(250 160 90)';
			$chart_data['pointBorderColor'] = '#fff';
			$chart_data['gradient'] = 'true';
			$chart_data['gradient_start'] = 'rgb(250, 160, 90,0.2)';
			$chart_data['gradient_end'] = 'rgb(250, 160, 90,0)';
			
		}
		
		$earlier = new DateTime($startdate);
		$later = new DateTime($enddate);
		$days = $later->diff($earlier)->format("%a");
		
		?>
		<div class="uk-card-body">
		  <?php if (!$data) { ?>
			  <p><?php _e('Connect analytics account to view site statistics','admin2020')?></p>
		  <?php } else { ?>
		  <div class="uk-grid-small" uk-grid>
			  
			  <div class="uk-width-auto">
				<div class="uk-h2 uk-margin-remove-bottom uk-margin-right">
					<span class="uk-margin-small-right"><?php echo number_format($total_page_views) ?></span>
					<?php echo $positive ?>
					  <span class="uk-text-meta uk-text-bold"><?php echo number_format($change,2) ?>%</span>
				  </div>
				  
				  <div class="uk-text-meta"><?php echo __('vs previous','admin2020') . ' ' . $days . ' ' . __('days','admin2020') . ' (' . number_format($total_page_views_comparison) . ')'?></div>
			  </div>
		  </div>
		  <?php } ?>
		</div>
		
		<?php if (is_object($data)) { ?>
		<div class="">
		  <canvas id="total_website_users_chart"></canvas>
		</div>
		<script>
		  jQuery(document).ready(function ($) {
			  a2020_new_chart('total_website_users_chart', 'line', <?php echo json_encode($date_timeline) ?>, <?php echo json_encode($chart_data) ?>);
		  })
		</script>
		<?php } ?>
				  
		<?php
	}
	
	/**
	* Creates page speed card
	* @since 1.4
	*/
	
	public function average_page_speed($startdate = null, $enddate = null){
	
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$data = $this->get_analytics_data($startdate,$enddate);
		
		if(is_object($data)){
			
			$generic_data = $data->generic;
			$total_page_views = $generic_data->totals->pageLoadTime;
			$total_page_views_comparison = $generic_data->totals_comparison->pageLoadTime;
			$timeline_page_views = $generic_data->timeline->data->pageLoadTime;
			$date_timeline = $generic_data->timeline->dates;
			
			
			if($total_page_views == 0 || $total_page_views_comparison == 0){
			  $change = 0;
			} else {
			  $change = $total_page_views / $total_page_views_comparison * 100 - 100;
			}
			
			if($change < 0){
				$positive = '<span class="uk-icon-button a2020_up"  uk-icon="icon:chevron-down"></span>';
			} else {
				$positive = '<span class="uk-icon-button a2020_down" uk-icon="chevron-up"></span>';
			}
			
			$chart_data = array();
			$chart_data['label'] = __('Avg page speed','admin2020');
			$chart_data['data'] = $timeline_page_views;
			$chart_data['backgroundColor'] = 'rgb(255, 102, 236)';
			$chart_data['borderColor'] = 'rgb(255, 102, 236)';
			$chart_data['pointBackgroundColor'] = 'rgb(255, 102, 236)';
			$chart_data['pointBorderColor'] = '#fff';
			$chart_data['gradient'] = 'true';
			$chart_data['gradient_start'] = 'rgb(255, 102, 236,0.2)';
			$chart_data['gradient_end'] = 'rgb(255, 102, 236,0)';
			
		}
		
		$earlier = new DateTime($startdate);
		$later = new DateTime($enddate);
		$days = $later->diff($earlier)->format("%a");
		
		?>
				  
		<div class="uk-card-body">
		  <?php if (!$data) { ?>
			  <p><?php _e('Connect analytics account to view site statistics','admin2020')?></p>
		  <?php } else { ?>
		  <div class="uk-grid-small" uk-grid>
			  
			  <div class="uk-width-auto">
				<div class="uk-h2 uk-margin-remove-bottom uk-margin-right">
					<span class="uk-margin-small-right"><?php echo number_format($total_page_views,2) ?>s</span>
					<?php echo $positive ?>
					  <span class="uk-text-meta uk-text-bold"><?php echo number_format($change,2) ?>%</span>
				  </div>
				  
				  <div class="uk-text-meta"><?php echo __('vs previous','admin2020') . ' ' . $days . ' ' . __('days','admin2020') . ' (' . number_format($total_page_views_comparison,2) . 's)'?></div>
			  </div>
		  </div>
		  <?php } ?>
		</div>
		
		<?php if (is_object($data)) { ?>
		<div class="">
		  <canvas id="average_page_speed_chart"></canvas>
		</div>
		<script>
		  jQuery(document).ready(function ($) {
			  a2020_new_chart('average_page_speed_chart', 'line', <?php echo json_encode($date_timeline) ?>, <?php echo json_encode($chart_data) ?>);
		  })
		</script>
		<?php } ?>
		<?php
	}
	
	
	/**
	* Visits by device
	* @since 1.4
	*/
	
	public function visits_by_device($startdate = null, $enddate = null){
	
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$data = $this->get_analytics_data($startdate,$enddate);
		
		if(is_object($data)){
			
			$device_data = $data->device;
			$device_names = array();
			$device_totals = array();
			
			
			foreach($device_data->totals as $key => $value) {
				$total = ' ('.number_format($value).')';
				array_push($device_names,ucfirst($key.$total));
				array_push($device_totals,$value);
			}
			
			$chart_data = array();
			$chart_data['label'] = __('Devices','admin2020');
			$chart_data['data'] = $device_totals;
			$chart_data['backgroundColor'] = ["rgba(30, 135, 240,0.2)", "rgba(255, 159, 243,0.2)", "rgba(29, 209, 161, 0.2)"];
			$chart_data['borderColor'] = ["rgb(30, 135, 240)", "rgb(255, 159, 243)", "rgba(29, 209, 161, 1)"];
			$chart_data['pointBackgroundColor'] = 'rgb(255, 102, 236)';
			$chart_data['pointBorderColor'] = '#fff';
			$chart_data['gradient'] = 'false';
			
		}
		
		?>
				  
		<div class="uk-card-body">
		<?php if (!$data) { ?>
			<p><?php _e('Connect analytics account to view site statistics','admin2020')?></p>
		<?php } else { ?>
		
			<div class="">
				<canvas id="device_breakdown_chart"></canvas>
			</div>
			<script>
				jQuery(document).ready(function ($) {
					a2020_new_chart('device_breakdown_chart', 'doughnut', <?php echo json_encode($device_names) ?>, <?php echo json_encode($chart_data) ?>);
				})
			</script>
		
		<?php } ?>
		</div>
		<?php
	}
	
	
	/**
	* Creates bounce rate card
	* @since 1.4
	*/
	
	public function bounce_rate($startdate = null, $enddate = null){
	
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$data = $this->get_analytics_data($startdate,$enddate);
		
		if(is_object($data)){
			
			$generic_data = $data->generic;
			$total_page_views = $generic_data->totals->bounceRate;
			$total_page_views_comparison = $generic_data->totals_comparison->bounceRate;
			$timeline_page_views = $generic_data->timeline->data->bounceRate;
			
			if($total_page_views == 0 || $total_page_views_comparison == 0){
			  $change = 0;
			} else {
			  $change = $total_page_views / $total_page_views_comparison * 100 - 100;
			}
			
			if($change < 0){
				$positive = '<span class="uk-icon-button a2020_up"  uk-icon="icon:chevron-down"></span>';
			} else {
				$positive = '<span class="uk-icon-button a2020_down" uk-icon="chevron-up"></span>';
			}
			
		}
		
		$earlier = new DateTime($startdate);
		$later = new DateTime($enddate);
		$days = $later->diff($earlier)->format("%a");
		
		?>
				  
		<div class="uk-card-body">
		  <?php if (!$data) { ?>
			  <p><?php _e('Connect analytics account to view site statistics','admin2020')?></p>
		  <?php } else { ?>
		  <div class="uk-grid-small" uk-grid>
			  
			  <div class="uk-width-auto">
				<div class="uk-h2 uk-margin-remove-bottom uk-margin-right">
					<span class="uk-margin-small-right"><?php echo number_format($total_page_views,2) ?>%</span>
					<?php echo $positive ?>
					  <span class="uk-text-meta uk-text-bold"><?php echo number_format($change,2) ?>%</span>
				  </div>
				  
				  <div class="uk-text-meta"><?php echo __('vs previous','admin2020') . ' ' . $days . ' ' . __('days','admin2020') . ' (' . number_format($total_page_views_comparison,2) . '%)'?></div>
			  </div>
		  </div>
		  <?php } ?>
		</div>
		<?php
	}
	
	
	/**
	* Creates session duration card
	* @since 1.4
	*/
	
	public function average_session_duration($startdate = null, $enddate = null){
	
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$data = $this->get_analytics_data($startdate,$enddate);
		
		if(is_object($data)){
			
			$generic_data = $data->generic;
			$session_duration = $generic_data->totals->avgSessionDuration;
			$session_duration_comparison = $generic_data->totals_comparison->avgSessionDuration;
			
			$minutes = gmdate("i", $session_duration);
			$seconds = gmdate("s", $session_duration);
			$string = $minutes.'m '.$seconds.'s';
			
			$minutes = gmdate("i", $session_duration_comparison);
			$seconds = gmdate("s", $session_duration_comparison);
			$string_comparison = $minutes.'m '.$seconds.'s';
			
			$sessionduration_comparison_string = gmdate("i", $session_duration_comparison);
			
			
			
			if($session_duration == 0 || $session_duration_comparison == 0){
			  $change = 0;
			} else {
			  $change = $session_duration / $session_duration_comparison * 100 - 100;
			}
			
			if($change > 0){
				$positive = '<span class="uk-icon-button a2020_up"  uk-icon="icon:chevron-up"></span>';
			} else {
				$positive = '<span class="uk-icon-button a2020_down" uk-icon="chevron-down"></span>';
			}
			
		}
		
		$earlier = new DateTime($startdate);
		$later = new DateTime($enddate);
		$days = $later->diff($earlier)->format("%a");
		
		?>
				  
		<div class="uk-card-body">
		  <?php if (!$data) { ?>
			  <p><?php _e('Connect analytics account to view site statistics','admin2020')?></p>
		  <?php } else { ?>
		  <div class="uk-grid-small" uk-grid>
			  
			  <div class="uk-width-auto">
				<div class="uk-h2 uk-margin-remove-bottom uk-margin-right">
					<span class="uk-margin-small-right"><?php echo $string ?></span>
					<?php echo $positive ?>
					  <span class="uk-text-meta uk-text-bold"><?php echo number_format($change,2) ?>%</span>
				  </div>
				  
				  <div class="uk-text-meta"><?php echo __('vs previous','admin2020') . ' ' . $days . ' ' . __('days','admin2020') . ' (' . $string_comparison. ')'?></div>
			  </div>
		  </div>
		  <?php } ?>
		</div>
		<?php
	}
	
	
	/**
	* Creates visits by Country card
	* @since 1.4
	*/
	
	public function visits_by_country($startdate = null, $enddate = null){
	
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$data = $this->get_analytics_data($startdate,$enddate);
		
		if(is_object($data)){
			
			$country_data = $data->country;
			
		}
		
		
		?>
		<div class="uk-card-body">
		  <?php if (!$data) { ?>
			  <p><?php _e('Connect analytics account to view site statistics','admin2020')?></p>
		  <?php } else { ?>
		  <div>
			  
			  <table class="uk-table uk-table-justify uk-table-small">
				  <thead>
					  <th><?php _e('Country','admin2020')?></th>
					  <th><?php _e('Visits','admin2020')?></th>
					  <th></th>
					  <th class="uk-text-right"><?php _e('Change','admin2020')?></th>
				  </thead>
				  <tbody>
					  <?php foreach ($country_data->totals as $key => $value){
						  $countryname = $key;
						  $visits = $value;
						  $comparison_visits = $country_data->totals_comparison->$key;
						  
						  if($comparison_visits == 0 || $visits == 0){
							  $change = 0;
						  } else {
							  $change = $visits / $comparison_visits * 100 - 100;
						  }
						  
						  $cc = $this->get_country_code($countryname);
						  $flagurl = 'https://lipis.github.io/flag-icon-css/flags/4x3/'.$cc.'.svg';
						  
						  if($change > 0){
							  $positive = '<span class="uk-icon-button a2020_up"  uk-icon="icon:chevron-up" style="width:14px;height:14px"></span>';
						  } else {
							  $positive = '<span class="uk-icon-button a2020_down" uk-icon="chevron-down" style="width:14px;height:14px"></span>';
						  }
						  
						  ?>
						  <tr>
							  <td>
								  <img src="<?php echo $flagurl?>" class="uk-image uk-border-pill uk-margin-small-right" width="15">
								  <?php echo $countryname?>
							  </td>
							  <td><?php echo number_format($visits) ?></td>
							  <td><?php echo $positive?></td>
							  <td class="uk-text-right"><?php echo number_format($change,2) ?>%</td>
						  </tr>
					  <?php } ?>
					  
				  </tbody>
			  </table>
			  
		  </div>
		  <?php } ?>
		</div>
		<?php
	}
	
	
	/**
	* Creates visits by source card
	* @since 1.4
	*/
	
	public function visits_by_source($startdate = null, $enddate = null){
	
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$data = $this->get_analytics_data($startdate,$enddate);
		
		if(is_object($data)){
			
			$country_data = $data->source;
			
		}
		
		
		?>
				  
		<div class="uk-card-body">
		  <?php if (!$data) { ?>
			  <p><?php _e('Connect analytics account to view site statistics','admin2020')?></p>
		  <?php } else { ?>
		  <div>
			  
			  <table class="uk-table uk-table-justify uk-table-small">
				  <thead>
					  <th><?php _e('Source','admin2020')?></th>
					  <th><?php _e('Visits','admin2020')?></th>
					  <th></th>
					  <th class="uk-text-right"><?php _e('Change','admin2020')?></th>
				  </thead>
				  <tbody>
					  <?php foreach ($country_data->totals as $key => $value){
						  $countryname = $key;
						  $visits = $value;
						  $comparison_visits = $country_data->totals_comparison->$key;
						  
						  if($comparison_visits == 0){
							  $change = 0;
						  } else {
							  $change = $visits / $comparison_visits * 100 - 100;
						  }
						  $flagurl = "https://s2.googleusercontent.com/s2/favicons?domain=".$countryname;
						  
						  if($change > 0){
							  $positive = '<span class="uk-icon-button a2020_up"  uk-icon="icon:chevron-up" style="width:14px;height:14px"></span>';
						  } else {
							  $positive = '<span class="uk-icon-button a2020_down" uk-icon="chevron-down" style="width:14px;height:14px"></span>';
						  }
						  
						  ?>
						  <tr>
							  <td>
								  <img src="<?php echo $flagurl?>" class="uk-image uk-border-pill uk-margin-small-right" width="15">
								  <?php echo $countryname?>
							  </td>
							  <td><?php echo number_format($visits) ?></td>
							  <td><?php echo $positive?></td>
							  <td class="uk-text-right"><?php echo number_format($change,2) ?>%</td>
						  </tr>
					  <?php } ?>
					  
				  </tbody>
			  </table>
			  
		  </div>
		  <?php } ?>
		</div>
		<?php
	}
	
	/**
	* Creates visits by page card
	* @since 1.4
	*/
	
	public function visits_by_page($startdate = null, $enddate = null){
	
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$data = $this->get_analytics_data($startdate,$enddate);
		
		if(is_object($data)){
			
			$country_data = $data->path;
			
		}
		
		
		?>
				  
		<div class="uk-card-body">
		  <?php if (!$data) { ?>
			  <p><?php _e('Connect analytics account to view site statistics','admin2020')?></p>
		  <?php } else { ?>
		  <div>
			  
			  <table class="uk-table uk-table-justify uk-table-small">
				  <thead>
					  <th><?php _e('Page','admin2020')?></th>
					  <th><?php _e('Visits','admin2020')?></th>
					  <th></th>
					  <th class="uk-text-right"><?php _e('Change','admin2020')?></th>
				  </thead>
				  <tbody>
					  <?php foreach ($country_data->totals as $key => $value){
						  $countryname = $key;
						  $visits = $value;
						  $comparison_visits = $country_data->totals_comparison->$key;
						  
							if($comparison_visits == 0 || $visits == 0){
								$change = 0;
							} else {
								$change = $visits / $comparison_visits * 100 - 100;
							}
						  
						  
						  if($change > 0){
							  $positive = '<span class="uk-icon-button a2020_up"  uk-icon="icon:chevron-up" style="width:14px;height:14px"></span>';
						  } else {
							  $positive = '<span class="uk-icon-button a2020_down" uk-icon="chevron-down" style="width:14px;height:14px"></span>';
						  }
						  
						  ?>
						  <tr>
							  <td><?php echo $countryname ?></td>
							  <td><?php echo number_format($visits) ?></td>
							  <td><?php echo $positive?></td>
							  <td class="uk-text-right"><?php echo number_format($change,2) ?>%</td>
						  </tr>
					  <?php } ?>
					  
				  </tbody>
			  </table>
			  
		  </div>
		  <?php } ?>
		</div>
				  
		<?php
	}


	public function get_country_code($countryname){
		
		$countries = array(
		  'AF' => 'Afghanistan',
		  'AX' => '&Aring;land Islands',
		  'AL' => 'Albania',
		  'DZ' => 'Algeria',
		  'AS' => 'American Samoa',
		  'AD' => 'Andorra',
		  'AO' => 'Angola',
		  'AI' => 'Anguilla',
		  'AG' => 'Antigua and Barbuda',
		  'AR' => 'Argentina',
		  'AM' => 'Armenia',
		  'AW' => 'Aruba',
		  'AU' => 'Australia',
		  'AT' => 'Austria',
		  'AZ' => 'Azerbaijan',
		  'BS' => 'Bahamas (the)',
		  'BH' => 'Bahrain',
		  'BD' => 'Bangladesh',
		  'BB' => 'Barbados',
		  'BY' => 'Belarus',
		  'BE' => 'Belgium',
		  'BZ' => 'Belize',
		  'BJ' => 'Benin',
		  'BM' => 'Bermuda',
		  'BT' => 'Bhutan',
		  'BO' => 'Bolivia (Plurinational State of)',
		  'BA' => 'Bosnia and Herzegovina',
		  'BW' => 'Botswana',
		  'BV' => 'Bouvet Island',
		  'BR' => 'Brazil',
		  'IO' => 'British Indian Ocean Territory (the)',
		  'BN' => 'Brunei Darussalam',
		  'BG' => 'Bulgaria',
		  'BF' => 'Burkina Faso',
		  'BI' => 'Burundi',
		  'KH' => 'Cambodia',
		  'CV' => 'Cabo Verde',
		  'CM' => 'Cameroon',
		  'CA' => 'Canada',
		  'CT' => 'Catalonia',
		  'KY' => 'Cayman Islands (the)',
		  'CF' => 'Central African Republic (the)',
		  'TD' => 'Chad',
		  'CL' => 'Chile',
		  'CN' => 'China',
		  'CX' => 'Christmas Island',
		  'CC' => 'Cocos (Keeling) Islands (the)',
		  'CO' => 'Colombia',
		  'KM' => 'Comoros',
		  'CD' => 'Congo (the Democratic Republic of the)',
		  'CG' => 'Congo (the)',
		  'CK' => 'Cook Islands (the)',
		  'CR' => 'Costa Rica',
		  'HR' => 'Croatia',
		  'CU' => 'Cuba',
		  'CY' => 'Cyprus',
		  'CZ' => 'Czech Republic (the)',
		  'DK' => 'Denmark',
		  'DJ' => 'Djibouti',
		  'DM' => 'Dominica',
		  'DO' => 'Dominican Republic (the)',
		  'EC' => 'Ecuador',
		  'EG' => 'Egypt',
		  'SV' => 'El Salvador',
		  'EN' => 'England',
		  'GQ' => 'Equatorial Guinea',
		  'ER' => 'Eritrea',
		  'EE' => 'Estonia',
		  'ET' => 'Ethiopia',
		  'EU' => 'European Union',
		  'FK' => 'Falkland Islands (the) [Malvinas]',
		  'FO' => 'Faroe Islands (the)',
		  'FJ' => 'Fiji',
		  'FI' => 'Finland',
		  'FR' => 'France',
		  'GF' => 'French Guiana',
		  'PF' => 'French Polynesia',
		  'TF' => 'French Southern Territories (the)',
		  'GA' => 'Gabon',
		  'GM' => 'Gambia (the)',
		  'GE' => 'Georgia',
		  'DE' => 'Germany',
		  'GH' => 'Ghana',
		  'GI' => 'Gibraltar',
		  'GR' => 'Greece',
		  'GL' => 'Greenland',
		  'GD' => 'Grenada',
		  'GP' => 'Guadeloupe',
		  'GU' => 'Guam',
		  'GT' => 'Guatemala',
		  'GN' => 'Guinea',
		  'GW' => 'Guinea-Bissau',
		  'GY' => 'Guyana',
		  'HT' => 'Haiti',
		  'HM' => 'Heard Island and McDonald Islands',
		  'VA' => 'Holy See (the)',
		  'HN' => 'Honduras',
		  'HK' => 'Hong Kong',
		  'HU' => 'Hungary',
		  'IS' => 'Iceland',
		  'IN' => 'India',
		  'ID' => 'Indonesia',
		  'IR' => 'Iran (Islamic Republic of)',
		  'IQ' => 'Iraq',
		  'IE' => 'Ireland',
		  'IL' => 'Israel',
		  'IT' => 'Italy',
		  'JM' => 'Jamaica',
		  'JP' => 'Japan',
		  'JO' => 'Jordan',
		  'KZ' => 'Kazakhstan',
		  'KE' => 'Kenya',
		  'KI' => 'Kiribati',
		  'KP' => 'Korea (the Democratic People\'s Republic of)',
		  'KR' => 'Korea (the Republic of)',
		  'KW' => 'Kuwait',
		  'KG' => 'Kyrgyzstan',
		  'LA' => 'Lao People\'s Democratic Republic (the)',
		  'LV' => 'Latvia',
		  'LB' => 'Lebanon',
		  'LS' => 'Lesotho',
		  'LR' => 'Liberia',
		  'LY' => 'Libya',
		  'LI' => 'Liechtenstein',
		  'LT' => 'Lithuania',
		  'LU' => 'Luxembourg',
		  'MO' => 'Macao',
		  'MK' => 'Macedonia (the former Yugoslav Republic of)',
		  'MG' => 'Madagascar',
		  'MW' => 'Malawi',
		  'MY' => 'Malaysia',
		  'MV' => 'Maldives',
		  'ML' => 'Mali',
		  'MT' => 'Malta',
		  'MH' => 'Marshall Islands (the)',
		  'MQ' => 'Martinique',
		  'MR' => 'Mauritania',
		  'MU' => 'Mauritius',
		  'YT' => 'Mayotte',
		  'MX' => 'Mexico',
		  'FM' => 'Micronesia (Federated States of)',
		  'MD' => 'Moldova (the Republic of)',
		  'MC' => 'Monaco',
		  'MN' => 'Mongolia',
		  'ME' => 'Montenegro',
		  'MS' => 'Montserrat',
		  'MA' => 'Morocco',
		  'MZ' => 'Mozambique',
		  'MM' => 'Myanmar',
		  'NA' => 'Namibia',
		  'NR' => 'Nauru',
		  'NP' => 'Nepal',
		  'NL' => 'Netherlands',
		  'AN' => 'Netherlands Antilles',
		  'NC' => 'New Caledonia',
		  'NZ' => 'New Zealand',
		  'NI' => 'Nicaragua',
		  'NE' => 'Niger (the)',
		  'NG' => 'Nigeria',
		  'NU' => 'Niue',
		  'NF' => 'Norfolk Island',
		  'MP' => 'Northern Mariana Islands (the)',
		  'NO' => 'Norway',
		  'OM' => 'Oman',
		  'PK' => 'Pakistan',
		  'PW' => 'Palau',
		  'PS' => 'Palestine, State of',
		  'PA' => 'Panama',
		  'PG' => 'Papua New Guinea',
		  'PY' => 'Paraguay',
		  'PE' => 'Peru',
		  'PH' => 'Philippines (the)',
		  'PN' => 'Pitcairn',
		  'PL' => 'Poland',
		  'PT' => 'Portugal',
		  'PR' => 'Puerto Rico',
		  'QA' => 'Qatar',
		  'RE' => 'R&eacute;union',
		  'RO' => 'Romania',
		  'RU' => 'Russian Federation (the)',
		  'RW' => 'Rwanda',
		  'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
		  'KN' => 'Saint Kitts and Nevis',
		  'LC' => 'Saint Lucia',
		  'PM' => 'Saint Pierre and Miquelon',
		  'VC' => 'Saint Vincent and the Grenadines',
		  'WS' => 'Samoa',
		  'SM' => 'San Marino',
		  'ST' => 'Sao Tome and Principe',
		  'SA' => 'Saudi Arabia',
		  'AB' => 'Scotland',
		  'SN' => 'Senegal',
		  'RS' => 'Serbia',
		  'CS' => 'Serbia and Montenegro',
		  'SC' => 'Seychelles',
		  'SL' => 'Sierra Leone',
		  'SG' => 'Singapore',
		  'SK' => 'Slovakia',
		  'SI' => 'Slovenia',
		  'SB' => 'Solomon Islands',
		  'SO' => 'Somalia',
		  'ZA' => 'South Africa',
		  'GS' => 'South Georgia and the South Sandwich Islands',
		  'ES' => 'Spain',
		  'LK' => 'Sri Lanka',
		  'SD' => 'Sudan (the)',
		  'SR' => 'Suriname',
		  'SJ' => 'Svalbard and Jan Mayen',
		  'SZ' => 'Swaziland',
		  'SE' => 'Sweden',
		  'CH' => 'Switzerland',
		  'SY' => 'Syrian Arab Republic',
		  'TW' => 'Taiwan (Province of China)',
		  'TJ' => 'Tajikistan',
		  'TZ' => 'Tanzania, United Republic of',
		  'TH' => 'Thailand',
		  'TL' => 'Timor-Leste',
		  'TG' => 'Togo',
		  'TK' => 'Tokelau',
		  'TO' => 'Tonga',
		  'TT' => 'Trinidad and Tobago',
		  'TN' => 'Tunisia',
		  'TR' => 'Turkey',
		  'TM' => 'Turkmenistan',
		  'TC' => 'Turks and Caicos Islands (the)',
		  'TV' => 'Tuvalu',
		  'UG' => 'Uganda',
		  'UA' => 'Ukraine',
		  'AE' => 'United Arab Emirates (the)',
		  'GB' => 'United Kingdom',
		  'UM' => 'United States Minor Outlying Islands (the)',
		  'US' => 'United States of America (the)',
		  'US' => 'United States',
		  'UY' => 'Uruguay',
		  'UZ' => 'Uzbekistan',
		  'VU' => 'Vanuatu',
		  'VE' => 'Venezuela (Bolivarian Republic of)',
		  'VN' => 'Viet Nam',
		  'VG' => 'Virgin Islands (British)',
		  'VI' => 'Virgin Islands (U.S.)',
		  'WA' => 'Wales',
		  'WF' => 'Wallis and Futuna',
		  'EH' => 'Western Sahara',
		  'YE' => 'Yemen',
		  'ZM' => 'Zambia',
		  'ZW' => 'Zimbabwe'
		);
		
		$result = array_search ( $countryname , $countries );
		
		if($result) {
			return strtolower($result);
		} else {
			return false;
		}
		
		
	}
	
	
}
