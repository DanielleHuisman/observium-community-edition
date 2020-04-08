ALTER TABLE `notifications_queue` ADD `notification_locked` BOOLEAN NOT NULL DEFAULT FALSE AFTER `notification_entry`;
