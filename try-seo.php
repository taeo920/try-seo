<?php

/*
Plugin Name: TRY SEO
Description: Barebones Search Engine Optimization
Version:     0.1
Author:      Weber Shandwick
Author URI:  http://www.webershandwick.com/
Text Domain: try_seo
Domain Path: /languages/
License:     GPL v2 or later
*/

if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] )
	die();

class TRY_SEO {
	var $google_title_limit = 60;
	var $google_description_limit = 300;

	public function __construct() {
		
		if( is_admin() ) {
			add_action('add_meta_boxes', array( $this, 'add_meta_boxes') );
			add_action('save_post', array( $this, 'save_seo_fields') );
			add_action('admin_enqueue_scripts', array( $this, 'add_assets_admin') );
			
			// Check for identical meta
			add_action('wp_ajax_get_identical_meta', array( $this, 'get_identical_meta') );
			add_action('wp_ajax_nopriv_get_identical_meta', array( $this, 'get_identical_meta') );
		} else {
			add_filter('pre_get_document_title', array( $this, 'seo_title' ), 10 );
			add_action('wp_head', array( $this, 'seo_description'), 10 );
			add_action('wp_head', array( $this, 'seo_keywords'), 10 );
		}
	}

	public function add_assets_admin() {
		
		$version = get_plugin_data(__FILE__);
		
		// Add Stylesheets
		wp_enqueue_style('try_seo', plugin_dir_url( __FILE__ ) . 'assets/try-seo.css', false, $version['Version'] );
		
		// Add Scripts
		wp_enqueue_script('try_seo_scripts', plugin_dir_url( __FILE__ ) . 'assets/try-seo.js', false, $version['Version'], true );
				
	}

	public function add_meta_boxes() {
		// Retrieve all post types that are publically
		$post_types = get_post_types( array('publicly_queryable' => true, 'show_ui' => true) );

		// Manually add page post type because it's not set to "publicly_queryable" by default
		$post_types['page'] = 'page';

		foreach( $post_types as $post_type ) {
			add_meta_box('try-seo', 'SEO', array( $this, 'add_seo_fields'), $post_type, 'normal', 'low');
		}
	}

	public function add_seo_fields() {
		global $post;
		$post_meta = get_post_custom( $post->ID );
		$seo_title = ( array_key_exists('seo_title', $post_meta ) ) ? $this->clean_attr( $post_meta['seo_title'][0] ) : null;
		$seo_title_default = $this->get_post_title( true ) . ' | ' . get_bloginfo('name', 'display');
		$seo_description = ( array_key_exists('seo_description', $post_meta ) ) ? $this->clean_attr( $post_meta['seo_description'][0] ): null;
		$seo_description_default = $this->get_post_description( true );
		$seo_keywords = ( array_key_exists('seo_keywords', $post_meta ) ) ? $this->clean_attr( $post_meta['seo_keywords'][0] ) : null;
		wp_nonce_field('try_seo_nonce', 'try_seo_nonce'); ?>

		<ul>
		<li>
			<label for="seo_title"><strong>Title</strong></label><br/>
			<input class="widefat" id="seo_title" type="text" name="seo_title" data-id="<?php echo $post->ID; ?>" value="<?php echo $seo_title; ?>" placeholder="<?php echo $seo_title_default; ?>" />		
			<span id="character_count_title" class="character_count"><span class="count"><?php echo $this->google_title_limit - strlen($seo_title); ?></span> <?php _e('Characters Remaining', 'try_seo'); ?></span>
		</li>
		<li>
			<label for="seo_description"><strong>Description</strong></label><br/>
			<textarea class="widefat" id="seo_description" name="seo_description" data-id="<?php echo $post->ID; ?>" style="resize:none" rows="4" placeholder="<?php echo $seo_description_default; ?>"><?php echo $seo_description; ?></textarea>
			<span id="character_count_desc" class="character_count"><span class="count"><?php echo $this->google_description_limit - strlen($seo_description); ?></span> <?php _e('Characters Remaining', 'try_seo'); ?></span>
		</li> 
		<li>
			<label for="seo_keywords"><strong>Keywords</strong></label><br/>
			<textarea class="widefat" id="seo_keywords" name="seo_keywords" style="resize:none" rows="4" placeholder="Please enter comma separated keywords."><?php echo $seo_keywords; ?></textarea>
		</li> 
		</ul>
		<?php
	}

