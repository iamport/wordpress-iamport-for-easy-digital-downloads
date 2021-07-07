<?php
/**
* Plugin Name: 아임포트 결제플러그인 for Easy Digital Downloads (국내 모든 PG를 한 번에)
* Plugin URI: http://www.iamport.kr
* Description: Easy Digital Downloads 를 위한 아임포트 결제 플러그인 ( 신용카드 / 실시간계좌이체 / 가상계좌 / 휴대폰소액결제 - 에스크로포함 )
* Version: 1.1.0
* Author: SIOT, Shoplic
* Contributors: movingcart,shoplic
* Author URI: https://www.iamport.kr
**/

class EddIamportGateway {

	const CUSTOMER_UID_META_KEY = '_edd_customer_uid';
	const CUSTOMER_UID_CANDIDATE_META_KEY = '_edd_customer_uid_candidate';

	private $gateways = array();
	private $methods = array(
		'card',
		'trans',
		'samsung',
		'kakao',
        'naverpay',
		'phone',
		'subscription',
	);

	public function plugin_not_installed() {
		$class = 'notice notice-error';
		$message = 'EasyDigitalDownload 플러그인이 비활성화되어, "아임포트 결제플러그인 for Easy Digital Downloads"을 사용하실 수 없습니다.';

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public function init() {
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			add_action( 'admin_notices', array($this, 'plugin_not_installed') );
			return null;
		}

		$this->gateways = $this->load_gateways($this->methods);

		add_filter( 'edd_payment_gateways', array($this, 'register_iamport_gateways') );
		add_filter( 'edd_currencies', array($this, 'korean_currency') );
		add_filter( 'edd_currency_decimal_count', array($this, 'korean_decimal_count'), 10, 2 );
		add_filter( 'edd_krw_currency_filter_before', array($this, 'korean_symbol'), 10, 3);
		add_action( 'wp_enqueue_scripts', array($this, 'iamport_enqueue_scripts') );
		add_filter( 'edd_get_purchase_summary', array($this, 'iamport_purchase_summary'), 10, 2 );

		add_action( 'edd_update_payment_status', array($this, 'process_refund'), 100, 3 );

		/* 사용자 추가정보에 전화번호 필드 추가 */
		add_filter( 'edd_purchase_form_required_fields', array($this, 'iamport_edd_required_checkout_fields') );
		add_action( 'edd_purchase_form_user_info_fields', array($this, 'iamport_edd_display_checkout_fields') );
		add_action( 'edd_checkout_error_checks', array($this, 'iamport_edd_validate_checkout_fields'), 10, 2 );
		add_filter( 'edd_payment_meta', array($this, 'iamport_edd_store_custom_fields') );
		add_action( 'edd_payment_personal_details_list', array($this, 'iamport_edd_view_order_details'), 10, 2 );

		edd_add_email_tag( 'phone', '구매자 전화번호', array($this, 'iamport_edd_email_tag_phone') );

		/* iamport 가상계좌 입금대기중 status 추가 */
		add_filter( 'edd_payment_statuses', array($this, 'iamport_add_edd_payment_statuses') );
		add_action( 'init', array($this, 'iamport_register_post_type_statuses') );
		add_filter( 'edd_payments_table_views', array($this, 'iamport_edd_payments_new_views') );
		add_filter( 'edd_payments_table_bulk_actions', array($this, 'iamport_edd_bulk_status_dropdown') );
		add_action( 'edd_payments_table_do_bulk_action', array($this, 'iamport_edd_bulk_status_action'), 10, 2 );

		add_filter( 'edd_get_earnings_by_date_args', array($this, 'iamport_edd_earnings_reporting_args') );
		add_filter( 'edd_get_sales_by_date_args', array($this, 'iamport_edd_sales_reporting_args') );

		/* mobile 결제 m_redirect_url처리 */
		add_action( 'init', array($this, 'iamport_confirm_payment') );

		/* 설정관련 Tab */
		add_filter( 'edd_settings_tabs', array($this, 'iamport_settings_tabs') );
		add_action( 'admin_init', array($this, 'iamport_register_settings') );

		/* 0원 결제 gateway태우기 위함 */
		add_filter( 'edd_chosen_gateway', array($this, 'chosen_gateway') );
        add_action( 'edd_purchase_form_after_cc_form', array($this, 'manual_cc_form') );
        add_filter( 'edd_purchase_data_before_gateway', array($this, 'purchase_data_before_gateway') );
	}

	public function korean_currency($currencies) {
		$currencies['KRW'] = '한국(&#8361;)';

		return $currencies;
	}

	public function korean_symbol($formatted, $currency, $price) {
		return "&#8361;$price";
	}

	public function korean_decimal_count($decimals, $currency) {
		if ( $currency === 'KRW' )	return 0;

		return $decimals;
	}

