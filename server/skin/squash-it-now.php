<?php
/**
 * Squash It! Now Tools page template
 */
?>
<div class="wbfy-squash-it-now">
	<h1>
		<?php esc_html_e('Squash It Now!', 'wbfy-squash-it');?>
	</h1>
	<p>
		<?php esc_html_e('Uncheck the \'Dry Run\' option to live resize all the images.', 'wbfy-squash-it');?>
	</p>
	<p>
<?php
echo wbfy_si_Libs_Html_Inputs::inputCheck(
    array
    (
        'id'    => 'wbfy-si-dry_run',
        'class' => 'wbfy-si-dry_run',
        'value' => true,
        'label' => __('Dry run only (no images will be updated)', 'wbfy-squash-it'),
    )
);
?>
	</p>
	<p>
<?php
echo '<input type="hidden" value="' . wp_create_nonce('wbfy-squash-it-verify-secure') . '" id="wbfy-si-verify">';
submit_button(__('Squash It!', 'wbfy-squash-it'));
?>
	</p>
	<div id="wbfy-si-log" class="wbfy-si-log">
	</div>
	<p class="wbfy-si-notice">
		<b>
			<?php esc_html_e('NB:', 'wbfy-squash-it');?>
		</b>
<?php
esc_html_e('It is strongly recommended that you use the non-destructive Dry Run option to check for any potential
				problems that you can correct before running the live update of your images.',
    'wbfy-squash-it');
?>
	</p>
</div>