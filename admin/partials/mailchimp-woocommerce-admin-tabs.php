<?php
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'api_key';

$active_job = isset($_GET['job']) ? $_GET['job'] : null;

$handler = MailChimp_Woocommerce_Admin::connect();

if ($active_job !== null) {
    $handler->job($active_job);
}

//Grab all options for this particular tab we're viewing.
$options = get_option($this->plugin_name, array());

$show_sync_tab = false;
$show_campaign_defaults = true;
$has_valid_api_key = false;

if (isset($options['mailchimp_api_key']) && $handler->hasValidApiKey()) {
    $has_valid_api_key = true;
    // if we don't have a valid api key we need to redirect back to the 'api_key' tab.
    if (($mailchimp_lists = $handler->getMailChimpLists()) && is_array($mailchimp_lists)) {
        $show_campaign_defaults = false;
    }

    // only display this button if the data is not syncing and we have a valid api key
    if ((bool) $this->getData('sync.syncing', false) === false) {
        $show_sync_tab = true;
    }
}

?>

<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
    <h2>MailChimp Settings</h2>

    <h2 class="nav-tab-wrapper">
        <a href="?page=mailchimp-woocommerce&tab=api_key" class="nav-tab <?php echo $active_tab == 'api_key' ? 'nav-tab-active' : ''; ?>">Connect</a>
        <?php if($has_valid_api_key): ?>
        <a href="?page=mailchimp-woocommerce&tab=store_info" class="nav-tab <?php echo $active_tab == 'store_info' ? 'nav-tab-active' : ''; ?>">Store Settings</a>
        <?php if ($handler->hasValidStoreInfo()) : ?>
        <?php if($show_campaign_defaults): ?>
        <a href="?page=mailchimp-woocommerce&tab=campaign_defaults" class="nav-tab <?php echo $active_tab == 'campaign_defaults' ? 'nav-tab-active' : ''; ?>">List Defaults</a>
        <?php endif; ?>
        <a href="?page=mailchimp-woocommerce&tab=newsletter_settings" class="nav-tab <?php echo $active_tab == 'newsletter_settings' ? 'nav-tab-active' : ''; ?>">Newsletter Settings</a>
        <a href="?page=mailchimp-woocommerce&tab=sync" class="nav-tab <?php echo $active_tab == 'sync' ? 'nav-tab-active' : ''; ?>">Sync Status</a>
        <?php endif;?>
        <?php endif; ?>
    </h2>

    <form method="post" name="cleanup_options" action="options.php">

        <?php
        settings_fields($this->plugin_name);
        do_settings_sections($this->plugin_name);
        settings_errors();
        include('tabs/notices.php');
        ?>

        <input type="hidden" name="<?php echo $this->plugin_name; ?>[mailchimp_active_tab]" value="<?php echo $active_tab; ?>"/>

        <?php if( $active_tab == 'api_key' ): ?>
            <?php include_once 'tabs/api_key.php'; ?>
        <?php endif; ?>

        <?php if( $active_tab == 'store_info' ): ?>
            <?php include_once 'tabs/store_info.php'; ?>
        <?php endif; ?>

        <?php if( $active_tab == 'campaign_defaults' ): ?>
            <?php include_once 'tabs/campaign_defaults.php'; ?>
        <?php endif; ?>

        <?php if( $active_tab == 'newsletter_settings' ): ?>
            <?php include_once 'tabs/newsletter_settings.php'; ?>
        <?php endif; ?>

        <?php if( $active_tab == 'sync' ): ?>
            <?php include_once 'tabs/store_sync.php'; ?>
        <?php endif; ?>

        <?php if ($active_tab !== 'sync') submit_button('Save all changes', 'primary','submit', TRUE); ?>

    </form>

</div><!-- /.wrap -->
