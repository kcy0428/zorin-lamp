# LAMP Stack 온라인 쇼핑몰 프로젝트

## 프로젝트 개요
- **프로젝트명**: ZorinShop - LAMP 기반 온라인 쇼핑몰
- **개발 환경**: Zorin OS + LAMP Stack (Linux, Apache, MySQL, PHP)
- **목적**: LAMP 스택을 활용한 풀스택 웹 쇼핑몰 구현

## 기술 스택
- **OS**: Zorin OS (Ubuntu 기반)
- **Web Server**: Apache2
- **Database**: MySQL / MariaDB
- **Backend**: PHP 8.x
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5

## 주요 기능

### 1. 회원 관리
- 회원가입 / 로그인 / 로그아웃
- 세션 기반 인증
- 회원 정보 수정

### 2. 상품 관리
- 상품 목록 조회 (카테고리별 필터링)
- 상품 상세 페이지
- 상품 검색
- 관리자: 상품 등록 / 수정 / 삭제

### 3. 장바구니
- 상품 장바구니 추가
- 수량 변경 / 삭제
- 장바구니 목록 조회

### 4. 주문 처리
- 주문서 작성
- 주문 내역 조회
- 주문 상태 관리 (주문완료 / 배송중 / 배송완료)

### 5. 관리자 페이지
- 대시보드 (매출 현황, 주문 현황)
- 상품 관리 CRUD
- 주문 관리

## 데이터베이스 설계

### 테이블 구조
```
users          - 회원 정보 (id, name, email, password, role, created_at)
categories     - 상품 카테고리 (id, name, description)
products       - 상품 정보 (id, category_id, name, description, price, stock, image, created_at)
cart           - 장바구니 (id, user_id, product_id, quantity)
orders         - 주문 헤더 (id, user_id, total_price, status, created_at)
order_items    - 주문 상세 (id, order_id, product_id, quantity, price)
```

## 시스템 아키텍처

### 디렉토리 구조
```
/var/www/html/shop/
├── index.php          - 메인 페이지 (상품 목록)
├── product.php        - 상품 상세
├── cart.php           - 장바구니
├── checkout.php       - 주문서
├── orders.php         - 주문 내역
├── login.php          - 로그인
├── register.php       - 회원가입
├── logout.php         - 로그아웃
├── admin/
│   ├── index.php      - 관리자 대시보드
│   ├── products.php   - 상품 관리
│   └── orders.php     - 주문 관리
├── includes/
│   ├── db.php         - DB 연결
│   ├── auth.php       - 인증 함수
│   └── functions.php  - 공통 함수
├── css/
│   └── style.css      - 커스텀 스타일
└── uploads/
    └── products/      - 상품 이미지 저장
```

## 개발 계획
1. DB 설계 및 생성
2. 회원 기능 구현 (로그인/회원가입)
3. 상품 목록/상세 페이지 구현
4. 장바구니 기능 구현
5. 주문 기능 구현
6. 관리자 페이지 구현
7. UI/UX 개선 및 테스트