	public function chosen_gateway($chosen_gateway)
    {
        if ( edd_get_cart_subtotal() <= 0 ) {
            if (apply_filters('iamport_edd_subscription_allow_zero', false)) { //0원 정기결제 허용
                //function edd_get_chosen_gateway() 에서 manual 설정하는 로직만 제거함
                $gateways = edd_get_enabled_payment_gateways();
                $chosen   = isset( $_REQUEST['payment-mode'] ) ? $_REQUEST['payment-mode'] : false;

                if ( false !== $chosen ) {
                    $chosen = preg_replace('/[^a-zA-Z0-9-_]+/', '', $chosen );
                }

                if ( !empty($chosen)) {
                    $chosen_gateway = urldecode($chosen);

                    if( ! edd_is_gateway_active($chosen_gateway) ) {
                        $chosen_gateway = edd_get_default_gateway();
                    }
                } else {
                    $chosen_gateway = edd_get_default_gateway();
                }
            }
        }

        return $chosen_gateway;
    }

    public function manual_cc_form()
    {
        if( edd_get_cart_total() <= 0 ) {
            $payment_mode = edd_get_chosen_gateway();

            if (strpos($payment_mode, 'iamport') === false) { //iamport 관련 gateway 일 때에만
                return false;
            }

            if ( has_action( 'edd_' . $payment_mode . '_cc_form' ) ) {
                do_action( 'edd_' . $payment_mode . '_cc_form' );
            } else {
                do_action( 'edd_cc_form' );
            }
        }
    }

    public function purchase_data_before_gateway($purchase_data)
    {
        if ( !$purchase_data['price'] ) {
            $payment_mode = edd_get_chosen_gateway();

            if (strpos($payment_mode, 'iamport') === 0) { //iamport 관련 gateway 일 때에만
                $purchase_data['gateway'] = $payment_mode;
            }
        }

        return $purchase_data;
    }

	public function iamport_confirm_payment() {
		if ( isset($_GET['edd-iamport-landing']) ) {
			require_once(dirname(__FILE__).'/includes/lib/iamport.php');

			$imp_uid = $_GET['imp_uid'];
			$merchant_uid = $_GET['merchant_uid'];
			// $payment_id = $_GET['payment_id'];

			$edd_payment = edd_get_payment_by('key', $merchant_uid);
			if ( $edd_payment->status === 'completed' )		return edd_send_to_success_page();

			$payment_id = $edd_payment->ID;
			$gateway = $edd_payment->gateway;

			$imp_rest_key = edd_get_option( $gateway . '_rest_key' );
			$imp_rest_secret = edd_get_option( $gateway . '_rest_secret' );

			$iamport = new EddIamport($imp_rest_key, $imp_rest_secret);
			$result = $iamport->findByImpUID( $imp_uid );

			$payment_data = $result->data;
			if ( $result->success ) {
				if ( $merchant_uid === $payment_data->merchant_uid ) { //merchant_uid 가 변조된 것은 아닌지 체크

					if ( $payment_data->status == 'paid' ) {
						// once a transaction is successful, set the purchase to complete
						edd_update_payment_status($payment_id, 'complete');
						$this->_iamport_post_meta( $payment_id, '_edd_imp_uid', $payment_data->imp_uid );

						//결제진행시 사용된 customer_uid가 있으면 저장
						$customer_uid = edd_get_payment_meta( $payment_id, self::CUSTOMER_UID_CANDIDATE_META_KEY );

						if ( !empty( $customer_uid ) ) {
							$this->_iamport_post_meta( $payment_id, self::CUSTOMER_UID_META_KEY, $customer_uid );
						}

						// go to the success page
						return edd_send_to_success_page();
					} else if ( $payment_data->status == 'ready' && $payment_data->pay_method == 'vbank' ) {
						// 가상계좌 입금대기중
						$this->_iamport_post_meta( $payment_id, '_edd_imp_uid', $payment_data->imp_uid );
						$this->_iamport_post_meta( $payment_id, '_edd_imp_vbank_name', $payment_data->vbank_name );
						$this->_iamport_post_meta( $payment_id, '_edd_imp_vbank_num', $payment_data->vbank_num );
						$this->_iamport_post_meta( $payment_id, '_edd_imp_vbank_date', $payment_data->vbank_date );

						return edd_send_to_success_page();
					} else {
						$error_message = '결제에 실패하였습니다. ' . $payment_data->fail_reason;
						edd_set_error('iamport_fail', $error_message);
						edd_record_gateway_error( '결제실패', $error_message, $payment_id );
						edd_update_payment_status( $payment_id, 'failed' );
						edd_insert_payment_note( $payment_id, $error_message );
					}

				}
			} else {
				edd_set_error('iamport_fail', $result->error['message']);
				edd_record_gateway_error( '결제실패', $result->error['message'], $payment_id );
				edd_update_payment_status( $payment_id, 'failed' );
				edd_insert_payment_note( $payment_id, $result->error['message'] );
			}

			// 끝까지 왔으면 에러가 있다는 의미
			edd_send_back_to_checkout('?payment-mode=' . $gateway);
		}
	}

