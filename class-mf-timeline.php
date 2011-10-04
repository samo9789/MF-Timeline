<?php
class MF_Timeline {
	public $years = array();
	public $pluginPath;
	public $pluginUrl;
	
	public function __construct() {
		$this->pluginPath = dirname( __FILE__ );
		$this->pluginUrl = WP_PLUGIN_URL . '/mf-timeline';
		
		// Action Hooks
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_init', array( &$this, 'mf_timeline_admin_init') );
		add_action( 'wp_print_styles', array( &$this, 'mf_timeline_styles' ) );
		add_action( 'init', array( &$this, 'mf_timeline_js' ) );
		
		// Shortcode
		add_shortcode( 'mf_timeline', array( &$this, 'shortcode' ) );  
	}
	
	/**
	 * MF Timeline Admin Init
	 * Initiate the plugin and its settings.
	 *
	 * @return void
	 * @author Matt Fairbrass
	 **/
	public function mf_timeline_admin_init() {
		register_setting( 'mf_timeline_settings', 'mf_timeline', array( &$this, 'validate_settings' ) );
		wp_register_style( 'mf_timeline_admin_styles', plugins_url( 'styles/admin.min.css', __FILE__ ) );
		wp_enqueue_style( 'mf_timeline_admin_styles' );
	}
	
	/**
	 * MF Timeline Styles
	 * Enqueue the plugin stylesheets to style the timeline output.
	 *
	 * @return void
	 * @author Matt Fairbrass
	 **/
	function mf_timeline_styles() {
		wp_register_style( 'mf_timeline_styles', plugins_url( 'styles/style.min.css' , __FILE__ ) );
        wp_enqueue_style( 'mf_timeline_styles' );
	}
	
	/**
	 * MF Timeline JS
	 * Register and enqueue the JS files used by the plugin
	 *
	 * @return void
	 * @author Matt Fairbrass
	 **/
	function mf_timeline_js() {
		$options = get_option( 'mf_timeline' );
		
		if( !is_admin() && $options['options']['timeline_nav'] == 1 ) {
			wp_register_script( 'mf_timeline_afterscroll', plugins_url( 'scripts/js/jquery.afterscroll.min.js', __FILE__), array( 'jquery' ) );
			wp_enqueue_script( 'mf_timeline_afterscroll' );
			
			wp_register_script( 'mf_timeline_stickyfloat', plugins_url( 'scripts/js/jquery.stickyfloat.min.js', __FILE__ ), array( 'jquery' ) );
			wp_enqueue_script( 'mf_timeline_stickyfloat' );
			
			wp_register_script( 'mf_timeline', plugins_url( 'scripts/js/jquery.mf_timeline.min.js', __FILE__ ), array( 'jquery', 'mf_timeline_afterscroll', 'mf_timeline_stickyfloat' ) );
			wp_enqueue_script( 'mf_timeline' );
		}
	}
	
	
	/**
	 * Admin Menu
	 * Set up the plugin options page.
	 *
	 * @return void
	 * @author Matt Fairbrass
	 **/
	public function admin_menu() {  
		add_options_page( 'MF Timeline Settings', 'MF-Timeline', 'manage_options', 'mf-timeline', array( &$this, 'get_plugin_options_page' ) );
	}
	
	/**
	 * Shortcode
	 * Create a shortcode that can be used within Wordpress posts to output the MF-Timeline.
	 *
	 * @return string the html output of the timeline
	 * @author Matt Fairbrass
	 **/
	public function shortcode() {
		return $this->get_timeline();
	}
	
