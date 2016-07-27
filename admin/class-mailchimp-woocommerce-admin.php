<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://mailchimp.com
 * @since      1.0.1
 *
 * @package    MailChimp_Woocommerce
 * @subpackage MailChimp_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    MailChimp_Woocommerce
 * @subpackage MailChimp_Woocommerce/admin
 * @author     Ryan Hungate <ryan@mailchimp.com>
 */
class MailChimp_Woocommerce_Admin extends MailChimp_Woocommerce_Options {

	/**
	 * @return MailChimp_Woocommerce_Admin
	 */
	public static function connect()
	{
		$env = mailchimp_environment_variables();

		return new self('mailchimp-woocommerce', $env->version);
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mailchimp-woocommerce-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mailchimp-woocommerce-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */

	public function add_plugin_admin_menu() {
		/*
         *  Documentation : http://codex.wordpress.org/Administration_Menus
         */
		add_options_page( 'MailChimp - WooCommerce Setup', 'MailChimp', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page'));
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links($links) {
		/*
        *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
        */
		$settings_link = array(
			'<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __('Settings', $this->plugin_name) . '</a>',
		);

		return array_merge($settings_link, $links);
	}

	/**
	 * Admin bar
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function admin_bar( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$wp_admin_bar->add_menu( array(
			'id'    => 'mailchimp-woocommerce',
			'title' => __('MailChimp', 'mailchimp-woocommerce' ),
			'href'  => '#',
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'mailchimp-woocommerce',
			'id'     => 'mailchimp-woocommerce-api-key',
			'title'  => __('API Key', 'mailchimp-woocommerce' ),
			'href'   => wp_nonce_url(admin_url('options-general.php?page=mailchimp-woocommerce&tab=api_key'), 'mc-api-key'),
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'mailchimp-woocommerce',
			'id'     => 'mailchimp-woocommerce-store-info',
			'title'  => __('Store Info', 'mailchimp-woocommerce' ),
			'href'   => wp_nonce_url(admin_url('options-general.php?page=mailchimp-woocommerce&tab=store_info'), 'mc-store-info'),
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'mailchimp-woocommerce',
			'id'     => 'mailchimp-woocommerce-campaign-defaults',
			'title'  => __('Campaign Defaults', 'mailchimp-woocommerce' ),
			'href'   => wp_nonce_url(admin_url('options-general.php?page=mailchimp-woocommerce&tab=campaign_defaults'), 'mc-campaign-defaults'),
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'mailchimp-woocommerce',
			'id'     => 'mailchimp-woocommerce-newsletter-settings',
			'title'  => __('Newsletter Settings', 'mailchimp-woocommerce' ),
			'href'   => wp_nonce_url(admin_url('options-general.php?page=mailchimp-woocommerce&tab=newsletter_settings'), 'mc-newsletter-settings'),
		));

		// only display this button if the data is not syncing and we have a valid api key
		if ((bool) $this->getOption('mailchimp_list', false) && (bool) $this->getData('sync.syncing', false) === false) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'mailchimp-woocommerce',
				'id'     => 'mailchimp-woocommerce-sync',
				'title'  => __('Sync', 'mailchimp-woocommerce'),
				'href'   => wp_nonce_url(admin_url('?mailchimp-woocommerce[action]=sync&mailchimp-woocommerce[action]=sync'), 'mc-sync'),
			));
		}
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_setup_page() {
		include_once( 'partials/mailchimp-woocommerce-admin-tabs.php' );
	}

	/**
	 *
	 */
	public function options_update() {
		register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
	}

	/**
	 * @param $input
	 * @return array
	 */
	public function validate($input) {

		$active_tab = isset($input['mailchimp_active_tab']) ? $input['mailchimp_active_tab'] : null;

		if (empty($active_tab)) {
			return $this->getOptions();
		}

		switch ($active_tab) {

			case 'api_key':
				$data = $this->validatePostApiKey($input);
				break;

			case 'store_info':
				$data = $this->validatePostStoreInfo($input);
				break;

			case 'campaign_defaults' :
				$data = $this->validatePostCampaignDefaults($input);
				break;

			case 'newsletter_settings':
				$data = $this->validatePostNewsletterSettings($input);
				break;
		}

		return (isset($data) && is_array($data)) ? array_merge($this->getOptions(), $data) : $this->getOptions();
	}

	/**
	 * STEP 1.
	 *
	 * Handle the 'api_key' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostApiKey($input)
	{
		$data = array(
			'mailchimp_api_key' => isset($input['mailchimp_api_key']) ? $input['mailchimp_api_key'] : false,
		);

		$api = new MailChimpApi($data['mailchimp_api_key']);

		$valid = true;
		if (empty($data['mailchimp_api_key']) || !$api->ping()) {
			unset($data['mailchimp_api_key']);
			$valid = false;
		}

		// tell our reporting system whether or not we had a valid ping.
		$this->setData('validation.api.ping', $valid);

		return $data;
	}

	/**
	 * STEP 2.
	 *
	 * Handle the 'store_info' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostStoreInfo($input)
	{
		$data = array(

			// store basics
			'store_name' => isset($input['store_name']) ? $input['store_name'] : get_option('blogname'),
			'store_street' => isset($input['store_street']) ? $input['store_street'] : false,
			'store_city' => isset($input['store_city']) ? $input['store_city'] : false,
			'store_state' => isset($input['store_state']) ? $input['store_state'] : false,
			'store_postal_code' => isset($input['store_postal_code']) ? $input['store_postal_code'] : false,
			'store_country' => isset($input['store_country']) ? $input['store_country'] : false,
			'store_phone' => isset($input['store_phone']) ? $input['store_phone'] : false,

			// locale info
			'store_locale' => isset($input['store_locale']) ? $input['store_locale'] : false,
			'store_timezone' => isset($input['store_timezone']) ? $input['store_timezone'] : false,
			'store_currency_code' => isset($input['store_currency_code']) ? $input['store_currency_code'] : false,
		);

		if (!$this->hasValidStoreInfo($data)) {
			$this->setData('validation.store_info', false);
			return array();
		}

		$this->setData('validation.store_info', true);

		if ($this->hasValidMailChimpList()) {
			$this->syncStore(array_merge($this->getOptions(), $data));
		}

		return $data;
	}

	/**
	 * STEP 3.
	 *
	 * Handle the 'campaign_defaults' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostCampaignDefaults($input)
	{
		$data = array(
			'campaign_from_name' => isset($input['campaign_from_name']) ? $input['campaign_from_name'] : false,
			'campaign_from_email' => isset($input['campaign_from_email']) && is_email($input['campaign_from_email']) ? $input['campaign_from_email'] : false,
			'campaign_subject' => isset($input['campaign_subject']) ? $input['campaign_subject'] : get_option('blogname'),
			'campaign_language' => isset($input['campaign_language']) ? $input['campaign_language'] : 'en',
			'campaign_permission_reminder' => isset($input['campaign_permission_reminder']) ? $input['campaign_permission_reminder'] : 'You were subscribed to the newsletter from '.get_option('blogname'),
		);

		if (!$this->hasValidCampaignDefaults($data)) {
			$this->setData('validation.campaign_defaults', false);
			return array();
		}

		$this->setData('validation.campaign_defaults', true);

		return $data;
	}

	/**
	 * STEP 4.
	 *
	 * Handle the 'newsletter_settings' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostNewsletterSettings($input)
	{
		$data = array(
			'mailchimp_list' => isset($input['mailchimp_list']) ? $input['mailchimp_list'] : '',
			'newsletter_label' => isset($input['newsletter_label']) ? $input['newsletter_label'] : 'Subscribe to our newsletter',
			'notify_on_subscribe' => isset($input['notify_on_subscribe']) && is_email($input['notify_on_subscribe']) ? $input['notify_on_subscribe'] : false,
			'notify_on_unsubscribe' => isset($input['notify_on_unsubscribe']) && is_email($input['notify_on_unsubscribe']) ? $input['notify_on_unsubscribe'] : false,
		);

		if ($data['mailchimp_list'] === 'create_new') {
			$data['mailchimp_list'] = $this->createMailChimpList(array_merge($this->getOptions(), $data));
		}

		// as long as we have a list set, and it's currently in MC as a valid list, let's sync the store.
		if (!empty($data['mailchimp_list']) && $this->api()->hasList($data['mailchimp_list'])) {
			$this->syncStore(array_merge($this->getOptions(), $data));

			// start the sync automatically if the sync is false
			if ((bool) $this->getData('sync.started_at', false) === false) {
				$job = new MailChimp_WooCommerce_Process_Products();
				$job->flagStartSync();
				wp_queue($job);
			}
		}

		return $data;
	}

	/**
	 * @param null|array $data
	 * @return bool
	 */
	public function hasValidStoreInfo($data = null)
	{
		return $this->validateOptions(array(
			'store_name', 'store_street', 'store_city', 'store_state',
			'store_postal_code', 'store_country', 'store_phone',
			'store_locale', 'store_timezone', 'store_currency_code',
		), $data);
	}

	/**
	 * @param null|array $data
	 * @return bool
	 */
	public function hasValidCampaignDefaults($data = null)
	{
		return $this->validateOptions(array(
			'campaign_from_name', 'campaign_from_email', 'campaign_subject', 'campaign_language',
			'campaign_permission_reminder'
		), $data);
	}

	/**
	 * @param null|array $data
	 * @return bool
	 */
	public function hasValidApiKey($data = null)
	{
		if (!$this->validateOptions(array('mailchimp_api_key'), $data)) {
			return false;
		}

		if (($pinged = $this->getCached('api-ping-check', null)) === null) {
			if (($pinged = $this->api()->ping())) {
				$this->setCached('api-ping-check', true, 120);
			}
			return $pinged;
		}
		return $pinged;
	}

	/**
	 * @return bool
	 */
	public function hasValidMailChimpList()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		if (!($this->validateOptions(array('mailchimp_list')))) {
			return $this->api()->getLists(true);
		}

		return $this->api()->hasList($this->getOption('mailchimp_list'));
	}

	/**
	 * @return array|bool
	 */
	public function getMailChimpLists()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		try {
			if (($pinged = $this->getCached('api-lists', null)) === null) {
				$pinged = $this->api()->getLists(true);
				if ($pinged) {
					$this->setCached('api-lists', $pinged, 120);
				}
				return $pinged;
			}
			return $pinged;
		} catch (\Exception $e) {
			return array();
		}
	}

	/**
	 * @return bool
	 */
	public function isReadyForSync()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		if (!$this->getOption('mailchimp_list', false)) {
			return false;
		}

		if (!$this->api()->hasList($this->getOption('mailchimp_list'))) {
			return false;
		}

		if (!$this->api()->getStore($this->getUniqueStoreID())) {
			return false;
		}

		return true;
	}

