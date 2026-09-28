-- ParkSmart base schema: reference/manual install into an EMPTY database.
-- Preferred installation is php artisan migrate --seed. Do not mix methods.
-- Generated from the original table migrations, without data.

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('driver','staff','admin') NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `assigned_lot` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`),
  KEY `users_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `parking_lots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` varchar(200) NOT NULL,
  `total_spaces` int(11) NOT NULL DEFAULT 0,
  `available_spaces` int(11) NOT NULL DEFAULT 0,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 5.00,
  `type` varchar(50) NOT NULL DEFAULT 'Standard',
  `features` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parking_lots_name_index` (`name`),
  KEY `parking_lots_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `parking_spaces` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parking_lot_id` bigint(20) unsigned NOT NULL,
  `space_number` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Available',
  `type` varchar(50) NOT NULL DEFAULT 'Standard',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parking_spaces_parking_lot_id_space_number_unique` (`parking_lot_id`,`space_number`),
  KEY `parking_spaces_status_index` (`status`),
  KEY `parking_spaces_space_number_index` (`space_number`),
  CONSTRAINT `parking_spaces_parking_lot_id_foreign` FOREIGN KEY (`parking_lot_id`) REFERENCES `parking_lots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vehicles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `make` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_plate_number_unique` (`plate_number`),
  KEY `vehicles_plate_number_index` (`plate_number`),
  KEY `vehicles_user_id_index` (`user_id`),
  CONSTRAINT `vehicles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `reservations` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `vehicle_id` bigint(20) unsigned NOT NULL,
    `space_id` bigint(20) unsigned NOT NULL,
    `reservation_date` date NOT NULL,
    `start_time` time NOT NULL,
    `end_time` time NOT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'Pending',
    `payment_status` varchar(20) NOT NULL DEFAULT 'Pending',
    `total_amount` decimal(10,2) DEFAULT NULL,
    `reservation_time` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `reservations_vehicle_id_foreign` (`vehicle_id`),
    KEY `reservations_space_id_foreign` (`space_id`),
    KEY `reservations_user_id_index` (`user_id`),
    KEY `reservations_status_index` (`status`),
    KEY `reservations_payment_status_index` (`payment_status`),
    KEY `reservations_reservation_date_index` (`reservation_date`),
    CONSTRAINT `reservations_space_id_foreign`
        FOREIGN KEY (`space_id`)
        REFERENCES `parking_spaces` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `reservations_user_id_foreign`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `reservations_vehicle_id_foreign`
        FOREIGN KEY (`vehicle_id`)
        REFERENCES `vehicles` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `parking_sessions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `reservation_id` bigint(20) unsigned DEFAULT NULL,
    `vehicle_id` bigint(20) unsigned NOT NULL,
    `space_id` bigint(20) unsigned NOT NULL,
    `entry_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `exit_time` timestamp NULL DEFAULT NULL,
    `duration_minutes` int(11) DEFAULT NULL,
    `hourly_rate` decimal(10,2) NOT NULL DEFAULT 5.00,
    `total_cost` decimal(10,2) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `parking_sessions_reservation_id_foreign` (`reservation_id`),
    KEY `parking_sessions_space_id_foreign` (`space_id`),
    KEY `parking_sessions_entry_time_index` (`entry_time`),
    KEY `parking_sessions_exit_time_index` (`exit_time`),
    KEY `parking_sessions_vehicle_id_index` (`vehicle_id`),
    CONSTRAINT `parking_sessions_reservation_id_foreign`
        FOREIGN KEY (`reservation_id`)
        REFERENCES `reservations` (`id`)
        ON DELETE SET NULL,
    CONSTRAINT `parking_sessions_space_id_foreign`
        FOREIGN KEY (`space_id`)
        REFERENCES `parking_spaces` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `parking_sessions_vehicle_id_foreign`
        FOREIGN KEY (`vehicle_id`)
        REFERENCES `vehicles` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
------------------------------------------------------------------

CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reservation_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `method` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Completed',
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_reservation_id_foreign` (`reservation_id`),
  KEY `payments_session_id_foreign` (`session_id`),
  KEY `payments_payment_date_index` (`payment_date`),
  KEY `payments_status_index` (`status`),
  KEY `payments_transaction_id_index` (`transaction_id`),
  CONSTRAINT `payments_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `parking_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `finds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned DEFAULT NULL,
  `reservation_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `issue_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `finds_session_id_foreign` (`session_id`),
  KEY `finds_reservation_id_foreign` (`reservation_id`),
  KEY `finds_payment_id_foreign` (`payment_id`),
  KEY `finds_status_index` (`status`),
  KEY `finds_issue_date_index` (`issue_date`),
  CONSTRAINT `finds_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finds_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finds_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `parking_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedbacks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rating` int(11) NOT NULL,
  `date` date NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `lot_id` bigint(20) unsigned NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedbacks_user_id_foreign` (`user_id`),
  KEY `feedbacks_lot_id_foreign` (`lot_id`),
  KEY `feedbacks_rating_index` (`rating`),
  KEY `feedbacks_date_index` (`date`),
  CONSTRAINT `feedbacks_lot_id_foreign` FOREIGN KEY (`lot_id`) REFERENCES `parking_lots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedbacks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `employees` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `shift` varchar(50) NOT NULL,
    `lot_id` bigint(20) unsigned NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `employees_lot_id_foreign` (`lot_id`),
    KEY `employees_shift_index` (`shift`),
    CONSTRAINT `employees_lot_id_foreign`
        FOREIGN KEY (`lot_id`)
        REFERENCES `parking_lots` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