	/**
	 * Validate Settings
	 * Validates the data being submitted from the MF-Timeline Settings
	 *
	 * @param $input array the data to validate
	 *
	 * @return $input array the sanitised data.
	 * @author Matt Fairbrass
	 **/
	public function validate_settings($input) {
		$valid_input = array();
		
		/* General Settings */
		$valid_input['options']['timeline_nav'] = ( $input['options']['timeline_nav'] == 1 ? 1 : 0 );
		
		/* Wordpress */
		if( !empty( $input['options']['wp']['content'] ) || !empty( $input['options']['wp']['filter'] ) ) {
			// Content
			foreach( $input['options']['wp']['content'] as $key=>$value ) {
				$valid_input['options']['wp']['content'][$key] = ( $value == 1 ? 1 : 0 );
			}
			
			// Filters
			foreach( $input['options']['wp']['filter'] as $filter=>$value ) {
				foreach( $value as $id=>$val ) {
					switch( $filter ) {
						case 'taxonomy' :
							$valid_input['options']['wp']['filter']['taxonomy'][$id] = ( $val == 1 ? 1 : 0 );
						break;
					
						default :
							$valid_input['options']['wp']['filter'][$filter][$id] = wp_filter_nohtml_kses( $val );
						break;
					}
				}
			}
		}
		
		/* Twitter */
		if( !empty( $input['options']['twitter']['content']) || !empty($input['options']['twitter']['filter'] ) ) {
			// Content
			foreach( $input['options']['twitter']['content'] as $key=>$value ) {
				switch( $key ) {
					case 'username' :
						$valid_input['options']['twitter']['content']['username'] = wp_filter_nohtml_kses( str_replace( '@', '', $value ) );
					break;
					
					case 'timeline' :
						$valid_options = array( '1', '2' );
						$valid_input['options']['twitter']['content']['timeline'] = ( in_array( $value, $valid_options ) == true ? $value : null );
					break;
					
					default :
						$valid_input['options']['twitter']['content'][$key] = wp_filter_nohtml_kses( $value );
					break;
				}
			}
			
			// Filters
			foreach( $input['options']['twitter']['filter'] as $filter=>$value ) {
				switch($filter) {
					case 'tags' :
						$valid_input['options']['twitter']['filter']['tags'] = wp_filter_nohtml_kses( str_replace('#', '', $value ) );
					break;
				}
			}
		}
		
		return $valid_input;
	}
	