    /**
     * @param $action
     */
    public function job($action)
    {
        switch ($action) {
            case 'lists';
                try {
                    print_r(array('getting_lists' => $this->api()->getLists(true)));die();
                } catch (\Exception $e) {
                    print $e->getMessage(); die();
                }
                break;

            case 'list_delete';
                try {
                    $list_id = isset($_GET['list_id']) ? $_GET['list_id'] : null;
                    print_r(array('deleting_list_by_id' => $this->api()->deleteList($list_id)));die();
                } catch (\Exception $e) {
                    print $e->getMessage(); die();
                }
                break;

            case 'stores_list';
                try {
                    print_r(array('getting_lists' => $this->api()->stores()));die();
                } catch (\Exception $e) {
                    print $e->getMessage(); die();
                }
                break;

            case 'store_get';

                try {
                    print_r(array('getting_store_by_id' => $this->api()->getStore($this->getUniqueStoreID())));die();
                } catch (\Exception $e) {
                    print $e->getMessage(); die();
                }
                break;

            case 'stores_delete':
                try {
                    print_r(array('deleting_store' => $this->api()->deleteStore($this->getUniqueStoreID())));die();
                } catch (\Exception $e) {
                    print $e->getMessage(); die();
                }
                break;

            case 'submit_order' :

                $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
                if (!empty($order_id)) {
                    $job = new MailChimp_WooCommerce_Single_Order($order_id);
                    print_r(array('submitting_single_order' => $job->handle()));die();
                }

                break;

            case 'delete_order' :

                $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
                if (!empty($order_id)) {
                    $response = $this->api()->deleteStoreOrder($this->getUniqueStoreID(), $order_id);
                    print_r(array('deleting_order' => array('handled delete store order' => $response)));die();
                }

                break;

            case 'stores_orders':
                $paging_limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
                $paging_page = isset($_GET['page']) ? $_GET['page'] : 1;
                print_r(array('getting_store_orders' => $this->api()->orders($this->getUniqueStoreID(), $paging_page, $paging_limit)));die();
                break;

            case 'stores_products':
                $paging_limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
                $paging_page = isset($_GET['page']) ? $_GET['page'] : 1;
                print_r(array('getting_store_products' => $this->api()->products($this->getUniqueStoreID(), $paging_page, $paging_limit)));die();
                break;

            case 'stores_carts':
                $paging_limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
                $paging_page = isset($_GET['page']) ? $_GET['page'] : 1;
                print_r(array('getting_store_carts' => $this->api()->carts($this->getUniqueStoreID(), $paging_page, $paging_limit)));die();
                break;

            case 'delete_cart':
                $cart_id = isset($_GET['cart_id']) ? $_GET['cart_id'] : null;
                print_r(array('deleting_cart_by_id' => $this->api()->deleteCartByID($this->getUniqueStoreID(), $cart_id)));die();
                break;

            case 'test_queue':

                $this->removeData('sync.completed_at');
                $this->removeData('sync.orders.completed_at');
                $this->removeData('sync.orders.current_page');

                $job = new MailChimp_WooCommerce_Process_Products();
                $job->flagStartSync();

                //$job = new MailChimp_WooCommerce_Process_Orders();
                //$job->go();

                wp_queue($job);
                print 'submitted store sync'; die();

                break;
        }
    }

