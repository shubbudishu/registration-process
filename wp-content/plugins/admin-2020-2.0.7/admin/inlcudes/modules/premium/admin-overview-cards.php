<?php
if (!defined('ABSPATH')) {
	exit();
}
class Admin_2020_overview_cards {
	
	public function start(){
		
		add_filter('admin2020_register_dash_card', array($this,'register_default_cards'));
		
	}
	
	/**
	* Registers standard cards
	* @since 1.4
	*/
	
	public function register_default_cards($dashitems){
		
		if(!is_array($dashitems)){
			$dashitems = array();
		}
		
		$admin2020_cards = array(
			array('total_posts',__('Total Posts','admin2020'),'General'),
			array('total_pages',__('Total Pages','admin2020'),'General'),
			array('total_comments',__('Total Comments','admin2020'),'General'),
			array('recent_comments',__('Recent Comments','admin2020'),'General'),
			array('system_info',__('System Info','admin2020'),'General'),
			array('recent_posts',__('Recently Published','admin2020'),'General'),
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
	* Builds the total posts cards
	* @since 1.4
	*/
	
	public function total_posts($startdate = null, $enddate = null){
	
		if($startdate != null && $enddate != null){
		
		  $args = array(
		  'date_query' => array(
			  array(
				  'after'     => $startdate,
				  'before'    => $enddate,
				  'inclusive' => true,
				  ),
			  ),
		  );
		
		  $query = new WP_Query( $args );
		  $totalposts = $query->found_posts;
		
		} else {
		
		  $tempcount = wp_count_posts("post");
		  $totalposts = $tempcount->publish;
		  
		  
		  $enddate = date('Y-m-d');
		  $startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$earlier = new DateTime($startdate);
		$later = new DateTime($enddate);
		
		$days = $later->diff($earlier)->format("%a");
		
		?>
				  
		<div class="uk-card-body">
		  <div class="uk-grid-small" uk-grid>
			  <div class="uk-width-auto">
				  	<span class="dashicons dashicons-admin-post a2020_overview_icon"></span>
			  </div>
			  
			  <div class="uk-width-auto">
				  <div class="uk-h3 uk-margin-remove-bottom uk-margin-right"> <?php echo number_format($totalposts)?></div>
				  <div class="uk-text-meta"><?php echo __('In the last ','admin2020') .  $days . ' ' . __('days','admin2020') ?></div>
			  </div>
		  </div>
		</div>
		<?php
	}
	
	/**
	* Builds the total pages cards
	* @since 1.4
	*/
	
	public function total_pages($startdate = null, $enddate = null){
	
		if($startdate != null && $enddate != null){
		
			$args = array(
			'numberposts' => -1,
			'post_type'   => 'page',
			'date_query' => array(
			  array(
				  'after'     => $startdate,
				  'before'    => $enddate,
				  'inclusive' => true,
				  ),
			  ),
			);
		
		} else {
		
			$args = array(
				'numberposts' => -1,
				'post_type'   => 'page'
			);
			
			
			$enddate = date('Y-m-d');
			$startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
		
		}
		
		$allpages = get_posts( $args );
		
		$earlier = new DateTime($startdate);
		$later = new DateTime($enddate);
		
		$days = $later->diff($earlier)->format("%a");
		
		?>
				  
				  
		<div class="uk-card-body">
		  <div class="uk-grid-small" uk-grid>
			  <div class="uk-width-auto">
			  	<span class="dashicons dashicons-admin-page a2020_overview_icon"></span>
			  </div>
			  
			  <div class="uk-width-auto">
				  <div class="uk-h3 uk-margin-remove-bottom uk-margin-right"> <?php echo number_format(count($allpages))?></div>
				  <div class="uk-text-meta"><?php echo __('In the last ','admin2020') .  $days . ' ' . __('days','admin2020') ?></div>
			  </div>
		  </div>
		</div>
		<?php
	}
	
	/**
	* Builds the total comments cards
	* @since 1.4
	*/
	
	public function total_comments($startdate = null, $enddate = null){
	
		if($startdate != null && $enddate != null){
		
			$args = array(
				'date_query' => array(
					'after' => $startdate,
					'before' => $enddate,
					'inclusive' => true,
				),
			);
		
		} else {
			
			
			$enddate = date('Y-m-d');
			$startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
			
			$args = array(
				'date_query' => array(
					'after' => $startdate,
					'before' => $enddate,
					'inclusive' => true,
				),
			);
		
		}
		
		$comments = get_comments( $args );
		
		$earlier = new DateTime($startdate);
		$later = new DateTime($enddate);
		
		$days = $later->diff($earlier)->format("%a");
		
		?>
				  
		<div class="uk-card-body">
		  <div class="uk-grid-small" uk-grid>
			  <div class="uk-width-auto">
				  <span class="dashicons dashicons-admin-comments a2020_overview_icon"></span>
			  </div>
			  
			  <div class="uk-width-auto">
				  <div class="uk-h3 uk-margin-remove-bottom uk-margin-right"> <?php echo number_format(count($comments))?></div>
				  <div class="uk-text-meta"><?php echo __('In the last ','admin2020') .  $days . ' ' . __('days','admin2020') ?></div>
			  </div>
		  </div>
		</div>
		<?php
	}
	
	/**
	* Builds the recent comments card
	* @since 1.4
	*/
	
	public function recent_comments($startdate = null, $enddate = null){
	
		if($startdate != null && $enddate != null){
		
			$args = array(
				'number'  => '5',
				'date_query' => array(
					'after' => $startdate,
					'before' => $enddate,
					'inclusive' => true,
				),
			);
		
		} else {
			
			
			$enddate = date('Y-m-d');
			$startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
			
			$args = array(
				'number'  => '5',
				'date_query' => array(
					'after' => $startdate,
					'before' => $enddate,
					'inclusive' => true,
				),
			);
		
		}
		
		$comments = get_comments( $args );
		
		$earlier = new DateTime($startdate);
		$later = new DateTime($enddate);
		
		$days = $later->diff($earlier)->format("%a");
		
		?>
				  
		<?php if(count($comments) < 1){ ?>
		
		<div class="uk-card-body">
		<p><?php _e('No comments for date range','admin2020') ?></p>
		</div>
		
		<?php } else { ?>
		
		<div class="uk-card-body">
		<table class="uk-table uk-table-small">
		  
		  <tbody>
			  
			  
			  <?php foreach ($comments as $comment){ 
				  
				  $comment_date = get_comment_date( 'Y-m-y', $comment->comment_ID );
				  $string = '';
				  
				  if($comment_date != date('Y-m-d')){
					  $string = __('ago','admin2020');
				  } 
			  
			  	  $commentdate = human_time_diff( get_comment_date( 'U', $comment->comment_ID ),current_time( 'timestamp' ) ) . ' ' . $string;
				  $author = $comment->comment_author;
				  $user = get_user_by( 'login', $author );
				  $thepostid = $comment->comment_post_ID;
				  $img = '';
				  $commentlink = get_comment_link($comment);
				  
				  if (isset($user->ID)){
					$img = get_avatar_url($user->ID);
				  } else {
					  
					  if (strpos($author, ' ') !== false) {
							$parts = str_split($author,1);
							$parts = explode(" ", $author);
							$first = str_split($parts[0]);
							$first = $first[0];
							
							$name_string = $first;
							
					  } else {
						  	$parts = str_split($author,1);
							$name_string = $parts[0];
					  }
				  }
				  ?>
				  <tr>
					  <td>
						  <?php if($img != ""){ ?>
							  <img class="uk-image uk-border-circle" style="width: 35px;" src="<?php echo $img?>">
						  <?php } else { ?>
							  <span class="uk-icon-button uk-button-primary uk-text-bold uk-text-small">
								  <?php echo $name_string ?>
							  </span>
						  <?php } ?>	  
					  </td>
					  
					  <td>
						  <strong><?php echo $author ?> </strong>
						  <?php _e('on','admin2020') ?>
						  <a class="uk-link" href="<?php echo $commentlink?>"><?php echo get_the_title($thepostid)?></a>
						  <span class="uk-text-meta"><?php echo $commentdate?></span>
					  </td>
				  </tr>
			  <?php } ?>
		  </tbody>
		  
		</table>
		</div>
		
		<?php } ?>
		<?php
	}
	
	/**
	* Builds the system info card
	* @since 1.4
	*/
	
	public function system_info($startdate = null, $enddate = null){
	
		
		$wp_v = get_bloginfo( 'version' );
		$phph_v = phpversion();
		$plugins = get_plugins();
		
		?>
				  
		<div class="uk-card-body">
		  <table class="uk-table uk-table-small">
			<tbody>
				<tr>
					<td><?php _e('WordPress version','admin2020') ?></td>
					<td><?php echo $wp_v?></td>
				</tr>
				<tr>
					<td><?php _e('PHP version','admin2020') ?></td>
					<td><?php echo $phph_v?></td>
				</tr>
				<tr>
					<td><?php _e('Plugins','admin2020') ?></td>
					<td><?php echo count($plugins)?></td>
				</tr>
			</tbody>
		  </table>
		</div>
				  
		<?php
	}
	
	/**
	* Builds the recent posts card
	* @since 1.4
	*/
	public function recent_posts($startdate = null, $enddate = null){
	
		if($startdate != null && $enddate != null){
		
			$args = array(
			'numberposts' => 5,
			'post_type'   => 'post',
			'date_query' => array(
			  array(
				  'after'     => $startdate,
				  'before'    => $enddate,
				  'inclusive' => true,
				  ),
			  ),
			);
		
		} else {
			
			
			$enddate = date('Y-m-d');
			$startdate = date('Y-m-d',strtotime( $enddate . ' - 7 days'));
			
			$args = array(
			'numberposts' => 5,
			'post_type'   => 'post',
			'date_query' => array(
			  array(
				  'after'     => $startdate,
				  'before'    => $enddate,
				  'inclusive' => true,
				  ),
			  ),
			);
		
		}
		
		
		$theposts = get_posts( $args );
		
		if(count($theposts) < 1){ ?>
		  
		  <div class="uk-card-body">
			  <p><?php _e('No posts for date range','admin2020') ?></p>
		  </div>
		  
		  <?php } else { ?>
		  
		  <div class="uk-card-body">
			  <table class="uk-table uk-table-small">
				  
				  <tbody>
					  <?php foreach ($theposts as $apost){ 
						  
						  
						  	$commentdate = human_time_diff( get_the_date( 'U', $apost),current_time( 'timestamp' ) ) . ' ' . __('ago','admin2020');
					  		$author_id=$apost->post_author;
							$author_meta = get_the_author_meta( 'user_nicename' , $author_id );
						  	?>
							  <tr>
								  <td>
									  <a class="uk-link" href="<?php echo get_the_permalink($apost)?>"><?php echo get_the_title($apost)?></a><br>
									  <span><?php _e('by','admin2020') ?> </span>
									  <span class="uk-text-meta"><?php echo $author_meta ?></span>
								  </td>
								  <td class="uk-text-right">
									  <?php echo  $commentdate?>
								  </td>
							  </tr>
					  <?php } ?>
				  </tbody>
				  
			  </table>
		  </div>
		  
		  <?php } ?>
		<?php
	}
}