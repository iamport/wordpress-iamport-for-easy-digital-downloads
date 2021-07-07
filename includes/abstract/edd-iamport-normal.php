<?php
abstract class EddIamportNormal extends EddIamportBase {

	private function get_user_info() {
		//회원가입해야하는지 체크(process-purchase.php - edd_process_purchase_form())
		$valid_data = edd_purchase_form_validate_fields();

		if ( is_user_logged_in() ) {
			// Set the valid user as the logged in collected data
			$user = $valid_data['logged_in_user'];
		} else if ( $valid_data['need_new_user'] === true || $valid_data['need_user_login'] === true  ) {
			// New user registration
			if ( $valid_data['need_new_user'] === true ) {
				// Set user
				$user = $valid_data['new_user_data'];
				// Register and login new user
				$user['user_id'] = edd_register_and_login_new_user( $user );
				// User login
			} else if ( $valid_data['need_user_login'] === true  && ! $is_ajax ) {
				/*
				 * The login form is now processed in the edd_process_purchase_login() function.
				 * This is still here for backwards compatibility.
				 * This also allows the old login process to still work if a user removes the
				 * checkout login submit button.
				 *
				 * This also ensures that the customer is logged in correctly if they click "Purchase"
				 * instead of submitting the login form, meaning the customer is logged in during the purchase process.
				 */

				// Set user
				$user = $valid_data['login_user_data'];

				// Login user
				if ( empty( $user ) || $user['user_id'] == -1 ) {
					edd_set_error( 'invalid_user', __( 'The user information is invalid', 'easy-digital-downloads' ) );
					return false;
				} else {
					edd_log_user_in( $user['user_id'], $user['user_login'], $user['user_pass'] );
				}
			}
		}

		// Check guest checkout
		if ( false === $user && false === edd_no_guest_checkout() ) {
			// Set user
			$user = $valid_data['guest_user_data'];
		}

		// Verify we have an user
		if ( false === $user || empty( $user ) ) {
			// Return false
			return false;
		}

		// Get user first name
		if ( ! isset( $user['user_first'] ) || strlen( trim( $user['user_first'] ) ) < 1 ) {
			$user['user_first'] = isset( $_POST["edd_first"] ) ? strip_tags( trim( $_POST["edd_first"] ) ) : '';
		}

		// Get user last name
		if ( ! isset( $user['user_last'] ) || strlen( trim( $user['user_last'] ) ) < 1 ) {
			$user['user_last'] = isset( $_POST["edd_last"] ) ? strip_tags( trim( $_POST["edd_last"] ) ) : '';
		}

        // Get user phone
        if ( ! isset( $user['phone'] ) || strlen( trim( $user['phone'] ) ) < 1 ) {
            $user['phone'] = isset( $_POST["edd_phone"] ) ? strip_tags( trim( $_POST["edd_phone"] ) ) : '';
        }

		// Get the user's billing address details
		$user['address'] = array();
		$user['address']['line1']   = ! empty( $_POST['card_address']    ) ? sanitize_text_field( $_POST['card_address']    ) : false;
		$user['address']['line2']   = ! empty( $_POST['card_address_2']  ) ? sanitize_text_field( $_POST['card_address_2']  ) : false;
		$user['address']['city']    = ! empty( $_POST['card_city']       ) ? sanitize_text_field( $_POST['card_city']       ) : false;
		$user['address']['state']   = ! empty( $_POST['card_state']      ) ? sanitize_text_field( $_POST['card_state']      ) : false;
		$user['address']['country'] = ! empty( $_POST['billing_country'] ) ? sanitize_text_field( $_POST['billing_country'] ) : false;
		$user['address']['zip']     = ! empty( $_POST['card_zip']        ) ? sanitize_text_field( $_POST['card_zip']        ) : false;

		if ( empty( $user['address']['country'] ) )
			$user['address'] = false; // Country will always be set if address fields are present

		if ( ! empty( $user['user_id'] ) && $user['user_id'] > 0 && ! empty( $user['address'] ) ) {
			// Store the address in the user's meta so the cart can be pre-populated with it on return purchases
			update_user_meta( $user['user_id'], '_edd_user_address', $user['address'] );
		}

		// Return valid user

		$user_info = array(
			'id'         => $user['user_id'],
			'email'      => $user['user_email'],
			'first_name' => $user['user_first'],
			'last_name'  => $user['user_last'],
			'discount'   => $valid_data['discount'],
			'address'    => $user['address'],
            'phone'      => $user['phone']
		);

		return $user_info;
	}

