<?php
/**
 * Plugin Name: PayTR Installment Table WooCommerce
 * Plugin URI: https://wordpress.org/plugins/paytr-taksit-tablosu-woocommerce/
 * Description: The plugin that allows you to show the installment options of your PayTR store on the product page.
 * Version: 1.3.2
 * Author: PayTR Ödeme ve Elektronik Para Kuruluşu A.Ş.
 * Author URI: http://www.paytr.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: paytr-taksit-tablosu-woocommerce
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
};

define( 'PAYTRTT_VERSION', '1.3.2' );
define( 'PAYTRTT_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'PAYTRTT_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PAYTRTT_MIN_WC_VER', '3.0' );
define( 'PAYTRTT_MIN_WP_VER', '4.4' );

function deactivate_paytrtt_plugin() {
	delete_option( 'woocommerce_paytrtaksit_product_tab_title' );
	delete_option( 'woocommerce_paytrtaksit_merchant_id' );
	delete_option( 'woocommerce_paytrtaksit_token' );
	delete_option( 'woocommerce_paytrtaksit_max_installment' );
	delete_option( 'woocommerce_paytrtaksit_extra_installment' );
	delete_option( 'woocommerce_paytrtaksit_tax_included' );
	delete_option( 'woocommerce_paytrtaksit_content_title' );
	delete_option( 'woocommerce_paytrtaksit_description_top' );
	delete_option( 'woocommerce_paytrtaksit_description_bottom' );
}

function notice_paytrtt_wc_missing() {
	echo '<div class="error"><p>' . esc_html__( 'WooCommerce is required to be installed and active!', 'paytr-taksit-tablosu-woocommerce' ) . '</p></div>';
}

function notice_paytrtt_wc_not_supported() {
	echo '<div class="error"><p>' . sprintf( esc_html__( 'WooCommerce %1$s or greater version to be installed and active. WooCommerce %2$s is no longer supported.', 'paytr-taksit-tablosu-woocommerce' ), PAYTRTT_MIN_WC_VER, WC_VERSION ) . '</p></div>';
}

function paytr_installment_table_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'notice_paytrtt_wc_missing' );

		return;
	}

	if ( version_compare( WC_VERSION, PAYTRTT_MIN_WC_VER, '<' ) ) {
		add_action( 'admin_notices', 'notice_paytrtt_wc_not_supported' );

		return;
	}

	load_plugin_textdomain( 'paytr-taksit-tablosu-woocommerce', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WC_PaytrInstallmentTable' ) ) {
		class WC_PaytrInstallmentTable {
			private static $instance;


			protected $text_domain = 'paytr-taksit-tablosu-woocommerce';
			protected $page_title;
			protected $page_menu;
			protected $page_slug;

			public $paytr_token;
			public $paytr_merchant_id;
            protected string $file;

            public static function get_instance() {
				if ( NULL == self::$instance ) {
					self::$instance = new self();
				}

				return self::$instance;
			}

			private function __construct() {
				$this->file = __FILE__;

				$this->page_title = __( 'PayTR Installment Table WooCommerce', $this->text_domain );
				$this->page_menu  = __( 'PayTR Installment Table', $this->text_domain );
				$this->page_slug  = __( 'paytr-installment-table', $this->text_domain );

				$this->paytr_token       = get_option( 'woocommerce_paytrtaksit_token' );
				$this->paytr_merchant_id = get_option( 'woocommerce_paytrtaksit_merchant_id' );

				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [
					$this,
					'plugin_action_links'
				] );
				add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

                add_action('admin_menu', [$this, 'my_custom_menu_icon']);

				if ( isset( $this->paytr_token ) && ! empty( $this->paytr_token ) && isset( $this->paytr_merchant_id ) && ! empty( $this->paytr_merchant_id ) ) {
					add_filter( 'woocommerce_product_tabs', [ $this, 'paytr_installment_table_tab' ] );
				}
			}

			/**
			 * @param $links
			 *
			 * @return array
			 */
			function plugin_action_links( $links ) {
				$plugin_links = [ '<a href="' . admin_url( 'admin.php?page=' . __( 'paytr-installment-table', $this->text_domain ) ) . '">' . esc_html__( 'Settings', $this->text_domain ) . '</a>' ];

				return array_merge( $plugin_links, $links );
			}

            /**
			 * @param $links
			 * @param $file
			 *
			 * @return array
			 */
			function plugin_row_meta( $links, $file ) {
				if ( plugin_basename( __FILE__ ) === $file ) {
					$row_meta = [
						'support' => '<a href="' . esc_url( apply_filters( 'paytrtt_support_url', 'https://www.paytr.com/magaza/destek' ) ) . '" target="_blank">' . __( 'Support', $this->text_domain ) . '</a>'
					];

					return array_merge( $links, $row_meta );
				}

				return (array) $links;
			}

            function my_custom_menu_icon() {
                add_menu_page(
                    $this->page_title,
                    $this->page_menu,
                    'manage_options',
                    $this->page_slug,
                    [$this, 'init'],
                    'dashicons-table-col-before'
                );
            }

			/**
			 * Admin Form
			 */
			function init() {
				register_setting(
					'woocommerce_paytrtaksit_settings',
					'woocommerce_paytrtaksit_settings',
					[ $this, 'sanitize' ]
				);

				add_settings_section(
					'paytrtaksit_section_id',
					'',
					[ $this, 'print_section_info' ],
					esc_html( $this->page_slug )
				);

				add_settings_field(
					'product_tab_title',
					__( 'Product Tab Title', $this->text_domain ),
					[ $this, 'field_callback_product_title' ],
					esc_html( $this->page_slug ),
					'paytrtaksit_section_id',
					[ 'label_for' => 'product_tab_title' ]
				);

				add_settings_field(
					'merchant_id',
					__( 'Merchant ID', $this->text_domain ),
					[ $this, 'field_callback_merchant_id' ],
					esc_html( $this->page_slug ),
					'paytrtaksit_section_id',
					[ 'label_for' => 'merchant_id' ]
				);

				add_settings_field(
					'token',
					__( 'Token', $this->text_domain ),
					[ $this, 'field_callback_token' ],
					esc_html( $this->page_slug ),
					'paytrtaksit_section_id',
					[ 'label_for' => 'token' ]
				);

				add_settings_field(
					'max_installment',
					__( 'Max Installment', $this->text_domain ),
					[ $this, 'field_callback_max_installment' ],
					esc_html( $this->page_slug ),
					'paytrtaksit_section_id',
					[ 'label_for' => 'max_installment' ]
				);

				add_settings_field(
					'extra_installment',
					__( 'Advantageous Installment', $this->text_domain ),
					[ $this, 'field_callback_extra_installment' ],
					esc_html( $this->page_slug ),
					'paytrtaksit_section_id',
					[ 'label_for' => 'extra_installment' ]
				);

				add_settings_field(
					'tax_included',
					__( 'Include Tax', $this->text_domain ),
					[ $this, 'field_callback_tax_included' ],
					esc_html( $this->page_slug ),
					'paytrtaksit_section_id',
					[ 'label_for' => 'tax_included' ]
				);

				add_settings_field(
					'content_title',
					__( 'Content Title', $this->text_domain ),
					[ $this, 'field_callback_content_title' ],
					esc_html( $this->page_slug ),
					'paytrtaksit_section_id',
					[ 'label_for' => 'content_title' ]
				);

				add_settings_field(
					'description_top',
					__( 'Top Description', $this->text_domain ),
					[ $this, 'field_callback_description_top' ],
					esc_html( $this->page_slug ),
					'paytrtaksit_section_id',
					[ 'label_for' => 'description_top' ]
				);

				add_settings_field(
					'description_bottom',
					__( 'Bottom Description', $this->text_domain ),
					[ $this, 'description_bottom_callback' ],
					esc_html( $this->page_slug ),
					'paytrtaksit_section_id',
					[ 'label_for' => 'description_bottom' ]
				);

				if ( isset( $_POST['woocommerce_paytrtaksit_settings'] ) ) {
					$bool = FALSE;

					// Merchant ID Validate
					if ( ! empty( $_POST['woocommerce_paytrtaksit_settings']['merchant_id'] ) && isset( $_POST['woocommerce_paytrtaksit_settings']['merchant_id'] ) && ! empty( $_POST['woocommerce_paytrtaksit_settings']['token'] ) && isset( $_POST['woocommerce_paytrtaksit_settings']['token'] ) ) {
						if ( ! intval( $_POST['woocommerce_paytrtaksit_settings']['merchant_id'] ) ) {
							$this->displayAdminNotice( __( 'Merchant ID must be numeric.', $this->text_domain ), 'notice-error' );
						} else {
							$bool = TRUE;
						}
					} else {
						$this->displayAdminNotice( __( 'Merchant ID and Token cannot empty!', $this->text_domain ), 'notice-error' );
					}

					if ( $bool ) {
						foreach ( $_POST['woocommerce_paytrtaksit_settings'] as $key => $value ) {
							if ( $key == 'description_top' || $key == 'description_bottom' ) {
								update_option( 'woocommerce_paytrtaksit_' . $key, wp_kses_post( $value ) );
							} else {
								update_option( 'woocommerce_paytrtaksit_' . $key, sanitize_text_field( $value ) );
							}
						}
						$this->displayAdminNotice( __( 'Saved Successfully.', $this->text_domain ), 'notice-success' );
					}
				}

				$this->create_admin_page();
			}

			/**
			 * Admin Page
			 */
			function create_admin_page() {
				?>
                <div class="wrap">
                    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
                    <form action="" method="post">
						<?php
						settings_fields( 'woocommerce_paytrtaksit' );
						do_settings_sections( $this->page_slug );
						submit_button();
						?>
                    </form>
                </div>
				<?php
			}

			function print_section_info() {
				include_once PAYTRTT_PLUGIN_PATH . '/includes/admin/html-section-info.php';
			}

			function displayAdminNotice( $message, $noticeLevel ) {
				echo wp_kses_normalize_entities( "<div class='notice " . sanitize_text_field( $noticeLevel ) . " is - dismissible'><p>" . sanitize_text_field( $message ) . "</p></div>", TRUE );
			}

			/**
			 * Form Fields
			 */
			function field_callback_product_title() {
				$option_title = get_option( 'woocommerce_paytrtaksit_product_tab_title' );

				printf(
					'<input type="text" class="regular-text" id="product_tab_title" name="woocommerce_paytrtaksit_settings[product_tab_title]" value="%s" />',
					isset( $option_title ) ? esc_attr( $option_title ) : ''
				);
				echo wp_kses_normalize_entities( '<p class="description">' . __( 'The default value is <strong>Installment Table</strong>.', $this->text_domain ) . '</p>' );
			}

			function field_callback_merchant_id() {
				$option_merchant_id = get_option( 'woocommerce_paytrtaksit_merchant_id' );

				printf(
					'<input type="text" maxlength="6" class="regular-text" id="merchant_id" name="woocommerce_paytrtaksit_settings[merchant_id]" value="%s" />',
					isset( $option_merchant_id ) ? esc_attr( $option_merchant_id ) : ''
				);
			}

			function field_callback_token() {
				$option_token = get_option( 'woocommerce_paytrtaksit_token' );

				printf(
					'<input type="text" maxlength="64" class="regular-text" id="token" name="woocommerce_paytrtaksit_settings[token]" value="%s" />',
					isset( $option_token ) ? esc_attr( $option_token ) : ''
				);
			}

			function field_callback_max_installment() {
				$option_max_installment = get_option( 'woocommerce_paytrtaksit_max_installment' );
				?>

                <select name="woocommerce_paytrtaksit_settings[max_installment]" id="max_installment"
                        class="regular-text">
					<?php
					for ( $i = 0; $i <= 12; $i ++ ) {
						if ( $i == 1 ) {
							continue;
						}

						if ( $option_max_installment == $i ) {

							if ( $i == 0 ) {
								printf( '<option value="%s" selected>' . __( 'All Installment Options', $this->text_domain ) . '</option>', $i );
							} else {
								printf( '<option value="%1$s" selected>' . __( 'Up To %2$s Installment', $this->text_domain ) . '</option>', $i, $i );
							}

						} else {
							if ( $i == 0 ) {
								printf( '<option value="%s">' . __( 'All Installment Options', $this->text_domain ) . '</option>', $i );
							} else {
								printf( '<option value="%1$s">' . __( 'Up To %2$s Installment', $this->text_domain ) . '</option>', $i, $i );
							}
						}
					}
					?>
                </select>

				<?php
				echo wp_kses_normalize_entities( '<p class="description">' . __( 'You can choose the maximum number of installments you want to show.', $this->text_domain ) . '</p>' );
			}

			function field_callback_extra_installment() {
				$option_extra_installment = get_option( 'woocommerce_paytrtaksit_extra_installment' );

				?>
                <select class="regular-text" id="extra_installment"
                        name="woocommerce_paytrtaksit_settings[extra_installment]">
                    <option value="0" <?php echo $option_extra_installment == 0 ? "selected" : "" ?>><?php echo __( 'Advantageous Installments', $this->text_domain ); ?></option>
                    <option value="1" <?php echo $option_extra_installment == 1 ? "selected" : "" ?>><?php echo __( 'All Installments', $this->text_domain ) ?>
                    </option>
                </select>
				<?php
			}

			function field_callback_tax_included() {
				$option_tax_included = get_option( 'woocommerce_paytrtaksit_tax_included' );

				?>
                <select name="woocommerce_paytrtaksit_settings[tax_included]" id="tax_included" class="regular-text">
					<?php
					if ( $option_tax_included == 1 ) {
						echo '<option value="1" selected> ' . __( 'Enabled', $this->text_domain ) . '</option > ';
						echo '<option value="0"> ' . __( 'Disabled', $this->text_domain ) . ' </option>';
					} else {
						echo '<option value="1"> ' . __( 'Enabled', $this->text_domain ) . '</option > ';
						echo '<option value="0" selected> ' . __( 'Disabled', $this->text_domain ) . ' </option>';
					}
					?>
                </select>
				<?php
			}

			function field_callback_content_title() {
				$option_title = get_option( 'woocommerce_paytrtaksit_content_title' );

				printf(
					' <input type="text" class="regular-text" id="content_title" name="woocommerce_paytrtaksit_settings[content_title]" value ="%s" />',
					isset( $option_title ) ? esc_attr( $option_title ) : ''
				);
			}

			function field_callback_description_top() {
				$option_description_top = get_option( 'woocommerce_paytrtaksit_description_top' );

				echo wp_kses_normalize_entities( ' <p class="description"> ' . __( 'Add content above of the installment table.', $this->text_domain ) . ' </p> ' );
				echo wp_editor( $option_description_top, 'description_top', [ 'textarea_name' => 'woocommerce_paytrtaksit_settings[description_top]' ] ) . "</br>";

			}

			function description_bottom_callback() {
				$paytrOptionsDescription = get_option( 'woocommerce_paytrtaksit_description_bottom' );

				echo wp_kses_normalize_entities( ' <p class="description"> ' . __( 'Add content under the installment table. ', $this->text_domain ) . ' </p > ' );
				echo wp_editor( $paytrOptionsDescription, 'description_bottom', [ 'textarea_name' => 'woocommerce_paytrtaksit_settings[description_bottom]' ] ) . "</br>";
			}

			/**
			 * Form Sanitize
			 *
			 * @param $input
			 */
			function sanitize( $input ) {
				$output = [];

				foreach ( $input as $key ) {
					if ( isset( $input[ $key ] ) ) {
						$output[ $key ] = sanitize_text_field( $input[ $key ] );
					}
				}
				print_r( $output );
			}

			/**
			 * WooCommerce Product Tab Settings
			 *
			 * @param $tabs
			 *
			 * @return mixed
			 */
			function paytr_installment_table_tab( $tabs ) {
				$default_tab_title        = __( 'Installment Table', $this->text_domain );
				$option_product_tab_title = get_option( 'woocommerce_paytrtaksit_product_tab_title' );

				if ( $option_product_tab_title != '' || $option_product_tab_title != NULL ) {
					$default_tab_title = $option_product_tab_title;
				}

				$tabs['paytr_installment_tab_arr'] = [
					'title'    => __( $default_tab_title ),
					'priority' => __( 50 ),
					'callback' => [ $this, 'paytr_installment_table_content' ]
				];

				return $tabs;
			}

			/**
			 * WooCommerce Product Tab Content
			 */
			function paytr_installment_table_content() {
				// Get Options
				$op_content_title      = get_option( 'woocommerce_paytrtaksit_content_title' );
				$op_description_top    = get_option( 'woocommerce_paytrtaksit_description_top' );
				$op_description_bottom = get_option( 'woocommerce_paytrtaksit_description_bottom' );
				$op_tax_included       = get_option( 'woocommerce_paytrtaksit_tax_included' );
				$op_merchant_id        = get_option( 'woocommerce_paytrtaksit_merchant_id' );
				$op_token              = get_option( 'woocommerce_paytrtaksit_token' );
				$op_max_installment    = get_option( 'woocommerce_paytrtaksit_max_installment' );
				$op_extra_installment  = get_option( 'woocommerce_paytrtaksit_extra_installment' );

				// Get Product
				$product = new WC_Product( get_the_ID() );

				if ( $op_tax_included ) {
					$price = wc_get_price_including_tax( $product );
				} else {
					$price = wc_get_price_excluding_tax( $product );
				}

				// Register Style
				wp_register_style( 'paytr_installment_table_style', PAYTRTT_PLUGIN_URL . "/assets/css/style.css", FALSE, '1.0', 'all' );
				wp_enqueue_style( 'paytr_installment_table_style' );

				// Register Script
				wp_register_script( 'paytr_installment_table_js', PAYTRTT_PLUGIN_URL . '/assets/js/paytr.js' );
				$paytr_js_array = [
					'paytr_product_price'     => $price,
					'paytr_merchant_id'       => $op_merchant_id,
					'paytr_token'             => $op_token,
					'paytr_max_installment'   => $op_max_installment,
					'paytr_extra_installment' => $op_extra_installment
				];
				wp_localize_script( 'paytr_installment_table_js', 'paytr_object', $paytr_js_array );
				wp_enqueue_script( 'paytr_installment_table_js' );

				if ( $op_content_title != '' || isset( $op_content_title ) ) {
					echo '<h2>' . $op_content_title . '</h2>';
				}

				if ( ! empty( $op_description_top ) ) {
					echo '<div class="paytr-installment-table-description-top">' . $op_description_top . '</div>';
				}

				echo '<div id="paytrInstallmentTableContent"><div id="paytr_taksit_tablosu"></div></div>';

				if ( ! empty( $op_description_bottom ) ) {
					echo '<div class="paytr-installment-table-description-top">' . $op_description_bottom . '</div>';
				}
			}
		}
	}

	WC_PaytrInstallmentTable::get_instance();
}

add_action( 'plugins_loaded', 'paytr_installment_table_init' );
register_deactivation_hook( __FILE__, 'deactivate_paytrtt_plugin' );