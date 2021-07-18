<?php
/**
 * New Account notification email
 * This email is getting send, when a new user is created by Add Customer for Woocommerce.
 * For this to happen, you have to activate the option in Wordpress Backend -> Settings -> Add Customer Settings -> Send Notifications to new user
 * 
 * @version 1.1
 */
defined('ABSPATH') || exit;

extract(get_defined_vars());
$email = (isset($template_args[0])) ? $template_args[0] : '';
$name = (isset($template_args[1])) ? $template_args[1] : '';
$password = (isset($template_args[2])) ? '<strong>'.$template_args[2].'</strong>' : '';
$site = (isset($template_args[3])) ? $template_args[3] : '';

$account_link = make_clickable(esc_url(wc_get_page_permalink('myaccount')));

do_action('woocommerce_email_header', esc_html__('New account created', 'wac'), 'header_email'); ?>

<h1><?php echo sprintf(esc_html__('Hi, %s', 'wac'), $name); ?></h1>
<p><?php echo sprintf(esc_html__('Your account on %s got created. You can login with the following credentials:', 'wac'), $site); ?></p>
<p><?php echo sprintf(esc_html__('Email: %s', 'wac'), $email); ?><br />
    <?php echo sprintf(esc_html__('Password: %s', 'wac'), $password); ?></p>

<p><?php echo sprintf(esc_html__('To your account: %s', 'wac'), $account_link); ?><br/>
<?php echo sprintf(esc_html__('We recommend changing the password as soon as you have logged in to improve security.', 'wac'), $account_link); ?>
</p>

<?php
do_action('woocommerce_email_footer', 'footer_email');
