<?php
namespace lowtone\ui\breadcrumbs\crumbs;
use lowtone\db\records\Record;

/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2013, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\ui\breadcrumbs\crumbs
 */
class Crumb extends Record {

	const PROPERTY_TITLE = "title",
		PROPERTY_URI = "uri",
		PROPERTY_CLASS = "class";


	public function __toString() {
		return sprintf('<a href="%s" class="%s">', esc_url($this->{self::PROPERTY_URI}), esc_attr(implode(" ", (array) $this->{self::PROPERTY_CLASS}))) . 
			esc_html($this->{self::PROPERTY_TITLE}) . 
			'</a>';
	}

}