	/**
	 * @param null|array $data
	 * @return bool|string
	 */
	private function createMailChimpList($data = null)
	{
		if (empty($data)) {
			$data = $this->getOptions();
		}

		$required = array(
			'store_name', 'store_street', 'store_city', 'store_state',
			'store_postal_code', 'store_country', 'campaign_from_name',
			'campaign_from_email', 'campaign_subject', 'campaign_permission_reminder',
		);

		foreach ($required as $requirement) {
			if (!isset($data[$requirement]) || empty($data[$requirement])) {
				return false;
			}
		}

		$submission = new MailChimp_CreateListSubmission();

		// allow the subscribers to choose preferred email type (html or text).
		$submission->setEmailTypeOption(true);

		// set the store name
		$submission->setName($data['store_name']);

		// set the campaign defaults
		$submission->setCampaignDefaults(
			$data['campaign_from_name'],
			$data['campaign_from_email'],
			$data['campaign_subject'],
			$data['campaign_language']
		);

		// set the permission reminder message.
		$submission->setPermissionReminder($data['campaign_permission_reminder']);

		if (isset($data['notify_on_subscribe']) && !empty($data['notify_on_subscribe'])) {
			$submission->setNotifyOnSubscribe($data['notify_on_subscribe']);
		}

		if (isset($data['notify_on_unsubscribe']) && !empty($data['notify_on_unsubscribe'])) {
			$submission->setNotifyOnUnSubscribe($data['notify_on_unsubscribe']);
		}

		$submission->setContact($this->address($data));

		try {
			$response = $this->api()->createList($submission);

			$list_id = array_key_exists('id', $response) ? $response['id'] : false;

			$this->setData('errors.mailchimp_list', false);

			return $list_id;

		} catch (MailChimp_Error $e) {
			$this->setData('errors.mailchimp_list', $e->getMessage());
			return false;
		}
	}

