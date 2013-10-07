<?php
/*
 * Plugin Name: UI: Breadcrumbs
 * Plugin URI: http://wordpress.lowtone.nl/plugins/ui-breadcrumbs/
 * Description: Display a breadcrumb trail.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */
/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2013, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\ui\breadcrumbs
 */

namespace lowtone\ui\breadcrumbs {

	use lowtone\content\packages\Package,
		lowtone\ui\breadcrumbs\crumbs\Crumb;

	// Includes
	
	if (!include_once WP_PLUGIN_DIR . "/lowtone-content/lowtone-content.php") 
		return trigger_error("Lowtone Content plugin is required", E_USER_ERROR) && false;

	$__i = Package::init(array(
			Package::INIT_PACKAGES => array("lowtone"),
			Package::INIT_MERGED_PATH => __NAMESPACE__,
			Package::INIT_SUCCESS => function() {

				add_shortcode("breadcrumbs", "lowtone\\ui\\breadcrumbs\\breadcrumbs");

				// Register textdomain
				
				add_action("plugins_loaded", function() {
					load_plugin_textdomain("lowtone_ui_breadcrumbs", false, basename(__DIR__) . "/assets/languages");
				});

			}
		));

	// Functions
	
	function trail($options = NULL) {
		$options = array_merge(apply_filters("lowtone_ui_breadcrumbs_trail_default_options", array(
				"include_parents" => true,
				"include_post_terms" => true,
				"main_taxonomies" => array(
					"post" => "category",
				),
				"ignore_terms" => array(1),
				"include_post_date" => true,
				"include_page" => true,
			)), (array) $options);

		global $post, $author;

		/**
		 * Get an option value.
		 * @param string $name The name of the required option.
		 * @return mixed Returns the value for the option or NULL if the option
		 * is not set.
		 */
		$option = function($name) use (&$options) {
			return isset($options[$name]) ? $options[$name] : NULL;
		};

		/**
		 * Extract and parse the main taxonomies setting from the options.
		 * @return array Returns a list of main taxonomies.
		 */
		$mainTaxonomies = function() use ($option) {
			$mainTaxonomies = $option("main_taxonomies") ?: array();

			if (is_string($mainTaxonomies)) {
				$parsed = array();

				array_map(function($string) use (&$parsed) {
					list($key, $val) = explode(",", $string);

					$parsed[trim($key)] = trim($val);
				}, explode(",", $mainTaxonomies));

				$mainTaxonomies = $parsed;
			} else 
				$mainTaxonomies = (array) $mainTaxonomies;

			return $mainTaxonomies;
		};

		/**
		 * Get the main taxonomy for a given post type.
		 * @param string $postType The subject post type.
		 * @return string|NULL Returns the main taxonomy for the given post type
		 * or NULL if no main taxonomy is defined.
		 */
		$mainTaxonomy = function($postType) use ($mainTaxonomies) {
			$taxonomies = $mainTaxonomies();
			
			return isset($taxonomies[$postType]) ? $taxonomies[$postType] : NULL;
		};

		/**
		 * Extract and parse a list of ignored terms.
		 * @return array Returns a list of ignored terms.
		 */
		$ignoreTerms = function() use ($option) {
			$ignoreTerms = $option("ignore_terms") ?: array();

			if (is_string($ignoreTerms))
				$ignoreTerms = array_map("trim", explode(",", $ignoreTerms));

			return (array) $ignoreTerms;
		};

		/**
		 * The crumbs
		 * @var array
		 */
		$trail = array();

		/**
		 * Add terms to the trail from a given base term.
		 * @param object $root The root term.
		 */
		$addTerms = function($root) use (&$trail) {
			$terms = array_map(function($id) use ($root) {
					return get_term($id, $root->taxonomy);
				}, array_reverse(get_ancestors($root->term_id, $root->taxonomy)));

			$terms[] = $root;

			foreach ($terms as $i => $term) {
				$trail["term-" . $i] = new Crumb(array(
						Crumb::PROPERTY_TITLE => $term->name,
						Crumb::PROPERTY_URI => get_term_link($term->slug, $root->taxonomy),
						Crumb::PROPERTY_CLASS => "term term-{$i} term-id-{$term->term_id}",
					));
			}
		};

		$addDate = function($year, $month = NULL, $day = NULL) use (&$trail) {
			if (!is_numeric($year))
				return;

			$trail["year"] = new Crumb(array(
					Crumb::PROPERTY_TITLE => $year,
					Crumb::PROPERTY_URI => get_year_link($year),
					Crumb::PROPERTY_CLASS => "year",
				));

			if (is_numeric($month)) {
				$trail["month"] = new Crumb(array(
						Crumb::PROPERTY_TITLE => date_i18n("F", mktime(0, 0, 0, $month, 1, $year)),
						Crumb::PROPERTY_URI => get_month_link($year, $month),
						Crumb::PROPERTY_CLASS => "month",
					));

				if (is_numeric($day)) 
					$trail["day"] = new Crumb(array(
							Crumb::PROPERTY_TITLE => $day,
							Crumb::PROPERTY_URI => get_day_link($year, $month, $day),
							Crumb::PROPERTY_CLASS => "day",
						));
			}
			
		};

		// Add front page if it's not the current location

		if (!is_front_page() || get_query_var("paged")) {
			$trail["front_page"] = new Crumb(array(
					Crumb::PROPERTY_TITLE => __("Home", "lowtone_ui_breadcrumbs"),
					Crumb::PROPERTY_URI => home_url(),
					Crumb::PROPERTY_CLASS => "front_page",
				));

			// Archive

			if (is_post_type_archive() /*|| is_archive() */|| is_home()) {

				$title = /*is_archive() ||*/ is_home() 
					? get_post_type_object(get_post_type())->labels->name
					: post_type_archive_title(NULL, false);

				$title = apply_filters("lowtone_ui_breadcrumbs_trail_archive_title", $title, get_post_type());

				$title = apply_filters("lowtone_ui_breadcrumbs_trail_archive_title_" . get_post_type(), $title);

				$uri = /*is_archive() ||*/ is_home() 
					? get_permalink(get_option("page_for_posts"))
					: get_post_type_archive_link(get_post_type());

				$trail["archive"] = new Crumb(array(
					Crumb::PROPERTY_TITLE => $title,
					Crumb::PROPERTY_URI => $uri,
					Crumb::PROPERTY_CLASS => "archive",
				));

			}
		}

		// On any kind of taxonomy

		if (is_tax() || is_category() || is_tag()) {

			$currentTerm = get_queried_object();

			$addTerms($currentTerm);

		} 

		// When viewing a date

		else if (is_date()) {

			$args = array(
					get_the_time("Y"),
					is_month() || is_day() ? get_the_time("m") : NULL,
					is_day() ? get_the_time("d") : NULL,
				);

			call_user_func_array($addDate, $args);

		} 

		// When viewing a specific post that is not an attachment

		elseif (is_singular() && !is_attachment()) {

			// Post parents

			if ($option("include_parents") && is_post_type_hierarchical(get_post_type())) {
				
				foreach (get_ancestors($post->ID, $post->post_type) as $i => $id) {
					$ancestor = get_post($id);

					$trail["ancestor-" . $i] = new Crumb(array(
								Crumb::PROPERTY_TITLE => get_the_title($ancestor->ID),
								Crumb::PROPERTY_URI => get_permalink($ancestor->ID),
								Crumb::PROPERTY_CLASS => sprintf("ancestor ancestor-%d post post-type-%s post-id-%d", $i, get_post_type($ancestor->ID), $ancestor->ID),
						));
				}

			}

			// Main post term

			else if ($option("include_post_terms")
				&& NULL !== ($taxonomy = $mainTaxonomy(get_post_type()))
				&& ($terms = wp_get_post_terms($post->ID, $taxonomy, array("orderby" => "parent", "order" => "DESC")))
				&& !in_array($terms[0]->term_id, $ignoreTerms())) 
					$addTerms($terms[0]);

			// Post date

			else if ($option("include_post_date"))
				$addDate(get_the_time("Y"), get_the_time("m"), get_the_time("d"));

			// The post

			$trail["post"] = new Crumb(array(
						Crumb::PROPERTY_TITLE => get_the_title(),
						Crumb::PROPERTY_URI => get_permalink(),
						Crumb::PROPERTY_CLASS => sprintf("singular post post-type-%s post-id-%d", get_post_type(), get_the_id()),
				));

		} 

		// Not found

		elseif (is_404()) {

			$trail["not_found"] = new Crumb(array(
						Crumb::PROPERTY_TITLE => __("Not found", "lowtone_ui_breadcrumbs"),
						Crumb::PROPERTY_CLASS => "not_found",
				));

		}

		// On search results

		elseif (is_search()) {

			$trail["search"] = new Crumb(array(
						Crumb::PROPERTY_TITLE => sprintf(__("Search results for &ldquo;%s&rdquo;", "lowtone_ui_breadcrumbs"), get_search_query()),
						Crumb::PROPERTY_CLASS => "search",
				));

		}

		// Author

		elseif (is_author()) {

			$userdata = get_userdata($author);

			$trail["author"] = new Crumb(array(
						Crumb::PROPERTY_TITLE => sprintf(__("Author: %s", "lowtone_ui_breadcrumbs"), $userdata->display_name),
						Crumb::PROPERTY_URI => get_author_posts_url($author),
						Crumb::PROPERTY_CLASS => "author",
				));

		}

		// Add page number
		
		if ($pageNumber = get_query_var("paged"))
			$trail["page"] = new Crumb(array(
						Crumb::PROPERTY_TITLE => sprintf(__("Page %d", "lowtone_ui_breadcrumbs"), $pageNumber),
						// Crumb::PROPERTY_URI => get_permalink(),
						Crumb::PROPERTY_CLASS => "page page-{$pageNumber}",
				));

		return Crumb::__createCollection(apply_filters("lowtone_ui_breadcrumbs_trail", $trail));
	}

	function breadcrumbs() {
		return '<ul class="breadcrumbs">' . 
			implode(array_map(function($crumb) {
				return '<li>' . $crumb . '</li>';
			}, (array) trail())) . 
			'</ul>';
	}

}