# 과거 변경 내역
> 1.1.1 이전 버전의 패치노트입니다.

* 1.1.1
    * 내부 개발용 : SVN 자동배포 적용

* 1.1.0
    * 아임포트 내 복수PG 설정 시 결제수단별 지정이 가능하도록 구분자 입력 처리
    * 네이버페이(결제형) 추가

* 1.0.7
    * 플러그인 확장이 가능하도록 아임포트 통신 클래스 속성에 대한 접근자 변경 및 환불가능 filter 추가

* 1.0.6
    * 실시간계좌이체 결제수단 추가 ([By Shoplic](https://shoplic.kr))
    * Checkout 페이지에서 이름 / 성 중 하나가 없어도 스크립트 오류나지 않도록 개선
    * customer_uid 생성시 특수기호 제거

* 1.0.5
    * 정기결제 카드등록만 가능하도록 0원 결제 허용 (iamport\_edd\_subscription\_allow\_zero 필터 적용)

* 1.0.4
    * 결제창 방식 결제에도 purchase\_key 를 merchant\_uid 로 사용
    * merchant\_uid 를 활용한 결제데이터 검증으로 안전성 강화
    * card-name class 제거하여 카드번호 validation 회피

* 1.0.3
    * user\_email 값이 비어있어 customer\_uid 생성 버그 수정

* 1.0.2
    * 결제창을 통한 빌링키 발급 지원( KG이니시스 / KCP / 다날 / JTNet 신용카드 빌링, 휴대폰 소액결제 빌링) - iamport\_edd\_need\_subscription 필터 적용

* 1.0.1
    * 상품명에 PG사가 지원하지 않는 unicode가 포함되면 \ * 기호로 대체하도록 수정
    * 신규 카카오페이 결제 지원

* 1.0.0
    * Checkout 페이지에서 회원가입, 비회원 등 모두 대응할 수 있도록 수정(EDD 내장 함수 사용)

* 0.9.18
    * 로그인된 사용자 구매시 주문서 입력필드 전달 및 회원 계정ID와 연결될 수 있도록 수정.
    * currency-switcher 플러그인을 이용해 주문 시 구매자가 currency를 선택하는 경우 동적으로 대응가능하도록 버그 수정( [issue](https://wordpress.org/support/topic/fix-for-the-gateway-to-take-the-correct-currency) )

* 0.9.17
    * 0.9.16 bug hotfix

* 0.9.16
    * EasyDigitalDownload플러그인이 설치/활성화되어있는지 체크하여 에러메세지 출력할 수 있도록 수정

* 0.9.15
    * 결제 시, 구매자 전화번호 필수로 입력받지 않아도 되도록 수정

* 0.9.14
    * 로그인되지 않은 상태에서 결제 진행하는 경우 이름, 이메일, 전화번호 등 정보 저장될 수 있도록 수정

* 0.9.13
    * 삼성페이 결제수단 추가
    * iamport.payment.js 버전 1.1.2로 상향

* 0.9.12
    * KRW 화폐단위일 때에만 소수점 출력되지 않도록 수정
    * ajaxPrefilter 부분에 type check 버그로 인한 스크립트 오류 수정

* 0.9.11
    * 아임포트 REST API연동 라이브러리(iamport.php) 함수명 변경 및 schedule/unschedule 함수 추가

* 0.9.10
    * Easy Digital Download Recurring Payment를 위해 구매자가 희망하는 경우 customer_uid생성 후 저장하도록 수정. edd_get_payment_meta( $payment_id, EddIamportGateway::CUSTOMER_UID_META_KEY );

* 0.9.9
    * 간편 카드결제 시 카드정보전달구간 RSA암호화 처리(jQuery 1.5버전 이상을 필요로 합니다)
    * PG사별 일반 신용카드 결제 / 카카오페이 / 휴대폰 소액결제 추가 (실시간계좌이체, 가상계좌는 다음 버전에 추가 예정)
    * 구매자 휴대폰번호를 필수 입력받도록 수정합니다 (PG사 결제시 구매자 전화번호가 없으면 오류나는 경우가 많음)
    * 카드정보를 입력받는 legend tag가 css틀어지는 버그 수정

* 0.9.2
    * 결제 실패시, 에러메세지 출력 & alert팝업이 뜨도록 수정

* 0.9.1
    * 원화 표시할 때 symbol적용 및 소수점 제거

* 0.9.0
    * 간편 신용카드 결제를 위한 최초 플러그인 배포
