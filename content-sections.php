<?php
if (!class_exists('skeleton_plugin')) require_once( dirname( __FILE__ ) . '/lib/plugin-skeleton.php');
if (!class_exists('content_sections')){

	class content_sections extends plugin_skeleton{
		var $friendly_name = "Content Sections";
		var $namespace = "content_sections";
		var $version = "0.1";
		var $settings = array(
			array(
				'name' 		=> 'Header Offset (in px)',
				'slug' 		=> 'header-offset',
				'type' 		=> 'text',
				'default'	=> false,
			),
		);

		function __construct($cfg){
			parent::__construct($cfg);
		}

		/**
	 	* THERE CAN BE ONLY ONE
	 	*/
		public static function instance($cfg = null) {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self($cfg);
				return;
			}
			return self::$instance;
		}

		/**
	 	* Add in various hooks
	 	* 
	 	* Place all add_action, add_filter, add_shortcode hook-ins here
	 	*/
		function add_hooks() {
			// Since we don't have any useful options yet, no need to
			// bother with the parent hooks.
			//parent::add_hooks();
			add_shortcode( 'section', array(&$this, 'section_shortcode') );
			add_filter( 'the_content', array(&$this, 'add_toc_shortcode'), 12 );
			add_action( 'wp_enqueue_scripts', array(&$this, 'register_scripts'));
		}

		/**
		 * Registers jQuery Waypoints for use by theme authors if they're so inclined.
		 *
		 * @todo turn this off by default and make it dependent on an option
		 */
		public function register_scripts() {
			//You can call these from your view templates if you need them
			wp_register_script( 'jquery-waypoints', CONTENT_SECTIONS_URL . '/js/waypoints.min.js', array('jquery'), '2.0.1', true );
		}

		/**
		 * Loads and rerturns parsed template contents. Allows theme authors to
		 * overload tempaltes used in shortcodes.
		 * 
		 * @global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID
		 */
		public function parse_template( $name , $context = null ) {
			//Hat Tip to WordPress codex: http://codex.wordpress.org/Function_Reference/load_template

			// locate_template() returns path to file
			// if either the child theme or the parent theme have overridden the template
			if ( !$template = locate_template( $name . '.php' ) )
				$template = CONTENT_SECTIONS_PATH . '/views/' . $name . '.php';
				
			/* 	This is basically a hand-rolled version of WordPress's 'load_template',
				because the WordPress template system is kind of goofy. See
				http://core.trac.wordpress.org/ticket/21676 for details. */
			
			global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;
	
	        if ( is_array( $wp_query->query_vars ) )
	        	extract( $wp_query->query_vars, EXTR_SKIP );

			if($context)
				extract($context);

			ob_start();
			require( $template );
			$parsed_template = ob_get_clean();

			return $parsed_template; 
		}

		/**
		 * Shortcode handler for the section links.
		 * 
		 * @global $post
		 * @uses content_sections::parse_template
		 */
		public function section_shortcode($atts, $content = null) {
			global $post;
			
			if(!$post->post_content_sections)
				$post->post_content_sections = array();

			if(!$atts['slug'])
				$atts['slug'] = str_replace(' ','_',$atts['title']);
			
			if(!isset( $post->post_content_sections[$atts['slug']] ))
				$post->post_content_sections[$atts['slug']] = $atts;

			$context = array(
				'content' 	=> $content,
				'atts'		=> $atts
			);

			return $this->parse_template('content-section-anchor', $context);
		}

		/**
		 * Run only one shortcode on the content.
		 * 
		 * @global $shortcode_tags
		 */
		public function do_shortcode_by_tags($content, $tags) {
			global $shortcode_tags;
			$_tags = $shortcode_tags; // store temp copy
			foreach ($_tags as $tag => $callback) {
				if (!in_array($tag, $tags)) // filter unwanted shortcode
					unset($shortcode_tags[$tag]);
			}
			$shortcoded = do_shortcode($content);
			$shortcode_tags = $_tags; // put all shortcode back
			return $shortcoded;
		}

		/**
		 * Get (and create, if necessary) the table of contents array.
		 * 
		 * @uses content_sections::do_shortcode_by_tag
		 * @global $post 
		 */
		public function get_post_content_sections() {
			global $post;
			
			if(!$post->post_content_sections) {
			/*  We're grabbing the menu before the_content() has been called.
				It's cool, we can deal with this! We just call our shortcode early.
				But we're not going to change the content — the shortcode shouldn't
				change the post content until the rest of the shortcodes are run.
				We're accepting a performance hit to avoid surprising the user. */
				$this->do_shortcode_by_tags($post->post_content, array('section'));
			}

			return $post->post_content_sections;
		}

		/**
		 * Create and return the table of contents display.
		 * 
		 * @uses content_sections::get_post_content_sections
		 * @uses content_sections::parse_template
		 */
		public function get_the_toc($context = array()) {
			$toc = $this->get_post_content_sections();
			$context['toc'] = $toc;
			return $this->parse_template('content-section-toc', $context);
		}

		/**
		 * Hack to make TOC shortcode run at priority 12
		 * 
		 */
		public function add_toc_shortcode($content) {
			add_shortcode( 'section-toc', array($this,'toc_shortcode') );
			$content = do_shortcode($content,'section-toc');
			return $content;
		}

		/**
		 * TOC shortcode handler
		 * 
		 */
		public function toc_shortcode($atts, $content = null) {
			//Eventually the atts will do something, idkwtf
			$atts['content'] = $content;
			return $this->get_the_toc($atts);
		}
		
	}

}
?>