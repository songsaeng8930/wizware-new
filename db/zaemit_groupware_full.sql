-- MySQL dump 10.13  Distrib 8.4.8, for Win64 (x86_64)
--
-- Host: localhost    Database: zaemit_groupware
-- ------------------------------------------------------
-- Server version	8.4.8

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
-- Table structure for table `account_categories`
--

DROP TABLE IF EXISTS `account_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_general_ci NOT NULL COMMENT '계정과목 코드 (예: 511)',
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '계정과목명 (예: 급여)',
  `parent_code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '상위 계정과목 코드',
  `type` enum('매출','매입','자산','부채','자본','비용','수익') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '비용',
  `tax_type` enum('과세','면세','영세율','불공제') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '과세',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=324 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='계정과목 마스터';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_categories`
--

LOCK TABLES `account_categories` WRITE;
/*!40000 ALTER TABLE `account_categories` DISABLE KEYS */;
INSERT INTO `account_categories` VALUES (30,'10100','현금','G_CA','자산','불공제',1,1000,'2026-07-03 01:24:09'),(31,'25100','외상매입금','G_CL','부채','불공제',1,2000,'2026-07-03 01:24:09'),(32,'40100','상품매출','G_SL','매출','과세',1,4000,'2026-07-03 01:24:09'),(33,'40200','제품매출','G_SL','매출','과세',1,4005,'2026-07-03 01:24:09'),(34,'40400','임대수입','G_SL','매출','과세',1,4020,'2026-07-03 01:24:09'),(35,'45100','상품매입','G_CG','매입','과세',1,4510,'2026-07-03 01:24:09'),(36,'45200','원재료매입','G_CG','매입','과세',1,4520,'2026-07-03 01:24:09'),(37,'82700','차량유지비','G_SGA','비용','과세',1,5145,'2026-07-03 01:24:09'),(38,'80900','감가상각비','G_SGA','비용','불공제',1,5035,'2026-07-03 01:24:09'),(39,'G_CA','유동자산',NULL,'자산','불공제',1,1000,'2026-07-03 01:24:09'),(40,'G_FA','유형자산',NULL,'자산','불공제',1,1085,'2026-07-03 01:24:09'),(41,'G_IA','무형자산',NULL,'자산','불공제',1,1115,'2026-07-03 01:24:09'),(42,'G_OA','기타비유동자산',NULL,'자산','불공제',1,1135,'2026-07-03 01:24:09'),(43,'G_CL','유동부채',NULL,'부채','불공제',1,2000,'2026-07-03 01:24:09'),(44,'G_NL','비유동부채',NULL,'부채','불공제',1,2105,'2026-07-03 01:24:09'),(45,'G_EQ','자본',NULL,'자본','불공제',1,3000,'2026-07-03 01:24:09'),(46,'G_SL','매출',NULL,'매출','과세',1,4000,'2026-07-03 01:24:09'),(47,'G_CG','매출원가',NULL,'매입','과세',1,4500,'2026-07-03 01:24:09'),(48,'G_NI','영업외수익',NULL,'수익','불공제',1,4505,'2026-07-03 01:24:09'),(49,'G_SGA','판매비와관리비',NULL,'비용','불공제',1,5000,'2026-07-03 01:24:09'),(50,'G_NE','영업외비용',NULL,'비용','불공제',1,5235,'2026-07-03 01:24:09');
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
  `employee_id` int NOT NULL,
  `year` smallint NOT NULL,
  `total_days` decimal(4,1) NOT NULL DEFAULT '15.0',
  `used_days` decimal(4,1) NOT NULL DEFAULT '0.0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_year` (`employee_id`,`year`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annual_leave`
--

LOCK TABLES `annual_leave` WRITE;
/*!40000 ALTER TABLE `annual_leave` DISABLE KEYS */;
INSERT INTO `annual_leave` VALUES (1,1,2026,17.0,1.0,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(2,2,2026,17.0,2.5,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(3,3,2026,17.0,2.5,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(4,4,2026,17.0,5.0,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(5,5,2026,17.0,0.0,'2026-04-14 18:23:13','2026-04-14 18:23:13'),(6,6,2026,17.0,1.0,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(7,7,2026,17.0,2.5,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(8,8,2026,17.0,4.5,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(9,9,2026,17.0,5.0,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(10,10,2026,17.0,0.0,'2026-04-14 18:23:13','2026-04-14 18:23:13'),(11,11,2026,17.0,1.0,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(12,12,2026,17.0,2.5,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(13,13,2026,16.0,2.5,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(14,14,2026,16.0,5.0,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(15,15,2026,16.0,0.0,'2026-04-14 18:23:13','2026-04-14 18:23:13'),(16,16,2026,16.0,1.0,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(17,17,2026,16.0,2.5,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(18,18,2026,16.0,2.5,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(19,19,2026,16.0,5.0,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(20,20,2026,16.0,0.0,'2026-04-14 18:23:13','2026-04-14 18:23:13'),(21,21,2026,16.0,1.0,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(22,22,2026,15.0,2.5,'2026-04-14 18:23:13','2026-07-03 02:21:57'),(45,1,2025,17.0,1.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(46,2,2025,17.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(47,3,2025,17.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(48,4,2025,17.0,5.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(49,5,2025,16.0,0.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(50,6,2025,16.0,1.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(51,7,2025,16.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(52,8,2025,16.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(53,9,2025,16.0,5.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(54,10,2025,16.0,0.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(55,11,2025,16.0,1.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(56,12,2025,16.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(57,13,2025,16.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(58,14,2025,16.0,5.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(59,15,2025,16.0,0.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(60,16,2025,15.0,1.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(61,17,2025,16.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(62,18,2025,15.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(63,19,2025,16.0,5.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(64,20,2025,16.0,0.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(65,21,2025,15.0,1.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(66,22,2025,15.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(67,1,2024,16.0,1.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(68,2,2024,16.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(69,3,2024,16.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(70,4,2024,16.0,5.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(71,5,2024,16.0,0.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(72,6,2024,16.0,1.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(73,7,2024,16.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(74,8,2024,16.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(75,9,2024,16.0,5.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(76,10,2024,16.0,0.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(77,11,2024,16.0,1.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(78,12,2024,16.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(79,13,2024,15.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(80,14,2024,15.0,5.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(81,15,2024,15.0,0.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(82,16,2024,15.0,1.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(83,17,2024,15.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(84,18,2024,15.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(85,19,2024,15.0,5.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(86,20,2024,15.0,0.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(87,21,2024,15.0,1.0,'2026-07-03 02:34:28','2026-07-03 02:34:28'),(88,22,2024,11.0,2.5,'2026-07-03 02:34:28','2026-07-03 02:34:28');
/*!40000 ALTER TABLE `annual_leave` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_audit_log`
--

DROP TABLE IF EXISTS `approval_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_type` varchar(30) NOT NULL COMMENT '이벤트 종류 (document_created, form_updated 등)',
  `event_category` varchar(20) NOT NULL COMMENT '카테고리 (document/admin/form/config)',
  `actor_id` int DEFAULT NULL COMMENT '수행자 employee_id',
  `actor_name` varchar(50) NOT NULL COMMENT '수행자 이름 (비정규화)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT '접속 IP 주소 (IPv4/IPv6)',
  `target_type` varchar(30) NOT NULL COMMENT '대상 종류 (document/form/line/config)',
  `target_id` int DEFAULT NULL COMMENT '대상 레코드 ID',
  `target_label` varchar(200) DEFAULT NULL COMMENT '사람이 읽을 수 있는 대상 식별자',
  `old_value` json DEFAULT NULL COMMENT '변경 전 값',
  `new_value` json DEFAULT NULL COMMENT '변경 후 값',
  `comment` text COMMENT '사유 (강제 완료/반려 시 필수)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_category` (`event_category`),
  KEY `idx_audit_actor` (`actor_id`),
  KEY `idx_audit_target` (`target_type`,`target_id`),
  KEY `idx_audit_created` (`created_at`),
  KEY `idx_audit_event_type` (`event_type`),
  KEY `idx_audit_view_dedup` (`event_type`,`actor_id`,`target_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_audit_log`
--

LOCK TABLES `approval_audit_log` WRITE;
/*!40000 ALTER TABLE `approval_audit_log` DISABLE KEYS */;
INSERT INTO `approval_audit_log` VALUES (1,'document_viewed','document',1,'김대표','127.0.0.1','document',61,'Zaemit_TEST_반려정리_1776222076',NULL,NULL,NULL,'2026-07-03 02:17:17'),(2,'document_viewed','document',1,'김대표','127.0.0.1','document',60,'Zaemit_TEST_반려_20260415045651',NULL,NULL,NULL,'2026-07-03 02:17:21'),(3,'document_viewed','document',1,'김대표','127.0.0.1','document',60,'Zaemit_TEST_반려_20260415045651',NULL,NULL,NULL,'2026-07-03 02:25:40');
/*!40000 ALTER TABLE `approval_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_delegates`
--

DROP TABLE IF EXISTS `approval_delegates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_delegates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `delegator_id` int NOT NULL COMMENT '원결재자 employees.id',
  `delegator_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '원결재자 이름 스냅샷',
  `delegate_id` int NOT NULL COMMENT '대결자 employees.id',
  `delegate_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '대결자 이름 스냅샷',
  `start_date` date NOT NULL COMMENT '대결 시작일',
  `end_date` date NOT NULL COMMENT '대결 종료일',
  `status` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active/expired/cancelled',
  `created_by_id` int NOT NULL COMMENT '설정자 employees.id',
  `created_by_type` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'self' COMMENT 'self/admin',
  `reason` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '대결 사유',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cancelled_at` datetime DEFAULT NULL COMMENT '해제 일시',
  PRIMARY KEY (`id`),
  KEY `idx_delegator` (`delegator_id`,`status`),
  KEY `idx_delegate` (`delegate_id`,`status`),
  KEY `idx_date_range` (`start_date`,`end_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_delegates`
--

LOCK TABLES `approval_delegates` WRITE;
/*!40000 ALTER TABLE `approval_delegates` DISABLE KEYS */;
/*!40000 ALTER TABLE `approval_delegates` ENABLE KEYS */;
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
  `form_id` int DEFAULT NULL COMMENT '결재양식 ID',
  `doc_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '문서종류',
  `drafter_id` int DEFAULT NULL,
  `drafter_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '기안자',
  `drafter_dept` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '기안부서',
  `status` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '기안' COMMENT '상태 (기안/진행/승인/반려/임시저장)',
  `metadata` text COLLATE utf8mb4_general_ci,
  `draft_date` date NOT NULL COMMENT '기안일',
  `complete_date` date DEFAULT NULL COMMENT '완료일',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'soft delete 플래그',
  `deleted_by` int DEFAULT NULL COMMENT '삭제자 employee_id',
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  KEY `idx_drafter_id` (`drafter_id`),
  KEY `idx_doc_status_date` (`status`,`draft_date`),
  KEY `idx_doc_deleted` (`is_deleted`),
  CONSTRAINT `approval_documents_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `approval_forms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_documents`
--

LOCK TABLES `approval_documents` WRITE;
/*!40000 ALTER TABLE `approval_documents` DISABLE KEYS */;
INSERT INTO `approval_documents` VALUES (38,'Zaemit_개발1_휴가_20260320100000','연차 사용 신청 (3/28~3/29)','<table style=\"width:100%;border-collapse:collapse;margin-bottom:16px;\">\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600;\">휴가 종류</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">연차</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600;\">잔여 연차</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">12일</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">시작일</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">2026-03-28 (토)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">종료일</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">2026-03-29 (일)</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">사용일수</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">2일</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">비상연락처</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">010-1234-5678</td></tr>\n</table>\n<p><b>사유:</b> 개인 사유로 인한 연차 사용 신청합니다. 업무 인수인계는 같은 팀 강개발 부장님께 완료하였습니다.</p>',NULL,'휴가신청서',15,'박대리','개발1팀','대기',NULL,'2026-03-20',NULL,'2026-03-27 16:24:11','2026-04-15 10:43:40',0,NULL),(39,'Zaemit_영업_외근_20260321090000','거래처 방문 외근 신청','<table style=\"width:100%;border-collapse:collapse;margin-bottom:16px;\">\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600;\">외근일</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">2026-03-25 (수)</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">외근 시간</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">14:00 ~ 18:00</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">외근지</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">서울특별시 강남구 역삼동 823-7 (주)그린테크 본사</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">목적</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">신규 거래처 미팅 및 서비스 데모 시연</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">동행인</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">최영업 이사</td></tr>\n</table>\n<p>그린테크 IT팀 이상훈 팀장과 사전 협의된 미팅입니다. Zaemit 그룹웨어 패키지 도입 관련 데모 시연 및 견적 논의 예정입니다.</p>',NULL,'외근신청서',20,'배대리','국내영업팀','대기',NULL,'2026-03-21',NULL,'2026-03-27 16:24:11','2026-04-15 10:43:40',0,NULL),(40,'Zaemit_개발1_품의_20260312110000','NHN클라우드 2026년 02월 서버 비용','<p>NHN클라우드 서버 운영에 따른 2026년 2월분 비용을 아래와 같이 품의합니다.</p>\n<table style=\"width:100%;border-collapse:collapse;margin:16px 0;\">\n<tr style=\"background:#f9fafb;\"><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left;\">항목</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left;\">사양</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">금액</th></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">Server (g3 Standard)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">vCPU 8 / Memory 32GB x 2대</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">1,240,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">Object Storage</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">500GB</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">85,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">CDN / Load Balancer</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">트래픽 기반</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">320,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">DB (CloudDB for MySQL)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">Standard / 100GB</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">205,000원</td></tr>\n<tr style=\"background:#f0f9ff;font-weight:600;\"><td style=\"padding:8px 12px;border:1px solid #d1d5db;\" colspan=\"2\">합계 (VAT 별도)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">1,850,000원</td></tr>\n</table>\n<p>전월 대비 동일 수준이며, 서비스 안정 운영을 위해 승인 부탁드립니다.</p>',NULL,'품의서',8,'강부장','개발1팀','승인',NULL,'2026-03-12','2026-04-15','2026-03-27 16:24:11','2026-04-15 10:43:40',0,NULL),(41,'Zaemit_경영_품의_20260310140000','사무용품 일괄 구매 품의','<p>2026년 1분기 사무용품이 소진되어 아래와 같이 일괄 구매를 품의합니다.</p>\n<table style=\"width:100%;border-collapse:collapse;margin:16px 0;\">\n<tr style=\"background:#f9fafb;\"><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left;\">품목</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">수량</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">단가</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">금액</th></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">A4 복사용지 (80g)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">20박스</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">12,000원</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">240,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">레이저 프린터 토너 (HP)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">3개</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">45,000원</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">135,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">볼펜 / 형광펜 세트</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">30세트</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">3,500원</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">105,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">포스트잇 / 바인더 등</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">일괄</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">-</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">40,000원</td></tr>\n<tr style=\"background:#f0f9ff;font-weight:600;\"><td style=\"padding:8px 12px;border:1px solid #d1d5db;\" colspan=\"3\">합계 (VAT 포함)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">520,000원</td></tr>\n</table>\n<p>구매처: 오피스디포 온라인몰 (최저가 비교 완료). 납품 예정일: 주문 후 2영업일.</p>',NULL,'품의서',13,'김대리','경영지원팀','진행',NULL,'2026-03-10',NULL,'2026-03-27 16:24:11','2026-04-15 10:43:40',0,NULL),(42,'Zaemit_개발2_출장_20260305090000','부산 고객사 기술 지원 출장','<table style=\"width:100%;border-collapse:collapse;margin-bottom:16px;\">\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600;\">출장지</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">부산광역시 해운대구 센텀중앙로 48 (주)마린시스템즈</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">출장 기간</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">2026-03-10 (화) ~ 2026-03-12 (목) / 2박 3일</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">출장 목적</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">고객사 시스템 구축 현장 지원 및 기술 교육</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">동행자</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">없음 (단독 출장)</td></tr>\n</table>\n<p><b>예상 경비:</b></p>\n<table style=\"width:100%;border-collapse:collapse;margin:8px 0 16px;\">\n<tr style=\"background:#f9fafb;\"><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left;\">항목</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">금액</th></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">교통비 (KTX 왕복)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">118,600원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">숙박비 (2박)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">160,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">식비 (일비 포함)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">90,000원</td></tr>\n<tr style=\"background:#f0f9ff;font-weight:600;\"><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">합계</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">368,600원</td></tr>\n</table>\n<p>마린시스템즈 IT인프라팀 김해준 차장과 사전 일정 조율 완료. 현장 서버 환경 구성 및 운영자 교육 진행 예정입니다.</p>',NULL,'출장신청서',17,'조대리','개발2팀','반려',NULL,'2026-03-05','2026-04-15','2026-03-27 16:24:11','2026-04-15 10:43:40',0,NULL),(43,'Zaemit_인사_품의_20260315100000','2026년 상반기 직무교육 예산','<p>2026년 상반기 직무역량 강화를 위한 교육 예산을 아래와 같이 품의합니다.</p>\n<table style=\"width:100%;border-collapse:collapse;margin:16px 0;\">\n<tr style=\"background:#f9fafb;\"><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left;\">교육과정</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">대상</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">인원</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">비용</th></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">정보보안 인식 교육</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">전 직원</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">32명</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">960,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">React / Next.js 심화</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">개발팀</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">8명</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">1,600,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">클라우드 인프라 운영</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">개발팀</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">4명</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">1,200,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">리더십 역량 과정</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">팀장급 이상</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center;\">6명</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">1,240,000원</td></tr>\n<tr style=\"background:#f0f9ff;font-weight:600;\"><td style=\"padding:8px 12px;border:1px solid #d1d5db;\" colspan=\"3\">합계</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right;\">5,000,000원</td></tr>\n</table>\n<p>교육 기관: 패스트캠퍼스 B2B / 한국정보보호진흥원. 교육 일정은 4월~6월 중 부서별 협의 후 확정 예정.</p>',NULL,'품의서',14,'이대리','인사팀','진행',NULL,'2026-03-15',NULL,'2026-03-27 16:24:11','2026-04-15 10:43:40',0,NULL),(44,'Zaemit_개발2_야근_20260224140000','긴급 배포 대응 야근 신청','<table style=\"width:100%;border-collapse:collapse;margin-bottom:16px;\">\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600;\">야근일</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">2026-02-26 (목)</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">야근 시간</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">18:00 ~ 23:00 (5시간)</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">야근 사유</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">고객사 긴급 핫픽스 배포 및 모니터링</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">업무 내용</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">마린시스템즈 결제 모듈 오류 긴급 수정 → 스테이징 테스트 → 프로덕션 배포 → 배포 후 1시간 모니터링</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;\">식대 청구</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">12,000원 (저녁 식대)</td></tr>\n</table>\n<p>마린시스템즈 프로덕션 환경에서 결제 실패 이슈가 발생하여 당일 긴급 대응이 필요합니다. 배포 후 안정화 확인까지 야근이 불가피합니다.</p>',NULL,'야근신청서',17,'조대리','개발2팀','승인',NULL,'2026-02-24','2026-02-25','2026-03-27 16:24:11','2026-04-15 10:43:40',0,NULL),(45,'Zaemit_영업_경비_20260301150000','3월 거래처 접대비 경비 청구','<p>거래처 미팅 후 접대비를 아래와 같이 청구합니다.</p>\n<table style=\"width:100%;border-collapse:collapse;margin:16px 0;\">\n<tr style=\"background:#f9fafb;\"><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left;\">항목</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left;\">내용</th></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;font-weight:600;\">사용일</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">2026-02-28 (금)</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;font-weight:600;\">사용처</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">트라토리아 디 마레 (역삼동)</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;font-weight:600;\">참석자</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">당사: 서영업, 최영업 이사 / 거래처: (주)한빛소프트 김정우 부장 외 1명</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;font-weight:600;\">미팅 목적</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">2026년 연간 유지보수 계약 갱신 논의</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;font-weight:600;\">청구 금액</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;font-weight:600;color:#1d4ed8;\">186,000원</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;font-weight:600;\">결제 수단</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;\">법인카드 (서영업)</td></tr>\n</table>\n<p>영수증 및 법인카드 매출전표를 첨부합니다.</p>',NULL,'경비청구서',11,'서과장','국내영업팀','반려',NULL,'2026-03-01','2026-03-03','2026-03-27 16:24:11','2026-04-15 10:43:40',0,NULL),(46,'Zaemit_개발1팀_휴일근무_20260405230605','333','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 휴일근무 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">근 무 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">근무일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (&nbsp;&nbsp;요일)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">근무시간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 ~&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 (총&nbsp;&nbsp;&nbsp;&nbsp;시간)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">근무구분</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 토요일&nbsp;&nbsp;&nbsp;□ 일요일&nbsp;&nbsp;&nbsp;□ 법정공휴일&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">근무사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">업무내용</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">보&nbsp;&nbsp;&nbsp;&nbsp;상</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 대체휴무&nbsp;&nbsp;&nbsp;□ 휴일수당&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 휴일근무를 신청하오니 승인하여 주시기 바랍니다.</p>',NULL,'휴일근무',15,'박대리','개발1팀','임시저장',NULL,'2026-04-05',NULL,'2026-04-06 06:06:05','2026-04-15 10:43:40',0,NULL),(47,'Zaemit_개발1팀_휴가신청서_20260405231047','3322222','<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 휴가 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">신 청 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">휴가종류</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 연차&nbsp;&nbsp;&nbsp;□ 반차(오전/오후)&nbsp;&nbsp;&nbsp;□ 병가&nbsp;&nbsp;&nbsp;□ 경조사&nbsp;&nbsp;&nbsp;□ 기타(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">휴가기간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 ~&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (총&nbsp;&nbsp;&nbsp;&nbsp;일)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">비상연락처</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">휴가사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">업무인수자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 휴가를 신청하오니 승인하여 주시기 바랍니다.</p>',NULL,'휴가신청서',15,'박대리','개발1팀','임시저장',NULL,'2026-04-05',NULL,'2026-04-06 06:10:47','2026-04-15 10:43:40',0,NULL),(48,'TEST_S01','2026년 상반기 팀 워크숍 기획 품의','<p>2026년 상반기 개발팀 워크숍을 아래와 같이 기획하여 품의합니다.</p>\r\n<table style=\"width:100%;border-collapse:collapse;margin:12px 0\">\r\n<tr style=\"background:#f9fafb\"><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left\">항목</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">금액</th></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">숙박 (1박, 15명)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">2,250,000원</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">식비 (2일)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">750,000원</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">교통비 (버스 대절)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">400,000원</td></tr>\r\n<tr style=\"background:#f0f9ff;font-weight:600\"><td style=\"padding:8px 12px;border:1px solid #d1d5db\">합계</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">3,400,000원</td></tr>\r\n</table>\r\n<p>일시: 2026년 5월 16~17일 (금~토) / 장소: 강원도 평창</p>',NULL,'품의서',8,'강부장','개발1팀','대기',NULL,'2026-04-10',NULL,'2026-04-15 10:12:37','2026-04-15 10:43:40',0,NULL),(49,'TEST_S02','연차 사용 신청 (4/21~4/22)','<table style=\"width:100%;border-collapse:collapse\">\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600\">휴가 종류</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">연차</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">기간</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">2026-04-21 (월) ~ 2026-04-22 (화) / 2일</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">사유</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">개인 사유 (가족 행사)</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">잔여 연차</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">12일</td></tr>\r\n</table>',NULL,'휴가신청서',15,'박대리','개발1팀','대기',NULL,'2026-04-14',NULL,'2026-04-15 10:12:37','2026-04-15 10:43:40',0,NULL),(50,'TEST_S03','대전 고객사 시스템 점검 출장','<table style=\"width:100%;border-collapse:collapse;margin-bottom:16px\">\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600\">출장지</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">대전광역시 유성구 테크노2로 (주)미래시스템</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">기간</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">2026-04-18 (금) / 당일 출장</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">목적</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">고객사 서버 정기 점검 및 보안 패치 적용</td></tr>\r\n</table>\r\n<p><b>예상 경비:</b> 교통비 59,000원 (KTX 왕복) + 식비 15,000원 = 74,000원</p>',NULL,'출장신청서',17,'조대리','개발2팀','진행',NULL,'2026-04-08',NULL,'2026-04-15 10:12:37','2026-04-15 10:43:40',0,NULL),(51,'TEST_S04','2026년 하반기 채용 공고 게시 품의','<p>2026년 하반기 신규 채용을 위한 공고 게시를 품의합니다.</p>\r\n<table style=\"width:100%;border-collapse:collapse;margin:12px 0\">\r\n<tr style=\"background:#f9fafb\"><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left\">직무</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center\">인원</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left\">비고</th></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">백엔드 개발자</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center\">2명</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">경력 3년 이상</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">UI/UX 디자이너</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center\">1명</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">경력 무관</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">영업 매니저</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:center\">1명</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">IT 영업 경험자</td></tr>\r\n</table>\r\n<p>공고 채널: 잡코리아, 사람인, 원티드 / 예산: 채널당 월 50만원</p>',NULL,'품의서',14,'이대리','인사팀','진행',NULL,'2026-04-07',NULL,'2026-04-15 10:12:37','2026-04-15 10:43:40',0,NULL),(52,'TEST_S05','긴급 서버 장애 대응 야근 신청','<table style=\"width:100%;border-collapse:collapse\">\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600\">야근 일시</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">2026-04-02 (수) 18:00 ~ 23:00</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">사유</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">프로덕션 서버 DB 복제 지연 장애 긴급 대응</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">예상 시간</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">약 5시간</td></tr>\r\n</table>',NULL,'야근신청서',8,'강부장','개발1팀','승인',NULL,'2026-04-01','2026-04-02','2026-04-15 10:12:37','2026-04-15 10:43:40',0,NULL),(53,'TEST_S06','4월 거래처 미팅 교통비 청구','<table style=\"width:100%;border-collapse:collapse;margin:12px 0\">\r\n<tr style=\"background:#f9fafb\"><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left\">일자</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left\">내용</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">금액</th></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">04/03</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">강남 거래처 방문 택시비</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">23,400원</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">04/04</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">판교 파트너사 방문 택시비</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">31,200원</td></tr>\r\n<tr style=\"background:#f0f9ff;font-weight:600\"><td style=\"padding:8px 12px;border:1px solid #d1d5db\" colspan=\"2\">합계</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">54,600원</td></tr>\r\n</table>',NULL,'경비청구서',11,'서과장','국내영업팀','승인',NULL,'2026-04-05','2026-04-06','2026-04-15 10:12:37','2026-04-15 10:43:40',0,NULL),(54,'TEST_S07','판교 파트너사 기술 미팅 외근','<table style=\"width:100%;border-collapse:collapse\">\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600\">외근지</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">경기도 성남시 분당구 판교역로 235</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">일시</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">2026-04-14 (월) 14:00 ~ 17:00</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">목적</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">프론트엔드 기술 공유 미팅</td></tr>\r\n</table>',NULL,'외근신청서',15,'박대리','개발1팀','반려',NULL,'2026-04-11','2026-04-12','2026-04-15 10:12:37','2026-04-15 10:43:40',0,NULL),(55,'TEST_S08','사무실 리모델링 견적 품의','<p>노후화된 3층 회의실 및 휴게공간 리모델링을 아래와 같이 품의합니다.</p>\r\n<table style=\"width:100%;border-collapse:collapse;margin:12px 0\">\r\n<tr style=\"background:#f9fafb\"><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left\">공간</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left\">내용</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">견적</th></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">대회의실</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">바닥 교체, 방음 보강, AV 장비</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">18,500,000원</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">휴게공간</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">인테리어, 가구, 커피머신</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">12,000,000원</td></tr>\r\n<tr style=\"background:#f0f9ff;font-weight:600\"><td style=\"padding:8px 12px;border:1px solid #d1d5db\" colspan=\"2\">합계 (VAT 별도)</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">30,500,000원</td></tr>\r\n</table>',NULL,'품의서',13,'김대리','경영지원팀','반려',NULL,'2026-04-03','2026-04-05','2026-04-15 10:12:37','2026-04-15 10:43:40',0,NULL),(56,'TEST_S09','거래처 접대 법인카드 사용 보고','<table style=\"width:100%;border-collapse:collapse;margin:12px 0\">\r\n<tr style=\"background:#f9fafb\"><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left\">일시</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left\">사용처</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:left\">용도</th><th style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">금액</th></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db\">04/12</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">본가 강남점</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">(주)테크솔루션 김부장 식사</td><td style=\"padding:8px 12px;border:1px solid #d1d5db;text-align:right\">187,000원</td></tr>\r\n</table>\r\n<p>거래처 관계 유지를 위한 정기 식사 미팅입니다.</p>',NULL,'법인카드 지출',11,'서과장','국내영업팀','대기',NULL,'2026-04-14',NULL,'2026-04-15 10:12:37','2026-04-15 10:43:40',0,NULL),(57,'TEST_S10','여름 휴가 사용 계획 (7/20~7/25)','<table style=\"width:100%;border-collapse:collapse\">\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;width:120px;font-weight:600\">휴가 종류</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">연차</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">기간</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">2026-07-20 (월) ~ 2026-07-25 (금) / 5일</td></tr>\r\n<tr><td style=\"padding:8px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600\">사유</td><td style=\"padding:8px 12px;border:1px solid #d1d5db\">여름 가족 여행</td></tr>\r\n</table>',NULL,'휴가신청서',8,'강부장','개발1팀','임시저장',NULL,'2026-04-15',NULL,'2026-04-15 10:12:37','2026-04-15 10:43:40',0,NULL),(58,'Zaemit_개발1팀_품의서_20260415035659','참조 기능 테스트 문서','<p>참조 기능이 정상 동작하는지 테스트합니다.</p>',NULL,'품의서',8,'강부장','개발1팀','대기',NULL,'2026-04-15',NULL,'2026-04-15 10:56:59','2026-04-15 10:56:59',0,NULL),(59,'Zaemit_TEST_전결_20260415045330','전결테스트_T01','전결 로직 검증용',NULL,'품의서',1,'김대표','경영지원본부','승인',NULL,'2026-04-15','2026-04-15','2026-04-15 11:53:30','2026-04-15 11:55:17',0,NULL),(60,'Zaemit_TEST_반려_20260415045651','반려테스트_T02','반려 시나리오',NULL,'품의서',1,'김대표','경영지원본부','반려',NULL,'2026-04-15','2026-04-15','2026-04-15 11:56:51','2026-04-15 11:56:51',0,NULL),(61,'Zaemit_TEST_반려정리_1776222076','반려정리_T03','반려 시 나머지 대기 정리',NULL,'품의서',1,'김대표','경영지원본부','반려',NULL,'2026-04-15','2026-04-15','2026-04-15 12:01:16','2026-04-15 12:01:16',0,NULL);
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
  `description` text COLLATE utf8mb4_general_ci COMMENT '양식 용도 설명',
  `content_template` text COLLATE utf8mb4_general_ci COMMENT '양식 HTML 템플릿',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '사용유무',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `allowed_departments` json DEFAULT NULL COMMENT '허용 부서 ID 배열 (null=전체)',
  `allowed_positions` json DEFAULT NULL COMMENT '허용 직급 배열 (null=전체)',
  `retention_days` int DEFAULT NULL COMMENT '완료 문서 보존 일수 (null=무제한)',
  `created_by` int DEFAULT NULL COMMENT '작성자 employee_id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_forms`
--

LOCK TABLES `approval_forms` WRITE;
/*!40000 ALTER TABLE `approval_forms` DISABLE KEYS */;
INSERT INTO `approval_forms` VALUES (1,'법인카드 지출','법인카드 지출',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 법인카드 사용 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">사 용 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">카드번호</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">****-****-****-____</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">사용일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">가 맹 점</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">사용금액</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">사용계정</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 접대비&nbsp;&nbsp;&nbsp;□ 회의비&nbsp;&nbsp;&nbsp;□ 복리후생비&nbsp;&nbsp;&nbsp;□ 소모품비&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">참 석 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">사용목적</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">※ 영수증(매출전표)을 반드시 첨부해 주시기 바랍니다.</p>',1,'2025-09-23 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(2,'변경품의서','원가변경품의',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 원가 변경 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:140px;\">대상 품목/프로젝트</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">변경일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 8px 0;font-size:13px;font-weight:600;\">■ 변경 전/후 비교</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<thead>\n<tr style=\"background:#f3f4f6;\">\n<th style=\"border:1px solid #d1d5db;padding:8px;\">구&nbsp;&nbsp;&nbsp;&nbsp;분</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;\">변경 전</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;\">변경 후</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;\">차&nbsp;&nbsp;&nbsp;&nbsp;이</th>\n</tr>\n</thead>\n<tbody>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;background:#f9fafb;font-weight:600;\">금&nbsp;&nbsp;&nbsp;&nbsp;액</td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td></tr>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;background:#f9fafb;font-weight:600;\">수&nbsp;&nbsp;&nbsp;&nbsp;량</td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\"><br></td></tr>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;background:#f9fafb;font-weight:600;\">기&nbsp;&nbsp;&nbsp;&nbsp;타</td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 8px 0;font-size:13px;font-weight:600;\">■ 변경 사유 및 영향</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;vertical-align:top;\">변경사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">영 향 도</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 원가 변경을 품의하오니 재가하여 주시기 바랍니다.</p>',0,'2022-11-17 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(3,'지급품의서','비용지급품의',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 비용 지급 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">지급대상</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">지급항목</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">지급금액</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">₩&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원 (부가세 포함/별도)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">지급일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">지급방법</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 계좌이체&nbsp;&nbsp;&nbsp;□ 법인카드&nbsp;&nbsp;&nbsp;□ 현금&nbsp;&nbsp;&nbsp;□ 어음/수표</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">사용계정</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">지급사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">증빙서류</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 세금계산서&nbsp;&nbsp;&nbsp;□ 계산서&nbsp;&nbsp;&nbsp;□ 현금영수증&nbsp;&nbsp;&nbsp;□ 카드전표&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 비용 지급을 품의하오니 재가하여 주시기 바랍니다.</p>',0,'2022-11-17 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(4,'발의품의서','발의품의서',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 발의 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">제&nbsp;&nbsp;&nbsp;&nbsp;목</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">발 의 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">발의배경</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">제안내용</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">기대효과</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소요예산</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">추진일정</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">참고자료</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">위와 같이 발의하오니 검토 후 재가하여 주시기 바랍니다.</p>',0,'2022-11-17 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(5,'기안취소','기안취소',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 기안 취소 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:140px;\">원기안 문서번호</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">원기안 제목</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">원기안 일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">원기안 기안자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">진행상태</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 결재 진행중&nbsp;&nbsp;&nbsp;□ 결재 완료&nbsp;&nbsp;&nbsp;□ 시행 전</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">취소사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기 기안에 대해 아래의 사유로 취소하고자 하오니 재가하여 주시기 바랍니다.</p>',0,'2022-11-17 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(6,'휴일근무','휴일근무',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 휴일근무 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">근 무 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">근무일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (&nbsp;&nbsp;요일)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">근무시간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 ~&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 (총&nbsp;&nbsp;&nbsp;&nbsp;시간)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">근무구분</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 토요일&nbsp;&nbsp;&nbsp;□ 일요일&nbsp;&nbsp;&nbsp;□ 법정공휴일&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">근무사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">업무내용</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">보&nbsp;&nbsp;&nbsp;&nbsp;상</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 대체휴무&nbsp;&nbsp;&nbsp;□ 휴일수당&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 휴일근무를 신청하오니 승인하여 주시기 바랍니다.</p>',1,'2022-11-15 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(7,'휴가신청서','휴가신청서',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 휴가 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">신 청 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">휴가종류</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 연차&nbsp;&nbsp;&nbsp;□ 반차(오전/오후)&nbsp;&nbsp;&nbsp;□ 병가&nbsp;&nbsp;&nbsp;□ 경조사&nbsp;&nbsp;&nbsp;□ 기타(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">휴가기간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 ~&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (총&nbsp;&nbsp;&nbsp;&nbsp;일)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">비상연락처</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">휴가사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">업무인수자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 휴가를 신청하오니 승인하여 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(8,'출장신청서','출장신청서',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 출장 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">출 장 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">출 장 지</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">출장기간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 ~&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일 (&nbsp;&nbsp;박&nbsp;&nbsp;일)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">동 행 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">출장목적</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">세부업무</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">교 통 편</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 항공&nbsp;&nbsp;&nbsp;□ KTX/기차&nbsp;&nbsp;&nbsp;□ 버스&nbsp;&nbsp;&nbsp;□ 자차&nbsp;&nbsp;&nbsp;□ 기타</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">예상경비</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원 (교통비:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;, 숙박비:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;, 식대:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;, 기타:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 출장을 신청하오니 승인하여 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(9,'외근신청서','외근신청서',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 외근 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">외 근 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">외근일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">외근시간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">출발&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 ~ 복귀&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">방 문 처</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">외근목적</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">연 락 처</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">복귀여부</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 사무실 복귀&nbsp;&nbsp;&nbsp;□ 직접 퇴근</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 외근을 신청하오니 승인하여 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(10,'야근신청서','야근신청서',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 야근 신청 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">신 청 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">야근일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">야근시간</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 ~&nbsp;&nbsp;&nbsp;&nbsp;시&nbsp;&nbsp;&nbsp;&nbsp;분 (총&nbsp;&nbsp;&nbsp;&nbsp;시간)</td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">야근사유</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">업무내용</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">식대/교통비</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">□ 신청&nbsp;&nbsp;&nbsp;□ 미신청&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;금액:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;원</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">상기와 같이 야근을 신청하오니 승인하여 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(11,'품의서','품의서',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 품의 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">제&nbsp;&nbsp;&nbsp;&nbsp;목</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">목&nbsp;&nbsp;&nbsp;&nbsp;적</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">시행일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소요예산</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;vertical-align:top;\">세부내용</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br><br><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">참고사항</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">위와 같이 품의하오니 재가하여 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL),(12,'경비청구서','경비청구서',NULL,'<p style=\"margin:0 0 12px 0;font-size:14px;font-weight:600;\">■ 경비 청구 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">청 구 자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">소속부서</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">청구일자</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\">&nbsp;&nbsp;&nbsp;&nbsp;년&nbsp;&nbsp;&nbsp;&nbsp;월&nbsp;&nbsp;&nbsp;&nbsp;일</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 8px 0;font-size:13px;font-weight:600;\">■ 사용 내역</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<thead>\n<tr style=\"background:#f3f4f6;\">\n<th style=\"border:1px solid #d1d5db;padding:8px;width:100px;\">사용일자</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;\">사용처</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;\">내역</th>\n<th style=\"border:1px solid #d1d5db;padding:8px;width:120px;\">금액</th>\n</tr>\n</thead>\n<tbody>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td></tr>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td></tr>\n<tr><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;\"><br></td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;\">원</td></tr>\n<tr><td colspan=\"3\" style=\"border:1px solid #d1d5db;padding:8px;text-align:right;background:#f9fafb;font-weight:600;\">합&nbsp;&nbsp;&nbsp;&nbsp;계</td><td style=\"border:1px solid #d1d5db;padding:8px;text-align:right;background:#f9fafb;font-weight:600;\">원</td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 8px 0;font-size:13px;font-weight:600;\">■ 입금 계좌</p>\n<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">\n<tbody>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;width:130px;\">은&nbsp;&nbsp;&nbsp;&nbsp;행</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">계좌번호</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n<tr><th style=\"background:#f3f4f6;border:1px solid #d1d5db;padding:10px 12px;text-align:left;\">예 금 주</th><td style=\"border:1px solid #d1d5db;padding:10px 12px;\"><br></td></tr>\n</tbody>\n</table>\n<p style=\"margin:16px 0 0 0;\">※ 증빙서류(영수증 등)를 반드시 첨부해 주시기 바랍니다.</p>',1,'2022-07-22 00:00:00','2026-04-06 06:03:12',NULL,NULL,NULL,NULL);
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
  `approver_id` int DEFAULT NULL,
  `delegate_id` int DEFAULT NULL COMMENT '대결자 employees.id',
  `delegate_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '대결자 이름',
  `approver_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '결재자',
  `approver_dept` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '결재자 부서',
  `approver_rank` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '결재자 직급 스냅샷',
  `role` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '결재',
  `step_order` int NOT NULL DEFAULT '0' COMMENT '결재 순서',
  `action` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '대기' COMMENT '처리 (대기/승인/반려/협의)',
  `comment` text COLLATE utf8mb4_general_ci COMMENT '의견',
  `action_date` datetime DEFAULT NULL COMMENT '처리일',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  KEY `idx_approver_id` (`approver_id`),
  CONSTRAINT `approval_history_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `approval_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_history`
--

LOCK TABLES `approval_history` WRITE;
/*!40000 ALTER TABLE `approval_history` DISABLE KEYS */;
INSERT INTO `approval_history` VALUES (63,38,8,NULL,NULL,'강부장','개발1팀',NULL,'결재',1,'대기',NULL,NULL,'2026-03-27 16:24:11'),(64,38,3,NULL,NULL,'박이사','기술개발본부',NULL,'결재',2,'대기',NULL,NULL,'2026-03-27 16:24:11'),(65,39,11,NULL,NULL,'서과장','국내영업팀',NULL,'결재',1,'대기',NULL,NULL,'2026-03-27 16:24:11'),(66,39,4,NULL,NULL,'최이사','영업본부',NULL,'결재',2,'대기',NULL,NULL,'2026-03-27 16:24:11'),(67,40,3,NULL,NULL,'박이사','기술개발본부',NULL,'결재',1,'승인','확인했습니다.','2026-03-13 10:00:00','2026-03-27 16:24:11'),(68,40,1,NULL,NULL,'김대표','(주)재밋',NULL,'결재',2,'승인','ok','2026-04-15 09:43:50','2026-03-27 16:24:11'),(69,41,5,NULL,NULL,'정부장','경영지원팀',NULL,'결재',1,'승인','승인합니다.','2026-03-11 14:00:00','2026-03-27 16:24:11'),(70,41,7,NULL,NULL,'오부장','재무회계팀',NULL,'결재',2,'대기',NULL,NULL,'2026-03-27 16:24:11'),(71,41,2,NULL,NULL,'이이사','경영지원본부',NULL,'결재',3,'대기',NULL,NULL,'2026-03-27 16:24:11'),(72,42,9,NULL,NULL,'윤과장','개발2팀',NULL,'결재',1,'승인','확인.','2026-03-05 15:00:00','2026-03-27 16:24:11'),(73,42,3,NULL,NULL,'박이사','기술개발본부',NULL,'결재',2,'승인','승인합니다.','2026-03-06 09:00:00','2026-03-27 16:24:11'),(74,42,1,NULL,NULL,'김대표','(주)재밋',NULL,'결재',3,'반려','예산 초과','2026-04-15 09:45:02','2026-03-27 16:24:11'),(75,43,6,NULL,NULL,'한부장','인사팀',NULL,'결재',1,'승인','교육 예산 승인합니다.','2026-03-16 11:00:00','2026-03-27 16:24:11'),(76,43,2,NULL,NULL,'이이사','경영지원본부',NULL,'결재',2,'대기',NULL,NULL,'2026-03-27 16:24:11'),(77,44,9,NULL,NULL,'윤과장','개발2팀',NULL,'결재',1,'승인','긴급 건이니 승인합니다.','2026-02-24 16:00:00','2026-03-27 16:24:11'),(78,44,3,NULL,NULL,'박이사','기술개발본부',NULL,'결재',2,'승인','고생 많습니다. 승인.','2026-02-25 09:30:00','2026-03-27 16:24:11'),(79,45,4,NULL,NULL,'최이사','영업본부',NULL,'결재',1,'승인','확인했습니다.','2026-03-02 10:00:00','2026-03-27 16:24:11'),(80,45,7,NULL,NULL,'오부장','재무회계팀',NULL,'결재',2,'반려','증빙서류 부족합니다.','2026-03-03 14:00:00','2026-03-27 16:24:11'),(81,46,NULL,NULL,NULL,'이정민','',NULL,'결재',1,'대기',NULL,NULL,'2026-04-06 06:06:05'),(82,46,NULL,NULL,NULL,'최민호','',NULL,'결재',2,'대기',NULL,NULL,'2026-04-06 06:06:05'),(83,47,NULL,NULL,NULL,'이정민','',NULL,'결재',1,'대기',NULL,NULL,'2026-04-06 06:10:47'),(84,47,NULL,NULL,NULL,'최민호','',NULL,'결재',2,'대기',NULL,NULL,'2026-04-06 06:10:47'),(85,48,3,NULL,NULL,'박이사','기술개발본부',NULL,'결재',1,'대기',NULL,NULL,'2026-04-15 10:12:37'),(86,48,1,NULL,NULL,'김대표','(주)재밋',NULL,'결재',2,'대기',NULL,NULL,'2026-04-15 10:12:37'),(87,49,8,NULL,NULL,'강부장','개발1팀',NULL,'결재',1,'대기',NULL,NULL,'2026-04-15 10:12:37'),(88,49,3,NULL,NULL,'박이사','기술개발본부',NULL,'결재',2,'대기',NULL,NULL,'2026-04-15 10:12:37'),(89,50,9,NULL,NULL,'윤과장','개발2팀',NULL,'결재',1,'승인','확인했습니다.','2026-04-09 10:00:00','2026-04-15 10:12:37'),(90,50,3,NULL,NULL,'박이사','기술개발본부',NULL,'결재',2,'대기',NULL,NULL,'2026-04-15 10:12:37'),(91,50,1,NULL,NULL,'김대표','(주)재밋',NULL,'결재',3,'대기',NULL,NULL,'2026-04-15 10:12:37'),(92,51,13,NULL,NULL,'김대리','경영지원팀',NULL,'결재',1,'승인','','2026-04-08 10:00:00','2026-04-15 10:12:37'),(93,51,1,NULL,NULL,'김대표','(주)재밋',NULL,'결재',2,'대기',NULL,NULL,'2026-04-15 10:12:37'),(94,52,3,NULL,NULL,'박이사','기술개발본부',NULL,'결재',1,'승인','수고하세요.','2026-04-01 10:00:00','2026-04-15 10:12:37'),(95,52,1,NULL,NULL,'김대표','(주)재밋',NULL,'결재',2,'승인','','2026-04-02 10:00:00','2026-04-15 10:12:37'),(96,53,20,NULL,NULL,'배대리','국내영업팀',NULL,'결재',1,'승인','확인.','2026-04-06 10:00:00','2026-04-15 10:12:37'),(97,54,8,NULL,NULL,'강부장','개발1팀',NULL,'결재',1,'반려','해당 일자에 스프린트 데모가 있어 일정 조정이 필요합니다.','2026-04-12 10:00:00','2026-04-15 10:12:37'),(98,54,3,NULL,NULL,'박이사','기술개발본부',NULL,'결재',2,'대기',NULL,NULL,'2026-04-15 10:12:37'),(99,55,14,NULL,NULL,'이대리','인사팀',NULL,'결재',1,'승인','필요성 공감합니다.','2026-04-04 10:00:00','2026-04-15 10:12:37'),(100,55,1,NULL,NULL,'김대표','(주)재밋',NULL,'결재',2,'반려','현재 분기 예산 여유가 부족합니다. 3분기 예산 편성 시 재검토 부탁드립니다.','2026-04-05 10:00:00','2026-04-15 10:12:37'),(101,56,20,NULL,NULL,'배대리','국내영업팀',NULL,'결재',1,'대기',NULL,NULL,'2026-04-15 10:12:37'),(102,58,3,NULL,NULL,'박이사','기술개발본부',NULL,'결재',1,'대기',NULL,NULL,'2026-04-15 10:56:59'),(103,58,1,NULL,NULL,'김대표','(주)재밋',NULL,'결재',2,'대기',NULL,NULL,'2026-04-15 10:56:59'),(104,59,9,NULL,NULL,'윤과장','기술개발본부',NULL,'결재',1,'승인','1차 결재 승인','2026-04-15 11:54:39','2026-04-15 11:53:30'),(105,59,3,NULL,NULL,'박이사','기술개발본부',NULL,'전결',2,'승인','전결 승인','2026-04-15 11:55:17','2026-04-15 11:53:30'),(106,59,1,NULL,NULL,'김대표','경영지원본부',NULL,'결재',3,'건너뜀','전결에 의한 자동 처리','2026-04-15 11:55:17','2026-04-15 11:53:30'),(107,60,9,NULL,NULL,'윤과장','기술개발본부',NULL,'결재',1,'반려','불가','2026-04-15 11:56:51','2026-04-15 11:56:51'),(108,60,3,NULL,NULL,'박이사','기술개발본부',NULL,'전결',2,'대기',NULL,NULL,'2026-04-15 11:56:51'),(109,61,9,NULL,NULL,'윤과장','기술개발본부',NULL,'결재',1,'반려','불가 사유','2026-04-15 12:01:16','2026-04-15 12:01:16'),(110,61,3,NULL,NULL,'박이사','기술개발본부',NULL,'전결',2,'건너뜀','반려에 의한 자동 처리','2026-04-15 12:01:16','2026-04-15 12:01:16'),(111,61,1,NULL,NULL,'김대표','경영지원본부',NULL,'결재',3,'건너뜀','반려에 의한 자동 처리','2026-04-15 12:01:16','2026-04-15 12:01:16');
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
  `created_by` int DEFAULT NULL COMMENT '생성자 employee_id (NULL=전사 공통)',
  `scope` enum('global','personal') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'global' COMMENT '전사/개인 구분',
  `line_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT '결재선 정보 (결재자 목록 JSON)',
  `amount_threshold` int NOT NULL DEFAULT '0' COMMENT '이 금액(원) 이상일 때 이 결재선 적용. 0=기본(금액 무관)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_approval_lines_user` (`created_by`,`scope`),
  CONSTRAINT `approval_lines_chk_1` CHECK (json_valid(`line_data`))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_lines`
--

LOCK TABLES `approval_lines` WRITE;
/*!40000 ALTER TABLE `approval_lines` DISABLE KEYS */;
INSERT INTO `approval_lines` VALUES (1,'개발팀 기본결재선','Zaemit 개발','품의서',NULL,'global','[{\"name\":\"이정민\",\"role\":\"팀장\",\"action\":\"승인\"},{\"name\":\"최민호\",\"role\":\"대표이사\",\"action\":\"승인\"}]',0,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(2,'경영지원 결재선','위즈웨어 경영지원','품의서',NULL,'global','[{\"name\":\"박지현\",\"role\":\"실장\",\"action\":\"승인\"},{\"name\":\"최민호\",\"role\":\"대표이사\",\"action\":\"승인\"}]',0,'2026-03-22 15:18:32','2026-03-22 15:18:32');
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
  `ref_id` int DEFAULT NULL,
  `ref_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '참조자',
  `ref_dept` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '참조자 부서',
  `read_at` datetime DEFAULT NULL COMMENT '열람일',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  KEY `idx_ref_id` (`ref_id`),
  CONSTRAINT `approval_references_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `approval_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_references`
--

LOCK TABLES `approval_references` WRITE;
/*!40000 ALTER TABLE `approval_references` DISABLE KEYS */;
INSERT INTO `approval_references` VALUES (4,58,9,'윤과장','개발2팀',NULL,'2026-04-15 10:56:59'),(5,59,10,'조대리','기술개발본부',NULL,'2026-04-15 11:53:30');
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
  `employee_id` int NOT NULL,
  `record_date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `work_plan` text COLLATE utf8mb4_general_ci COMMENT '출근 시 작성한 오늘의 업무 계획',
  `clock_out` time DEFAULT NULL,
  `leave_note` text COLLATE utf8mb4_general_ci COMMENT '퇴근 시 작성한 특이사항 메모',
  `work_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'NRM',
  `note` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_date` (`employee_id`,`record_date`),
  CONSTRAINT `attendance_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_records`
--

LOCK TABLES `attendance_records` WRITE;
/*!40000 ALTER TABLE `attendance_records` DISABLE KEYS */;
INSERT INTO `attendance_records` VALUES (1,1,'2026-03-30','08:55:00',NULL,'18:10:00',NULL,'NRM',NULL,'2026-04-03 00:29:18','2026-04-03 00:29:18'),(2,1,'2026-03-31','09:02:00',NULL,'18:25:00',NULL,'NRM',NULL,'2026-04-03 00:29:18','2026-04-03 00:29:18'),(3,1,'2026-04-01','08:48:00',NULL,'18:05:00',NULL,'NRM',NULL,'2026-04-03 00:29:18','2026-04-03 00:29:18'),(4,1,'2026-04-02','09:10:00',NULL,'18:30:00',NULL,'NRM',NULL,'2026-04-03 00:29:18','2026-04-03 00:29:18'),(6,1,'2026-04-03','05:44:02',NULL,'05:44:03',NULL,'NRM',NULL,'2026-04-03 05:44:02','2026-04-03 05:44:03'),(7,1,'2026-04-05','22:49:53',NULL,'22:50:04',NULL,'NRM',NULL,'2026-04-05 22:49:53','2026-04-05 22:50:04'),(8,1,'2026-04-07','15:06:19',NULL,'15:06:20',NULL,'NRM',NULL,'2026-04-07 15:06:19','2026-04-07 15:06:20'),(9,1,'2026-04-08','10:36:46',NULL,'10:36:47',NULL,'NRM',NULL,'2026-04-08 10:36:46','2026-04-08 10:36:47'),(10,1,'2026-04-09','17:47:47',NULL,'17:47:48',NULL,'NRM',NULL,'2026-04-09 17:47:47','2026-04-09 17:47:48'),(11,1,'2026-04-10','08:59:21',NULL,'17:21:18',NULL,'NRM',NULL,'2026-04-10 08:59:21','2026-04-10 17:21:18'),(12,1,'2026-04-14','17:27:11',NULL,'17:27:11',NULL,'NRM',NULL,'2026-04-14 17:27:11','2026-04-14 17:27:11'),(13,1,'2026-04-15','13:44:00',NULL,'13:44:03',NULL,'NRM',NULL,'2026-04-15 13:44:00','2026-04-15 13:44:03'),(14,1,'2026-04-18','21:19:31',NULL,'21:19:31',NULL,'NRM',NULL,'2026-04-18 21:19:31','2026-04-18 21:19:31'),(15,1,'2026-06-25','18:45:06',NULL,'18:45:07',NULL,'NRM',NULL,'2026-06-25 18:45:06','2026-06-25 18:45:07'),(16,1,'2026-07-03','02:29:32',NULL,NULL,NULL,'NRM',NULL,'2026-07-03 02:29:32','2026-07-03 02:29:32');
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
  `bank_code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '은행코드 (KB, NH 등 · Bank API용)',
  `bank_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '은행명',
  `account_no` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT '계좌번호',
  `account_alias` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '계좌 별칭',
  `account_type` enum('운영','급여','세금','예비','기타') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '운영' COMMENT '계좌 용도',
  `owner_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '예금주',
  `account_password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '계좌 비밀번호 (AES-256-GCM)',
  `memo` text COLLATE utf8mb4_general_ci COMMENT '관리 메모/비고',
  `consent_agreed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '자동수집 동의 여부',
  `consent_agreed_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '표시 순서',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='등록 계좌 정보';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
INSERT INTO `bank_accounts` VALUES (1,1,'KB','국민은행','123-456-789012','운영계좌','운영','주식회사 재밋',NULL,NULL,1,'2026-01-15 10:00:00',1,0,'2026-03-22 15:18:32'),(2,1,'SHINHAN','신한은행','234-567-890123','급여계좌','운영','주식회사 재밋',NULL,NULL,1,'2026-01-15 10:00:00',1,0,'2026-03-22 15:18:32'),(3,1,'IBK','기업은행','345-678-901234','세금납부계좌','운영','주식회사 재밋',NULL,NULL,0,NULL,1,0,'2026-03-22 15:18:32');
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
  `description` varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT '거래 적요',
  `amount` bigint NOT NULL COMMENT '금액 (양수)',
  `tx_type` enum('입금','출금') COLLATE utf8mb4_general_ci NOT NULL,
  `balance` bigint DEFAULT NULL COMMENT '거래 후 잔액',
  `account_code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '분류된 계정과목 코드',
  `account_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '분류된 계정과목명',
  `ai_confidence` tinyint unsigned DEFAULT NULL COMMENT 'AI 신뢰도 0-100',
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '사용자 확정 여부',
  `memo` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `bank_transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='통장 입출금 내역';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_transactions`
--

LOCK TABLES `bank_transactions` WRITE;
/*!40000 ALTER TABLE `bank_transactions` DISABLE KEYS */;
INSERT INTO `bank_transactions` VALUES (1,1,'2025-11-03','용역대금 입금 (주)테크솔루션',15000000,'입금',65000000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(2,1,'2025-11-05','사무실 월세',3500000,'출금',61500000,'810','임차료',NULL,0,NULL,'2026-04-10 11:07:20'),(3,2,'2025-11-10','11월 급여 이체',18500000,'출금',12000000,'811','급여',NULL,0,NULL,'2026-04-10 11:07:20'),(4,1,'2025-11-15','프로젝트 중도금 (주)디지털웨이브',22000000,'입금',83500000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(5,3,'2025-11-25','부가세 납부',4800000,'출금',8200000,'255','부가세예수금',NULL,0,NULL,'2026-04-10 11:07:20'),(6,1,'2025-11-28','법인카드 대금 결제',2800000,'출금',80700000,'253','미지급금',NULL,0,NULL,'2026-04-10 11:07:20'),(7,1,'2025-12-02','유지보수 월정액 (주)스마트시스',8000000,'입금',88700000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(8,1,'2025-12-05','사무실 월세',3500000,'출금',85200000,'810','임차료',NULL,0,NULL,'2026-04-10 11:07:20'),(9,2,'2025-12-10','12월 급여 이체',18500000,'출금',13500000,'811','급여',NULL,0,NULL,'2026-04-10 11:07:20'),(10,1,'2025-12-12','프로젝트 잔금 (주)테크솔루션',15000000,'입금',100200000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(11,3,'2025-12-20','원천세 납부',3200000,'출금',5000000,'254','원천징수예수금',NULL,0,NULL,'2026-04-10 11:07:20'),(12,1,'2025-12-27','법인카드 대금 결제',3100000,'출금',97100000,'253','미지급금',NULL,0,NULL,'2026-04-10 11:07:20'),(13,1,'2025-12-30','연말 성과급 입금 (본사)',5000000,'입금',102100000,'999','기타수입',NULL,0,NULL,'2026-04-10 11:07:20'),(14,1,'2026-01-03','SI 프로젝트 착수금 (주)넥스트컴',30000000,'입금',132100000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(15,1,'2026-01-05','사무실 월세',3500000,'출금',128600000,'810','임차료',NULL,0,NULL,'2026-04-10 11:07:20'),(16,2,'2026-01-10','1월 급여 이체',19200000,'출금',12800000,'811','급여',NULL,0,NULL,'2026-04-10 11:07:20'),(17,1,'2026-01-20','유지보수 월정액 (주)스마트시스',8000000,'입금',136600000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(18,3,'2026-01-25','부가세 납부',5100000,'출금',7900000,'255','부가세예수금',NULL,0,NULL,'2026-04-10 11:07:20'),(19,1,'2026-01-28','법인카드 대금 결제',2500000,'출금',134100000,'253','미지급금',NULL,0,NULL,'2026-04-10 11:07:20'),(20,1,'2026-02-03','프로젝트 중도금 (주)넥스트컴',20000000,'입금',154100000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(21,1,'2026-02-05','사무실 월세',3500000,'출금',150600000,'810','임차료',NULL,0,NULL,'2026-04-10 11:07:20'),(22,2,'2026-02-10','2월 급여 이체',19200000,'출금',13600000,'811','급여',NULL,0,NULL,'2026-04-10 11:07:20'),(23,1,'2026-02-18','컨설팅비 입금 (주)그로우업',12000000,'입금',162600000,'40200','제품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(24,3,'2026-02-25','원천세 납부',3400000,'출금',4500000,'254','원천징수예수금',NULL,0,NULL,'2026-04-10 11:07:20'),(25,1,'2026-02-27','법인카드 대금 결제',1900000,'출금',160700000,'253','미지급금',NULL,0,NULL,'2026-04-10 11:07:20'),(26,1,'2026-03-02','유지보수 월정액 (주)스마트시스',8000000,'입금',168700000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(27,1,'2026-03-05','사무실 월세',3500000,'출금',165200000,'810','임차료',NULL,0,NULL,'2026-04-10 11:07:20'),(28,1,'2026-03-08','프로젝트 잔금 (주)넥스트컴',20000000,'입금',185200000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(29,2,'2026-03-10','3월 급여 이체',19200000,'출금',14400000,'811','급여',NULL,0,NULL,'2026-04-10 11:07:20'),(30,1,'2026-03-15','신규계약 착수금 (주)블루오션',25000000,'입금',210200000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(31,3,'2026-03-25','부가세 납부',5500000,'출금',7000000,'255','부가세예수금',NULL,0,NULL,'2026-04-10 11:07:20'),(32,1,'2026-03-28','법인카드 대금 결제',3200000,'출금',207000000,'253','미지급금',NULL,0,NULL,'2026-04-10 11:07:20'),(33,1,'2026-04-01','유지보수 월정액 (주)스마트시스',8000000,'입금',215000000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(34,1,'2026-04-03','프로젝트 중도금 (주)블루오션',15000000,'입금',230000000,'40100','상품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(35,1,'2026-04-05','사무실 월세',3500000,'출금',226500000,'810','임차료',NULL,0,NULL,'2026-04-10 11:07:20'),(36,1,'2026-04-07','사무용 가구 구입',2800000,'출금',223700000,'812','비품',NULL,0,NULL,'2026-04-10 11:07:20'),(37,2,'2026-04-10','4월 급여 이체',19200000,'출금',15200000,'811','급여',NULL,0,NULL,'2026-04-10 11:07:20'),(38,1,'2026-04-08','컨설팅비 입금 (주)이노텍',9500000,'입금',233200000,'40200','제품매출',NULL,0,NULL,'2026-04-10 11:07:20'),(39,1,'2026-05-05','테크솔루션 세금계산서 입금',16500000,'입금',NULL,'40200','제품매출',92,1,NULL,'2026-07-03 02:43:01'),(40,1,'2026-05-07','5월 급여 이체',48000000,'출금',NULL,NULL,NULL,NULL,0,NULL,'2026-07-03 02:43:01'),(41,1,'2026-05-10','법인카드 대금 결제',3850000,'출금',NULL,'82700','차량유지비',92,1,NULL,'2026-07-03 02:43:01'),(42,1,'2026-05-15','사무실 임대료',2200000,'출금',NULL,NULL,NULL,NULL,1,NULL,'2026-07-03 02:43:01'),(43,1,'2026-05-20','4대보험 납부',3120000,'출금',NULL,NULL,NULL,NULL,0,NULL,'2026-07-03 02:43:01'),(44,1,'2026-05-25','스마트커머스 용역대금 입금',9350000,'입금',NULL,'40200','제품매출',92,1,NULL,'2026-07-03 02:43:01'),(45,1,'2026-05-28','예금 이자',42500,'입금',NULL,'G_NI','영업외수익',92,0,NULL,'2026-07-03 02:43:01'),(46,1,'2026-06-05','테크솔루션 세금계산서 입금',16500000,'입금',NULL,'40200','제품매출',92,1,NULL,'2026-07-03 02:43:01'),(47,1,'2026-06-07','5월 급여 이체',48000000,'출금',NULL,NULL,NULL,NULL,0,NULL,'2026-07-03 02:43:01'),(48,1,'2026-06-10','법인카드 대금 결제',3850000,'출금',NULL,'82700','차량유지비',92,1,NULL,'2026-07-03 02:43:01'),(49,1,'2026-06-15','사무실 임대료',2200000,'출금',NULL,NULL,NULL,NULL,1,NULL,'2026-07-03 02:43:01'),(50,1,'2026-06-20','4대보험 납부',3120000,'출금',NULL,NULL,NULL,NULL,0,NULL,'2026-07-03 02:43:01'),(51,1,'2026-06-25','스마트커머스 용역대금 입금',9350000,'입금',NULL,'40200','제품매출',92,1,NULL,'2026-07-03 02:43:01'),(52,1,'2026-06-28','예금 이자',42500,'입금',NULL,'G_NI','영업외수익',92,0,NULL,'2026-07-03 02:43:01'),(53,1,'2026-07-05','테크솔루션 세금계산서 입금',16500000,'입금',NULL,'40200','제품매출',92,1,NULL,'2026-07-03 02:43:01'),(54,1,'2026-07-07','5월 급여 이체',48000000,'출금',NULL,NULL,NULL,NULL,0,NULL,'2026-07-03 02:43:01'),(55,1,'2026-07-10','법인카드 대금 결제',3850000,'출금',NULL,'82700','차량유지비',92,1,NULL,'2026-07-03 02:43:01'),(56,1,'2026-07-15','사무실 임대료',2200000,'출금',NULL,NULL,NULL,NULL,1,NULL,'2026-07-03 02:43:01'),(57,1,'2026-07-20','4대보험 납부',3120000,'출금',NULL,NULL,NULL,NULL,0,NULL,'2026-07-03 02:43:01'),(58,1,'2026-07-25','스마트커머스 용역대금 입금',9350000,'입금',NULL,'40200','제품매출',92,1,NULL,'2026-07-03 02:43:01'),(59,1,'2026-07-28','예금 이자',42500,'입금',NULL,'G_NI','영업외수익',92,0,NULL,'2026-07-03 02:43:01');
/*!40000 ALTER TABLE `bank_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `board_attachments`
--

DROP TABLE IF EXISTS `board_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int unsigned DEFAULT '0',
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`),
  CONSTRAINT `board_attachments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `board_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `board_attachments`
--

LOCK TABLES `board_attachments` WRITE;
/*!40000 ALTER TABLE `board_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `board_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `board_comments`
--

DROP TABLE IF EXISTS `board_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `author_id` int NOT NULL,
  `author_name` varchar(50) NOT NULL,
  `author_dept` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('active','deleted') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`,`status`,`created_at`),
  CONSTRAINT `board_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `board_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `board_comments`
--

LOCK TABLES `board_comments` WRITE;
/*!40000 ALTER TABLE `board_comments` DISABLE KEYS */;
INSERT INTO `board_comments` VALUES (1,1,2,'이이사','경영지원본부','확인했습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,1,3,'박이사','기술개발본부','참고하겠습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,3,4,'최이사','영업본부','도움이 많이 됐어요.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,5,6,'한부장','인사팀','수고하셨습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,5,7,'오부장','재무회계팀','잘 봤습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,5,8,'강부장','개발1팀','문의드릴 게 있어요.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(7,7,8,'강부장','개발1팀','문의드릴 게 있어요.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(8,7,9,'윤과장','개발2팀','좋은 정보 감사합니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(9,9,10,'임과장','QA팀','확인했습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(10,11,12,'류과장','해외영업팀','도움이 많이 됐어요.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(11,11,13,'김대리','경영지원팀','공유 감사합니다!','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(12,11,14,'이대리','인사팀','수고하셨습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(13,13,14,'이대리','인사팀','수고하셨습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(14,13,15,'박대리','개발1팀','잘 봤습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(15,15,16,'송사원','개발1팀','문의드릴 게 있어요.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(16,17,18,'황사원','개발2팀','확인했습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(17,17,19,'문대리','QA팀','참고하겠습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(18,17,20,'배대리','국내영업팀','도움이 많이 됐어요.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(19,19,20,'배대리','국내영업팀','도움이 많이 됐어요.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(20,19,21,'노대리','개발1팀','공유 감사합니다!','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(21,21,22,'심사원','인사팀','수고하셨습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(22,23,2,'이이사','경영지원본부','문의드릴 게 있어요.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(23,23,3,'박이사','기술개발본부','좋은 정보 감사합니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(24,23,4,'최이사','영업본부','확인했습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(25,25,4,'최이사','영업본부','확인했습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(26,25,5,'정부장','경영지원팀','참고하겠습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(27,27,6,'한부장','인사팀','도움이 많이 됐어요.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(28,29,8,'강부장','개발1팀','수고하셨습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(29,29,9,'윤과장','개발2팀','잘 봤습니다.','active','2026-07-03 02:58:24','2026-07-03 02:58:24'),(30,29,10,'임과장','QA팀','문의드릴 게 있어요.','active','2026-07-03 02:58:24','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `board_comments` ENABLE KEYS */;
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
  `department_id` int DEFAULT NULL COMMENT '부서게시판용 departments.id',
  `is_pinned` tinyint(1) DEFAULT '0',
  `views` int DEFAULT '0',
  `status` enum('active','deleted') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`board_type`,`status`),
  KEY `idx_type_pinned` (`board_type`,`is_pinned`),
  KEY `idx_created` (`created_at`),
  KEY `idx_board_dept` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `board_posts`
--

LOCK TABLES `board_posts` WRITE;
/*!40000 ALTER TABLE `board_posts` DISABLE KEYS */;
INSERT INTO `board_posts` VALUES (1,'notice','중요','2026년 상반기 인사발령 안내','2026년 상반기 인사발령 사항을 안내드립니다.\n\n1. 발령일자: 2026년 4월 1일\n2. 대상: 전 부서\n3. 상세 내용은 첨부 파일을 참조해 주세요.\n\n문의사항은 경영지원본부로 연락 바랍니다.',1,'관리자','경영지원본부',NULL,1,235,'active','2026-04-01 09:00:00','2026-04-06 10:40:06'),(2,'notice','공지','사내 보안 정책 변경 안내','사내 보안 정책이 아래와 같이 변경됩니다.\n\n1. 비밀번호 변경 주기: 60일 → 90일\n2. 2단계 인증 필수 적용\n3. 외부 USB 사용 제한\n\n적용일: 2026년 4월 15일부터',1,'관리자','경영지원본부',NULL,1,194,'active','2026-03-28 10:30:00','2026-04-07 15:25:15'),(3,'notice','공지','2026년 연간 휴무일 안내','2026년 연간 휴무일을 안내드립니다.\n\n- 설날: 2월 16일~18일\n- 어린이날: 5월 5일\n- 추석: 9월 24일~26일\n- 크리스마스: 12월 25일\n\n자세한 내용은 인사팀에 문의해 주세요.',1,'관리자','경영지원본부',NULL,0,157,'active','2026-03-20 14:00:00','2026-04-06 10:33:35'),(4,'notice','안내','사무용품 신청 절차 변경','사무용품 신청 절차가 변경되었습니다.\n\n기존: 이메일 신청 → 총무팀 처리\n변경: 그룹웨어 전자결재 → 자동 발주\n\n적용일: 2026년 4월 1일부터',1,'관리자','경영지원본부',NULL,0,102,'active','2026-03-15 11:00:00','2026-04-07 15:25:16'),(5,'notice','중요','전사 회의 일정 공지 (4월)','4월 전사 회의 일정을 공지합니다.\n\n- 4월 7일(월) 10:00 월간 경영회의\n- 4월 14일(월) 14:00 부서장 회의\n- 4월 21일(월) 10:00 전체 조회\n\n참석 대상자는 일정을 확인해 주세요.',1,'관리자','경영지원본부',NULL,0,145,'active','2026-03-10 09:30:00','2026-04-06 10:40:11'),(6,'notice','공지','신규 입사자 OJT 일정','4월 신규 입사자 OJT 일정을 안내합니다.\n\n- 기간: 4월 6일 ~ 4월 10일\n- 장소: 본사 3층 교육장\n- 대상: 4월 입사자 전원\n\n부서별 멘토 지정은 별도 안내 예정입니다.',1,'관리자','경영지원본부',NULL,0,89,'active','2026-03-05 13:00:00','2026-04-07 15:25:17'),(7,'notice','안내','사무실 이전 안내','본사 사무실 이전을 안내드립니다.\n\n- 이전일: 2026년 5월 중 (확정 시 별도 공지)\n- 신규 주소: 서울시 강남구 테헤란로 123\n\n이전 관련 문의는 총무팀으로 연락 바랍니다.',1,'관리자','경영지원본부',NULL,0,205,'active','2026-02-28 16:00:00','2026-04-06 10:42:49'),(8,'free','일반','점심 맛집 추천 받습니다','회사 근처 점심 맛집 추천 부탁드립니다!\n\n- 예산: 1만원 내외\n- 선호: 한식, 일식\n- 비선호: 매운 음식\n\n좋은 곳 있으면 댓글로 알려주세요~',2,'김영수','기술개발본부',NULL,0,67,'active','2026-04-02 12:30:00','2026-04-06 08:59:52'),(9,'free','건의','회의실 예약 시스템 개선 건의','현재 회의실 예약 시스템 관련 건의사항입니다.\n\n1. 반복 예약 기능 추가 요청\n2. 예약 취소 시 알림 기능\n3. 회의실 현황 대시보드\n\n검토 부탁드립니다.',3,'이지은','영업본부',NULL,0,47,'active','2026-03-30 15:00:00','2026-04-07 15:25:22'),(10,'free','일반','사내 동호회 모집 (축구/독서)','사내 동호회 회원을 모집합니다!\n\n축구 동호회:\n- 활동: 매주 토요일 오전\n- 장소: 근처 풋살장\n\n독서 동호회:\n- 활동: 격주 수요일 저녁\n- 장소: 사내 카페\n\n관심 있으신 분은 댓글 남겨주세요.',4,'박민수','기술개발본부',NULL,0,89,'active','2026-03-25 17:00:00','2026-04-06 08:59:52'),(11,'free','기타','분실물 안내 (검은색 우산)','3층 회의실에서 검은색 접이식 우산을 발견했습니다.\n\n- 발견 장소: 3층 319호 회의실\n- 발견 일시: 3월 19일 오후\n- 브랜드: 무인양품\n\n주인이시면 총무팀으로 연락주세요.',2,'김영수','기술개발본부',NULL,0,32,'active','2026-03-20 09:00:00','2026-04-06 08:59:52'),(12,'free','일반','카페테리아 메뉴 변경 안내','4월부터 카페테리아 메뉴가 변경됩니다.\n\n- 신메뉴: 샐러드바, 수제버거\n- 폐지: 라면 코너\n- 운영시간: 11:30 ~ 13:30 (동일)\n\n의견 있으시면 댓글로 남겨주세요.',1,'관리자','경영지원본부',NULL,0,54,'active','2026-03-15 10:00:00','2026-04-06 08:59:52'),(13,'free','건의','야근 시 석식 지원 요청','야근 시 석식 지원에 대한 건의입니다.\n\n현재: 야근 수당만 지급\n건의: 20시 이후 야근 시 석식비 1만원 지원\n\n타 회사 사례도 참고하면 좋겠습니다.',3,'이지은','영업본부',NULL,0,42,'active','2026-03-08 18:30:00','2026-04-07 15:25:22'),(14,'free','일반','주말 등산 모임 후기','지난 주말 관악산 등산 모임 후기입니다.\n\n- 참여: 8명\n- 코스: 관악산역 → 정상 → 사당역\n- 소요시간: 약 3시간\n\n다음 모임은 4월 중으로 계획 중입니다!',4,'박민수','기술개발본부',NULL,0,39,'active','2026-03-01 20:00:00','2026-04-07 15:25:23'),(15,'archive','양식','출장보고서 양식 (2026년 개정)','2026년 개정된 출장보고서 양식을 공유합니다.\n\n변경사항:\n- 출장 목적 세분화\n- 성과 기술란 추가\n- 경비 항목 상세화\n\n기존 양식은 4월부터 사용 불가합니다.',1,'관리자','경영지원본부',NULL,0,79,'active','2026-03-28 11:00:00','2026-04-08 18:02:08'),(16,'archive','매뉴얼','그룹웨어 사용 매뉴얼 v2.0','그룹웨어 사용 매뉴얼 v2.0을 배포합니다.\n\n주요 업데이트:\n- 전자결재 사용법\n- 일정관리 사용법\n- 게시판 사용법\n\n문의사항은 IT팀으로 연락 바랍니다.',2,'김영수','기술개발본부',NULL,0,112,'active','2026-03-20 09:00:00','2026-04-06 08:59:52'),(17,'archive','참고자료','2025년 4분기 실적 보고서','2025년 4분기 실적 보고서를 공유합니다.\n\n주요 지표:\n- 매출: 전분기 대비 12% 증가\n- 영업이익: 전년 동기 대비 8% 증가\n- 신규 고객: 23건\n\n상세 내용은 첨부 자료를 참조해 주세요.',1,'관리자','경영지원본부',NULL,0,95,'active','2026-03-10 14:00:00','2026-04-06 08:59:52'),(18,'archive','양식','휴가 신청서 양식','휴가 신청서 양식을 공유합니다.\n\n- 연차, 반차, 특별휴가 구분\n- 인수인계 사항 기재란 포함\n- 부서장 결재란 포함\n\n전자결재로도 신청 가능합니다.',1,'관리자','경영지원본부',NULL,0,134,'active','2026-02-25 10:00:00','2026-04-06 08:59:52'),(19,'archive','매뉴얼','화상회의 시스템 사용 가이드','화상회의 시스템 사용 가이드입니다.\n\n1. 접속 방법\n2. 화면 공유\n3. 녹화 기능\n4. 트러블슈팅\n\n자세한 내용은 문서를 참조해 주세요.',2,'김영수','기술개발본부',NULL,0,65,'active','2026-02-15 11:00:00','2026-04-06 08:59:52'),(20,'archive','참고자료','사내 교육 프로그램 안내','2026년 사내 교육 프로그램을 안내합니다.\n\n- 리더십 과정 (4~6월)\n- 직무역량 과정 (7~9월)\n- IT 역량 과정 (10~12월)\n\n신청은 인사팀에서 별도 안내 예정입니다.',1,'관리자','경영지원본부',NULL,0,48,'active','2026-02-01 09:00:00','2026-04-06 08:59:52'),(21,'department','','코드 리뷰 가이드라인 공유','코드 리뷰 가이드라인을 공유합니다.\n\n1. PR 크기: 300줄 이하 권장\n2. 리뷰 응답: 24시간 이내\n3. 필수 리뷰어: 2명 이상\n4. 테스트 커버리지: 80% 이상\n\n질문이나 의견은 댓글로 남겨주세요.',3,'이지은','기술개발본부',3,0,34,'active','2026-04-01 16:00:00','2026-07-03 01:26:27'),(22,'department','','4월 마케팅 캠페인 기획안','4월 마케팅 캠페인 기획안을 공유합니다.\n\n캠페인명: 봄맞이 신규 고객 프로모션\n기간: 4월 14일 ~ 4월 30일\n목표: 신규 가입 500건\n\n피드백 부탁드립니다.',4,'박민수','영업본부',4,0,28,'active','2026-03-28 14:00:00','2026-07-03 01:26:27'),(23,'department','','UI 디자인 시스템 컴포넌트 정리','UI 디자인 시스템 컴포넌트를 정리했습니다.\n\n- 버튼 (Primary/Secondary/Ghost)\n- 입력 필드 (Text/Select/Checkbox)\n- 카드 (Basic/Image/Stat)\n- 모달 (Alert/Confirm/Form)\n\nFigma 링크는 첨부를 참조해 주세요.',2,'김영수','기술개발본부',3,0,41,'active','2026-03-22 10:00:00','2026-07-03 01:26:27'),(24,'department','','API 문서 업데이트 (v3.1)','API 문서가 v3.1로 업데이트되었습니다.\n\n변경사항:\n- 인증 방식 변경 (Bearer Token)\n- 응답 포맷 통일\n- 에러 코드 표준화\n\n개발팀 전원 확인 부탁드립니다.',2,'김영수','기술개발본부',3,0,56,'active','2026-03-15 15:00:00','2026-07-03 01:26:27'),(25,'department','','고객 만족도 조사 결과','2026년 1분기 고객 만족도 조사 결과입니다.\n\n- 전체 만족도: 4.2/5.0\n- 서비스 품질: 4.5/5.0\n- 응대 속도: 3.8/5.0\n- 개선 요청: UI 개선, 모바일 지원\n\n상세 분석은 다음 주 보고 예정.',3,'이지은','영업본부',4,0,33,'active','2026-03-05 11:00:00','2026-07-03 01:26:27'),(26,'department','','브랜드 가이드라인 업데이트','브랜드 가이드라인이 업데이트되었습니다.\n\n변경사항:\n- 로고 사용 규정 변경\n- 서브 컬러 추가\n- 폰트 가이드 개정\n\n모든 디자인 작업 시 새 가이드라인을 적용해 주세요.',4,'박민수','기술개발본부',3,0,29,'active','2026-02-20 13:00:00','2026-07-03 01:26:27'),(27,'free','free','Updated title','Updated content',1,'admin','test',NULL,0,1,'deleted','2026-04-06 09:05:11','2026-04-06 09:05:20'),(28,'notice','test','test','test content',1,'admin','dept',NULL,0,0,'active','2026-04-06 10:13:03','2026-04-06 10:13:03'),(29,'notice','공지','안녕하세요','테스트입니다',NULL,'관리자',NULL,NULL,1,6,'active','2026-04-06 10:19:52','2026-04-07 15:25:12');
/*!40000 ALTER TABLE `board_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_color_overrides`
--

DROP TABLE IF EXISTS `calendar_color_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_color_overrides` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `calendar_key` varchar(50) NOT NULL COMMENT '오버레이 키: schedules, holidays, tax, insurance, labor, company',
  `color_code` varchar(20) NOT NULL COMMENT 'hex 또는 색상명',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_emp_cal` (`employee_id`,`calendar_key`),
  CONSTRAINT `calendar_color_overrides_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_color_overrides`
--

LOCK TABLES `calendar_color_overrides` WRITE;
/*!40000 ALTER TABLE `calendar_color_overrides` DISABLE KEYS */;
/*!40000 ALTER TABLE `calendar_color_overrides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `event_date` date NOT NULL,
  `end_date` date DEFAULT NULL COMMENT 'NULL=단일일, 기간 이벤트 시 종료일',
  `category` enum('tax','insurance','labor','company') COLLATE utf8mb4_general_ci NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '1' COMMENT '시드=1, 사용자등록=0',
  `is_deadline` tinyint(1) NOT NULL DEFAULT '0' COMMENT '마감일 여부 (강조 표시)',
  `source_ref` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '법적 근거',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`event_date`),
  KEY `idx_category` (`category`),
  KEY `idx_year_cat` (`event_date`,`category`)
) ENGINE=InnoDB AUTO_INCREMENT=235 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='시스템 캘린더 이벤트 (세무/보험/노무/회사)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_events`
--

LOCK TABLES `calendar_events` WRITE;
/*!40000 ALTER TABLE `calendar_events` DISABLE KEYS */;
INSERT INTO `calendar_events` VALUES (1,'원천세 신고·납부','2025년 12월분 원천징수세액 신고 및 납부','2026-01-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(2,'원천세 신고·납부','2026년 1월분 원천징수세액 신고 및 납부','2026-02-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(3,'원천세 신고·납부','2026년 2월분 원천징수세액 신고 및 납부','2026-03-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(4,'원천세 신고·납부','2026년 3월분 원천징수세액 신고 및 납부','2026-04-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(5,'원천세 신고·납부','2026년 4월분 원천징수세액 신고 및 납부','2026-05-11',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(6,'원천세 신고·납부','2026년 5월분 원천징수세액 신고 및 납부','2026-06-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(7,'원천세 신고·납부','2026년 6월분 원천징수세액 신고 및 납부','2026-07-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(8,'원천세 신고·납부','2026년 7월분 원천징수세액 신고 및 납부','2026-08-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(9,'원천세 신고·납부','2026년 8월분 원천징수세액 신고 및 납부','2026-09-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(10,'원천세 신고·납부','2026년 9월분 원천징수세액 신고 및 납부','2026-10-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(11,'원천세 신고·납부','2026년 10월분 원천징수세액 신고 및 납부','2026-11-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(12,'원천세 신고·납부','2026년 11월분 원천징수세액 신고 및 납부','2026-12-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(13,'부가세 2기 확정신고','2025년 하반기 부가가치세 확정신고·납부','2026-01-26',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(14,'부가세 1기 예정신고','2026년 상반기 부가가치세 예정신고·납부','2026-04-27',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(15,'부가세 1기 확정신고','2026년 상반기 부가가치세 확정신고·납부','2026-07-27',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(16,'부가세 2기 예정신고','2026년 하반기 부가가치세 예정신고·납부','2026-10-26',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(17,'법인세 신고·납부','2025 사업연도(12월 결산) 법인세 신고 기한','2026-03-31',NULL,'tax',1,1,'법인세법 §60',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(18,'지방소득세 신고(법인)','2025 사업연도 법인 지방소득세 신고·납부','2026-04-30',NULL,'tax',1,1,'지방세법 §103의23',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(19,'종합소득세 확정신고','2025년 귀속 종합소득세 확정신고·납부','2026-06-01',NULL,'tax',1,1,'소득세법 §70',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(20,'지방소득세 신고(종소)','2025년 귀속 개인 지방소득세 신고·납부','2026-06-01',NULL,'tax',1,1,'지방세법 §95',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(21,'성실신고확인 종소세','성실신고확인대상 종합소득세 신고 기한','2026-06-30',NULL,'tax',1,1,'소득세법 §70의2',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(22,'연말정산 간소화 오픈','국세청 연말정산 간소화 서비스 개통','2026-01-15',NULL,'tax',1,0,NULL,NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(23,'연말정산 서류 제출','근로소득 연말정산 서류 제출 기간','2026-01-20','2026-03-03','tax',1,0,'소득세법 §137',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(24,'연말정산 신고 기한','2025년 귀속 연말정산 완료(원천징수이행상황 신고)','2026-03-10',NULL,'tax',1,1,'소득세법 §137',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(25,'지급명세서 제출','전년도 지급명세서(근로/사업/기타소득) 제출 기한','2026-03-03',NULL,'tax',1,1,'소득세법 §164',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(26,'건강보험 보수총액 신고','전년도 건강보험·국민연금 보수총액 신고','2026-03-10',NULL,'insurance',1,1,'국민건강보험법 §70',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(27,'고용·산재보험 보수총액 신고','전년도 고용보험·산재보험 보수총액 신고','2026-03-16',NULL,'insurance',1,1,'고용보험법 §16의10',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(28,'고용·산재 개산보험료 신고','당해 연도 고용·산재보험 개산보험료 신고·납부','2026-03-31',NULL,'insurance',1,1,'고용보험 및 산재보험의 보험료징수 등에 관한 법률 §17',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(29,'건강보험 보수월액 변경','건강보험 보수월액 정기 변경 적용','2026-04-01',NULL,'insurance',1,0,'국민건강보험법 §70',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(30,'국민연금 기준소득월액 변경','국민연금 기준소득월액 정기 결정 적용','2026-07-01',NULL,'insurance',1,0,'국민연금법 §63',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(31,'고용·산재 확정보험료 신고','전년도 고용·산재보험 확정보험료 정산','2026-03-31',NULL,'insurance',1,1,'보험료징수법 §16의3',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(32,'최저임금 변경 적용','2026년 최저임금 적용 시작','2026-01-01',NULL,'labor',1,0,'최저임금법 §10',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(33,'근로자의 날','법정 유급휴일 (공휴일 아님, 5인 이상 사업장)','2026-05-01',NULL,'labor',1,0,'근로자의 날 제정에 관한 법률',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(34,'장애인 의무고용 현황 신고','장애인 고용 현황 및 부담금 신고','2026-01-31',NULL,'labor',1,1,'장애인고용촉진법 §33',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(35,'산업안전보건 교육(1분기)','정기 안전보건 교육 실시 (분기별)','2026-03-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(36,'산업안전보건 교육(2분기)','정기 안전보건 교육 실시 (분기별)','2026-06-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(37,'산업안전보건 교육(3분기)','정기 안전보건 교육 실시 (분기별)','2026-09-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(38,'산업안전보건 교육(4분기)','정기 안전보건 교육 실시 (분기별)','2026-12-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(39,'고용형태 공시','고용형태별 근로자 현황 공시 (300인 이상)','2026-03-31',NULL,'labor',1,1,'근로기준법 §14',NULL,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(40,'원천세 신고·납부','2025년 12월분 원천징수세액 신고 및 납부','2026-01-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(41,'원천세 신고·납부','2026년 1월분 원천징수세액 신고 및 납부','2026-02-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(42,'원천세 신고·납부','2026년 2월분 원천징수세액 신고 및 납부','2026-03-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(43,'원천세 신고·납부','2026년 3월분 원천징수세액 신고 및 납부','2026-04-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(44,'원천세 신고·납부','2026년 4월분 원천징수세액 신고 및 납부','2026-05-11',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(45,'원천세 신고·납부','2026년 5월분 원천징수세액 신고 및 납부','2026-06-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(46,'원천세 신고·납부','2026년 6월분 원천징수세액 신고 및 납부','2026-07-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(47,'원천세 신고·납부','2026년 7월분 원천징수세액 신고 및 납부','2026-08-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(48,'원천세 신고·납부','2026년 8월분 원천징수세액 신고 및 납부','2026-09-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(49,'원천세 신고·납부','2026년 9월분 원천징수세액 신고 및 납부','2026-10-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(50,'원천세 신고·납부','2026년 10월분 원천징수세액 신고 및 납부','2026-11-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(51,'원천세 신고·납부','2026년 11월분 원천징수세액 신고 및 납부','2026-12-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(52,'부가세 2기 확정신고','2025년 하반기 부가가치세 확정신고·납부','2026-01-26',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(53,'부가세 1기 예정신고','2026년 상반기 부가가치세 예정신고·납부','2026-04-27',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(54,'부가세 1기 확정신고','2026년 상반기 부가가치세 확정신고·납부','2026-07-27',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(55,'부가세 2기 예정신고','2026년 하반기 부가가치세 예정신고·납부','2026-10-26',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(56,'법인세 신고·납부','2025 사업연도(12월 결산) 법인세 신고 기한','2026-03-31',NULL,'tax',1,1,'법인세법 §60',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(57,'지방소득세 신고(법인)','2025 사업연도 법인 지방소득세 신고·납부','2026-04-30',NULL,'tax',1,1,'지방세법 §103의23',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(58,'종합소득세 확정신고','2025년 귀속 종합소득세 확정신고·납부','2026-06-01',NULL,'tax',1,1,'소득세법 §70',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(59,'지방소득세 신고(종소)','2025년 귀속 개인 지방소득세 신고·납부','2026-06-01',NULL,'tax',1,1,'지방세법 §95',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(60,'성실신고확인 종소세','성실신고확인대상 종합소득세 신고 기한','2026-06-30',NULL,'tax',1,1,'소득세법 §70의2',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(61,'연말정산 간소화 오픈','국세청 연말정산 간소화 서비스 개통','2026-01-15',NULL,'tax',1,0,NULL,NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(62,'연말정산 서류 제출','근로소득 연말정산 서류 제출 기간','2026-01-20','2026-03-03','tax',1,0,'소득세법 §137',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(63,'연말정산 신고 기한','2025년 귀속 연말정산 완료(원천징수이행상황 신고)','2026-03-10',NULL,'tax',1,1,'소득세법 §137',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(64,'지급명세서 제출','전년도 지급명세서(근로/사업/기타소득) 제출 기한','2026-03-03',NULL,'tax',1,1,'소득세법 §164',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(65,'건강보험 보수총액 신고','전년도 건강보험·국민연금 보수총액 신고','2026-03-10',NULL,'insurance',1,1,'국민건강보험법 §70',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(66,'고용·산재보험 보수총액 신고','전년도 고용보험·산재보험 보수총액 신고','2026-03-16',NULL,'insurance',1,1,'고용보험법 §16의10',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(67,'고용·산재 개산보험료 신고','당해 연도 고용·산재보험 개산보험료 신고·납부','2026-03-31',NULL,'insurance',1,1,'고용보험 및 산재보험의 보험료징수 등에 관한 법률 §17',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(68,'건강보험 보수월액 변경','건강보험 보수월액 정기 변경 적용','2026-04-01',NULL,'insurance',1,0,'국민건강보험법 §70',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(69,'국민연금 기준소득월액 변경','국민연금 기준소득월액 정기 결정 적용','2026-07-01',NULL,'insurance',1,0,'국민연금법 §63',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(70,'고용·산재 확정보험료 신고','전년도 고용·산재보험 확정보험료 정산','2026-03-31',NULL,'insurance',1,1,'보험료징수법 §16의3',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(71,'최저임금 변경 적용','2026년 최저임금 적용 시작','2026-01-01',NULL,'labor',1,0,'최저임금법 §10',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(72,'근로자의 날','법정 유급휴일 (공휴일 아님, 5인 이상 사업장)','2026-05-01',NULL,'labor',1,0,'근로자의 날 제정에 관한 법률',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(73,'장애인 의무고용 현황 신고','장애인 고용 현황 및 부담금 신고','2026-01-31',NULL,'labor',1,1,'장애인고용촉진법 §33',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(74,'산업안전보건 교육(1분기)','정기 안전보건 교육 실시 (분기별)','2026-03-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(75,'산업안전보건 교육(2분기)','정기 안전보건 교육 실시 (분기별)','2026-06-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(76,'산업안전보건 교육(3분기)','정기 안전보건 교육 실시 (분기별)','2026-09-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(77,'산업안전보건 교육(4분기)','정기 안전보건 교육 실시 (분기별)','2026-12-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(78,'고용형태 공시','고용형태별 근로자 현황 공시 (300인 이상)','2026-03-31',NULL,'labor',1,1,'근로기준법 §14',NULL,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(79,'원천세 신고·납부','2025년 12월분 원천징수세액 신고 및 납부','2026-01-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(80,'원천세 신고·납부','2026년 1월분 원천징수세액 신고 및 납부','2026-02-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(81,'원천세 신고·납부','2026년 2월분 원천징수세액 신고 및 납부','2026-03-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(82,'원천세 신고·납부','2026년 3월분 원천징수세액 신고 및 납부','2026-04-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(83,'원천세 신고·납부','2026년 4월분 원천징수세액 신고 및 납부','2026-05-11',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(84,'원천세 신고·납부','2026년 5월분 원천징수세액 신고 및 납부','2026-06-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(85,'원천세 신고·납부','2026년 6월분 원천징수세액 신고 및 납부','2026-07-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(86,'원천세 신고·납부','2026년 7월분 원천징수세액 신고 및 납부','2026-08-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(87,'원천세 신고·납부','2026년 8월분 원천징수세액 신고 및 납부','2026-09-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(88,'원천세 신고·납부','2026년 9월분 원천징수세액 신고 및 납부','2026-10-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(89,'원천세 신고·납부','2026년 10월분 원천징수세액 신고 및 납부','2026-11-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(90,'원천세 신고·납부','2026년 11월분 원천징수세액 신고 및 납부','2026-12-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(91,'부가세 2기 확정신고','2025년 하반기 부가가치세 확정신고·납부','2026-01-26',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(92,'부가세 1기 예정신고','2026년 상반기 부가가치세 예정신고·납부','2026-04-27',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(93,'부가세 1기 확정신고','2026년 상반기 부가가치세 확정신고·납부','2026-07-27',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(94,'부가세 2기 예정신고','2026년 하반기 부가가치세 예정신고·납부','2026-10-26',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(95,'법인세 신고·납부','2025 사업연도(12월 결산) 법인세 신고 기한','2026-03-31',NULL,'tax',1,1,'법인세법 §60',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(96,'지방소득세 신고(법인)','2025 사업연도 법인 지방소득세 신고·납부','2026-04-30',NULL,'tax',1,1,'지방세법 §103의23',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(97,'종합소득세 확정신고','2025년 귀속 종합소득세 확정신고·납부','2026-06-01',NULL,'tax',1,1,'소득세법 §70',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(98,'지방소득세 신고(종소)','2025년 귀속 개인 지방소득세 신고·납부','2026-06-01',NULL,'tax',1,1,'지방세법 §95',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(99,'성실신고확인 종소세','성실신고확인대상 종합소득세 신고 기한','2026-06-30',NULL,'tax',1,1,'소득세법 §70의2',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(100,'연말정산 간소화 오픈','국세청 연말정산 간소화 서비스 개통','2026-01-15',NULL,'tax',1,0,NULL,NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(101,'연말정산 서류 제출','근로소득 연말정산 서류 제출 기간','2026-01-20','2026-03-03','tax',1,0,'소득세법 §137',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(102,'연말정산 신고 기한','2025년 귀속 연말정산 완료(원천징수이행상황 신고)','2026-03-10',NULL,'tax',1,1,'소득세법 §137',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(103,'지급명세서 제출','전년도 지급명세서(근로/사업/기타소득) 제출 기한','2026-03-03',NULL,'tax',1,1,'소득세법 §164',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(104,'건강보험 보수총액 신고','전년도 건강보험·국민연금 보수총액 신고','2026-03-10',NULL,'insurance',1,1,'국민건강보험법 §70',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(105,'고용·산재보험 보수총액 신고','전년도 고용보험·산재보험 보수총액 신고','2026-03-16',NULL,'insurance',1,1,'고용보험법 §16의10',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(106,'고용·산재 개산보험료 신고','당해 연도 고용·산재보험 개산보험료 신고·납부','2026-03-31',NULL,'insurance',1,1,'고용보험 및 산재보험의 보험료징수 등에 관한 법률 §17',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(107,'건강보험 보수월액 변경','건강보험 보수월액 정기 변경 적용','2026-04-01',NULL,'insurance',1,0,'국민건강보험법 §70',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(108,'국민연금 기준소득월액 변경','국민연금 기준소득월액 정기 결정 적용','2026-07-01',NULL,'insurance',1,0,'국민연금법 §63',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(109,'고용·산재 확정보험료 신고','전년도 고용·산재보험 확정보험료 정산','2026-03-31',NULL,'insurance',1,1,'보험료징수법 §16의3',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(110,'최저임금 변경 적용','2026년 최저임금 적용 시작','2026-01-01',NULL,'labor',1,0,'최저임금법 §10',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(111,'근로자의 날','법정 유급휴일 (공휴일 아님, 5인 이상 사업장)','2026-05-01',NULL,'labor',1,0,'근로자의 날 제정에 관한 법률',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(112,'장애인 의무고용 현황 신고','장애인 고용 현황 및 부담금 신고','2026-01-31',NULL,'labor',1,1,'장애인고용촉진법 §33',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(113,'산업안전보건 교육(1분기)','정기 안전보건 교육 실시 (분기별)','2026-03-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(114,'산업안전보건 교육(2분기)','정기 안전보건 교육 실시 (분기별)','2026-06-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(115,'산업안전보건 교육(3분기)','정기 안전보건 교육 실시 (분기별)','2026-09-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(116,'산업안전보건 교육(4분기)','정기 안전보건 교육 실시 (분기별)','2026-12-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(117,'고용형태 공시','고용형태별 근로자 현황 공시 (300인 이상)','2026-03-31',NULL,'labor',1,1,'근로기준법 §14',NULL,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(118,'원천세 신고·납부','2025년 12월분 원천징수세액 신고 및 납부','2026-01-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(119,'원천세 신고·납부','2026년 1월분 원천징수세액 신고 및 납부','2026-02-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(120,'원천세 신고·납부','2026년 2월분 원천징수세액 신고 및 납부','2026-03-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(121,'원천세 신고·납부','2026년 3월분 원천징수세액 신고 및 납부','2026-04-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(122,'원천세 신고·납부','2026년 4월분 원천징수세액 신고 및 납부','2026-05-11',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(123,'원천세 신고·납부','2026년 5월분 원천징수세액 신고 및 납부','2026-06-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(124,'원천세 신고·납부','2026년 6월분 원천징수세액 신고 및 납부','2026-07-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(125,'원천세 신고·납부','2026년 7월분 원천징수세액 신고 및 납부','2026-08-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(126,'원천세 신고·납부','2026년 8월분 원천징수세액 신고 및 납부','2026-09-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(127,'원천세 신고·납부','2026년 9월분 원천징수세액 신고 및 납부','2026-10-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(128,'원천세 신고·납부','2026년 10월분 원천징수세액 신고 및 납부','2026-11-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(129,'원천세 신고·납부','2026년 11월분 원천징수세액 신고 및 납부','2026-12-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(130,'부가세 2기 확정신고','2025년 하반기 부가가치세 확정신고·납부','2026-01-26',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(131,'부가세 1기 예정신고','2026년 상반기 부가가치세 예정신고·납부','2026-04-27',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(132,'부가세 1기 확정신고','2026년 상반기 부가가치세 확정신고·납부','2026-07-27',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(133,'부가세 2기 예정신고','2026년 하반기 부가가치세 예정신고·납부','2026-10-26',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(134,'법인세 신고·납부','2025 사업연도(12월 결산) 법인세 신고 기한','2026-03-31',NULL,'tax',1,1,'법인세법 §60',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(135,'지방소득세 신고(법인)','2025 사업연도 법인 지방소득세 신고·납부','2026-04-30',NULL,'tax',1,1,'지방세법 §103의23',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(136,'종합소득세 확정신고','2025년 귀속 종합소득세 확정신고·납부','2026-06-01',NULL,'tax',1,1,'소득세법 §70',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(137,'지방소득세 신고(종소)','2025년 귀속 개인 지방소득세 신고·납부','2026-06-01',NULL,'tax',1,1,'지방세법 §95',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(138,'성실신고확인 종소세','성실신고확인대상 종합소득세 신고 기한','2026-06-30',NULL,'tax',1,1,'소득세법 §70의2',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(139,'연말정산 간소화 오픈','국세청 연말정산 간소화 서비스 개통','2026-01-15',NULL,'tax',1,0,NULL,NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(140,'연말정산 서류 제출','근로소득 연말정산 서류 제출 기간','2026-01-20','2026-03-03','tax',1,0,'소득세법 §137',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(141,'연말정산 신고 기한','2025년 귀속 연말정산 완료(원천징수이행상황 신고)','2026-03-10',NULL,'tax',1,1,'소득세법 §137',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(142,'지급명세서 제출','전년도 지급명세서(근로/사업/기타소득) 제출 기한','2026-03-03',NULL,'tax',1,1,'소득세법 §164',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(143,'건강보험 보수총액 신고','전년도 건강보험·국민연금 보수총액 신고','2026-03-10',NULL,'insurance',1,1,'국민건강보험법 §70',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(144,'고용·산재보험 보수총액 신고','전년도 고용보험·산재보험 보수총액 신고','2026-03-16',NULL,'insurance',1,1,'고용보험법 §16의10',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(145,'고용·산재 개산보험료 신고','당해 연도 고용·산재보험 개산보험료 신고·납부','2026-03-31',NULL,'insurance',1,1,'고용보험 및 산재보험의 보험료징수 등에 관한 법률 §17',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(146,'건강보험 보수월액 변경','건강보험 보수월액 정기 변경 적용','2026-04-01',NULL,'insurance',1,0,'국민건강보험법 §70',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(147,'국민연금 기준소득월액 변경','국민연금 기준소득월액 정기 결정 적용','2026-07-01',NULL,'insurance',1,0,'국민연금법 §63',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(148,'고용·산재 확정보험료 신고','전년도 고용·산재보험 확정보험료 정산','2026-03-31',NULL,'insurance',1,1,'보험료징수법 §16의3',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(149,'최저임금 변경 적용','2026년 최저임금 적용 시작','2026-01-01',NULL,'labor',1,0,'최저임금법 §10',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(150,'근로자의 날','법정 유급휴일 (공휴일 아님, 5인 이상 사업장)','2026-05-01',NULL,'labor',1,0,'근로자의 날 제정에 관한 법률',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(151,'장애인 의무고용 현황 신고','장애인 고용 현황 및 부담금 신고','2026-01-31',NULL,'labor',1,1,'장애인고용촉진법 §33',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(152,'산업안전보건 교육(1분기)','정기 안전보건 교육 실시 (분기별)','2026-03-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(153,'산업안전보건 교육(2분기)','정기 안전보건 교육 실시 (분기별)','2026-06-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(154,'산업안전보건 교육(3분기)','정기 안전보건 교육 실시 (분기별)','2026-09-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(155,'산업안전보건 교육(4분기)','정기 안전보건 교육 실시 (분기별)','2026-12-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(156,'고용형태 공시','고용형태별 근로자 현황 공시 (300인 이상)','2026-03-31',NULL,'labor',1,1,'근로기준법 §14',NULL,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(157,'원천세 신고·납부','2025년 12월분 원천징수세액 신고 및 납부','2026-01-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(158,'원천세 신고·납부','2026년 1월분 원천징수세액 신고 및 납부','2026-02-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(159,'원천세 신고·납부','2026년 2월분 원천징수세액 신고 및 납부','2026-03-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(160,'원천세 신고·납부','2026년 3월분 원천징수세액 신고 및 납부','2026-04-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(161,'원천세 신고·납부','2026년 4월분 원천징수세액 신고 및 납부','2026-05-11',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(162,'원천세 신고·납부','2026년 5월분 원천징수세액 신고 및 납부','2026-06-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(163,'원천세 신고·납부','2026년 6월분 원천징수세액 신고 및 납부','2026-07-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(164,'원천세 신고·납부','2026년 7월분 원천징수세액 신고 및 납부','2026-08-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(165,'원천세 신고·납부','2026년 8월분 원천징수세액 신고 및 납부','2026-09-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(166,'원천세 신고·납부','2026년 9월분 원천징수세액 신고 및 납부','2026-10-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(167,'원천세 신고·납부','2026년 10월분 원천징수세액 신고 및 납부','2026-11-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(168,'원천세 신고·납부','2026년 11월분 원천징수세액 신고 및 납부','2026-12-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(169,'부가세 2기 확정신고','2025년 하반기 부가가치세 확정신고·납부','2026-01-26',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(170,'부가세 1기 예정신고','2026년 상반기 부가가치세 예정신고·납부','2026-04-27',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(171,'부가세 1기 확정신고','2026년 상반기 부가가치세 확정신고·납부','2026-07-27',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(172,'부가세 2기 예정신고','2026년 하반기 부가가치세 예정신고·납부','2026-10-26',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(173,'법인세 신고·납부','2025 사업연도(12월 결산) 법인세 신고 기한','2026-03-31',NULL,'tax',1,1,'법인세법 §60',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(174,'지방소득세 신고(법인)','2025 사업연도 법인 지방소득세 신고·납부','2026-04-30',NULL,'tax',1,1,'지방세법 §103의23',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(175,'종합소득세 확정신고','2025년 귀속 종합소득세 확정신고·납부','2026-06-01',NULL,'tax',1,1,'소득세법 §70',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(176,'지방소득세 신고(종소)','2025년 귀속 개인 지방소득세 신고·납부','2026-06-01',NULL,'tax',1,1,'지방세법 §95',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(177,'성실신고확인 종소세','성실신고확인대상 종합소득세 신고 기한','2026-06-30',NULL,'tax',1,1,'소득세법 §70의2',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(178,'연말정산 간소화 오픈','국세청 연말정산 간소화 서비스 개통','2026-01-15',NULL,'tax',1,0,NULL,NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(179,'연말정산 서류 제출','근로소득 연말정산 서류 제출 기간','2026-01-20','2026-03-03','tax',1,0,'소득세법 §137',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(180,'연말정산 신고 기한','2025년 귀속 연말정산 완료(원천징수이행상황 신고)','2026-03-10',NULL,'tax',1,1,'소득세법 §137',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(181,'지급명세서 제출','전년도 지급명세서(근로/사업/기타소득) 제출 기한','2026-03-03',NULL,'tax',1,1,'소득세법 §164',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(182,'건강보험 보수총액 신고','전년도 건강보험·국민연금 보수총액 신고','2026-03-10',NULL,'insurance',1,1,'국민건강보험법 §70',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(183,'고용·산재보험 보수총액 신고','전년도 고용보험·산재보험 보수총액 신고','2026-03-16',NULL,'insurance',1,1,'고용보험법 §16의10',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(184,'고용·산재 개산보험료 신고','당해 연도 고용·산재보험 개산보험료 신고·납부','2026-03-31',NULL,'insurance',1,1,'고용보험 및 산재보험의 보험료징수 등에 관한 법률 §17',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(185,'건강보험 보수월액 변경','건강보험 보수월액 정기 변경 적용','2026-04-01',NULL,'insurance',1,0,'국민건강보험법 §70',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(186,'국민연금 기준소득월액 변경','국민연금 기준소득월액 정기 결정 적용','2026-07-01',NULL,'insurance',1,0,'국민연금법 §63',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(187,'고용·산재 확정보험료 신고','전년도 고용·산재보험 확정보험료 정산','2026-03-31',NULL,'insurance',1,1,'보험료징수법 §16의3',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(188,'최저임금 변경 적용','2026년 최저임금 적용 시작','2026-01-01',NULL,'labor',1,0,'최저임금법 §10',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(189,'근로자의 날','법정 유급휴일 (공휴일 아님, 5인 이상 사업장)','2026-05-01',NULL,'labor',1,0,'근로자의 날 제정에 관한 법률',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(190,'장애인 의무고용 현황 신고','장애인 고용 현황 및 부담금 신고','2026-01-31',NULL,'labor',1,1,'장애인고용촉진법 §33',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(191,'산업안전보건 교육(1분기)','정기 안전보건 교육 실시 (분기별)','2026-03-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(192,'산업안전보건 교육(2분기)','정기 안전보건 교육 실시 (분기별)','2026-06-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(193,'산업안전보건 교육(3분기)','정기 안전보건 교육 실시 (분기별)','2026-09-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(194,'산업안전보건 교육(4분기)','정기 안전보건 교육 실시 (분기별)','2026-12-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(195,'고용형태 공시','고용형태별 근로자 현황 공시 (300인 이상)','2026-03-31',NULL,'labor',1,1,'근로기준법 §14',NULL,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(196,'원천세 신고·납부','2025년 12월분 원천징수세액 신고 및 납부','2026-01-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(197,'원천세 신고·납부','2026년 1월분 원천징수세액 신고 및 납부','2026-02-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(198,'원천세 신고·납부','2026년 2월분 원천징수세액 신고 및 납부','2026-03-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(199,'원천세 신고·납부','2026년 3월분 원천징수세액 신고 및 납부','2026-04-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(200,'원천세 신고·납부','2026년 4월분 원천징수세액 신고 및 납부','2026-05-11',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(201,'원천세 신고·납부','2026년 5월분 원천징수세액 신고 및 납부','2026-06-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(202,'원천세 신고·납부','2026년 6월분 원천징수세액 신고 및 납부','2026-07-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(203,'원천세 신고·납부','2026년 7월분 원천징수세액 신고 및 납부','2026-08-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(204,'원천세 신고·납부','2026년 8월분 원천징수세액 신고 및 납부','2026-09-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(205,'원천세 신고·납부','2026년 9월분 원천징수세액 신고 및 납부','2026-10-12',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(206,'원천세 신고·납부','2026년 10월분 원천징수세액 신고 및 납부','2026-11-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(207,'원천세 신고·납부','2026년 11월분 원천징수세액 신고 및 납부','2026-12-10',NULL,'tax',1,1,'소득세법 §128',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(208,'부가세 2기 확정신고','2025년 하반기 부가가치세 확정신고·납부','2026-01-26',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(209,'부가세 1기 예정신고','2026년 상반기 부가가치세 예정신고·납부','2026-04-27',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(210,'부가세 1기 확정신고','2026년 상반기 부가가치세 확정신고·납부','2026-07-27',NULL,'tax',1,1,'부가가치세법 §49',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(211,'부가세 2기 예정신고','2026년 하반기 부가가치세 예정신고·납부','2026-10-26',NULL,'tax',1,1,'부가가치세법 §48',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(212,'법인세 신고·납부','2025 사업연도(12월 결산) 법인세 신고 기한','2026-03-31',NULL,'tax',1,1,'법인세법 §60',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(213,'지방소득세 신고(법인)','2025 사업연도 법인 지방소득세 신고·납부','2026-04-30',NULL,'tax',1,1,'지방세법 §103의23',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(214,'종합소득세 확정신고','2025년 귀속 종합소득세 확정신고·납부','2026-06-01',NULL,'tax',1,1,'소득세법 §70',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(215,'지방소득세 신고(종소)','2025년 귀속 개인 지방소득세 신고·납부','2026-06-01',NULL,'tax',1,1,'지방세법 §95',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(216,'성실신고확인 종소세','성실신고확인대상 종합소득세 신고 기한','2026-06-30',NULL,'tax',1,1,'소득세법 §70의2',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(217,'연말정산 간소화 오픈','국세청 연말정산 간소화 서비스 개통','2026-01-15',NULL,'tax',1,0,NULL,NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(218,'연말정산 서류 제출','근로소득 연말정산 서류 제출 기간','2026-01-20','2026-03-03','tax',1,0,'소득세법 §137',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(219,'연말정산 신고 기한','2025년 귀속 연말정산 완료(원천징수이행상황 신고)','2026-03-10',NULL,'tax',1,1,'소득세법 §137',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(220,'지급명세서 제출','전년도 지급명세서(근로/사업/기타소득) 제출 기한','2026-03-03',NULL,'tax',1,1,'소득세법 §164',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(221,'건강보험 보수총액 신고','전년도 건강보험·국민연금 보수총액 신고','2026-03-10',NULL,'insurance',1,1,'국민건강보험법 §70',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(222,'고용·산재보험 보수총액 신고','전년도 고용보험·산재보험 보수총액 신고','2026-03-16',NULL,'insurance',1,1,'고용보험법 §16의10',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(223,'고용·산재 개산보험료 신고','당해 연도 고용·산재보험 개산보험료 신고·납부','2026-03-31',NULL,'insurance',1,1,'고용보험 및 산재보험의 보험료징수 등에 관한 법률 §17',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(224,'건강보험 보수월액 변경','건강보험 보수월액 정기 변경 적용','2026-04-01',NULL,'insurance',1,0,'국민건강보험법 §70',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(225,'국민연금 기준소득월액 변경','국민연금 기준소득월액 정기 결정 적용','2026-07-01',NULL,'insurance',1,0,'국민연금법 §63',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(226,'고용·산재 확정보험료 신고','전년도 고용·산재보험 확정보험료 정산','2026-03-31',NULL,'insurance',1,1,'보험료징수법 §16의3',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(227,'최저임금 변경 적용','2026년 최저임금 적용 시작','2026-01-01',NULL,'labor',1,0,'최저임금법 §10',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(228,'근로자의 날','법정 유급휴일 (공휴일 아님, 5인 이상 사업장)','2026-05-01',NULL,'labor',1,0,'근로자의 날 제정에 관한 법률',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(229,'장애인 의무고용 현황 신고','장애인 고용 현황 및 부담금 신고','2026-01-31',NULL,'labor',1,1,'장애인고용촉진법 §33',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(230,'산업안전보건 교육(1분기)','정기 안전보건 교육 실시 (분기별)','2026-03-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(231,'산업안전보건 교육(2분기)','정기 안전보건 교육 실시 (분기별)','2026-06-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(232,'산업안전보건 교육(3분기)','정기 안전보건 교육 실시 (분기별)','2026-09-30',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(233,'산업안전보건 교육(4분기)','정기 안전보건 교육 실시 (분기별)','2026-12-31',NULL,'labor',1,0,'산업안전보건법 §29',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(234,'고용형태 공시','고용형태별 근로자 현황 공시 (300인 이상)','2026-03-31',NULL,'labor',1,1,'근로기준법 §14',NULL,'2026-07-03 01:33:13','2026-07-03 01:33:13');
/*!40000 ALTER TABLE `calendar_events` ENABLE KEYS */;
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
INSERT INTO `card_approvals` VALUES (1,1,1,'AP-2026-001','한우마을 강남점',45000,'2026-02-20 12:30:00','승인','2026-03-22 15:18:32'),(2,2,1,'AP-2026-002','카카오택시',32000,'2026-02-21 09:15:00','승인','2026-03-22 15:18:32'),(3,3,2,'AP-2026-003','쿠팡',128000,'2026-02-19 14:20:00','승인','2026-03-22 15:18:32'),(4,4,3,'AP-2026-004','스시오마카세',85000,'2026-02-18 18:45:00','승인','2026-03-22 15:18:32'),(5,5,1,'AP-2026-005','카카오택시',15000,'2026-02-22 10:00:00','승인','2026-03-22 15:18:32'),(6,6,2,'AP-2026-006','고깃집 서초점',62000,'2026-02-17 19:30:00','승인','2026-03-22 15:18:32'),(7,7,4,'AP-2026-007','르씨엘 레스토랑',250000,'2026-02-15 12:00:00','승인','2026-03-22 15:18:32'),(8,8,3,'AP-2026-008','오피스디포',35000,'2026-02-16 11:10:00','승인','2026-03-22 15:18:32'),(9,9,1,'AP-2026-009','더플레이스 역삼',55000,'2026-02-23 12:15:00','승인','2026-03-22 15:18:32'),(10,10,2,'AP-2026-010','KTX',28000,'2026-02-24 08:00:00','승인','2026-03-22 15:18:32'),(11,NULL,1,'AP-2026-011','GS25 역삼점',8500,'2026-02-25 15:30:00','승인','2026-03-22 15:18:32'),(12,NULL,3,'AP-2026-012','네이버페이',42000,'2026-02-25 16:00:00','취소','2026-03-22 15:18:32');
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
  `exception_reason` text COLLATE utf8mb4_general_ci COMMENT '한도 초과 예외 사유',
  `regulation_limit` int DEFAULT NULL COMMENT '적용된 규정 한도 (원)',
  `settlement_updater` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '정산 업데이트 작성자',
  `settlement_date` datetime DEFAULT NULL COMMENT '최종 업데이트일',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `card_id` (`card_id`),
  CONSTRAINT `card_expenses_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_expenses`
--

LOCK TABLES `card_expenses` WRITE;
/*!40000 ALTER TABLE `card_expenses` DISABLE KEYS */;
INSERT INTO `card_expenses` VALUES (1,1,'최영업','AP-2026-001','법인','식대','식사',45000,'거래처 미팅 식사','A프로젝트',NULL,'DOC-2026-0101','최영업','2026-02-20',1,'준수',NULL,NULL,'김대표','2026-04-06 06:57:26','2026-03-22 15:18:32','2026-04-06 06:57:26'),(2,1,'최영업','AP-2026-002','법인','교통비','여비교통비',32000,'거래처 방문 택시비','A프로젝트',NULL,'DOC-2026-0102','최영업','2026-02-21',1,'준수',NULL,NULL,'김대표','2026-04-06 06:57:26','2026-03-22 15:18:32','2026-04-06 06:57:26'),(3,2,'박기술','AP-2026-003','법인','소모품','구입비',128000,'개발용 장비 구입','',NULL,'','박기술','2026-02-19',1,'준수',NULL,NULL,'정지원','2026-02-25 10:00:00','2026-03-22 15:18:32','2026-04-03 10:38:24'),(4,3,'정지원','AP-2026-004','법인','접대비','영업사업비',85000,'고객사 접대','B프로젝트',NULL,'DOC-2026-0201','정지원','2026-02-18',1,'준수',NULL,NULL,'정지원','2026-02-25 10:00:00','2026-03-22 15:18:32','2026-04-03 10:38:24'),(5,1,'한인사','AP-2026-005','개인','교통비','여비교통비',15000,'외근 교통비','',NULL,'','한인사','2026-02-22',1,'준수',NULL,NULL,'김대표','2026-04-06 06:57:26','2026-03-22 15:18:32','2026-04-06 06:57:26'),(6,2,'박기술','AP-2026-006','법인','식대','식사',62000,'팀 회식','',NULL,'','박기술','2026-02-17',1,'준수',NULL,NULL,'김대표','2026-04-06 06:57:26','2026-03-22 15:18:32','2026-04-06 06:57:26'),(7,4,'김대표','AP-2026-007','법인','접대비','영업사업비',250000,'VIP 고객 미팅','C프로젝트',NULL,'DOC-2026-0301','김대표','2026-02-15',1,'준수',NULL,NULL,'정지원','2026-02-25 10:00:00','2026-03-22 15:18:32','2026-04-03 10:38:24'),(8,3,'정지원','AP-2026-008','법인','기타','구입비',35000,'사무용품 구입','',NULL,'','정지원','2026-02-16',1,'준수',NULL,NULL,'김대표','2026-04-06 06:57:26','2026-03-22 15:18:32','2026-04-06 06:57:26'),(9,1,'최영업','AP-2026-009','법인','식대','식사',55000,'프로젝트 킥오프 식사','D프로젝트',NULL,'DOC-2026-0401','최영업','2026-02-23',1,'준수',NULL,NULL,'김대표','2026-04-06 06:57:26','2026-03-22 15:18:32','2026-04-06 06:57:26'),(10,2,'이본부장','AP-2026-010','개인','교통비','여비교통비',28000,'출장 교통비','A프로젝트',NULL,'','이본부장','2026-02-24',1,'준수',NULL,NULL,'김대표','2026-04-06 06:57:26','2026-03-22 15:18:32','2026-04-06 06:57:26'),(11,1,'최영업','AP-2026-011','법인','식대','식사',67000,'거래처 점심 회식','B프로젝트',NULL,NULL,'최영업','2026-03-03',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:19','2026-04-10 11:07:19'),(12,2,'박기술','AP-2026-012','법인','소모품','구입비',234000,'모니터 거치대 3개','',NULL,NULL,'박기술','2026-03-05',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:19','2026-04-10 11:07:19'),(13,3,'정지원','AP-2026-013','법인','사무용품','문구류',48500,'프린터 토너 교체','',NULL,NULL,'정지원','2026-03-07',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:19','2026-04-10 11:07:19'),(14,1,'최영업','AP-2026-014','법인','교통비','여비교통비',156000,'부산 출장 KTX','C프로젝트',NULL,NULL,'최영업','2026-03-10',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:19','2026-04-10 11:07:19'),(15,4,'김대표','AP-2026-015','법인','접대비','접대',320000,'거래처 대표 만찬','A프로젝트',NULL,NULL,'김대표','2026-03-12',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:19','2026-04-10 11:07:19'),(16,5,'한인사','AP-2026-016','법인','광고선전비','마케팅',550000,'네이버 키워드 광고','',NULL,NULL,'한인사','2026-03-14',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(17,2,'박기술','AP-2026-017','법인','식대','식사',89000,'팀 회식 (치킨)','',NULL,NULL,'박기술','2026-03-18',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(18,1,'최영업','AP-2026-018','법인','교통비','여비교통비',28000,'강남역 택시','B프로젝트',NULL,NULL,'최영업','2026-03-20',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(19,3,'정지원','AP-2026-019','법인','복리후생비','복리후생',175000,'팀빌딩 다과','',NULL,NULL,'정지원','2026-03-22',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(20,5,'한인사','AP-2026-020','법인','광고선전비','마케팅',330000,'인스타그램 광고','',NULL,NULL,'한인사','2026-03-25',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(21,4,'김대표','AP-2026-021','법인','교통비','여비교통비',45000,'공항 리무진','',NULL,NULL,'김대표','2026-03-28',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(22,2,'박기술','AP-2026-022','법인','소모품','구입비',89000,'키보드 2개','',NULL,NULL,'박기술','2026-03-30',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(23,1,'최영업','AP-2026-023','법인','식대','식사',54000,'거래처 점심','D프로젝트',NULL,NULL,'최영업','2026-04-01',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(24,2,'박기술','AP-2026-024','법인','소모품','구입비',1250000,'개발서버 SSD 업그레이드','',NULL,NULL,'박기술','2026-04-02',0,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(25,3,'정지원','AP-2026-025','법인','사무용품','문구류',32000,'A4 용지 10박스','',NULL,NULL,'정지원','2026-04-03',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(26,5,'한인사','AP-2026-026','법인','광고선전비','마케팅',780000,'구글 애드워즈 충전','',NULL,NULL,'한인사','2026-04-03',0,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(27,1,'최영업','AP-2026-027','법인','교통비','여비교통비',189000,'대전 출장 KTX 왕복','D프로젝트',NULL,NULL,'최영업','2026-04-04',0,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(28,4,'김대표','AP-2026-028','법인','접대비','접대',485000,'투자자 저녁 식사','',NULL,NULL,'김대표','2026-04-05',1,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(29,2,'박기술','AP-2026-029','개인','식대','식사',15000,'편의점 간식','',NULL,NULL,'박기술','2026-04-07',0,'미준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(30,1,'최영업','AP-2026-030','법인','식대','식사',112000,'팀 점심 회식','',NULL,NULL,'최영업','2026-04-07',0,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(31,5,'한인사','AP-2026-031','법인','광고선전비','마케팅',450000,'유튜브 프리롤 광고','',NULL,NULL,'한인사','2026-04-08',0,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(32,3,'정지원','AP-2026-032','법인','복리후생비','복리후생',230000,'생일자 케이크+선물','',NULL,NULL,'정지원','2026-04-08',0,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(33,4,'김대표','AP-2026-033','개인','교통비','여비교통비',88000,'주말 골프장 택시 (개인)','',NULL,NULL,'김대표','2026-04-09',0,'미준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(34,2,'박기술','AP-2026-034','법인','소모품','구입비',567000,'노트북 배터리+충전기','',NULL,NULL,'박기술','2026-04-09',0,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(35,1,'최영업','AP-2026-035','법인','식대','식사',73000,'거래처 커피미팅','B프로젝트',NULL,NULL,'최영업','2026-04-10',0,'준수',NULL,NULL,NULL,NULL,'2026-04-10 11:07:20','2026-04-10 11:07:20'),(36,1,'최영업','30100000','법인','접대비','식대',180000,'접대비 지출','한우명가',NULL,NULL,'최영업','2026-05-06',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(37,2,'박기술','30100001','법인','소모품비','개발장비',320000,'소모품비 지출','테크몰',NULL,NULL,'박기술','2026-05-09',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(38,3,'정지원','30100002','법인','복리후생','간식',95000,'복리후생 지출','이마트',NULL,NULL,'정지원','2026-05-12',0,'미확인',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(39,4,'김대표','30100003','법인','접대비','골프',450000,'접대비 지출','레이크힐스CC',NULL,NULL,'김대표','2026-05-15',0,'미확인',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(40,5,'한인사','30100004','법인','광고선전비','온라인광고',700000,'광고선전비 지출','메타광고',NULL,NULL,'한인사','2026-05-18',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(41,1,'최영업','30100005','법인','여비교통비','주유',88000,'여비교통비 지출','GS칼텍스',NULL,NULL,'최영업','2026-05-22',0,'미확인',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(42,2,'박기술','30100006','법인','통신비','클라우드',264000,'통신비 지출','AWS',NULL,NULL,'박기술','2026-05-25',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(43,1,'최영업','30100000','법인','접대비','식대',180000,'접대비 지출','한우명가',NULL,NULL,'최영업','2026-06-06',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(44,2,'박기술','30100001','법인','소모품비','개발장비',320000,'소모품비 지출','테크몰',NULL,NULL,'박기술','2026-06-09',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(45,3,'정지원','30100002','법인','복리후생','간식',95000,'복리후생 지출','이마트',NULL,NULL,'정지원','2026-06-12',0,'미확인',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(46,4,'김대표','30100003','법인','접대비','골프',450000,'접대비 지출','레이크힐스CC',NULL,NULL,'김대표','2026-06-15',0,'미확인',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(47,5,'한인사','30100004','법인','광고선전비','온라인광고',700000,'광고선전비 지출','메타광고',NULL,NULL,'한인사','2026-06-18',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(48,1,'최영업','30100005','법인','여비교통비','주유',88000,'여비교통비 지출','GS칼텍스',NULL,NULL,'최영업','2026-06-22',0,'미확인',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(49,2,'박기술','30100006','법인','통신비','클라우드',264000,'통신비 지출','AWS',NULL,NULL,'박기술','2026-06-25',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(50,1,'최영업','30100000','법인','접대비','식대',180000,'접대비 지출','한우명가',NULL,NULL,'최영업','2026-07-06',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(51,2,'박기술','30100001','법인','소모품비','개발장비',320000,'소모품비 지출','테크몰',NULL,NULL,'박기술','2026-07-09',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(52,3,'정지원','30100002','법인','복리후생','간식',95000,'복리후생 지출','이마트',NULL,NULL,'정지원','2026-07-12',0,'미확인',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(53,4,'김대표','30100003','법인','접대비','골프',450000,'접대비 지출','레이크힐스CC',NULL,NULL,'김대표','2026-07-15',0,'미확인',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(54,5,'한인사','30100004','법인','광고선전비','온라인광고',700000,'광고선전비 지출','메타광고',NULL,NULL,'한인사','2026-07-18',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(55,1,'최영업','30100005','법인','여비교통비','주유',88000,'여비교통비 지출','GS칼텍스',NULL,NULL,'최영업','2026-07-22',0,'미확인',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01'),(56,2,'박기술','30100006','법인','통신비','클라우드',264000,'통신비 지출','AWS',NULL,NULL,'박기술','2026-07-25',1,'준수',NULL,NULL,NULL,NULL,'2026-07-03 02:43:01','2026-07-03 02:43:01');
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
  `name` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT 'gray',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_regulation_categories`
--

LOCK TABLES `card_regulation_categories` WRITE;
/*!40000 ALTER TABLE `card_regulation_categories` DISABLE KEYS */;
INSERT INTO `card_regulation_categories` VALUES (1,'식사','red',1,1,'2026-04-07 17:58:55'),(2,'여비교통비','blue',2,1,'2026-04-07 17:58:55'),(3,'영업사업비','purple',3,1,'2026-04-07 17:58:55'),(4,'구입비','orange',4,1,'2026-04-07 17:58:55');
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_regulations`
--

LOCK TABLES `card_regulations` WRITE;
/*!40000 ALTER TABLE `card_regulations` DISABLE KEYS */;
INSERT INTO `card_regulations` VALUES (1,'식사','중식/석식',15000,'영수증, 참석자명단','1인당 15,000원 이내. 4인 이상 시 사전 승인 필요',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(2,'식사','회식',50000,'영수증, 참석자명단, 사유서','1인당 50,000원 이내. 팀장 사전승인 필수',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(3,'식사','간식/음료',10000,'영수증','1인당 10,000원 이내',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(4,'여비교통비','시내교통',0,'영수증, 출발/도착지','실비 정산. 택시 이용 시 사유 기재 필수',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(5,'여비교통비','출장교통',0,'영수증, 출장보고서','실비 정산. KTX 이상 시 사전승인 필요',5,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(6,'여비교통비','주차비/톨비',0,'영수증','실비 정산',6,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(7,'영업사업비','거래처 접대',100000,'영수증, 접대보고서','1건당 100,000원 이내. 초과 시 부서장 승인',7,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(8,'영업사업비','경조사비',50000,'경조사 증빙','건당 50,000원. 경조사 규정 참조',8,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(9,'영업사업비','선물/기념품',30000,'영수증, 사유서','1건당 30,000원 이내',9,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(10,'구입비','사무용품',50000,'영수증, 구매요청서','건당 50,000원 이내. 초과 시 구매부서 경유',10,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(11,'구입비','소프트웨어',0,'영수증, 구매요청서, 라이선스 정보','IT부서 사전 승인 필수',11,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(12,'구입비','장비/비품',0,'영수증, 구매요청서, 자산등록','50만원 이상 시 자산등록 필수',12,1,'2026-03-22 15:18:32','2026-03-22 15:18:32');
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
  `manager_employee_id` int unsigned DEFAULT NULL,
  `affiliation` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '소속',
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '부서',
  `department_id` int unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1' COMMENT '사용여부',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cards`
--

LOCK TABLES `cards` WRITE;
/*!40000 ALTER TABLE `cards` DISABLE KEYS */;
INSERT INTO `cards` VALUES (1,'영업팀 법인카드','9410-****-****-1234','영업팀 업무용','최영업',NULL,'위즈웨어','영업팀',NULL,1,'2026-03-22 15:18:32','2026-04-03 10:38:24'),(2,'개발팀 법인카드','9410-****-****-5678','개발팀 업무용','박기술',NULL,'위즈웨어','개발팀',NULL,1,'2026-03-22 15:18:32','2026-04-03 10:38:24'),(3,'경영지원 법인카드','5412-****-****-9012','경영지원실 업무용','정지원',NULL,'위즈웨어','경영지원실',NULL,1,'2026-03-22 15:18:32','2026-04-03 10:38:24'),(4,'대표이사 법인카드','4532-****-****-3456','대표이사 전용','김대표',1,'위즈웨어','경영진',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:24:10'),(5,'마케팅팀 법인카드','9410-****-****-7890','마케팅 업무용','한인사',NULL,'위즈웨어','마케팅팀',NULL,0,'2026-03-22 15:18:32','2026-04-03 10:38:24');
/*!40000 ALTER TABLE `cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `closing_attachments`
--

DROP TABLE IF EXISTS `closing_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `closing_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fiscal_year` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int DEFAULT '0',
  `uploaded_by` varchar(50) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `closing_attachments`
--

LOCK TABLES `closing_attachments` WRITE;
/*!40000 ALTER TABLE `closing_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `closing_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `closing_checklist`
--

DROP TABLE IF EXISTS `closing_checklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `closing_checklist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fiscal_year` int NOT NULL,
  `item_key` varchar(50) NOT NULL,
  `is_checked` tinyint(1) DEFAULT '0',
  `checked_by` varchar(50) DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fy_item` (`fiscal_year`,`item_key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `closing_checklist`
--

LOCK TABLES `closing_checklist` WRITE;
/*!40000 ALTER TABLE `closing_checklist` DISABLE KEYS */;
INSERT INTO `closing_checklist` VALUES (1,2026,'insurance_paid',0,NULL,'2026-04-14 17:23:06',NULL);
/*!40000 ALTER TABLE `closing_checklist` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `common_code_groups`
--

LOCK TABLES `common_code_groups` WRITE;
/*!40000 ALTER TABLE `common_code_groups` DISABLE KEYS */;
INSERT INTO `common_code_groups` VALUES (3,'hr','고용형태','직원의 고용 형태 분류',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(4,'hr','고용상태','직원의 재직/퇴직 상태 분류',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(5,'attendance','근무유형','출퇴근 근무 유형 분류',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(6,'attendance','휴가유형','연차/반차 등 휴가 유형',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(7,'card','비용항목','법인카드 사용 시 비용 분류 항목',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(8,'card','카드유형','법인카드 종류',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(9,'business','사업원가항목','사업 비용 책정 시 입력하는 사업원가항목 구분',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(10,'business','사업상태','사업 진행 상태 분류',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(11,'business','사업구분','사업 유형 분류',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(12,'reservation','자원목록','회사가 보유하고 운영하는 자원 정보 (회의실, 비품, 차량 등)',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(13,'schedule','일정유형','일정 분류 유형',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(14,'schedule','캘린더 색상','일정 캘린더 색상 구분',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(15,'hr','발령유형','인사발령 유형 분류',5,1,'2026-07-03 01:24:10','2026-07-03 01:24:10');
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
) ENGINE=InnoDB AUTO_INCREMENT=172 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `common_code_items`
--

LOCK TABLES `common_code_items` WRITE;
/*!40000 ALTER TABLE `common_code_items` DISABLE KEYS */;
INSERT INTO `common_code_items` VALUES (17,3,'FT','정규직',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(18,3,'CT','계약직',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(19,3,'PT','시간제',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(20,3,'DP','파견직',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(21,4,'ACT','재직',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(22,4,'LOA','휴직',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(23,4,'MAT','육아휴직',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(24,4,'RES','퇴사',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(25,5,'NRM','정상근무',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(26,5,'WFH','재택근무',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(27,5,'OUT','외근',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(28,5,'BIZ','출장',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(29,6,'AL','연차',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(30,6,'HAM','반차(오전)',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(31,6,'HAP','반차(오후)',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(32,6,'SL','병가',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(33,6,'FL','경조사',5,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(34,6,'OL','공가',6,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(35,7,'FOOD','식대',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(36,7,'TRANS','교통비',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(37,7,'ENT','접대비',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(38,7,'SUP','소모품',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(39,7,'ETC','기타',5,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(40,8,'CORP','법인카드',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(41,8,'PRIV','개인카드',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(42,9,'OS_C','외주비(기업)',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(43,9,'OS_P','외주비(개인)',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(44,9,'RES','자원구입비',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(45,9,'MKT','마케팅 수수료',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(46,9,'PRM','사업 판촉비',5,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(47,9,'EXP','진행 경비',6,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(48,9,'FREE','무상 서비스 원가',7,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(49,10,'SALES','영업',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(50,10,'CONT','계약',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(51,10,'PROG','진행중',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(52,10,'DONE','완료',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(53,10,'HOLD','보류',5,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(54,11,'SI','SI',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(55,11,'SM','SM',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(56,11,'CONS','컨설팅',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(57,11,'EDU','교육',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(58,12,'MR1','319호 - 회의실',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(59,12,'MR2','319호 - 탕비실(회의용)',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(60,12,'NB1','노트북 1 (내부)',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(61,12,'TAB','태블릿',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(62,13,'MTG','회의',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(63,13,'EXT','외부미팅',2,1,'2026-03-22 15:18:32','2026-07-03 01:24:11'),(64,13,'TRIP','출장',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(65,13,'EDU','교육',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(66,13,'ETC','기타',5,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(67,14,'BLUE','파랑',1,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(68,14,'RED','빨강',2,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(69,14,'GREEN','초록',3,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(70,14,'YELLOW','노랑',4,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(71,14,'PURPLE','보라',5,1,'2026-03-22 15:18:32','2026-03-22 15:18:32'),(77,15,'NEW_HIRE','신규입사',1,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(78,15,'TRANSFER','전보',2,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(79,15,'PROMOTION','승진',3,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(80,15,'POS_CHANGE','직급변경',4,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(81,15,'TITLE_CHANGE','직책변경',5,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(82,15,'TYPE_CHANGE','고용형태변경',6,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(83,15,'DISPATCH','파견',7,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(84,15,'TRANSFER_OUT','전출',8,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(85,15,'TRANSFER_IN','전입',9,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(86,15,'LEAVE','휴직',10,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(87,15,'RETURN','복직',11,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(88,15,'STATUS_CHANGE','상태변경',12,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(89,15,'COMPOUND','복합발령',13,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(90,15,'RESIGN','퇴사',14,1,'2026-07-03 01:24:10','2026-07-03 01:24:10'),(91,13,'OUT','외근',6,1,'2026-07-03 01:24:11','2026-07-03 01:24:11'),(92,13,'INTV','면담',7,1,'2026-07-03 01:24:11','2026-07-03 01:24:11'),(93,13,'EVT','행사',8,1,'2026-07-03 01:24:11','2026-07-03 01:24:11'),(94,13,'DUE','마감',9,1,'2026-07-03 01:24:11','2026-07-03 01:24:11'),(98,5,'OT','야근',5,1,'2026-07-03 01:24:11','2026-07-03 01:24:11'),(99,5,'HOL','휴일근무',6,1,'2026-07-03 01:24:11','2026-07-03 01:24:11'),(100,15,'DISPATCH','파견',7,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(101,15,'TRANSFER_OUT','전출',8,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(102,15,'TRANSFER_IN','전입',9,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(103,15,'LEAVE','휴직',10,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(104,15,'RETURN','복직',11,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(105,15,'DISPATCH','파견',7,1,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(106,15,'TRANSFER_OUT','전출',8,1,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(107,15,'TRANSFER_IN','전입',9,1,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(108,15,'LEAVE','휴직',10,1,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(109,15,'RETURN','복직',11,1,'2026-07-03 01:26:27','2026-07-03 01:26:27'),(110,15,'DISPATCH','파견',7,1,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(111,15,'TRANSFER_OUT','전출',8,1,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(112,15,'TRANSFER_IN','전입',9,1,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(113,15,'LEAVE','휴직',10,1,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(114,15,'RETURN','복직',11,1,'2026-07-03 01:29:21','2026-07-03 01:29:21'),(115,15,'DISPATCH','파견',7,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(116,15,'TRANSFER_OUT','전출',8,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(117,15,'TRANSFER_IN','전입',9,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(118,15,'LEAVE','휴직',10,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(119,15,'RETURN','복직',11,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(120,15,'NEW_HIRE','신규입사',1,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(121,15,'TRANSFER','전보',2,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(122,15,'PROMOTION','승진',3,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(123,15,'POS_CHANGE','직급변경',4,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(124,15,'TITLE_CHANGE','직책변경',5,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(125,15,'TYPE_CHANGE','고용형태변경',6,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(126,15,'DISPATCH','파견',7,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(127,15,'TRANSFER_OUT','전출',8,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(128,15,'TRANSFER_IN','전입',9,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(129,15,'LEAVE','휴직',10,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(130,15,'RETURN','복직',11,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(131,15,'STATUS_CHANGE','상태변경',12,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(132,15,'COMPOUND','복합발령',13,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(133,15,'RESIGN','퇴사',14,1,'2026-07-03 01:31:25','2026-07-03 01:31:25'),(134,15,'DISPATCH','파견',7,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(135,15,'TRANSFER_OUT','전출',8,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(136,15,'TRANSFER_IN','전입',9,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(137,15,'LEAVE','휴직',10,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(138,15,'RETURN','복직',11,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(139,15,'NEW_HIRE','신규입사',1,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(140,15,'TRANSFER','전보',2,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(141,15,'PROMOTION','승진',3,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(142,15,'POS_CHANGE','직급변경',4,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(143,15,'TITLE_CHANGE','직책변경',5,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(144,15,'TYPE_CHANGE','고용형태변경',6,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(145,15,'DISPATCH','파견',7,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(146,15,'TRANSFER_OUT','전출',8,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(147,15,'TRANSFER_IN','전입',9,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(148,15,'LEAVE','휴직',10,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(149,15,'RETURN','복직',11,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(150,15,'STATUS_CHANGE','상태변경',12,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(151,15,'COMPOUND','복합발령',13,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(152,15,'RESIGN','퇴사',14,1,'2026-07-03 01:32:20','2026-07-03 01:32:20'),(153,15,'DISPATCH','파견',7,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(154,15,'TRANSFER_OUT','전출',8,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(155,15,'TRANSFER_IN','전입',9,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(156,15,'LEAVE','휴직',10,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(157,15,'RETURN','복직',11,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(158,15,'NEW_HIRE','신규입사',1,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(159,15,'TRANSFER','전보',2,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(160,15,'PROMOTION','승진',3,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(161,15,'POS_CHANGE','직급변경',4,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(162,15,'TITLE_CHANGE','직책변경',5,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(163,15,'TYPE_CHANGE','고용형태변경',6,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(164,15,'DISPATCH','파견',7,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(165,15,'TRANSFER_OUT','전출',8,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(166,15,'TRANSFER_IN','전입',9,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(167,15,'LEAVE','휴직',10,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(168,15,'RETURN','복직',11,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(169,15,'STATUS_CHANGE','상태변경',12,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(170,15,'COMPOUND','복합발령',13,1,'2026-07-03 01:33:13','2026-07-03 01:33:13'),(171,15,'RESIGN','퇴사',14,1,'2026-07-03 01:33:13','2026-07-03 01:33:13');
/*!40000 ALTER TABLE `common_code_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_calendars`
--

DROP TABLE IF EXISTS `custom_calendars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_calendars` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `color_code` varchar(20) NOT NULL DEFAULT '#4F6AFF',
  `creator_id` int NOT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_creator_active` (`creator_id`,`is_active`),
  CONSTRAINT `custom_calendars_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_calendars`
--

LOCK TABLES `custom_calendars` WRITE;
/*!40000 ALTER TABLE `custom_calendars` DISABLE KEYS */;
/*!40000 ALTER TABLE `custom_calendars` ENABLE KEYS */;
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
  `level_id` int DEFAULT NULL COMMENT 'org_levels.id',
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
  KEY `fk_dept_level` (`level_id`),
  CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_ibfk_2` FOREIGN KEY (`head_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dept_level` FOREIGN KEY (`level_id`) REFERENCES `org_levels` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,NULL,1,'(주)재밋','ZAEMIT',1,0,1,'2026-03-22 15:18:32','2026-07-03 01:24:12'),(2,1,2,'경영지원본부','MGT',2,1,1,'2026-03-22 15:18:32','2026-07-03 01:24:12'),(3,1,2,'기술개발본부','TECH',3,2,1,'2026-03-22 15:18:32','2026-07-03 01:24:12'),(4,1,2,'영업본부','SALES',4,3,1,'2026-03-22 15:18:32','2026-07-03 01:24:12'),(5,1,3,'경영지원팀','MGT-SUPPORT',NULL,0,1,'2026-03-22 15:18:32','2026-07-03 02:13:29'),(6,2,3,'인사팀','MGT-HR',NULL,2,1,'2026-03-22 15:18:32','2026-07-03 01:35:30'),(7,2,3,'재무회계팀','MGT-FIN',7,3,1,'2026-03-22 15:18:32','2026-07-03 01:24:12'),(8,3,3,'개발1팀','TECH-DEV1',8,1,1,'2026-03-22 15:18:32','2026-07-03 01:24:12'),(9,3,3,'개발2팀','TECH-DEV2',9,2,1,'2026-03-22 15:18:32','2026-07-03 01:24:12'),(10,3,3,'QA팀','TECH-QA',10,3,1,'2026-03-22 15:18:32','2026-07-03 01:24:12'),(11,4,3,'국내영업팀','SALES-DOM',11,1,1,'2026-03-22 15:18:32','2026-07-03 01:24:12'),(12,4,3,'해외영업팀','SALES-INT',12,2,1,'2026-03-22 15:18:32','2026-07-03 01:24:12'),(13,1,2,'전략기획본부','',NULL,4,1,'2026-04-03 14:20:40','2026-07-03 01:24:12'),(14,13,3,'전략지원','',NULL,1,1,'2026-04-03 14:21:00','2026-07-03 01:24:12'),(15,13,3,'기획','',NULL,2,1,'2026-04-03 14:21:06','2026-07-03 01:24:12'),(16,2,3,'해외영업','',NULL,4,1,'2026-04-15 13:59:55','2026-07-03 01:24:12'),(17,2,3,'해외영업','',NULL,5,1,'2026-04-15 14:00:14','2026-07-03 01:24:12'),(18,1,2,'해외영업','',NULL,5,1,'2026-04-15 14:00:33','2026-07-03 01:24:12');
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
  `doc_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT '요청 서류명',
  `category` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'general' COMMENT 'business_docs 탭 키',
  `description` text COLLATE utf8mb4_general_ci COMMENT '요청 상세 설명',
  `due_date` date DEFAULT NULL COMMENT '제출 기한',
  `status` enum('요청중','업로드완료','확인완료','취소') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '요청중',
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='세무 서류 요청';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_requests`
--

LOCK TABLES `doc_requests` WRITE;
/*!40000 ALTER TABLE `doc_requests` DISABLE KEYS */;
INSERT INTO `doc_requests` VALUES (1,1,1,'2025년 4분기 통장거래내역','general','국민은행 운영계좌 전체 내역 PDF','2026-03-10','요청중','2026-03-22 15:18:33',NULL),(2,1,1,'2025년 12월 급여대장','general','전직원 급여 지급 내역','2026-03-07','업로드완료','2026-03-22 15:18:33',NULL),(3,1,1,'사업자등록증 사본','general','최신 발급본','2026-03-15','확인완료','2026-03-22 15:18:33',NULL),(4,1,1,'2025년 부가세 신고용 매입세금계산서 목록','general','엑셀 또는 PDF 형식','2026-03-20','요청중','2026-03-22 15:18:33',NULL);
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
  `file_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `file_size` int unsigned DEFAULT NULL COMMENT 'bytes',
  `uploaded_by` int unsigned NOT NULL DEFAULT '1',
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `doc_uploads_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `doc_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='서류 업로드 파일';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_uploads`
--

LOCK TABLES `doc_uploads` WRITE;
/*!40000 ALTER TABLE `doc_uploads` DISABLE KEYS */;
INSERT INTO `doc_uploads` VALUES (1,2,'2025년_12월_급여대장.pdf','/uploads/docs/2_2025년_12월_급여대장.pdf',248000,1,'2026-07-03 02:43:01'),(2,3,'사업자등록증_사본.pdf','/uploads/docs/3_사업자등록증_사본.pdf',248000,1,'2026-07-03 02:43:01');
/*!40000 ALTER TABLE `doc_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_appointments`
--

DROP TABLE IF EXISTS `employee_appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT '대상 직원',
  `appointment_type` varchar(30) NOT NULL COMMENT '발령유형 (신규입사/전보/승진/직급변경/직책변경/고용형태변경/파견/전출/전입/휴직/복직/상태변경/복합발령/퇴사)',
  `appointment_date` date NOT NULL COMMENT '발령일 (시행일)',
  `appointment_no` varchar(50) DEFAULT NULL COMMENT '발령번호 (선택)',
  `source` varchar(10) NOT NULL DEFAULT 'auto' COMMENT '등록 경로 (auto=자동기록, manual=수동입력)',
  `prev_department_id` int DEFAULT NULL COMMENT '이전 부서 ID',
  `prev_department_name` varchar(100) DEFAULT NULL COMMENT '이전 부서명 (스냅샷)',
  `prev_position` varchar(50) DEFAULT NULL COMMENT '이전 직급',
  `prev_title` varchar(100) DEFAULT NULL COMMENT '이전 직책',
  `prev_employment_type` varchar(20) DEFAULT NULL COMMENT '이전 고용형태',
  `prev_employment_status` varchar(20) DEFAULT NULL COMMENT '이전 고용상태',
  `new_department_id` int DEFAULT NULL COMMENT '변경 부서 ID',
  `new_department_name` varchar(100) DEFAULT NULL COMMENT '변경 부서명 (스냅샷)',
  `new_position` varchar(50) DEFAULT NULL COMMENT '변경 직급',
  `new_title` varchar(100) DEFAULT NULL COMMENT '변경 직책',
  `new_employment_type` varchar(20) DEFAULT NULL COMMENT '변경 고용형태',
  `new_employment_status` varchar(20) DEFAULT NULL COMMENT '변경 고용상태',
  `reason` text COMMENT '발령 사유',
  `created_by` int DEFAULT NULL COMMENT '등록자 (employees.id)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appt_emp` (`employee_id`),
  KEY `idx_appt_date` (`appointment_date`),
  KEY `idx_appt_type` (`appointment_type`),
  CONSTRAINT `employee_appointments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_appointments`
--

LOCK TABLES `employee_appointments` WRITE;
/*!40000 ALTER TABLE `employee_appointments` DISABLE KEYS */;
INSERT INTO `employee_appointments` VALUES (1,1,'신규입사','2020-01-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,1,'(주)재밋','대표이사','CEO','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(2,2,'신규입사','2020-03-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,2,'경영지원본부','이사','경영지원본부장','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(3,3,'신규입사','2020-03-15',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,3,'기술개발본부','이사','CTO','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(4,4,'신규입사','2020-06-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,4,'영업본부','이사','영업본부장','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(5,5,'신규입사','2021-01-10',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,5,'경영지원팀','부장','경영지원팀장','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(6,6,'신규입사','2021-02-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,6,'인사팀','부장','인사팀장','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(7,7,'신규입사','2021-03-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,7,'재무회계팀','부장','재무회계팀장','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(8,8,'신규입사','2021-04-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,8,'개발1팀','부장','개발1팀장','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(9,9,'신규입사','2021-05-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,9,'개발2팀','과장','개발2팀장','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(10,10,'신규입사','2021-06-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,10,'QA팀','과장','QA팀장','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(11,11,'신규입사','2021-07-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,11,'국내영업팀','과장','국내영업팀장','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(12,12,'신규입사','2021-08-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,12,'해외영업팀','과장','해외영업팀장','정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(13,13,'신규입사','2022-01-15',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,5,'경영지원팀','대리',NULL,'정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(14,14,'신규입사','2022-03-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,6,'인사팀','대리',NULL,'정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(15,15,'신규입사','2022-04-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,8,'개발1팀','대리',NULL,'정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(16,16,'신규입사','2023-01-02',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,8,'개발1팀','사원',NULL,'정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(17,17,'신규입사','2022-06-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,9,'개발2팀','대리',NULL,'정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(18,18,'신규입사','2023-03-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,9,'개발2팀','사원',NULL,'정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(19,19,'신규입사','2022-07-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,10,'QA팀','대리',NULL,'정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(20,20,'신규입사','2022-09-01',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,11,'국내영업팀','대리',NULL,'정규직','재직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(21,21,'신규입사','2023-03-15',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,8,'개발1팀','대리',NULL,'정규직','휴직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(22,22,'신규입사','2024-01-10',NULL,'auto',NULL,NULL,NULL,NULL,NULL,NULL,6,'인사팀','사원',NULL,'정규직','휴직','시스템 자동 생성 (현재 기준 값)',NULL,'2026-07-03 01:24:12','2026-07-03 01:24:12');
/*!40000 ALTER TABLE `employee_appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_awards`
--

DROP TABLE IF EXISTS `employee_awards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_awards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `type` varchar(10) NOT NULL COMMENT '수상/징계',
  `discipline_level` varchar(20) DEFAULT NULL,
  `title` varchar(100) NOT NULL COMMENT '상명/징계명',
  `awarded_date` date NOT NULL COMMENT '일자',
  `follow_up_date` date DEFAULT NULL,
  `awarding_org` varchar(100) DEFAULT NULL COMMENT '수여기관/결정기관',
  `description` text COMMENT '사유/내용',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_award_emp` (`employee_id`),
  CONSTRAINT `employee_awards_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_awards`
--

LOCK TABLES `employee_awards` WRITE;
/*!40000 ALTER TABLE `employee_awards` DISABLE KEYS */;
INSERT INTO `employee_awards` VALUES (1,3,'수상',NULL,'제안왕','2025-12-20',NULL,'(주)재밋','제안왕 수상','2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,6,'수상',NULL,'분기 MVP','2024-12-20',NULL,'(주)재밋','분기 MVP 수상','2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,9,'수상',NULL,'고객만족 대상','2023-12-20',NULL,'(주)재밋','고객만족 대상 수상','2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,12,'수상',NULL,'장기근속 표창','2022-12-20',NULL,'(주)재밋','장기근속 표창 수상','2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,15,'수상',NULL,'연간 우수사원상','2025-12-20',NULL,'(주)재밋','연간 우수사원상 수상','2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,18,'수상',NULL,'제안왕','2024-12-20',NULL,'(주)재밋','제안왕 수상','2026-07-03 02:58:24','2026-07-03 02:58:24'),(7,21,'수상',NULL,'분기 MVP','2023-12-20',NULL,'(주)재밋','분기 MVP 수상','2026-07-03 02:58:24','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `employee_awards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_careers`
--

DROP TABLE IF EXISTS `employee_careers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_careers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `company_name` varchar(100) NOT NULL COMMENT '회사명',
  `department` varchar(100) DEFAULT NULL COMMENT '부서',
  `position` varchar(50) DEFAULT NULL COMMENT '직급/직책',
  `job_type` varchar(50) DEFAULT NULL,
  `employment_type` varchar(20) DEFAULT '정규직',
  `start_date` date NOT NULL COMMENT '입사일',
  `end_date` date DEFAULT NULL COMMENT '퇴사일 (NULL=재직중)',
  `is_current` tinyint(1) DEFAULT '0' COMMENT '현재 재직 여부',
  `leave_reason` varchar(200) DEFAULT NULL,
  `description` text COMMENT '담당 업무',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_career_emp` (`employee_id`),
  CONSTRAINT `employee_careers_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_careers`
--

LOCK TABLES `employee_careers` WRITE;
/*!40000 ALTER TABLE `employee_careers` DISABLE KEYS */;
INSERT INTO `employee_careers` VALUES (1,1,'네이버','경영지원본부','대리','백엔드 개발','정규직','2016-02-01','2020-02-28',0,'이직','백엔드 개발 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,2,'카카오','마케팅실','과장','프론트엔드 개발','정규직','2017-03-01','2021-03-28',0,'이직','프론트엔드 개발 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,3,'LG전자','영업본부','사원','인사운영','정규직','2018-04-01','2019-04-28',0,'이직','인사운영 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,4,'쿠팡','개발본부','대리','회계','정규직','2019-05-01','2020-05-28',0,'이직','회계 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,5,'우아한형제들','경영지원본부','과장','마케팅','정규직','2015-06-01','2021-06-28',0,'이직','마케팅 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,7,'당근마켓','영업본부','대리','디자인','정규직','2017-08-01','2020-08-28',0,'이직','디자인 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(7,8,'SK하이닉스','개발본부','과장','품질관리','정규직','2018-09-01','2021-09-28',0,'이직','품질관리 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(8,9,'현대자동차','경영지원본부','사원','고객지원','정규직','2019-01-01','2019-01-28',0,'이직','고객지원 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(9,10,'KT','마케팅실','대리','영업관리','정규직','2015-02-01','2020-02-28',0,'이직','영업관리 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(10,11,'CJ제일제당','영업본부','과장','백엔드 개발','정규직','2016-03-01','2021-03-28',0,'이직','백엔드 개발 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(11,13,'네이버','경영지원본부','대리','인사운영','정규직','2018-05-01','2020-05-28',0,'이직','인사운영 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(12,14,'카카오','마케팅실','과장','회계','정규직','2019-06-01','2021-06-28',0,'이직','회계 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(13,15,'LG전자','영업본부','사원','마케팅','정규직','2015-07-01','2019-07-28',0,'이직','마케팅 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(14,16,'쿠팡','개발본부','대리','기획','정규직','2016-08-01','2020-08-28',0,'이직','기획 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(15,17,'우아한형제들','경영지원본부','과장','디자인','정규직','2017-09-01','2021-09-28',0,'이직','디자인 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(16,19,'당근마켓','영업본부','대리','고객지원','정규직','2019-02-01','2020-02-28',0,'이직','고객지원 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(17,20,'SK하이닉스','개발본부','과장','영업관리','정규직','2015-03-01','2021-03-28',0,'이직','영업관리 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(18,21,'현대자동차','경영지원본부','사원','백엔드 개발','정규직','2016-04-01','2019-04-28',0,'이직','백엔드 개발 담당','2026-07-03 02:58:24','2026-07-03 02:58:24'),(19,22,'KT','마케팅실','대리','프론트엔드 개발','정규직','2017-05-01','2020-05-28',0,'이직','프론트엔드 개발 담당','2026-07-03 02:58:24','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `employee_careers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_certifications`
--

DROP TABLE IF EXISTS `employee_certifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_certifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `cert_name` varchar(100) NOT NULL COMMENT '자격증명',
  `issuing_org` varchar(100) NOT NULL COMMENT '발급기관',
  `cert_number` varchar(50) DEFAULT NULL COMMENT '자격증 번호',
  `cert_grade` varchar(50) DEFAULT NULL,
  `acquired_date` date NOT NULL COMMENT '취득일',
  `expiry_date` date DEFAULT NULL COMMENT '만료일 (NULL=무기한)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cert_emp` (`employee_id`),
  CONSTRAINT `employee_certifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_certifications`
--

LOCK TABLES `employee_certifications` WRITE;
/*!40000 ALTER TABLE `employee_certifications` DISABLE KEYS */;
INSERT INTO `employee_certifications` VALUES (1,2,'SQL 개발자(SQLD)','한국데이터산업진흥원','2020-000274',NULL,'2020-03-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,4,'사회조사분석사 2급','한국산업인력공단','2022-000548',NULL,'2022-05-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,6,'정보처리기사','한국산업인력공단','2019-000822',NULL,'2019-07-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,8,'SQL 개발자(SQLD)','한국데이터산업진흥원','2021-001096',NULL,'2021-09-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,10,'사회조사분석사 2급','한국산업인력공단','2018-001370',NULL,'2018-02-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,12,'정보처리기사','한국산업인력공단','2020-001644',NULL,'2020-04-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(7,14,'SQL 개발자(SQLD)','한국데이터산업진흥원','2022-001918',NULL,'2022-06-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(8,16,'사회조사분석사 2급','한국산업인력공단','2019-002192',NULL,'2019-08-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(9,18,'정보처리기사','한국산업인력공단','2021-002466',NULL,'2021-01-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(10,20,'SQL 개발자(SQLD)','한국데이터산업진흥원','2018-002740',NULL,'2018-03-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(11,22,'사회조사분석사 2급','한국산업인력공단','2020-003014',NULL,'2020-05-15',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `employee_certifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_change_requests`
--

DROP TABLE IF EXISTS `employee_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_change_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT '요청 직원',
  `status` enum('대기','승인','반려') NOT NULL DEFAULT '대기' COMMENT '처리 상태',
  `changes_json` json NOT NULL COMMENT '변경 내용 {"field":{"old":"...","new":"..."}, ...}',
  `reason` text COMMENT '변경 사유 (직원 입력)',
  `reject_reason` text COMMENT '반려 사유 (관리자 입력)',
  `reviewed_by` int DEFAULT NULL COMMENT '처리한 관리자 ID',
  `reviewed_at` datetime DEFAULT NULL COMMENT '처리 일시',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_ecr_emp_status` (`employee_id`,`status`),
  KEY `idx_ecr_status_date` (`status`,`created_at`),
  CONSTRAINT `employee_change_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_change_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_change_requests`
--

LOCK TABLES `employee_change_requests` WRITE;
/*!40000 ALTER TABLE `employee_change_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_educations`
--

DROP TABLE IF EXISTS `employee_educations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_educations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `school_name` varchar(100) NOT NULL COMMENT '학교명',
  `major` varchar(100) DEFAULT NULL COMMENT '전공',
  `minor` varchar(100) DEFAULT NULL,
  `degree` varchar(20) NOT NULL COMMENT '고졸/전문학사/학사/석사/박사',
  `school_type` varchar(30) DEFAULT NULL COMMENT '학교구분 (고등학교/대학교(2,3년)/대학교(4년)/대학원(석사)/대학원(박사))',
  `gpa` decimal(3,2) DEFAULT NULL COMMENT '학점',
  `gpa_scale` decimal(2,1) DEFAULT NULL COMMENT '학점 만점 (4.5 또는 4.0)',
  `start_date` date DEFAULT NULL COMMENT '입학일',
  `end_date` date DEFAULT NULL COMMENT '졸업일',
  `status` varchar(20) DEFAULT '졸업' COMMENT '졸업/재학/중퇴/수료/졸업예정',
  `description` text COMMENT '비고',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_edu_emp` (`employee_id`),
  CONSTRAINT `employee_educations_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_educations`
--

LOCK TABLES `employee_educations` WRITE;
/*!40000 ALTER TABLE `employee_educations` DISABLE KEYS */;
INSERT INTO `employee_educations` VALUES (1,1,'연세대학교','컴퓨터공학과',NULL,'학사','대학교(4년)',3.10,4.5,'2009-03-02','2013-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,2,'고려대학교','전자공학과',NULL,'학사','대학교(4년)',3.20,4.5,'2010-03-02','2014-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,3,'성균관대학교','산업공학과',NULL,'학사','대학교(4년)',3.30,4.5,'2011-03-02','2015-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,4,'한양대학교','회계학과',NULL,'학사','대학교(4년)',3.40,4.5,'2012-03-02','2016-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,5,'중앙대학교','시각디자인학과',NULL,'학사','대학교(4년)',3.50,4.5,'2013-03-02','2017-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,6,'경희대학교','국어국문학과',NULL,'학사','대학교(4년)',3.60,4.5,'2014-03-02','2018-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(7,7,'서강대학교','심리학과',NULL,'학사','대학교(4년)',3.70,4.5,'2015-03-02','2019-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(8,8,'부산대학교','경제학과',NULL,'학사','대학교(4년)',3.80,4.5,'2008-03-02','2012-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(9,9,'인하대학교','정보통신공학과',NULL,'학사','대학교(4년)',3.90,4.5,'2009-03-02','2013-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(10,10,'건국대학교','경영학과',NULL,'학사','대학교(4년)',4.00,4.5,'2010-03-02','2014-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(11,11,'동국대학교','컴퓨터공학과',NULL,'학사','대학교(4년)',4.10,4.5,'2011-03-02','2015-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(12,12,'서울대학교','전자공학과',NULL,'학사','대학교(4년)',4.20,4.5,'2012-03-02','2016-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(13,13,'연세대학교','산업공학과',NULL,'학사','대학교(4년)',4.30,4.5,'2013-03-02','2017-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(14,14,'고려대학교','회계학과',NULL,'학사','대학교(4년)',4.40,4.5,'2014-03-02','2018-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(15,15,'성균관대학교','시각디자인학과',NULL,'학사','대학교(4년)',3.00,4.5,'2015-03-02','2019-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(16,16,'한양대학교','국어국문학과',NULL,'학사','대학교(4년)',3.10,4.5,'2008-03-02','2012-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(17,17,'중앙대학교','심리학과',NULL,'학사','대학교(4년)',3.20,4.5,'2009-03-02','2013-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(18,18,'경희대학교','경제학과',NULL,'학사','대학교(4년)',3.30,4.5,'2010-03-02','2014-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(19,19,'서강대학교','정보통신공학과',NULL,'학사','대학교(4년)',3.40,4.5,'2011-03-02','2015-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(20,20,'부산대학교','경영학과',NULL,'학사','대학교(4년)',3.50,4.5,'2012-03-02','2016-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(21,21,'인하대학교','컴퓨터공학과',NULL,'학사','대학교(4년)',3.60,4.5,'2013-03-02','2017-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(22,22,'건국대학교','전자공학과',NULL,'학사','대학교(4년)',3.70,4.5,'2014-03-02','2018-02-25','졸업',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `employee_educations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_families`
--

DROP TABLE IF EXISTS `employee_families`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_families` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `relationship` varchar(20) NOT NULL COMMENT '관계 (배우자/자녀/부/모/형제 등)',
  `name` varchar(50) NOT NULL COMMENT '이름',
  `birth_date` date DEFAULT NULL COMMENT '생년월일',
  `phone` varchar(20) DEFAULT NULL COMMENT '연락처',
  `is_cohabitant` tinyint(1) DEFAULT '1' COMMENT '동거 여부',
  `is_dependent` tinyint(1) DEFAULT '0' COMMENT '부양가족 여부 (세금공제)',
  `is_health_dependent` tinyint(1) DEFAULT '0',
  `memo` varchar(200) DEFAULT NULL COMMENT '비고',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fam_emp` (`employee_id`),
  CONSTRAINT `employee_families_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_families`
--

LOCK TABLES `employee_families` WRITE;
/*!40000 ALTER TABLE `employee_families` DISABLE KEYS */;
INSERT INTO `employee_families` VALUES (1,1,'배우자','이서연','1986-02-02',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,2,'배우자','박지호','1987-03-03',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,2,'자녀','박도윤','2017-03-03',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,4,'배우자','정예준','1989-05-05',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,4,'자녀','정지호','2019-05-05',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,5,'배우자','강수아','1990-06-06',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(7,6,'배우자','조지우','1991-07-07',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(8,6,'자녀','조예준','2015-07-07',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(9,8,'배우자','장서준','1985-09-09',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(10,8,'자녀','장지우','2017-09-09',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(11,9,'배우자','임하윤','1986-10-10',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(12,10,'배우자','김도윤','1987-11-11',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(13,10,'자녀','김서준','2019-11-11',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(14,12,'배우자','박지호','1989-01-13',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(15,12,'자녀','박도윤','2015-01-13',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(16,13,'배우자','최하은','1990-02-14',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(17,14,'배우자','정예준','1991-03-15',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(18,14,'자녀','정지호','2017-03-15',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(19,16,'배우자','조지우','1985-05-17',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(20,16,'자녀','조예준','2019-05-17',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(21,17,'배우자','윤유진','1986-06-18',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(22,18,'배우자','장서준','1987-07-19',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(23,18,'자녀','장지우','2015-07-19',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(24,20,'배우자','김도윤','1989-09-21',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(25,20,'자녀','김서준','2017-09-21',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(26,21,'배우자','이서연','1990-10-22',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(27,22,'배우자','박지호','1991-11-23',NULL,1,0,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(28,22,'자녀','박도윤','2019-11-23',NULL,1,1,0,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `employee_families` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_languages`
--

DROP TABLE IF EXISTS `employee_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_languages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `language` varchar(50) NOT NULL COMMENT '언어 (영어, 일본어 등)',
  `level` varchar(20) NOT NULL COMMENT '초급/중급/고급/원어민',
  `test_type` varchar(20) DEFAULT NULL COMMENT '시험유형 (공인시험/회화/자격)',
  `test_name` varchar(50) DEFAULT NULL COMMENT '시험명 (TOEIC, JLPT 등)',
  `test_score` varchar(20) DEFAULT NULL COMMENT '점수/등급',
  `test_date` date DEFAULT NULL COMMENT '시험일',
  `validity_years` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lang_emp` (`employee_id`),
  CONSTRAINT `employee_languages_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_languages`
--

LOCK TABLES `employee_languages` WRITE;
/*!40000 ALTER TABLE `employee_languages` DISABLE KEYS */;
INSERT INTO `employee_languages` VALUES (1,1,'영어','중','TOEIC','TOEIC','713','2024-02-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,3,'영어','중','TOEIC','TOEIC','739','2023-04-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,4,'영어','중','TOEIC','TOEIC','752','2024-05-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,6,'영어','중','TOEIC','TOEIC','778','2023-07-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,7,'영어','중','TOEIC','TOEIC','791','2024-08-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,9,'영어','중상','TOEIC','TOEIC','817','2023-01-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(7,10,'영어','중상','TOEIC','TOEIC','830','2024-02-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(8,10,'일본어','중','JLPT','JLPT','N2','2024-07-05',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(9,12,'영어','중상','TOEIC','TOEIC','856','2023-04-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(10,13,'영어','중상','TOEIC','TOEIC','869','2024-05-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(11,15,'영어','중상','TOEIC','TOEIC','895','2023-07-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(12,15,'일본어','중','JLPT','JLPT','N2','2023-07-05',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(13,16,'영어','상','TOEIC','TOEIC','908','2024-08-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(14,18,'영어','상','TOEIC','TOEIC','934','2023-01-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(15,19,'영어','상','TOEIC','TOEIC','947','2024-02-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(16,21,'영어','중','TOEIC','TOEIC','723','2023-04-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(17,22,'영어','중','TOEIC','TOEIC','736','2024-05-10',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `employee_languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_military`
--

DROP TABLE IF EXISTS `employee_military`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_military` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `military_status` varchar(20) NOT NULL DEFAULT '해당없음' COMMENT '병역구분',
  `branch` varchar(20) DEFAULT NULL COMMENT '군별 (육군/해군/공군/해병대 등)',
  `branch_specialty` varchar(50) DEFAULT NULL,
  `rank_title` varchar(30) DEFAULT NULL COMMENT '계급',
  `enlist_date` date DEFAULT NULL COMMENT '입대일',
  `discharge_date` date DEFAULT NULL COMMENT '전역일',
  `discharge_type` varchar(20) DEFAULT NULL,
  `exemption_reason` varchar(100) DEFAULT NULL COMMENT '면제사유',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_military_emp` (`employee_id`),
  CONSTRAINT `employee_military_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_military`
--

LOCK TABLES `employee_military` WRITE;
/*!40000 ALTER TABLE `employee_military` DISABLE KEYS */;
INSERT INTO `employee_military` VALUES (1,1,'군필','해군',NULL,'병장','2009-02-05','2010-08-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,2,'군필','공군',NULL,'병장','2010-03-05','2011-09-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,3,'군필','의무경찰',NULL,'병장','2011-04-05','2012-10-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,4,'군필','육군',NULL,'병장','2012-05-05','2013-11-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,5,'비대상',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,6,'비대상',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(7,7,'군필','의무경찰',NULL,'병장','2009-08-05','2011-02-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(8,8,'군필','육군',NULL,'병장','2010-09-05','2012-03-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(9,9,'군필','해군',NULL,'병장','2011-01-05','2012-07-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(10,10,'비대상',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(11,11,'군필','의무경찰',NULL,'병장','2013-03-05','2014-09-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(12,12,'비대상',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(13,13,'비대상',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(14,14,'비대상',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(15,15,'군필','의무경찰',NULL,'병장','2011-07-05','2013-01-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(16,16,'군필','육군',NULL,'병장','2012-08-05','2014-02-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(17,17,'군필','해군',NULL,'병장','2013-09-05','2015-03-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(18,18,'군필','공군',NULL,'병장','2008-01-05','2009-07-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(19,19,'비대상',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(20,20,'군필','육군',NULL,'병장','2010-03-05','2011-09-05','만기전역',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(21,21,'비대상',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(22,22,'비대상',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `employee_military` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_skills`
--

DROP TABLE IF EXISTS `employee_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_skills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `skill_name` varchar(100) NOT NULL COMMENT '스킬명',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_skill_emp_name` (`employee_id`,`skill_name`),
  KEY `idx_skill_emp` (`employee_id`),
  CONSTRAINT `employee_skills_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_skills`
--

LOCK TABLES `employee_skills` WRITE;
/*!40000 ALTER TABLE `employee_skills` DISABLE KEYS */;
INSERT INTO `employee_skills` VALUES (1,1,'PowerPoint','2026-07-03 02:58:24'),(2,1,'React','2026-07-03 02:58:24'),(3,1,'회계','2026-07-03 02:58:24'),(4,2,'SQL','2026-07-03 02:58:24'),(5,2,'Figma','2026-07-03 02:58:24'),(6,2,'영어회화','2026-07-03 02:58:24'),(7,2,'PowerPoint','2026-07-03 02:58:24'),(8,3,'Python','2026-07-03 02:58:24'),(9,3,'포토샵','2026-07-03 02:58:24'),(10,4,'Java','2026-07-03 02:58:24'),(11,4,'프로젝트관리','2026-07-03 02:58:24'),(12,4,'카피라이팅','2026-07-03 02:58:24'),(13,5,'JavaScript','2026-07-03 02:58:24'),(14,5,'데이터분석','2026-07-03 02:58:24'),(15,5,'재무모델링','2026-07-03 02:58:24'),(16,5,'Java','2026-07-03 02:58:24'),(17,6,'React','2026-07-03 02:58:24'),(18,6,'회계','2026-07-03 02:58:24'),(19,7,'Figma','2026-07-03 02:58:24'),(20,7,'영어회화','2026-07-03 02:58:24'),(21,7,'PowerPoint','2026-07-03 02:58:24'),(22,8,'포토샵','2026-07-03 02:58:24'),(23,8,'ERP운영','2026-07-03 02:58:24'),(24,8,'SQL','2026-07-03 02:58:24'),(25,8,'Figma','2026-07-03 02:58:24'),(26,9,'프로젝트관리','2026-07-03 02:58:24'),(27,9,'카피라이팅','2026-07-03 02:58:24'),(28,10,'데이터분석','2026-07-03 02:58:24'),(29,10,'재무모델링','2026-07-03 02:58:24'),(30,10,'Java','2026-07-03 02:58:24'),(31,11,'회계','2026-07-03 02:58:24'),(32,11,'Excel','2026-07-03 02:58:24'),(33,11,'JavaScript','2026-07-03 02:58:24'),(34,11,'데이터분석','2026-07-03 02:58:24'),(35,12,'영어회화','2026-07-03 02:58:24'),(36,12,'PowerPoint','2026-07-03 02:58:24'),(37,13,'ERP운영','2026-07-03 02:58:24'),(38,13,'SQL','2026-07-03 02:58:24'),(39,13,'Figma','2026-07-03 02:58:24'),(40,14,'카피라이팅','2026-07-03 02:58:24'),(41,14,'Python','2026-07-03 02:58:24'),(42,14,'포토샵','2026-07-03 02:58:24'),(43,14,'ERP운영','2026-07-03 02:58:24'),(44,15,'재무모델링','2026-07-03 02:58:24'),(45,15,'Java','2026-07-03 02:58:24'),(46,16,'Excel','2026-07-03 02:58:24'),(47,16,'JavaScript','2026-07-03 02:58:24'),(48,16,'데이터분석','2026-07-03 02:58:24'),(49,17,'PowerPoint','2026-07-03 02:58:24'),(50,17,'React','2026-07-03 02:58:24'),(51,17,'회계','2026-07-03 02:58:24'),(52,17,'Excel','2026-07-03 02:58:24'),(53,18,'SQL','2026-07-03 02:58:24'),(54,18,'Figma','2026-07-03 02:58:24'),(55,19,'Python','2026-07-03 02:58:24'),(56,19,'포토샵','2026-07-03 02:58:24'),(57,19,'ERP운영','2026-07-03 02:58:24'),(58,20,'Java','2026-07-03 02:58:24'),(59,20,'프로젝트관리','2026-07-03 02:58:24'),(60,20,'카피라이팅','2026-07-03 02:58:24'),(61,20,'Python','2026-07-03 02:58:24'),(62,21,'JavaScript','2026-07-03 02:58:24'),(63,21,'데이터분석','2026-07-03 02:58:24'),(64,22,'React','2026-07-03 02:58:24'),(65,22,'회계','2026-07-03 02:58:24'),(66,22,'Excel','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `employee_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_no` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '사번',
  `department_id` int DEFAULT NULL,
  `affiliation` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '소속 (WEVEN, Zaemit 등)',
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `position` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '직급 (대표이사, 이사, 부장, 과장, 대리, 사원 등)',
  `rank_id` int DEFAULT NULL COMMENT 'hr_ranks.id',
  `title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '직책 (CEO, CTO, 팀장 등)',
  `duty_id` int DEFAULT NULL COMMENT 'hr_duties.id',
  `position_id` int DEFAULT NULL COMMENT 'hr_positions.id',
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '비밀번호 해시',
  `user_role` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user' COMMENT '역할',
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL COMMENT '생년월일',
  `gender` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '성별 (M/F)',
  `zipcode` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '우편번호',
  `address1` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '기본주소',
  `address2` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '상세주소',
  `employment_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '정규직' COMMENT '고용형태 (정규직, 계약직, 시간제, 파견직)',
  `employment_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '재직' COMMENT '고용상태 (재직, 휴직, 육아휴직, 퇴사)',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '부서 내 표시 순서 (작을수록 위)',
  `memo` text COLLATE utf8mb4_general_ci COMMENT '메모',
  `is_dept_head` tinyint(1) DEFAULT '0' COMMENT '부서장 여부',
  `profile_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `resign_date` date DEFAULT NULL COMMENT '퇴사일',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_employee_no` (`employee_no`),
  KEY `department_id` (`department_id`),
  KEY `fk_emp_rank` (`rank_id`),
  KEY `fk_emp_duty` (`duty_id`),
  KEY `fk_emp_position` (`position_id`),
  KEY `idx_dept_sort` (`department_id`,`sort_order`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_emp_duty` FOREIGN KEY (`duty_id`) REFERENCES `hr_duties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_emp_position` FOREIGN KEY (`position_id`) REFERENCES `hr_positions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_emp_rank` FOREIGN KEY (`rank_id`) REFERENCES `hr_ranks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'2020-001',1,'Zaemit','김대표','대표이사',1,'CEO',1,NULL,'ceo@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','admin','010-1234-5678','1975-03-15','M','06236','서울시 강남구 테헤란로 427','위워크 10층','정규직','재직',10,NULL,1,NULL,'2020-01-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(2,'2020-002',2,'Zaemit','이이사','이사',2,'경영지원본부장',8,NULL,'lee@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','admin','010-2345-6789','1978-07-22','M','06140','서울시 강남구 논현로 508','GS타워 13층','정규직','재직',10,NULL,1,NULL,'2020-03-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(3,'2020-003',3,'Zaemit','박이사','이사',2,'CTO',2,NULL,'park@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','admin','010-3456-7890','1985-11-03','M','13529','경기도 성남시 분당구 판교역로 235','에이치스퀘어 N동 8층','정규직','재직',10,NULL,1,NULL,'2020-03-15',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(4,'2020-004',4,'Zaemit','최이사','이사',2,'영업본부장',9,NULL,'choi@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','admin','010-4567-8901','1982-04-18','M','04538','서울시 중구 세종대로 110','서울시청 별관 5층','정규직','재직',10,NULL,1,NULL,'2020-06-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(5,'2021-005',5,'Zaemit','정부장','부장',3,'경영지원팀장',10,NULL,'jung@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-5678-9012','1990-09-12','F','06194','서울시 강남구 선릉로 525','삼성2동 현대아파트 103동 702호','정규직','재직',10,NULL,0,NULL,'2021-01-10',NULL,1,'2026-03-22 15:18:32','2026-07-03 02:13:29'),(6,'2021-006',6,'Zaemit','한부장','부장',3,'인사팀장',11,NULL,'han@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-6789-0123','1988-02-28','F','04104','서울시 마포구 월드컵북로 396','상암 누리꿈스퀘어 7층','정규직','재직',10,NULL,0,NULL,'2021-02-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:35:30'),(7,'2021-007',7,'Zaemit','오부장','부장',3,'재무회계팀장',12,NULL,'oh@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-7890-1234','1980-06-05','M','07236','서울시 영등포구 의사당대로 1','여의도 파크원 1401호','정규직','재직',10,NULL,1,NULL,'2021-03-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(8,'2021-008',8,'Zaemit','강부장','부장',3,'개발1팀장',13,NULL,'kang@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-8901-2345','1992-01-14','M','13494','경기도 성남시 분당구 대왕판교로 660','유스페이스 A동 12층','정규직','재직',10,NULL,1,NULL,'2021-04-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(9,'2021-009',9,'Zaemit','윤과장','과장',5,'개발2팀장',14,NULL,'yoon@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-9012-3456','1993-08-30','M','16514','경기도 수원시 영통구 광교중앙로 170','광교 엘포레 209동 1503호','정규직','재직',10,NULL,1,NULL,'2021-05-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(10,'2021-010',10,'Zaemit','임과장','과장',5,'QA팀장',15,NULL,'lim@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-0123-4567','1991-12-07','F','06035','서울시 강남구 가로수길 53','신사동 한진타운 302호','정규직','재직',10,NULL,1,NULL,'2021-06-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(11,'2021-011',11,'Zaemit','서과장','과장',5,'국내영업팀장',16,NULL,'seo@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-1111-2222','1987-05-25','M','05510','서울시 송파구 올림픽로 300','잠실엘스 103동 1204호','정규직','재직',10,NULL,1,NULL,'2021-07-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(12,'2021-012',12,'Zaemit','류과장','과장',5,'해외영업팀장',17,NULL,'ryu@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-3333-4444','1989-10-19','F','03925','서울시 마포구 양화로 45','메세나폴리스 1012호','정규직','재직',10,NULL,1,NULL,'2021-08-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(13,'2022-013',5,'Zaemit','김대리','대리',6,NULL,NULL,NULL,'kimm@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-5555-6666','1994-03-08','F','06581','서울시 서초구 반포대로 45','래미안 퍼스티지 502동 1801호','정규직','재직',20,NULL,0,NULL,'2022-01-15',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(14,'2022-014',6,'Zaemit','이대리','대리',6,NULL,NULL,NULL,'leehr@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-7777-8888','1992-07-16','F','04378','서울시 용산구 이태원로 200','한남더힐 A동 901호','정규직','재직',20,NULL,0,NULL,'2022-03-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(15,'2022-015',8,'Zaemit','박대리','대리',6,NULL,NULL,NULL,'parkfe@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-9999-0000','1995-11-21','M','13561','경기도 성남시 분당구 정자일로 95','네이버 그린팩토리 옆 오피스텔 801호','정규직','재직',30,NULL,0,NULL,'2022-04-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(16,'2023-016',8,'Zaemit','송사원','사원',8,NULL,NULL,NULL,'songbe@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-1212-3434','1996-04-09','M','16942','경기도 용인시 기흥구 보정동 1189','죽전역 자이 아파트 305동 1202호','정규직','재직',40,NULL,0,NULL,'2023-01-02',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(17,'2022-017',9,'Zaemit','조대리','대리',6,NULL,NULL,NULL,'jofs@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-5656-7878','1993-06-27','M','12925','경기도 하남시 미사강변중앙로 190','미사역 파라곤 112동 403호','정규직','재직',20,NULL,0,NULL,'2022-06-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(18,'2023-018',9,'Zaemit','황사원','사원',8,NULL,NULL,NULL,'hwangmb@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-9090-1212','1997-02-14','M','05345','서울시 강동구 천호대로 1077','래미안 솔베뉴 305동 801호','정규직','재직',40,NULL,0,NULL,'2023-03-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(19,'2022-019',10,'Zaemit','문대리','대리',6,NULL,NULL,NULL,'moonqa@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-3434-5656','1994-09-03','F','08378','서울시 구로구 디지털로 300','G밸리 비즈타워 902호','정규직','재직',20,NULL,0,NULL,'2022-07-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(20,'2022-020',11,'Zaemit','배대리','대리',6,NULL,NULL,NULL,'baes@zaemit.com','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-7878-9090','1991-01-30','M','07299','서울시 영등포구 여의대방로65길 20','신길뉴타운 e편한세상 108동 1503호','정규직','재직',20,NULL,0,NULL,'2022-09-01',NULL,1,'2026-03-22 15:18:32','2026-07-03 01:33:13'),(21,'2023-021',8,NULL,'노대리','대리',6,NULL,NULL,NULL,'noh@zaemit.kr','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-5555-1001',NULL,NULL,NULL,NULL,NULL,'정규직','휴직',20,NULL,0,NULL,'2023-03-15',NULL,1,'2026-04-02 18:21:56','2026-07-03 01:33:13'),(22,'2024-022',6,NULL,'심사원','사원',8,NULL,NULL,NULL,'sim@zaemit.kr','$2y$10$6YvMJgid.bXTxxD2ZhciA.n6iQaThm2YyCuUOE0f4zUa3vEdT13GG','user','010-5555-1002',NULL,NULL,NULL,NULL,NULL,'정규직','휴직',30,NULL,0,NULL,'2024-01-10',NULL,1,'2026-04-02 18:21:56','2026-07-03 01:33:13'),(23,NULL,9,NULL,'장주임','주임',7,NULL,NULL,NULL,'jang@zaemit.kr',NULL,'user','010-5555-2001',NULL,NULL,NULL,NULL,NULL,'정규직','퇴사',30,NULL,0,NULL,'2022-06-01','2026-03-31',0,'2026-04-02 18:21:56','2026-07-03 02:59:40'),(24,NULL,11,NULL,'구사원','사원',8,NULL,NULL,NULL,'gu@zaemit.kr',NULL,'user','010-5555-2002',NULL,NULL,NULL,NULL,NULL,'계약직','퇴사',30,NULL,0,NULL,'2023-09-01','2026-05-31',0,'2026-04-02 18:21:56','2026-07-03 02:59:40'),(25,NULL,7,NULL,'탁대리','대리',6,NULL,NULL,NULL,'tak@zaemit.kr',NULL,'user','010-5555-2003',NULL,NULL,NULL,NULL,NULL,'정규직','퇴사',20,NULL,0,NULL,'2021-04-20','2026-06-30',0,'2026-04-02 18:21:56','2026-07-03 02:59:40');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` int NOT NULL AUTO_INCREMENT,
  `year` smallint NOT NULL,
  `holiday_date` date NOT NULL,
  `name` varchar(50) NOT NULL COMMENT '공휴일 명칭',
  `type` enum('법정','대체','임시') NOT NULL DEFAULT '법정',
  `created_by` int DEFAULT NULL COMMENT 'employees.id (임시공휴일 등록자)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_date` (`holiday_date`),
  KEY `idx_year` (`year`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='공휴일';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES (1,2025,'2025-01-01','신정','법정',NULL,'2026-07-03 01:24:10'),(2,2025,'2025-01-28','설날 전날','법정',NULL,'2026-07-03 01:24:10'),(3,2025,'2025-01-29','설날','법정',NULL,'2026-07-03 01:24:10'),(4,2025,'2025-01-30','설날 다음날','법정',NULL,'2026-07-03 01:24:10'),(5,2025,'2025-03-01','삼일절','법정',NULL,'2026-07-03 01:24:10'),(6,2025,'2025-05-05','어린이날','법정',NULL,'2026-07-03 01:24:10'),(7,2025,'2025-05-06','석가탄신일','법정',NULL,'2026-07-03 01:24:10'),(8,2025,'2025-06-06','현충일','법정',NULL,'2026-07-03 01:24:10'),(9,2025,'2025-08-15','광복절','법정',NULL,'2026-07-03 01:24:10'),(10,2025,'2025-10-03','개천절','법정',NULL,'2026-07-03 01:24:10'),(11,2025,'2025-10-05','추석 전날','법정',NULL,'2026-07-03 01:24:10'),(12,2025,'2025-10-06','추석','법정',NULL,'2026-07-03 01:24:10'),(13,2025,'2025-10-07','추석 다음날','법정',NULL,'2026-07-03 01:24:10'),(14,2025,'2025-10-08','대체공휴일(추석)','대체',NULL,'2026-07-03 01:24:10'),(15,2025,'2025-10-09','한글날','법정',NULL,'2026-07-03 01:24:10'),(16,2025,'2025-12-25','성탄절','법정',NULL,'2026-07-03 01:24:10'),(17,2026,'2026-01-01','신정','법정',NULL,'2026-07-03 01:24:10'),(18,2026,'2026-02-16','설날 전날','법정',NULL,'2026-07-03 01:24:10'),(19,2026,'2026-02-17','설날','법정',NULL,'2026-07-03 01:24:10'),(20,2026,'2026-02-18','설날 다음날','법정',NULL,'2026-07-03 01:24:10'),(21,2026,'2026-03-01','삼일절','법정',NULL,'2026-07-03 01:24:10'),(22,2026,'2026-03-02','대체공휴일(삼일절)','대체',NULL,'2026-07-03 01:24:10'),(23,2026,'2026-05-05','어린이날','법정',NULL,'2026-07-03 01:24:10'),(24,2026,'2026-05-24','석가탄신일','법정',NULL,'2026-07-03 01:24:10'),(25,2026,'2026-05-25','대체공휴일(석가탄신일)','대체',NULL,'2026-07-03 01:24:10'),(26,2026,'2026-06-06','현충일','법정',NULL,'2026-07-03 01:24:10'),(27,2026,'2026-08-15','광복절','법정',NULL,'2026-07-03 01:24:10'),(28,2026,'2026-09-24','추석 전날','법정',NULL,'2026-07-03 01:24:10'),(29,2026,'2026-09-25','추석','법정',NULL,'2026-07-03 01:24:10'),(30,2026,'2026-09-26','추석 다음날','법정',NULL,'2026-07-03 01:24:10'),(31,2026,'2026-10-03','개천절','법정',NULL,'2026-07-03 01:24:10'),(32,2026,'2026-10-09','한글날','법정',NULL,'2026-07-03 01:24:10'),(33,2026,'2026-12-25','성탄절','법정',NULL,'2026-07-03 01:24:10'),(34,2027,'2027-01-01','신정','법정',NULL,'2026-07-03 01:24:10'),(35,2027,'2027-02-05','설날 전날','법정',NULL,'2026-07-03 01:24:10'),(36,2027,'2027-02-06','설날','법정',NULL,'2026-07-03 01:24:10'),(37,2027,'2027-02-07','설날 다음날','법정',NULL,'2026-07-03 01:24:10'),(38,2027,'2027-02-08','대체공휴일(설날)','대체',NULL,'2026-07-03 01:24:10'),(39,2027,'2027-03-01','삼일절','법정',NULL,'2026-07-03 01:24:10'),(40,2027,'2027-05-05','어린이날','법정',NULL,'2026-07-03 01:24:10'),(41,2027,'2027-05-13','석가탄신일','법정',NULL,'2026-07-03 01:24:10'),(42,2027,'2027-06-06','현충일','법정',NULL,'2026-07-03 01:24:10'),(43,2027,'2027-06-07','대체공휴일(현충일)','대체',NULL,'2026-07-03 01:24:10'),(44,2027,'2027-08-15','광복절','법정',NULL,'2026-07-03 01:24:10'),(45,2027,'2027-08-16','대체공휴일(광복절)','대체',NULL,'2026-07-03 01:24:10'),(46,2027,'2027-09-14','추석 전날','법정',NULL,'2026-07-03 01:24:10'),(47,2027,'2027-09-15','추석','법정',NULL,'2026-07-03 01:24:10'),(48,2027,'2027-09-16','추석 다음날','법정',NULL,'2026-07-03 01:24:10'),(49,2027,'2027-10-03','개천절','법정',NULL,'2026-07-03 01:24:10'),(50,2027,'2027-10-04','대체공휴일(개천절)','대체',NULL,'2026-07-03 01:24:10'),(51,2027,'2027-10-09','한글날','법정',NULL,'2026-07-03 01:24:10'),(52,2027,'2027-12-25','성탄절','법정',NULL,'2026-07-03 01:24:10');
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
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
  `sync_type` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'sales_invoice, purchase_invoice 등',
  `sync_count` int unsigned NOT NULL DEFAULT '0' COMMENT '동기화 건수',
  `status` enum('성공','실패','진행중') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '성공',
  `message` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='홈택스 동기화 이력';
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
-- Table structure for table `hr_duties`
--

DROP TABLE IF EXISTS `hr_duties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_duties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '직책명 (CEO, 본부장, 팀장 등)',
  `code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '코드 (CEO, HEAD, TL 등)',
  `tier` int NOT NULL DEFAULT '0' COMMENT '등급 그룹 (0=미분류)',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_duties`
--

LOCK TABLES `hr_duties` WRITE;
/*!40000 ALTER TABLE `hr_duties` DISABLE KEYS */;
INSERT INTO `hr_duties` VALUES (1,'CEO','CEO',0,1,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(2,'CTO','CTO',0,2,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(3,'CFO','CFO',0,3,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(4,'COO','COO',0,4,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(5,'본부장','HEAD',0,5,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(6,'팀장','TL',0,6,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(7,'파트장','PL',0,7,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(8,'경영지원본부장',NULL,0,100,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(9,'영업본부장',NULL,0,101,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(10,'경영지원팀장',NULL,0,102,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(11,'인사팀장',NULL,0,103,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(12,'재무회계팀장',NULL,0,104,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(13,'개발1팀장',NULL,0,105,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(14,'개발2팀장',NULL,0,106,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(15,'QA팀장',NULL,0,107,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(16,'국내영업팀장',NULL,0,108,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(17,'해외영업팀장',NULL,0,109,1,'2026-07-03 01:24:12','2026-07-03 01:24:12');
/*!40000 ALTER TABLE `hr_duties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_positions`
--

DROP TABLE IF EXISTS `hr_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_positions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '직위명 (수석연구원, 선임컨설턴트 등)',
  `code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tier` int NOT NULL DEFAULT '0' COMMENT '등급 그룹 (0=미분류)',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_positions`
--

LOCK TABLES `hr_positions` WRITE;
/*!40000 ALTER TABLE `hr_positions` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_ranks`
--

DROP TABLE IF EXISTS `hr_ranks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_ranks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '직급명 (사원, 대리, 과장 등)',
  `code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '코드 (STF, AM, MGR 등)',
  `tier` int NOT NULL DEFAULT '0' COMMENT '등급 그룹 (0=미분류)',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '서열 (1=최고위)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_ranks`
--

LOCK TABLES `hr_ranks` WRITE;
/*!40000 ALTER TABLE `hr_ranks` DISABLE KEYS */;
INSERT INTO `hr_ranks` VALUES (1,'대표이사','CEO',0,1,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(2,'이사','DIR',0,2,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(3,'부장','GM',0,3,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(4,'차장','DGM',0,4,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(5,'과장','MGR',0,5,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(6,'대리','AM',0,6,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(7,'주임','SR',0,7,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(8,'사원','STF',0,8,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(9,'인턴','INT',0,9,1,'2026-07-03 01:24:12','2026-07-03 01:24:12');
/*!40000 ALTER TABLE `hr_ranks` ENABLE KEYS */;
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
  `version` int DEFAULT '1' COMMENT '怨꾩빟 媛깆떊 ?? 踰꾩쟾 利앷??',
  `company_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `company_ceo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `company_address` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_bizno` varchar(14) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '?ъ뾽?먮벑濡앸쾲??',
  `contract_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'permanent' COMMENT 'permanent/fixed/parttime',
  `contract_start` date DEFAULT NULL,
  `contract_end` date DEFAULT NULL COMMENT 'permanent?대㈃ NULL',
  `job_description` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '醫낆궗?낅Т',
  `workplace` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '洹쇰Т?μ냼',
  `work_start` time DEFAULT '09:00:00',
  `work_end` time DEFAULT '18:00:00',
  `break_start` time DEFAULT '12:00:00',
  `break_end` time DEFAULT '13:00:00',
  `work_days` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '洹쇰Т?붿씪',
  `weekly_holiday` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `annual_leave` varchar(200) COLLATE utf8mb4_general_ci DEFAULT '',
  `base_pay` int DEFAULT '0' COMMENT '湲곕낯湲?(??)',
  `meal_allowance` int DEFAULT '0' COMMENT '?앸??',
  `car_allowance` int DEFAULT '0' COMMENT '李⑤웾吏??먮퉬',
  `child_allowance` int DEFAULT '0' COMMENT '?≪븘?섎떦',
  `extra_pay_1` int DEFAULT '0' COMMENT '異붽???섎떦1',
  `extra_pay_2` int DEFAULT '0' COMMENT '異붽???섎떦2',
  `extra_pay_3` int DEFAULT '0' COMMENT '異붽???섎떦3',
  `monthly_total` int DEFAULT '0' COMMENT '?? 湲됱뿬 ?⑷퀎',
  `annual_total` int DEFAULT '0' COMMENT '?곕큺',
  `pay_day` int DEFAULT '25' COMMENT '留ㅼ썡 吏?湲됱씪',
  `pay_method` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'transfer' COMMENT 'transfer/cash/other',
  `ins_pension` tinyint(1) DEFAULT '1' COMMENT '援???곌툑',
  `ins_health` tinyint(1) DEFAULT '1' COMMENT '嫄닿컯蹂댄뿕',
  `ins_employment` tinyint(1) DEFAULT '1' COMMENT '怨좎슜蹂댄뿕',
  `ins_industrial` tinyint(1) DEFAULT '1' COMMENT '?곗옱蹂댄뿕',
  `retirement_pay` tinyint(1) DEFAULT '1' COMMENT '1=?곸슜, 0=誘몄쟻??',
  `probation` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '3' COMMENT '0/1/3/6 (媛쒖썡)',
  `additional_terms` text COLLATE utf8mb4_general_ci COMMENT '湲고?? 洹쇰줈議곌굔',
  `signed_at` datetime DEFAULT NULL COMMENT '泥닿껐?꾨즺 ?쒖젏',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_status` (`contract_status`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `labor_contracts`
--

LOCK TABLES `labor_contracts` WRITE;
/*!40000 ALTER TABLE `labor_contracts` DISABLE KEYS */;
INSERT INTO `labor_contracts` VALUES (1,1,'signed',1,'주식회사 재밋','송승환',NULL,NULL,'permanent','2020-01-01',NULL,NULL,NULL,'09:00:00','18:00:00','12:00:00','13:00:00','월~금','매주 토요일, 일요일','근로기준법에 의한 연차유급휴가',5000000,200000,300000,0,0,0,0,5500000,66000000,25,'transfer',1,1,1,1,1,'3',NULL,'2026-04-06 11:08:37','2026-04-06 11:08:16','2026-04-06 11:08:37'),(2,2,'signed',1,'주식회사 재밋','송승환',NULL,NULL,'permanent','2021-03-01',NULL,NULL,NULL,'09:00:00','18:00:00','12:00:00','13:00:00','월~금','매주 토요일, 일요일','근로기준법에 의한 연차유급휴가',4500000,200000,0,0,0,0,0,4700000,56400000,25,'transfer',1,1,1,1,1,'3',NULL,'2026-04-06 11:35:26','2026-04-06 11:08:33','2026-04-06 11:35:26'),(3,4,'signed',1,'주식회사 재밋','송승환','서울특별시 마포구 성암로 330 첨단산업센터 319호',NULL,'permanent','2020-06-01',NULL,'영업','(갑)사업장','09:00:00','18:00:00','12:00:00','13:00:00','월요일부터 금요일','일요일','근로기준법에 의한 연차유급휴가',4500000,200000,300000,0,0,0,0,5000000,60000000,25,'transfer',1,1,1,1,1,'3',NULL,'2026-04-15 14:04:36','2026-04-15 14:04:36','2026-04-15 14:04:36'),(4,3,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2020-03-15',NULL,'기술개발본부 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',6500000,200000,300000,0,0,0,0,7000000,84000000,25,'transfer',1,1,1,1,1,'3',NULL,'2020-03-15 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(5,5,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2021-01-10',NULL,'경영지원팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',4500000,200000,200000,0,0,0,0,4900000,58800000,25,'transfer',1,1,1,1,1,'3',NULL,'2021-01-10 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(6,6,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2021-02-01',NULL,'인사팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',4500000,200000,200000,0,0,0,0,4900000,58800000,25,'transfer',1,1,1,1,1,'3',NULL,'2021-02-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(7,7,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2021-03-01',NULL,'재무회계팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',4500000,200000,200000,0,0,0,0,4900000,58800000,25,'transfer',1,1,1,1,1,'3',NULL,'2021-03-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(8,8,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2021-04-01',NULL,'개발1팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',4500000,200000,200000,0,0,0,0,4900000,58800000,25,'transfer',1,1,1,1,1,'3',NULL,'2021-04-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(9,9,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2021-05-01',NULL,'개발2팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',3500000,200000,0,0,0,0,0,3700000,44400000,25,'transfer',1,1,1,1,1,'3',NULL,'2021-05-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(10,10,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2021-06-01',NULL,'QA팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',3500000,200000,0,0,0,0,0,3700000,44400000,25,'transfer',1,1,1,1,1,'3',NULL,'2021-06-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(11,11,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2021-07-01',NULL,'국내영업팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',3500000,200000,0,0,0,0,0,3700000,44400000,25,'transfer',1,1,1,1,1,'3',NULL,'2021-07-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(12,12,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2021-08-01',NULL,'해외영업팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',3500000,200000,0,0,0,0,0,3700000,44400000,25,'transfer',1,1,1,1,1,'3',NULL,'2021-08-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(13,13,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2022-01-15',NULL,'경영지원팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2800000,200000,0,0,0,0,0,3000000,36000000,25,'transfer',1,1,1,1,1,'3',NULL,'2022-01-15 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(14,14,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2022-03-01',NULL,'인사팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2800000,200000,0,0,0,0,0,3000000,36000000,25,'transfer',1,1,1,1,1,'3',NULL,'2022-03-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(15,15,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2022-04-01',NULL,'개발1팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2800000,200000,0,0,0,0,0,3000000,36000000,25,'transfer',1,1,1,1,1,'3',NULL,'2022-04-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(16,16,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2023-01-02',NULL,'개발1팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2500000,200000,0,0,0,0,0,2700000,32400000,25,'transfer',1,1,1,1,1,'3',NULL,'2023-01-02 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(17,17,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2022-06-01',NULL,'개발2팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2700000,200000,0,0,0,0,0,2900000,34800000,25,'transfer',1,1,1,1,1,'3',NULL,'2022-06-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(18,18,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2023-03-01',NULL,'개발2팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2500000,200000,0,0,0,0,0,2700000,32400000,25,'transfer',1,1,1,1,1,'3',NULL,'2023-03-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(19,19,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2022-07-01',NULL,'QA팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2700000,200000,0,0,0,0,0,2900000,34800000,25,'transfer',1,1,1,1,1,'3',NULL,'2022-07-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(20,20,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2022-09-01',NULL,'국내영업팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2800000,200000,0,0,0,0,0,3000000,36000000,25,'transfer',1,1,1,1,1,'3',NULL,'2022-09-01 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(21,21,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2023-03-15',NULL,'개발1팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2500000,200000,0,0,0,0,0,2700000,32400000,25,'transfer',1,1,1,1,1,'3',NULL,'2023-03-15 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(22,22,'signed',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2024-01-10',NULL,'인사팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2500000,200000,0,0,0,0,0,2700000,32400000,25,'transfer',1,1,1,1,1,'3',NULL,'2024-01-10 09:00:00','2026-06-26 11:16:08','2026-06-26 11:16:08'),(23,23,'none',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2022-06-01',NULL,'개발2팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2500000,200000,0,0,0,0,0,2700000,32400000,25,'transfer',1,1,1,1,1,'3',NULL,NULL,'2026-06-26 11:16:08','2026-06-26 11:16:08'),(24,24,'none',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','fixed','2023-09-01','2025-09-01','국내영업팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2500000,200000,0,0,0,0,0,2700000,32400000,25,'transfer',1,1,1,1,1,'3',NULL,NULL,'2026-06-26 11:16:08','2026-06-26 11:16:08'),(25,25,'none',1,'(주)재밋','김대표','서울특별시 강남구 테헤란로 123','123-45-67890','permanent','2021-04-20',NULL,'재무회계팀 담당 업무','서울특별시 강남구 테헤란로 123','09:00:00','18:00:00','12:00:00','13:00:00','월화수목금','일요일','연차휴가 15일 (근로기준법 적용)',2500000,200000,0,0,0,0,0,2700000,32400000,25,'transfer',1,1,1,1,1,'3',NULL,NULL,'2026-06-26 11:16:08','2026-06-26 11:16:08');
/*!40000 ALTER TABLE `labor_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `labor_rules`
--

DROP TABLE IF EXISTS `labor_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `labor_rules` (
  `section_idx` tinyint unsigned NOT NULL,
  `article_num` tinyint unsigned NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`section_idx`,`article_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `labor_rules`
--

LOCK TABLES `labor_rules` WRITE;
/*!40000 ALTER TABLE `labor_rules` DISABLE KEYS */;
INSERT INTO `labor_rules` VALUES (1,1,'이 규칙은 (주)재밋(이하 \"회사\")의 근로자 복무에 관한 사항과 근로조건을 규정하여 노사 간의 질서를 유지하고 업무의 능률을 향상시킴을 목적으로 한다.','2026-06-26 02:16:08'),(1,2,'이 규칙은 회사에 재직하는 모든 근로자에게 적용한다. 단, 별도의 근로계약을 체결한 임원은 그 계약에 따른다.','2026-06-26 02:16:08'),(2,3,'회사는 결원 또는 사업 확장 등의 필요에 따라 공개채용 또는 특별채용의 방식으로 근로자를 채용한다.','2026-06-26 02:16:08'),(2,4,'신규 채용된 근로자에 대하여는 입사일부터 3개월간 수습기간을 둘 수 있으며, 이 기간 중 성적이 불량한 자는 채용을 취소할 수 있다.','2026-06-26 02:16:08'),(3,5,'근무시간은 1일 8시간, 1주 40시간으로 한다. 시업 시각은 09:00, 종업 시각은 18:00로 한다.','2026-06-26 02:16:08'),(3,6,'근무 중 12:00부터 13:00까지 1시간의 휴게시간을 부여한다.','2026-06-26 02:16:08'),(3,7,'주휴일은 일요일로 하며, 근로자의 날(5월 1일)과 관공서 공휴일을 유급휴일로 한다.','2026-06-26 02:16:08'),(4,8,'1년간 80% 이상 출근한 근로자에게 15일의 연차유급휴가를 부여한다. 계속 근로기간이 1년 미만인 경우 1개월 개근 시 1일의 유급휴가를 부여한다.','2026-06-26 02:16:08'),(4,9,'결혼(본인 5일), 배우자 출산(10일), 부모·배우자 사망(5일), 자녀 사망(3일), 형제자매 사망(1일)의 경조사 휴가를 부여한다.','2026-06-26 02:16:08'),(5,10,'임금은 기본급, 직책수당, 식대, 차량지원비 등으로 구성하며, 매월 25일(휴일인 경우 전날)에 근로자 명의 계좌로 지급한다.','2026-06-26 02:16:08'),(5,11,'회사는 근로자퇴직급여 보장법에 따라 퇴직급여제도를 운영한다.','2026-06-26 02:16:08'),(6,12,'근로자는 직무에 성실히 임하고, 업무상 취득한 비밀을 누설하지 않으며, 회사 자산을 사적으로 이용하지 않아야 한다.','2026-06-26 02:16:08'),(6,13,'직장 내 괴롭힘 및 성희롱 행위는 엄격히 금지하며, 위반 시 관계 법령에 따라 처분한다.','2026-06-26 02:16:08'),(7,14,'징계의 종류는 경고, 감봉, 정직, 강등, 해고로 한다.','2026-06-26 02:16:08'),(7,15,'무단결근 3일 이상, 업무 태만, 금품 수수, 기밀 누설, 성희롱·직장 내 괴롭힘 등의 행위는 징계 사유로 한다.','2026-06-26 02:16:08');
/*!40000 ALTER TABLE `labor_rules` ENABLE KEYS */;
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
-- Table structure for table `leave_adjustments`
--

DROP TABLE IF EXISTS `leave_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_adjustments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'employees.id',
  `year` smallint NOT NULL COMMENT '귀속년도',
  `adjust_type` enum('add','deduct') NOT NULL COMMENT '추가/차감',
  `adjust_days` decimal(4,1) NOT NULL COMMENT '조정일수 (양수)',
  `reason` varchar(200) NOT NULL COMMENT '조정 사유',
  `category` varchar(30) DEFAULT NULL COMMENT '분류 (포상/이월/보정/기타)',
  `created_by` int DEFAULT NULL COMMENT '등록자 employees.id',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emp_year` (`employee_id`,`year`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='연차 수동 조정 이력';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_adjustments`
--

LOCK TABLES `leave_adjustments` WRITE;
/*!40000 ALTER TABLE `leave_adjustments` DISABLE KEYS */;
INSERT INTO `leave_adjustments` VALUES (1,5,2026,'add',1.0,'우수사원 포상 연차','포상',1,'2026-07-03 02:58:24'),(2,10,2026,'add',1.0,'우수사원 포상 연차','포상',1,'2026-07-03 02:58:24'),(3,15,2026,'add',1.0,'우수사원 포상 연차','포상',1,'2026-07-03 02:58:24'),(4,20,2026,'add',1.0,'우수사원 포상 연차','포상',1,'2026-07-03 02:58:24');
/*!40000 ALTER TABLE `leave_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_carryovers`
--

DROP TABLE IF EXISTS `leave_carryovers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_carryovers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'employees.id',
  `from_year` smallint NOT NULL COMMENT '이월 원년도',
  `to_year` smallint NOT NULL COMMENT '이월 대상년도',
  `days` decimal(4,1) NOT NULL COMMENT '이월 일수',
  `reason` varchar(200) DEFAULT NULL COMMENT '이월 사유',
  `status` enum('신청','승인','반려') NOT NULL DEFAULT '신청',
  `agreement_date` date DEFAULT NULL COMMENT '노사 합의일',
  `approved_by` int DEFAULT NULL COMMENT 'employees.id',
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_from_to` (`employee_id`,`from_year`,`to_year`),
  KEY `idx_emp_year` (`employee_id`,`from_year`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='연차 이월';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_carryovers`
--

LOCK TABLES `leave_carryovers` WRITE;
/*!40000 ALTER TABLE `leave_carryovers` DISABLE KEYS */;
INSERT INTO `leave_carryovers` VALUES (1,1,2025,2026,2.0,'미사용 연차 이월 합의','승인','2026-01-05',1,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,5,2025,2026,2.0,'미사용 연차 이월 합의','승인','2026-01-05',1,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,9,2025,2026,2.0,'미사용 연차 이월 합의','승인','2026-01-05',1,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,13,2025,2026,2.0,'미사용 연차 이월 합의','승인','2026-01-05',1,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,17,2025,2026,2.0,'미사용 연차 이월 합의','승인','2026-01-05',1,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,21,2025,2026,2.0,'미사용 연차 이월 합의','승인','2026-01-05',1,'2026-07-03 02:58:24','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `leave_carryovers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_promotions`
--

DROP TABLE IF EXISTS `leave_promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_promotions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'employees.id',
  `year` smallint NOT NULL COMMENT '귀속년도',
  `stage` tinyint NOT NULL COMMENT '1=1차(6개월전), 2=2차(2개월전)',
  `notified_at` datetime DEFAULT NULL COMMENT '통보 일시',
  `deadline` date NOT NULL COMMENT '응답 기한',
  `response_status` enum('미응답','계획제출','지정통보') NOT NULL DEFAULT '미응답',
  `use_plan_dates` json DEFAULT NULL COMMENT '1차: 직원 제출 사용 계획 날짜',
  `designated_dates` json DEFAULT NULL COMMENT '2차: 회사 지정 사용 날짜',
  `responded_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL COMMENT '통보 생성자 employees.id',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_year_stage` (`employee_id`,`year`,`stage`),
  KEY `idx_year_stage` (`year`,`stage`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='연차 촉진 통보';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_promotions`
--

LOCK TABLES `leave_promotions` WRITE;
/*!40000 ALTER TABLE `leave_promotions` DISABLE KEYS */;
INSERT INTO `leave_promotions` VALUES (1,2,2026,1,'2026-07-03 02:58:24','2026-07-31','미응답',NULL,NULL,NULL,1,'2026-07-03 02:58:24'),(2,8,2026,1,'2026-07-03 02:58:24','2026-07-31','미응답',NULL,NULL,NULL,1,'2026-07-03 02:58:24'),(3,14,2026,1,'2026-07-03 02:58:24','2026-07-31','미응답',NULL,NULL,NULL,1,'2026-07-03 02:58:24'),(4,20,2026,1,'2026-07-03 02:58:24','2026-07-31','미응답',NULL,NULL,NULL,1,'2026-07-03 02:58:24');
/*!40000 ALTER TABLE `leave_promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `leave_type` varchar(10) NOT NULL DEFAULT 'AL',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_used` decimal(3,1) NOT NULL DEFAULT '1.0',
  `reason` varchar(200) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT '승인',
  `approved_at` datetime DEFAULT NULL COMMENT '승인 일시',
  `approver_id` int DEFAULT NULL COMMENT '승인자',
  `penalty_flag` tinyint(1) NOT NULL DEFAULT '0',
  `penalty_reason` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emp_year` (`employee_id`,`start_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` VALUES (1,8,'AL','2026-04-15','2026-04-15',1.0,'test','승인',NULL,NULL,0,NULL,'2026-04-14 18:23:32'),(2,8,'HAM','2026-04-16','2026-04-16',0.5,'','취소',NULL,NULL,0,NULL,'2026-04-14 18:23:39'),(3,8,'AL','2026-04-16','2026-04-16',1.0,'','승인',NULL,NULL,0,NULL,'2026-04-15 13:10:40'),(4,1,'AL','2026-03-04','2026-03-04',1.0,'[샘플] 개인 연차','승인','2026-03-04 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(5,2,'AL','2026-02-10','2026-02-11',2.0,'[샘플] 가족 여행','승인','2026-02-10 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(6,2,'HAM','2026-04-15','2026-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2026-04-15 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(7,3,'AL','2026-03-20','2026-03-20',1.0,'[샘플] 개인 사유','승인','2026-03-20 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(8,3,'AL','2026-05-08','2026-05-08',1.0,'[샘플] 경조사','승인','2026-05-08 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(9,3,'HAP','2026-06-12','2026-06-12',0.5,'[샘플] 오후 반차','승인','2026-06-12 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(10,4,'AL','2026-06-01','2026-06-05',5.0,'[샘플] 여름 휴가','승인','2026-06-01 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(11,6,'AL','2026-03-04','2026-03-04',1.0,'[샘플] 개인 연차','승인','2026-03-04 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(12,7,'AL','2026-02-10','2026-02-11',2.0,'[샘플] 가족 여행','승인','2026-02-10 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(13,7,'HAM','2026-04-15','2026-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2026-04-15 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(14,8,'AL','2026-03-20','2026-03-20',1.0,'[샘플] 개인 사유','승인','2026-03-20 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(15,8,'AL','2026-05-08','2026-05-08',1.0,'[샘플] 경조사','승인','2026-05-08 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(16,8,'HAP','2026-06-12','2026-06-12',0.5,'[샘플] 오후 반차','승인','2026-06-12 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(17,9,'AL','2026-06-01','2026-06-05',5.0,'[샘플] 여름 휴가','승인','2026-06-01 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(18,11,'AL','2026-03-04','2026-03-04',1.0,'[샘플] 개인 연차','승인','2026-03-04 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(19,12,'AL','2026-02-10','2026-02-11',2.0,'[샘플] 가족 여행','승인','2026-02-10 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(20,12,'HAM','2026-04-15','2026-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2026-04-15 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(21,13,'AL','2026-03-20','2026-03-20',1.0,'[샘플] 개인 사유','승인','2026-03-20 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(22,13,'AL','2026-05-08','2026-05-08',1.0,'[샘플] 경조사','승인','2026-05-08 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(23,13,'HAP','2026-06-12','2026-06-12',0.5,'[샘플] 오후 반차','승인','2026-06-12 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(24,14,'AL','2026-06-01','2026-06-05',5.0,'[샘플] 여름 휴가','승인','2026-06-01 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(25,16,'AL','2026-03-04','2026-03-04',1.0,'[샘플] 개인 연차','승인','2026-03-04 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(26,17,'AL','2026-02-10','2026-02-11',2.0,'[샘플] 가족 여행','승인','2026-02-10 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(27,17,'HAM','2026-04-15','2026-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2026-04-15 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(28,18,'AL','2026-03-20','2026-03-20',1.0,'[샘플] 개인 사유','승인','2026-03-20 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(29,18,'AL','2026-05-08','2026-05-08',1.0,'[샘플] 경조사','승인','2026-05-08 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(30,18,'HAP','2026-06-12','2026-06-12',0.5,'[샘플] 오후 반차','승인','2026-06-12 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(31,19,'AL','2026-06-01','2026-06-05',5.0,'[샘플] 여름 휴가','승인','2026-06-01 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(32,21,'AL','2026-03-04','2026-03-04',1.0,'[샘플] 개인 연차','승인','2026-03-04 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(33,22,'AL','2026-02-10','2026-02-11',2.0,'[샘플] 가족 여행','승인','2026-02-10 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(34,22,'HAM','2026-04-15','2026-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2026-04-15 10:00:00',1,0,NULL,'2026-07-03 02:21:57'),(35,1,'AL','2025-03-04','2025-03-04',1.0,'[샘플] 개인 연차','승인','2025-03-04 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(36,2,'AL','2025-02-10','2025-02-11',2.0,'[샘플] 가족 여행','승인','2025-02-10 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(37,2,'HAM','2025-04-15','2025-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2025-04-15 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(38,3,'AL','2025-03-20','2025-03-20',1.0,'[샘플] 개인 사유','승인','2025-03-20 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(39,3,'AL','2025-05-08','2025-05-08',1.0,'[샘플] 경조사','승인','2025-05-08 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(40,3,'HAP','2025-06-12','2025-06-12',0.5,'[샘플] 오후 반차','승인','2025-06-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(41,4,'AL','2025-06-02','2025-06-06',5.0,'[샘플] 여름 휴가','승인','2025-06-02 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(42,6,'AL','2025-03-04','2025-03-04',1.0,'[샘플] 개인 연차','승인','2025-03-04 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(43,7,'AL','2025-02-10','2025-02-11',2.0,'[샘플] 가족 여행','승인','2025-02-10 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(44,7,'HAM','2025-04-15','2025-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2025-04-15 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(45,8,'AL','2025-03-20','2025-03-20',1.0,'[샘플] 개인 사유','승인','2025-03-20 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(46,8,'AL','2025-05-08','2025-05-08',1.0,'[샘플] 경조사','승인','2025-05-08 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(47,8,'HAP','2025-06-12','2025-06-12',0.5,'[샘플] 오후 반차','승인','2025-06-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(48,9,'AL','2025-06-02','2025-06-06',5.0,'[샘플] 여름 휴가','승인','2025-06-02 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(49,11,'AL','2025-03-04','2025-03-04',1.0,'[샘플] 개인 연차','승인','2025-03-04 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(50,12,'AL','2025-02-10','2025-02-11',2.0,'[샘플] 가족 여행','승인','2025-02-10 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(51,12,'HAM','2025-04-15','2025-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2025-04-15 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(52,13,'AL','2025-03-20','2025-03-20',1.0,'[샘플] 개인 사유','승인','2025-03-20 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(53,13,'AL','2025-05-08','2025-05-08',1.0,'[샘플] 경조사','승인','2025-05-08 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(54,13,'HAP','2025-06-12','2025-06-12',0.5,'[샘플] 오후 반차','승인','2025-06-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(55,14,'AL','2025-06-02','2025-06-06',5.0,'[샘플] 여름 휴가','승인','2025-06-02 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(56,16,'AL','2025-03-04','2025-03-04',1.0,'[샘플] 개인 연차','승인','2025-03-04 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(57,17,'AL','2025-02-10','2025-02-11',2.0,'[샘플] 가족 여행','승인','2025-02-10 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(58,17,'HAM','2025-04-15','2025-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2025-04-15 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(59,18,'AL','2025-03-20','2025-03-20',1.0,'[샘플] 개인 사유','승인','2025-03-20 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(60,18,'AL','2025-05-08','2025-05-08',1.0,'[샘플] 경조사','승인','2025-05-08 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(61,18,'HAP','2025-06-12','2025-06-12',0.5,'[샘플] 오후 반차','승인','2025-06-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(62,19,'AL','2025-06-02','2025-06-06',5.0,'[샘플] 여름 휴가','승인','2025-06-02 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(63,21,'AL','2025-03-04','2025-03-04',1.0,'[샘플] 개인 연차','승인','2025-03-04 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(64,22,'AL','2025-02-10','2025-02-11',2.0,'[샘플] 가족 여행','승인','2025-02-10 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(65,22,'HAM','2025-04-15','2025-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2025-04-15 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(66,1,'AL','2024-03-04','2024-03-04',1.0,'[샘플] 개인 연차','승인','2024-03-04 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(67,2,'AL','2024-02-12','2024-02-13',2.0,'[샘플] 가족 여행','승인','2024-02-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(68,2,'HAM','2024-04-15','2024-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2024-04-15 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(69,3,'AL','2024-03-20','2024-03-20',1.0,'[샘플] 개인 사유','승인','2024-03-20 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(70,3,'AL','2024-05-08','2024-05-08',1.0,'[샘플] 경조사','승인','2024-05-08 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(71,3,'HAP','2024-06-12','2024-06-12',0.5,'[샘플] 오후 반차','승인','2024-06-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(72,4,'AL','2024-06-03','2024-06-07',5.0,'[샘플] 여름 휴가','승인','2024-06-03 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(73,6,'AL','2024-03-04','2024-03-04',1.0,'[샘플] 개인 연차','승인','2024-03-04 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(74,7,'AL','2024-02-12','2024-02-13',2.0,'[샘플] 가족 여행','승인','2024-02-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(75,7,'HAM','2024-04-15','2024-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2024-04-15 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(76,8,'AL','2024-03-20','2024-03-20',1.0,'[샘플] 개인 사유','승인','2024-03-20 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(77,8,'AL','2024-05-08','2024-05-08',1.0,'[샘플] 경조사','승인','2024-05-08 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(78,8,'HAP','2024-06-12','2024-06-12',0.5,'[샘플] 오후 반차','승인','2024-06-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(79,9,'AL','2024-06-03','2024-06-07',5.0,'[샘플] 여름 휴가','승인','2024-06-03 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(80,11,'AL','2024-03-04','2024-03-04',1.0,'[샘플] 개인 연차','승인','2024-03-04 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(81,12,'AL','2024-02-12','2024-02-13',2.0,'[샘플] 가족 여행','승인','2024-02-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(82,12,'HAM','2024-04-15','2024-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2024-04-15 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(83,13,'AL','2024-03-20','2024-03-20',1.0,'[샘플] 개인 사유','승인','2024-03-20 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(84,13,'AL','2024-05-08','2024-05-08',1.0,'[샘플] 경조사','승인','2024-05-08 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(85,13,'HAP','2024-06-12','2024-06-12',0.5,'[샘플] 오후 반차','승인','2024-06-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(86,14,'AL','2024-06-03','2024-06-07',5.0,'[샘플] 여름 휴가','승인','2024-06-03 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(87,16,'AL','2024-03-04','2024-03-04',1.0,'[샘플] 개인 연차','승인','2024-03-04 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(88,17,'AL','2024-02-12','2024-02-13',2.0,'[샘플] 가족 여행','승인','2024-02-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(89,17,'HAM','2024-04-15','2024-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2024-04-15 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(90,18,'AL','2024-03-20','2024-03-20',1.0,'[샘플] 개인 사유','승인','2024-03-20 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(91,18,'AL','2024-05-08','2024-05-08',1.0,'[샘플] 경조사','승인','2024-05-08 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(92,18,'HAP','2024-06-12','2024-06-12',0.5,'[샘플] 오후 반차','승인','2024-06-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(93,19,'AL','2024-06-03','2024-06-07',5.0,'[샘플] 여름 휴가','승인','2024-06-03 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(94,21,'AL','2024-03-04','2024-03-04',1.0,'[샘플] 개인 연차','승인','2024-03-04 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(95,22,'AL','2024-02-12','2024-02-13',2.0,'[샘플] 가족 여행','승인','2024-02-12 10:00:00',1,0,NULL,'2026-07-03 02:34:28'),(96,22,'HAM','2024-04-15','2024-04-15',0.5,'[샘플] 오전 반차 (병원)','승인','2024-04-15 10:00:00',1,0,NULL,'2026-07-03 02:34:28');
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_settlements`
--

DROP TABLE IF EXISTS `leave_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_settlements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL COMMENT 'employees.id',
  `year` smallint NOT NULL COMMENT '귀속년도',
  `resign_date` date NOT NULL COMMENT '퇴사일',
  `hire_date` date NOT NULL COMMENT '입사일',
  `worked_months` decimal(4,1) NOT NULL COMMENT '해당년도 근무 월수',
  `prorated_days` decimal(4,1) NOT NULL COMMENT '일할 부여일 = total × (months/12)',
  `used_days` decimal(4,1) NOT NULL COMMENT '실 사용일',
  `remaining_days` decimal(4,1) NOT NULL COMMENT '미사용 잔여일',
  `base_salary` bigint NOT NULL DEFAULT '0' COMMENT '기본급',
  `daily_wage` bigint NOT NULL DEFAULT '0' COMMENT '일급 = base_salary / 21.67',
  `settlement_amount` bigint NOT NULL DEFAULT '0' COMMENT '보상액 = remaining × daily_wage',
  `settled_by` int DEFAULT NULL COMMENT '정산 처리자 employees.id',
  `settled_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `memo` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_year` (`employee_id`,`year`),
  KEY `idx_emp` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='퇴사자 연차 정산';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_settlements`
--

LOCK TABLES `leave_settlements` WRITE;
/*!40000 ALTER TABLE `leave_settlements` DISABLE KEYS */;
INSERT INTO `leave_settlements` VALUES (1,23,2026,'2026-03-31','2022-06-01',3.0,3.8,1.5,2.3,2200000,73333,168666,1,'2026-07-03 02:59:40','퇴사자 연차 정산'),(2,24,2026,'2026-05-31','2023-09-01',5.0,6.3,2.5,3.8,2000000,66667,253335,1,'2026-07-03 02:59:40','퇴사자 연차 정산'),(3,25,2026,'2026-06-30','2021-04-20',6.0,7.5,3.0,4.5,2500000,83333,374999,1,'2026-07-03 02:59:40','퇴사자 연차 정산');
/*!40000 ALTER TABLE `leave_settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_permissions`
--

DROP TABLE IF EXISTS `menu_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `menu_key` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `role_key` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `access_level` enum('view','edit','admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'view',
  `note` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menu_role_dept` (`menu_key`,`role_key`,`department_id`),
  KEY `idx_menu` (`menu_key`),
  KEY `idx_role` (`role_key`),
  KEY `idx_dept` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_permissions`
--

LOCK TABLES `menu_permissions` WRITE;
/*!40000 ALTER TABLE `menu_permissions` DISABLE KEYS */;
INSERT INTO `menu_permissions` VALUES (1,'*','admin',NULL,'admin','관리자는 모든 메뉴 접근','2026-06-25 18:46:07','2026-06-25 18:46:07'),(2,'dashboard','manager',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(3,'dashboard','user',NULL,'view',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(4,'attendance','manager',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(5,'attendance','user',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(6,'schedule','manager',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(7,'schedule','user',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(8,'approval','manager',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(9,'approval','user',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(10,'board','manager',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(11,'board','user',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(12,'hr','manager',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(13,'hr','user',NULL,'view',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(14,'hospital','manager',NULL,'edit','병원 전용 운영관리','2026-06-25 18:46:07','2026-06-25 18:46:07'),(15,'hospital','user',NULL,'view','병원 전용 운영조회','2026-06-25 18:46:07','2026-06-25 18:46:07'),(16,'accounting','manager',NULL,'view',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(17,'accounting.settle','manager',NULL,'edit','회계 정산','2026-06-25 18:46:07','2026-06-25 18:46:07'),(18,'labor','manager',NULL,'view',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(19,'labor.rules','manager',NULL,'view','취업규칙 편집은 관리자 전용','2026-06-25 18:46:07','2026-06-25 18:46:07'),(20,'business','manager',NULL,'edit',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(21,'business','user',NULL,'view',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(22,'business_docs','manager',NULL,'view',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(23,'business_docs','user',NULL,'view',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(24,'groupware','admin',NULL,'admin',NULL,'2026-06-25 18:46:07','2026-06-25 18:46:07'),(25,'groupware.permissions','admin',NULL,'admin','접근권한 관리','2026-06-25 18:46:07','2026-06-25 18:46:07'),(26,'*','admin',NULL,'admin','관리자는 모든 메뉴에 접근','2026-07-03 01:24:10','2026-07-03 01:24:10'),(27,'dashboard','manager',NULL,'edit','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(28,'dashboard','user',NULL,'view','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(29,'attendance','manager',NULL,'edit','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(30,'attendance','user',NULL,'edit','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(31,'schedule','manager',NULL,'edit','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(32,'schedule','user',NULL,'edit','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(33,'approval','manager',NULL,'edit','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(34,'approval','user',NULL,'edit','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(35,'board','manager',NULL,'edit','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(36,'board','user',NULL,'edit','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(37,'hr','manager',NULL,'edit','부서장은 본인 부서 직원 조회','2026-07-03 01:24:10','2026-07-03 01:24:10'),(38,'hr','user',NULL,'view','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(39,'accounting','manager',NULL,'view','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(40,'accounting.settle','manager',NULL,'edit','회계 정산 · 본부장/회계팀만','2026-07-03 01:24:10','2026-07-03 01:24:10'),(41,'labor','manager',NULL,'view','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(42,'labor.rules','manager',NULL,'view','취업규칙 편집은 admin 전용 (기본)','2026-07-03 01:24:10','2026-07-03 01:24:10'),(43,'business','manager',NULL,'edit','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(44,'business','user',NULL,'view','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(45,'business_docs','manager',NULL,'view','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(46,'business_docs','user',NULL,'view','','2026-07-03 01:24:10','2026-07-03 01:24:10'),(47,'groupware','admin',NULL,'admin','시스템 관리는 관리자만','2026-07-03 01:24:10','2026-07-03 01:24:10'),(48,'groupware.permissions','admin',NULL,'admin','접근권한 관리는 관리자만','2026-07-03 01:24:10','2026-07-03 01:24:10'),(49,'*','admin',NULL,'admin','관리자는 모든 메뉴에 접근','2026-07-03 01:31:25','2026-07-03 01:31:25'),(50,'dashboard','manager',NULL,'edit','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(51,'dashboard','user',NULL,'view','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(52,'attendance','manager',NULL,'edit','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(53,'attendance','user',NULL,'edit','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(54,'schedule','manager',NULL,'edit','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(55,'schedule','user',NULL,'edit','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(56,'approval','manager',NULL,'edit','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(57,'approval','user',NULL,'edit','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(58,'board','manager',NULL,'edit','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(59,'board','user',NULL,'edit','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(60,'hr','manager',NULL,'edit','부서장은 본인 부서 직원 조회','2026-07-03 01:31:25','2026-07-03 01:31:25'),(61,'hr','user',NULL,'view','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(62,'accounting','manager',NULL,'view','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(63,'accounting.settle','manager',NULL,'edit','회계 정산 · 본부장/회계팀만','2026-07-03 01:31:25','2026-07-03 01:31:25'),(64,'labor','manager',NULL,'view','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(65,'labor.rules','manager',NULL,'view','취업규칙 편집은 admin 전용 (기본)','2026-07-03 01:31:25','2026-07-03 01:31:25'),(66,'business','manager',NULL,'edit','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(67,'business','user',NULL,'view','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(68,'business_docs','manager',NULL,'view','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(69,'business_docs','user',NULL,'view','','2026-07-03 01:31:25','2026-07-03 01:31:25'),(70,'groupware','admin',NULL,'admin','시스템 관리는 관리자만','2026-07-03 01:31:25','2026-07-03 01:31:25'),(71,'groupware.permissions','admin',NULL,'admin','접근권한 관리는 관리자만','2026-07-03 01:31:25','2026-07-03 01:31:25'),(72,'*','admin',NULL,'admin','관리자는 모든 메뉴에 접근','2026-07-03 01:32:20','2026-07-03 01:32:20'),(73,'dashboard','manager',NULL,'edit','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(74,'dashboard','user',NULL,'view','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(75,'attendance','manager',NULL,'edit','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(76,'attendance','user',NULL,'edit','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(77,'schedule','manager',NULL,'edit','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(78,'schedule','user',NULL,'edit','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(79,'approval','manager',NULL,'edit','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(80,'approval','user',NULL,'edit','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(81,'board','manager',NULL,'edit','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(82,'board','user',NULL,'edit','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(83,'hr','manager',NULL,'edit','부서장은 본인 부서 직원 조회','2026-07-03 01:32:20','2026-07-03 01:32:20'),(84,'hr','user',NULL,'view','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(85,'accounting','manager',NULL,'view','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(86,'accounting.settle','manager',NULL,'edit','회계 정산 · 본부장/회계팀만','2026-07-03 01:32:20','2026-07-03 01:32:20'),(87,'labor','manager',NULL,'view','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(88,'labor.rules','manager',NULL,'view','취업규칙 편집은 admin 전용 (기본)','2026-07-03 01:32:20','2026-07-03 01:32:20'),(89,'business','manager',NULL,'edit','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(90,'business','user',NULL,'view','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(91,'business_docs','manager',NULL,'view','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(92,'business_docs','user',NULL,'view','','2026-07-03 01:32:20','2026-07-03 01:32:20'),(93,'groupware','admin',NULL,'admin','시스템 관리는 관리자만','2026-07-03 01:32:20','2026-07-03 01:32:20'),(94,'groupware.permissions','admin',NULL,'admin','접근권한 관리는 관리자만','2026-07-03 01:32:20','2026-07-03 01:32:20'),(95,'*','admin',NULL,'admin','관리자는 모든 메뉴에 접근','2026-07-03 01:33:13','2026-07-03 01:33:13'),(96,'dashboard','manager',NULL,'edit','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(97,'dashboard','user',NULL,'view','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(98,'attendance','manager',NULL,'edit','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(99,'attendance','user',NULL,'edit','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(100,'schedule','manager',NULL,'edit','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(101,'schedule','user',NULL,'edit','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(102,'approval','manager',NULL,'edit','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(103,'approval','user',NULL,'edit','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(104,'board','manager',NULL,'edit','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(105,'board','user',NULL,'edit','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(106,'hr','manager',NULL,'edit','부서장은 본인 부서 직원 조회','2026-07-03 01:33:13','2026-07-03 01:33:13'),(107,'hr','user',NULL,'view','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(108,'accounting','manager',NULL,'view','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(109,'accounting.settle','manager',NULL,'edit','회계 정산 · 본부장/회계팀만','2026-07-03 01:33:13','2026-07-03 01:33:13'),(110,'labor','manager',NULL,'view','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(111,'labor.rules','manager',NULL,'view','취업규칙 편집은 admin 전용 (기본)','2026-07-03 01:33:13','2026-07-03 01:33:13'),(112,'business','manager',NULL,'edit','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(113,'business','user',NULL,'view','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(114,'business_docs','manager',NULL,'view','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(115,'business_docs','user',NULL,'view','','2026-07-03 01:33:13','2026-07-03 01:33:13'),(116,'groupware','admin',NULL,'admin','시스템 관리는 관리자만','2026-07-03 01:33:13','2026-07-03 01:33:13'),(117,'groupware.permissions','admin',NULL,'admin','접근권한 관리는 관리자만','2026-07-03 01:33:13','2026-07-03 01:33:13');
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
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '알림 유형',
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `link_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '클릭 시 이동 URL',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='알림';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'doc_upload','서류 업로드 완료','2025년 12월 급여대장이 업로드되었습니다.','/pages/tax_docs.php',1,'2026-03-22 15:18:33'),(2,1,'doc_request','새 서류 요청','세무사가 사업자등록증 사본을 요청했습니다.','/pages/tax_docs.php',1,'2026-03-22 15:18:33'),(3,1,'doc_confirmed','서류 확인 완료','사업자등록증 사본 확인이 완료되었습니다.','/pages/tax_docs.php',1,'2026-03-22 15:18:33');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `org_levels`
--

DROP TABLE IF EXISTS `org_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `org_levels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `depth` tinyint NOT NULL COMMENT '정렬 순서 (0=최상위, 6=최하위)',
  `key_name` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT '시스템 키 (company, division, department 등)',
  `label` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '표시명 (회사, 본부, 부서 등)',
  `head_title` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT '책임자 호칭 (대표, 본부장, 부서장 등)',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `is_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT '삭제 불가 필수 레벨 (company, division, department)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `org_levels`
--

LOCK TABLES `org_levels` WRITE;
/*!40000 ALTER TABLE `org_levels` DISABLE KEYS */;
INSERT INTO `org_levels` VALUES (1,0,'company','회사','대표',1,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(2,1,'division','본부','본부장',1,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(3,2,'department','부서','부서장',1,1,'2026-07-03 01:24:12','2026-07-03 01:24:12');
/*!40000 ALTER TABLE `org_levels` ENABLE KEYS */;
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
  `purpose` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`,`work_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outside_work_records`
--

LOCK TABLES `outside_work_records` WRITE;
/*!40000 ALTER TABLE `outside_work_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `outside_work_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_pay_types`
--

DROP TABLE IF EXISTS `payroll_pay_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_pay_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `category` enum('pay','deduct') NOT NULL,
  `is_taxable` tinyint(1) NOT NULL DEFAULT '1' COMMENT '과세 여부 (4대보험 기준액 산입)',
  `calc_type` enum('manual','rate') NOT NULL DEFAULT 'manual',
  `calc_base` enum('base_salary','gross_pay') DEFAULT NULL COMMENT 'rate 타입일 때 기준',
  `calc_rate` decimal(10,6) DEFAULT NULL COMMENT 'rate 타입일 때 요율',
  `has_hours` tinyint(1) NOT NULL DEFAULT '0' COMMENT '시간 입력 필요 여부',
  `custom_hourly_rate` int unsigned DEFAULT NULL COMMENT '시간급 항목의 커스텀 시급 (원). NULL이면 법정 공식 적용',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '시스템 항목 (삭제 불가)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='급여 지급/공제 항목 마스터';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_pay_types`
--

LOCK TABLES `payroll_pay_types` WRITE;
/*!40000 ALTER TABLE `payroll_pay_types` DISABLE KEYS */;
INSERT INTO `payroll_pay_types` VALUES (1,'BASE','기본급','pay',1,'manual',NULL,NULL,0,NULL,10,1,1,'2026-07-03 01:24:10'),(2,'MEAL','식대','pay',0,'manual',NULL,NULL,0,NULL,20,1,1,'2026-07-03 01:24:10'),(3,'CAR','차량지원','pay',0,'manual',NULL,NULL,0,NULL,30,1,0,'2026-07-03 01:24:10'),(4,'CHILD','육아수당','pay',0,'manual',NULL,NULL,0,NULL,40,1,0,'2026-07-03 01:24:10'),(5,'OT','초과수당','pay',1,'manual',NULL,NULL,1,NULL,50,1,1,'2026-07-03 01:24:10'),(6,'NP','국민연금','deduct',0,'rate','base_salary',0.047500,0,NULL,110,1,1,'2026-07-03 01:24:10'),(7,'HI','건강보험','deduct',0,'rate','base_salary',0.035950,0,NULL,120,1,1,'2026-07-03 01:24:10'),(8,'LC','장기요양보험','deduct',0,'rate','base_salary',0.004724,0,NULL,125,1,1,'2026-07-03 01:24:10'),(9,'EI','고용보험','deduct',0,'rate','base_salary',0.009000,0,NULL,130,1,1,'2026-07-03 01:24:10'),(10,'IT','소득세','deduct',0,'rate','gross_pay',0.030000,0,NULL,140,1,1,'2026-07-03 01:24:10'),(11,'LT','지방소득세','deduct',0,'rate','gross_pay',0.003000,0,NULL,150,1,1,'2026-07-03 01:24:10');
/*!40000 ALTER TABLE `payroll_pay_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_rates`
--

DROP TABLE IF EXISTS `payroll_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_rates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint unsigned NOT NULL COMMENT '적용 연도',
  `rate_pension` decimal(7,5) NOT NULL DEFAULT '0.04500' COMMENT '국민연금 근로자부담률',
  `rate_health` decimal(7,5) NOT NULL DEFAULT '0.03545' COMMENT '건강보험 근로자부담률',
  `rate_employ` decimal(7,5) NOT NULL DEFAULT '0.00900' COMMENT '고용보험 근로자부담률',
  `rate_tax` decimal(7,5) NOT NULL DEFAULT '0.03300' COMMENT '소득세+지방소득세 합산률',
  `memo` varchar(200) NOT NULL DEFAULT '' COMMENT '비고 (고시번호 등)',
  `updated_by` int unsigned DEFAULT NULL COMMENT '수정자 employees.id',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_year` (`year`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='급여 공제요율 (연도별)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_rates`
--

LOCK TABLES `payroll_rates` WRITE;
/*!40000 ALTER TABLE `payroll_rates` DISABLE KEYS */;
INSERT INTO `payroll_rates` VALUES (1,2025,0.04500,0.03545,0.00900,0.03300,'2025년 고시 기준 (시범 운영용 간이 세율)',NULL,'2026-07-03 01:24:10'),(2,2026,0.04500,0.03545,0.00900,0.03300,'2026년 (2025년과 동일, 고시 확인 후 수정)',NULL,'2026-07-03 01:24:10');
/*!40000 ALTER TABLE `payroll_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslip_items`
--

DROP TABLE IF EXISTS `payslip_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslip_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `payslip_id` int unsigned NOT NULL,
  `pay_type_id` int unsigned NOT NULL,
  `amount` bigint NOT NULL DEFAULT '0',
  `hours` decimal(5,1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_payslip_type` (`payslip_id`,`pay_type_id`),
  KEY `pay_type_id` (`pay_type_id`),
  CONSTRAINT `payslip_items_ibfk_1` FOREIGN KEY (`payslip_id`) REFERENCES `payslips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payslip_items_ibfk_2` FOREIGN KEY (`pay_type_id`) REFERENCES `payroll_pay_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=243 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='급여명세 항목별 금액';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslip_items`
--

LOCK TABLES `payslip_items` WRITE;
/*!40000 ALTER TABLE `payslip_items` DISABLE KEYS */;
INSERT INTO `payslip_items` VALUES (1,1,1,5000000,NULL),(2,1,2,200000,NULL),(3,1,3,300000,NULL),(4,1,4,0,NULL),(5,1,5,0,NULL),(6,1,6,237500,NULL),(7,1,7,179750,NULL),(8,1,8,23620,NULL),(9,1,9,45000,NULL),(10,1,10,165000,NULL),(11,1,11,16500,NULL),(12,2,1,4500000,NULL),(13,2,2,200000,NULL),(14,2,3,0,NULL),(15,2,4,0,NULL),(16,2,5,0,NULL),(17,2,6,213750,NULL),(18,2,7,161775,NULL),(19,2,8,21258,NULL),(20,2,9,40500,NULL),(21,2,10,141000,NULL),(22,2,11,14100,NULL),(23,3,1,6500000,NULL),(24,3,2,200000,NULL),(25,3,3,300000,NULL),(26,3,4,0,NULL),(27,3,5,0,NULL),(28,3,6,308750,NULL),(29,3,7,233675,NULL),(30,3,8,30706,NULL),(31,3,9,58500,NULL),(32,3,10,210000,NULL),(33,3,11,21000,NULL),(34,4,1,4500000,NULL),(35,4,2,200000,NULL),(36,4,3,300000,NULL),(37,4,4,0,NULL),(38,4,5,0,NULL),(39,4,6,213750,NULL),(40,4,7,161775,NULL),(41,4,8,21258,NULL),(42,4,9,40500,NULL),(43,4,10,150000,NULL),(44,4,11,15000,NULL),(45,5,1,4500000,NULL),(46,5,2,200000,NULL),(47,5,3,200000,NULL),(48,5,4,0,NULL),(49,5,5,0,NULL),(50,5,6,213750,NULL),(51,5,7,161775,NULL),(52,5,8,21258,NULL),(53,5,9,40500,NULL),(54,5,10,147000,NULL),(55,5,11,14700,NULL),(56,6,1,4500000,NULL),(57,6,2,200000,NULL),(58,6,3,200000,NULL),(59,6,4,0,NULL),(60,6,5,0,NULL),(61,6,6,213750,NULL),(62,6,7,161775,NULL),(63,6,8,21258,NULL),(64,6,9,40500,NULL),(65,6,10,147000,NULL),(66,6,11,14700,NULL),(67,7,1,4500000,NULL),(68,7,2,200000,NULL),(69,7,3,200000,NULL),(70,7,4,0,NULL),(71,7,5,0,NULL),(72,7,6,213750,NULL),(73,7,7,161775,NULL),(74,7,8,21258,NULL),(75,7,9,40500,NULL),(76,7,10,147000,NULL),(77,7,11,14700,NULL),(78,8,1,4500000,NULL),(79,8,2,200000,NULL),(80,8,3,200000,NULL),(81,8,4,0,NULL),(82,8,5,0,NULL),(83,8,6,213750,NULL),(84,8,7,161775,NULL),(85,8,8,21258,NULL),(86,8,9,40500,NULL),(87,8,10,147000,NULL),(88,8,11,14700,NULL),(89,9,1,3500000,NULL),(90,9,2,200000,NULL),(91,9,3,0,NULL),(92,9,4,0,NULL),(93,9,5,0,NULL),(94,9,6,166250,NULL),(95,9,7,125825,NULL),(96,9,8,16534,NULL),(97,9,9,31500,NULL),(98,9,10,111000,NULL),(99,9,11,11100,NULL),(100,10,1,3500000,NULL),(101,10,2,200000,NULL),(102,10,3,0,NULL),(103,10,4,0,NULL),(104,10,5,0,NULL),(105,10,6,166250,NULL),(106,10,7,125825,NULL),(107,10,8,16534,NULL),(108,10,9,31500,NULL),(109,10,10,111000,NULL),(110,10,11,11100,NULL),(111,11,1,3500000,NULL),(112,11,2,200000,NULL),(113,11,3,0,NULL),(114,11,4,0,NULL),(115,11,5,0,NULL),(116,11,6,166250,NULL),(117,11,7,125825,NULL),(118,11,8,16534,NULL),(119,11,9,31500,NULL),(120,11,10,111000,NULL),(121,11,11,11100,NULL),(122,12,1,3500000,NULL),(123,12,2,200000,NULL),(124,12,3,0,NULL),(125,12,4,0,NULL),(126,12,5,0,NULL),(127,12,6,166250,NULL),(128,12,7,125825,NULL),(129,12,8,16534,NULL),(130,12,9,31500,NULL),(131,12,10,111000,NULL),(132,12,11,11100,NULL),(133,13,1,2800000,NULL),(134,13,2,200000,NULL),(135,13,3,0,NULL),(136,13,4,0,NULL),(137,13,5,0,NULL),(138,13,6,133000,NULL),(139,13,7,100660,NULL),(140,13,8,13227,NULL),(141,13,9,25200,NULL),(142,13,10,90000,NULL),(143,13,11,9000,NULL),(144,14,1,2800000,NULL),(145,14,2,200000,NULL),(146,14,3,0,NULL),(147,14,4,0,NULL),(148,14,5,0,NULL),(149,14,6,133000,NULL),(150,14,7,100660,NULL),(151,14,8,13227,NULL),(152,14,9,25200,NULL),(153,14,10,90000,NULL),(154,14,11,9000,NULL),(155,15,1,2800000,NULL),(156,15,2,200000,NULL),(157,15,3,0,NULL),(158,15,4,0,NULL),(159,15,5,0,NULL),(160,15,6,133000,NULL),(161,15,7,100660,NULL),(162,15,8,13227,NULL),(163,15,9,25200,NULL),(164,15,10,90000,NULL),(165,15,11,9000,NULL),(166,16,1,2500000,NULL),(167,16,2,200000,NULL),(168,16,3,0,NULL),(169,16,4,0,NULL),(170,16,5,0,NULL),(171,16,6,118750,NULL),(172,16,7,89875,NULL),(173,16,8,11810,NULL),(174,16,9,22500,NULL),(175,16,10,81000,NULL),(176,16,11,8100,NULL),(177,17,1,2700000,NULL),(178,17,2,200000,NULL),(179,17,3,0,NULL),(180,17,4,0,NULL),(181,17,5,0,NULL),(182,17,6,128250,NULL),(183,17,7,97065,NULL),(184,17,8,12755,NULL),(185,17,9,24300,NULL),(186,17,10,87000,NULL),(187,17,11,8700,NULL),(188,18,1,2500000,NULL),(189,18,2,200000,NULL),(190,18,3,0,NULL),(191,18,4,0,NULL),(192,18,5,0,NULL),(193,18,6,118750,NULL),(194,18,7,89875,NULL),(195,18,8,11810,NULL),(196,18,9,22500,NULL),(197,18,10,81000,NULL),(198,18,11,8100,NULL),(199,19,1,2700000,NULL),(200,19,2,200000,NULL),(201,19,3,0,NULL),(202,19,4,0,NULL),(203,19,5,0,NULL),(204,19,6,128250,NULL),(205,19,7,97065,NULL),(206,19,8,12755,NULL),(207,19,9,24300,NULL),(208,19,10,87000,NULL),(209,19,11,8700,NULL),(210,20,1,2800000,NULL),(211,20,2,200000,NULL),(212,20,3,0,NULL),(213,20,4,0,NULL),(214,20,5,0,NULL),(215,20,6,133000,NULL),(216,20,7,100660,NULL),(217,20,8,13227,NULL),(218,20,9,25200,NULL),(219,20,10,90000,NULL),(220,20,11,9000,NULL),(221,21,1,2500000,NULL),(222,21,2,200000,NULL),(223,21,3,0,NULL),(224,21,4,0,NULL),(225,21,5,0,NULL),(226,21,6,118750,NULL),(227,21,7,89875,NULL),(228,21,8,11810,NULL),(229,21,9,22500,NULL),(230,21,10,81000,NULL),(231,21,11,8100,NULL),(232,22,1,2500000,NULL),(233,22,2,200000,NULL),(234,22,3,0,NULL),(235,22,4,0,NULL),(236,22,5,0,NULL),(237,22,6,118750,NULL),(238,22,7,89875,NULL),(239,22,8,11810,NULL),(240,22,9,22500,NULL),(241,22,10,81000,NULL),(242,22,11,8100,NULL);
/*!40000 ALTER TABLE `payslip_items` ENABLE KEYS */;
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
  `employee_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
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
  `status` enum('draft','confirmed','paid') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT '상태',
  `confirmed_at` datetime DEFAULT NULL COMMENT '확정일시',
  `confirmed_by` int unsigned DEFAULT NULL COMMENT '확정자 ID',
  `paid_at` datetime DEFAULT NULL COMMENT '지급일시',
  `memo` varchar(200) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '비고',
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_yearmonth` (`employee_id`,`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='급여 명세';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslips`
--

LOCK TABLES `payslips` WRITE;
/*!40000 ALTER TABLE `payslips` DISABLE KEYS */;
INSERT INTO `payslips` VALUES (1,1,'김대표',2026,7,0,0.0,0,0,0,0,5500000,0,0,0,0,667370,4832630,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(2,2,'이이사',2026,7,0,0.0,0,0,0,0,4700000,0,0,0,0,592383,4107617,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(3,3,'박이사',2026,7,0,0.0,0,0,0,0,7000000,0,0,0,0,862631,6137369,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(4,4,'최이사',2026,7,0,0.0,0,0,0,0,5000000,0,0,0,0,602283,4397717,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(5,5,'정부장',2026,7,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(6,6,'한부장',2026,7,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(7,7,'오부장',2026,7,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(8,8,'강부장',2026,7,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(9,9,'윤과장',2026,7,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(10,10,'임과장',2026,7,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(11,11,'서과장',2026,7,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(12,12,'류과장',2026,7,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(13,13,'김대리',2026,7,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(14,14,'이대리',2026,7,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(15,15,'박대리',2026,7,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(16,16,'송사원',2026,7,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(17,17,'조대리',2026,7,0,0.0,0,0,0,0,2900000,0,0,0,0,358070,2541930,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(18,18,'황사원',2026,7,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(19,19,'문대리',2026,7,0,0.0,0,0,0,0,2900000,0,0,0,0,358070,2541930,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(20,20,'배대리',2026,7,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(21,21,'노대리',2026,7,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(22,22,'심사원',2026,7,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'draft',NULL,NULL,NULL,'','2026-07-03 02:13:45'),(23,1,'김대표',2026,5,0,0.0,0,0,0,0,5500000,0,0,0,0,667370,4832630,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(24,2,'이이사',2026,5,0,0.0,0,0,0,0,4700000,0,0,0,0,592383,4107617,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(25,3,'박이사',2026,5,0,0.0,0,0,0,0,7000000,0,0,0,0,862631,6137369,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(26,4,'최이사',2026,5,0,0.0,0,0,0,0,5000000,0,0,0,0,602283,4397717,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(27,5,'정부장',2026,5,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(28,6,'한부장',2026,5,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(29,7,'오부장',2026,5,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(30,8,'강부장',2026,5,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(31,9,'윤과장',2026,5,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(32,10,'임과장',2026,5,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(33,11,'서과장',2026,5,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(34,12,'류과장',2026,5,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(35,13,'김대리',2026,5,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(36,14,'이대리',2026,5,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(37,15,'박대리',2026,5,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(38,16,'송사원',2026,5,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(39,17,'조대리',2026,5,0,0.0,0,0,0,0,2900000,0,0,0,0,358070,2541930,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(40,18,'황사원',2026,5,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(41,19,'문대리',2026,5,0,0.0,0,0,0,0,2900000,0,0,0,0,358070,2541930,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(42,20,'배대리',2026,5,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(43,21,'노대리',2026,5,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(44,22,'심사원',2026,5,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(45,1,'김대표',2026,6,0,0.0,0,0,0,0,5500000,0,0,0,0,667370,4832630,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(46,2,'이이사',2026,6,0,0.0,0,0,0,0,4700000,0,0,0,0,592383,4107617,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(47,3,'박이사',2026,6,0,0.0,0,0,0,0,7000000,0,0,0,0,862631,6137369,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(48,4,'최이사',2026,6,0,0.0,0,0,0,0,5000000,0,0,0,0,602283,4397717,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(49,5,'정부장',2026,6,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(50,6,'한부장',2026,6,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(51,7,'오부장',2026,6,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(52,8,'강부장',2026,6,0,0.0,0,0,0,0,4900000,0,0,0,0,598983,4301017,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(53,9,'윤과장',2026,6,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(54,10,'임과장',2026,6,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(55,11,'서과장',2026,6,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(56,12,'류과장',2026,6,0,0.0,0,0,0,0,3700000,0,0,0,0,462209,3237791,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(57,13,'김대리',2026,6,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(58,14,'이대리',2026,6,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(59,15,'박대리',2026,6,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(60,16,'송사원',2026,6,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(61,17,'조대리',2026,6,0,0.0,0,0,0,0,2900000,0,0,0,0,358070,2541930,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(62,18,'황사원',2026,6,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(63,19,'문대리',2026,6,0,0.0,0,0,0,0,2900000,0,0,0,0,358070,2541930,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(64,20,'배대리',2026,6,0,0.0,0,0,0,0,3000000,0,0,0,0,371087,2628913,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(65,21,'노대리',2026,6,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01'),(66,22,'심사원',2026,6,0,0.0,0,0,0,0,2700000,0,0,0,0,332035,2367965,'paid',NULL,NULL,NULL,'','2026-07-03 02:43:01');
/*!40000 ALTER TABLE `payslips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_tasks`
--

DROP TABLE IF EXISTS `personal_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `owner_id` int NOT NULL COMMENT '할 일 소유자(employees.id) — 세션에서 주입',
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `due_date` date DEFAULT NULL COMMENT 'NULL=기한 없음',
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'normal',
  `status` enum('todo','done') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'todo',
  `completed_at` datetime DEFAULT NULL COMMENT '완료 처리 시각 (완료 탭 정렬·보존 기간 판단용)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_owner_due` (`owner_id`,`due_date`),
  KEY `idx_owner_status` (`owner_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='개인 할 일 (직원별 To-do)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_tasks`
--

LOCK TABLES `personal_tasks` WRITE;
/*!40000 ALTER TABLE `personal_tasks` DISABLE KEYS */;
INSERT INTO `personal_tasks` VALUES (1,2,'경비 정산 제출','경비 정산 제출 처리','2026-07-03','high','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,2,'회의록 정리','회의록 정리 처리','2026-07-02','urgent','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,2,'신규 입사자 온보딩','신규 입사자 온보딩 처리','2026-07-03','low','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,2,'월말 마감 점검','월말 마감 점검 처리','2026-07-04','normal','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,4,'신규 입사자 온보딩','신규 입사자 온보딩 처리','2026-07-03','low','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,4,'월말 마감 점검','월말 마감 점검 처리','2026-07-04','normal','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(7,4,'디자인 시안 검토','디자인 시안 검토 처리','2026-07-05','high','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(8,6,'디자인 시안 검토','디자인 시안 검토 처리','2026-07-05','high','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(9,6,'코드 리뷰','코드 리뷰 처리','2026-07-06','urgent','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(10,8,'계약서 검토','계약서 검토 처리','2026-07-07','low','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(11,8,'비품 발주','비품 발주 처리','2026-07-08','normal','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(12,8,'주간 보고서 작성','주간 보고서 작성 처리','2026-07-09','high','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(13,8,'거래처 견적 회신','거래처 견적 회신 처리','2026-07-10','urgent','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(14,10,'주간 보고서 작성','주간 보고서 작성 처리','2026-07-09','high','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(15,10,'거래처 견적 회신','거래처 견적 회신 처리','2026-07-10','urgent','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(16,10,'경비 정산 제출','경비 정산 제출 처리','2026-07-11','low','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(17,12,'경비 정산 제출','경비 정산 제출 처리','2026-07-11','low','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(18,12,'회의록 정리','회의록 정리 처리','2026-07-12','normal','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(19,14,'신규 입사자 온보딩','신규 입사자 온보딩 처리','2026-07-05','high','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(20,14,'월말 마감 점검','월말 마감 점검 처리','2026-07-04','urgent','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(21,14,'디자인 시안 검토','디자인 시안 검토 처리','2026-07-03','low','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(22,14,'코드 리뷰','코드 리뷰 처리','2026-07-02','normal','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(23,16,'디자인 시안 검토','디자인 시안 검토 처리','2026-07-03','low','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(24,16,'코드 리뷰','코드 리뷰 처리','2026-07-02','normal','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(25,16,'계약서 검토','계약서 검토 처리','2026-07-03','high','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(26,18,'계약서 검토','계약서 검토 처리','2026-07-03','high','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(27,18,'비품 발주','비품 발주 처리','2026-07-04','urgent','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(28,20,'주간 보고서 작성','주간 보고서 작성 처리','2026-07-05','low','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(29,20,'거래처 견적 회신','거래처 견적 회신 처리','2026-07-06','normal','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(30,20,'경비 정산 제출','경비 정산 제출 처리','2026-07-07','high','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(31,20,'회의록 정리','회의록 정리 처리','2026-07-08','urgent','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(32,22,'경비 정산 제출','경비 정산 제출 처리','2026-07-07','high','done','2026-07-02 19:58:24','2026-07-03 02:58:24','2026-07-03 02:58:24'),(33,22,'회의록 정리','회의록 정리 처리','2026-07-08','urgent','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24'),(34,22,'신규 입사자 온보딩','신규 입사자 온보딩 처리','2026-07-09','low','todo',NULL,'2026-07-03 02:58:24','2026-07-03 02:58:24');
/*!40000 ALTER TABLE `personal_tasks` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservation_resource_config`
--

LOCK TABLES `reservation_resource_config` WRITE;
/*!40000 ALTER TABLE `reservation_resource_config` DISABLE KEYS */;
INSERT INTO `reservation_resource_config` VALUES (1,58,1,'2026-07-03 02:58:24'),(2,59,1,'2026-07-03 02:58:24'),(3,60,3,'2026-07-03 02:58:24'),(4,61,2,'2026-07-03 02:58:24');
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,58,'주간 팀 회의','김대표','2026-07-04','09:00:00','10:00:00','주간 팀 회의 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(2,59,'1:1 미팅','이이사','2026-07-03','10:00:00','11:00:00','1:1 미팅 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(3,60,'프로젝트 킥오프','박이사','2026-07-02','11:00:00','12:00:00','프로젝트 킥오프 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(4,61,'고객사 미팅 준비','최이사','2026-07-03','12:00:00','13:00:00','고객사 미팅 준비 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(5,58,'디자인 리뷰','정부장','2026-07-04','13:00:00','14:00:00','디자인 리뷰 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(6,59,'스프린트 회고','한부장','2026-07-05','14:00:00','15:00:00','스프린트 회고 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(7,60,'채용 면접','오부장','2026-07-06','15:00:00','16:00:00','채용 면접 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(8,61,'교육 세션','강부장','2026-07-07','09:00:00','10:00:00','교육 세션 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(9,58,'주간 팀 회의','윤과장','2026-07-08','10:00:00','11:00:00','주간 팀 회의 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(10,59,'1:1 미팅','임과장','2026-07-09','11:00:00','12:00:00','1:1 미팅 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(11,60,'프로젝트 킥오프','서과장','2026-07-10','12:00:00','13:00:00','프로젝트 킥오프 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(12,61,'고객사 미팅 준비','류과장','2026-07-11','13:00:00','14:00:00','고객사 미팅 준비 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(13,58,'디자인 리뷰','김대리','2026-07-12','14:00:00','15:00:00','디자인 리뷰 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24'),(14,59,'스프린트 회고','이대리','2026-07-13','15:00:00','16:00:00','스프린트 회고 예약','confirmed','2026-07-03 02:58:24','2026-07-03 02:58:24');
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
INSERT INTO `schedule_attendees` VALUES (1,1,1,'pending','2026-04-02 11:35:58'),(2,1,2,'pending','2026-04-02 11:35:58'),(3,1,3,'pending','2026-04-02 11:35:58'),(4,2,1,'pending','2026-04-02 11:35:58'),(5,2,4,'pending','2026-04-02 11:35:58'),(6,3,1,'pending','2026-04-02 11:35:58'),(7,3,2,'pending','2026-04-02 11:35:58'),(8,5,1,'pending','2026-04-02 11:35:58'),(9,5,2,'pending','2026-04-02 11:35:58'),(10,5,3,'pending','2026-04-02 11:35:58'),(11,5,4,'pending','2026-04-02 11:35:58'),(12,6,1,'pending','2026-04-02 11:35:58'),(13,7,1,'pending','2026-04-02 11:35:58'),(14,7,2,'pending','2026-04-02 11:35:58'),(15,7,3,'pending','2026-04-02 11:35:58'),(16,7,4,'pending','2026-04-02 11:35:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedule_category_config`
--

LOCK TABLES `schedule_category_config` WRITE;
/*!40000 ALTER TABLE `schedule_category_config` DISABLE KEYS */;
INSERT INTO `schedule_category_config` VALUES (1,62,'blue'),(2,63,'green'),(3,64,'red'),(4,65,'purple'),(5,66,'yellow'),(6,91,'orange'),(7,92,'teal'),(8,93,'pink'),(9,94,'gray');
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
  `custom_calendar_id` int DEFAULT NULL,
  `creator_id` int NOT NULL COMMENT 'employees.id (작성자)',
  `visibility` enum('public','private','department') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'public' COMMENT '공개범위',
  `is_important` tinyint(1) NOT NULL DEFAULT '0',
  `recurrence_rule` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Phase 2: 반복규칙',
  `status` enum('active','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_date_range` (`start_date`,`end_date`),
  KEY `idx_creator` (`creator_id`),
  KEY `idx_status` (`status`),
  KEY `idx_custom_calendar` (`custom_calendar_id`),
  KEY `idx_schedules_important` (`is_important`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`custom_calendar_id`) REFERENCES `custom_calendars` (`id`) ON DELETE SET NULL,
  CONSTRAINT `schedules_ibfk_3` FOREIGN KEY (`custom_calendar_id`) REFERENCES `custom_calendars` (`id`) ON DELETE SET NULL,
  CONSTRAINT `schedules_ibfk_4` FOREIGN KEY (`custom_calendar_id`) REFERENCES `custom_calendars` (`id`) ON DELETE SET NULL,
  CONSTRAINT `schedules_ibfk_5` FOREIGN KEY (`custom_calendar_id`) REFERENCES `custom_calendars` (`id`) ON DELETE SET NULL,
  CONSTRAINT `schedules_ibfk_6` FOREIGN KEY (`custom_calendar_id`) REFERENCES `custom_calendars` (`id`) ON DELETE SET NULL,
  CONSTRAINT `schedules_ibfk_7` FOREIGN KEY (`custom_calendar_id`) REFERENCES `custom_calendars` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (1,'전사 회의','4월 경영 현황 보고 및 목표 점검','2026-04-03','10:00:00','2026-04-03','11:00:00',0,62,NULL,1,'public',0,NULL,'active','2026-04-02 11:35:58','2026-04-02 11:35:58'),(2,'프로젝트 킥오프','신규 웹서비스 프로젝트 시작','2026-04-07','14:00:00','2026-04-07','15:30:00',0,63,NULL,1,'public',0,NULL,'active','2026-04-02 11:35:58','2026-04-02 11:35:58'),(3,'디자인 리뷰','UI/UX 시안 검토','2026-04-10','11:00:00','2026-04-10','12:00:00',0,62,NULL,1,'public',0,NULL,'active','2026-04-02 11:35:58','2026-04-02 11:35:58'),(4,'고객사 출장','서울 본사 미팅','2026-04-14',NULL,'2026-04-15',NULL,1,64,NULL,1,'public',0,NULL,'active','2026-04-02 11:35:58','2026-04-02 11:35:58'),(5,'월간 보고','4월 실적 정리 및 보고','2026-04-18','09:00:00','2026-04-18','10:00:00',0,62,NULL,1,'public',0,NULL,'active','2026-04-02 11:35:58','2026-04-02 11:35:58'),(6,'신입사원 교육','온보딩 프로그램 1일차','2026-04-21','09:00:00','2026-04-21','17:00:00',0,65,NULL,1,'public',0,NULL,'active','2026-04-02 11:35:58','2026-04-02 11:35:58'),(7,'기술 세미나','AI 트렌드 세미나','2026-04-24','14:00:00','2026-04-24','16:00:00',0,65,NULL,1,'public',0,NULL,'active','2026-04-02 11:35:58','2026-04-02 11:35:58'),(8,'월말 정산','4월 경비 정산 마감','2026-04-28','17:00:00','2026-04-28','18:00:00',0,66,NULL,1,'public',0,NULL,'active','2026-04-02 11:35:58','2026-04-02 11:35:58'),(9,'누끼토끼 미팅','','2026-04-06','14:00:00','2026-04-06','15:00:00',0,63,NULL,4,'public',0,NULL,'active','2026-04-02 14:12:53','2026-04-02 14:12:53');
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
  `region` enum('수도권','비수도권') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '수도권',
  `youth_credit_per` bigint NOT NULL DEFAULT '11000000' COMMENT '청년 인당 공제액',
  `elder_credit_per` bigint NOT NULL DEFAULT '7700000' COMMENT '장년 인당 공제액',
  `total_credit` bigint NOT NULL DEFAULT '0' COMMENT '총 세액공제액',
  `memo` text COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='세액공제 시뮬레이션 이력';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_credit_simulations`
--

LOCK TABLES `tax_credit_simulations` WRITE;
/*!40000 ALTER TABLE `tax_credit_simulations` DISABLE KEYS */;
INSERT INTO `tax_credit_simulations` VALUES (1,1,2025,18,20,5,1,'수도권',11000000,7700000,62700000,'2025년 고용증대 세액공제 시뮬레이션','2026-07-03 02:43:01'),(2,1,2026,20,22,6,1,'수도권',11000000,7700000,73700000,'2026년 고용증대 세액공제 시뮬레이션','2026-07-03 02:43:01');
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
  `item_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT '품목명',
  `spec` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '규격',
  `quantity` decimal(12,2) NOT NULL DEFAULT '1.00' COMMENT '수량',
  `unit_price` bigint NOT NULL DEFAULT '0' COMMENT '단가',
  `supply_amount` bigint NOT NULL DEFAULT '0' COMMENT '공급가액',
  `tax_amount` bigint NOT NULL DEFAULT '0' COMMENT '세액',
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `tax_invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `tax_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='세금계산서 품목';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_invoice_items`
--

LOCK TABLES `tax_invoice_items` WRITE;
/*!40000 ALTER TABLE `tax_invoice_items` DISABLE KEYS */;
INSERT INTO `tax_invoice_items` VALUES (1,15,'2026-03-04','소프트웨어 개발 용역','월정액',1.00,1500000,1500000,150000),(2,16,'2026-03-12','유지보수 계약','월정액',1.00,2350000,2350000,235000),(3,17,'2026-03-20','기술 컨설팅','월정액',1.00,3200000,3200000,320000),(4,18,'2026-03-06','사무용품','',1.00,300000,300000,30000),(5,19,'2026-03-13','클라우드 서버 이용료','',1.00,940000,940000,94000),(6,20,'2026-03-20','통신비','',1.00,1580000,1580000,158000),(7,21,'2026-04-04','유지보수 계약','월정액',1.00,4050000,4050000,405000),(8,22,'2026-04-12','기술 컨설팅','월정액',1.00,4900000,4900000,490000),(9,23,'2026-04-20','라이선스 사용료','월정액',1.00,5750000,5750000,575000),(10,24,'2026-04-06','물류 대행 수수료','',1.00,2220000,2220000,222000),(11,25,'2026-04-13','전자부품 매입','',1.00,2860000,2860000,286000),(12,26,'2026-04-20','사무용품','',1.00,300000,300000,30000),(13,27,'2026-05-04','기술 컨설팅','월정액',1.00,1500000,1500000,150000),(14,28,'2026-05-12','라이선스 사용료','월정액',1.00,2350000,2350000,235000),(15,29,'2026-05-20','소프트웨어 개발 용역','월정액',1.00,3200000,3200000,320000),(16,30,'2026-05-06','클라우드 서버 이용료','',1.00,940000,940000,94000),(17,31,'2026-05-13','통신비','',1.00,1580000,1580000,158000),(18,32,'2026-05-20','물류 대행 수수료','',1.00,2220000,2220000,222000),(19,33,'2026-06-04','라이선스 사용료','월정액',1.00,4050000,4050000,405000),(20,34,'2026-06-12','소프트웨어 개발 용역','월정액',1.00,4900000,4900000,490000),(21,35,'2026-06-20','유지보수 계약','월정액',1.00,5750000,5750000,575000),(22,36,'2026-06-06','전자부품 매입','',1.00,2860000,2860000,286000),(23,37,'2026-06-13','사무용품','',1.00,300000,300000,30000),(24,38,'2026-06-20','클라우드 서버 이용료','',1.00,940000,940000,94000),(25,39,'2026-07-04','소프트웨어 개발 용역','월정액',1.00,1500000,1500000,150000),(26,40,'2026-07-12','유지보수 계약','월정액',1.00,2350000,2350000,235000),(27,41,'2026-07-20','기술 컨설팅','월정액',1.00,3200000,3200000,320000),(28,42,'2026-07-06','통신비','',1.00,1580000,1580000,158000),(29,43,'2026-07-13','물류 대행 수수료','',1.00,2220000,2220000,222000),(30,44,'2026-07-20','전자부품 매입','',1.00,2860000,2860000,286000),(31,1,'2026-02-01','용역 대금','',1.00,15000000,15000000,1500000),(32,2,'2026-02-05','용역 대금','',1.00,8500000,8500000,850000),(33,3,'2026-02-10','용역 대금','',1.00,3200000,3200000,320000),(34,4,'2026-02-15','용역 대금','',1.00,12000000,12000000,1200000),(35,5,'2026-02-18','용역 대금','',1.00,5500000,5500000,550000),(36,6,'2026-02-20','용역 대금','',1.00,2000000,2000000,0),(37,7,'2026-02-25','용역 대금','',1.00,9800000,9800000,980000),(38,8,'2026-02-03','매입 대금','',1.00,4200000,4200000,420000),(39,9,'2026-02-05','매입 대금','',1.00,1800000,1800000,180000),(40,10,'2026-02-10','매입 대금','',1.00,650000,650000,65000),(41,11,'2026-02-12','매입 대금','',1.00,3800000,3800000,380000),(42,12,'2026-02-15','매입 대금','',1.00,3500000,3500000,350000),(43,13,'2026-02-20','매입 대금','',1.00,980000,980000,98000),(44,14,'2026-02-22','매입 대금','',1.00,2500000,2500000,250000);
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
  `invoice_type` enum('매출','매입') COLLATE utf8mb4_general_ci NOT NULL COMMENT '매출/매입 구분',
  `invoice_number` varchar(24) COLLATE utf8mb4_general_ci NOT NULL COMMENT '세금계산서 승인번호',
  `issue_date` date NOT NULL COMMENT '작성일자',
  `send_date` date DEFAULT NULL COMMENT '전송일자',
  `supplier_bizno` varchar(12) COLLATE utf8mb4_general_ci NOT NULL COMMENT '공급자 사업자번호',
  `supplier_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '공급자 상호',
  `supplier_ceo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '공급자 대표자',
  `buyer_bizno` varchar(12) COLLATE utf8mb4_general_ci NOT NULL COMMENT '공급받는자 사업자번호',
  `buyer_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT '공급받는자 상호',
  `buyer_ceo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '공급받는자 대표자',
  `supply_amount` bigint NOT NULL DEFAULT '0' COMMENT '공급가액',
  `tax_amount` bigint NOT NULL DEFAULT '0' COMMENT '세액',
  `total_amount` bigint NOT NULL DEFAULT '0' COMMENT '합계금액',
  `tax_type` enum('과세','영세율','면세') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '과세' COMMENT '과세유형',
  `invoice_status` enum('정상','수정','취소') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '정상',
  `hometax_sync` tinyint(1) NOT NULL DEFAULT '0' COMMENT '홈택스 동기화 여부',
  `synced_at` datetime DEFAULT NULL COMMENT '마지막 동기화 시각',
  `memo` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_date` (`invoice_type`,`issue_date`),
  KEY `idx_supplier` (`supplier_bizno`),
  KEY `idx_buyer` (`buyer_bizno`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='세금계산서';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_invoices`
--

LOCK TABLES `tax_invoices` WRITE;
/*!40000 ALTER TABLE `tax_invoices` DISABLE KEYS */;
INSERT INTO `tax_invoices` VALUES (1,1,'매출','20260201-41000001-000000','2026-02-01','2026-02-02','123-45-67890','주식회사 재밋','송승환','234-56-78901','(주)테크솔루션','김태호',15000000,1500000,16500000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(2,1,'매출','20260205-41000001-000000','2026-02-05','2026-02-06','123-45-67890','주식회사 재밋','송승환','345-67-89012','디자인웍스','박지연',8500000,850000,9350000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(3,1,'매출','20260210-41000001-000000','2026-02-10','2026-02-11','123-45-67890','주식회사 재밋','송승환','456-78-90123','(주)스마트커머스','이준혁',3200000,320000,3520000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(4,1,'매출','20260215-41000001-000000','2026-02-15','2026-02-16','123-45-67890','주식회사 재밋','송승환','567-89-01234','한국데이터','최수진',12000000,1200000,13200000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(5,1,'매출','20260218-41000001-000000','2026-02-18','2026-02-19','123-45-67890','주식회사 재밋','송승환','234-56-78901','(주)테크솔루션','김태호',5500000,550000,6050000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(6,1,'매출','20260220-41000001-000000','2026-02-20','2026-02-21','123-45-67890','주식회사 재밋','송승환','678-90-12345','그린에너지(주)','정민우',2000000,0,2000000,'영세율','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(7,1,'매출','20260225-41000001-000000','2026-02-25','2026-02-26','123-45-67890','주식회사 재밋','송승환','789-01-23456','(주)미래건설','한동훈',9800000,980000,10780000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(8,1,'매입','20260203-52000001-000000','2026-02-03','2026-02-04','890-12-34567','NHN클라우드(주)','김동훈','123-45-67890','주식회사 재밋','송승환',4200000,420000,4620000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(9,1,'매입','20260205-52000001-000000','2026-02-05','2026-02-06','901-23-45678','(주)오피스허브','윤서영','123-45-67890','주식회사 재밋','송승환',1800000,180000,1980000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(10,1,'매입','20260210-52000001-000000','2026-02-10','2026-02-11','012-34-56789','세종사무기기','강현수','123-45-67890','주식회사 재밋','송승환',650000,65000,715000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(11,1,'매입','20260212-52000001-000000','2026-02-12','2026-02-13','890-12-34567','NHN클라우드(주)','김동훈','123-45-67890','주식회사 재밋','송승환',3800000,380000,4180000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(12,1,'매입','20260215-52000001-000000','2026-02-15','2026-02-16','234-56-00001','(주)디지털마케팅','오지훈','123-45-67890','주식회사 재밋','송승환',3500000,350000,3850000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(13,1,'매입','20260220-52000001-000000','2026-02-20','2026-02-21','345-67-00002','코리아호스팅','임재현','123-45-67890','주식회사 재밋','송승환',980000,98000,1078000,'과세','정상',1,'2026-02-28 09:00:00',NULL,'2026-03-22 15:18:33'),(14,1,'매입','20260222-52000001-000000','2026-02-22',NULL,'456-78-00003','인테리어플러스','배수진','123-45-67890','주식회사 재밋','송승환',2500000,250000,2750000,'과세','수정',0,NULL,NULL,'2026-03-22 15:18:33'),(15,1,'매출','20260304-41000001-000001','2026-03-04','2026-03-04','123-45-67890','주식회사 재밋','송승환','234-56-78901','(주)테크솔루션','김태호',1500000,150000,1650000,'과세','정상',1,'2026-03-04 09:00:00',NULL,'2026-07-03 02:43:01'),(16,1,'매출','20260312-41000001-000002','2026-03-12','2026-03-12','123-45-67890','주식회사 재밋','송승환','345-67-89012','디자인웍스','박지연',2350000,235000,2585000,'과세','정상',1,'2026-03-12 09:00:00',NULL,'2026-07-03 02:43:01'),(17,1,'매출','20260320-41000001-000003','2026-03-20','2026-03-20','123-45-67890','주식회사 재밋','송승환','456-78-90123','(주)스마트커머스','이준혁',3200000,320000,3520000,'과세','정상',1,'2026-03-20 09:00:00',NULL,'2026-07-03 02:43:01'),(18,1,'매입','20260306-02000002-000001','2026-03-06','2026-03-06','111-22-33344','(주)오피스마트','고영수','123-45-67890','주식회사 재밋','송승환',300000,30000,330000,'과세','정상',1,'2026-03-06 09:00:00',NULL,'2026-07-03 02:43:01'),(19,1,'매입','20260313-12000002-000002','2026-03-13','2026-03-13','222-33-44455','클라우드호스팅','남기훈','123-45-67890','주식회사 재밋','송승환',940000,94000,1034000,'과세','정상',1,'2026-03-13 09:00:00',NULL,'2026-07-03 02:43:01'),(20,1,'매입','20260320-22000002-000003','2026-03-20','2026-03-20','333-44-55566','한빛통신','조현우','123-45-67890','주식회사 재밋','송승환',1580000,158000,1738000,'과세','정상',1,'2026-03-20 09:00:00',NULL,'2026-07-03 02:43:01'),(21,1,'매출','20260404-41000001-000001','2026-04-04','2026-04-04','123-45-67890','주식회사 재밋','송승환','567-89-01234','한국데이터','최수진',4050000,405000,4455000,'과세','정상',1,'2026-04-04 09:00:00',NULL,'2026-07-03 02:43:01'),(22,1,'매출','20260412-41000001-000002','2026-04-12','2026-04-12','123-45-67890','주식회사 재밋','송승환','678-90-12345','(주)클라우드베이스','정민우',4900000,490000,5390000,'과세','정상',1,'2026-04-12 09:00:00',NULL,'2026-07-03 02:43:01'),(23,1,'매출','20260420-41000001-000003','2026-04-20','2026-04-20','123-45-67890','주식회사 재밋','송승환','789-01-23456','넥스트비즈','한서영',5750000,575000,6325000,'과세','정상',1,'2026-04-20 09:00:00',NULL,'2026-07-03 02:43:01'),(24,1,'매입','20260406-02000002-000001','2026-04-06','2026-04-06','444-55-66677','스마트물류','백승현','123-45-67890','주식회사 재밋','송승환',2220000,222000,2442000,'과세','정상',1,'2026-04-06 09:00:00',NULL,'2026-07-03 02:43:01'),(25,1,'매입','20260413-12000002-000002','2026-04-13','2026-04-13','555-66-77788','대한전자','문태식','123-45-67890','주식회사 재밋','송승환',2860000,286000,3146000,'과세','정상',1,'2026-04-13 09:00:00',NULL,'2026-07-03 02:43:01'),(26,1,'매입','20260420-22000002-000003','2026-04-20','2026-04-20','111-22-33344','(주)오피스마트','고영수','123-45-67890','주식회사 재밋','송승환',300000,30000,330000,'과세','정상',1,'2026-04-20 09:00:00',NULL,'2026-07-03 02:43:01'),(27,1,'매출','20260504-41000001-000001','2026-05-04','2026-05-04','123-45-67890','주식회사 재밋','송승환','234-56-78901','(주)테크솔루션','김태호',1500000,150000,1650000,'과세','정상',1,'2026-05-04 09:00:00',NULL,'2026-07-03 02:43:01'),(28,1,'매출','20260512-41000001-000002','2026-05-12','2026-05-12','123-45-67890','주식회사 재밋','송승환','345-67-89012','디자인웍스','박지연',2350000,235000,2585000,'과세','정상',1,'2026-05-12 09:00:00',NULL,'2026-07-03 02:43:01'),(29,1,'매출','20260520-41000001-000003','2026-05-20','2026-05-20','123-45-67890','주식회사 재밋','송승환','456-78-90123','(주)스마트커머스','이준혁',3200000,320000,3520000,'과세','정상',1,'2026-05-20 09:00:00',NULL,'2026-07-03 02:43:01'),(30,1,'매입','20260506-02000002-000001','2026-05-06','2026-05-06','222-33-44455','클라우드호스팅','남기훈','123-45-67890','주식회사 재밋','송승환',940000,94000,1034000,'과세','정상',1,'2026-05-06 09:00:00',NULL,'2026-07-03 02:43:01'),(31,1,'매입','20260513-12000002-000002','2026-05-13','2026-05-13','333-44-55566','한빛통신','조현우','123-45-67890','주식회사 재밋','송승환',1580000,158000,1738000,'과세','정상',1,'2026-05-13 09:00:00',NULL,'2026-07-03 02:43:01'),(32,1,'매입','20260520-22000002-000003','2026-05-20','2026-05-20','444-55-66677','스마트물류','백승현','123-45-67890','주식회사 재밋','송승환',2220000,222000,2442000,'과세','정상',1,'2026-05-20 09:00:00',NULL,'2026-07-03 02:43:01'),(33,1,'매출','20260604-41000001-000001','2026-06-04','2026-06-04','123-45-67890','주식회사 재밋','송승환','567-89-01234','한국데이터','최수진',4050000,405000,4455000,'과세','정상',1,'2026-06-04 09:00:00',NULL,'2026-07-03 02:43:01'),(34,1,'매출','20260612-41000001-000002','2026-06-12','2026-06-12','123-45-67890','주식회사 재밋','송승환','678-90-12345','(주)클라우드베이스','정민우',4900000,490000,5390000,'과세','정상',1,'2026-06-12 09:00:00',NULL,'2026-07-03 02:43:01'),(35,1,'매출','20260620-41000001-000003','2026-06-20','2026-06-20','123-45-67890','주식회사 재밋','송승환','789-01-23456','넥스트비즈','한서영',5750000,575000,6325000,'과세','정상',1,'2026-06-20 09:00:00',NULL,'2026-07-03 02:43:01'),(36,1,'매입','20260606-02000002-000001','2026-06-06','2026-06-06','555-66-77788','대한전자','문태식','123-45-67890','주식회사 재밋','송승환',2860000,286000,3146000,'과세','정상',1,'2026-06-06 09:00:00',NULL,'2026-07-03 02:43:01'),(37,1,'매입','20260613-12000002-000002','2026-06-13','2026-06-13','111-22-33344','(주)오피스마트','고영수','123-45-67890','주식회사 재밋','송승환',300000,30000,330000,'과세','정상',1,'2026-06-13 09:00:00',NULL,'2026-07-03 02:43:01'),(38,1,'매입','20260620-22000002-000003','2026-06-20','2026-06-20','222-33-44455','클라우드호스팅','남기훈','123-45-67890','주식회사 재밋','송승환',940000,94000,1034000,'과세','정상',1,'2026-06-20 09:00:00',NULL,'2026-07-03 02:43:01'),(39,1,'매출','20260704-41000001-000001','2026-07-04','2026-07-04','123-45-67890','주식회사 재밋','송승환','234-56-78901','(주)테크솔루션','김태호',1500000,150000,1650000,'과세','정상',1,'2026-07-04 09:00:00',NULL,'2026-07-03 02:43:01'),(40,1,'매출','20260712-41000001-000002','2026-07-12','2026-07-12','123-45-67890','주식회사 재밋','송승환','345-67-89012','디자인웍스','박지연',2350000,235000,2585000,'과세','정상',1,'2026-07-12 09:00:00',NULL,'2026-07-03 02:43:01'),(41,1,'매출','20260720-41000001-000003','2026-07-20','2026-07-20','123-45-67890','주식회사 재밋','송승환','456-78-90123','(주)스마트커머스','이준혁',3200000,320000,3520000,'과세','정상',1,'2026-07-20 09:00:00',NULL,'2026-07-03 02:43:01'),(42,1,'매입','20260706-02000002-000001','2026-07-06','2026-07-06','333-44-55566','한빛통신','조현우','123-45-67890','주식회사 재밋','송승환',1580000,158000,1738000,'과세','정상',1,'2026-07-06 09:00:00',NULL,'2026-07-03 02:43:01'),(43,1,'매입','20260713-12000002-000002','2026-07-13','2026-07-13','444-55-66677','스마트물류','백승현','123-45-67890','주식회사 재밋','송승환',2220000,222000,2442000,'과세','정상',1,'2026-07-13 09:00:00',NULL,'2026-07-03 02:43:01'),(44,1,'매입','20260720-22000002-000003','2026-07-20','2026-07-20','555-66-77788','대한전자','문태식','123-45-67890','주식회사 재밋','송승환',2860000,286000,3146000,'과세','정상',1,'2026-07-20 09:00:00',NULL,'2026-07-03 02:43:01');
/*!40000 ALTER TABLE `tax_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `terminology_display_config`
--

DROP TABLE IF EXISTS `terminology_display_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminology_display_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `context_key` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT '맥락 키 (default, org_chart, approval, board, profile)',
  `format_pattern` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '{name} {rank}' COMMENT '표시 패턴 ({name},{rank},{duty},{position},{dept},{suffix})',
  `suffix` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '호칭 접미사 (님, 씨 등)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_context` (`context_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terminology_display_config`
--

LOCK TABLES `terminology_display_config` WRITE;
/*!40000 ALTER TABLE `terminology_display_config` DISABLE KEYS */;
INSERT INTO `terminology_display_config` VALUES (1,'default','{name} {rank}',NULL,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(2,'org_chart','{name} {duty}',NULL,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(3,'approval','{name} {rank}',NULL,1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(4,'board','{name}{suffix}','님',1,'2026-07-03 01:24:12','2026-07-03 01:24:12'),(5,'profile','{name} {rank} / {dept} {duty}',NULL,1,'2026-07-03 01:24:12','2026-07-03 01:24:12');
/*!40000 ALTER TABLE `terminology_display_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_dashboard_widgets`
--

DROP TABLE IF EXISTS `user_dashboard_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_dashboard_widgets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `widget_id` varchar(50) NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_widget` (`employee_id`,`widget_id`),
  CONSTRAINT `user_dashboard_widgets_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_dashboard_widgets`
--

LOCK TABLES `user_dashboard_widgets` WRITE;
/*!40000 ALTER TABLE `user_dashboard_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_dashboard_widgets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-03  3:14:50
