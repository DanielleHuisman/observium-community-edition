DELETE FROM ports_stack WHERE NOT EXISTS ( SELECT 1 FROM ports WHERE ports_stack.device_id = ports.device_id AND (ports_stack.port_id_high = ports.ifIndex OR ports_stack.port_id_low = ports.ifIndex));
UPDATE ports_stack ps SET port_id_high = ( SELECT p.port_id FROM ports p WHERE p.device_id = ps.device_id AND p.ifIndex = ps.port_id_high);
UPDATE ports_stack ps SET port_id_low = ( SELECT p.port_id FROM ports p WHERE p.device_id = ps.device_id AND p.ifIndex = ps.port_id_low);
DELETE FROM `ports_stack` WHERE `port_id_high` = 0 OR `port_id_low` = 0
