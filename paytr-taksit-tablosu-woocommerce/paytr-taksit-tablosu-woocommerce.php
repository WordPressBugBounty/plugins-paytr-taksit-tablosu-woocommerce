<?php
/**
 * Plugin Name: PayTR Installment Table WooCommerce
 * Plugin URI: https://wordpress.org/plugins/paytr-taksit-tablosu-woocommerce/
 * Description: The plugin that allows you to show the installment options of your PayTR store on the product page.
 * Version: 1.3.4
 * Author: PayTR Ödeme ve Elektronik Para Kuruluşu A.Ş.
 * Author URI: http://www.paytr.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: paytr-taksit-tablosu-woocommerce
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PAYTRTT_VERSION', '1.3.3');
define('PAYTRTT_PLUGIN_URL', untrailingslashit(plugins_url('/', __FILE__)));
define('PAYTRTT_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('PAYTRTT_MIN_WC_VER', '3.0');
define('PAYTRTT_MIN_WP_VER', '4.4');

function deactivate_paytrtt_plugin() {
    delete_option('woocommerce_paytrtaksit_product_tab_title');
    delete_option('woocommerce_paytrtaksit_merchant_id');
    delete_option('woocommerce_paytrtaksit_token');
    delete_option('woocommerce_paytrtaksit_max_installment');
    delete_option('woocommerce_paytrtaksit_extra_installment');
    delete_option('woocommerce_paytrtaksit_tax_included');
    delete_option('woocommerce_paytrtaksit_content_title');
    delete_option('woocommerce_paytrtaksit_description_top');
    delete_option('woocommerce_paytrtaksit_description_bottom');
}

function notice_paytrtt_wc_missing() {
    echo '<div class="error"><p>' . esc_html__('WooCommerce is required to be installed and active!', 'paytr-taksit-tablosu-woocommerce') . '</p></div>';
}

function notice_paytrtt_wc_not_supported() {
    echo '<div class="error"><p>' . sprintf(esc_html__('WooCommerce %1$s or greater version to be installed and active. WooCommerce %2$s is no longer supported.', 'paytr-taksit-tablosu-woocommerce'), PAYTRTT_MIN_WC_VER, WC_VERSION) . '</p></div>';
}

function paytr_installment_table_init() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'notice_paytrtt_wc_missing');
        return;
    }

    if (version_compare(WC_VERSION, PAYTRTT_MIN_WC_VER, '<')) {
        add_action('admin_notices', 'notice_paytrtt_wc_not_supported');
        return;
    }

    load_plugin_textdomain('paytr-taksit-tablosu-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (!class_exists('WC_PaytrInstallmentTable')) {
        class WC_PaytrInstallmentTable {
            private static $instance;
            protected $text_domain = 'paytr-taksit-tablosu-woocommerce';
            protected $page_title;
            protected $page_menu;
            protected $page_slug;
            public $paytr_token;
            public $paytr_merchant_id;
            protected $file;

            public static function get_instance() {
                if (null === self::$instance) {
                    self::$instance = new self();
                }
                return self::$instance;
            }

            private function __construct() {
                $this->file = __FILE__;
                $this->page_title = __('PayTR Installment Table WooCommerce', $this->text_domain);
                $this->page_menu = __('PayTR Installment Table', $this->text_domain);
                $this->page_slug = 'paytr-installment-table';

                $this->paytr_token = get_option('woocommerce_paytrtaksit_token');
                $this->paytr_merchant_id = get_option('woocommerce_paytrtaksit_merchant_id');

                add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links']);
                add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
                add_action('admin_menu', [$this, 'my_custom_menu_icon']);

                if (!empty($this->paytr_token) && !empty($this->paytr_merchant_id)) {
                    add_filter('woocommerce_product_tabs', [$this, 'paytr_installment_table_tab']);
                }
            }

            function plugin_action_links($links) {
                $plugin_links = [
                    '<a href="' . esc_url(admin_url('admin.php?page=' . $this->page_slug)) . '">' . esc_html__('Settings', $this->text_domain) . '</a>'
                ];
                return array_merge($plugin_links, $links);
            }

            function plugin_row_meta($links, $file) {
                if (plugin_basename(__FILE__) === $file) {
                    $row_meta = [
                        'support' => '<a href="' . esc_url('https://www.paytr.com/magaza/destek') . '" target="_blank" rel="noopener noreferrer">' . __('Support', $this->text_domain) . '</a>'
                    ];
                    return array_merge($links, $row_meta);
                }
                return $links;
            }

            function my_custom_menu_icon() {
				add_menu_page(
					$this->page_title,             // Sayfa Başlığı (Tarayıcı Sekmesi)
					$this->page_menu,              // Menü Başlığı (Sol Menüde Görünen)
					'manage_options',              // Yetki Seviyesi
					$this->page_slug,              // Menü Slug'ı
					[$this, 'init'],               // Callback Fonksiyon
					plugins_url('assets/img/dashboard-admin-icon.png', __FILE__), // İkon URL
					                             // Menü Pozisyonu (isteğe bağlı)
				);
			}

            function init() {
                if (!current_user_can('manage_woocommerce')) {
                    wp_die(__('Bu sayfaya erişim yetkiniz yok.', $this->text_domain));
                }

                register_setting(
                    'woocommerce_paytrtaksit',
                    'woocommerce_paytrtaksit_settings',
                    [$this, 'sanitize']
                );

                add_settings_section(
                    'paytrtaksit_section_id',
                    '',
                    [$this, 'print_section_info'],
                    $this->page_slug
                );

                add_settings_field(
                    'product_tab_title',
                    __('Product Tab Title', $this->text_domain),
                    [$this, 'field_callback_product_title'],
                    $this->page_slug,
                    'paytrtaksit_section_id',
                    ['label_for' => 'product_tab_title']
                );

                add_settings_field(
                    'merchant_id',
                    __('Merchant ID', $this->text_domain),
                    [$this, 'field_callback_merchant_id'],
                    $this->page_slug,
                    'paytrtaksit_section_id',
                    ['label_for' => 'merchant_id']
                );

                add_settings_field(
                    'token',
                    __('Token', $this->text_domain),
                    [$this, 'field_callback_token'],
                    $this->page_slug,
                    'paytrtaksit_section_id',
                    ['label_for' => 'token']
                );

                add_settings_field(
                    'max_installment',
                    __('Max Installment', $this->text_domain),
                    [$this, 'field_callback_max_installment'],
                    $this->page_slug,
                    'paytrtaksit_section_id',
                    ['label_for' => 'max_installment']
                );

                add_settings_field(
                    'extra_installment',
                    __('Advantageous Installment', $this->text_domain),
                    [$this, 'field_callback_extra_installment'],
                    $this->page_slug,
                    'paytrtaksit_section_id',
                    ['label_for' => 'extra_installment']
                );

                add_settings_field(
                    'tax_included',
                    __('Include Tax', $this->text_domain),
                    [$this, 'field_callback_tax_included'],
                    $this->page_slug,
                    'paytrtaksit_section_id',
                    ['label_for' => 'tax_included']
                );

                add_settings_field(
                    'content_title',
                    __('Content Title', $this->text_domain),
                    [$this, 'field_callback_content_title'],
                    $this->page_slug,
                    'paytrtaksit_section_id',
                    ['label_for' => 'content_title']
                );

                add_settings_field(
                    'description_top',
                    __('Top Description', $this->text_domain),
                    [$this, 'field_callback_description_top'],
                    $this->page_slug,
                    'paytrtaksit_section_id',
                    ['label_for' => 'description_top']
                );

                add_settings_field(
                    'description_bottom',
                    __('Bottom Description', $this->text_domain),
                    [$this, 'description_bottom_callback'],
                    $this->page_slug,
                    'paytrtaksit_section_id',
                    ['label_for' => 'description_bottom']
                );

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!isset($_POST['paytrtt_nonce']) || !wp_verify_nonce($_POST['paytrtt_nonce'], 'paytrtt_settings')) {
                        $this->displayAdminNotice(__('Güvenlik ihlali tespit edildi!', $this->text_domain), 'notice-error');
                        return;
                    }

                    $bool = false;
                    if (!empty($_POST['woocommerce_paytrtaksit_settings']['merchant_id']) && !empty($_POST['woocommerce_paytrtaksit_settings']['token'])) {
                        if (!is_numeric($_POST['woocommerce_paytrtaksit_settings']['merchant_id'])) {
                            $this->displayAdminNotice(__('Merchant ID must be numeric.', $this->text_domain), 'notice-error');
                        } else {
                            $bool = true;
                        }
                    } else {
                        $this->displayAdminNotice(__('Merchant ID and Token cannot be empty!', $this->text_domain), 'notice-error');
                    }

                    if ($bool) {
                        foreach ($_POST['woocommerce_paytrtaksit_settings'] as $key => $value) {
                            update_option(
                                'woocommerce_paytrtaksit_' . $key,
                                in_array($key, ['description_top', 'description_bottom']) 
                                    ? wp_kses_post($value) 
                                    : sanitize_text_field($value)
                            );
                        }
                        $this->displayAdminNotice(__('Ayarlar başarıyla kaydedildi.', $this->text_domain), 'notice-success');
                    }
                }

                $this->create_admin_page();
            }

            function create_admin_page() {
                $current_page = sanitize_text_field(isset($_GET['page']) ? $_GET['page'] : '');
                if ($current_page !== $this->page_slug) {
                    wp_die(__('Geçersiz sayfa erişimi.', $this->text_domain));
                }
                ?>
                <div class="wrap">
                    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . $this->page_slug)); ?>">
                        <?php 
                        wp_nonce_field('paytrtt_settings', 'paytrtt_nonce');
                        settings_fields('woocommerce_paytrtaksit');
                        do_settings_sections($this->page_slug);
                        submit_button();
                        ?>
                    </form>
                </div>
                <?php
            }

            function print_section_info() {
                if (!file_exists(PAYTRTT_PLUGIN_PATH . '/includes/admin/html-section-info.php')) {
                    echo '<div class="error"><p>Bilgi bölümü dosyası eksik!</p></div>';
                    return;
                }
                include_once PAYTRTT_PLUGIN_PATH . '/includes/admin/html-section-info.php';
            }

            function sanitize($input) {
                $output = [];
                foreach ($input as $key => $value) {
                    $output[$key] = in_array($key, ['description_top', 'description_bottom']) 
                        ? wp_kses_post($value) 
                        : sanitize_text_field($value);
                }
                return $output;
            }

            function paytr_installment_table_tab($tabs) {
                $default_title = __('Installment Table', $this->text_domain);
                $saved_title = get_option('woocommerce_paytrtaksit_product_tab_title');
                
                if (!empty($saved_title)) {
                    $default_title = $saved_title;
                }

                $tabs['paytr_installment'] = [
                    'title'    => esc_html($default_title),
                    'priority' => 50,
                    'callback' => [$this, 'paytr_installment_table_content']
                ];
                
                return $tabs;
            }

            function paytr_installment_table_content() {
                global $product;
                $product = wc_get_product(get_the_ID());
                
                if (!$product) {
                    echo '<p>'.__('Ürün bulunamadı.', $this->text_domain).'</p>';
                    return;
                }
                echo '<div type="button" id="updateInstallmentsButton"><center>Taksitleri Güncelle</center></div><p><p>';

                $op_content_title = get_option('woocommerce_paytrtaksit_content_title');
                $op_description_top = get_option('woocommerce_paytrtaksit_description_top');
                $op_description_bottom = get_option('woocommerce_paytrtaksit_description_bottom');
                $op_tax_included = get_option('woocommerce_paytrtaksit_tax_included');

                $unit_price = $op_tax_included 
                    ? wc_get_price_including_tax($product) 
                    : wc_get_price_excluding_tax($product);

                wp_enqueue_style('paytr_installment_table_style', PAYTRTT_PLUGIN_URL . '/assets/css/style.css');
                wp_enqueue_script('paytr_installment_table_js', PAYTRTT_PLUGIN_URL . '/assets/js/paytr.js');

                $js_data = [
                    'unit_price' => $unit_price,
                    'paytr_merchant_id' => $this->paytr_merchant_id,
                    'paytr_token' => $this->paytr_token,
                    'paytr_max_installment' => get_option('woocommerce_paytrtaksit_max_installment'),
                    'paytr_extra_installment' => get_option('woocommerce_paytrtaksit_extra_installment')
                ];
                wp_localize_script('paytr_installment_table_js', 'paytr_object', $js_data);

                if (!empty($op_content_title)) {
                    echo '<h2>' . esc_html($op_content_title) . '</h2>';
                }

                if (!empty($op_description_top)) {
                    echo '<div class="paytr-installment-table-description-top">' . wp_kses_post($op_description_top) . '</div>';
                }

                echo '<div id="paytrInstallmentTableContent"><div id="paytr_taksit_tablosu"></div></div>';

                if (!empty($op_description_bottom)) {
                    echo '<div class="paytr-installment-table-description-bottom">' . wp_kses_post($op_description_bottom) . '</div>';
                }
                wp_enqueue_script(
                    'paytr_installment_table_js',
                    PAYTRTT_PLUGIN_URL . '/assets/js/paytr.js',
                    ['jquery'], // Sadece jQuery'ye bağımlı yapın
                    filemtime(PAYTRTT_PLUGIN_PATH . '/assets/js/paytr.js'),
                    true
                  );
            }

            function displayAdminNotice($message, $noticeLevel) {
                echo '<div class="notice ' . esc_attr($noticeLevel) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }

            // Field Callback Fonksiyonları
            function field_callback_product_title() {
                $option = get_option('woocommerce_paytrtaksit_product_tab_title');
                printf(
                    '<input type="text" class="regular-text" id="product_tab_title" name="woocommerce_paytrtaksit_settings[product_tab_title]" value="%s" />',
                    esc_attr($option)
                );
                echo '<p class="description">' . __('The default value is <strong>Installment Table</strong>.', $this->text_domain) . '</p>';
            }

            function field_callback_merchant_id() {
                $option = get_option('woocommerce_paytrtaksit_merchant_id');
                printf(
                    '<input type="text" class="regular-text" id="merchant_id" name="woocommerce_paytrtaksit_settings[merchant_id]" value="%s" maxlength="6" />',
                    esc_attr($option)
                );
            }

            function field_callback_token() {
                $option = get_option('woocommerce_paytrtaksit_token');
                printf(
                    '<input type="text" class="regular-text" id="token" name="woocommerce_paytrtaksit_settings[token]" value="%s" maxlength="64" />',
                    esc_attr($option)
                );
            }

            function field_callback_max_installment() {
                $option = get_option('woocommerce_paytrtaksit_max_installment');
                ?>
                <select name="woocommerce_paytrtaksit_settings[max_installment]" id="max_installment" class="regular-text">
                    <?php for($i=0; $i<=12; $i++): ?>
                        <?php if($i == 1) continue; ?>
                        <option value="<?php echo $i; ?>" <?php selected($option, $i); ?>>
                            <?php 
                            if($i == 0) {
                                _e('All Installment Options', $this->text_domain);
                            } else {
                                printf(__('Up To %s Installment', $this->text_domain), $i);
                            }
                            ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <p class="description"><?php _e('You can choose the maximum number of installments you want to show.', $this->text_domain); ?></p>
                <?php
            }

            function field_callback_extra_installment() {
                $option = get_option('woocommerce_paytrtaksit_extra_installment');
                ?>
                <select name="woocommerce_paytrtaksit_settings[extra_installment]" id="extra_installment" class="regular-text">
                    <option value="0" <?php selected($option, 0); ?>><?php _e('Advantageous Installments', $this->text_domain); ?></option>
                    <option value="1" <?php selected($option, 1); ?>><?php _e('All Installments', $this->text_domain); ?></option>
                </select>
                <?php
            }

            function field_callback_tax_included() {
                $option = get_option('woocommerce_paytrtaksit_tax_included');
                ?>
                <select name="woocommerce_paytrtaksit_settings[tax_included]" id="tax_included" class="regular-text">
                    <option value="1" <?php selected($option, 1); ?>><?php _e('Enabled', $this->text_domain); ?></option>
                    <option value="0" <?php selected($option, 0); ?>><?php _e('Disabled', $this->text_domain); ?></option>
                </select>
                <?php
            }

            function field_callback_content_title() {
                $option = get_option('woocommerce_paytrtaksit_content_title');
                printf(
                    '<input type="text" class="regular-text" id="content_title" name="woocommerce_paytrtaksit_settings[content_title]" value="%s" />',
                    esc_attr($option)
                );
            }

            function field_callback_description_top() {
                $option = get_option('woocommerce_paytrtaksit_description_top');
                echo '<p class="description">' . __('Add content above of the installment table.', $this->text_domain) . '</p>';
                wp_editor($option, 'description_top', [
                    'textarea_name' => 'woocommerce_paytrtaksit_settings[description_top]',
                    'media_buttons' => false,
                    'teeny' => true
                ]);
            }

            function description_bottom_callback() {
                $option = get_option('woocommerce_paytrtaksit_description_bottom');
                echo '<p class="description">' . __('Add content under the installment table.', $this->text_domain) . '</p>';
                wp_editor($option, 'description_bottom', [
                    'textarea_name' => 'woocommerce_paytrtaksit_settings[description_bottom]',
                    'media_buttons' => false,
                    'teeny' => true
                ]);
            }
        }

        WC_PaytrInstallmentTable::get_instance();
    }
}

add_action('plugins_loaded', 'paytr_installment_table_init');
register_deactivation_hook(__FILE__, 'deactivate_paytrtt_plugin');