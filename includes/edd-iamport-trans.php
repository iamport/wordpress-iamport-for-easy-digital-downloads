<?php
class EddIamportTrans extends EddIamportNormal {

	public function identify() {
		return array(
			'gateway_id' => 'iamport_trans',
			'admin_label' => '아임포트(실시간 계좌이체)',
			'checkout_label' => '실시간 계좌이체'
		);
	}

	public function settings($settings) {
		$settings['iamport_trans'] = array(
			'iamport_trans_settings' => array(
				'id' => 'iamport_trans_settings',
				'name' => '아임포트(실시간 계좌이체)',
				'type' => 'header'
			),
			'iamport_trans_user_code' => array(
				'id' => 'iamport_trans_user_code',
				'name' => '아임포트 사용자 식별코드',
				'type' => 'text',
				'size' => 'regular'
			),
			'iamport_trans_rest_key' => array(
				'id' => 'iamport_trans_rest_key',
				'name' => '아임포트 REST API Key',
				'type' => 'text',
				'size' => 'regular'
			),
			'iamport_trans_rest_secret' => array(
				'id' => 'iamport_trans_rest_secret',
				'name' => '아임포트 REST API Secret',
				'type' => 'text',
				'size' => 'large'
			),
            'iamport_trans_pg' => array(
                'id' => 'iamport_trans_pg',
                'name' => '복수PG 설정 구분자',
                'type' => 'text',
                'size' => 'regular',
                'desc' => '복수PG설정을 활용할 때에만 입력합니다. {PG사구분자}.{상점아이디} 형식으로 입력합니다. (예시 : html5_inicis.MOIxxxxxxx)',
            )
		);

		return $settings;
	}

	public function sections($sections) {
		$sections['iamport_trans'] = '아임포트(실시간 계좌이체)';
		return $sections;
	}

	public function cc_form() {
		$user_code = edd_get_option( 'iamport_trans_user_code' );
		// $merchant_uid = $this->generate_merchant_uid();

		ob_start(); ?>
		<input type="hidden" name="iamport_user_code" value="<?=$user_code?>">
		<input type="hidden" name="iamport_pay_method" value="trans">
		<!-- <input type="hidden" name="iamport_merchant_uid" value="<?=$merchant_uid?>"> -->
		<?php
		ob_end_flush();
	}

}
