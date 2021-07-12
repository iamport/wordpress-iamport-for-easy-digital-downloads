<p align="center"><a href="https://www.iamport.kr"><img src="https://ps.w.org/iamport-for-easy-digital-downloads/assets/banner-772x250.png" width="100%" alt="Iamport For Woocommerce"></a></p>

> Easy Digital Downloads를 위한 결제 플러그인.

[워드프레스 플러그인 링크](https://wordpress.org/plugins/iamport-for-easy-digital-downloads/) 

아임포트는 국내 PG서비스들을 표준화하고 있는 결제 서비스입니다.<br>
아임포트 하나면 국내 여러 PG사들의 결제 기능을 표준화된 동일한 방식으로 사용할 수 있게 됩니다.

이 플러그인은 아임포트 서비스를 Easy Digital Downloads(EDD)환경에 맞게 적용한 결제 플러그인입니다.

국내 여러 PG사(`KG이니시스`, `나이스정보통신`, `JTNet`, `카카오페이`, `다날`)를 지원합니다. <br>
현재 `신용카드`(일반결제 / 간편결제), `휴대폰 소액결제`, `카카오페이`를 지원합니다.

http://www.iamport.kr 에서 아임포트 서비스에 대한 보다 상세한 내용을 확인하실 수 있습니다.

*   아임포트 관리자 페이지( https://admin.iamport.kr ) 에서 관리자 회원가입을 합니다.
*   아임포트 플러그인을 다운받아 워드프레스에 설치합니다.
*   아임포트 시스템설정 페이지에서 "REST API키", "REST API secret"을 플러그인 설정에 저장합니다.

## 설치
> 아임포트 플러그인 설치, https://admin.iamport.kr 에서 관리자 회원가입, 시스템설정 정보저장이 필요합니다.

1. 다운받은 iamport-for-edd.zip파일을 `/wp-content/plugins/` 디렉토리에 복사합니다.
2. unzip iamport-for-edd.zip으로 압축 파일을 해제하면 iamport-for-edd폴더가 생성됩니다.

![screenshot_1](https://ps.w.org/iamport-for-easy-digital-downloads/assets/screenshot-1.png)

3. https://admin.iamport.kr 에서 관리자 회원가입 후 시스템설정 페이지의 "REST API키", "REST API secret"를 확인합니다.
   
![screenshot_2](https://ps.w.org/iamport-for-easy-digital-downloads/assets/screenshot-2.png)

4. Easy Digital Downloads 결제 설정 페이지에서 "아임포트(비인증결제)" 옵션 활성 체크
   
![screenshot_3](https://ps.w.org/iamport-for-easy-digital-downloads/assets/screenshot-3.png)

5. 아임포트(비인증결제) 설정 페이지에서 "REST API키", "REST API secret" 정보를 저장합니다.


## Action Hook

> 아임포트 for Easy Digital Downloads 플러그인이 제공하는 filter hook
* `iamport_edd_need_subscription` : 진행되는 주문이 정기결제인지 여부를 식별하는 filter. $flag, $edd\_payment, $purchase\_data 3개의 파라메터가 지원되며, `true`를 반환할 경우 빌링키 발급을 위한 결제프로세스가 진행됩니다. PG사에 따라 빌링키 발급단계에서 실제 금액청구가 되지 않는 경우가 있으므로 이 경우에는 첫 과금을 위해서는 별도도 결제요청을 해주어야 합니다. [PG사별 차이점 확인](https://github.com/iamport/iamport-manual/tree/master/%EB%B9%84%EC%9D%B8%EC%A6%9D%EA%B2%B0%EC%A0%9C/example)
* `iamport_edd_subscription_allow_zero` : 정기결제 시, 결제승인없이 최초 카드등록만 진행되는 결제(최초승인금액 : 0원)를 허용할지 판단하는 filter입니다. 반환값은 boolean이며 기본값은 false입니다. 해당 filter를 구현하여 true로 변경하는 경우, EDD에서 지원되는 filter인 `edd_show_gateways` 역시 true로 반환하여야 합니다.
* `iamport_edd_refundable_old_status` : 결제상태가 refunded로 변경되면, 아임포트에 결제환불 API 를 전송하게 됩니다. 이 때, 기존 상태값(old\_status)를 체크하는데 기본적으로는 `publish`, `revoked` 만 허용합니다. 커스텀 상태값 추가 등의 이유로 다른 old\_status 도 환불요청 API 를 허용하시려면 해당 filter를 구현해주세요.

## 변경 내역
1.1.1 버전 이후의 패치는 [Github Releases](https://github.com/iamport/wordpress-iamport-for-easy-digital-downloads/releases) 에서 확인해보실 수 있습니다.<br>
과거 변경 내역은 [여기](https://github.com/iamport/wordpress-iamport-for-easy-digital-downloads/blob/master/manuals/VERSION.md) 있습니다.

## FAQ
### 서비스 소개
https://www.iamport.kr
### 관리자 페이지
https://admin.iamport.kr
### 아임포트 docs
https://docs.iamport.kr
### 페이스북
https://www.facebook.com/iamportservice
### 고객센터
1670-5176 / cs@iamport.kr
