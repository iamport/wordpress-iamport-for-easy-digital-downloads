<?php
class EddIamportCard extends EddIamportNormal {

	public function identify() {
		return array(
			'gateway_id' => 'iamport_card',
			'admin_label' => '아임포트(신용카드)',
			'checkout_label' => '신용카드 결제'
		);
	}

	public function settings($settings) {
		$settings['iamport_card'] = array(
			'iamport_card_settings' => array(
				'id' => 'iamport_card_settings',
				'name' => '아임포트(신용카드)',
				'type' => 'header'
			),
			'iamport_card_user_code' => array(
				'id' => 'iamport_card_user_code',
				'name' => '아임포트 사용자 식별코드',
				'type' => 'text',
				'size' => 'regular'
			),
			'iamport_card_rest_key' => array(
				'id' => 'iamport_card_rest_key',
				'name' => '아임포트 REST API Key',
				'type' => 'text',
				'size' => 'regular'
			),
			'iamport_card_rest_secret' => array(
				'id' => 'iamport_card_rest_secret',
				'name' => '아임포트 REST API Secret',
				'type' => 'text',
				'size' => 'large'
			),
            'iamport_card_pg' => array(
                'id' => 'iamport_card_pg',
                'name' => '복수PG 설정 구분자',
                'type' => 'text',
                'size' => 'regular',
                'desc' => '복수PG설정을 활용할 때에만 입력합니다. {PG사구분자}.{상점아이디} 형식으로 입력합니다. (예시 : html5_inicis.MOIxxxxxxx)',
            )
		);

		return $settings;
	}

	public function sections($sections) {
		$sections['iamport_card'] = '아임포트(신용카드)';
		return $sections;
	}

	public function cc_form() {
		$user_code = edd_get_option( 'iamport_card_user_code' );
		// $merchant_uid = $this->generate_merchant_uid();

		ob_start(); ?>
		<input type="hidden" name="iamport_user_code" value="<?=$user_code?>">
		<input type="hidden" name="iamport_pay_method" value="card">
		<!-- <input type="hidden" name="iamport_merchant_uid" value="<?=$merchant_uid?>"> -->
		<?php
		ob_end_flush();
	}

}
