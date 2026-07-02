# Zaemit 그룹웨어 - 설치 가이드

## 필수 요구사항
| 구성 요소 | 최소 버전 | 권장 버전 | 비고 |
|-----------|----------|----------|------|
| PHP | 8.1+ | 8.4 | PDO, curl, json, mbstring 확장 필요 |
| MySQL | 8.0+ | 9.6 | utf8mb4 지원 필수 |
| Apache/Nginx | 2.4+ / 1.18+ | 최신 | mod_rewrite 권장 |
| 웹 브라우저 | Chrome 90+ | 최신 | Tailwind CSS 호환 |

## 방법 1: XAMPP (Windows 권장)

### 1단계: XAMPP 설치
```
1. https://www.apachefriends.org 에서 XAMPP 다운로드 (PHP 8.4 버전)
2. 설치 경로: C:\xampp (기본값)
3. 설치 시 Apache + MySQL + PHP 선택
4. XAMPP Control Panel 실행 → Apache, MySQL 시작
```

### 2단계: 프로젝트 배포
```bash
# 프로젝트를 웹 루트에 배치 (심볼릭 링크 또는 복사)
# 방법 A: htdocs 하위에 복사
xcopy /E /I "D:\www\zaemit_plugin" "C:\xampp\htdocs\zaemit_plugin"

# 방법 B: Apache VirtualHost 설정 (권장)
# C:\xampp\apache\conf\extra\httpd-vhosts.conf 에 추가:
```
```apache
<VirtualHost *:80>
    DocumentRoot "D:/www/zaemit_plugin"
    ServerName zaemit.local
    <Directory "D:/www/zaemit_plugin">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
```
# hosts 파일 편집 (관리자 권한)
# C:\Windows\System32\drivers\etc\hosts 에 추가:
127.0.0.1    zaemit.local
```

### 3단계: PHP 확장 활성화
`C:\xampp\php\php.ini` 에서 아래 확장의 주석(;) 제거:
```ini
extension=pdo_mysql
extension=curl
extension=mbstring
extension=openssl
```

## 방법 2: Docker (크로스 플랫폼)
```yaml
# docker-compose.yml (프로젝트 루트에 생성)
version: '3.8'
services:
  web:
    image: php:8.4-apache
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html

  db:
    image: mysql:9.0
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: zaemit_groupware
      MYSQL_CHARSET: utf8mb4
    volumes:
      - ./db/init.sql:/docker-entrypoint-initdb.d/init.sql
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```
```bash
docker-compose up -d
# 접속: http://localhost:8080
```

## 방법 3: 수동 설치 (Linux/macOS)
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y apache2 php8.4 php8.4-mysql php8.4-curl php8.4-mbstring mysql-server

# macOS (Homebrew)
brew install php@8.4 mysql
brew services start php
brew services start mysql

# 프로젝트를 웹 루트에 심볼릭 링크
sudo ln -s /path/to/zaemit_plugin /var/www/html/zaemit_plugin
```

## 데이터베이스 초기화

### 통합 스크립트 (권장)
```bash
# 전체 DB 생성 + 테이블 + 샘플 데이터 한번에 실행
mysql -u root -p < db/init.sql
```

### 개별 스키마 실행 (순서 중요)
외래키 의존성 때문에 반드시 아래 순서대로 실행:
```bash
# 1. 데이터베이스 생성
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS zaemit_groupware CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"

# 2. 조직/인사 (기본 테이블)
mysql -u root -p zaemit_groupware < db/schema_organization.sql

# 3. 직원 확장 필드
mysql -u root -p zaemit_groupware < db/schema_employees_update.sql

# 4. 공통코드
mysql -u root -p zaemit_groupware < db/schema_common_codes.sql

# 5. 전자결재
mysql -u root -p zaemit_groupware < db/schema_approval.sql

# 6. 법인카드
mysql -u root -p zaemit_groupware < db/schema_card.sql

# 7. 자원예약
mysql -u root -p zaemit_groupware < db/schema_reservation.sql

# 8. 세무/회계
mysql -u root -p zaemit_groupware < db/schema_tax.sql

# 9. 세금계산서
mysql -u root -p zaemit_groupware < db/schema_tax_invoice.sql
```

## DB 접속 설정
`config/database.php` 수정:
```php
define('DB_HOST', '127.0.0.1');   // DB 호스트
define('DB_PORT', 3306);           // DB 포트
define('DB_NAME', 'zaemit_groupware');  // DB명
define('DB_USER', 'root');         // DB 사용자
define('DB_PASS', '');             // DB 비밀번호 (운영환경에서 반드시 변경)
```

## API 설정 (선택)
Bank API 등 외부 연동이 필요한 경우:
1. 웹 브라우저에서 `그룹웨어 관리 > API 설정` 페이지 접속
2. bankapi.co.kr API Key/Secret 입력
3. 연결 테스트 버튼으로 확인

설정값은 `config/api_settings.json`에 저장됨.

## 인증 초기화 (필수)

`db/init.sql` 은 테이블·샘플 직원까지만 생성하고 **비밀번호는 설정하지 않습니다**.
아래 한 번만 수행하면 테스트 계정(`ceo@zaemit.com`, `jung@zaemit.com` 등)으로 로그인할 수 있습니다.

```bash
# 1) password_hash / user_role 컬럼 추가 (이미 추가되어 있으면 ALTER 블록만 생략)
mysql -u root -p zaemit_groupware < db/schema_auth.sql
```

```
2) 브라우저에서 한 번 접근하여 기본 비밀번호 설정:
   http://localhost/zaemit_plugin/pages/setup_passwords.php
   → "비어있는 계정에 비밀번호 설정" 버튼 클릭 → zaemit1234 로 일괄 세팅
```

> 이 페이지는 이미 해시가 있는 계정은 건드리지 않습니다. 운영 배포 전 `pages/setup_passwords.php` 는 **삭제하거나 접근 차단**하세요.

`"이메일 또는 비밀번호가 올바르지 않습니다"` 오류가 뜬다면 대부분 `employees.password_hash` 가 NULL 인 상태입니다. 위 단계를 다시 실행하세요.

## 접속 확인
```
http://localhost/zaemit_plugin/          → 대시보드 리다이렉트
http://localhost/zaemit_plugin/pages/dashboard.php  → 메인 대시보드
```
