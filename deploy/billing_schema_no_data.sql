-- MySQL dump 10.13  Distrib 8.0.44, for Linux (aarch64)
--
-- Host: localhost    Database: billing
-- ------------------------------------------------------
-- Server version	8.0.44

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
-- Table structure for table `billing_robo_demands`
--

DROP TABLE IF EXISTS `billing_robo_demands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_robo_demands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `demand_number` bigint unsigned DEFAULT NULL COMMENT '請求管理ロボ 請求情報番号',
  `demand_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '請求管理ロボ 請求情報コード',
  `demand_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '種別: initial / recurring 等',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_robo_demands_contract_id_index` (`contract_id`),
  KEY `billing_robo_demands_demand_number_index` (`demand_number`),
  KEY `billing_robo_demands_demand_code_index` (`demand_code`),
  CONSTRAINT `billing_robo_demands_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contract_form_urls`
--

DROP TABLE IF EXISTS `contract_form_urls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_form_urls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '閲覧用トークン（申込フォームURLの場合はNULL）',
  `url` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '生成されたURL',
  `plan_ids` json NOT NULL COMMENT '選択された契約プランIDの配列',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL名（管理用メモ）',
  `expires_at` timestamp NOT NULL COMMENT '有効期限（申込フォームURLの場合は長期間有効）',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '有効フラグ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_form_urls_token_unique` (`token`),
  KEY `contract_form_urls_is_active_expires_at_index` (`is_active`,`expires_at`),
  KEY `contract_form_urls_token_index` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contract_items`
--

DROP TABLE IF EXISTS `contract_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `contract_plan_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商品名（スナップショット）',
  `product_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商品コード（スナップショット）',
  `quantity` int unsigned NOT NULL DEFAULT '1' COMMENT '数量',
  `unit_price` int unsigned NOT NULL COMMENT '単価（スナップショット）',
  `subtotal` int unsigned NOT NULL COMMENT '小計',
  `billing_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time' COMMENT '決済タイプスナップショット: monthly / one_time',
  `product_attributes` json DEFAULT NULL COMMENT '商品属性（スナップショット）',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_items_contract_id_index` (`contract_id`),
  KEY `contract_items_product_id_index` (`product_id`),
  KEY `contract_items_contract_plan_id_index` (`contract_plan_id`),
  CONSTRAINT `contract_items_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_items_contract_plan_id_foreign` FOREIGN KEY (`contract_plan_id`) REFERENCES `contract_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contract_plan_option_products`
--

DROP TABLE IF EXISTS `contract_plan_option_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_plan_option_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_plan_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_plan_option_product_unique` (`contract_plan_id`,`product_id`),
  KEY `contract_plan_option_products_contract_plan_id_index` (`contract_plan_id`),
  KEY `contract_plan_option_products_product_id_index` (`product_id`),
  CONSTRAINT `contract_plan_option_products_contract_plan_id_foreign` FOREIGN KEY (`contract_plan_id`) REFERENCES `contract_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_plan_option_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contract_plans`
--

