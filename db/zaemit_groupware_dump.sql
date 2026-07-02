-- MySQL dump 10.13  Distrib 8.4.9, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: zaemit_groupware
-- ------------------------------------------------------
-- Server version	8.4.9

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `zaemit_groupware`
--

/*!40000 DROP DATABASE IF EXISTS `zaemit_groupware`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `zaemit_groupware` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `zaemit_groupware`;

--
-- Table structure for table `account_categories`
--

DROP TABLE IF EXISTS `account_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL COMMENT '계정과목 코드 (예: 511)',
  `name` varchar(100) NOT NULL COMMENT '계정과목명 (예: 급여)',
  `parent_code` varchar(10) DEFAULT NULL COMMENT '상위 계정과목 코드',
  `type` enum('매출','매입','자산','부채','자본','비용','수익') NOT NULL DEFAULT '비용',
  `tax_type` enum('과세','면세','영세율','불공제') NOT NULL DEFAULT '과세',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='계정과목 마스터';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_categories`
--

LOCK TABLES `account_categories` WRITE;
/*!40000 ALTER TABLE `account_categories` DISABLE KEYS */;
INSERT INTO `account_categories` VALUES (1,'401','상품매출',NULL,'매출','과세',1,10,'2026-05-13 10:00:48'),(2,'402','제품매출',NULL,'매출','과세',1,20,'2026-05-13 10:00:48'),(3,'403','서비스매출',NULL,'매출','과세',1,30,'2026-05-13 10:00:48'),(4,'404','임대수입',NULL,'매출','과세',1,40,'2026-05-13 10:00:48'),(5,'501','상품매입',NULL,'매입','과세',1,10,'2026-05-13 10:00:48'),(6,'502','원재료매입',NULL,'매입','과세',1,20,'2026-05-13 10:00:48'),(7,'511','급여',NULL,'비용','불공제',1,10,'2026-05-13 10:00:48'),(8,'512','퇴직급여',NULL,'비용','불공제',1,20,'2026-05-13 10:00:48'),(9,'513','복리후생비',NULL,'비용','과세',1,30,'2026-05-13 10:00:48'),(10,'521','임차료',NULL,'비용','과세',1,40,'2026-05-13 10:00:48'),(11,'522','지급수수료',NULL,'비용','과세',1,50,'2026-05-13 10:00:48'),(12,'523','광고선전비',NULL,'비용','과세',1,60,'2026-05-13 10:00:48'),(13,'524','접대비',NULL,'비용','과세',1,70,'2026-05-13 10:00:48'),(14,'525','통신비',NULL,'비용','과세',1,80,'2026-05-13 10:00:48'),(15,'526','소모품비',NULL,'비용','과세',1,90,'2026-05-13 10:00:48'),(16,'527','차량유지비',NULL,'비용','과세',1,100,'2026-05-13 10:00:48'),(17,'528','여비교통비',NULL,'비용','과세',1,110,'2026-05-13 10:00:48'),(18,'529','교육훈련비',NULL,'비용','과세',1,120,'2026-05-13 10:00:48'),(19,'531','감가상각비',NULL,'비용','불공제',1,130,'2026-05-13 10:00:48'),(20,'532','보험료',NULL,'비용','불공제',1,140,'2026-05-13 10:00:48'),(21,'533','세금과공과',NULL,'비용','불공제',1,150,'2026-05-13 10:00:48'),(22,'601','이자수익',NULL,'수익','불공제',1,10,'2026-05-13 10:00:48'),(23,'602','잡이익',NULL,'수익','불공제',1,20,'2026-05-13 10:00:48'),(24,'701','이자비용',NULL,'비용','불공제',1,10,'2026-05-13 10:00:48'),(25,'801','현금',NULL,'자산','불공제',1,10,'2026-05-13 10:00:48'),(26,'802','보통예금',NULL,'자산','불공제',1,20,'2026-05-13 10:00:48'),(27,'803','외상매출금',NULL,'자산','불공제',1,30,'2026-05-13 10:00:48'),(28,'901','외상매입금',NULL,'부채','불공제',1,10,'2026-05-13 10:00:48'),(29,'902','미지급금',NULL,'부채','불공제',1,20,'2026-05-13 10:00:48');
/*!40000 ALTER TABLE `account_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `annual_leave`
--

DROP TABLE IF EXISTS `annual_leave`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `annual_leave` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'employees.id',
  `year` smallint NOT NULL COMMENT '귀속년도',
  `total_days` decimal(4,1) NOT NULL DEFAULT '15.0' COMMENT '총 부여일수',
  `used_days` decimal(4,1) NOT NULL DEFAULT '0.0' COMMENT '사용일수',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_year` (`employee_id`,`year`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='연차 잔액';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annual_leave`
--

LOCK TABLES `annual_leave` WRITE;
/*!40000 ALTER TABLE `annual_leave` DISABLE KEYS */;
INSERT INTO `annual_leave` VALUES (1,1,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(2,2,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(3,3,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(4,4,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(5,5,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(6,6,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(7,7,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(8,8,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(9,9,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(10,10,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(11,11,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(12,12,2026,17.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(13,13,2026,16.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(14,14,2026,16.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(15,15,2026,16.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(16,16,2026,16.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(17,17,2026,16.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(18,18,2026,16.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(19,19,2026,16.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57'),(20,20,2026,16.0,0.0,'2026-05-13 10:05:57','2026-05-13 10:05:57');
/*!40000 ALTER TABLE `annual_leave` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_documents`
--

DROP TABLE IF EXISTS `approval_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doc_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '문서번호',
  `title` varchar(300) COLLATE utf8mb4_general_ci NOT NULL COMMENT '제목',
  `content` text COLLATE utf8mb4_general_ci COMMENT '문서 내용',
  `metadata` json DEFAULT NULL COMMENT '연동 데이터 (예: {"source":"card_expense","source_id":123})',
  `form_id` int DEFAULT NULL COMMENT '결재양식 ID',
  `doc_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '문서종류',
  `drafter_id` int DEFAULT NULL,
  `drafter_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '기안자',
  `drafter_dept` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '기안부서',
  `status` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '기안' COMMENT '상태 (기안/진행/승인/반려/임시저장)',
  `draft_date` date NOT NULL COMMENT '기안일',
  `complete_date` date DEFAULT NULL COMMENT '완료일',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  KEY `idx_drafter_id` (`drafter_id`),
  CONSTRAINT `approval_documents_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `approval_forms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_documents`
--

LOCK TABLES `approval_documents` WRITE;
/*!40000 ALTER TABLE `approval_documents` DISABLE KEYS */;
INSERT INTO `approval_documents` VALUES (1,'Zaemit_개발_품의_20260212110327','NHN클라우드 2026년 01월 청구서',NULL,NULL,NULL,'품의서',8,'강개발','개발1팀','진행','2026-02-12',NULL,'2026-05-13 10:00:48','2026-05-13 10:01:15'),(2,'Zaemit_개발_품의_20251212153040','NHN클라우드 2025년 11월 청구서',NULL,NULL,NULL,'품의서',8,'강개발','개발1팀','승인','2025-12-12','2025-12-13','2026-05-13 10:00:48','2026-05-13 10:01:15'),(3,'Zaemit_개발_품의_20251111163956','NHN클라우드 2025년 10월 청구서',NULL,NULL,NULL,'품의서',8,'강개발','개발1팀','승인','2025-11-11','2025-11-12','2026-05-13 10:00:48','2026-05-13 10:01:15'),(4,'Zaemit_개발_품의_20251021133245','NHN클라우드 2025년 9월 청구서',NULL,NULL,NULL,'품의서',8,'강개발','개발1팀','승인','2025-10-21','2025-10-22','2026-05-13 10:00:48','2026-05-13 10:01:15'),(5,'Zaemit_개발_품의_20250912084647','NHN클라우드 2025년 8월 청구서',NULL,NULL,NULL,'품의서',8,'강개발','개발1팀','승인','2025-09-12','2025-09-13','2026-05-13 10:00:48','2026-05-13 10:01:15'),(6,'Zaemit_개발_품의_20250808093827','NHN클라우드 2025년 7월 청구서',NULL,NULL,NULL,'품의서',8,'강개발','개발1팀','승인','2025-08-08','2025-08-09','2026-05-13 10:00:48','2026-05-13 10:01:15'),(7,'Zaemit_개발_품의_20250715091450','NHN클라우드 2025년 6월 청구서',NULL,NULL,NULL,'품의서',8,'강개발','개발1팀','승인','2025-07-15','2025-07-16','2026-05-13 10:00:48','2026-05-13 10:01:15'),(8,'Zaemit_개발_품의_20250619163208','NHN클라우드 2025년 5월 청구서',NULL,NULL,NULL,'품의서',8,'강개발','개발1팀','승인','2025-06-19','2025-06-20','2026-05-13 10:00:48','2026-05-13 10:01:15'),(9,'ZgAi_개발_품의_20250520162607','NHN클라우드 2025년 4월 청구서',NULL,NULL,NULL,'품의서',8,'강개발','개발1팀','승인','2025-05-20','2025-05-21','2026-05-13 10:00:48','2026-05-13 10:01:15'),(10,'ZgAi_개발_품의_20250421150148','NHN클라우드 2025년 3월 청구서',NULL,NULL,NULL,'품의서',8,'강개발','개발1팀','승인','2025-04-21','2025-04-22','2026-05-13 10:00:48','2026-05-13 10:01:15'),(11,'Zaemit_개발_휴가_20260225100000','연차 사용 신청',NULL,NULL,NULL,'휴가신청서',NULL,'김영수','Zaemit 개발','기안','2026-02-25',NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(12,'Zaemit_개발_출장_20260224090000','부산 출장 신청',NULL,NULL,NULL,'출장신청서',NULL,'이정민','Zaemit 개발','임시저장','2026-02-24',NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(13,'Zaemit_경영_품의_20260220140000','사무용품 구매 품의',NULL,NULL,NULL,'품의서',NULL,'박지현','위즈웨어 경영지원','반려','2026-02-20','2026-02-21','2026-05-13 10:00:48','2026-05-13 10:00:48');
/*!40000 ALTER TABLE `approval_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_forms`
--

DROP TABLE IF EXISTS `approval_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_forms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doc_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '문서종류',
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT '양식 제목',
  `content_template` text COLLATE utf8mb4_general_ci COMMENT '양식 HTML 템플릿',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '사용유무',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_forms`
--

LOCK TABLES `approval_forms` WRITE;
/*!40000 ALTER TABLE `approval_forms` DISABLE KEYS */;
INSERT INTO `approval_forms` VALUES (1,'법인카드 지출','법인카드 지출','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 법인카드 사용 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">사 용 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">카드번호</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">****-****-****-____</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">사용일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">가 맹 점</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">사용금액</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">사용계정</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 접대비&nbsp;&nbsp;&nbsp;□ 회의비&nbsp;&nbsp;&nbsp;□ 복리후생비&nbsp;&nbsp;&nbsp;□ 소모품비&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">참 석 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">사용목적</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">※ 영수증(매출전표)을 반드시 첨부해 주시기 바랍니다.</p>',1,'2025-09-23 00:00:00','2026-05-13 10:01:15'),(2,'변경품의서','원가변경품의','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 원가 변경 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:140px;\">대상 품목/프로젝트</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">변경일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 8px 0;font-size:13px;font-weight:600;\">■ 변경 전/후 비교</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<thead>\n<tr style=\"background:#f3f4f6;\">\n<th style=\"border:1px solid #d1d5db;padding:8px;\">구&nbsp;&nbsp;&nbsp;&nbsp;분</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;\">변경 전</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;\">변경 후</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;\">차&nbsp;&nbsp;&nbsp;&nbsp;이</th>\n</tr>\n</thead>\n<tbody>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;background:#f9fafb;font-weight:600;\">금&nbsp;&nbsp;&nbsp;&nbsp;액</td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td></tr>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;background:#f9fafb;font-weight:600;\">수&nbsp;&nbsp;&nbsp;&nbsp;량</td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\"><br></td></tr>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;background:#f9fafb;font-weight:600;\">기&nbsp;&nbsp;&nbsp;&nbsp;타</td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 8px 0;font-size:13px;font-weight:600;\">■ 변경 사유 및 영향</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;vertical-align:top;\">변경사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">영 향 도</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 원가 변경을 품의하오니 재가하여 주시기 바랍니다.</p>',0,'2022-11-17 00:00:00','2026-05-13 10:01:15'),(3,'지급품의서','비용지급품의','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 비용 지급 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">지급대상</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">지급항목</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">지급금액</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원 (부가세 포함/별도)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">지급일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">지급방법</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 계좌이체&nbsp;&nbsp;&nbsp;□ 법인카드&nbsp;&nbsp;&nbsp;□ 현금&nbsp;&nbsp;&nbsp;□ 어음/수표</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">사용계정</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">지급사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">증빙서류</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 세금계산서&nbsp;&nbsp;&nbsp;□ 계산서&nbsp;&nbsp;&nbsp;□ 현금영수증&nbsp;&nbsp;&nbsp;□ 카드전표&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 비용 지급을 품의하오니 재가하여 주시기 바랍니다.</p>',0,'2022-11-17 00:00:00','2026-05-13 10:01:15'),(4,'발의품의서','발의품의서','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 발의 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">제&nbsp;&nbsp;&nbsp;&nbsp;목</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">발 의 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">발의배경</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">제안내용</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">기대효과</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소요예산</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">추진일정</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">참고자료</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">위와 같이 발의하오니 검토 후 재가하여 주시기 바랍니다.</p>',0,'2022-11-17 00:00:00','2026-05-13 10:01:15'),(5,'기안취소','기안취소','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 기안 취소 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:140px;\">원기안 문서번호</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">원기안 제목</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">원기안 일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">원기안 기안자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">진행상태</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 결재 진행중&nbsp;&nbsp;&nbsp;□ 결재 완료&nbsp;&nbsp;&nbsp;□ 시행 전</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">취소사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기 기안에 대해 아래의 사유로 취소하고자 하오니 재가하여 주시기 바랍니다.</p>',0,'2022-11-17 00:00:00','2026-05-13 10:01:15'),(6,'휴일근무','휴일근무','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 휴일근무 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">근 무 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">근무일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (&nbsp;&nbsp;요일)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">근무시간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 ~&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 (총&nbsp;&nbsp;&nbsp;&nbsp;시간)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">근무구분</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 토요일&nbsp;&nbsp;&nbsp;□ 일요일&nbsp;&nbsp;&nbsp;□ 법정공휴일&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">근무사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">업무내용</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">보&nbsp;&nbsp;&nbsp;&nbsp;상</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 대체휴무&nbsp;&nbsp;&nbsp;□ 휴일수당&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 휴일근무를 신청하오니 승인하여 주시기 바랍니다.</p>',1,'2022-11-15 00:00:00','2026-05-13 10:01:15'),(7,'휴가신청서','휴가신청서','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 휴가 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">신 청 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">휴가종류</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 연차&nbsp;&nbsp;&nbsp;□ 반차(오전/오후)&nbsp;&nbsp;&nbsp;□ 병가&nbsp;&nbsp;&nbsp;□ 경조사&nbsp;&nbsp;&nbsp;□ 기타(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">휴가기간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 ~&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (총&nbsp;&nbsp;&nbsp;&nbsp;일)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">비상연락처</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">휴가사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">업무인수자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 휴가를 신청하오니 승인하여 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-05-13 10:01:15'),(8,'출장신청서','출장신청서','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 출장 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">출 장 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">출 장 지</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">출장기간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 ~&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (&nbsp;&nbsp;박&nbsp;&nbsp;일)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">동 행 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">출장목적</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">세부업무</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">교 통 편</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 항공&nbsp;&nbsp;&nbsp;□ KTX/기차&nbsp;&nbsp;&nbsp;□ 버스&nbsp;&nbsp;&nbsp;□ 자차&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">예상경비</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원 (교통비:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;, 숙박비:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;, 식대:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;, 기타:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 출장을 신청하오니 승인하여 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-05-13 10:01:15'),(9,'외근신청서','외근신청서','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 외근 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">외 근 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">외근일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">외근시간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">출발&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 ~ 복귀&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">방 문 처</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">외근목적</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">연 락 처</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">복귀여부</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 사무실 복귀&nbsp;&nbsp;&nbsp;□ 직접 퇴근</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 외근을 신청하오니 승인하여 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-05-13 10:01:15'),(10,'야근신청서','야근신청서','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 야근 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">신 청 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">야근일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">야근시간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 ~&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 (총&nbsp;&nbsp;&nbsp;&nbsp;시간)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">야근사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">업무내용</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">식대/교통비</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 신청&nbsp;&nbsp;&nbsp;□ 미신청&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;금액:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 야근을 신청하오니 승인하여 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-05-13 10:01:15'),(11,'품의서','품의서','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 품의 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">제&nbsp;&nbsp;&nbsp;&nbsp;목</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">목&nbsp;&nbsp;&nbsp;&nbsp;적</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">시행일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소요예산</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">세부내용</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">참고사항</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">위와 같이 품의하오니 재가하여 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-05-13 10:01:15'),(12,'경비청구서','경비청구서','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 경비 청구 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">청 구 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">청구일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 8px 0;font-size:13px;font-weight:600;\">■ 사용 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<thead>\n<tr style=\"background:#f3f4f6;\">\n<th style=\"border:1px solid #d1d5db;padding:8px;width:100px;\">사용일자</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;\">사용처</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;\">내역</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;width:120px;\">금액</th>\n</tr>\n</thead>\n<tbody>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td></tr>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td></tr>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td></tr>\n<tr><td colspan=\"3\" style=\"border:1px solid #d1d5db;padding:8px;text-align:right;background:#f9fafb;font-weight:600;\">합&nbsp;&nbsp;&nbsp;&nbsp;계</td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;background:#f9fafb;font-weight:600;\">원</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 8px 0;font-size:13px;font-weight:600;\">■ 입금 계좌</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">은&nbsp;&nbsp;&nbsp;&nbsp;행</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">계좌번호</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">예 금 주</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">※ 증빙서류(영수증 등)를 반드시 첨부해 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-05-13 10:01:15');
/*!40000 ALTER TABLE `approval_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_history`
--

DROP TABLE IF EXISTS `approval_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `document_id` int NOT NULL,
  `approver_id` int DEFAULT NULL COMMENT '결재자 employees.id',
  `approver_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '결재자 이름 (스냅샷)',
  `approver_dept` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '결재자 부서 (스냅샷)',
  `role` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '결재' COMMENT '역할 (결재/전결/참조)',
  `step_order` int NOT NULL DEFAULT '0' COMMENT '결재 순서',
  `action` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '대기' COMMENT '처리 (대기/승인/반려/건너뜀/협의)',
  `comment` text COLLATE utf8mb4_general_ci COMMENT '의견',
  `action_date` datetime DEFAULT NULL COMMENT '처리일',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_approver_id` (`approver_id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `approval_history_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `approval_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_history`
--

LOCK TABLES `approval_history` WRITE;
/*!40000 ALTER TABLE `approval_history` DISABLE KEYS */;
INSERT INTO `approval_history` VALUES (1,1,NULL,'이정민','Zaemit 개발','결재',1,'승인','확인했습니다.','2026-02-12 14:00:00','2026-05-13 10:00:48'),(2,1,NULL,'최민호','경영진','결재',2,'대기',NULL,NULL,'2026-05-13 10:00:48'),(3,2,NULL,'이정민','Zaemit 개발','결재',1,'승인',NULL,'2025-12-12 16:00:00','2026-05-13 10:00:48'),(4,2,NULL,'최민호','경영진','결재',2,'승인',NULL,'2025-12-13 09:30:00','2026-05-13 10:00:48'),(5,11,NULL,'이정민','Zaemit 개발','결재',1,'대기',NULL,NULL,'2026-05-13 10:00:48'),(6,13,NULL,'최민호','경영진','결재',1,'반려','금액 재확인 필요','2026-02-21 10:00:00','2026-05-13 10:00:48');
/*!40000 ALTER TABLE `approval_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_lines`
--

DROP TABLE IF EXISTS `approval_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_lines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '결재선 이름',
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '소속/부서',
  `doc_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '문서종류',
  `line_data` json DEFAULT NULL COMMENT '결재선 정보 (결재자 목록 JSON)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_lines`
--

LOCK TABLES `approval_lines` WRITE;
/*!40000 ALTER TABLE `approval_lines` DISABLE KEYS */;
INSERT INTO `approval_lines` VALUES (1,'개발팀 기본결재선','Zaemit 개발','품의서','[{\"name\": \"이정민\", \"role\": \"팀장\", \"action\": \"승인\"}, {\"name\": \"최민호\", \"role\": \"대표이사\", \"action\": \"승인\"}]','2026-05-13 10:00:48','2026-05-13 10:00:48'),(2,'경영지원 결재선','위즈웨어 경영지원','품의서','[{\"name\": \"박지현\", \"role\": \"실장\", \"action\": \"승인\"}, {\"name\": \"최민호\", \"role\": \"대표이사\", \"action\": \"승인\"}]','2026-05-13 10:00:48','2026-05-13 10:00:48');
/*!40000 ALTER TABLE `approval_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_references`
--

DROP TABLE IF EXISTS `approval_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_references` (
  `id` int NOT NULL AUTO_INCREMENT,
  `document_id` int NOT NULL,
  `ref_id` int DEFAULT NULL COMMENT '참조자 employees.id',
  `ref_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '참조자 이름 (스냅샷)',
  `ref_dept` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '참조자 부서 (스냅샷)',
  `read_at` datetime DEFAULT NULL COMMENT '열람일',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ref_id` (`ref_id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `approval_references_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `approval_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_references`
--

LOCK TABLES `approval_references` WRITE;
/*!40000 ALTER TABLE `approval_references` DISABLE KEYS */;
INSERT INTO `approval_references` VALUES (1,1,NULL,'박지현','위즈웨어 경영지원',NULL,'2026-05-13 10:00:48'),(2,1,NULL,'김영수','Zaemit 개발','2026-02-12 15:00:00','2026-05-13 10:00:48'),(3,2,NULL,'박지현','위즈웨어 경영지원','2025-12-13 10:00:00','2026-05-13 10:00:48');
/*!40000 ALTER TABLE `approval_references` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_records`
--

DROP TABLE IF EXISTS `attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'employees.id',
  `record_date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `work_plan` text COMMENT '출근 시 작성한 오늘의 업무 계획',
  `clock_out` time DEFAULT NULL,
  `leave_note` text COMMENT '퇴근 시 작성한 특이사항 메모',
  `work_type` varchar(20) DEFAULT NULL COMMENT '외근/출장/재택 등',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_date` (`employee_id`,`record_date`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_record_date` (`record_date`),
  CONSTRAINT `fk_att_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_records`
--

LOCK TABLES `attendance_records` WRITE;
/*!40000 ALTER TABLE `attendance_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int unsigned NOT NULL DEFAULT '1',
  `bank_name` varchar(50) NOT NULL COMMENT '은행명',
  `account_no` varchar(30) NOT NULL COMMENT '계좌번호',
  `account_alias` varchar(50) DEFAULT NULL COMMENT '계좌 별칭',
  `owner_name` varchar(50) NOT NULL COMMENT '예금주',
  `consent_agreed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '자동수집 동의 여부',
  `consent_agreed_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='등록 계좌 정보';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
INSERT INTO `bank_accounts` VALUES (1,1,'국민은행','123-456-789012','운영계좌','주식회사 재밋',1,'2026-01-15 10:00:00',1,'2026-05-13 10:00:48'),(2,1,'신한은행','234-567-890123','급여계좌','주식회사 재밋',1,'2026-01-15 10:00:00',1,'2026-05-13 10:00:48'),(3,1,'기업은행','345-678-901234','세금납부계좌','주식회사 재밋',0,NULL,1,'2026-05-13 10:00:48');
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_transactions`
--

DROP TABLE IF EXISTS `bank_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL COMMENT 'bank_accounts.id',
  `transaction_date` date NOT NULL,
  `description` varchar(200) NOT NULL COMMENT '거래 적요',
  `amount` bigint NOT NULL COMMENT '금액 (양수)',
  `tx_type` enum('입금','출금') NOT NULL,
  `balance` bigint DEFAULT NULL COMMENT '거래 후 잔액',
  `account_code` varchar(10) DEFAULT NULL COMMENT '분류된 계정과목 코드',
  `account_name` varchar(100) DEFAULT NULL COMMENT '분류된 계정과목명',
  `ai_confidence` tinyint unsigned DEFAULT NULL COMMENT 'AI 신뢰도 0-100',
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '사용자 확정 여부',
  `memo` varchar(200) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `bank_transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='통장 입출금 내역';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_transactions`
--

LOCK TABLES `bank_transactions` WRITE;
/*!40000 ALTER TABLE `bank_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `board_posts`
--

DROP TABLE IF EXISTS `board_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `board_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'notice/free/archive/department',
  `category` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT '카테고리 (공지/안내/중요/일반 등)',
  `title` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `author_id` int DEFAULT NULL COMMENT 'employees.id',
  `author_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `author_dept` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT '0',
  `views` int DEFAULT '0',
  `status` enum('active','deleted') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`board_type`,`status`),
  KEY `idx_type_pinned` (`board_type`,`is_pinned`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `board_posts`
--

LOCK TABLES `board_posts` WRITE;
/*!40000 ALTER TABLE `board_posts` DISABLE KEYS */;
INSERT INTO `board_posts` VALUES (1,'notice','중요','2026년 상반기 인사발령 안내','2026년 상반기 인사발령 사항을 안내드립니다.\n\n1. 발령일자: 2026년 4월 1일\n2. 대상: 전 부서\n3. 상세 내용은 첨부 파일을 참조해 주세요.\n\n문의사항은 경영지원본부로 연락 바랍니다.',1,'관리자','경영지원본부',1,234,'active','2026-04-01 09:00:00','2026-05-13 10:01:14'),(2,'notice','공지','사내 보안 정책 변경 안내','사내 보안 정책이 아래와 같이 변경됩니다.\n\n1. 비밀번호 변경 주기: 60일 → 90일\n2. 2단계 인증 필수 적용\n3. 외부 USB 사용 제한\n\n적용일: 2026년 4월 15일부터',1,'관리자','경영지원본부',1,189,'active','2026-03-28 10:30:00','2026-05-13 10:01:14'),(3,'notice','공지','2026년 연간 휴무일 안내','2026년 연간 휴무일을 안내드립니다.\n\n- 설날: 2월 16일~18일\n- 어린이날: 5월 5일\n- 추석: 9월 24일~26일\n- 크리스마스: 12월 25일\n\n자세한 내용은 인사팀에 문의해 주세요.',1,'관리자','경영지원본부',0,157,'active','2026-03-20 14:00:00','2026-05-13 10:25:48'),(4,'notice','안내','사무용품 신청 절차 변경','사무용품 신청 절차가 변경되었습니다.\n\n기존: 이메일 신청 → 총무팀 처리\n변경: 그룹웨어 전자결재 → 자동 발주\n\n적용일: 2026년 4월 1일부터',1,'관리자','경영지원본부',0,99,'active','2026-03-15 11:00:00','2026-05-13 10:25:46'),(5,'notice','중요','전사 회의 일정 공지 (4월)','4월 전사 회의 일정을 공지합니다.\n\n- 4월 7일(월) 10:00 월간 경영회의\n- 4월 14일(월) 14:00 부서장 회의\n- 4월 21일(월) 10:00 전체 조회\n\n참석 대상자는 일정을 확인해 주세요.',1,'관리자','경영지원본부',0,142,'active','2026-03-10 09:30:00','2026-05-13 10:01:14'),(6,'notice','공지','신규 입사자 OJT 일정','4월 신규 입사자 OJT 일정을 안내합니다.\n\n- 기간: 4월 6일 ~ 4월 10일\n- 장소: 본사 3층 교육장\n- 대상: 4월 입사자 전원\n\n부서별 멘토 지정은 별도 안내 예정입니다.',1,'관리자','경영지원본부',0,87,'active','2026-03-05 13:00:00','2026-05-13 10:01:14'),(7,'notice','안내','사무실 이전 안내','본사 사무실 이전을 안내드립니다.\n\n- 이전일: 2026년 5월 중 (확정 시 별도 공지)\n- 신규 주소: 서울시 강남구 테헤란로 123\n\n이전 관련 문의는 총무팀으로 연락 바랍니다.',1,'관리자','경영지원본부',0,203,'active','2026-02-28 16:00:00','2026-05-13 10:01:14'),(8,'free','일반','점심 맛집 추천 받습니다','회사 근처 점심 맛집 추천 부탁드립니다!\n\n- 예산: 1만원 내외\n- 선호: 한식, 일식\n- 비선호: 매운 음식\n\n좋은 곳 있으면 댓글로 알려주세요~',2,'김영수','기술개발본부',0,67,'active','2026-04-02 12:30:00','2026-05-13 10:01:14'),(9,'free','건의','회의실 예약 시스템 개선 건의','현재 회의실 예약 시스템 관련 건의사항입니다.\n\n1. 반복 예약 기능 추가 요청\n2. 예약 취소 시 알림 기능\n3. 회의실 현황 대시보드\n\n검토 부탁드립니다.',3,'이지은','영업본부',0,45,'active','2026-03-30 15:00:00','2026-05-13 10:01:14'),(10,'free','일반','사내 동호회 모집 (축구/독서)','사내 동호회 회원을 모집합니다!\n\n축구 동호회:\n- 활동: 매주 토요일 오전\n- 장소: 근처 풋살장\n\n독서 동호회:\n- 활동: 격주 수요일 저녁\n- 장소: 사내 카페\n\n관심 있으신 분은 댓글 남겨주세요.',4,'박민수','기술개발본부',0,89,'active','2026-03-25 17:00:00','2026-05-13 10:01:14'),(11,'free','기타','분실물 안내 (검은색 우산)','3층 회의실에서 검은색 접이식 우산을 발견했습니다.\n\n- 발견 장소: 3층 319호 회의실\n- 발견 일시: 3월 19일 오후\n- 브랜드: 무인양품\n\n주인이시면 총무팀으로 연락주세요.',2,'김영수','기술개발본부',0,32,'active','2026-03-20 09:00:00','2026-05-13 10:01:14'),(12,'free','일반','카페테리아 메뉴 변경 안내','4월부터 카페테리아 메뉴가 변경됩니다.\n\n- 신메뉴: 샐러드바, 수제버거\n- 폐지: 라면 코너\n- 운영시간: 11:30 ~ 13:30 (동일)\n\n의견 있으시면 댓글로 남겨주세요.',1,'관리자','경영지원본부',0,54,'active','2026-03-15 10:00:00','2026-05-13 10:01:14'),(13,'free','건의','야근 시 석식 지원 요청','야근 시 석식 지원에 대한 건의입니다.\n\n현재: 야근 수당만 지급\n건의: 20시 이후 야근 시 석식비 1만원 지원\n\n타 회사 사례도 참고하면 좋겠습니다.',3,'이지은','영업본부',0,41,'active','2026-03-08 18:30:00','2026-05-13 10:01:14'),(14,'free','일반','주말 등산 모임 후기','지난 주말 관악산 등산 모임 후기입니다.\n\n- 참여: 8명\n- 코스: 관악산역 → 정상 → 사당역\n- 소요시간: 약 3시간\n\n다음 모임은 4월 중으로 계획 중입니다!',4,'박민수','기술개발본부',0,38,'active','2026-03-01 20:00:00','2026-05-13 10:01:14'),(15,'archive','양식','출장보고서 양식 (2026년 개정)','2026년 개정된 출장보고서 양식을 공유합니다.\n\n변경사항:\n- 출장 목적 세분화\n- 성과 기술란 추가\n- 경비 항목 상세화\n\n기존 양식은 4월부터 사용 불가합니다.',1,'관리자','경영지원본부',0,78,'active','2026-03-28 11:00:00','2026-05-13 10:01:14'),(16,'archive','매뉴얼','그룹웨어 사용 매뉴얼 v2.0','그룹웨어 사용 매뉴얼 v2.0을 배포합니다.\n\n주요 업데이트:\n- 전자결재 사용법\n- 일정관리 사용법\n- 게시판 사용법\n\n문의사항은 IT팀으로 연락 바랍니다.',2,'김영수','기술개발본부',0,112,'active','2026-03-20 09:00:00','2026-05-13 10:01:14'),(17,'archive','참고자료','2025년 4분기 실적 보고서','2025년 4분기 실적 보고서를 공유합니다.\n\n주요 지표:\n- 매출: 전분기 대비 12% 증가\n- 영업이익: 전년 동기 대비 8% 증가\n- 신규 고객: 23건\n\n상세 내용은 첨부 자료를 참조해 주세요.',1,'관리자','경영지원본부',0,95,'active','2026-03-10 14:00:00','2026-05-13 10:01:14'),(18,'archive','양식','휴가 신청서 양식','휴가 신청서 양식을 공유합니다.\n\n- 연차, 반차, 특별휴가 구분\n- 인수인계 사항 기재란 포함\n- 부서장 결재란 포함\n\n전자결재로도 신청 가능합니다.',1,'관리자','경영지원본부',0,134,'active','2026-02-25 10:00:00','2026-05-13 10:01:14'),(19,'archive','매뉴얼','화상회의 시스템 사용 가이드','화상회의 시스템 사용 가이드입니다.\n\n1. 접속 방법\n2. 화면 공유\n3. 녹화 기능\n4. 트러블슈팅\n\n자세한 내용은 문서를 참조해 주세요.',2,'김영수','기술개발본부',0,65,'active','2026-02-15 11:00:00','2026-05-13 10:01:14'),(20,'archive','참고자료','사내 교육 프로그램 안내','2026년 사내 교육 프로그램을 안내합니다.\n\n- 리더십 과정 (4~6월)\n- 직무역량 과정 (7~9월)\n- IT 역량 과정 (10~12월)\n\n신청은 인사팀에서 별도 안내 예정입니다.',1,'관리자','경영지원본부',0,48,'active','2026-02-01 09:00:00','2026-05-13 10:01:14'),(21,'department','개발','코드 리뷰 가이드라인 공유','코드 리뷰 가이드라인을 공유합니다.\n\n1. PR 크기: 300줄 이하 권장\n2. 리뷰 응답: 24시간 이내\n3. 필수 리뷰어: 2명 이상\n4. 테스트 커버리지: 80% 이상\n\n질문이나 의견은 댓글로 남겨주세요.',3,'이지은','기술개발본부',0,34,'active','2026-04-01 16:00:00','2026-05-13 10:01:14'),(22,'department','기획','4월 마케팅 캠페인 기획안','4월 마케팅 캠페인 기획안을 공유합니다.\n\n캠페인명: 봄맞이 신규 고객 프로모션\n기간: 4월 14일 ~ 4월 30일\n목표: 신규 가입 500건\n\n피드백 부탁드립니다.',4,'박민수','영업본부',0,28,'active','2026-03-28 14:00:00','2026-05-13 10:01:14'),(23,'department','디자인','UI 디자인 시스템 컴포넌트 정리','UI 디자인 시스템 컴포넌트를 정리했습니다.\n\n- 버튼 (Primary/Secondary/Ghost)\n- 입력 필드 (Text/Select/Checkbox)\n- 카드 (Basic/Image/Stat)\n- 모달 (Alert/Confirm/Form)\n\nFigma 링크는 첨부를 참조해 주세요.',2,'김영수','기술개발본부',0,41,'active','2026-03-22 10:00:00','2026-05-13 10:01:14'),(24,'department','개발','API 문서 업데이트 (v3.1)','API 문서가 v3.1로 업데이트되었습니다.\n\n변경사항:\n- 인증 방식 변경 (Bearer Token)\n- 응답 포맷 통일\n- 에러 코드 표준화\n\n개발팀 전원 확인 부탁드립니다.',2,'김영수','기술개발본부',0,56,'active','2026-03-15 15:00:00','2026-05-13 10:01:14'),(25,'department','기획','고객 만족도 조사 결과','2026년 1분기 고객 만족도 조사 결과입니다.\n\n- 전체 만족도: 4.2/5.0\n- 서비스 품질: 4.5/5.0\n- 응대 속도: 3.8/5.0\n- 개선 요청: UI 개선, 모바일 지원\n\n상세 분석은 다음 주 보고 예정.',3,'이지은','영업본부',0,33,'active','2026-03-05 11:00:00','2026-05-13 10:01:14'),(26,'department','디자인','브랜드 가이드라인 업데이트','브랜드 가이드라인이 업데이트되었습니다.\n\n변경사항:\n- 로고 사용 규정 변경\n- 서브 컬러 추가\n- 폰트 가이드 개정\n\n모든 디자인 작업 시 새 가이드라인을 적용해 주세요.',4,'박민수','기술개발본부',0,29,'active','2026-02-20 13:00:00','2026-05-13 10:01:14');
/*!40000 ALTER TABLE `board_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_approvals`
--

DROP TABLE IF EXISTS `card_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_approvals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expense_id` int DEFAULT NULL COMMENT '관련 지출내역 ID',
  `card_id` int NOT NULL,
  `approval_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '승인번호',
  `merchant_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '가맹점명',
  `approval_amount` int NOT NULL DEFAULT '0' COMMENT '승인금액',
  `approval_date` datetime NOT NULL COMMENT '승인일시',
  `approval_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '승인' COMMENT '승인상태 (승인/취소)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `card_id` (`card_id`),
  KEY `expense_id` (`expense_id`),
  CONSTRAINT `card_approvals_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `card_approvals_ibfk_2` FOREIGN KEY (`expense_id`) REFERENCES `card_expenses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_approvals`
--

LOCK TABLES `card_approvals` WRITE;
/*!40000 ALTER TABLE `card_approvals` DISABLE KEYS */;
INSERT INTO `card_approvals` VALUES (1,1,1,'AP-2026-001','한우마을 강남점',45000,'2026-02-20 12:30:00','승인','2026-05-13 10:00:48'),(2,2,1,'AP-2026-002','카카오택시',32000,'2026-02-21 09:15:00','승인','2026-05-13 10:00:48'),(3,3,2,'AP-2026-003','쿠팡',128000,'2026-02-19 14:20:00','승인','2026-05-13 10:00:48'),(4,4,3,'AP-2026-004','스시오마카세',85000,'2026-02-18 18:45:00','승인','2026-05-13 10:00:48'),(5,5,1,'AP-2026-005','카카오택시',15000,'2026-02-22 10:00:00','승인','2026-05-13 10:00:48'),(6,6,2,'AP-2026-006','고깃집 서초점',62000,'2026-02-17 19:30:00','승인','2026-05-13 10:00:48'),(7,7,4,'AP-2026-007','르씨엘 레스토랑',250000,'2026-02-15 12:00:00','승인','2026-05-13 10:00:48'),(8,8,3,'AP-2026-008','오피스디포',35000,'2026-02-16 11:10:00','승인','2026-05-13 10:00:48'),(9,9,1,'AP-2026-009','더플레이스 역삼',55000,'2026-02-23 12:15:00','승인','2026-05-13 10:00:48'),(10,10,2,'AP-2026-010','KTX',28000,'2026-02-24 08:00:00','승인','2026-05-13 10:00:48'),(11,NULL,1,'AP-2026-011','GS25 역삼점',8500,'2026-02-25 15:30:00','승인','2026-05-13 10:00:48'),(12,NULL,3,'AP-2026-012','네이버페이',42000,'2026-02-25 16:00:00','취소','2026-05-13 10:00:48');
/*!40000 ALTER TABLE `card_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_expenses`
--

DROP TABLE IF EXISTS `card_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `card_id` int NOT NULL,
  `registrant_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '등록자',
  `approval_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '승인번호',
  `usage_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '법인' COMMENT '사용구분 (법인/개인)',
  `category` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '항목 (식대,교통비,접대비 등)',
  `sub_category` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '세부항목',
  `amount` int NOT NULL DEFAULT '0' COMMENT '사용금액',
  `description` text COLLATE utf8mb4_general_ci COMMENT '적요',
  `business_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '사업명',
  `business_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '사업코드',
  `document_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '문서번호',
  `user_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '사용자',
  `usage_date` date NOT NULL COMMENT '사용일',
  `is_settled` tinyint(1) DEFAULT '0' COMMENT '정산여부',
  `compliance_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '미확인' COMMENT '규정준수여부 (준수/미준수/미확인)',
  `settlement_updater` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '정산 업데이트 작성자',
  `settlement_date` datetime DEFAULT NULL COMMENT '최종 업데이트일',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `card_id` (`card_id`),
  CONSTRAINT `card_expenses_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_expenses`
--

LOCK TABLES `card_expenses` WRITE;
/*!40000 ALTER TABLE `card_expenses` DISABLE KEYS */;
INSERT INTO `card_expenses` VALUES (1,1,'김영수','AP-2026-001','법인','식대','식사',45000,'거래처 미팅 식사','A프로젝트',NULL,'DOC-2026-0101','김영수','2026-02-20',0,'미확인',NULL,NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(2,1,'김영수','AP-2026-002','법인','교통비','여비교통비',32000,'거래처 방문 택시비','A프로젝트',NULL,'DOC-2026-0102','김영수','2026-02-21',0,'미확인',NULL,NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(3,2,'이정민','AP-2026-003','법인','소모품','구입비',128000,'개발용 장비 구입','',NULL,'','이정민','2026-02-19',1,'준수',NULL,NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(4,3,'박지현','AP-2026-004','법인','접대비','영업사업비',85000,'고객사 접대','B프로젝트',NULL,'DOC-2026-0201','박지현','2026-02-18',1,'준수',NULL,NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(5,1,'홍길동','AP-2026-005','개인','교통비','여비교통비',15000,'외근 교통비','',NULL,'','홍길동','2026-02-22',0,'미확인',NULL,NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(6,2,'이정민','AP-2026-006','법인','식대','식사',62000,'팀 회식','',NULL,'','이정민','2026-02-17',0,'미준수',NULL,NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(7,4,'최민호','AP-2026-007','법인','접대비','영업사업비',250000,'VIP 고객 미팅','C프로젝트',NULL,'DOC-2026-0301','최민호','2026-02-15',1,'준수',NULL,NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(8,3,'박지현','AP-2026-008','법인','기타','구입비',35000,'사무용품 구입','',NULL,'','박지현','2026-02-16',0,'미확인',NULL,NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(9,1,'김영수','AP-2026-009','법인','식대','식사',55000,'프로젝트 킥오프 식사','D프로젝트',NULL,'DOC-2026-0401','김영수','2026-02-23',0,'미확인',NULL,NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(10,2,'서지우','AP-2026-010','개인','교통비','여비교통비',28000,'출장 교통비','A프로젝트',NULL,'','서지우','2026-02-24',0,'미확인',NULL,NULL,'2026-05-13 10:00:48','2026-05-13 10:00:48');
/*!40000 ALTER TABLE `card_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_regulation_categories`
--

DROP TABLE IF EXISTS `card_regulation_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_regulation_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'gray' COMMENT 'Tailwind 색상키',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_regulation_categories`
--

LOCK TABLES `card_regulation_categories` WRITE;
/*!40000 ALTER TABLE `card_regulation_categories` DISABLE KEYS */;
INSERT INTO `card_regulation_categories` VALUES (1,'식사','green',1,1,'2026-05-13 10:00:48'),(2,'여비교통비','blue',2,1,'2026-05-13 10:00:48'),(3,'영업사업비','purple',3,1,'2026-05-13 10:00:48'),(4,'구입비','orange',4,1,'2026-05-13 10:00:48');
/*!40000 ALTER TABLE `card_regulation_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_regulations`
--

DROP TABLE IF EXISTS `card_regulations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_regulations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '항목',
  `sub_category` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '세부항목',
  `limit_amount` int DEFAULT '0' COMMENT '한도 (원)',
  `required_fields` text COLLATE utf8mb4_general_ci COMMENT '입력시 필수사항',
  `guide` text COLLATE utf8mb4_general_ci COMMENT '세부항목 가이드',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_regulations`
--

LOCK TABLES `card_regulations` WRITE;
/*!40000 ALTER TABLE `card_regulations` DISABLE KEYS */;
INSERT INTO `card_regulations` VALUES (1,'식사/회식','중식/석식 (업무)',15000,'영수증, 참석자 명단','업무상 필요한 식사 비용. 1인당 15,000원 이내. 참석자 3인 이상일 때 명단 필수 기재. 주류 포함 시 회식 규정 적용.',10,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(2,'식사/회식','야근식대',12000,'영수증, 야근 보고서','정규 근무시간(18시) 이후 2시간 이상 근무 시 지급. 1인당 12,000원 이내. 야근대장 기재 필수.',11,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(3,'식사/회식','조식 (출장 시)',10000,'영수증, 출장명령서','숙박 출장 시 아침 식사. 1인당 10,000원 이내. 숙박업소 조식 포함 시 별도 지급 불가.',12,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(4,'식사/회식','팀 회식',50000,'영수증, 참석자 명단, 회식 사유, 팀장 사전승인','팀 단위 회식 1인당 50,000원 이내. 월 1회 한도. 주류 구매 시 반드시 별도 명세 기재. 팀장 사전 승인 필수.',13,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(5,'식사/회식','부서 회식',70000,'영수증, 참석자 명단, 회식 사유, 부서장 사전승인','부서 단위 회식 1인당 70,000원 이내. 분기 1회 한도. 부서장 사전 승인 및 총무팀 통보 필수.',14,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(6,'식사/회식','간식/음료',5000,'영수증','업무 중 간식/음료 구매. 1인당 5,000원 이내. 팀 단위 구매 시 인원수 기재.',15,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(7,'여비교통비','시내 대중교통',0,'영수증 (T-money 내역 가능)','업무 목적 대중교통(버스/지하철) 실비 정산. 영수증 또는 교통카드 이용내역으로 갈음.',20,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(8,'여비교통비','시내 택시',30000,'영수증, 출발지/도착지, 사유','1회 30,000원 이내. 대중교통 운행 시간(05:00~23:00) 내에는 사유 필수 기재. 초과 시 팀장 승인.',21,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(9,'여비교통비','심야/긴급 택시',50000,'영수증, 사유서','23:00 이후 또는 긴급 업무 택시. 1회 50,000원 이내. 사유서 필수. 월 4회 초과 시 재무팀 검토.',22,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(10,'여비교통비','출장 교통비 (국내)',0,'출장명령서, 영수증, KTX/항공권','실비 정산. KTX 일반실/고속버스 우선 이용 원칙. 항공기는 편도 3시간 이상 구간만 허용(부서장 사전승인).',23,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(11,'여비교통비','출장 교통비 (해외)',0,'해외출장 승인서, 영수증, 항공권','실비 정산. 항공권은 이코노미 클래스 원칙. 임원 이상 비즈니스 허용. 예약은 총무팀 법인 계정 경유.',24,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(12,'여비교통비','법인차량 주유',0,'영수증, 차량번호, 주행거리','법인차량 주유비 실비. 차량번호 및 주행거리 기재 필수. 개인차량 주유는 불가.',25,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(13,'여비교통비','주차비/통행료',0,'영수증','업무 목적 주차료 및 고속도로 통행료 실비 정산.',26,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(14,'여비교통비','렌트카',0,'대여증, 출장보고서, 부서장 승인','출장 시 렌트카 이용. 중형 이하 차량 원칙. 부서장 사전 승인 필수. 보험(자차/대인) 필수 가입.',27,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(15,'접대/영업','거래처 식사 접대',100000,'영수증, 접대 보고서, 거래처명/담당자','건당 1인 100,000원 이내. 접대 보고서 필수(거래처명, 참석자, 접대 목적). 국세청 접대비 한도 관리 대상.',30,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(16,'접대/영업','거래처 선물',100000,'영수증, 수령처, 품목, 사유','건당 100,000원 이내(명절 선물 포함). 청탁금지법 기준 3만원/5만원 규정 준수. 공직자 대상 별도 검토.',31,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(17,'접대/영업','판촉물/홍보물',50000,'영수증, 사용 계획서','자사 홍보용 판촉물 구매. 1회 50,000원 이내. 마케팅팀 사전 협의. 다량 구매 시 발주 절차 경유.',32,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(18,'접대/영업','전시회/박람회 참가',0,'참가 신청서, 사전승인, 참가 보고서','전시회/박람회 참가비 및 부스료 실비. 사전 승인서 및 참가 후 7일 이내 보고서 제출.',33,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(19,'경조사비','화환/조화',100000,'경조사 증빙, 수령인','건당 100,000원 이내. 거래처/직원 경조사. 경조사 증빙(부고, 청첩장 등) 첨부.',40,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(20,'경조사비','축의금/조의금',100000,'경조사 증빙, 사내 경조사 규정 참조','법인카드 사용 불가 원칙. 총무팀 경조사 규정에 따라 회사에서 별도 집행 또는 현금 지급. 개인이 법인카드로 사용 시 반드시 환수.',41,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(21,'구입비','사무용품',50000,'영수증, 품목 내역','건당 50,000원 이내. 필기구, 용지, 파일 등 일반 소모품. 반복 구매 시 총무팀 일괄 구매 권장.',50,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(22,'구입비','사무가구',500000,'영수증, 구매요청서, 자산등록, 총무팀 경유','건당 500,000원 이내. 책상/의자/캐비넷 등. 총무팀 경유 의무. 50만원 초과 시 자산등록.',51,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(23,'구입비','IT 장비 (노트북/모니터 등)',0,'영수증, IT부서 사전승인, 자산등록','IT부서 사전 승인 필수. 견적 3개 이상 비교. 반드시 자산등록. 지정 모델 외 구매 금지.',52,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(24,'구입비','소프트웨어 라이선스',0,'영수증, 라이선스 정보, IT부서 사전승인','IT부서 사전 승인 필수. 라이선스 증명서 수령. 구독형 결제 시 자동갱신 여부 확인.',53,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(25,'구입비','도서/자료',100000,'영수증, 업무 관련성 설명','업무 관련 도서/간행물 구매. 1인당 월 100,000원 이내. 팀장 확인. 개인 학습서는 교육훈련비로 청구.',54,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(26,'구입비','사무실 생수/다과',0,'영수증','사내 공용 생수/다과 구매. 총무팀 월 예산 내. 개별 팀 과자 구매는 불가(식대 규정 적용).',55,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(27,'통신비','법인 휴대폰 요금',0,'청구서','법인 명의 휴대폰 요금. 총무팀 일괄 납부 원칙. 개인 명의는 불가.',60,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(28,'통신비','인터넷/통신회선',0,'청구서','사무실 인터넷/전화 회선 요금. 총무팀 일괄 납부.',61,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(29,'통신비','택배/퀵서비스',0,'영수증, 발송지/수취인, 사유','업무용 택배/퀵 실비. 영수증 + 내용(문서/샘플 등) 기재. 개인 발송 엄격 금지.',62,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(30,'교육훈련비','외부 교육 (단기)',500000,'수강신청서, 수료증, 팀장 승인','1일~4주 과정. 건당 500,000원 이내. 팀장 승인. 수료증/수료보고서 제출.',70,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(31,'교육훈련비','외부 교육 (장기 3개월+)',0,'사전승인, 근속 의무 서약서','3개월 이상 장기 교육. 부서장+경영진 승인. 수료 후 2년 이상 근속 의무 서약. 중도 퇴사 시 비용 환수.',71,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(32,'교육훈련비','자격증 응시료',0,'응시증빙, 합격증 (사후)','업무 관련 자격증 응시료 지원. 합격 시 전액 지원, 불합격 시 50% 지원. 1년 2회 제한.',72,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(33,'교육훈련비','컨퍼런스/세미나',500000,'참가 신청서, 사전승인, 참가 보고서','업무 관련 컨퍼런스/세미나 참가비. 건당 500,000원 이내. 참가 후 7일 이내 팀 공유 보고서.',73,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(34,'교육훈련비','사내 교육 운영비',0,'강사료 계약서, 영수증','사내 초빙 교육 강사료/자료비. HR팀 경유. 계약서 기반 집행.',74,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(35,'복리후생','직원 생일/기념일 선물',30000,'영수증, 대상자','직원 개인 생일/입사기념일. 1인당 30,000원 이내. HR팀 일괄 집행 원칙.',80,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(36,'복리후생','건강검진 본인부담분 보조',200000,'영수증, 검진 결과서','정기 건강검진 본인부담금 보조. 연 200,000원 이내. 1인 1회/년.',81,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(37,'복리후생','사내 동호회 지원',300000,'활동 계획서, 활동 보고서','사내 공식 등록 동호회. 분기당 300,000원 이내. 분기별 활동 보고서 제출.',82,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(38,'복리후생','직원 단체 선물 (명절)',0,'품목 내역, 총무팀 일괄 집행','명절 선물 일괄 집행. 총무팀 경유. 개별 법인카드 사용 불가.',83,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(39,'광고선전비','온라인 광고',0,'집행 내역, 마케팅팀 승인','디지털 광고 집행비(네이버/구글/SNS 등). 마케팅팀 승인. 월별 집행 내역 보고.',90,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(40,'광고선전비','오프라인 광고',0,'견적서, 계약서, 마케팅팀 승인','지면/옥외 광고. 견적서 3개 이상 비교. 10백만원 이상 시 임원 승인.',91,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(41,'광고선전비','브로셔/카탈로그 제작',0,'견적서, 샘플, 마케팅팀 승인','회사 홍보물 제작. 마케팅팀 검수 후 발주. 인쇄본은 총무팀 보관.',92,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(42,'수수료/외주비','은행 수수료',0,'이체 증빙','송금/이체 수수료 실비. 재무팀 자동 처리. 개인 법인카드 사용 금지.',100,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(43,'수수료/외주비','법무/세무/회계 자문료',0,'계약서, 세금계산서','외부 전문가 자문료. 계약서 기반 집행. 재무팀 검토 후 승인.',101,1,'2026-05-13 10:01:15','2026-05-13 10:01:15'),(44,'수수료/외주비','외주 용역비',0,'계약서, 과업완료 보고서, 세금계산서','외주 용역 대금. 계약서 및 과업완료 보고서 검수 후 집행. 500만원 이상 시 임원 승인.',102,1,'2026-05-13 10:01:15','2026-05-13 10:01:15');
/*!40000 ALTER TABLE `card_regulations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cards`
--

DROP TABLE IF EXISTS `cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `card_alias` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '카드별칭',
  `card_number` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '카드번호 (마스킹)',
  `memo` text COLLATE utf8mb4_general_ci COMMENT '비고',
  `manager_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '책임자',
  `affiliation` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '소속',
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '부서',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '사용여부',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cards`
--

LOCK TABLES `cards` WRITE;
/*!40000 ALTER TABLE `cards` DISABLE KEYS */;
INSERT INTO `cards` VALUES (1,'영업팀 법인카드','9410-****-****-1234','영업팀 업무용','김영수','위즈웨어','영업팀',1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(2,'개발팀 법인카드','9410-****-****-5678','개발팀 업무용','이정민','위즈웨어','개발팀',1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(3,'경영지원 법인카드','5412-****-****-9012','경영지원실 업무용','박지현','위즈웨어','경영지원실',1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(4,'대표이사 법인카드','4532-****-****-3456','대표이사 전용','최민호','위즈웨어','경영진',1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(5,'마케팅팀 법인카드','9410-****-****-7890','마케팅 업무용','정수진','위즈웨어','마케팅팀',0,'2026-05-13 10:00:48','2026-05-13 10:00:48');
/*!40000 ALTER TABLE `cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `common_code_groups`
--

DROP TABLE IF EXISTS `common_code_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `common_code_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT '모듈 (hr, attendance, card, business, reservation, schedule)',
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '공통정보명',
  `description` text COLLATE utf8mb4_general_ci COMMENT '비고',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `common_code_groups`
--

LOCK TABLES `common_code_groups` WRITE;
/*!40000 ALTER TABLE `common_code_groups` DISABLE KEYS */;
INSERT INTO `common_code_groups` VALUES (1,'hr','직급','직원의 직급 분류',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(2,'hr','직책','직원의 직책 분류',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(3,'hr','고용형태','직원의 고용 형태 분류',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(4,'hr','고용상태','직원의 재직/퇴직 상태 분류',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(5,'attendance','근무유형','출퇴근 근무 유형 분류',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(6,'attendance','휴가유형','연차/반차 등 휴가 유형',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(7,'card','비용항목','법인카드 사용 시 비용 분류 항목',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(8,'card','카드유형','법인카드 종류',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(9,'business','사업원가항목','사업 비용 책정 시 입력하는 사업원가항목 구분',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(10,'business','사업상태','사업 진행 상태 분류',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(11,'business','사업구분','사업 유형 분류',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(12,'reservation','자원목록','회사가 보유하고 운영하는 자원 정보 (회의실, 비품, 차량 등)',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(13,'schedule','일정유형','일정 분류 유형',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(14,'schedule','캘린더 색상','일정 캘린더 색상 구분',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48');
/*!40000 ALTER TABLE `common_code_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `common_code_items`
--

DROP TABLE IF EXISTS `common_code_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `common_code_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '코드',
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '항목명(레이블)',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `common_code_items_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `common_code_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `common_code_items`
--

LOCK TABLES `common_code_items` WRITE;
/*!40000 ALTER TABLE `common_code_items` DISABLE KEYS */;
INSERT INTO `common_code_items` VALUES (1,1,'CEO','대표이사',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(2,1,'DIR','이사',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(3,1,'GM','부장',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(4,1,'DGM','차장',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(5,1,'MGR','과장',5,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(6,1,'AM','대리',6,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(7,1,'SR','주임',7,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(8,1,'STF','사원',8,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(9,1,'INT','인턴',9,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(10,2,'CEO','CEO',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(11,2,'CTO','CTO',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(12,2,'CFO','CFO',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(13,2,'COO','COO',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(14,2,'HEAD','본부장',5,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(15,2,'TL','팀장',6,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(16,2,'PL','파트장',7,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(17,3,'FT','정규직',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(18,3,'CT','계약직',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(19,3,'PT','시간제',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(20,3,'DP','파견직',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(21,4,'ACT','재직',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(22,4,'LOA','휴직',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(23,4,'MAT','육아휴직',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(24,4,'RES','퇴사',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(25,5,'NRM','정상근무',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(26,5,'WFH','재택근무',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(27,5,'OUT','외근',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(28,5,'BIZ','출장',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(29,6,'AL','연차',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(30,6,'HAM','반차(오전)',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(31,6,'HAP','반차(오후)',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(32,6,'SL','병가',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(33,6,'FL','경조사',5,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(34,6,'OL','공가',6,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(35,7,'FOOD','식대',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(36,7,'TRANS','교통비',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(37,7,'ENT','접대비',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(38,7,'SUP','소모품',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(39,7,'ETC','기타',5,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(40,8,'CORP','법인카드',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(41,8,'PRIV','개인카드',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(42,9,'OS_C','외주비(기업)',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(43,9,'OS_P','외주비(개인)',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(44,9,'RES','자원구입비',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(45,9,'MKT','마케팅 수수료',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(46,9,'PRM','사업 판촉비',5,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(47,9,'EXP','진행 경비',6,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(48,9,'FREE','무상 서비스 원가',7,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(49,10,'SALES','영업',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(50,10,'CONT','계약',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(51,10,'PROG','진행중',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(52,10,'DONE','완료',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(53,10,'HOLD','보류',5,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(54,11,'SI','SI',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(55,11,'SM','SM',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(56,11,'CONS','컨설팅',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(57,11,'EDU','교육',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(58,12,'MR1','319호 - 회의실',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(59,12,'MR2','319호 - 탕비실(회의용)',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(60,12,'NB1','노트북 1 (내부)',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(61,12,'TAB','태블릿',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(62,13,'MTG','회의',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(63,13,'MEET','미팅',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(64,13,'TRIP','출장',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(65,13,'EDU','교육',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(66,13,'ETC','기타',5,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(67,14,'BLUE','파랑',1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(68,14,'RED','빨강',2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(69,14,'GREEN','초록',3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(70,14,'YELLOW','노랑',4,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(71,14,'PURPLE','보라',5,1,'2026-05-13 10:00:48','2026-05-13 10:00:48');
/*!40000 ALTER TABLE `common_code_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contract_templates`
--

DROP TABLE IF EXISTS `contract_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '표준 근로계약서' COMMENT '양식 이름 (예: 정규직, 계약직, 인턴)',
  `version_label` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '버전 레이블 (예: v1, 2026 개정)',
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '양식 설명',
  `body` longtext COLLATE utf8mb4_general_ci NOT NULL COMMENT '계약서 본문 (HTML, 표 포함)',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '기본 양식 여부',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '사용 여부',
  `updated_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '최근 수정자',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_default` (`is_default`,`is_active`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='근로계약서 양식 · 다중 버전/종류';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contract_templates`
--

LOCK TABLES `contract_templates` WRITE;
/*!40000 ALTER TABLE `contract_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `contract_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '부서코드',
  `head_employee_id` int DEFAULT NULL COMMENT '부서장 ID',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `head_employee_id` (`head_employee_id`),
  CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_ibfk_2` FOREIGN KEY (`head_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,NULL,'(주)재밋','ZAEMIT',1,0,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(2,1,'경영지원본부','MGT',2,1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(3,1,'기술개발본부','TECH',3,2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(4,1,'영업본부','SALES',4,3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(5,2,'경영지원팀','MGT-SUPPORT',5,1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(6,2,'인사팀','MGT-HR',6,2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(7,2,'재무회계팀','MGT-FIN',7,3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(8,3,'개발1팀','TECH-DEV1',8,1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(9,3,'개발2팀','TECH-DEV2',9,2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(10,3,'QA팀','TECH-QA',10,3,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(11,4,'국내영업팀','SALES-DOM',11,1,1,'2026-05-13 10:00:48','2026-05-13 10:00:48'),(12,4,'해외영업팀','SALES-INT',12,2,1,'2026-05-13 10:00:48','2026-05-13 10:00:48');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_requests`
--

DROP TABLE IF EXISTS `doc_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `requester_id` int unsigned NOT NULL DEFAULT '1' COMMENT '요청자(세무사) ID',
  `company_id` int unsigned NOT NULL DEFAULT '1',
  `doc_name` varchar(200) NOT NULL COMMENT '요청 서류명',
  `category` varchar(30) NOT NULL DEFAULT 'general' COMMENT 'business_docs 탭 키',
  `description` text COMMENT '요청 상세 설명',
  `due_date` date DEFAULT NULL COMMENT '제출 기한',
  `status` enum('요청중','업로드완료','확인완료','취소') NOT NULL DEFAULT '요청중',
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='세무 서류 요청';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_requests`
--

LOCK TABLES `doc_requests` WRITE;
/*!40000 ALTER TABLE `doc_requests` DISABLE KEYS */;
INSERT INTO `doc_requests` VALUES (1,1,1,'2025년 4분기 통장거래내역','general','국민은행 운영계좌 전체 내역 PDF','2026-03-10','요청중','2026-05-13 10:00:48',NULL),(2,1,1,'2025년 12월 급여대장','general','전직원 급여 지급 내역','2026-03-07','업로드완료','2026-05-13 10:00:48',NULL),(3,1,1,'사업자등록증 사본','general','최신 발급본','2026-03-15','확인완료','2026-05-13 10:00:48',NULL),(4,1,1,'2025년 부가세 신고용 매입세금계산서 목록','general','엑셀 또는 PDF 형식','2026-03-20','요청중','2026-05-13 10:00:48',NULL);
/*!40000 ALTER TABLE `doc_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_uploads`
--

DROP TABLE IF EXISTS `doc_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_uploads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int unsigned DEFAULT NULL COMMENT 'bytes',
  `uploaded_by` int unsigned NOT NULL DEFAULT '1',
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `doc_uploads_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `doc_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='서류 업로드 파일';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_uploads`
--

LOCK TABLES `doc_uploads` WRITE;
/*!40000 ALTER TABLE `doc_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_resumes`
--

DROP TABLE IF EXISTS `employee_resumes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_resumes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'employees.id',
  `file_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT '원본 파일명',
  `file_path` varchar(500) COLLATE utf8mb4_general_ci NOT NULL COMMENT '저장 상대경로 (uploads/resumes/...)',
  `file_size` int NOT NULL DEFAULT '0' COMMENT 'bytes',
  `mime_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL COMMENT 'employees.id (업로더)',
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee` (`employee_id`,`uploaded_at` DESC),
  CONSTRAINT `fk_er_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='직원 이력서 파일 (여러 버전 누적)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_resumes`
--

LOCK TABLES `employee_resumes` WRITE;
/*!40000 ALTER TABLE `employee_resumes` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_resumes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int DEFAULT NULL,
  `affiliation` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '소속 (WEVEN, Zaemit 등)',
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `position` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '직급 (대표이사, 이사, 부장, 과장, 대리, 사원 등)',
  `title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '직책 (CEO, CTO, 팀장 등)',
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '비밀번호 해시 (bcrypt)',
  `user_role` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user' COMMENT '역할 (admin/manager/user)',
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employment_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '정규직' COMMENT '고용형태 (정규직, 계약직, 시간제, 파견직)',
  `employment_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '재직' COMMENT '고용상태 (재직, 휴직, 육아휴직, 퇴사)',
  `is_dept_head` tinyint(1) DEFAULT '0' COMMENT '부서장 여부',
  `profile_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `idx_user_role` (`user_role`),
  KEY `idx_email_active` (`email`,`is_active`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_employees_user_role` CHECK ((`user_role` in (_utf8mb4'admin',_utf8mb4'manager',_utf8mb4'user')))
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,1,'Zaemit','김대표','대표이사','CEO','ceo@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','admin','010-1234-5678','정규직','재직',1,NULL,'2020-01-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(2,2,'Zaemit','이본부장','이사','경영지원본부장','lee@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','admin','010-2345-6789','정규직','재직',1,NULL,'2020-03-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(3,3,'Zaemit','박기술','이사','CTO','park@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','admin','010-3456-7890','정규직','재직',1,NULL,'2020-03-15',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(4,4,'Zaemit','최영업','이사','영업본부장','choi@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','admin','010-4567-8901','정규직','재직',1,NULL,'2020-06-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(5,5,'Zaemit','정지원','부장','경영지원팀장','jung@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','manager','010-5678-9012','정규직','재직',1,NULL,'2021-01-10',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(6,6,'Zaemit','한인사','부장','인사팀장','han@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','manager','010-6789-0123','정규직','재직',1,NULL,'2021-02-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(7,7,'Zaemit','오재무','부장','재무회계팀장','oh@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','manager','010-7890-1234','정규직','재직',1,NULL,'2021-03-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(8,8,'Zaemit','강개발','부장','개발1팀장','kang@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','manager','010-8901-2345','정규직','재직',1,NULL,'2021-04-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(9,9,'Zaemit','윤개발','과장','개발2팀장','yoon@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','manager','010-9012-3456','정규직','재직',1,NULL,'2021-05-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(10,10,'Zaemit','임품질','과장','QA팀장','lim@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','manager','010-0123-4567','정규직','재직',1,NULL,'2021-06-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(11,11,'Zaemit','서영업','과장','국내영업팀장','seo@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','manager','010-1111-2222','정규직','재직',1,NULL,'2021-07-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(12,12,'Zaemit','류해외','과장','해외영업팀장','ryu@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','manager','010-3333-4444','정규직','재직',1,NULL,'2021-08-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(13,5,'Zaemit','김경영','대리',NULL,'kimm@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','user','010-5555-6666','정규직','재직',0,NULL,'2022-01-15',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(14,6,'Zaemit','이인사','대리',NULL,'leehr@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','user','010-7777-8888','정규직','재직',0,NULL,'2022-03-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(15,8,'Zaemit','박프론트','대리',NULL,'parkfe@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','user','010-9999-0000','정규직','재직',0,NULL,'2022-04-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(16,8,'Zaemit','송백엔드','사원',NULL,'songbe@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','user','010-1212-3434','정규직','재직',0,NULL,'2023-01-02',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(17,9,'Zaemit','조풀스택','대리',NULL,'jofs@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','user','010-5656-7878','정규직','재직',0,NULL,'2022-06-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(18,9,'Zaemit','황모바일','사원',NULL,'hwangmb@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','user','010-9090-1212','정규직','재직',0,NULL,'2023-03-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(19,10,'Zaemit','문테스터','대리',NULL,'moonqa@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','user','010-3434-5656','정규직','재직',0,NULL,'2022-07-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39'),(20,11,'Zaemit','배영업','대리',NULL,'baes@zaemit.com','$2y$12$DMSjmiErdqS9cdMTo7FxuOi0nhTILJXMY0U/jOHQpCJbJAR/rVgwC','user','010-7878-9090','정규직','재직',0,NULL,'2022-09-01',1,'2026-05-13 10:00:48','2026-05-13 10:01:39');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hometax_sync_log`
--

DROP TABLE IF EXISTS `hometax_sync_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hometax_sync_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int unsigned NOT NULL DEFAULT '1',
  `sync_type` varchar(30) NOT NULL COMMENT 'sales_invoice, purchase_invoice 등',
  `sync_count` int unsigned NOT NULL DEFAULT '0' COMMENT '동기화 건수',
  `status` enum('성공','실패','진행중') NOT NULL DEFAULT '성공',
  `message` varchar(500) DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='홈택스 동기화 이력';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hometax_sync_log`
--

LOCK TABLES `hometax_sync_log` WRITE;
/*!40000 ALTER TABLE `hometax_sync_log` DISABLE KEYS */;
INSERT INTO `hometax_sync_log` VALUES (1,1,'sales_invoice',7,'성공','2026년 2월 매출 세금계산서 7건 동기화 완료','2026-02-28 09:00:00','2026-02-28 09:00:12'),(2,1,'purchase_invoice',6,'성공','2026년 2월 매입 세금계산서 6건 동기화 완료','2026-02-28 09:00:12','2026-02-28 09:00:25');
/*!40000 ALTER TABLE `hometax_sync_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `labor_contracts`
--

DROP TABLE IF EXISTS `labor_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `labor_contracts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'employees.id',
  `contract_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'draft' COMMENT 'draft/signed/expiring/none',
  `version` int DEFAULT '1' COMMENT '계약 갱신 시 버전 증가',
  `company_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `company_ceo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `company_address` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_bizno` varchar(14) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '사업자등록번호',
  `contract_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'permanent' COMMENT 'permanent/fixed/parttime',
  `contract_start` date DEFAULT NULL,
  `contract_end` date DEFAULT NULL COMMENT 'permanent이면 NULL',
  `job_description` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '종사업무',
  `workplace` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '근무장소',
  `work_start` time DEFAULT '09:00:00',
  `work_end` time DEFAULT '18:00:00',
  `break_start` time DEFAULT '12:00:00',
  `break_end` time DEFAULT '13:00:00',
  `work_days` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '근무요일',
  `weekly_holiday` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `annual_leave` varchar(200) COLLATE utf8mb4_general_ci DEFAULT '',
  `base_pay` int DEFAULT '0' COMMENT '기본급(월)',
  `meal_allowance` int DEFAULT '0' COMMENT '식대',
  `car_allowance` int DEFAULT '0' COMMENT '차량지원비',
  `child_allowance` int DEFAULT '0' COMMENT '육아수당',
  `extra_pay_1` int DEFAULT '0' COMMENT '추가수당1',
  `extra_pay_2` int DEFAULT '0' COMMENT '추가수당2',
  `extra_pay_3` int DEFAULT '0' COMMENT '추가수당3',
  `monthly_total` int DEFAULT '0' COMMENT '월 급여 합계',
  `annual_total` int DEFAULT '0' COMMENT '연봉',
  `pay_day` int DEFAULT '25' COMMENT '매월 지급일',
  `pay_method` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'transfer' COMMENT 'transfer/cash/other',
  `ins_pension` tinyint(1) DEFAULT '1' COMMENT '국민연금',
  `ins_health` tinyint(1) DEFAULT '1' COMMENT '건강보험',
  `ins_employment` tinyint(1) DEFAULT '1' COMMENT '고용보험',
  `ins_industrial` tinyint(1) DEFAULT '1' COMMENT '산재보험',
  `retirement_pay` tinyint(1) DEFAULT '1' COMMENT '1=적용, 0=미적용',
  `probation` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '3' COMMENT '0/1/3/6 (개월)',
  `additional_terms` text COLLATE utf8mb4_general_ci COMMENT '기타 근로조건',
  `signed_at` datetime DEFAULT NULL COMMENT '체결완료 시점',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_status` (`contract_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `labor_contracts`
--

LOCK TABLES `labor_contracts` WRITE;
/*!40000 ALTER TABLE `labor_contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `labor_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `labor_rules_document`
--

DROP TABLE IF EXISTS `labor_rules_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `labor_rules_document` (
  `id` int NOT NULL DEFAULT '1',
  `body` mediumtext NOT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `labor_rules_document`
--

LOCK TABLES `labor_rules_document` WRITE;
/*!40000 ALTER TABLE `labor_rules_document` DISABLE KEYS */;
/*!40000 ALTER TABLE `labor_rules_document` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'employees.id',
  `leave_type` varchar(10) NOT NULL DEFAULT 'AL' COMMENT 'AL/HAM/HAP/SL/FL/OL',
  `start_date` date NOT NULL COMMENT '시작일',
  `end_date` date NOT NULL COMMENT '종료일',
  `days_used` decimal(3,1) NOT NULL DEFAULT '1.0' COMMENT '사용일수 (0.5=반차)',
  `reason` varchar(200) DEFAULT NULL COMMENT '사유',
  `status` varchar(10) NOT NULL DEFAULT '대기' COMMENT '대기/승인/반려/취소',
  `approved_at` datetime DEFAULT NULL COMMENT '승인 일시',
  `approver_id` int DEFAULT NULL COMMENT '승인자 employees.id',
  `penalty_flag` tinyint(1) NOT NULL DEFAULT '0' COMMENT '페널티 여부 (예: 지각 반차)',
  `penalty_reason` varchar(100) DEFAULT NULL COMMENT '페널티 사유 문구',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '신청 일시',
  PRIMARY KEY (`id`),
  KEY `idx_emp_year` (`employee_id`,`start_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='연차 사용 기록';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_permissions`
--

DROP TABLE IF EXISTS `menu_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `menu_key` varchar(60) COLLATE utf8mb4_general_ci NOT NULL COMMENT '예: dashboard, accounting.settle, labor.rules',
  `role_key` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'admin | manager | user (NULL 이면 역할 조건 없음)',
  `department_id` int DEFAULT NULL COMMENT '특정 부서에만 한정 (NULL 이면 부서 조건 없음)',
  `access_level` enum('view','edit','admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'view',
  `note` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '운영 메모',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menu_role_dept` (`menu_key`,`role_key`,`department_id`),
  KEY `idx_menu` (`menu_key`),
  KEY `idx_role` (`role_key`),
  KEY `idx_dept` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_permissions`
--

LOCK TABLES `menu_permissions` WRITE;
/*!40000 ALTER TABLE `menu_permissions` DISABLE KEYS */;
INSERT INTO `menu_permissions` VALUES (1,'*','admin',NULL,'admin','관리자는 모든 메뉴에 접근','2026-05-13 10:01:15','2026-05-13 10:01:15'),(2,'dashboard','manager',NULL,'edit','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(3,'dashboard','user',NULL,'view','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(4,'attendance','manager',NULL,'edit','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(5,'attendance','user',NULL,'edit','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(6,'schedule','manager',NULL,'edit','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(7,'schedule','user',NULL,'edit','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(8,'approval','manager',NULL,'edit','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(9,'approval','user',NULL,'edit','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(10,'board','manager',NULL,'edit','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(11,'board','user',NULL,'edit','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(12,'hr','manager',NULL,'edit','부서장은 본인 부서 직원 조회','2026-05-13 10:01:15','2026-05-13 10:01:15'),(13,'hr','user',NULL,'view','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(14,'accounting','manager',NULL,'view','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(15,'accounting.settle','manager',NULL,'edit','회계 정산 · 본부장/회계팀만','2026-05-13 10:01:15','2026-05-13 10:01:15'),(16,'labor','manager',NULL,'view','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(17,'labor.rules','manager',NULL,'view','취업규칙 편집은 admin 전용 (기본)','2026-05-13 10:01:15','2026-05-13 10:01:15'),(18,'business','manager',NULL,'edit','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(19,'business','user',NULL,'view','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(20,'business_docs','manager',NULL,'view','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(21,'business_docs','user',NULL,'view','','2026-05-13 10:01:15','2026-05-13 10:01:15'),(22,'groupware','admin',NULL,'admin','시스템 관리는 관리자만','2026-05-13 10:01:15','2026-05-13 10:01:15'),(23,'groupware.permissions','admin',NULL,'admin','접근권한 관리는 관리자만','2026-05-13 10:01:15','2026-05-13 10:01:15'),(24,'hospital','manager',NULL,'edit','병원 전용 운영관리','2026-05-13 10:01:15','2026-05-13 10:01:15'),(25,'hospital','user',NULL,'view','병원 전용 운영조회','2026-05-13 10:01:15','2026-05-13 10:01:15');
/*!40000 ALTER TABLE `menu_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '1',
  `type` varchar(50) NOT NULL COMMENT '알림 유형',
  `title` varchar(200) NOT NULL,
  `message` text,
  `link_url` varchar(500) DEFAULT NULL COMMENT '클릭 시 이동 URL',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='알림';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'doc_upload','서류 업로드 완료','2025년 12월 급여대장이 업로드되었습니다.','/pages/tax_docs.php',0,'2026-05-13 10:00:48'),(2,1,'doc_request','새 서류 요청','세무사가 사업자등록증 사본을 요청했습니다.','/pages/tax_docs.php',0,'2026-05-13 10:00:48'),(3,1,'doc_confirmed','서류 확인 완료','사업자등록증 사본 확인이 완료되었습니다.','/pages/tax_docs.php',0,'2026-05-13 10:00:48');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outside_work_records`
--

DROP TABLE IF EXISTS `outside_work_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outside_work_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `work_date` date NOT NULL,
  `departure_time` time DEFAULT NULL,
  `return_time` time DEFAULT NULL,
  `destination` varchar(200) NOT NULL,
  `purpose` varchar(300) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`,`work_date`),
  CONSTRAINT `fk_ow_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outside_work_records`
--

LOCK TABLES `outside_work_records` WRITE;
/*!40000 ALTER TABLE `outside_work_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `outside_work_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslips` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `employee_name` varchar(50) NOT NULL,
  `year` smallint unsigned NOT NULL,
  `month` tinyint unsigned NOT NULL,
  `base_salary` bigint NOT NULL DEFAULT '0' COMMENT '기본급',
  `overtime_hours` decimal(5,1) NOT NULL DEFAULT '0.0' COMMENT '초과근무시간',
  `overtime_pay` bigint NOT NULL DEFAULT '0' COMMENT '초과수당',
  `meal_allowance` bigint NOT NULL DEFAULT '0' COMMENT '식대',
  `car_allowance` bigint NOT NULL DEFAULT '0' COMMENT '차량지원',
  `child_allowance` bigint NOT NULL DEFAULT '0' COMMENT '육아수당',
  `gross_pay` bigint NOT NULL DEFAULT '0' COMMENT '총지급액',
  `national_pension` bigint NOT NULL DEFAULT '0' COMMENT '국민연금',
  `health_insurance` bigint NOT NULL DEFAULT '0' COMMENT '건강보험',
  `emp_insurance` bigint NOT NULL DEFAULT '0' COMMENT '고용보험',
  `income_tax` bigint NOT NULL DEFAULT '0' COMMENT '소득세',
  `total_deduction` bigint NOT NULL DEFAULT '0' COMMENT '총공제액',
  `net_pay` bigint NOT NULL DEFAULT '0' COMMENT '실수령액',
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_payslip_month_employee` (`year`,`month`,`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='급여 명세';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslips`
--

LOCK TABLES `payslips` WRITE;
/*!40000 ALTER TABLE `payslips` DISABLE KEYS */;
/*!40000 ALTER TABLE `payslips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservation_resource_config`
--

DROP TABLE IF EXISTS `reservation_resource_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservation_resource_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL COMMENT 'common_code_items.id',
  `max_count` int NOT NULL DEFAULT '1' COMMENT '최대 동시 예약 수',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservation_resource_config`
--

LOCK TABLES `reservation_resource_config` WRITE;
/*!40000 ALTER TABLE `reservation_resource_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `reservation_resource_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resource_item_id` int NOT NULL COMMENT 'common_code_items.id',
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT '예약 제목',
  `user_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '예약자명',
  `reservation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `description` text COLLATE utf8mb4_general_ci COMMENT '메모',
  `status` enum('confirmed','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'confirmed',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`reservation_date`),
  KEY `idx_resource_date` (`resource_item_id`,`reservation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedule_attendees`
--

DROP TABLE IF EXISTS `schedule_attendees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule_attendees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `schedule_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `response_status` enum('pending','accepted','declined') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending' COMMENT 'Phase 2: 응답상태',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schedule_employee` (`schedule_id`,`employee_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `schedule_attendees_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `schedule_attendees_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedule_attendees`
--

LOCK TABLES `schedule_attendees` WRITE;
/*!40000 ALTER TABLE `schedule_attendees` DISABLE KEYS */;
INSERT INTO `schedule_attendees` VALUES (1,1,1,'pending','2026-05-13 10:01:14'),(2,1,2,'pending','2026-05-13 10:01:14'),(3,1,3,'pending','2026-05-13 10:01:14'),(4,2,1,'pending','2026-05-13 10:01:14'),(5,2,4,'pending','2026-05-13 10:01:14'),(6,3,1,'pending','2026-05-13 10:01:14'),(7,3,2,'pending','2026-05-13 10:01:14'),(8,5,1,'pending','2026-05-13 10:01:14'),(9,5,2,'pending','2026-05-13 10:01:14'),(10,5,3,'pending','2026-05-13 10:01:14'),(11,5,4,'pending','2026-05-13 10:01:14'),(12,6,1,'pending','2026-05-13 10:01:14'),(13,7,1,'pending','2026-05-13 10:01:14'),(14,7,2,'pending','2026-05-13 10:01:14'),(15,7,3,'pending','2026-05-13 10:01:14'),(16,7,4,'pending','2026-05-13 10:01:14');
/*!40000 ALTER TABLE `schedule_attendees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedule_category_config`
--

DROP TABLE IF EXISTS `schedule_category_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule_category_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL COMMENT 'common_code_items.id',
  `color_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'blue',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_item` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedule_category_config`
--

LOCK TABLES `schedule_category_config` WRITE;
/*!40000 ALTER TABLE `schedule_category_config` DISABLE KEYS */;
INSERT INTO `schedule_category_config` VALUES (1,62,'blue'),(2,63,'green'),(3,64,'red'),(4,65,'purple'),(5,66,'yellow');
/*!40000 ALTER TABLE `schedule_category_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT '일정 제목',
  `description` text COLLATE utf8mb4_general_ci COMMENT '일정 내용',
  `start_date` date NOT NULL COMMENT '시작일',
  `start_time` time DEFAULT NULL COMMENT '시작시간 (NULL=종일)',
  `end_date` date NOT NULL COMMENT '종료일',
  `end_time` time DEFAULT NULL COMMENT '종료시간 (NULL=종일)',
  `is_all_day` tinyint(1) NOT NULL DEFAULT '0' COMMENT '종일 여부',
  `category_item_id` int DEFAULT NULL COMMENT 'common_code_items.id (일정유형)',
  `creator_id` int NOT NULL COMMENT 'employees.id (작성자)',
  `visibility` enum('public','private','department') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'public' COMMENT '공개범위',
  `recurrence_rule` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Phase 2: 반복규칙',
  `status` enum('active','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_date_range` (`start_date`,`end_date`),
  KEY `idx_creator` (`creator_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (1,'전사 회의','4월 경영 현황 보고 및 목표 점검','2026-04-03','10:00:00','2026-04-03','11:00:00',0,62,1,'public',NULL,'active','2026-05-13 10:01:14','2026-05-13 10:01:14'),(2,'프로젝트 킥오프','신규 웹서비스 프로젝트 시작','2026-04-07','14:00:00','2026-04-07','15:30:00',0,63,1,'public',NULL,'active','2026-05-13 10:01:14','2026-05-13 10:01:14'),(3,'디자인 리뷰','UI/UX 시안 검토','2026-04-10','11:00:00','2026-04-10','12:00:00',0,62,1,'public',NULL,'active','2026-05-13 10:01:14','2026-05-13 10:01:14'),(4,'고객사 출장','서울 본사 미팅','2026-04-14',NULL,'2026-04-15',NULL,1,64,1,'public',NULL,'active','2026-05-13 10:01:14','2026-05-13 10:01:14'),(5,'월간 보고','4월 실적 정리 및 보고','2026-04-18','09:00:00','2026-04-18','10:00:00',0,62,1,'public',NULL,'active','2026-05-13 10:01:14','2026-05-13 10:01:14'),(6,'신입사원 교육','온보딩 프로그램 1일차','2026-04-21','09:00:00','2026-04-21','17:00:00',0,65,1,'public',NULL,'active','2026-05-13 10:01:14','2026-05-13 10:01:14'),(7,'기술 세미나','AI 트렌드 세미나','2026-04-24','14:00:00','2026-04-24','16:00:00',0,65,1,'public',NULL,'active','2026-05-13 10:01:14','2026-05-13 10:01:14'),(8,'월말 정산','4월 경비 정산 마감','2026-04-28','17:00:00','2026-04-28','18:00:00',0,66,1,'public',NULL,'active','2026-05-13 10:01:14','2026-05-13 10:01:14');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_credit_simulations`
--

DROP TABLE IF EXISTS `tax_credit_simulations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_credit_simulations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int unsigned NOT NULL DEFAULT '1',
  `sim_year` smallint unsigned NOT NULL COMMENT '공제 적용 연도',
  `base_employee_count` int unsigned NOT NULL DEFAULT '0' COMMENT '기준년도 상시근로자 수',
  `current_employee_count` int unsigned NOT NULL DEFAULT '0' COMMENT '당해년도 상시근로자 수',
  `youth_count` int unsigned NOT NULL DEFAULT '0' COMMENT '청년 증가 인원',
  `elder_count` int unsigned NOT NULL DEFAULT '0' COMMENT '장년 증가 인원',
  `region` enum('수도권','비수도권') NOT NULL DEFAULT '수도권',
  `youth_credit_per` bigint NOT NULL DEFAULT '11000000' COMMENT '청년 인당 공제액',
  `elder_credit_per` bigint NOT NULL DEFAULT '7700000' COMMENT '장년 인당 공제액',
  `total_credit` bigint NOT NULL DEFAULT '0' COMMENT '총 세액공제액',
  `memo` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='세액공제 시뮬레이션 이력';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_credit_simulations`
--

LOCK TABLES `tax_credit_simulations` WRITE;
/*!40000 ALTER TABLE `tax_credit_simulations` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_credit_simulations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_invoice_items`
--

DROP TABLE IF EXISTS `tax_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_invoice_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int unsigned NOT NULL,
  `item_date` date DEFAULT NULL COMMENT '품목 일자',
  `item_name` varchar(200) NOT NULL COMMENT '품목명',
  `spec` varchar(100) DEFAULT NULL COMMENT '규격',
  `quantity` decimal(12,2) NOT NULL DEFAULT '1.00' COMMENT '수량',
  `unit_price` bigint NOT NULL DEFAULT '0' COMMENT '단가',
  `supply_amount` bigint NOT NULL DEFAULT '0' COMMENT '공급가액',
  `tax_amount` bigint NOT NULL DEFAULT '0' COMMENT '세액',
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `tax_invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `tax_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='세금계산서 품목';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_invoice_items`
--

LOCK TABLES `tax_invoice_items` WRITE;
/*!40000 ALTER TABLE `tax_invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_invoices`
--

DROP TABLE IF EXISTS `tax_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int unsigned NOT NULL DEFAULT '1',
  `invoice_type` enum('매출','매입') NOT NULL COMMENT '매출/매입 구분',
  `invoice_number` varchar(32) NOT NULL COMMENT '세금계산서 승인번호',
  `issue_date` date NOT NULL COMMENT '작성일자',
  `send_date` date DEFAULT NULL COMMENT '전송일자',
  `supplier_bizno` varchar(12) NOT NULL COMMENT '공급자 사업자번호',
  `supplier_name` varchar(100) NOT NULL COMMENT '공급자 상호',
  `supplier_ceo` varchar(50) DEFAULT NULL COMMENT '공급자 대표자',
  `buyer_bizno` varchar(12) NOT NULL COMMENT '공급받는자 사업자번호',
  `buyer_name` varchar(100) NOT NULL COMMENT '공급받는자 상호',
  `buyer_ceo` varchar(50) DEFAULT NULL COMMENT '공급받는자 대표자',
  `supply_amount` bigint NOT NULL DEFAULT '0' COMMENT '공급가액',
  `tax_amount` bigint NOT NULL DEFAULT '0' COMMENT '세액',
  `total_amount` bigint NOT NULL DEFAULT '0' COMMENT '합계금액',
  `tax_type` enum('과세','영세율','면세') NOT NULL DEFAULT '과세' COMMENT '과세유형',
  `invoice_status` enum('정상','수정','취소') NOT NULL DEFAULT '정상',
  `hometax_sync` tinyint(1) NOT NULL DEFAULT '0' COMMENT '홈택스 동기화 여부',
  `synced_at` datetime DEFAULT NULL COMMENT '마지막 동기화 시각',
  `memo` varchar(200) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_date` (`invoice_type`,`issue_date`),
  KEY `idx_supplier` (`supplier_bizno`),
  KEY `idx_buyer` (`buyer_bizno`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='세금계산서';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_invoices`
--

LOCK TABLES `tax_invoices` WRITE;
/*!40000 ALTER TABLE `tax_invoices` DISABLE KEYS */;
INSERT INTO `tax_invoices` VALUES (1,1,'매출','20260201-41000001-00000001','2026-02-01','2026-02-02','123-45-67890','주식회사 재밋','송승환','234-56-78901','(주)테크솔루션','김태호',15000000,1500000,16500000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(2,1,'매출','20260205-41000001-00000002','2026-02-05','2026-02-06','123-45-67890','주식회사 재밋','송승환','345-67-89012','디자인웍스','박지연',8500000,850000,9350000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(3,1,'매출','20260210-41000001-00000003','2026-02-10','2026-02-11','123-45-67890','주식회사 재밋','송승환','456-78-90123','(주)스마트커머스','이준혁',3200000,320000,3520000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(4,1,'매출','20260215-41000001-00000004','2026-02-15','2026-02-16','123-45-67890','주식회사 재밋','송승환','567-89-01234','한국데이터','최수진',12000000,1200000,13200000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(5,1,'매출','20260218-41000001-00000005','2026-02-18','2026-02-19','123-45-67890','주식회사 재밋','송승환','234-56-78901','(주)테크솔루션','김태호',5500000,550000,6050000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(6,1,'매출','20260220-41000001-00000006','2026-02-20','2026-02-21','123-45-67890','주식회사 재밋','송승환','678-90-12345','그린에너지(주)','정민우',2000000,0,2000000,'영세율','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(7,1,'매출','20260225-41000001-00000007','2026-02-25','2026-02-26','123-45-67890','주식회사 재밋','송승환','789-01-23456','(주)미래건설','한동훈',9800000,980000,10780000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(8,1,'매입','20260203-52000001-00000001','2026-02-03','2026-02-04','890-12-34567','NHN클라우드(주)','김동훈','123-45-67890','주식회사 재밋','송승환',4200000,420000,4620000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(9,1,'매입','20260205-52000001-00000002','2026-02-05','2026-02-06','901-23-45678','(주)오피스허브','윤서영','123-45-67890','주식회사 재밋','송승환',1800000,180000,1980000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(10,1,'매입','20260210-52000001-00000003','2026-02-10','2026-02-11','012-34-56789','세종사무기기','강현수','123-45-67890','주식회사 재밋','송승환',650000,65000,715000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(11,1,'매입','20260212-52000001-00000004','2026-02-12','2026-02-13','890-12-34567','NHN클라우드(주)','김동훈','123-45-67890','주식회사 재밋','송승환',3800000,380000,4180000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(12,1,'매입','20260215-52000001-00000005','2026-02-15','2026-02-16','234-56-00001','(주)디지털마케팅','오지훈','123-45-67890','주식회사 재밋','송승환',3500000,350000,3850000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(13,1,'매입','20260220-52000001-00000006','2026-02-20','2026-02-21','345-67-00002','코리아호스팅','임재현','123-45-67890','주식회사 재밋','송승환',980000,98000,1078000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-05-13 10:00:48'),(14,1,'매입','20260222-52000001-00000007','2026-02-22',NULL,'456-78-00003','인테리어플러스','배수진','123-45-67890','주식회사 재밋','송승환',2500000,250000,2750000,'과세','수정',0,NULL,NULL,'2026-05-13 10:00:48');
/*!40000 ALTER TABLE `tax_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Hospital module schema and seed data
--

SOURCE db/schema_hospital.sql;

--
-- Dumping events for database 'zaemit_groupware'
--

--
-- Dumping routines for database 'zaemit_groupware'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-26 15:41:22
