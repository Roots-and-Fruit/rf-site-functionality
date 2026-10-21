<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.mattcromwell.com
 * @since      1.0.0
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get the snippet loader to show loaded snippets
$snippet_loader = new Rf_Site_Functionality_Snippet_Loader();
$all_snippets   = $snippet_loader->get_all_snippets();
$mc_plugin_file = dirname( __DIR__, 2 ) . '/rf-site-functionality.php';
?>

<div class="wrap mc-rf-settings">
	<header class="mc-rf-settings__header">
		<h1 class="mc-rf-settings__title">
			<img class="mc-rf-settings__mark" src="<?php echo esc_url( plugins_url( 'admin/images/site-functionality-mark.svg', $mc_plugin_file ) ); ?>" alt="" width="56" height="56" />
			<?php esc_html_e( 'Site Functionality', 'rf-site-functionality' ); ?>
		</h1>
		<p class="mc-rf-settings__lede"><?php esc_html_e( 'Snippets live as files. Turn one off by renaming it, or let a fatal error do that for you.', 'rf-site-functionality' ); ?></p>
	</header>
	<?php // WordPress moves .notice elements to after this marker, under the heading. ?>
	<hr class="wp-header-end" />

	<div class="mc-rf-settings__body">
		<div class="rf-tabs">
			<?php require_once plugin_dir_path( __FILE__ ) . 'components/tab-navigation.php'; ?>
			<div class="rf-tabs__panel">
				<div class="rf-site-functionality-admin-content">
					<?php
					$current_tab = mc_get_current_tab();

					switch ( $current_tab ) {
						case 'overview':
							require_once plugin_dir_path( __FILE__ ) . 'tabs/overview-tab.php';
							break;
						case 'settings':
							require_once plugin_dir_path( __FILE__ ) . 'tabs/settings-tab.php';
							break;
						default:
							require_once plugin_dir_path( __FILE__ ) . 'tabs/snippets-tab.php';
							break;
					}
					?>
				</div>
			</div>
		</div>
	</div>

	<footer class="mc-rf-footer">
		<button type="button" class="mc-rf-footer__brand" aria-expanded="false" aria-controls="mc-rf-about">
			<img src="<?php echo esc_url( plugins_url( 'admin/images/rf-logo.svg', $mc_plugin_file ) ); ?>" alt="<?php esc_attr_e( 'Roots and Fruit', 'rf-site-functionality' ); ?>" width="120" height="53" />
		</button>
	</footer>

	<aside id="mc-rf-about" class="mc-rf-about" hidden>
		<p class="mc-rf-about__hello"><strong><?php esc_html_e( 'Hi, I’m Matt!', 'rf-site-functionality' ); ?></strong>
		<span><?php esc_html_e( 'But most folks call me Cromwell.', 'rf-site-functionality' ); ?></span></p>
		<p><?php esc_html_e( 'Roots & Fruit is my digital product practice for solopreneurs and product teams: sustainable growth focused on CX and Marketing driven ROI.', 'rf-site-functionality' ); ?></p>
		<h2 class="mc-rf-about__heading"><?php esc_html_e( 'About Site Functionality', 'rf-site-functionality' ); ?></h2>
		<p><?php esc_html_e( 'Site Functionality keeps small pieces of PHP as files beside WordPress. A snippet stays readable, easy to version, and easy to turn off. The plugin loads those files early, and a fatal error disables the file that caused it instead of taking the site down. Three commitments:', 'rf-site-functionality' ); ?></p>
		<ol class="mc-rf-about__list">
			<li><?php esc_html_e( 'Snippets live as files, outside the database.', 'rf-site-functionality' ); ?></li>
			<li><?php esc_html_e( 'A broken snippet turns itself off. The rest of the site stays up.', 'rf-site-functionality' ); ?></li>
			<li><?php esc_html_e( 'Renaming a file is enough to disable it. You do not have to delete the code.', 'rf-site-functionality' ); ?></li>
		</ol>
		<p><?php esc_html_e( 'I’m always available to chat.', 'rf-site-functionality' ); ?> <a href="#"><?php esc_html_e( 'Here’s my comment form.', 'rf-site-functionality' ); ?></a></p>
		<div class="mc-rf-about__person">
			<img class="mc-rf-about__photo" src="<?php echo esc_url( plugins_url( 'admin/images/matt-cromwell.jpg', $mc_plugin_file ) ); ?>" alt="" width="64" height="64" />
			<p class="mc-rf-about__id"><strong><?php esc_html_e( 'Matt Cromwell', 'rf-site-functionality' ); ?></strong>
			<span><?php esc_html_e( 'Founder and CGO at Roots and Fruit', 'rf-site-functionality' ); ?></span></p>
		</div>
	</aside>
</div>

<?php
// Include modal only on snippets tab
if ( 'snippets' === $current_tab ) {
	require_once plugin_dir_path( __FILE__ ) . 'components/snippet-editor-modal.php';
}
?>
