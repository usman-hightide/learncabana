<?php
/**
 * Admin Template retrieval class.
 *
 * @since 1.6.7
 *
 * @package LearnDash\Notifications
 */

namespace LearnDash\Notifications\Templates;

use LearnDash\Notifications\StellarWP\Templates\Template as StellarWP_Template;

/**
 * Admin Template retrieval class.
 *
 * @since 1.6.7
 */
class Admin_Template extends StellarWP_Template {
	/**
	 * Base template for where to look for template.
	 *
	 * @since 1.6.7
	 *
	 * @var string[]
	 */
	protected array $template_base_path = [ LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'src/admin-views' ];

	/**
	 * Allow changing if class will extract data from the local context.
	 *
	 * @since 1.6.7
	 *
	 * @var boolean
	 */
	protected bool $template_context_extract = true;
}
