<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2016 Observium Limited
 *
 *
 * CISCO-LWAPP-AP-MIB
 *
 * Discovery SysObjectID has to start either with 1.3.6.1.4.1.14179. or 1.3.6.1.4.1.9. and SysDescription contains the string "Cisco Controller".
 *
 * Controller ciscoLwappAp 1.3.6.1.4.1.9.9.513
 * Controller cLApEthernetIfEntry 1.3.6.1.4.1.9.9.513.1.2.2.1
 *
 * Thin AP ports      Table                           1.3.6.1.4.1.9.9.513.1.2.2         enterprises.cisco.ciscoMgmt.ciscoLwappApMIB.ciscoLwappApMIBObjects.ciscoLwappApIf.cLApEthernetIfTable.cLApEthernetIfEntry
 *                    cLApSysMacAddress               1.3.6.1.4.1.9.9.513.1.1.1.1.1     This object represents the radio MAC address common to the dot11 interfaces of the AP and uniquely identifies an entry in this table.
 * port_label_base    cLApName + cLApEthernetIfName   1.3.6.1.4.1.9.9.513.1.1.1.1.5     This object represents the administrative name assigned to the AP by the user.
 * port_label_num     cLApEthernetIfSlotId            1.3.6.1.4.1.9.9.513.1.2.2.1.1     This object represents the slot ID of an Ethernet interface on an AP. The slot ID for a particular Ethernet interface as represented by this object ranges from 0 to cLApMaxNumberOfEthernetSlots - 1.
 *                    cLApEthernetIfName              1.3.6.1.4.1.9.9.513.1.2.2.1.2     This object represents the name of the Ethernet interface. SnmpAdminString ( SIZE ( 0 .. 32))
 * ifPhysAddress      cLApEthernetIfMacAddress        1.3.6.1.4.1.9.9.513.1.2.2.1.3     This object represents MAC address of the Ethernet interface in the slot represented by cLApEthernetIfSlotId.
 * ifAdminStatus      cLApEthernetIfAdminStatus       1.3.6.1.4.1.9.9.513.1.2.2.1.4     This object represents the admin state of the physical Ethernet interface on the AP. INTEGER { up ( 1), down ( 2) }
 * ifOperStatus       cLApEthernetIfOperStatus        1.3.6.1.4.1.9.9.513.1.2.2.1.5     This object represents the operational state of the physical Ethernet interface on the AP. INTEGER { up ( 1), down ( 2) }
 * ifInUcastPkts      cLApEthernetIfRxUcastPkts       1.3.6.1.4.1.9.9.513.1.2.2.1.6     This object represents total number of unicast packets received on the interface. Counter32
 * ifInNUcastPkts     cLApEthernetIfRxNUcastPkts      1.3.6.1.4.1.9.9.513.1.2.2.1.7     This object represents total number of non-unicast or multicast packets received on the interface. Counter32
 * ifOutUcastPkts     cLApEthernetIfTxUcastPkts       1.3.6.1.4.1.9.9.513.1.2.2.1.8     This object represents total number of unicast packets transmitted on the interface. Counter32
 * ifOutNUcastPkts    cLApEthernetIfTxNUcastPkts      1.3.6.1.4.1.9.9.513.1.2.2.1.9     This object represents total number of non-unicast or multicast packets transmitted on the interface Counter32
 * ifDuplex           cLApEthernetIfDuplex            1.3.6.1.4.1.9.9.513.1.2.2.1.10    This object represents interface's duplex mode. INTEGER { unknown ( 1), halfduplex ( 2), fullduplex ( 3), auto ( 4) }
 * ifSpeed            cLApEthernetIfLinkSpeed         1.3.6.1.4.1.9.9.513.1.2.2.1.11    Speed of the interface in units of 1,000,000 bits per second. Gauge32
 * ifType ??          cLApEthernetIfPOEPower          1.3.6.1.4.1.9.9.513.1.2.2.1.12    This object represents whether this interface supports Power Over Ethernet (POE) none - POE is not supported drawn - This interface supports POE, and power is being drawn notdrawn - POE power is not drawn INTEGER { none ( 1), drawn ( 2), notdrawn ( 3) }
 * ifInOctets         cLApEthernetIfRxTotalBytes      1.3.6.1.4.1.9.9.513.1.2.2.1.13    This object represents total number of bytes in the error-free packets received on the interface. Counter32
 * ifOutOctets        cLApEthernetIfTxTotalBytes      1.3.6.1.4.1.9.9.513.1.2.2.1.14    This object represents total number of bytes in the error-free packets transmitted on the interface. Counter32
 *                    cLApEthernetIfInputCrc          1.3.6.1.4.1.9.9.513.1.2.2.1.15    This object represents total number of CRC error in packets received on the interface. Counter32
 *                    cLApEthernetIfInputAborts       1.3.6.1.4.1.9.9.513.1.2.2.1.16    This object represents total number of packet aborted while receiving on the interface. Counter32
 * ifInErrors         cLApEthernetIfInputErrors       1.3.6.1.4.1.9.9.513.1.2.2.1.17    This object represents sum of all errors in the packets while receiving on the interface. Counter32
 *                    cLApEthernetIfInputFrames       1.3.6.1.4.1.9.9.513.1.2.2.1.18    This object represents total number of packet received incorrectly having a CRC error and a noninteger number of octets on the interface. Counter32
 *                    cLApEthernetIfInputOverrun      1.3.6.1.4.1.9.9.513.1.2.2.1.19    This object represents the number of times the receiver hardware was incapable of handing received data to a hardware buffer because the input rate exceeded the receiver's capability to handle the data. Counter32
 * ifInDiscards       cLApEthernetIfInputDrops        1.3.6.1.4.1.9.9.513.1.2.2.1.20    This object represents total number of packets dropped while receiving on the interface because the queue was full. Counter32
 *                    cLApEthernetIfInputResource     1.3.6.1.4.1.9.9.513.1.2.2.1.21    This object represents total number of resource errors in packets received on the interface. Counter32
 *                    cLApEthernetIfUnknownProtocol   1.3.6.1.4.1.9.9.513.1.2.2.1.22    This object represents total number of packet discarded on the interface due to unknown protocol.
 *                    cLApEthernetIfRunts             1.3.6.1.4.1.9.9.513.1.2.2.1.23    This object represents number of packets that are discarded because they are smaller than the medium's minimum packet size. Counter32
 *                    cLApEthernetIfGiants            1.3.6.1.4.1.9.9.513.1.2.2.1.24    This object represents number of packets that are discarded because they exceed the medium's maximum packet size.  Counter32
 *                    cLApEthernetIfThrottle          1.3.6.1.4.1.9.9.513.1.2.2.1.25    This object represents total number of times the interface advised a sending NIC that it was overwhelmed by packets being sent and to slow the pace of delivery. Counter32
 *                    cLApEthernetIfResets            1.3.6.1.4.1.9.9.513.1.2.2.1.26    This object represents number of times that an interface has been completely reset. Counter32
 *                    cLApEthernetIfOutputCollision   1.3.6.1.4.1.9.9.513.1.2.2.1.27    This object represents total number of packet retransmitted due to an Ethernet collision. Counter32
 *                    cLApEthernetIfOutputNoBuffer    1.3.6.1.4.1.9.9.513.1.2.2.1.28    This object represents total number of packets discarded because there was no buffer space.  Counter32
 *                    cLApEthernetIfOutputResource    1.3.6.1.4.1.9.9.513.1.2.2.1.29    This object represents total number of resource errors in packets transmitted on the interface. Counter32
 *                    cLApEthernetIfOutputUnderrun    1.3.6.1.4.1.9.9.513.1.2.2.1.30    This object represents the number of times the transmitter has been running faster than the router can handle. Counter32
 * ifOutErrors        cLApEthernetIfOutputErrors      1.3.6.1.4.1.9.9.513.1.2.2.1.31    This object represents sum of all errors that prevented the final transmission of packets out of the interface.  Counter32
 * ifOutDiscards      cLApEthernetIfOutputTotalDrops  1.3.6.1.4.1.9.9.513.1.2.2.1.32    This object represents total number of packets dropped while transmitting from the interface because the queue was full. Counter32
 *                    cLApEthernetIfCdpEnabled        1.3.6.1.4.1.9.9.513.1.2.2.1.33    This object indicates the status of Cisco Discovery Protocol(CDP) in this interface represented by cLApEthernetIfSlotId of the AP represented by cLApSysMacAddress. A value of 'true' indicates that CDP is enabled in this interface. A value of 'false' indicates that CDP is disabled in this interface.
 *
 */

 echo('CISCO-LWAPP-AP-MIB' . PHP_EOL);

 //Thin AP Get Interfaces
 print_cli_data("Collecting", "CISCO-LWAPP-AP-MIB Ports", 3);



// EOF
