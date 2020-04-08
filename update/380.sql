ALTER TABLE `ipv6_addresses` DROP INDEX `ipv6_cache`, ADD INDEX `ipv6_cache` (`device_id`, `ipv6_address`, `ipv6_compressed`);
