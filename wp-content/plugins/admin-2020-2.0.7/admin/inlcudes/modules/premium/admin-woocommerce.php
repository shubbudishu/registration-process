<?php
if (!defined('ABSPATH')) {
	exit();
}

class Admin_2020_module_woocommerce
{
	public function __construct($version, $path, $utilities)
	{
		$this->version = $version;
		$this->path = $path;
		$this->utils = $utilities;
		$this->orders = '';
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
		
		
		add_filter('admin2020_register_dash_card', array($this,'register_default_cards'));
		
		
	}
	
	
	/**
	 * Register admin bar component
	 * @since 1.4
	 * @variable $components (array) array of registered admin 2020 components
	 */
	public function register($components){
		
		///DON'T START IF WC ISN'T INSTALLED
		if (!is_plugin_active('woocommerce/woocommerce.php')){
			return $components;
		}
		
		array_push($components,$this);
		return $components;
		
	}
	
	
	
	/**
	 * Returns component info for settings page
	 * @since 1.4
	 */
	public function component_info(){
		
		$data = array();
		$data['title'] = __('WooCommerce','admin2020');
		$data['option_name'] = 'admin2020_woocommerce';
		$data['description'] = __('Creates the woocommerce cards for the overview page.','admin2020');
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
		  
		  if(!is_array($disabled_for)){
			  $disabled_for = array();
		  }
		  ///GET ROLES
		  global $wp_roles;
		  ///GET USERS
		  $blogusers = get_users();
		  ?>
		  <div class="uk-grid" id="a2020_woocommerce_settings" uk-grid>
			  <!-- LOCKED FOR USERS / ROLES -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('WooCommerce Cards','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("WooCommerce cards will be disabled for any users or roles you select",'admin2020') ?></div>
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
					  jQuery('#a2020_woocommerce_settings #a2020-role-types').tokenize2({
						  placeholder: '<?php _e('Select roles or users','admin2020') ?>'
					  });
					  jQuery(document).ready(function ($) {
						  $('#a2020_woocommerce_settings #a2020-role-types').on('tokenize:select', function(container){
							  $(this).tokenize2().trigger('tokenize:search', [$(this).tokenize2().input.val()]);
						  });
					  })
				  </script>
				  
				 
				  
			  </div>	
			  <div class="uk-width-1-1@ uk-width-1-3@m">
			  </div>
			  
			 
		  </div>	
		  
		  <?php
	  }
	
	
	/**
	* Registers analytics cards
	* @since 1.4
	*/
	
	public function register_default_cards($dashitems){
		
		///DON'T START IF WC ISN'T INSTALLED
		if (!is_plugin_active('woocommerce/woocommerce.php')){
			return $dashitems;
		}
		
		if(!is_array($dashitems)){
			$dashitems = array();
		}
		
		$admin2020_cards = array(
			array('total_sales',__('Total Sales','admin2020'),'Store'),
			array('total_orders',__('Total Orders','admin2020'),'Store'),
			array('average_order',__('Average Order Value','admin2020'),'Store'),
			array('products_by_sold',__('Popular Products','admin2020'),'Store'),
			array('recent_orders',__('Recent Orders','admin2020'),'Store'),
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
	* Fetches orders  / returns current query
	* @since 1.4
	*/
	
	public function get_orders($startdate = null, $enddate = null){
		
		
		if(is_object($this->orders) && $this->start_date == $startdate && $this->end_date == $enddate ) {
			return $this->orders;
		} else {
			$this->start_date = $startdate;
			$this->end_date = $enddate;
			
			$args = [
				'post_type' => 'shop_order',
				'posts_per_page' => '-1',
				'post_status' => array('wc-completed','wc-pending','wc-processing', 'wc-on-hold'),
				'date_query' => array(
					array(
						'after'     => $startdate,
						'before'    => $enddate,
						'inclusive' => true,
						),
					  ),
			];
			$my_query = new WP_Query($args);
			
			$this->orders = $my_query;
			return $my_query;
		}
	}
	
	/**
	* Creates total sales chart card
	* @since 1.4
	*/
	
	public function total_sales($startdate = null, $enddate = null){
	
		if($startdate == null && $enddate == null){
			
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
	
		///GET ARRAY OF DATES
		$dates = $this->utils->date_array($startdate,$enddate);
		$json_dates = json_encode($dates);
		
		$earlier = new DateTime($startdate);
		$later = new DateTime($enddate);
		$days = $later->diff($earlier)->format("%a");
	
		global  $woocommerce;
		$curreny_symbol = get_woocommerce_currency_symbol();
	
		
		
		$allorders = $this->get_orders($startdate,$enddate);
	
		$total = 0;
	
		$orders = $allorders->posts;
		$total_orders = $allorders->post_count;
		$logo = esc_url(plugins_url('/assets/img/woocommerce.png', __DIR__));
		$array_orders_totals = array();
		$array_orders_counts = array();
	
		foreach ($dates as $date){
		  $array_orders_totals[$date] = 0;
		  $array_orders_counts[$date] = 0;
		}
		
		if($total_orders > 1){
			
			foreach ($orders as $ctr => $value)
			{
				$order_id = $value->ID;
		
				$order = wc_get_order($order_id);
				
				$order_total = $order->get_total();
				$order_date = date("d/m/Y",strtotime($order->get_date_created()));
		
				$array_orders_totals[$order_date] += $order_total;
				$array_orders_counts[$order_date] += 1;
		
				$total_sales += $order_total;
			}
		
			$temparray = array();
			foreach($array_orders_totals as $item){
			  array_push($temparray,$item);
			}
			$array_orders_totals = $temparray;
		
			$temparray = array();
			foreach($array_orders_counts as $item){
			  array_push($temparray,$item);
			}
			$array_orders_counts = $temparray;
		
			$json_orders_value = json_encode($array_orders_totals);
			$json_orders_count = json_encode($array_orders_counts);
			
			$chart_data = array();
			$chart_data['label'] = __('Total Sales','admin2020');
			$chart_data['data'] = $array_orders_totals;
			$chart_data['backgroundColor'] = "rgba(180, 118, 255, 0.2)";
			$chart_data['borderColor'] =  "rgb(180 118 255)";
			$chart_data['pointBackgroundColor'] =  "rgba(180, 118, 255, 0.2)";
			$chart_data['pointBorderColor'] = "rgb(180 118 255)";
			$chart_data['gradient'] = 'false';
			
		}
		
	
		?>
	
	
		<div class="uk-card-body">
			<?php if($total_orders < 1){ ?>
			
				<p><?php _e("No sales data available for dates","admin2020");?> </p>
			
			<?php } else { ?>
			
				<div class="uk-h2 uk-text-primary uk-margin-remove">
					<?php echo $curreny_symbol.number_format($total_sales)?>
					
				</div>
				<span class="uk-text-meta"><?php echo __('In the last','admin2020').' '.$days.' '.__('days','admin2020')?></span>
				
				<div class="uk-width-1-1" style="margin-top: 20px;">
					<canvas id="woocommerce_sales_chart" style="height:250px;max-height:250px;" ></canvas>
				</div>
				
				<script>
				
					jQuery(document).ready(function($) {
					
						a2020_new_chart('woocommerce_sales_chart', 'bar', <?php echo $json_dates ?>, <?php echo json_encode($chart_data) ?>);
					
					})
				
				</script>
			
			<?php } ?>
		</div>
	
		<?php
	  }
	
	  /**
	  * Creates total sales chart card
	  * @since 1.4
	  */
	  
	  public function total_orders($startdate = null, $enddate = null){
	  
		  if($startdate == null && $enddate == null){
			  
			$enddate = date('Y-m-d');
			$startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		  
		  }
	  
		  ///GET ARRAY OF DATES
		  $dates = $this->utils->date_array($startdate,$enddate);
		  $json_dates = json_encode($dates);
		  
		  $earlier = new DateTime($startdate);
		  $later = new DateTime($enddate);
		  $days = $later->diff($earlier)->format("%a");
	  
		  global  $woocommerce;
		  $curreny_symbol = get_woocommerce_currency_symbol();
	  
		  $allorders = $this->get_orders($startdate,$enddate);
	  
		  $total = 0;
	  
		  $orders = $allorders->posts;
		  $total_orders = $allorders->post_count;
		  $logo = esc_url(plugins_url('/assets/img/woocommerce.png', __DIR__));
		  $array_orders_totals = array();
		  $array_orders_counts = array();
	  
		  foreach ($dates as $date){
			$array_orders_totals[$date] = 0;
			$array_orders_counts[$date] = 0;
		  }
		  
		  if($total_orders > 1){
			  
			  foreach ($orders as $ctr => $value)
			  {
				  $order_id = $value->ID;
		  
				  $order = wc_get_order($order_id);
				  $order_total = $order->get_total();
				  $order_date = date("d/m/Y",strtotime($order->get_date_created()));
		  
				  $array_orders_totals[$order_date] += $order_total;
				  $array_orders_counts[$order_date] += 1;
		  
				  $total_sales += $order_total;
			  }
		  
			  $temparray = array();
			  foreach($array_orders_totals as $item){
				array_push($temparray,$item);
			  }
			  $array_orders_totals = $temparray;
		  
			  $temparray = array();
			  foreach($array_orders_counts as $item){
				array_push($temparray,$item);
			  }
			  $array_orders_counts = $temparray;
		  
			  $json_orders_value = json_encode($array_orders_totals);
			  $json_orders_count = json_encode($array_orders_counts);
			  
			  $chart_data = array();
			  $chart_data['label'] = __('Total Orders','admin2020');
			  $chart_data['data'] = $array_orders_counts;
			  $chart_data['backgroundColor'] = "rgba(180, 118, 255, 0.2)";
			  $chart_data['borderColor'] =  "rgb(180 118 255)";
			  $chart_data['pointBackgroundColor'] =  "rgba(180, 118, 255, 0.2)";
			  $chart_data['pointBorderColor'] = "rgb(180 118 255)";
			  $chart_data['gradient'] = 'false';
			  
		  }
		  ?>
	  
	  
		  <div class="uk-card-body">
			  <?php
			  if($total_orders < 1){
			  ?>
			  
				  <p><?php _e("No sales data available for dates","admin2020");?> </p>
			  
			  <?php
			  } else {
			  ?>
			  
				  <div class="uk-h2 uk-text-primary uk-margin-remove">
					  <?php echo number_format($total_orders)?>
				  </div>
				  <span class="uk-text-meta"><?php echo __('In the last','admin2020').' '.$days.' '.__('days','admin2020')?></span>
				  
				  <div class="uk-width-1-1" style="margin-top: 20px;">
				  <canvas id="woocommerce_orders_chart" style="height:250px;max-height:250px;" ></canvas>
				  </div>
				  
				  <script>
				  
					  jQuery(document).ready(function($) {
					  
						  a2020_new_chart('woocommerce_orders_chart', 'bar', <?php echo $json_dates ?>, <?php echo json_encode($chart_data) ?>);
					  
					  })
				  
				  </script>
			  
			  <?php } ?>
		  </div>
	  
	  
		  <?php
		}
		
		
		/**
		* Creates total sales chart card
		* @since 1.4
		*/
		
		public function average_order($startdate = null, $enddate = null){
		
		  if($startdate == null && $enddate == null){
			  
			$enddate = date('Y-m-d');
			$startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		  
		  }
		
		  ///GET ARRAY OF DATES
		  $dates = $this->utils->date_array($startdate,$enddate);
		  $json_dates = json_encode($dates);
		  
		  $earlier = new DateTime($startdate);
		  $later = new DateTime($enddate);
		  $days = $later->diff($earlier)->format("%a");
		
		  global  $woocommerce;
		  $curreny_symbol = get_woocommerce_currency_symbol();
		
		  $allorders = $this->get_orders($startdate,$enddate);
		
		  $total = 0;
		
		  $orders = $allorders->posts;
		  $total_orders = $allorders->post_count;
		  $logo = esc_url(plugins_url('/assets/img/woocommerce.png', __DIR__));
		  $array_orders_totals = array();
		  $array_orders_counts = array();
		
		  foreach ($dates as $date){
			$array_orders_totals[$date] = 0;
			$array_orders_counts[$date] = 0;
		  }
		  
		  if($total_orders > 1){
			  
			  foreach ($orders as $ctr => $value)
			  {
				  $order_id = $value->ID;
		  
				  $order = wc_get_order($order_id);
				  $order_total = $order->get_total();
				  $order_date = date("d/m/Y",strtotime($order->get_date_created()));
		  
				  $array_orders_totals[$order_date] += $order_total;
				  $array_orders_counts[$order_date] += 1;
		  
				  $total_sales += $order_total;
			  }
		  
			  $temparray = array();
			  foreach($array_orders_totals as $item){
				array_push($temparray,$item);
			  }
			  $array_orders_totals = $temparray;
		  
			  $temparray = array();
			  foreach($array_orders_counts as $item){
				array_push($temparray,$item);
			  }
			  $array_orders_counts = $temparray;
		  
			  $json_orders_value = json_encode($array_orders_totals);
			  $json_orders_count = json_encode($array_orders_counts);
			  
			  $average_order = $total_sales / $total_orders;
			  
		  }
		  
		
		  ?>
		
			<div class="uk-card-body">
			  <?php
			  if($total_orders < 1){
			  ?>
			  
				  <p><?php _e("No sales data available for dates","admin2020");?> </p>
			  
			  <?php
			  } else {
			  ?>
			  
				  <div class="uk-h2 uk-text-primary uk-margin-remove">
					  <?php echo$curreny_symbol.number_format($average_order)?>
				  </div>
				  <span class="uk-text-meta"><?php echo __('For given date period','admin2020')?></span>
				  
			  
			  <?php } ?>
			</div>
		
		  <?php
		}
		
		
		
		/**
		* Creates total sales chart card
		* @since 1.4
		*/
		
		public function recent_orders($startdate = null, $enddate = null){
		
		  $enddate = date('Y-m-d');
	      $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		  ///GET ARRAY OF DATES
		  $dates = $this->utils->date_array($startdate,$enddate);
		  $json_dates = json_encode($dates);
		  
		  $earlier = new DateTime($startdate);
		  $later = new DateTime($enddate);
		  $days = $later->diff($earlier)->format("%a");
		
		  global  $woocommerce;
		  $curreny_symbol = get_woocommerce_currency_symbol();
		
		  $allorders = $this->get_orders($startdate,$enddate);
		
		  $total = 0;
		
		  $orders = $allorders->posts;
		  $total_orders = $allorders->post_count;
		  
		
		  ?>
		
		
			<div class="uk-card-body">
			  <?php if($total_orders < 1){ ?>
			  
				  <p><?php _e("No sales data available for dates","admin2020");?> </p>
			  
			  <?php } else { ?>
			  
			  		<table class="uk-table uk-table-justify uk-table-small uk-table-middle">
						<thead>
							<th><?php _e('Order','admin2020')?></th>
							<th><?php _e('Customer','admin2020')?></th>
							<th><?php _e('Status','admin2020')?></th>
							<th><?php _e('Value','admin2020')?></th>
						</thead>
						<tbody>
						
			  
						<?php foreach ($orders as $ctr => $value) {
						
							$order_id = $value->ID;
							$order = wc_get_order($order_id);
							$order_total = $order->get_total();
							$status = $order->get_status(); 
							$user = $order->get_user();
							$username = $user->first_name;
							$surname = $user->last_name;
							$ordernum = $order->get_order_number();
							$editurl = get_edit_post_link($order_id);
							$userlink = get_edit_user_link($user->ID);
							?>
						
							<tr>
								<td>
									<a uk-tooltip="title:<?php _e('View Order','admin2020')?>" href="<?php echo $editurl?>">#<?php echo $ordernum?></a>
								</td>
								<td>
									<a href="<?php echo $userlink?>" uk-tooltip="title:<?php _e('View Customer','admin2020')?>"><?php echo $username.' '.$surname?></a>
								</td>
								<td><span class="uk-label <?php echo $status?>" style="font-size: 12px;text-transform: none;"><?php echo $status ?></span></td>
								<td><?php echo $curreny_symbol.$order_total ?></td>
							</tr>
						
						
						<?php } ?> 
						
						</tbody>
					</table> 
					
			<?php } ?>  
			</div>
		
		
		  <?php
		}
			
			
		/**
		* Creates total sales chart card
		* @since 1.4
		*/
		
		public function products_by_sold($startdate = null, $enddate = null){
		
		  if($startdate == null && $enddate == null){
			  
			$enddate = date('Y-m-d');
			$startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		  
		  }
		
		  $products = $this->best_selling_products(8,$startdate,$enddate);
		  global  $woocommerce;
		  $curreny_symbol = get_woocommerce_currency_symbol();
		
		  ?>
		
		
			<div class="uk-card-body">
			  
			<?php
			if(count($products) > 0) {
				?>
				<table class="uk-table uk-table-justify uk-table-small uk-table-middle">
					  <thead>
						  <th></th>
						  <th><?php _e('Product','admin2020')?></th>
						  <th><?php _e('Sold','admin2020')?></th>
						  <th><?php _e('Value','admin2020')?></th>
					  </thead>
					  <tbody>
						<?php
						foreach($products as $values){
						
							$product_id = $values->id;
							$img = get_the_post_thumbnail_url($product_id);
							$title = get_the_title($product_id);
							$link = get_edit_post_link($product_id);
							$total_sales = get_post_meta( $product_id, 'total_sales', true);
							$product = wc_get_product( $product_id );
							$price = $product->get_price();
							$total_price = $price * $total_sales;
							?>
							<tr>
								<td>
									<?php if ($img){ ?>
										<img alt="<?php echo $title?>" src="<?php echo $img?>" style="height:27px;width:27px;border-radius: 4px;">
									<?php } else { ?>
										<span class="a2020_round_icon" uk-icon="icon: tag"></span>
									<?php } ?>
								</td>
								<td><a href="<?php echo $link ?>"><?php echo $title ?></a></td>
								<td><?php echo get_post_meta( $product_id, 'total_sales', true) ?></td>
								<td><?php echo $curreny_symbol.number_format($total_price)?></td>
							</tr>
							
							<?php
						
						};
						?>
					  </tbody>
				</table> <?php
				
				
			} else {
				?> <p><?php _e("No sales data available for dates","admin2020");?> </p><?php
			}; ?>
			</div>
		
		
		  <?php
		}
	 
	
	public function best_selling_products( $limit = '-1', $startdate, $enddate){
		global $wpdb;
	
		$limit_clause = intval($limit) <= 0 ? '' : 'LIMIT '. intval($limit);
		
	
		return (array) $wpdb->get_results("
			SELECT p.ID as id, COUNT(oim2.meta_value) as count
			FROM {$wpdb->prefix}posts p
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
				ON p.ID = oim.meta_value
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim2
				ON oim.order_item_id = oim2.order_item_id
			INNER JOIN {$wpdb->prefix}woocommerce_order_items oi
				ON oim.order_item_id = oi.order_item_id
			INNER JOIN {$wpdb->prefix}posts as o
				ON o.ID = oi.order_id
			WHERE p.post_type = 'product'
			AND p.post_status = 'publish'
			AND o.post_status IN ('wc-processing','wc-completed','wc-pending','wc-on-hold')
			AND o.post_date >= '$startdate'
			AND o.post_date <= '$enddate'
			AND oim.meta_key = '_product_id'
			AND oim2.meta_key = '_qty'
			GROUP BY p.ID
			ORDER BY COUNT(oim2.meta_value) + 0 DESC
			$limit_clause
		");
	}
	
}
