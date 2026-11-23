-- SMS Templates Table
CREATE TABLE IF NOT EXISTS `sms_templates` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `message` text NOT NULL,
    `description` text DEFAULT NULL,
    `branch_id` bigint(20) NOT NULL DEFAULT 0,
    `created_by` bigint(20) NOT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS Logs Table
CREATE TABLE IF NOT EXISTS `sms_logs` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `sender_id` varchar(50) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `message` text NOT NULL,
    `sent_by` bigint(20) NOT NULL,
    `event_type` varchar(50) DEFAULT NULL,
    `is_auto` tinyint(1) NOT NULL DEFAULT 0,
    `sent_at` datetime NOT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'pending',
    `api_response` text DEFAULT NULL,
    `branch_id` bigint(20) DEFAULT NULL,
    `message_id` varchar(100) DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

