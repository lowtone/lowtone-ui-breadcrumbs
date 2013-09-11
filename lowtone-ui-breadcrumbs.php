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

			}
		));

	// Functions
	
	function trail() {
		$trail = array();

		if (!is_front_page())
			$trail[] = new Crumb(array(
					Crumb::PROPERTY_TITLE => __("Home", "lowtone_ui_breadcrumbs"),
					Crumb::PROPERTY_URI => home_url(),
					Crumb::PROPERTY_CLASS => "front_page",
				));

		return apply_filters("lowtone_ui_breadcrumbs_trail", $trail);
	}

	function breadcrumbs() {
		return '<ul class="breadcrumbs">' . 
			implode(array_map(function($crumb) {
				return '<li>' . $crumb . '</li>';
			}, trail())) . 
			'</ul>';
	}

}