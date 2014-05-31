<?php
/*
Plugin Name: BNS SMF Feeds
Plugin URI: http://buynowshop.com/plugins/bns-smf-feeds/
Description: Plugin with multi-widget functionality that builds an SMF Forum RSS feed url by user option choices; and, displays a SMF forum feed.
Version: 1.9.4
Text Domain: bns-smf
Author: Edward Caissie
Author URI: http://edwardcaissie.com/
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/**
 * BNS SMF Feeds
 *
 * Plugin with multi-widget functionality that builds an SMF Forum RSS feed url
 * by user option choices; and, displays a SMF forum feed.
 *
 * @package     BNS_SMF_Feeds
 * @link        http://buynowshop.com/plugins/bns-smf-feeds/
 * @link        https://github.com/Cais/bns-smf-feeds/
 * @link        http://wordpress.org/extend/plugins/bns-smf-feeds/
 * @version     1.9.4
 * @author      Edward Caissie <edward.caissie@gmail.com>
 * @copyright   Copyright (c) 2009-2014, Edward Caissie
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.
 *
 * You may NOT assume that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to:
 *
 *      Free Software Foundation, Inc.
 *      51 Franklin St, Fifth Floor
 *      Boston, MA  02110-1301  USA
 *
 * The license for this software can also likely be found here:
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version     1.9.3
 * @date        November 2013
 *
 * @version     1.9.4
 * @date        May 2014
 */
