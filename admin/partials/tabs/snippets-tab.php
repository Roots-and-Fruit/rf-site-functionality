<?php
/**
 * Snippets Tab Component
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/admin/partials/tabs
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include components
require_once plugin_dir_path( __FILE__ ) . '../components/snippets-table.php';

/**
 * Plus icon for the Add Snippet button.
 *
 * Uses the core icon library (WordPress 7.1+). Older releases get Dashicons.
 *
 * @return string
 */
function rf_site_functionality_plus_icon() {
	if ( function_exists( 'wp_get_icon' ) ) {
		$icon = wp_get_icon(
			'core/plus',
			array(
				'size'  => 20,
				'class' => 'mc-add-snippet__icon',
			)
		);
		if ( is_string( $icon ) && '' !== $icon ) {
			return wp_kses(
				$icon,
				array(
					'svg'  => array(
						'xmlns'       => true,
						'width'       => true,
						'height'      => true,
						'viewbox'     => true,
						'fill'        => true,
						'class'       => true,
						'aria-hidden' => true,
						'focusable'   => true,
						'role'        => true,
					),
					'path' => array(
						'd'    => true,
						'fill' => true,
					),
				)
			);
		}
	}

	return '<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>';
}
?>

<!-- Snippets Tab Content -->
<div class="rf-site-functionality-snippets-status">
	<div class="mc-snippets-status__header">
		<h3><?php esc_html_e( 'Loaded Snippets', 'rf-site-functionality' ); ?></h3>
		<div class="mc-add-snippet-section">
			<button type="button" class="button button-primary" id="mc-add-snippet-btn">
				<?php echo rf_site_functionality_plus_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is escaped in rf_site_functionality_plus_icon(). ?>
				<?php esc_html_e( 'Add Snippet', 'rf-site-functionality' ); ?>
			</button>
		</div>
	</div>
	<?php mc_render_snippets_table( $all_snippets ); ?>
</div>