DROP TABLE IF EXISTS `contract_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'プランコード（一意識別子）',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'プラン名',
  `price` int unsigned NOT NULL COMMENT '料金（税込）',
  `billing_type` enum('one_time','monthly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time' COMMENT '決済タイプ（one_time: 一回限り, monthly: 月額課金）',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'プラン説明',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '有効フラグ',
  `display_order` int unsigned NOT NULL DEFAULT '0' COMMENT '表示順',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_plans_plan_code_unique` (`item`),
  KEY `contract_plans_is_active_display_order_index` (`is_active`,`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contract_statuses`
--

DROP TABLE IF EXISTS `contract_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ステータスコード（契約レコードの status に保存）',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表示名',
  `display_order` int unsigned NOT NULL DEFAULT '0' COMMENT '表示順',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '有効フラグ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_statuses_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contracts`
--

DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_plan_id` bigint unsigned DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `customer_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'F-REGI顧客番号（月額課金用）',
  `billing_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '請求管理ロボ 請求先コード',
  `billing_individual_number` bigint unsigned DEFAULT NULL COMMENT '請求管理ロボ 請求先部署番号',
  `billing_individual_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '請求管理ロボ 請求先部署コード',
  `billing_robo_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Billing-Robo決済モード: api3_standard | api5_immediate',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'applied' COMMENT '契約状態（contract_statuses.code を参照）',
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会社名',
  `company_name_kana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '会社名（フリガナ）',
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '部署名',
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '役職',
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '担当者名',
  `contact_name_kana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '担当者名（フリガナ）',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'メールアドレス',
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '電話番号',
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '郵便番号',
  `prefecture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '都道府県',
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '市区町村',
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '番地',
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '建物名',
  `usage_url_domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ご利用URL・ドメイン',
  `import_from_trial` tinyint(1) NOT NULL DEFAULT '0' COMMENT '体験版からのインポートを希望する',
  `desired_start_date` date NOT NULL COMMENT '利用開始希望日',
  `actual_start_date` date DEFAULT NULL COMMENT '実際の利用開始日',
  `end_date` date DEFAULT NULL COMMENT '利用終了日',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '備考',
  `card_last4` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'カード番号下4桁（表示用・特定不可）',
  `mail_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contracts_payment_id_foreign` (`payment_id`),
  KEY `contracts_status_created_at_index` (`status`,`created_at`),
  KEY `contracts_email_index` (`email`),
  KEY `contracts_desired_start_date_index` (`desired_start_date`),
  KEY `contracts_customer_id_index` (`customer_id`),
  KEY `contracts_contract_plan_id_foreign` (`contract_plan_id`),
  CONSTRAINT `contracts_contract_plan_id_foreign` FOREIGN KEY (`contract_plan_id`) REFERENCES `contract_plans` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `contracts_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_events`
--

DROP TABLE IF EXISTS `payment_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint unsigned NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'イベントタイプ: request, redirect, notify, return 等',
  `raw_query` text COLLATE utf8mb4_unicode_ci COMMENT '通知クエリ文字列の原文',
  `rp_gid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RP 決済番号（冪等キー）',
  `rp_acid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RP 自動課金番号',
  `payload` json DEFAULT NULL COMMENT 'ペイロード（JSON、マスク必須）',
  `created_at` timestamp NOT NULL COMMENT 'イベント発生日時',
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_events_event_type_rp_gid_unique` (`event_type`,`rp_gid`),
  KEY `payment_events_payment_id_event_type_index` (`payment_id`,`event_type`),
  KEY `payment_events_created_at_index` (`created_at`),
  KEY `payment_events_rp_gid_index` (`rp_gid`),
  CONSTRAINT `payment_events_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL COMMENT '会社ID',
  `provider` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '決済プロバイダ: robotpayment',
  `payment_kind` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'auto_initial / auto_recurring / normal',
  `merchant_order_no` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '当社 cod（店舗オーダー番号）',
  `billing_payment_method_number` bigint unsigned DEFAULT NULL COMMENT '請求管理ロボ 決済情報番号',
  `billing_payment_method_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '請求管理ロボ 決済情報コード',
  `rp_gid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RP 決済番号 gid',
  `rp_acid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RP 自動課金番号 acid',
  `contract_id` bigint unsigned DEFAULT NULL COMMENT '契約ID（既存テーブル参照前提）',
  `orderid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '自社採番オーダー番号',
  `settleno` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'F-REGI発行番号（SETTLENO）',
  `receiptno` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'F-REGI発行番号',
  `slipno` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'F-REGI伝票番号',
  `amount` int unsigned NOT NULL COMMENT '金額（税込）',
  `amount_initial` int unsigned DEFAULT NULL COMMENT '初回請求合計（ta）',
  `amount_recurring` int unsigned DEFAULT NULL COMMENT '次月以降請求合計',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'JPY' COMMENT '通貨コード',
  `payment_method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'card' COMMENT '支払方法',
  `status` enum('created','redirect_issued','waiting_notify','paid','failed','canceled','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created' COMMENT 'ステータス',
  `requested_at` timestamp NULL DEFAULT NULL COMMENT '請求日時',
  `notified_at` timestamp NULL DEFAULT NULL COMMENT '通知受領日時',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT '完了日時',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT '決済成立日時',
  `failure_reason` text COLLATE utf8mb4_unicode_ci COMMENT '失敗理由',
  `raw_notify_payload` json DEFAULT NULL COMMENT '通知ペイロード（JSON、アクセス制御必要）',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_company_id_order_no_unique` (`company_id`,`orderid`),
  UNIQUE KEY `payments_fregi_unique` (`receiptno`,`slipno`),
  KEY `payments_company_id_status_index` (`company_id`,`status`),
  KEY `payments_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商品コード',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商品名',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '商品説明',
  `unit_price` int unsigned NOT NULL DEFAULT '0' COMMENT '単価',
  `tax_category` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '税区分 0:外税 1:内税 2:対象外 3:非課税（請求管理ロボAPI用）',
  `tax` tinyint unsigned NOT NULL DEFAULT '10' COMMENT '消費税率 5/8/10（tax_category=0,1時。請求管理ロボAPI用）',
  `type` enum('plan','option','addon') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'option' COMMENT '商品種別',
  `billing_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time' COMMENT '決済タイプ（one_time: 一回限り, monthly: 月額課金）。オプション製品で利用。',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '有効フラグ',
  `display_order` int unsigned NOT NULL DEFAULT '0' COMMENT '表示順',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_code_unique` (`code`),
  KEY `products_is_active_type_display_order_index` (`is_active`,`type`,`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '設定キー',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '設定値',
  `value_text` longtext COLLATE utf8mb4_unicode_ci COMMENT 'プレーンテキスト版（検索・一覧用）',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '設定の説明',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_settings_key_unique` (`key`),
  KEY `site_settings_key_index` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'billing'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-23 14:53:39