	public function iamport_purchase_summary($summary, $purchase_data) {
		if ( ! empty( $purchase_data['downloads'] ) ) {
			$numbers = count($purchase_data['downloads']);

			foreach ( $purchase_data['downloads'] as $download ) {
				$summary .= get_the_title( $download['id'] );
				break; //loop한 번만 돌고 강제로 빠져나감
			}

			if ( $numbers > 1 )	$summary .= sprintf(' 외 %d', $numbers-1);

			return mb_substr( $summary, 0, 14, 'UTF-8' ); //14글자 제한
		} else {
			return $purchase_data['user_email'];
		}
	}

	public function iamport_edd_display_checkout_fields() {
		$hide_phone = edd_get_option('iamport_hide_phone');
		if ( !$hide_phone ) {
		?>
		<p id="edd-phone-wrap">
			<label class="edd-label" for="edd-phone">
				전화번호
			</label>
			<span class="edd-description">
				구매하시는 분의 전화번호를 입력해주세요.
			</span>
			<input class="edd-input" type="tel" name="edd_phone" id="edd-phone" placeholder="전화번호" />
		</p>
		<?php
		}
	}

	public function iamport_edd_required_checkout_fields( $required_fields ) {
		$hide_phone = edd_get_option('iamport_hide_phone');

		if ( !$hide_phone ) {
			$required_fields = array(
				'edd_phone' => array(
					'error_id' => 'invalid_phone',
					'error_message' => '구매하시는 분의 전화번호를 입력해주세요.'
				),
			);
		}

		return $required_fields;
	}

	public function iamport_edd_validate_checkout_fields( $valid_data, $data ) {
		$hide_phone = edd_get_option('iamport_hide_phone');

		if ( !$hide_phone ) {
			if ( empty( $data['edd_phone'] ) ) {
				edd_set_error( 'invalid_phone', '구매하시는 분의 전화번호를 입력해주세요.' );
			}
		}
	}

	public function iamport_edd_store_custom_fields( $payment_meta ) {
		$payment_meta['phone'] = isset( $_POST['edd_phone'] ) ? sanitize_text_field( $_POST['edd_phone'] ) : '';

		return $payment_meta;
	}

	public function iamport_edd_view_order_details( $payment_meta, $user_info ) {
		$phone = isset( $payment_meta['phone'] ) ? $payment_meta['phone'] : 'none';
		?>
		<div class="column-container">
			<div class="column">
				<strong><?php echo 'Phone: '; ?></strong>
				<?php echo $phone; ?>
			</div>
			</div>
		<?php
	}

	public function iamport_edd_email_tag_phone( $payment_id ) {
		$payment_data = edd_get_payment_meta( $payment_id );

		return isset($payment_data['phone']) ? $payment_data['phone']:'';
	}

	// 가상계좌 입금대기중 관련 추가
	public function iamport_add_edd_payment_statuses( $payment_statuses ) {
		$payment_statuses['awaiting_vbank']	= '가상계좌 입금대기중';

		return $payment_statuses;
	}

