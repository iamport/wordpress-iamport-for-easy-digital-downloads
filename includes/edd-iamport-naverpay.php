<?php
class EddIamportNaverpay extends EddIamportNormal {

    public static $PRODUCT_CATEGORIES = array(
        "PRODUCT_DIGITAL_CONTENT"    => "[상품] 디지털 컨텐츠",
        "BOOK_GENERAL"               => "[도서] 일반",
        "BOOK_EBOOK"                 => "[도서] 전자책",
        "BOOK_USED"                  => "[도서] 중고",
        "MUSIC_CD"                   => "[음악] CD",
        "MUSIC_LP"                   => "[음악] LP",
        "MUSIC_USED"                 => "[음악] 중고 음반",
        "MOVIE_DVD"                  => "[영화] DVD",
        "MOVIE_BLUERAY"              => "[영화] 블루레이",
        "MOVIE_VOD"                  => "[영화] VOD",
        "MOVIE_TICKET"               => "[영화] 티켓",
        "MOVIE_USED"                 => "[영화] 중고 DVD, 블루 레이등",
        "PRODUCT_GENERAL"            => "[상품] 일반",
        "PRODUCT_CASHABLE"           => "[상품] 환금성",
        "PRODUCT_CLAIM"              => "[상품] 클레임",
        "PRODUCT_SUPPORT"            => "[상품] 후원",
        "PLAY_TICKET"                => "[공연/전시] 티켓",
        "TRAVEL_DOMESTIC"            => "[여행] 국내 숙박",
        "TRAVEL_OVERSEA"             => "[여행] 해외 숙박",
        "INSURANCE_CAR"              => "[보험] 자동차보험",
        "INSURANCE_DRIVER"           => "[보험] 운전자보험",
        "INSURANCE_HEALTH"           => "[보험] 건강보험",
        "INSURANCE_CHILD"            => "[보험] 어린이보험",
        "INSURANCE_TRAVELER"         => "[보험] 여행자보험",
        "INSURANCE_GOLF"             => "[보험] 골프보험",
        "INSURANCE_ANNUITY"          => "[보험] 연금보험",
        "INSURANCE_ANNUITY_SAVING"   => "[보험] 연금저축보험",
        "INSURANCE_SAVING"           => "[보험] 저축보험",
        "INSURANCE_VARIABLE_ANNUITY" => "[보험] 변액적립보험",
        "INSURANCE_CANCER"           => "[보험] 암보험",
        "INSURANCE_DENTIST"          => "[보험] 치아보험",
        "INSURANCE_ACCIDENT"         => "[보험] 상해보험",
        "INSURANCE_SEVERANCE"        => "[보험] 퇴직연금",
        "FLIGHT_TICKET"              => "[항공] 티켓",
        "FOOD_DELIVERY"              => "[음식] 배달",
        "ETC_ETC"                    => "[기타]",
    );

    /**
     * EddIamportNaverpay constructor.
     */
    public function __construct()
    {
        add_filter('edd_price_row_args', array($this, 'edd_price_row_args'), 10, 2);
        add_filter('edd_metabox_fields_save', array($this, 'edd_metabox_fields_save'));
        add_action('edd_download_price_option_row', array($this, 'edd_download_price_option_row'), 10, 3);
        add_action('edd_price_field', array($this, 'edd_price_field'));
    }


    public function identify() {
		return array(
			'gateway_id' => 'iamport_naverpay',
			'admin_label' => '아임포트(네이버페이-결제형)',
			'checkout_label' => '네이버페이'
		);
	}

	public function settings($settings) {
		$settings['iamport_naverpay'] = array(
			'iamport_naverpay_settings' => array(
				'id' => 'iamport_naverpay_settings',
				'name' => '아임포트(네이버페이-결제형)',
				'type' => 'header'
			),
			'iamport_naverpay_user_code' => array(
				'id' => 'iamport_naverpay_user_code',
				'name' => '아임포트 사용자 식별코드',
				'type' => 'text',
				'size' => 'regular'
			),
			'iamport_naverpay_rest_key' => array(
				'id' => 'iamport_naverpay_rest_key',
				'name' => '아임포트 REST API Key',
				'type' => 'text',
				'size' => 'regular'
			),
			'iamport_naverpay_rest_secret' => array(
				'id' => 'iamport_naverpay_rest_secret',
				'name' => '아임포트 REST API Secret',
				'type' => 'text',
				'size' => 'large'
			),
            'iamport_naverpay_pg' => array(
                'id' => 'iamport_naverpay_pg',
                'name' => '복수PG 설정 구분자',
                'type' => 'text',
                'size' => 'regular',
                'desc' => '복수PG설정을 활용할 때에만 입력합니다. naverpay.{상점아이디} 형식으로 입력합니다. (예시 : naverpay.xxxxxx)',
            ),
            'iamport_naverpay_cfm' => array(
                'id' => 'iamport_naverpay_cfm',
                'name' => '서비스 이용 완료일',
                'type' => 'number',
                'size' => 'regular',
                'desc' => '결제일 기준 X일 후(기본값은 2)',
                'std' => 2,
                'min' => 0,
            )
		);

		return $settings;
	}