class BNS_SMF_Feeds_Widget extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @package         BNS_SMF_Feeds
	 * @since           1.0
	 *
	 * @uses    (class) WP_Widget
	 * @uses            __
	 * @uses            add_action
	 * @uses            add_shortcode
	 */
	function BNS_SMF_Feeds_Widget() {
		/**
		 * Check installed WordPress version for compatibility
		 *
		 * @version    1.9.4
		 * @date       May 31, 2014
		 * Updated required WordPress version to 3.6 to use shortcode filter parameter
		 */
		global $wp_version;
		$exit_message = __( 'BNS SMF Feeds requires WordPress version 3.6 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please Update!</a>', 'bns-smf' );
		if ( version_compare( $wp_version, "3.6", "<" ) ) {
			exit ( $exit_message );
		}
		/** End if - version compare */

		/** Widget settings */
		$widget_ops = array(
			'classname'   => 'bns-smf-feeds',
			'description' => __( 'Displays recent feeds from a SMF Forum.', 'bns-smf' )
		);

		/** Widget control settings */
		$control_ops = array( 'width' => 200, 'id_base' => 'bns-smf-feeds' );

		/** Create the widget */
		$this->WP_Widget( 'bns-smf-feeds', 'BNS SMF Feeds', $widget_ops, $control_ops );

		/** Add Widget */
		add_action(
			'widgets_init', array(
				$this,
				'load_bns_smf_feeds_widget'
			)
		);

		/** Add Shortcode */
		add_shortcode(
			'bns_smf_feeds', array(
				$this,
				'BNS_SMF_Feeds_Shortcode'
			)
		);

	}


	/**
	 * Widget
	 *
	 * @package BNS_SMF_Feeds
	 *
	 * @uses    apply_filters
	 * @uses    esc_attr
	 * @uses    esc_html
	 * @uses    esc_url
	 * @uses    get_description
	 * @uses    get_option
	 * @uses    get_permalink
	 * @uses    get_title
	 * @uses    includes_url
	 * @uses    is_wp_error
	 *
	 * @param   array $args
	 * @param   array $instance
	 */
	function widget( $args, $instance ) {
		global $blank_window;
		extract( $args );
		/** User-selected settings */
		$title          = apply_filters( 'widget_title', $instance['title'] );
		$smf_forum_url  = $instance['smf_forum_url'];
		$smf_feed_type  = $instance['smf_feed_type'];
		$smf_sub_action = $instance['smf_sub_action'];
		$smf_boards     = $instance['smf_boards'];
		$smf_categories = $instance['smf_categories'];
		$limit_count    = $instance['limit_count'];
		$show_author    = $instance['show_author'];
		$show_date      = $instance['show_date'];
		$show_summary   = $instance['show_summary'];
		$blank_window   = $instance['blank_window'];
		$feed_refresh   = $instance['feed_refresh'];
		$smf_feed_url   = $instance['smf_feed_url'];

		if ( empty( $smf_feed_url ) ) {
			$smf_feed_url = '';
			$smf_feed_url .= $smf_forum_url . "index.php?";
			$smf_feed_url .= "type=" . $smf_feed_type . ";";
			$smf_feed_url .= "action=.xml;";
			if ( ! $smf_sub_action ) {
				$smf_feed_url .= "sa=news;"; /* sets feed to Recent Topics */
			} else {
				$smf_feed_url .= "sa=recent;"; /* sets feed to Recent Posts */
			}
			$smf_feed_url .= "board=" . $smf_boards . ";"; /* specify boards */
			$smf_feed_url .= "c=" . $smf_categories . ";"; /* specify categories */
			$smf_feed_url .= "limit=" . $limit_count;
		}
		/** End if - empty smf feed url */

		/* ---- taken from ../wp-includes/default-widgets.php ---- */
		while ( stristr( $smf_feed_url, 'http' ) != $smf_feed_url ) {
			$smf_feed_url = substr( $smf_feed_url, 1 );
		}
		/** End while */

		if ( empty( $smf_feed_url ) ) {
			return;
		}
		/** End if - empty smf feed url */

		$rss = $this->bns_fetch_feed( $smf_feed_url, $feed_refresh );

		if ( ! is_wp_error( $rss ) ) {
			$desc = esc_attr( strip_tags( @html_entity_decode( $rss->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) ) ) );

			if ( empty( $title ) ) {
				$title = esc_html( strip_tags( $rss->get_title() ) );
			}
			/** End if - empty title */

			$link = esc_url( strip_tags( $rss->get_permalink() ) );

			while ( stristr( $link, 'http' ) != $link ) {
				$link = substr( $link, 1 );
			}
			/** End while */
		} else {
			$desc = '';
			$link = '';
		}
		/** End of - is wp error */

		if ( empty( $title ) ) {
			$title = empty( $desc ) ? __( 'Unknown Feed', 'bns-smf' ) : $desc;
		}
		/** End if - empty title */

		$title        = apply_filters( 'widget_title', $title );
		$smf_feed_url = esc_url( strip_tags( $smf_feed_url ) );
		$icon         = includes_url( 'images/rss.png' );

		if ( $title ) {
			$title = "<a class='bns-smf-feeds rsswidget' href='$smf_feed_url' " . ( ! $blank_window ? "target=''" : "target='_blank'" ) . " title='" . esc_attr( __( 'Syndicate this content', 'bns-smf' ) ) . "'><img style='background:orange;color:white;border:none;' width='14' height='14' src='$icon' alt='RSS' /></a> <a class='bns-smf-feeds rsswidget' href='$link' " . ( ! $blank_window ? "target=''" : "target='_blank'" ) . " title='$desc'>$title</a>";
		}
		/** End if - title */
		/* ---- ... and the wheels on the bus go round and round ... ---- */

		/** @var $before_widget string - defined by theme */
		echo $before_widget;

		/** $title of widget */
		if ( $title ) {
			/** @var $before_title string - defined by theme */
			/** @var $after_title string - defined by theme */
			echo $before_title . $title . $after_title;
		}
		/** End if - title */

		/** Display feed from widget settings */
		$this->bns_wp_widget_rss_output(
			 $smf_feed_url, $feed_refresh, array(
					 'show_author'  => ( ( $show_author ) ? 1 : 0 ),
					 'show_date'    => ( ( $show_date ) ? 1 : 0 ),
					 'show_summary' => ( ( $show_summary ) ? 1 : 0 )
				 )
		);

		/** @var $after_widget string - defined by theme */
		echo $after_widget;

	} /** End function - widget */


	/**
	 * Update
	 *
	 * @package BNS_SMF_Feeds
	 *
	 * @param   array $new_instance
	 * @param   array $old_instance
	 *
	 * @return  array
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/** Strip tags (if needed) and update the widget settings */
		$instance['title']          = strip_tags( $new_instance['title'] );
		$instance['smf_forum_url']  = $new_instance['smf_forum_url'];
		$instance['smf_feed_type']  = $new_instance['smf_feed_type'];
		$instance['smf_sub_action'] = $new_instance['smf_sub_action'];
		$instance['smf_boards']     = $new_instance['smf_boards'];
		$instance['smf_categories'] = $new_instance['smf_categories'];
		$instance['limit_count']    = $new_instance['limit_count'];
		$instance['show_author']    = $new_instance['show_author'];
		$instance['show_date']      = $new_instance['show_date'];
		$instance['show_summary']   = $new_instance['show_summary'];
		$instance['blank_window']   = $new_instance['blank_window'];
		$instance['feed_refresh']   = $new_instance['feed_refresh'];
		$instance['smf_feed_url']   = $new_instance['smf_feed_url'];

		return $instance;

	} /** End function - update */


	/**
	 * Form
	 *
	 * @package    BNS_SMF_Feeds
	 * @since      1.0
	 *
	 * @uses       __
	 * @uses       _e
	 * @uses       checked
	 * @uses       get_field_id
	 * @uses       get_field_name
	 * @uses       selected
	 * @uses       wp_parse_args
	 *
	 * @param   array $instance
	 *
	 * @return  string|void
	 *
	 * @version    1.9.4
	 * @date       May 31, 2014
	 * Fixed selection code for feed types
	 */
	function form( $instance ) {
		/** Set up default widget settings */
		$defaults           = array(
			'title'          => __( 'SMF Forum Feed', 'bns-smf' ),
			'smf_forum_url'  => '',
			'smf_feed_type'  => 'rss2',
			'smf_sub_action' => false,
			// default to 'news' or recent Topics, check for 'recent' Posts
			'smf_boards'     => '',
			// defaults to all
			'smf_categories' => '',
			// defaults to all
			'limit_count'    => '10',
			'show_author'    => false,
			// Not currently supported by SMF feeds; future version?
			'show_date'      => false,
			'show_summary'   => false,
			'blank_window'   => false,
			'feed_refresh'   => '43200'
			// Default value as noted in feed.php core file = 12 hours
		);
		$instance['number'] = $this->number;
		$instance           = wp_parse_args( ( array ) $instance, $defaults ); ?>

		<p>
			<label
				for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (optional; if blank: defaults to feed title, if it exists):', 'bns-smf' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>"
				   name="<?php echo $this->get_field_name( 'title' ); ?>"
				   value="<?php echo $instance['title']; ?>"
				   style="width:100%;" />
		</p>

		<p>
			<label
				for="<?php echo $this->get_field_id( 'smf_forum_url' ); ?>"><?php _e( 'SMF Forum URL (e.g. http://www.simplemachines.org/community/):', 'bns-smf' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'smf_forum_url' ); ?>"
				   name="<?php echo $this->get_field_name( 'smf_forum_url' ); ?>"
				   value="<?php echo $instance['smf_forum_url']; ?>"
				   style="width:100%;" />
		</p>

		<p>
			<label
				for="<?php echo $this->get_field_id( 'smf_feed_type' ); ?>"><?php _e( 'Feed Type:', 'bns-smf' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'smf_feed_type' ); ?>"
					name="<?php echo $this->get_field_name( 'smf_feed_type' ); ?>"
					class="widefat" style="width:100%;">
				<option value="rss" <?php selected( 'rss', $instance['smf_feed_type'], true ); ?>>
					<?php _e( 'RSS', 'bns-smf' ); ?>
				</option>
				<option value="rss2" <?php selected( 'rss2', $instance['smf_feed_type'], true ); ?>>
					<?php _e( 'RSS2', 'bns-smf' ); ?>
				</option>
				<option value="atom" <?php selected( 'atom', $instance['smf_feed_type'], true ); ?>>
					<?php _e( 'ATOM', 'bns-smf' ); ?>
				</option>
				<option value="rdf" <?php selected( 'rdf', $instance['smf_feed_type'], true ); ?>>
					<?php _e( 'RDF', 'bns-smf' ); ?>
				</option>
			</select>
		</p>

		<p>
			<input class="checkbox"
				   type="checkbox" <?php checked( ( bool ) $instance['smf_sub_action'], true ); ?>
				   id="<?php echo $this->get_field_id( 'smf_sub_action' ); ?>"
				   name="<?php echo $this->get_field_name( 'smf_sub_action' ); ?>" />
			<label
				for="<?php echo $this->get_field_id( 'smf_sub_action' ); ?>"><?php _e( 'Display Recent Posts (default is Topics)?', 'bns-smf' ); ?></label>
		</p>

		<p>
			<label
				for="<?php echo $this->get_field_id( 'smf_boards' ); ?>"><?php _e( 'Specify Boards separated by commas by ID (default is ALL):', 'bns-smf' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'smf_boards' ); ?>"
				   name="<?php echo $this->get_field_name( 'smf_boards' ); ?>"
				   value="<?php echo $instance['smf_boards']; ?>"
				   style="width:100%;" />
		</p>

		<p>
			<label
				for="<?php echo $this->get_field_id( 'smf_categories' ); ?>"><?php _e( 'Specify Categories separated by commas by ID (default is ALL):', 'bns-smf' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'smf_categories' ); ?>"
				   name="<?php echo $this->get_field_name( 'smf_categories' ); ?>"
				   value="<?php echo $instance['smf_categories']; ?>"
				   style="width:100%;" />
		</p>

		<p>
			<label
				for="<?php echo $this->get_field_id( 'limit_count' ); ?>"><?php _e( 'Maximum items to display (affected by SMF permissions):', 'bns-smf' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'limit_count' ); ?>"
				   name="<?php echo $this->get_field_name( 'limit_count' ); ?>"
				   value="<?php echo $instance['limit_count']; ?>"
				   style="width:100%;" />
		</p>

		<table width="100%">
			<tr>
				<td>
					<p>
						<input class="checkbox"
							   type="checkbox" <?php checked( ( bool ) $instance['show_date'], true ); ?>
							   id="<?php echo $this->get_field_id( 'show_date' ); ?>"
							   name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
						<label
							for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display item date?', 'bns-smf' ); ?></label>
					</p>
				</td>
				<td>
					<p>
						<input class="checkbox"
							   type="checkbox" <?php checked( ( bool ) $instance['show_summary'], true ); ?>
							   id="<?php echo $this->get_field_id( 'show_summary' ); ?>"
							   name="<?php echo $this->get_field_name( 'show_summary' ); ?>" />
						<label
							for="<?php echo $this->get_field_id( 'show_summary' ); ?>"><?php _e( 'Show item summary?', 'bns-smf' ); ?></label>
					</p>
				</td>
			</tr>
			<tr>
				<td>
					<p>
						<input class="checkbox"
							   type="checkbox" <?php checked( ( bool ) $instance['blank_window'], true ); ?>
							   id="<?php echo $this->get_field_id( 'blank_window' ); ?>"
							   name="<?php echo $this->get_field_name( 'blank_window' ); ?>" />
						<label
							for="<?php echo $this->get_field_id( 'blank_window' ); ?>"><?php _e( 'Open in new window?', 'bns-smf' ); ?></label>
					</p>
				</td>
			</tr>
		</table>

		<p>
			<label
				for="<?php echo $this->get_field_id( 'feed_refresh' ); ?>"><?php _e( 'Feed Refresh frequency (in seconds):', 'bns-smf' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'feed_refresh' ); ?>"
				   name="<?php echo $this->get_field_name( 'feed_refresh' ); ?>"
				   value="<?php echo $instance['feed_refresh']; ?>"
				   style="width:100%;" />
		</p>

	<?php
	} /** End function - form */


	/* ---- */
	/* ---- Why re-invent the wheel? ---- */

	/* ---- taken from ../wp-includes/feed.php ---- */
	/**
	 * Build SimplePie object based on RSS or Atom feed from URL.
	 *
	 * @package    WordPress
	 * @since      2.8
	 *
	 * @param   string $url URL to retrieve feed
	 * @param    int   $feed_refresh
	 *
	 * @return  WP_Error|SimplePie WP_Error object on failure or SimplePie object on success
	 *
	 * @version    1.9.4
	 * @date       May 31, 2014
	 * Added $feed_fresh parameter to take it out of the global realm
	 */
	function bns_fetch_feed( $url, $feed_refresh ) {
		require_once( ABSPATH . WPINC . '/class-feed.php' );
		$feed = new SimplePie();
		$feed->set_feed_url( $url );
		$feed->set_cache_class( 'WP_Feed_Cache' );
		$feed->set_file_class( 'WP_SimplePie_File' );
		$feed->set_cache_duration( apply_filters( 'wp_feed_cache_transient_lifetime', $feed_refresh ) );
		$feed->init();
		$feed->handle_content_type();

		if ( $feed->error() ) {
			return new WP_Error( 'simplepie-error', $feed->error() );
		}

		/** End if - feed error */

		return $feed;

	} /** End function - bns fetch feed */


	/* ---- taken from ../wp-includes/default-widgets.php ---- */
	/**
	 * Display the RSS entries in a list.
	 *
	 * @package    WordPress
	 * @since      2.5.0
	 *
	 * @param         $rss
	 * @param         $feed_refresh
	 * @param   array $args
	 *
	 * @return  void
	 *
	 * @version    1.9.4
	 * @date       May 31, 2014
	 * Added $feed_fresh parameter to take it out of the global realm
	 */
	function bns_wp_widget_rss_output( $rss, $feed_refresh, $args = array() ) {
		global $blank_window, $limit_count;

		if ( is_string( $rss ) ) {
			$rss = $this->bns_fetch_feed( $rss, $feed_refresh );
		} elseif ( is_array( $rss ) && isset( $rss['url'] ) ) {
			$args = $rss;
			$rss  = $this->bns_fetch_feed( $rss['url'], $feed_refresh );
		} elseif ( ! is_object( $rss ) ) {
			return;
		}
		/** End if - is string */

		if ( is_wp_error( $rss ) ) {
			if ( is_admin() || current_user_can( 'manage_options' ) ) {
				echo '<p>' . sprintf( __( '<strong>RSS Error</strong>: %s' ), $rss->get_error_message() ) . '</p>';
			}

			/** End if - is admin */

			return;

		}
		/** End if - is wp error */

		$default_args = array(
			'show_author'  => 0,
			'show_date'    => 0,
			'show_summary' => 0
		);
		$args         = wp_parse_args( $args, $default_args );
		extract( $args, EXTR_SKIP );

		/** @var $show_summary boolean */
		$show_summary = ( int ) $show_summary;
		/** @var $show_author boolean */
		$show_author = ( int ) $show_author;
		/** @var $show_date boolean */
		$show_date = ( int ) $show_date;

		if ( ! $rss->get_item_quantity() ) {
			echo '<ul><li>' . __( 'An error has occurred; the feed is probably down. Try again later.', 'bns-smf' ) . '</li></ul>';
			$rss->__destruct();
			unset( $rss );

			return;

		}
		/** end if - not rss */

		echo '<ul class="bns-smf-feeds">';

		foreach ( $rss->get_items( 0, $limit_count ) as $item ) {

			$link = $item->get_link();
			while ( stristr( $link, 'http' ) != $link ) {
				$link = substr( $link, 1 );
			}
			/** End while */

			$link = esc_url( strip_tags( $link ) );

			/** @todo - Can this be optionally limited? */
			$title = esc_attr( strip_tags( $item->get_title() ) );

			if ( empty( $title ) ) {
				$title = __( 'Untitled', 'bns-smf' );
			}
			/** End if - empty title */

			$desc = str_replace(
				array(
					"\n",
					"\r"
				), ' ', esc_attr( strip_tags( @html_entity_decode( $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) ) ) )
			);
			$desc = wp_html_excerpt( $desc, 360 );
			$desc = esc_html( $desc );

			if ( $show_summary ) {
				$summary = "<div class='bns-smf-feeds rssSummary'>$desc</div>";
			} else {
				$summary = '';
			}
			/** End if - show summary */

			$date = '';

			if ( $show_date ) {
				$date = $item->get_date();
				if ( $date ) {
					if ( $date_stamp = strtotime( $date ) ) {
						$date = '<br /><span class="bns-smf-feeds rss-date">' . date_i18n( get_option( 'date_format' ), $date_stamp ) . '</span>';
					} else {
						$date = '';
					}
				}
				/** End if - date */

			}
			/** End if - show date */

			$author = '';

			if ( $show_author ) {
				$author = $item->get_author();
				if ( is_object( $author ) ) {
					$author = $author->get_name();
					$author = ' <cite>' . esc_html( strip_tags( $author ) ) . '</cite>';
				}
				/** End if - is object */

			}
			/** End if - show author */

			if ( $link == '' ) {
				echo "<li class='bns-smf-feeds'>$title{$date}{$summary}{$author}</li>";
			} else {
				echo "<li><a class='bns-smf-feeds rsswidget' href='$link' " . ( ! $blank_window ? "target=''" : "target='_blank'" ) . " title='$desc'>$title</a>{$date}{$summary}{$author}</li>";
			}
			/** End if - link */

		}
		/** End for - rss */

		echo '</ul>';
		$rss->__destruct();
		unset( $rss );

	} /** End function - widget rss output */
	/* ---- ... and the wheels on the bus go round and round ... ---- */
	/* ---- */


	/**
	 * Register Widget
	 *
	 * @package BNS_SMF_Feeds
	 *
	 * @uses    register_widget
	 */
	function load_bns_smf_feeds_widget() {
		register_widget( 'BNS_SMF_Feeds_Widget' );
	} /** End function - register widget */


	/**
	 * BNS SMF Feeds Widget Shortcode
	 * Adds shortcode functionality by using the PHP output buffer methods to
	 * capture `the_widget` output and return the data to be displayed via the use
	 * of the `bns_smf_feeds` shortcode.
	 *
	 * @package    BNS_SMF_Feeds
	 * @since      1.8
	 *
	 * @uses       the_widget
	 * @uses       shortcode_atts
	 *
	 * @param   $atts
	 *
	 * @internal   used with add_shortcode
	 *
	 * @return string
	 *
	 * @version    1.9.4
	 * @date       May 31, 2014
	 * Added shortcode option filter
	 */
	function BNS_SMF_Feeds_Shortcode( $atts ) {

		/** Start output buffer capture */
		ob_start(); ?>
		<div class="bns-smf-feeds-shortcode">
			<?php
			/**
			 * Use 'the_widget' as the main output function to be captured
			 * @link http://codex.wordpress.org/Function_Reference/the_widget
			 */
			the_widget(
			/** The widget name as defined in the class extension */
				'BNS_SMF_Feeds_Widget',
				/**
				 * The default options (as the shortcode attributes array) to be
				 * used with the widget
				 */
				$instance = shortcode_atts(
					array(
						/** Set title to null for aesthetic reasons */
						'title'          => __( 'SMF Forum Feed', 'bns-smf' ),
						'smf_forum_url'  => '',
						'smf_feed_type'  => 'rss2',
						'smf_sub_action' => false,
						/** default to 'news' or recent Topics, check for 'recent' Posts */
						'smf_boards'     => '',
						/** defaults to all */
						'smf_categories' => '',
						/** defaults to all */
						'limit_count'    => '10',
						'show_author'    => false,
						/** Not currently supported by SMF feeds; future version? */
						'show_date'      => false,
						'show_summary'   => false,
						'blank_window'   => false,
						'feed_refresh'   => '43200',
						/** Default value as noted in feed.php core file = 12 hours */
						'smf_feed_url'   => '',
						/** @todo need to fix this - Should not be used as a parameter */
					),
					$atts, 'bns_smf_feeds'
				),
				/**
				 * Override the widget arguments and set to null. This will set the
				 * theme related widget definitions to null for aesthetic purposes.
				 */
				$args = array(
					'before_widget' => '',
					'before_title'  => '',
					'after_title'   => '',
					'after_widget'  => ''
				)
			); ?>
		</div><!-- .bns-smf-feeds-shortcode -->
		<?php
		/** Get the current output buffer contents and delete current output buffer. */
		/** @var $bns_smf_feeds_output string */
		$bns_smf_feeds_output = ob_get_clean();

		/** Return the output buffer data for use with add_shortcode output */

		return $bns_smf_feeds_output;

	}
	/** End function - shortcode */

}

/** End class BNS_SMF_Feeds_Widget */


/** @var $bnssmf - instantiate the class */
$bnssmf = new BNS_SMF_Feeds_Widget();