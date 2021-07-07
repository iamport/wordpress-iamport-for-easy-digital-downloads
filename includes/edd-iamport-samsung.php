<?php
class EddIamportSamsung extends EddIamportNormal {

	public function identify() {
		return array(
			'gateway_id' => 'iamport_samsung',
			'admin_label' => '아임포트(삼성페이)',
			'checkout_label' => '삼성페이 결제'
		);
	}

	public function settings($settings) {
		$settings['iamport_samsung'] = array(
			'iamport_samsung_settings' => array(
				'id' => 'iamport_samsung_settings',
				'name' => '아임포트(삼성페이)',
				'type' => 'header'
			),
			'iamport_samsung_user_code' => array(
				'id' => 'iamport_samsung_user_code',
				'name' => '아임포트 사용자 식별코드',
				'type' => 'text',
				'size' => 'regular'
			),
			'iamport_samsung_rest_key' => array(
				'id' => 'iamport_samsung_rest_key',
				'name' => '아임포트 REST API Key',
				'type' => 'text',
				'size' => 'regular'
			),
			'iamport_samsung_rest_secret' => array(
				'id' => 'iamport_samsung_rest_secret',
				'name' => '아임포트 REST API Secret',
				'type' => 'text',
				'size' => 'large'
			),
            'iamport_samsung_pg' => array(
                'id' => 'iamport_samsung_pg',
                'name' => '복수PG 설정 구분자',
                'type' => 'text',
                'size' => 'regular',
                'desc' => '복수PG설정을 활용할 때에만 입력합니다. {PG사구분자}.{상점아이디} 형식으로 입력합니다. (예시 : kcp.IPxxxxxx)',
            )
		);

		return $settings;
	}

	public function sections($sections) {
		$sections['iamport_samsung'] = '아임포트(삼성페이)';
		return $sections;
	}

	public function cc_form() {
		$user_code = edd_get_option( 'iamport_samsung_user_code' );
		// $merchant_uid = $this->generate_merchant_uid();

		ob_start(); ?>
		<input type="hidden" name="iamport_user_code" value="<?=$user_code?>">
		<input type="hidden" name="iamport_pay_method" value="samsung">
		<!-- <input type="hidden" name="iamport_merchant_uid" value="<?=$merchant_uid?>"> -->
		<?php
		ob_end_flush();
	}

}