	public function process_payment($purchase_data) {
		global $edd_options;

		//ajax인 경우 edd_get_purchase_form_user()가 error check만 하고 user_info정보를 반환하지 않음

		// Get the user's billing address details(private function 을 사용하지 않는 버전)
		/*
		$address = array();
		$address['line1']   = ! empty( $_POST['card_address']    ) ? sanitize_text_field( $_POST['card_address']    ) : false;
		$address['line2']   = ! empty( $_POST['card_address_2']  ) ? sanitize_text_field( $_POST['card_address_2']  ) : false;
		$address['city']    = ! empty( $_POST['card_city']       ) ? sanitize_text_field( $_POST['card_city']       ) : false;
		$address['state']   = ! empty( $_POST['card_state']      ) ? sanitize_text_field( $_POST['card_state']      ) : false;
		$address['country'] = ! empty( $_POST['billing_country'] ) ? sanitize_text_field( $_POST['billing_country'] ) : false;
		$address['zip']     = ! empty( $_POST['card_zip']        ) ? sanitize_text_field( $_POST['card_zip']        ) : false;

		if ( empty( $address['country'] ) )
			$address = false; // Country will always be set if address fields are present

		$user_info = array(
			'id'			=> get_current_user_id(),
			'email'			=> isset( $_POST['edd_email'] ) ? sanitize_email( $_POST['edd_email'] ) : false,
			'first_name'	=> isset( $_POST["edd_first"] ) ? sanitize_text_field( trim( $_POST["edd_first"] ) ) : false,
			'last_name'		=> isset( $_POST["edd_last"] ) ? sanitize_text_field( trim( $_POST["edd_last"] ) ) : false,
			'phone'			=> isset( $_POST["edd_phone"] ) ? sanitize_text_field( trim( $_POST["edd_phone"] ) ) : false,
			'address'		=> $address
		);
		*/

		// 회원가입, 로그인, 비회원 등 모든 경우를 대응하려면 결국 EDD private function을 사용할 수 밖에 없음. ㅠㅠ
		$user_info = $this->get_user_info();

		$errors = edd_get_errors();
		if( !$errors ) {
            $identity = $this->identify();

			// pending 상태로 먼저 저장
			$payment = array(
				'price' => $purchase_data['price'],
				'date' => $purchase_data['date'],
				'user_email' => $user_info['email'],
				'purchase_key' => $purchase_data['purchase_key'],
				'currency' => edd_get_currency(),
				'downloads' => $purchase_data['downloads'],
				'cart_details' => $purchase_data['cart_details'],
				'user_info' => $user_info,
				'status' => 'pending',
                'gateway' => $identity['gateway_id'], // [정기결제] 0원 결제시 edd_insert_payment() 내부에서 manual 로 gateway 바뀌지 않도록 강제로 세팅(2019-05-26)
			);

			// record the pending payment
			$payment_id = edd_insert_payment($payment);
			if ( $user_info['phone'] )	edd_update_payment_meta($payment_id, 'phone', $user_info['phone']);

			//json response
			$json_response = array(
				'payment_id' => $payment_id,
				'merchant_uid' => $purchase_data['purchase_key'], //purchase_key 를 merchant_uid로 사용
				'price' => $purchase_data['price'], //discount가 적용된 금액
				'currency' => edd_get_currency(),
				'order_title' => $this->order_title( $purchase_data['cart_details'] ),
				'landing_url' => add_query_arg( array(
						'edd-iamport-landing' => date('YmdHis'),
					), home_url() )
			);

			//[2019-10-25] 복수PG 설정
            $pg = edd_get_option($identity['gateway_id'] . '_pg', null);
            if (!empty($pg)) {
                $json_response['pg'] = $pg;
            }

			//apply filter ( need subscription )
			$edd_payment = edd_get_payment_by('id', $payment_id);
			$need_subscription = apply_filters("iamport_edd_need_subscription", false, $edd_payment, $purchase_data);

			if ( $need_subscription === true ) {
				$customer_uid = $this->get_customer_uid( $user_info['email'] );
				edd_update_payment_meta($payment_id, EddIamportGateway::CUSTOMER_UID_CANDIDATE_META_KEY, $customer_uid); //candidate 에 저장

				$json_response["customer_uid"] = $customer_uid;
			}

			//gateway별 custom response
            $gateway_response = $this->process_payment_response($purchase_data);
            if (!empty($gateway_response) && is_array($gateway_response)) {
                $json_response['extended'] = $gateway_response;
            }

			echo json_encode($json_response);
		}
	}

	protected function process_payment_response($purchase_data)
    {
        return null;
    }

	protected function order_title($cart_items) {
		$names = array();

		foreach ($cart_items as $key => $item) {
			$n = esc_html( edd_get_cart_item_name( $item ) );

			$names[] = preg_replace("/\&#\d{4};/", "*", $n); //esc_html이 unicode로 변환한 것을 *로 치환(PG사 특수기호 오류 방지)
		}

		if ( count($names) == 1 )	return $names[0];

		return sprintf("%s 외 상품(%s)", $names[0], count($names)-1);
	}

}