	public function sections($sections) {
		$sections['iamport_naverpay'] = '아임포트(네이버페이-결제형)';
		return $sections;
	}

	public function cc_form() {
		$user_code = edd_get_option( 'iamport_naverpay_user_code' );
		// $merchant_uid = $this->generate_merchant_uid();

		ob_start(); ?>
		<input type="hidden" name="iamport_user_code" value="<?=$user_code?>">
		<input type="hidden" name="iamport_pay_method" value="card">
		<!-- <input type="hidden" name="iamport_merchant_uid" value="<?=$merchant_uid?>"> -->
		<?php
		ob_end_flush();
	}

	//widget hook - register product category
    public function edd_metabox_fields_save($fields)
    {
        $fields[] = 'naver_product_category';

        return $fields;
    }

    public function edd_price_field($post_id)
    {
        //단일 가격일 때에만 가격 뒤에 render cateogory select box
        $variable_pricing   = edd_has_variable_prices( $post_id );
        if (!$variable_pricing) {
            $naver_product_category = 'PRODUCT_DIGITAL_CONTENT';
            $value = get_post_meta($post_id, 'naver_product_category', true);
            if ($value && in_array($value, array_keys(self::$PRODUCT_CATEGORIES))) {
                $naver_product_category = $value;
            }

            ob_start();
            ?>
            <div class="edd_pricing_fields">
                <?php _e( '네이버페이 상품유형', 'iamport-for-edd' ); ?>
                <?php echo EDD()->html->select( array(
                    'name'  => 'naver_product_category',
                    'selected' => $naver_product_category,
                    'placeholder' => __( '네이버페이 상품유형', 'iamport-for-edd' ),
                    'class' => 'edd_variable_prices_name large-text',
                    'options' => self::$PRODUCT_CATEGORIES,
                    'show_option_all' => false,
                    'show_option_none' => false,
                ) ); ?>
            </div>
            <?php
            echo ob_get_clean();
        }
    }

    public function edd_price_row_args($args, $value)
    {
        $naver_product_category = 'PRODUCT_DIGITAL_CONTENT';
        if ( isset($value['naver_product_category']) && in_array($value['naver_product_category'], array_keys(self::$PRODUCT_CATEGORIES)) ) {
            $naver_product_category = $value['naver_product_category'];
        }

        $args['naver_product_category'] = $naver_product_category;

        return $args;
    }

    public function edd_download_price_option_row($post_id, $key, $args)
    {
        ob_start();
        ?>
        <div class="edd-repeatable-row-standard-fields">

            <div class="edd-option-name">
                <span class="edd-repeatable-row-setting-label"><?php _e( '네이버페이 상품유형', 'iamport-for-edd' ); ?></span>
                <?php echo EDD()->html->select( array(
                    'name'  => 'edd_variable_prices[' . $key . '][naver_product_category]',
                    'selected' => esc_attr( $args['naver_product_category'] ),
                    'placeholder' => __( '네이버페이 상품유형', 'iamport-for-edd' ),
                    'class' => 'edd_variable_prices_name large-text',
                    'options' => self::$PRODUCT_CATEGORIES,
                    'show_option_all' => false,
                    'show_option_none' => false,
                ) ); ?>
            </div>
        </div>
        <?php
        echo ob_get_clean();
    }

    protected function order_title($cart_items)
    {
        // XXX 외 2개 와 같이 suffix를 붙이지 않는다. 첫 번째 상품의 이름 반환
        foreach ($cart_items as $key => $item) {
            $n = esc_html( edd_get_cart_item_name( $item ) );

            return preg_replace("/\&#\d{4};/", "*", $n); //esc_html이 unicode로 변환한 것을 *로 치환(PG사 특수기호 오류 방지)
        }

        return '네이버페이 상품주문'; //fallback - 도달할 일이 없어야 함
    }

