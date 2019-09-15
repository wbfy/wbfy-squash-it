<?php
/**
 * Squash It! WP Admin options page template
 */

$action_url = esc_url(
    add_query_arg(
        'page',
        'wbfy-squash-it-now',
        get_admin_url() . 'admin.php'
    )
);
$action_link = "<a href='$action_url'>" . esc_html__('Squash It! Now') . '</a>';
?>
<div class="wrap">
	<h1>
        <?php esc_html_e('Configure Squash It! Image Resizer', 'wbfy-squash-it');?>
    </h1>
	<form method="post" action="options.php" name="wbfy-squash-it-admin" class="wbfy-squash-it-admin">
<?php
settings_fields('wbfy_si_options');
do_settings_sections('wbfy_si_options');
submit_button();
?>
	</form>
<?php
echo $action_link;
?>
</div>
