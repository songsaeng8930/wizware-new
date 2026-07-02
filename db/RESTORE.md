# DB 복구 안내

이 폴더의 `zaemit_groupware_full.sql` 은 데모 데이터가 포함된 전체 DB 백업입니다.
(직원·조직·연차·프로필·재무·결재 등 모든 테이블 + 더미 데이터)

## 복구 방법

MySQL 8.4+ (또는 MySQL 9.x) 이 설치된 환경에서:

```bash
# 1) 빈 DB 생성
mysql -u root -e "CREATE DATABASE IF NOT EXISTS zaemit_groupware CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2) 백업 파일 넣기
mysql -u root zaemit_groupware < db/zaemit_groupware_full.sql
```

Windows(XAMPP 등)에서 mysqldump/mysql 이 인증 플러그인 오류(`caching_sha2_password`)를 내면
정품 MySQL 설치본의 클라이언트를 쓰세요:
`C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe`

## 접속 설정

`config/database.php` 기본값: host `127.0.0.1`, port `3306`, DB `zaemit_groupware`, user `root`, 비밀번호 없음.
환경이 다르면 `config/database.local.php` 를 만들어 오버라이드하세요 (git 제외됨).

## 데모 데이터 다시 넣기

DB 는 있는데 더미만 다시 채우려면:

```bash
php db/seed_annual_leave_demo.php   # 연차 사용내역 (2024~2026)
php db/seed_demo_all.php            # 프로필·연차확장·게시판·예약·할일
```

두 스크립트 모두 멱등(재실행해도 중복 안 쌓임).
