/*
SQLyog Ultimate
MySQL - 8.0.12 : Database - inventory
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `categories` */

CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `categories` */

insert  into `categories`(`id`,`name`,`slug`,`description`,`created_at`,`updated_at`) values (5,'Material Konstruksi','material-konstruksi','Material Bangunan','2026-04-21 14:36:11','2026-04-21 14:36:11');

/*Table structure for table `delivery_order_materials` */

CREATE TABLE `delivery_order_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_order_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `requested_volume` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_order_materials_delivery_order_id_foreign` (`delivery_order_id`),
  KEY `delivery_order_materials_material_id_foreign` (`material_id`),
  CONSTRAINT `delivery_order_materials_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_order_materials_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `delivery_order_materials` */

/*Table structure for table `delivery_orders` */

CREATE TABLE `delivery_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `surat_jalan_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `lokasi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pemohon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `petugas` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penerima` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_polisi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pelaksana_kecamatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','processing','shipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_orders_surat_jalan_no_unique` (`surat_jalan_no`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `delivery_orders` */

/*Table structure for table `failed_jobs` */

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `failed_jobs` */

/*Table structure for table `inventory_transactions` */

CREATE TABLE `inventory_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_order_id` bigint(20) unsigned DEFAULT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `type` enum('in','out') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'out',
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `volume_masuk` decimal(10,2) NOT NULL DEFAULT '0.00',
  `volume_keluar` decimal(10,2) NOT NULL DEFAULT '0.00',
  `balance_after` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Saldo sisa volume setelah transaksi',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_delivery_order_id_foreign` (`delivery_order_id`),
  KEY `inventory_transactions_material_id_foreign` (`material_id`),
  KEY `inventory_transactions_user_id_foreign` (`user_id`),
  CONSTRAINT `inventory_transactions_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `inventory_transactions` */

/*Table structure for table `materials` */

CREATE TABLE `materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pcs',
  `current_volume` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `materials_category_id_foreign` (`category_id`),
  CONSTRAINT `materials_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `materials` */

insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (9,5,'KARUNG PLASTIK','Lembar',683527.00,'2026-04-21 14:36:50','2026-04-21 16:00:35');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (10,5,'PAKU','Kg',0.00,'2026-04-21 14:39:09','2026-04-21 14:39:09');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (11,5,'SEMEN','Zak',0.00,'2026-04-21 14:39:29','2026-04-21 14:39:29');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (12,5,'PASIR BETON','M3',0.00,'2026-04-21 14:39:51','2026-04-21 14:39:51');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (13,5,'BESI 8','Batang',0.00,'2026-04-21 14:40:12','2026-04-21 14:40:12');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (14,5,'BESI 10','Batang',0.00,'2026-04-21 14:40:38','2026-04-21 14:40:38');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (15,5,'BESI 12','Batang',0.00,'2026-04-21 14:40:52','2026-04-21 14:40:52');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (16,5,'TRIPLEK','Lembar',0.00,'2026-04-21 14:41:10','2026-04-21 14:41:10');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (17,5,'PLAT HD','Buah',0.00,'2026-04-21 14:41:30','2026-04-21 14:41:30');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (18,5,'PIPA PVC','Batang',0.00,'2026-04-21 14:41:44','2026-04-21 14:41:44');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (19,5,'PASIR PASANG','M3',0.00,'2026-04-21 14:42:14','2026-04-21 14:42:14');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (20,5,'KAYU KASO','Batang',0.00,'2026-04-21 14:42:29','2026-04-21 14:42:29');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (21,5,'DOLKEN','Batang',0.00,'2026-04-21 14:42:45','2026-04-21 14:42:45');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (22,5,'KAWAT BETON','Kg',0.00,'2026-04-21 14:43:04','2026-04-21 14:43:04');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (23,5,'KAWAT BERONJONG','Buah',0.00,'2026-04-21 14:43:26','2026-04-21 14:43:26');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (24,5,'BATU PECAH','M3',0.00,'2026-04-21 14:43:43','2026-04-21 14:43:43');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (25,5,'BATU BELAH','M3',0.00,'2026-04-21 14:44:00','2026-04-21 14:44:00');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (26,5,'PLAT BAJA','Lembar',0.00,'2026-04-21 14:44:24','2026-04-21 14:44:24');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (27,5,'TAMBAL BAN','Set',0.00,'2026-04-21 14:44:46','2026-04-21 14:44:46');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (28,5,'KARET TAMBAL BAN','Lembar',0.00,'2026-04-21 14:45:11','2026-04-21 14:45:11');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (29,5,'ARIT','Buah',0.00,'2026-04-21 14:45:33','2026-04-21 14:45:33');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (30,5,'BLENCONG','Buah',0.00,'2026-04-21 14:45:49','2026-04-21 14:45:49');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (31,5,'CANGKRANG','Buah',0.00,'2026-04-21 14:46:04','2026-04-21 14:46:04');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (32,5,'PACUL','Buah',0.00,'2026-04-21 14:46:19','2026-04-21 14:46:19');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (33,5,'EMBER PLASTIK','Buah',0.00,'2026-04-21 14:46:32','2026-04-21 14:46:32');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (34,5,'GERGAJI BESI','Buah',0.00,'2026-04-21 14:46:47','2026-04-21 14:46:47');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (35,5,'GERGAJI KAYU','Buah',0.00,'2026-04-21 14:47:02','2026-04-21 14:47:02');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (36,5,'GOLOK','Buah',0.00,'2026-04-21 14:47:14','2026-04-21 14:47:14');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (37,5,'LINGGIS','Buah',0.00,'2026-04-21 14:47:31','2026-04-21 14:47:31');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (38,5,'METERAN','Buah',0.00,'2026-04-21 14:47:49','2026-04-21 14:47:49');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (39,5,'PAHAT BETON','Buah',0.00,'2026-04-21 14:48:03','2026-04-21 14:48:03');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (40,5,'PALU BODEM','Buah',0.00,'2026-04-21 14:48:17','2026-04-21 14:48:17');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (41,5,'PALU BESI','Buah',0.00,'2026-04-21 14:48:34','2026-04-21 14:48:34');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (42,5,'SEKOP','Buah',0.00,'2026-04-21 14:48:46','2026-04-21 14:48:46');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (43,5,'SENDOK SEMEN','Buah',0.00,'2026-04-21 14:48:58','2026-04-21 14:48:58');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (44,5,'SELANG AIR','Buah',0.00,'2026-04-21 14:49:11','2026-04-21 14:49:11');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (45,5,'GEROBAK SORONG','Set',0.00,'2026-04-21 14:49:26','2026-04-21 14:49:26');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (46,5,'MATA GERINDA Spec 4\"','Buah',0.00,'2026-04-21 14:50:14','2026-04-21 14:50:14');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (47,5,'MATA GERINDA SIKAT','Buah',0.00,'2026-04-21 14:50:51','2026-04-21 14:50:51');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (48,5,'MATA BOR BETON','Buah',0.00,'2026-04-21 14:51:07','2026-04-21 14:51:07');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (49,5,'BENANG','Rol',0.00,'2026-04-21 14:51:18','2026-04-21 14:51:18');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (50,5,'SIKAT KAWAT','Buah',0.00,'2026-04-21 14:51:30','2026-04-21 14:51:30');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (51,5,'JERIGEN','Buah',0.00,'2026-04-21 14:51:43','2026-04-21 14:51:43');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (52,5,'KABEL NYM ','Meter',0.00,'2026-04-21 14:51:57','2026-04-21 14:51:57');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (53,5,'KAWAT LAS','Batang',0.00,'2026-04-21 14:52:09','2026-04-21 14:52:09');
insert  into `materials`(`id`,`category_id`,`name`,`unit`,`current_volume`,`created_at`,`updated_at`) values (54,5,'TENDA BIRU','Set',0.00,'2026-04-21 14:52:24','2026-04-21 16:03:57');

/*Table structure for table `migrations` */

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `migrations` */

insert  into `migrations`(`id`,`migration`,`batch`) values (1,'2014_10_12_000000_create_users_table',1);
insert  into `migrations`(`id`,`migration`,`batch`) values (2,'2014_10_12_100000_create_password_reset_tokens_table',1);
insert  into `migrations`(`id`,`migration`,`batch`) values (3,'2019_08_19_000000_create_failed_jobs_table',1);
insert  into `migrations`(`id`,`migration`,`batch`) values (4,'2019_12_14_000001_create_personal_access_tokens_table',1);
insert  into `migrations`(`id`,`migration`,`batch`) values (5,'2026_04_21_124710_create_categories_table',1);
insert  into `migrations`(`id`,`migration`,`batch`) values (6,'2026_04_21_124710_create_materials_table',1);
insert  into `migrations`(`id`,`migration`,`batch`) values (7,'2026_04_21_124711_create_delivery_orders_table',1);
insert  into `migrations`(`id`,`migration`,`batch`) values (8,'2026_04_21_124711_create_inventory_transactions_table',1);
insert  into `migrations`(`id`,`migration`,`batch`) values (9,'2026_04_21_125058_create_permission_tables',1);
insert  into `migrations`(`id`,`migration`,`batch`) values (10,'2026_04_21_134855_add_balance_after_to_inventory_transactions_table',2);
insert  into `migrations`(`id`,`migration`,`batch`) values (11,'2026_04_21_140454_simplify_inventory_structure',3);
insert  into `migrations`(`id`,`migration`,`batch`) values (12,'2026_04_21_151420_add_details_to_inventory_transactions_table',4);
insert  into `migrations`(`id`,`migration`,`batch`) values (13,'2026_04_21_153340_create_delivery_order_materials_table',5);

/*Table structure for table `model_has_permissions` */

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `model_has_permissions` */

/*Table structure for table `model_has_roles` */

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `model_has_roles` */

insert  into `model_has_roles`(`role_id`,`model_type`,`model_id`) values (1,'App\\Models\\User',1);
insert  into `model_has_roles`(`role_id`,`model_type`,`model_id`) values (2,'App\\Models\\User',2);
insert  into `model_has_roles`(`role_id`,`model_type`,`model_id`) values (2,'App\\Models\\User',3);

/*Table structure for table `password_reset_tokens` */

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `password_reset_tokens` */

/*Table structure for table `permissions` */

CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `permissions` */

/*Table structure for table `personal_access_tokens` */

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
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

/*Data for the table `personal_access_tokens` */

/*Table structure for table `role_has_permissions` */

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `role_has_permissions` */

/*Table structure for table `roles` */

CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `roles` */

insert  into `roles`(`id`,`name`,`guard_name`,`created_at`,`updated_at`) values (1,'superadmin','web','2026-04-21 13:20:33','2026-04-21 13:20:33');
insert  into `roles`(`id`,`name`,`guard_name`,`created_at`,`updated_at`) values (2,'gudang','web','2026-04-21 13:20:33','2026-04-21 13:20:33');

/*Table structure for table `users` */

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`name`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`) values (1,'Super Administrator','admin@gmail.com',NULL,'$2y$12$3Y4Q66aeL7WmjW6UUO.4O.ErS3KtgBGmYljA40Vgs6BGO4IvJvWRq',NULL,'2026-04-21 13:20:34','2026-04-21 13:20:34');
insert  into `users`(`id`,`name`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`) values (2,'Petugas Gudang','gudang@gmail.com',NULL,'$2y$12$1JnGXAcQK2eg3M7aEgkVJOXYDfZmaHwfS1eCJ7exZSU.Itib25PRO',NULL,'2026-04-21 13:20:34','2026-04-21 13:20:34');
insert  into `users`(`id`,`name`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`) values (3,'Tatang','Tatang@gmail.com',NULL,'$2y$12$3bDSoUG8YmqnuxO1IYSQfuFDgyxZgPfozILndeutvNJiDBrqemZRa',NULL,'2026-04-21 14:16:54','2026-04-21 14:16:54');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