	public function iamport_register_post_type_statuses() {
		// Payment Statuses
		register_post_status( 'awaiting_vbank', array(
			'label'                     => _x( 'Awating Vbank', '가상계좌 입금대기중', 'iamport-edd' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( '가상계좌 입금대기중 <span class="count">(%s)</span>', '가상계좌 입금대기중 <span class="count">(%s)</span>', 'iamport-edd' )
		));
	}

	public function iamport_edd_payments_new_views( $views ) {
		$views['awaiting_vbank']	= sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'status' => 'awaiting_vbank', 'paged' => FALSE ) ), '가상계좌 입금대기중' );

		return $views;
	}

	public function iamport_edd_bulk_status_dropdown( $actions ) {
		$new_bulk_status_actions = array();

		// Loop through existing bulk actions
		foreach ( $actions as $key => $action ) {

			$new_bulk_status_actions[ $key ] = $action;

			// Add our actions after the "Set To Cancelled" action
			if ( 'set-status-cancelled' === $key ) {
				$new_bulk_status_actions['set-status-awaiting-vbank']	= '가상계좌 입금대기중으로 변경';
			}
		}

		return $new_bulk_status_actions;
	}

	public function iamport_edd_bulk_status_action( $id, $action ) {
		if ( 'set-status-awaiting-vbank' === $action ) {
			edd_update_payment_status( $id, 'awaiting_vbank' );
		}
	}

	public function iamport_edd_earnings_reporting_args( $args ) {
		$args['post_status'] = array_merge( $args['post_status'], array( 'awaiting_vbank' ) );

		return $args;
	}

	public function iamport_edd_sales_reporting_args( $args ) {
		$args['post_status'] = array_merge( $args['post_status'], array( 'awaiting_vbank' ) );

		return $args;
	}

	public function iamport_enqueue_scripts() {
		wp_register_script( 'iamport_rsa', plugins_url( '/assets/js/rsa.bundle.js',plugin_basename(__FILE__) ));
		wp_register_script( 'iamport_edd_checkout', plugins_url( '/assets/js/checkout.js', plugin_basename(__FILE__) ), array('jquery'), '20191025' );
		wp_register_script( 'iamport_script', 'https://cdn.iamport.kr/js/iamport.payment-1.1.6.js', array('jquery') );
		wp_register_script( 'samsung_runnable', 'https://d3sfvyfh4b9elq.cloudfront.net/pmt/web/device.json' );

		wp_enqueue_script('iamport_rsa');
		wp_enqueue_script('iamport_edd_checkout');
		wp_enqueue_script('iamport_script');
		wp_enqueue_script( 'samsung_runnable' );
	}

	private function load_gateways($methods) {
		$gateways = array();
		$base_path = dirname(__FILE__) . '/includes/';

		require_once($base_path . 'abstract/edd-iamport-base.php');
		require_once($base_path . 'abstract/edd-iamport-normal.php');

		foreach ($methods as $m) {
			require_once($base_path . 'edd-iamport-' . $m . '.php');

			$clazz = 'EddIamport'.ucwords($m);
			$gateways[] = new $clazz();
		}

		return $gateways;
	}

	private function find_gateway($gateway_id) {
		foreach ($this->gateways as $gw) {
			$info = $gw->identify();

			if ( $info['gateway_id'] === $gateway_id )	return $gw;
		}

		return false;
	}

	protected function _iamport_post_meta($payment_id, $meta_key, $meta_value) {
		if ( !add_post_meta($payment_id, $meta_key, $meta_value, true) ) {
			update_post_meta($payment_id, $meta_key, $meta_value);
		}
	}

	public function register_iamport_gateways($gateway_list) {
		foreach ($this->gateways as $gw) {
			$info = $gw->identify();

			$gateway_list[ $info['gateway_id'] ] = array(
				'admin_label' => $info['admin_label'],
				'checkout_label' => $info['checkout_label']
			);

			//common hook for each gateway

			//register form hook for each
			add_action( 'edd_' . $info['gateway_id'] . '_cc_form', array($gw, 'cc_form') );

			//register payment hook for each
			add_action( 'edd_gateway_' . $info['gateway_id'], array($gw, 'process_payment') );

			//register setting hook for each
			add_filter( 'edd_settings_gateways', array($gw, 'settings') );
			add_filter( 'edd_settings_sections_gateways', array($gw, 'sections') );
		}

		return $gateway_list;
	}

	public function process_refund($payment_id, $new_status, $old_status) {
	    $allowedOldStatus = apply_filters('iamport_edd_refundable_old_status', array('publish', 'revoked'));

		if( !in_array($old_status, $allowedOldStatus) ) {
			return;
		}

		if( 'refunded' != $new_status ) {
			return;
		}

		$gateway_id = edd_get_payment_gateway( $payment_id );

		$gw = $this->find_gateway($gateway_id);
		if ( !$gw )	return;


		$gw->process_refund( $payment_id );
	}

	public function iamport_settings_tabs($tabs) {
		$tabs['iamport'] = '아임포트';

		return $tabs;
	}

	public function iamport_register_settings() {
		add_settings_section(
			'edd_settings_iamport_main', // edd_settings_XXX - xxx is the tab name
			__return_null(),
			'__return_false',
			'edd_settings_iamport_main' // edd_settings_XXX - xxx is the tab name
		);

		add_settings_field(
			'edd_settings[iamport_hide_phone]', // "test" should be unique to this specific option
			'휴대폰 번호 입력 숨기기',   // NAME of the option
			'edd_checkbox_callback', // built in callbacks see https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/master/includes/admin/settings/register-settings.php for a full list
			'edd_settings_iamport_main', // edd_settings_XXX - xxx is the tab name
			'edd_settings_iamport_main', // edd_settings_XXX - xxx is the tab name
			array(
				'id'      => 'iamport_hide_phone', // should match the id in teh serialized optoin array
				'desc'    => '결제 시 휴대폰 번호를 입력받지 않습니다',
				'name'    => '휴대폰 번호 입력 숨기기',  // NAME of the option
				'section' => 'iamport', // xxx is the tab name
				'checked' => true
			)
		);
	}

}

add_action('plugins_loaded', array( new EddIamportGateway(), 'init' ), 0);
