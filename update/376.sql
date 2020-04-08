ALTER TABLE `processors` DROP `processor_ignore`;
ALTER TABLE `processors` ADD `processor_ignore` BOOLEAN NOT NULL DEFAULT FALSE AFTER `processor_polled`;