    protected function process_payment_response($purchase_data)
    {
        $cfm = intval(edd_get_option('iamport_naverpay_cfm', 2));

        $cfmDateTime = new DateTime('now', new DateTimeZone('Asia/Seoul'));
        $cfmDateTime->add(new DateInterval('P' . $cfm . 'D'));

        $pg = edd_get_option('iamport_naverpay_pg', null);
        if (empty($pg)) {
            $pg = 'naverpay';
        }

        return array(
            'naverProducts' => $this->get_naver_products($purchase_data),
            'naverUseCfm' => $cfmDateTime->format('Ymd'),
            'naverV2' => true,
            'pg' => $pg,
        );
    }

    private function get_naver_products($purchase_data)
    {
        $naver_products = array();
        foreach ($purchase_data['cart_details'] as $key=>$item) {
            //0원 상품은 건너뛰기
            if ($item['price'] <= 0) {
                continue;
            }

            $categoryInfo = $this->get_product_category($item);
            $title = $this->get_product_title($item);

            $naver_products[] = array(
                'categoryType' => $categoryInfo['type'],
                'categoryId' => $categoryInfo['id'],
                'uid' => $this->get_item_uid($item),
                'name' => $title,
                'count' => $item['quantity'],
                //startDate, endDate
            );
        }

        //merge : 같은 uid가 cart_detail 에 반복될 수 있다.
        $merged_products = array();
        foreach ($naver_products as $idx=>$product) {
            $key = $product['uid'];

            if (isset($merged_products[$key])) { //merge
                $merged_products[$key]['count'] += $product['count'];
            } else {
                $merged_products[$key] = $product;
            }
        }

        return array_values($merged_products);
    }

    private function get_product_title($item)
    {
        return preg_replace("/\&#\d{4};/", "*", esc_html( edd_get_cart_item_name( $item ) )); //esc_html이 unicode로 변환한 것을 *로 치환(PG사 특수기호 오류 방지)
    }

    private function get_item_uid($item)
    {
        $post_id = $item['id'];

        if (!empty($item['item_number']['options'])) {
            return $post_id . '-' . $item['item_number']['options']['price_id'];
        }

        return $post_id;
    }

    private function get_product_category($item)
    {
        $post_id = $item['id'];

        if (empty($item['item_number']['options'])) { //단일가격
            $category = get_post_meta($post_id, 'naver_product_category', true);
        } else { //option - variable price
            $prices = edd_get_variable_prices( $post_id ); //variable prices 옵션 안에 카테고리 정보가 저장되어있다.
            $key = $item['item_number']['options']['price_id'];

            $category = $prices[$key]['naver_product_category'];
        }

        if (empty($category) || !in_array($category, array_keys(self::$PRODUCT_CATEGORIES))) { //fallback - default
            $category = 'PRODUCT_DIGITAL_CONTENT';
        }

        $arr = explode("_", $category, 2); //처음만나는 _ 로만 잘라야 함

        return array(
            'type' => $arr[0],
            'id' => $arr[1],
        );

        // === cart_details ===
        //[0] => Array
        //    (
        //        [name] => asdf
        //        [id] => 666
        //        [item_number] => Array
        //            (
        //                [id] => 666
        //                [options] => Array
        //                    (
        //                        [price_id] => 1
        //                    )
        //
        //                [quantity] => 1
        //            )
        //
        //        [item_price] => 1000
        //        [quantity] => 1
        //        [discount] => 0
        //        [subtotal] => 1000
        //        [tax] => 0
        //        [fees] => Array
        //            (
        //            )
        //
        //        [price] => 1000
        //    )
        //
        //[1] => Array
        //    (
        //        [name] => asdf
        //        [id] => 666
        //        [item_number] => Array
        //            (
        //                [id] => 666
        //                [options] => Array
        //                    (
        //                        [price_id] => 2
        //                    )
        //
        //                [quantity] => 1
        //            )
        //
        //        [item_price] => 2000
        //        [quantity] => 1
        //        [discount] => 0
        //        [subtotal] => 2000
        //        [tax] => 0
        //        [fees] => Array
        //            (
        //            )
        //
        //        [price] => 2000
        //    )
        //
        //[2] => Array
        //    (
        //        [name] => test&#8211;te😊s
        //        [id] => 644
        //        [item_number] => Array
        //            (
        //                [id] => 644
        //                [options] => Array
        //                    (
        //                    )
        //
        //                [quantity] => 1
        //            )
        //
        //        [item_price] => 2000
        //        [quantity] => 1
        //        [discount] => 0
        //        [subtotal] => 2000
        //        [tax] => 0
        //        [fees] => Array
        //            (
        //            )
        //
        //        [price] => 2000
        //    )
    }

}