	public function save_seo_fields() {
		global $post;
		if ( array_key_exists('try_seo_nonce', $_POST ) && wp_verify_nonce( $_POST['try_seo_nonce'], 'try_seo_nonce') ) {
			$fields = array('seo_title', 'seo_description', 'seo_keywords');

			foreach( $fields as $field ) {
				if( array_key_exists( $field, $_POST ) && !empty( $_POST[$field] ) ) {
					update_post_meta( $post->ID, $field, $_POST[$field] );
				} else {
					delete_post_meta( $post->ID, $field, '');
				}
			}
		}
	}

	public function get_identical_meta() {
		extract($_POST, EXTR_OVERWRITE);
		
		global $wpdb;
		
		$args = array(
			'meta_key' => $key,
			'fields' => 'ids',
			'post_type' => 'any'
		);
		
		if(isset($post_id) && absint($post_id)){
			if($value === ''){
				$value = get_the_title($post_id);
			}
			$args['exclude'][] = $post_id;
			
		}
		$args['meta_value'] = $value;
		
		$results = get_posts($args, ARRAY_N);		
				
		if(count($results) > 0) {
			printf('<span>This SEO field contains duplicate content from <a href="%s" target="_blank">another post</a>.', admin_url('post.php?post='.current($results).'&action=edit'));
		}
		exit;
	}

	public function seo_title() {
		if( is_singular() || $this->is_posts_page() ) {
			$title = $this->get_post_title();
		}

		// Provide filter and return stripped and cleaned title
		if( !empty( $title ) ) {
			return $this->clean_attr( apply_filters('seo_title', $title ) );
		}
	}

	public function seo_description() {
		if( is_singular() || $this->is_posts_page() ) {
			$description = $this->get_post_description();
		}

		// Provide filter and return stripped and cleaned description
		if( !empty( $description ) && is_string( $description ) ) {
			echo '<meta name="description" content="' . $this->clean_attr( apply_filters('seo_description', $description ) ) . '"/>' . "\n";
		}
	}
	
	public function seo_keywords() {
		if( is_singular() || $this->is_posts_page() ) {
			$keywords = $this->get_post_keywords();
		}

		// Provide filter and return stripped and cleaned keywords
		if( !empty( $keywords ) && is_string( $keywords ) ) {
			echo '<meta name="keywords" content="' . $this->clean_attr( apply_filters('seo_keywords', $keywords ) ) . '"/>' . "\n";
		}
	}

	protected function get_post_title( $is_default = false ) {
		global $post;

		$seo_title = ( $is_default ) ? false : trim( get_post_meta( $post->ID, 'seo_title', true ) );
		
		// TODO : Allow admin to call wp_get_document_title
		if( is_admin() ) {
			$seo_title = ( $seo_title ) ? $seo_title : get_the_title();
		} else {
			// Remove seo_title filter to avoid infinite loop
			remove_filter('pre_get_document_title', array( $this, 'seo_title' ), 10 );

			$seo_title = ( $seo_title ) ? $seo_title : wp_get_document_title();

			// Reinstate seo_title filter
			add_filter('pre_get_document_title', array( $this, 'seo_title' ), 10 );
		}

		return $this->clean_attr( $seo_title );
	}

	protected function get_post_description( $is_default = false ) {
		global $post;
	
		$seo_description = ( $is_default ) ? false : trim( get_post_meta( $post->ID, 'seo_description', true ) );
		$seo_description = ( $seo_description ) ? $seo_description : $this->get_excerpt( $post->post_content );
		
		return $this->clean_attr( $seo_description );
	}

	protected function get_post_keywords() {
		global $post;
		$seo_description = trim( get_post_meta( $post->ID, 'seo_keywords', true ) );
		if(!is_null($seo_description)){
			return $this->clean_attr( $seo_description );
		}
	}

	protected function is_posts_page() {
		return ( is_home() && 'page' == get_option( 'show_on_front' ) );
	}

	protected function get_excerpt( $string ) {
		if( strlen( $string ) > $this->google_description_limit ) {
			$string = substr( $string, 0, strrpos( substr( $string, 0, $this->google_description_limit + 3 ), ' ') );
			$string .= '...';
		}
		return $string;
	}

	protected function clean_attr( $string ) {
		return esc_attr( str_replace( array("\r\n\r\n", "\r\n", "\r", "\n"), ' ', trim( strip_shortcodes( strip_tags( stripslashes( $string ) ) ) ) ) );
	}
}

$try_seo = new TRY_SEO();