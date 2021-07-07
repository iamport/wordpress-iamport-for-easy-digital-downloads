<?php
class EddIamportSubscription extends EddIamportBase {

	public function identify() {
		return array(
			'gateway_id' => 'iamport_subscription',
			'admin_label' => '아임포트(비인증결제)',
			'checkout_label' => '간편 신용카드 결제'
		);
	}

	public function settings($settings) {
		$settings['iamport_subscription'] = array(
			'iamport_subscription_settings' => array(
				'id' => 'iamport_subscription_settings',
				'name' => '아임포트(비인증결제)',
				'type' => 'header'
			),
			'iamport_subscription_rest_key' => array(
				'id' => 'iamport_subscription_rest_key',
				'name' => '아임포트 REST API Key',
				'type' => 'text',
				'size' => 'regular'
			),
			'iamport_subscription_rest_secret' => array(
				'id' => 'iamport_subscription_rest_secret',
				'name' => '아임포트 REST API Secret',
				'type' => 'text',
				'size' => 'large'
			)
		);

		return $settings;
	}

	public function sections($sections) {
		$sections['iamport_subscription'] = '아임포트(비인증결제)';
		return $sections;
	}

	public function process_payment($purchase_data) {
		global $edd_options;

		$errors = edd_get_errors();
		if( !$errors ) {
			require_once(dirname(__FILE__).'/lib/iamport.php');

			// $card_number 	= $_POST['iamport_subscription_card_number'];
			// $card_expiry_y 	= $_POST['iamport_subscription_card_expiry_y'];
			// $card_expiry_m 	= $_POST['iamport_subscription_card_expiry_m'];
			// $card_birth 	= $_POST['iamport_subscription_card_birth'];
			// $card_pwd 		= $_POST['iamport_subscription_card_pwd'];
			// $card_expiry  	= '20'.$card_expiry_y.$card_expiry_m;

			$card_number 	= $_POST['enc_iamport_subscription_card_number'];
			$expiry 		= $_POST['enc_iamport_subscription_card_expiry'];
			$birth 			= $_POST['enc_iamport_subscription_card_birth'];
			$pwd_2digit 	= $_POST['enc_iamport_subscription_card_pwd'];

			$private_key = $this->get_private_key();

			$dec_card_number 	= $this->decrypt( $card_number, $private_key );
			$dec_expiry			= $this->decrypt( $expiry, $private_key );
			$dec_birth			= $this->decrypt( $birth, $private_key );
			$dec_pwd			= $this->decrypt( $pwd_2digit, $private_key );

            $isBilling = !empty($_POST['iamport_subscription_remember']) || $purchase_data['price'] <= 0;

			$purchase_summary = edd_get_purchase_summary($purchase_data);

			// pending 상태로 먼저 저장
			$payment = array(
				'price' => $purchase_data['price'],
				'date' => $purchase_data['date'],
				'user_email' => $purchase_data['user_email'],
				'purchase_key' => $purchase_data['purchase_key'],
				'currency' => edd_get_currency(),
				'downloads' => $purchase_data['downloads'],
				'cart_details' => $purchase_data['cart_details'],
				'user_info' => $purchase_data['user_info'],
				'status' => 'pending'
			);

			// record the pending payment
			$payment_id = edd_insert_payment($payment);

			$imp_rest_key = edd_get_option( 'iamport_subscription_rest_key' );
			$imp_rest_secret = edd_get_option( 'iamport_subscription_rest_secret' );

			$payment_request = array(
				'amount' => $purchase_data['price'],
				'merchant_uid' => $purchase_data['purchase_key'],
				'card_number' => $dec_card_number,
				'expiry' => $dec_expiry,
				'birth' => $dec_birth,
				'pwd_2digit' => $dec_pwd,
				'name' => $purchase_summary,
				'buyer_name' => $purchase_data['user_info']['last_name'] . $purchase_data['user_info']['first_name'],
				'buyer_email' => $purchase_data['user_info']['email'],
				'buyer_tel' => $_POST['edd_phone']
			);

			if ($isBilling) {
				//비회원 구매인 경우에도 user_email이 unique key로 사용될 수 있음
				$customer_uid = $this->get_customer_uid( $purchase_data['user_email'] );
				$payment_request['customer_uid'] = $customer_uid;
			}

			$iamport = new EddIamport($imp_rest_key, $imp_rest_secret);

			if ($purchase_data['price'] > 0) {
                $result = $iamport->onetime($payment_request);

                $payment_data = $result->data;
                if ( $result->success ) {
                    if ( $payment_data->status == 'paid' ) {
                        // once a transaction is successful, set the purchase to complete
                        edd_update_payment_status($payment_id, 'complete');
                        $this->_iamport_post_meta( $payment_id, '_edd_imp_uid', $payment_data->imp_uid );

                        if ( !empty($customer_uid) ) {
                            $this->_iamport_post_meta( $payment_id, EddIamportGateway::CUSTOMER_UID_META_KEY, $customer_uid );
                        }

                        // go to the success page
                        return edd_send_to_success_page();
                    } else {
                        $error_message = '결제에 실패하였습니다. ' . $payment_data->fail_reason;
                        edd_set_error('iamport_fail', $error_message);
                        edd_record_gateway_error( '결제실패', $error_message, $payment_id );
                        edd_update_payment_status( $payment_id, 'failed' );
                        edd_insert_payment_note( $payment_id, $error_message );
                    }
                } else {
                    edd_set_error('iamport_fail', $result->error['message']);
                    edd_record_gateway_error( '결제실패', $result->error['message'], $payment_id );
                    edd_update_payment_status( $payment_id, 'failed' );
                    edd_insert_payment_note( $payment_id, $result->error['message'] );
                }
            } else {
			    $payment_request['customer_name'] = $payment_request['buyer_name'];
			    $payment_request['customer_email'] = $payment_request['buyer_email'];
			    $payment_request['customer_tel'] = $payment_request['buyer_tel'];

			    $result = $iamport->customer_add($customer_uid, $payment_request);
			    if ($result->success) {
                    edd_update_payment_status($payment_id, 'complete');
                    $this->_iamport_post_meta( $payment_id, EddIamportGateway::CUSTOMER_UID_META_KEY, $customer_uid );

                    // go to the success page
                    return edd_send_to_success_page();
                } else {
                    edd_set_error('iamport_fail', $result->error['message']);
                    edd_record_gateway_error( '카드등록실패', $result->error['message'], $payment_id );
                    edd_update_payment_status( $payment_id, 'failed' );
                    edd_insert_payment_note( $payment_id, $result->error['message'] );
                }
            }
        }

		// 끝까지 왔으면 에러가 있다는 의미
		edd_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['edd-gateway']);
	}

	public function cc_form() {
		$private_key = $this->get_private_key();
		$public_key = $this->get_public_key($private_key, $this->keyphrase());

		$isFree = edd_get_cart_total() <= 0;

		ob_start(); ?>
		<fieldset id="edd-iamport-subscription-rsa" data-module="<?=$public_key['module']?>" data-exponent="<?=$public_key['exponent']?>">
			<input type="hidden" name="enc_iamport_subscription_card_number" value="">
			<input type="hidden" name="enc_iamport_subscription_card_expiry" value="">
			<input type="hidden" name="enc_iamport_subscription_card_birth" value="">
			<input type="hidden" name="enc_iamport_subscription_card_pwd" value="">

			<span><legend>결제카드정보</legend></span>
			<p>
				<label class="edd-label">카드번호</label>
				<input type="text" autocomplete="off" name="iamport_subscription_card_number" class="edd-input required" placeholder="1234123412341234" maxlength="16"/>
			</p>
			<p class="card-expiration">
				<label class="edd-label">유효기간(MM/YY)</label>
				<input type="text" size="2" name="iamport_subscription_card_expiry_m" class="card-expiry-month edd-input required" placeholder="MM" style="width:30%" maxlength="2"/>
				<span class="exp-divider"> / </span>
				<input type="text" size="2" name="iamport_subscription_card_expiry_y" class="card-expiry-year edd-input required" placeholder="YY" style="width:30%" maxlength="2"/>
			</p>
			<p>
				<label class="edd-label">카드소지자 생년월일 또는 사업자등록번호</label>
				<input type="text" maxlength="10" autocomplete="off" name="iamport_subscription_card_birth" class="card-birth edd-input required" placeholder="생년월일 또는 사업자등록번호" />
			</p>
			<p>
				<label class="edd-label">카드비밀번호 앞2자리</label>
				<input type="password" maxlength="2" autocomplete="off" name="iamport_subscription_card_pwd" class="card-pwd2digit edd-input required" placeholder="**" style="width:20%"/>
			</p>
            <?php if (!$isFree) : ?>
			<p>
				<label class="edd-label">다음 번 결제에 사용</label>
				<label style="font-weight:normal"><input type="checkbox" name="iamport_subscription_remember" value="Y">결제에 사용된 카드 정보를 다음 번 결제에 사용하시겠습니까? (암호화된 빌링키가 저장됩니다)</label>
			</p>
            <?php endif; ?>
		</fieldset>
		<?php
		ob_end_flush();
	}

	//rsa

	private function keyphrase() {
		$keyphrase = get_option('_iamport_edd_rsa_keyphrase');
		if ( $keyphrase )		return $keyphrase;

		require_once( ABSPATH . 'wp-includes/class-phpass.php');
		$hasher = new PasswordHash( 8, false );
		$keyphrase = md5( $hasher->get_random_bytes( 16 ) );

		if ( add_option('_iamport_edd_rsa_keyphrase', $keyphrase) )		return $keyphrase;

		return false;
	}

	private function get_private_key() {
		$private_key = get_option('_iamport_edd_rsa_private_key');

		if ( $private_key )		return $private_key; //있으면 기존 것을 반환

		$config = array(
			"digest_alg" => "sha256",
			"private_key_bits" => 4096,
			"private_key_type" => OPENSSL_KEYTYPE_RSA
		);

		// Create the private key
		$res = openssl_pkey_new($config);
		$success = openssl_pkey_export($res, $private_key, $this->keyphrase()); //-------BEGIN RSA PRIVATE KEY...로 시작되는 문자열을 $private_key에 저장

		if ( $success && add_option('_iamport_edd_rsa_private_key', $private_key) )		return $private_key;

		return false;
	}

	private function get_public_key($private_key, $keyphrase) {
		$res = openssl_pkey_get_private($private_key, $keyphrase);
		$details = openssl_pkey_get_details($res);

		return array('module'=>$this->to_hex($details['rsa']['n']), 'exponent'=>$this->to_hex($details['rsa']['e']));
	}

	private function to_hex($data) {
		return strtoupper(bin2hex($data));
	}

	private function decrypt($encrypted, $private_key) {
		$payload = pack('H*', $encrypted);
		$pk_info = openssl_pkey_get_private($private_key, $this->keyphrase());
		if ( $pk_info && openssl_private_decrypt($payload, $decrypted, $pk_info) ) {
			return $decrypted;
		}

		return false;
	}

}
