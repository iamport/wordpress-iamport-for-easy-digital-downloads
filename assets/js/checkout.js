(function($) {
	function deserialize(serializedString) {
	    serializedString = serializedString.replace(/\+/g, '%20');
	    var formFieldArray = serializedString.split("&");

	    var obj = {};

	    $.each(formFieldArray, function(i, pair){
	        var nameValue = pair.split("=");
	        var name = decodeURIComponent(nameValue[0]);
	        var value = decodeURIComponent(nameValue[1]);

	        obj[ name ] = value;
	    });

	    return obj;
	}

	function isSamsungPayRunnable() {
		var runnable = false;
		var isAndroid = navigator.userAgent.match(/Android/i);

		if(isAndroid){
			var mydata = JSON.parse(device);
			var i = 0;
			while (mydata[i]) {
				if(navigator.userAgent.indexOf(mydata[i])>0){
					runnable = true;
					break;
				}
				i++;
			}
		}
		return runnable;
	}

	function getFormValue(form, key, defaultValue) {
		if (typeof form[key] != 'undefined') {
			return form[key].value;
		}

		return typeof defaultValue == 'undefined' ? '' : defaultValue;
	}

	$.ajaxPrefilter( function( options, originalOptions, jqXHR ) {
		if ( originalOptions.type == 'post' ) {
			if ( typeof originalOptions.data == 'string' && originalOptions.data.indexOf('iamport_subscription_card_number') > -1 ) {
				var param = deserialize(originalOptions.data);

				// pre 단계에서 굳이 rsa암호화해서 넣어줄 필요는 없음. 카드정보만 삭제해서 발송하도록
				/*
				var holder = $('#edd-iamport-subscription-rsa'),
					module = holder.data('module'),
					exponent = holder.data('exponent');

				var rsa = new RSAKey();
				rsa.setPublic(module, exponent);

				// encrypt using public key
				var enc_card_number = rsa.encrypt( param['iamport_subscription_card_number'] );
				var enc_card_expiry = rsa.encrypt( '20' + param['iamport_subscription_card_expiry_y'] + param['iamport_subscription_card_expiry_m'] );
				var enc_card_birth 	= rsa.encrypt( param['iamport_subscription_card_birth'] );
				var enc_card_pwd 	= rsa.encrypt( param['iamport_subscription_card_pwd'] );

				param['enc_iamport_subscription_card_number'] = enc_card_number;
				param['enc_iamport_subscription_card_expiry'] = enc_card_expiry;
				param['enc_iamport_subscription_card_birth'] = enc_card_birth;
				param['enc_iamport_subscription_card_pwd'] = enc_card_pwd;
				*/

				delete param['iamport_subscription_card_number'];
				delete param['iamport_subscription_card_expiry_y'];
				delete param['iamport_subscription_card_expiry_m'];
				delete param['iamport_subscription_card_birth'];
				delete param['iamport_subscription_card_pwd'];

				options.data = $.param(param);
			} else if ( typeof originalOptions.data == 'object' && originalOptions.data != null && originalOptions.data.hasOwnProperty('iamport_subscription_card_number') ) { //2016-08-08 bugfix : typeof null == 'object'
				delete options.data['iamport_subscription_card_number'];
				delete options.data['iamport_subscription_card_expiry_y'];
				delete options.data['iamport_subscription_card_expiry_m'];
				delete options.data['iamport_subscription_card_birth'];
				delete options.data['iamport_subscription_card_pwd'];
			}
		}
	});

	$(document).ready(function() {
		if ( $('#edd_error_iamport_fail').length > 0 ) {
			alert('결제에 실패하였습니다. 하단의 실패 원인을 확인하신 후 다시 시도해주십시요.');
		}

		var iamport_gateways = ['iamport_naverpay', 'iamport_card', 'iamport_trans', 'iamport_samsung', 'iamport_kakao', 'iamport_phone'];
		$('#edd_purchase_form').on('submit', function(e) {
			var gateway  = this['edd-gateway'].value;
			var $form = $(this);
			var rawForm = $form[0];

			if ( jQuery.inArray(gateway, iamport_gateways) > -1 ) { // 일반 인증 결제
				//edd_ajax=true만 제거하고 edd-ajax.js와 동일하다
				$.post(edd_global_vars.ajaxurl, $form.serialize() + '&action=edd_process_checkout', function(json_str) {
					var obj = null;
					try {
						obj = $.parseJSON( $.trim(json_str) );
					} catch(e) {}

					if ( obj && obj.payment_id ) {
						var user_code 		= getFormValue(rawForm, 'iamport_user_code'),
							pay_method		= getFormValue(rawForm, 'iamport_pay_method'),
							// merchant_uid 	= getFormValuerawForm, iamport_merchant_uid'].v)
							merchant_uid  = obj.merchant_uid,
							buyer_name		= getFormValue(rawForm, 'edd_last') + getFormValue(rawForm, 'edd_first'),
							buyer_email		= getFormValue(rawForm, 'edd_email'),
							buyer_tel		= getFormValue(rawForm, 'edd_phone');

						if (!buyer_tel) {
							buyer_tel = '010-0000-0000';
						}

						//삼성페이 처리
						if ( pay_method == 'samsung' && !isSamsungPayRunnable() ) {
							if ( !confirm('현재 삼성페이가 지원되지 않는 단말입니다. 일반 신용카드 결제를 진행하시겠습니까?') )	return false;

							pay_method = 'card'; //지원안되는 단말인데 결제시도하면 card로 시도
						}

						var payment_data = {
							pg : obj.pg,
							amount : obj.price,
							name : obj.order_title,
							pay_method : pay_method,
							merchant_uid : merchant_uid,
							buyer_name : buyer_name,
							buyer_email : buyer_email,
							buyer_tel : buyer_tel,
							digital : true,
							m_redirect_url : obj.landing_url
						};

						if ( obj.customer_uid ) {
							payment_data.customer_uid = obj.customer_uid;
						}

						if ( obj.extended ) {
							for (var key in obj.extended) {
								payment_data[key] = obj.extended[key];
							}
						}

						IMP.init(user_code);
						IMP.request_pay(payment_data, function(rsp) {
							if ( rsp.success ) {
								location.href = obj.landing_url + '&imp_uid=' + rsp.imp_uid + '&merchant_uid=' + rsp.merchant_uid;
							} else {
								alert(rsp.error_msg);
								location.reload();
							}
						});
					} else {
						alert(json_str);
					}
				});

				return false;
			} else if ( gateway === 'iamport_subscription' ) { //비인증 결제
				var holder = $('#edd-iamport-subscription-rsa'),
					module = holder.data('module'),
					exponent = holder.data('exponent');

				var rsa = new RSAKey();
				rsa.setPublic(module, exponent);

				// encrypt using public key
				var enc_card_number = rsa.encrypt( getFormValue(rawForm, 'iamport_subscription_card_number'));
				var enc_card_expiry = rsa.encrypt( '20' + getFormValue(rawForm, 'iamport_subscription_card_expiry_y') + getFormValue(rawForm, 'iamport_subscription_card_expiry_m'));
				var enc_card_birth 	= rsa.encrypt( getFormValue(rawForm, 'iamport_subscription_card_birth'));
				var enc_card_pwd 	= rsa.encrypt( getFormValue(rawForm, 'iamport_subscription_card_pwd'));

				rawForm['enc_iamport_subscription_card_number'].value = enc_card_number;
				rawForm['enc_iamport_subscription_card_expiry'].value = enc_card_expiry;
				rawForm['enc_iamport_subscription_card_birth'].value = enc_card_birth;
				rawForm['enc_iamport_subscription_card_pwd'].value = enc_card_pwd;

				$form.find('input[name="iamport_subscription_card_number"]').attr('disabled', true);
				$form.find('input[name="iamport_subscription_card_expiry_y"]').attr('disabled', true);
				$form.find('input[name="iamport_subscription_card_expiry_m"]').attr('disabled', true);
				$form.find('input[name="iamport_subscription_card_birth"]').attr('disabled', true);
				$form.find('input[name="iamport_subscription_card_pwd"]').attr('disabled', true);
			}

			return true;
		});
	});
})( jQuery );
