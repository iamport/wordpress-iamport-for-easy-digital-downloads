=== Plugin Name ===
Contributors: movingcart,shoplic
Donate link: https://www.iamport.kr
Tags: edd, easy digital downloads, download, commerce, payment, checkout, 카카오페이, kakao, kakaopay, 이니시스, kpay, inicis, 유플러스, lguplus, uplus, 나이스, 나이스페이, nice, nicepay, 제이티넷, 티페이, jtnet, tpay, 다날, danal, 정기결제, subscription, 해외카드, visa, master, jcb, shopping, mall, iamport
Requires at least: 3.0.1
Tested up to: 5.0.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easy Digital Downloads를 위한 결제 플러그인. 국내 여러 PG사(KG이니시스, 나이스정보통신, JTNet, 카카오페이, 다날)를 지원합니다. 현재 신용카드(일반결제 / 간편결제), 휴대폰 소액결제, 카카오페이를 지원합니다.

== Description ==

아임포트는 국내 PG서비스들을 표준화하고 있는 결제 서비스입니다. 아임포트 하나면 국내 여러 PG사들의 결제 기능을 표준화된 동일한 방식으로 사용할 수 있게 됩니다.
이 플러그인은 아임포트 서비스를 Easy Digital Downloads(EDD)환경에 맞게 적용한 결제 플러그인입니다.

http://www.iamport.kr 에서 아임포트 서비스에 대한 보다 상세한 내용을 확인하실 수 있습니다.

