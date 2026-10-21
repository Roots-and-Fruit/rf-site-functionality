<?php
/**
 * Tab Navigation Component
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/admin/partials/components
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'mc_get_current_tab' ) ) {
	/**
	 * Snippets, overview, or settings.
	 *
	 * @return string
	 */
	function mc_get_current_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab only chooses a view.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'snippets';
		if ( 'overview' === $tab || 'settings' === $tab ) {
			return $tab;
		}
		return 'snippets';
	}
}

if ( ! function_exists( 'rf_site_functionality_tab_shoulder' ) ) {
	/**
	 * Slanted right edge of a folder tab.
	 *
	 * @return string
	 */
	function rf_site_functionality_tab_shoulder() {
		return wp_kses(
			'<svg class="rf-tabs__shoulder" viewBox="0 0 48 40" width="48" height="40" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path class="rf-tabs__shoulder-face" d="M0 0H20Q26 0 30 8L46 40H0Z" /><path class="rf-tabs__shoulder-edge" d="M0 .5H20Q26 .5 30 8.5L46 40" /></svg>',
			array(
				'svg'  => array(
					'class'               => true,
					'viewbox'             => true,
					'width'               => true,
					'height'              => true,
					'preserveaspectratio' => true,
					'aria-hidden'         => true,
					'focusable'           => true,
				),
				'path' => array(
					'class' => true,
					'd'     => true,
				),
			)
		);
	}
}

$current_tab  = mc_get_current_tab();
$mc_tab_items = array(
	'snippets' => __( 'Snippets', 'rf-site-functionality' ),
	'overview' => __( 'Overview', 'rf-site-functionality' ),
	'settings' => __( 'Settings', 'rf-site-functionality' ),
);
?>

<nav class="rf-tabs__list" aria-label="<?php esc_attr_e( 'Site Functionality', 'rf-site-functionality' ); ?>">
	<?php foreach ( $mc_tab_items as $slug => $label ) : ?>
		<?php
		$active = $slug === $current_tab;
		$url    = add_query_arg(
			array(
				'page' => 'rf-site-functionality',
				'tab'  => $slug,
			),
			admin_url( 'plugins.php' )
		);
		?>
		<a class="rf-tabs__tab<?php echo $active ? ' is-current' : ''; ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
			<span class="rf-tabs__dot" aria-hidden="true"></span>
			<?php echo esc_html( $label ); ?>
			<?php echo rf_site_functionality_tab_shoulder(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in rf_site_functionality_tab_shoulder(). ?>
		</a>
	<?php endforeach; ?>
</nav>
