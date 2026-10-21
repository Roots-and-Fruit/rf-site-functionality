<?php
/**
 * Snippets Table Component
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/admin/partials/components
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Render the snippets table
 *
 * @param array $all_snippets Array of snippet data
 */
function mc_render_snippets_table( $all_snippets ) {
	if ( empty( $all_snippets ) ) : ?>
		<div class="notice notice-info">
			<p><strong>No snippets found.</strong> Create PHP files in the <code><?php echo esc_html( RF_SITE_FUNCTIONALITY_SNIPPETS_DIR ); ?></code> directory to get started.</p>
		</div>
	<?php else : ?>
		<div class="mc-snippets-list table-wrap">
			<table class="rf-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'File Name', 'rf-site-functionality' ); ?></th>
						<th><?php esc_html_e( 'Path', 'rf-site-functionality' ); ?></th>
						<th><?php esc_html_e( 'Status', 'rf-site-functionality' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$mc_snippet_index  = 0;
					$mc_enabled_label  = _x( 'Enabled', 'toggle state', 'rf-site-functionality' );
					$mc_disabled_label = _x( 'Disabled', 'toggle state', 'rf-site-functionality' );
					foreach ( $all_snippets as $snippet ) :
						++$mc_snippet_index;
						$mc_snippet_slug = sanitize_html_class( $snippet['filename'] . '-' . $mc_snippet_index );
						$mc_name_id      = 'mc-snippet-name-' . $mc_snippet_slug;
						$mc_state_id     = 'mc-snippet-state-' . $mc_snippet_slug;
						?>
						<tr<?php echo $snippet['enabled'] ? '' : ' class="is-disabled"'; ?>>
							<td>
								<a id="<?php echo esc_attr( $mc_name_id ); ?>" href="#" class="mc-edit-snippet" data-filename="<?php echo esc_attr( $snippet['filename'] ); ?>">
									<strong><?php echo esc_html( $snippet['filename'] ); ?></strong>
								</a>
							</td>
							<td class="mc-snippet-path"><?php echo wp_kses( str_replace( array( '/', '\\' ), array( '/<wbr>', '\\<wbr>' ), esc_html( $snippet['path'] ) ), array( 'wbr' => array() ) ); ?></td>
							<td>
								<label class="mc-rf-toggle">
									<input class="mc-rf-toggle__input mc-toggle-snippet" type="checkbox" value="1" data-filename="<?php echo esc_attr( $snippet['filename'] ); ?>" aria-labelledby="<?php echo esc_attr( $mc_name_id . ' ' . $mc_state_id ); ?>" <?php checked( ! empty( $snippet['enabled'] ) ); ?> />
									<span class="mc-rf-toggle__track" aria-hidden="true"></span>
									<span class="mc-rf-toggle__state" id="<?php echo esc_attr( $mc_state_id ); ?>" data-enabled="<?php echo esc_attr( $mc_enabled_label ); ?>" data-disabled="<?php echo esc_attr( $mc_disabled_label ); ?>"><?php echo esc_html( $snippet['enabled'] ? $mc_enabled_label : $mc_disabled_label ); ?></span>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	endif;
}