*   아임포트 관리자 페이지( https://admin.iamport.kr ) 에서 관리자 회원가입을 합니다.
*   아임포트 플러그인을 다운받아 워드프레스에 설치합니다.
*   아임포트 시스템설정 페이지에서 "REST API키", "REST API secret"을 플러그인 설정에 저장합니다.


== Installation ==

아임포트 플러그인 설치, https://admin.iamport.kr 에서 관리자 회원가입, 시스템설정 정보저장이 필요합니다.


1. 다운받은 iamport-for-edd.zip파일을 `/wp-content/plugins/` 디렉토리에 복사합니다.
2. unzip iamport-for-edd.zip으로 압축 파일을 해제하면 iamport-for-edd폴더가 생성됩니다.
3. 워드프레스 관리자페이지에서 'Plugins'메뉴를 통해 "아임포트" 플러그인을 활성화합니다.
4. https://admin.iamport.kr 에서 관리자 회원가입 후 시스템설정 페이지의 "REST API키", "REST API secret"를 확인합니다.
5. Easy Digital Downloads 결제 설정페이지에서 해당 정보를 저장합니다.

== Frequently Asked Questions ==
= 서비스 소개 =
http://www.iamport.kr
= 관리자 페이지 =
https://admin.iamport.kr
= 페이스북 =
https://www.facebook.com/iamportservice

= 고객센터 =
1670-5176 / iamport@siot.do

== Screenshots ==
1. 아임포트 관리자 로그인 후 "시스템 설정" 페이지에서 "REST API키", "REST API secret" 정보를 확인합니다.
2. Easy Digital Downloads 결제 설정 페이지에서 "아임포트(비인증결제)" 옵션 활성 체크
3. 아임포트(비인증결제) 설정 페이지에서 "REST API키", "REST API secret" 정보를 저장합니다.


== Changelog ==
= 1.1.0 =
* 아임포트 내 복수PG 설정 시 결제수단별 지정이 가능하도록 구분자 입력 처리
* 네이버페이(결제형) 추가

= 1.0.7 =
* 플러그인 확장이 가능하도록 아임포트 통신 클래스 속성에 대한 접근자 변경 및 환불가능 filter 추가

= 1.0.6 =
* 실시간계좌이체 결제수단 추가 ([By Shoplic](https://shoplic.kr))
* Checkout 페이지에서 이름 / 성 중 하나가 없어도 스크립트 오류나지 않도록 개선
* customer_uid 생성시 특수기호 제거

= 1.0.5 =
* 정기결제 카드등록만 가능하도록 0원 결제 허용 (iamport\_edd\_subscription\_allow\_zero 필터 적용)

= 1.0.4 =
* 결제창 방식 결제에도 purchase\_key 를 merchant\_uid 로 사용
* merchant\_uid 를 활용한 결제데이터 검증으로 안전성 강화
* card-name class 제거하여 카드번호 validation 회피

= 1.0.3 =
* user\_email 값이 비어있어 customer\_uid 생성 버그 수정

= 1.0.2 =
* 결제창을 통한 빌링키 발급 지원( KG이니시스 / KCP / 다날 / JTNet 신용카드 빌링, 휴대폰 소액결제 빌링) - iamport\_edd\_need\_subscription 필터 적용

= 1.0.1 =
* 상품명에 PG사가 지원하지 않는 unicode가 포함되면 \* 기호로 대체하도록 수정
* 신규 카카오페이 결제 지원

= 1.0.0 =
* Checkout 페이지에서 회원가입, 비회원 등 모두 대응할 수 있도록 수정(EDD 내장 함수 사용)

= 0.9.18 =
* 로그인된 사용자 구매시 주문서 입력필드 전달 및 회원 계정ID와 연결될 수 있도록 수정.
* currency-switcher 플러그인을 이용해 주문 시 구매자가 currency를 선택하는 경우 동적으로 대응가능하도록 버그 수정( [issue](https://wordpress.org/support/topic/fix-for-the-gateway-to-take-the-correct-currency) )

= 0.9.17 =
* 0.9.16 bug hotfix

= 0.9.16 =
* EasyDigitalDownload플러그인이 설치/활성화되어있는지 체크하여 에러메세지 출력할 수 있도록 수정

= 0.9.15 =
* 결제 시, 구매자 전화번호 필수로 입력받지 않아도 되도록 수정

= 0.9.14 =
* 로그인되지 않은 상태에서 결제 진행하는 경우 이름, 이메일, 전화번호 등 정보 저장될 수 있도록 수정

= 0.9.13 =
* 삼성페이 결제수단 추가
* iamport.payment.js 버전 1.1.2로 상향

= 0.9.12 =
* KRW 화폐단위일 때에만 소수점 출력되지 않도록 수정
* ajaxPrefilter 부분에 type check 버그로 인한 스크립트 오류 수정

= 0.9.11 =
* 아임포트 REST API연동 라이브러리(iamport.php) 함수명 변경 및 schedule/unschedule 함수 추가

= 0.9.10 =
* Easy Digital Download Recurring Payment를 위해 구매자가 희망하는 경우 customer_uid생성 후 저장하도록 수정. edd_get_payment_meta( $payment_id, EddIamportGateway::CUSTOMER_UID_META_KEY );

= 0.9.9 =
* 간편 카드결제 시 카드정보전달구간 RSA암호화 처리(jQuery 1.5버전 이상을 필요로 합니다)
* PG사별 일반 신용카드 결제 / 카카오페이 / 휴대폰 소액결제 추가 (실시간계좌이체, 가상계좌는 다음 버전에 추가 예정)
* 구매자 휴대폰번호를 필수 입력받도록 수정합니다 (PG사 결제시 구매자 전화번호가 없으면 오류나는 경우가 많음)
* 카드정보를 입력받는 legend tag가 css틀어지는 버그 수정

= 0.9.2 =
* 결제 실패시, 에러메세지 출력 & alert팝업이 뜨도록 수정

= 0.9.1 =
* 원화 표시할 때 symbol적용 및 소수점 제거

= 0.9.0 =
* 간편 신용카드 결제를 위한 최초 플러그인 배포


== Action Hook ==

= 아임포트 for Easy Digital Downloads 플러그인이 제공하는 filter hook =
* `iamport_edd_need_subscription` : 진행되는 주문이 정기결제인지 여부를 식별하는 filter. $flag, $edd\_payment, $purchase\_data 3개의 파라메터가 지원되며, `true`를 반환할 경우 빌링키 발급을 위한 결제프로세스가 진행됩니다. PG사에 따라 빌링키 발급단계에서 실제 금액청구가 되지 않는 경우가 있으므로 이 경우에는 첫 과금을 위해서는 별도도 결제요청을 해주어야 합니다. [PG사별 차이점 확인](https://github.com/iamport/iamport-manual/tree/master/%EB%B9%84%EC%9D%B8%EC%A6%9D%EA%B2%B0%EC%A0%9C/example)
* `iamport_edd_subscription_allow_zero` : 정기결제 시, 결제승인없이 최초 카드등록만 진행되는 결제(최초승인금액 : 0원)를 허용할지 판단하는 filter입니다. 반환값은 boolean이며 기본값은 false입니다. 해당 filter를 구현하여 true로 변경하는 경우, EDD에서 지원되는 filter인 `edd_show_gateways` 역시 true로 반환하여야 합니다.
* `iamport_edd_refundable_old_status` : 결제상태가 refunded로 변경되면, 아임포트에 결제환불 API 를 전송하게 됩니다. 이 때, 기존 상태값(old\_status)를 체크하는데 기본적으로는 `publish`, `revoked` 만 허용합니다. 커스텀 상태값 추가 등의 이유로 다른 old\_status 도 환불요청 API 를 허용하시려면 해당 filter를 구현해주세요.