	/**
	 * @param null $data
	 * @return bool
	 */
	private function syncStore($data = null)
	{
		if (empty($data)) {
			$data = $this->getOptions();
		}

		$site_url = $this->getUniqueStoreID();

		$new = false;

		if (!($store = $this->api()->getStore($site_url))) {
			$new = true;
			$store = new MailChimp_Store();
		}

		$list_id = $this->array_get($data, 'mailchimp_list', false);
		$call = $new ? 'addStore' : 'updateStore';
		$time_key = $new ? 'store_created_at' : 'store_updated_at';

		$store->setId($site_url);
		$store->setPlatform('woocommerce');

		// set the locale data
		$store->setPrimaryLocale($this->array_get($data, 'store_locale', 'en'));
		$store->setTimezone($this->array_get($data, 'store_timezone', 'America\New_York'));
		$store->setCurrencyCode($this->array_get($data, 'store_currency_code', 'USD'));

		// set the basics
		$store->setName($this->array_get($data, 'store_name'));
		$store->setDomain(get_option('siteurl'));
		$store->setEmailAddress($this->array_get($data, 'campaign_from_email'));
		$store->setAddress($this->address($data));
		$store->setPhone($this->array_get($data, 'store_phone'));
		$store->setListId($list_id);

		try {
			// let's create a new store for this user through the API
			$this->api()->$call($store);

			// apply extra meta for store created at
			$this->setData('errors.store_info', false);
			$this->setData($time_key, time());

			return true;

		} catch (\Exception $e) {
			$this->setData('errors.store_info', $e->getMessage());
		}

		return false;
	}

	/**
	 * @param array $data
	 * @return MailChimp_Address
	 */
	private function address(array $data)
	{
		$address = new MailChimp_Address();

		if (isset($data['store_street']) && $data['store_street']) {
			$address->setAddress1($data['store_street']);
		}

		if (isset($data['store_city']) && $data['store_city']) {
			$address->setCity($data['store_city']);
		}

		if (isset($data['store_state']) && $data['store_state']) {
			$address->setProvince($data['store_state']);
		}

		if (isset($data['store_country']) && $data['store_country']) {
			$address->setCountry($data['store_country']);
		}

		if (isset($data['store_postal_code']) && $data['store_postal_code']) {
			$address->setPostalCode($data['store_postal_code']);
		}

		if (isset($data['store_name']) && $data['store_name']) {
			$address->setCompany($data['store_name']);
		}

		if (isset($data['store_phone']) && $data['store_phone']) {
			$address->setPhone($data['store_phone']);
		}

		$address->setCountryCode($this->array_get($data, 'store_currency_code', 'USD'));

		return $address;
	}

	/**
	 * @param array $required
	 * @param null $options
	 * @return bool
	 */
	private function validateOptions(array $required, $options = null)
	{
		$options = is_array($options) ? $options : $this->getOptions();

		foreach ($required as $requirement) {
			if (!isset($options[$requirement]) || empty($options[$requirement])) {
				return false;
			}
		}

		return true;
	}

}