	/**
	 * Get Plugin Settings Page
	 * Output the options for the plugin settings page.
	 *
	 * @return void
	 * @author Matt Fairbrass
	 **/
	public function get_plugin_options_page() {
		if( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		if( $_GET['tab'] == 'settings' || !isset($_GET['tab'] ) ) {
			$settings_active = 'nav-tab-active';
		}
		else if( $_GET['tab'] == 'stories' ) {
			$stories_active = 'nav-tab-active';
		}
	?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div><h2>MF-Timeline Options</h2>
			
			<div id="nav">
				<h3 class="themes-php">
					<a class="nav-tab <?php echo $settings_active;?>" href="?page=mf-timeline&amp;tab=settings">Settings</a>
				</h3>
			</div>
			
			<?php if( $_GET['tab'] == 'settings' || !isset($_GET['tab'] ) ) :?>
				<p>Configure the default MF-Timeline settings below. You can override these settings when calling the shortcode in your posts or the function in your templates.</p>
				<form action="options.php" method="POST">
					<?php 
						settings_fields( 'mf_timeline_settings' );
						$options = get_option( 'mf_timeline' );
					?>
					<h3>General Settings</h3>
					<fieldset>
						<ul>
							<li>
								<label for="mf_timeline[options][timeline_nav]"><strong>Timeline Years Menu:</strong></label><br/>
								<select name="mf_timeline[options][timeline_nav]" id="mf_timeline[options][timeline_nav]" style="width: 100px;">
									<option value="1" <?php selected( '1', $options['options']['timeline_nav'] ); ?>>Show</option>
									<option value="0" <?php selected( '0', $options['options']['timeline_nav'] ); ?>>Hide</option>
								</select><br/>
								<span class="description">Appears fixed next to the timeline allowing the user to navigate past events more easily.</span>
							</li>
						</ul>
					</fieldset>
					<h3>Wordpress Content</h3>
					<fieldset>
						<ul>
							<li>
								<h4>Include content from:</h4>
								<?php foreach( get_post_types( '', 'object' ) as $key=>$post_type ) :?>
									<input type="checkbox" name="mf_timeline[options][wp][content][<?php echo $key;?>]" id="mf_timeline[options][wp][content][<?php echo $key;?>]" value="1" <?php checked( '1', $options['options']['wp']['content'][$key] ); ?> />
									<label for="mf_timeline[options][wp][content][<?php echo $key;?>]"><?php _e( $post_type->labels->name ); ?></label><br />
								<?php endforeach;?>
							</li>
							<li>
								<h4>Filter by the following taxonomies:</h4>
								<p class="description clear">Leave blank to not filter by taxonomies.</p>
								<?php global $wp_taxonomies; ?>
								<?php if ( is_array( $wp_taxonomies ) ) : ?>
									<?php foreach ( $wp_taxonomies as $tax ) :?>
										<?php if ( !in_array( $tax->name, array( 'nav_menu', 'link_category', 'podcast_format' ) ) ) : ?>
											<?php if ( !is_taxonomy_hierarchical( $tax->name ) ) : // non-hierarchical ?>
												<?php 
													$nonhierarchical .= '<p class="alignleft"><label for="mf_timeline[options][wp][filter][term][' . esc_attr($tax->name).']"><strong>' . esc_html( $tax->label ) . ': </strong></label><br />';
													$nonhierarchical .= '<input type="text" name="mf_timeline[options][wp][filter][term][' . esc_attr( $tax->name ) . ']" id="mf_timeline[options][wp][filter][term][' . esc_attr( $tax->name ) . ']" class="widefloat" style="margin-right: 2em;" value="' . $options['options']['wp']['filter']['term'][$tax->name] . '" /></p>';
												?>
											<?php else: // hierarchical ?>
												 <div class="categorychecklistbox">
													<label><strong><?php echo $tax->label;?></strong><br />
										        	<ul class="categorychecklist">
											     		<?php $terms = get_terms( $tax->name );?>
														
														<?php foreach( $terms as $term ) :?>
															<li>
																<input type="checkbox" name="mf_timeline[options][wp][filter][taxonomy][<?php echo $term->term_id;?>]" id="mf_timeline[options][wp][filter][taxonomy][<?php echo $term->term_id;?>]" value="1" <?php checked('1', $options['options']['wp']['filter']['taxonomy'][$term->term_id]); ?> />
																<label for="mf_timeline[options][wp][filter][taxonomy][<?php echo esc_html($term->term_id);?>]"><?php echo $term->name;?></label>
															</li>
														<?php endforeach;?>
													</ul>  
												</div>
											<?php endif;?>
										<?php endif;?>
									<?php endforeach; ?>
								<?php endif; ?>
							</li>
							<li class="clear">
								<br /><h4>Filter by the following terms:</h4>
								<p class="description">Separate terms with commas. Leave blank to not filter by terms.</p>
								<?php echo $nonhierarchical;?>
							</li>
						</ul>
					</fieldset>
				
					<h3>Twitter Content</h3>
					<fieldset>
						<ul>
							<li>
								<label for="mf_timeline[options][twitter][content][username]"><strong>Twitter Username:</strong></label><br/>
								<input type="text" name="mf_timeline[options][twitter][content][username]" id="mf_timeline[options][twitter][content][username]" value="<?php echo $options['options']['twitter']['content']['username'];?>" />
							</li>
							<li>
								<label for="mf_timeline[options][twitter][filter][tags]"><strong>Filter by the following hashtags:</strong></label><br/>
								<input type="text" name="mf_timeline[options][twitter][filter][tags]" id="mf_timeline[options][twitter][filter][tags]" value="<?php echo $options['options']['twitter']['filter']['tags'];?>" />
								<span class="description">Separate tags with commas. Leave blank to not filter by any tags.</span>
							</li>
						</ul>
					</fieldset>
					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button-primary" value="Save Settings">
					</p>
				</form>
			<?php elseif( $_GET['tab'] == 'stories' ) :?>
				<p>Timeline stories enable you to add content to the timeline without the need to create individual posts. You can manage all your timeline stores from this area.</p>
			<?php endif;?>
		</div>
		
	<?php
	}
	
	/**
	 * Get Timeline Posts
	 * Returns an array of wordpress posts organised by year filtered by taxonomies.
	 *
	 * @return $posts array the posts returned by the query
	 * @author Matt Fairbrass
	 **/
	protected function get_content_posts() {
		global $wpdb;
		$options = get_option( 'mf_timeline' );
		
		if( isset( $options['options']['wp']['content']) && !empty($options['options']['wp']['content'] ) ) {			
			/**
			 * // HACK
			 * Wordpress $wpdb->prepare() doesn't handle passing multiple arrays to its values. So we have to merge them.
			 * It is also unable to determine how many placeholders are needed for handling array values, so we have to work out how many we need.
			 * To be blunt, this is crap and needs to be looked at by the Wordpress dev team.
			 **/
			$post_types = array_keys( $options['options']['wp']['content'] );
			
			foreach( $post_types as $post_type ) {
				$post_types_escape[] = '%s';
			}
			
			$sql = "SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_title, {$wpdb->posts}.post_content, {$wpdb->posts}.post_excerpt, {$wpdb->posts}.post_date, {$wpdb->posts}.post_author, {$wpdb->terms}.term_id 
				FROM `{$wpdb->posts}` 
				INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id) 
				INNER JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
				INNER JOIN {$wpdb->terms} ON ({$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id)
				WHERE {$wpdb->posts}.post_status = 'publish' 
				AND {$wpdb->posts}.post_type IN (".implode(',', $post_types_escape).")";
			
			// Check if we are filtering the post types by hireachrical taxonomy terms
			if( isset( $options['options']['wp']['filter']['taxonomy'] ) && !empty( $options['options']['wp']['filter']['taxonomy'] ) ) {
				$term_ids = array_keys( $options['options']['wp']['filter']['taxonomy'] );
				
				foreach( $term_ids as $term_id ) {
					$term_ids_escape[] = '%d';
				}
			}
			
			// Check if we are filter the post types by non-hireachrical taxonomy terms
			if( isset($options['options']['wp']['filter']['term'] ) && !empty( $options['options']['wp']['filter']['term'] ) ) {
				foreach( $options['options']['wp']['filter']['term'] as $taxonomy_name=>$terms ) {
					foreach( explode( ',', $terms ) as $term ) {
						$the_term = get_term_by( 'slug', str_replace( ' ', '-', trim( $term ) ), $taxonomy_name );
						
						if( $the_term != false ) {
							$term_ids[] = $the_term->term_id;
							$term_ids_escape[] = '%d';
						}
					}	
				}
			}
			
			// Append the filters to the SQL statement
			if( isset( $term_ids_escape ) && !empty( $term_ids_escape ) ) {
				$sql .= "AND {$wpdb->terms}.term_id IN (" . implode( ',', $term_ids_escape ) . ")";
			}
			
			$sql .= "GROUP BY {$wpdb->posts}.ID";
			
			$query = $wpdb->prepare( $sql, array_merge( (array) $post_types, (array) $term_ids ) );
			$results = $wpdb->get_results( $query, 'ARRAY_A' );
			
			foreach($results as $post) {
				$year = date( 'Y', strtotime( $post['post_date'] ) );
				$post['source'] = 'wp';
				$posts[$year][] = $post;
			}
		
			return $posts;
		}
	}
	
	/**
	 * Get Content Tweets
	 * Returns an array of tweets organised by year filtered by hashtags.
	 *
	 * @return $tweets array the tweets returned by the query
	 * @author Matt Fairbrass
	 **/
	protected function get_content_tweets() {
		global $wpdb;
		$options = get_option( 'mf_timeline' );
		
		if( isset($options['options']['twitter']['content']['username'] ) && !empty( $options['options']['twitter']['content']['username'] ) ) {

			$user = $options['options']['twitter']['content']['username'];
			
			if( !empty($options['options']['twitter']['filter']['tags'] ) ) {
				$hashtags = explode( ',', $options['options']['twitter']['filter']['tags'] );
				
				
				foreach( $hashtags as $key=>$hashtag ) {
					$hashtags[$key] = urlencode( '#' . $hashtag );
				}
				
				$query = implode( '+OR+', $hashtags ) . "+from:{$user}&amp;rpp=100";
			}
			else {
				$query = "from:{$user}&amp;rpp=100";
			}
			
			
			$url = "http://search.twitter.com/search.json?q={$query}";
			$json_file = file_get_contents( $url, 0, null, null );
			$json = json_decode( $json_file );
			
			$tweets = array();	
				
			if( is_object($json) && isset( $json->results ) ) {
				foreach( $json->results as $result ) {
					$year = date( 'Y', strtotime( $result->created_at ) );
					
					$row['post_content'] = (string) $result->text;
					$row['post_date'] = (string) $result->created_at;
					$row['post_author'] = (string) $result->from_user;
					$row['profile_image'] = (string) $result->profile_image_url;
					$row['source'] = 'twitter';
					
					$tweets[$year][] = $row;
				}
			}
			
			return $tweets;
		}
	}
	
	/**
	 * Get Timeline Events
	 * Returns an array of events organised by year.
	 *
	 * @uses get_content_posts()
	 * @uses get_content_tweets()
	 *
	 * @return $events array the events merged returned by the queries.
	 * @author Matt Fairbrass
	 **/
	protected function get_timeline_events() {
		$posts = $this->get_content_posts();
		$tweets = $this->get_content_tweets();
		
		$events = array();
		
		// Create an array of years based on the years avaialble from the content sources
		$years = array_unique( array_merge( (array) array_keys( $tweets ), (array) array_keys( $posts ) ) );		
		
		if( !empty( $posts ) && !empty( $tweets ) ) {
			foreach( $years as $year ) {
			    $events[$year] = array_merge( (array) $posts[$year], (array) $tweets[$year] );
			}
		}
		else if( !empty( $posts ) ) {
			$events = $posts;
		}
		else if( !empty( $tweets ) ) {
			$events = $tweets;
		}
		else {
			return null;
		}
		
		foreach( $events as $year=>&$event ) {
			usort( $event, array( &$this, 'sort_events_by_date' ) );
		}
		
		krsort( $events ); // Sort the years numeric
		
		return $events;
	}
	
	/**
	 * Sort Events By Date
	 * Sorts the combined events array by date in ascending order.
	 *
	 * @return int the calculation.
	 * @author Matt Fairbrass
	 **/
	public function sort_events_by_date( $elem1, $elem2 ) {
		return strtotime( $elem2['post_date'] ) - strtotime( $elem1['post_date'] );
	}
	
	/**
	 * Get Timeline
	 * Output the timeline html to the page. This function can be called either via a shortcode or within a theme's template page.
	 *
	 * @return void
	 * @author Matt Fairbrass
	 **/
	public function get_timeline() {
		$events = $this->get_timeline_events();
		
		$html = '<div class="timeline">';
			$html .= '<a href="#" class="timeline_spine"></a>';
			
			foreach( $events as $year=>$timeline_events ) {
				$html .= '<div class="section" id="' . $year . '">';
					$html .= '<div class="title">';
						$html .= '<a href="#">' . $year . '</a>';
					$html .= '</div>';
					
					$html .= '<ol class="events">';
						foreach( $timeline_events as $event ) {
							$is_featured = get_post_meta( $event['ID'], 'mf_timeline_featured', true );
							
							if( $is_featured == true ) {
								$excerpt_length = 700;
								$class = ' featured';
							}
							else {
								$excerpt_length = 300;
								$class = null;
							}
							
							$html .= '<li class="event ' . $event['source'] . $class . '">';
								$html .= '<div class="event_pointer"></div>';
								
								$html .= '<div class="event_container">';
									$html .= '<div class="event_title">';
										switch( $event['source'] ) {
											case 'wp' :
												$html .= '<h3><a href="' . get_permalink( $event['ID'] ) . '">' . $event['post_title'] . '</a></h3>';
											break;

											case 'twitter' :
												$html .= '<img src="' . $event['profile_image'] . '" alt="' . $event['post_author'] . '" width="50" height="50" class="profile_image" />';
												$html .= '<h3><a href="http://www.twitter.com/' . $event['post_author'] . '/">@' . $event['post_author'] . '</a></h3>';
											break;
										}
										
										$html .= '<span class="subtitle">';
											$html .= $this->format_date( $event['post_date'] );
										$html .= '</span>';
									$html .= '</div>';
									
									$html .= '<div class="event_content">';
										if($event['source'] == 'wp') {
											$html .= apply_filters( 'the_content', $this->format_excerpt( $event['post_content'], $excerpt_length, $event['post_excerpt'] ) );
										}
										else {
											$html .= apply_filters( 'the_content', $this->format_text( $event['post_content'] ) );
										}
									$html .= '</div>';
								$html .= '</div>';
							$html .= '</li>';
						}
					$html .= '</ol>';
					
				$html .= '</div>';
			}
			
			$html .= $this->get_timeline_nav( array_keys( $events ) );
		$html .= '</div>';
			
		return $html;
	}
	
	/**
	 * Get Timeline Nav
	 * Outputs the timeline navigation menu and enqueues the the Javascript
	 *
	 * @param $years array an array of years
	 *
	 * @return void
	 * @author Matt Fairbrass
	 **/
	function get_timeline_nav($years) {
		$options = get_option( 'mf_timeline' );
		
		if( $options['options']['timeline_nav'] == 1 ) {
			$html = '<ol class="timeline_nav">';
				foreach( $years as $year ) {
					$html .= '<li id="menu_year_' . $year . '"><a href="#' . $year . '">' . $year . '</a></li>';
				}
			$html .= '</ol>';
			
			return $html;
		}
	}
	
	/**
	 * Format Date
	 * Convert a given date to (x) minutes/hours/days/weeks ago/from now or the date if outside of difference range.
	 *
	 * @param $date string the date to convert
	 *
	 * @return $difference $periods[$j] {$tense}
	 * @author Matt Fairbrass
	 **/
	protected function format_date($date) {
	    if( empty( $date ) ) {
	        return false;
	    }

	    $periods = array( 'second', 'minute', 'hour', 'day', 'week', 'date' );
	    $lengths = array( '60', '60','24','7', '2', '12' );

	    $now = time();
	    $unix_date = strtotime( $date );

	    // check validity of date
	    if( empty( $unix_date ) ) {   
	        return 'Bad date';
	    }

	    // is it future date or past date
	    if( $now > $unix_date ) {   
	        $difference = $now - $unix_date;
	        $tense = 'ago';

	    } else {
	        $difference = $unix_date - $now;
	        $tense = 'from now';
	    }

	    for( $j = 0; $difference >= $lengths[$j] && $j < count( $lengths ) - 1; $j++ ) {
	        $difference /= $lengths[$j];
	    }

	    $difference = round( $difference );

	    if( $difference != 1 ) {
	        $periods[$j].= 's';
	    }
		
		if( $j == count( $lengths ) -1 ) {
			return date( 'd F Y', $unix_date );
		}
		else {
			return "$difference $periods[$j] {$tense}";
		}
	}
	
	/**
	 * Format Excerpt
	 * Allows us to format the content as an except outside of the loop
	 *
	 * @param $text string the text to format - usually the post content
	* @param $length int the number of characters to trim to. Set to 140 by default so full tweet content is shown on the timeline.
	 * @param $excerpt the except of the post
	 *
	 * @return $text string the formatted text.
	 * @author Matt Fairbrass
	 **/
	function format_excerpt( $text, $length = 140, $excerpt ) {
	    if ( $excerpt ) return $excerpt;

	    $text = strip_shortcodes( $text );

	    $text = apply_filters( 'the_content', $text );
	    $text = str_replace( ']]>', ']]&gt;', $text );
	    $text = strip_tags( $text );
	    $excerpt_length = apply_filters( 'excerpt_length', $length );
	    $excerpt_more = apply_filters( 'excerpt_more', ' ' . '[...]' );
	    $words = preg_split( "/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY );
	    
		if ( count( $words ) > $excerpt_length ) {
	    	array_pop( $words );
	        $text = implode( ' ', $words );
	        $text = $text . $excerpt_more;
	    } 
		else {
			$text = implode( ' ', $words );
	    }
		
		$text = $this->format_text( $text );
		
	    return apply_filters( 'wp_trim_excerpt', $text, $raw_excerpt );
	}
	
	
	/**
	 * Format Text
	 * Calls format_text_to_links and format_text_to_twitter
	 * 
	 * @param $text string the text to format
	 *
	 * @see format_text_to_links()
	 * @see format_text_to_twitter()
	 *
	 * @return $text string the formatted text
	 * @author Matt Fairbrass
	 **/
	function format_text( $text ) {
		$text = $this->format_text_to_links($text);
		$text = $this->format_text_to_twitter($text);
		
		return $text;
	}
	
	/**
	 * Format Text To Links
	 * Transforms text urls into valid html hyperlinks
	 * 
	 * @param $text string the text to format
	 *
	 * @return $text string the formatted text
	 * @author Matt Fairbrass
	 **/
	function format_text_to_links( $text ) {
		if(empty($text)) {
			return null;
		}
		
	    $text = preg_replace( "/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is", "$1$2<a href=\"$3\" >$3</a>", $text );
	    $text = preg_replace( "/(^|[\n ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r<]*)/is", "$1$2<a href=\"http://$3\" >$3</a>", $text );
	    $text = preg_replace( "/(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"mailto:$2@$3\">$2@$3</a>", $text );
	    
		return $text;
	}
	
	/**
	 * Format Text To Twitter
	 * Transforms text to twitter users (@someone) links and hastag (#something) to links
	 * 
	 * @param $text string the text to format
	 *
	 * @return $text string the formatted text
	 * @author Matt Fairbrass
	 **/
	function format_text_to_twitter($text) {
		if( empty( $text ) ) {
			return null;
		}
		
	    $text = preg_replace( "/@(\w+)/", '<a href="http://www.twitter.com/$1" target="_blank">@$1</a>', $text );
		$text = preg_replace( "/\#(\w+)/", '<a href="http://search.twitter.com/search?q=$1" target="_blank">#$1</a>', $text );
		
		return $text;
	}
}
?>
