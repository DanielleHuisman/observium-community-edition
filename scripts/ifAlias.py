#!/usr/bin/python -u
# -------------------------------------------------------------------------
# Observium
#
#   This file is part of Observium.
#
# @package    observium
# @subpackage scripts
# @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
# -------------------------------------------------------------------------

# Add to snmpd.conf something like:
# pass_persist .1.3.6.1.2.1.31.1.1.1.18 /usr/local/bin/ifAlias.py

import os
import sys
import time
import subprocess
import re

try:
    import snmp_passpersist as snmp
except ImportError:
    print("ERROR: missing python module: snmp_passpersist")
    print("Install: sudo pip install snmp_passpersist")
    sys.exit(2)

# General stuff
UPDATE = 120  # refresh every 120 sec
MAX_RETRY = 10  # Number of successive retry in case of error
OID_BASE = ".1.3.6.1.2.1.31.1.1.1.18"  # IF-MIB::ifAlias

# Globals vars
pp = None
cache = {'frr': True}  # exist FRR or not


def get_links():
    """Get dict of system interfaces"""
    links = {}
    out = subprocess.run(['ip', 'link'], stdout=subprocess.PIPE)
    for line in out.stdout.decode('utf-8').splitlines():
        match = re.match(r'^(\d+):\s+(\w\S*?): ', line)
        if match:
            links[int(match.group(1))] = match.group(2)
    # print(links)
    return links


def ifalias_sys(ifname):
    """Get ifalias from sys alias"""
    sys_file = '/sys/class/net/' + ifname + '/ifalias'
    if os.path.isfile(sys_file):
        sys_net = open(sys_file, "r")
        ifalias = sys_net.read()
        sys_net.close()

        return ifalias

    return ''


def ifalias_frr(ifname):
    """Get ifalias from FRR config files or vtysh"""
    global cache

    if not cache['frr']:
        return ''

    if os.path.isfile('/bin/vtysh') and os.access('/bin/vtysh', os.X_OK):
        # prefer vtysh
        vty_pattern = r'^%s\s+\w+\s+\w+\s*(\S.*)?$' % re.escape(ifname)
        out = subprocess.run(['vtysh', '-c', 'show interface description'],
                             stdout=subprocess.PIPE, stderr=subprocess.DEVNULL)
        for line in out.stdout.decode('utf-8').splitlines():
            match = re.match(vty_pattern, line, re.IGNORECASE)
            if match:
                return str(match.group(1))
    elif os.path.isfile('/etc/frr/frr.conf'):
        out = subprocess.run(['awk', '/^interface ' + ifname + '/,/^exit/', '/etc/frr/frr.conf'],
                             stdout=subprocess.PIPE, stderr=subprocess.DEVNULL)
        for line in out.stdout.decode('utf-8').splitlines():
            match = re.match(r'^ description +(\S.*)?', line)
            if match:
                return str(match.group(1))

    cache['frr'] = False  # set not exist FRR
    return ''


def ifalias_conf(ifname):
    """Get ifalias from interface config files"""
    global cache

    if os.path.isfile('/etc/network/interfaces.d/' + ifname):
        cfg = '/etc/network/interfaces.d/' + ifname
    elif os.path.isfile('/etc/network/interfaces'):
        cfg = '/etc/network/interfaces'
    elif os.path.isfile('/etc/sysconfig/network-scripts/ifcfg-' + ifname):
        cfg = '/etc/sysconfig/network-scripts/ifcfg-' + ifname
    elif os.path.isfile('/etc/conf.d/net-conf-' + ifname):
        cfg = '/etc/conf.d/net-conf-' + ifname
    elif os.path.isfile('/etc/conf.d/net'):
        cfg = '/etc/conf.d/net'
    else:
        return ''

    conf_pattern = r'^\s*\#\s+%s:\s+(.+)' % re.escape(ifname)
    conf = open(cfg, "r")
    for line in conf.readlines():
        match = re.match(conf_pattern, line, re.IGNORECASE)
        if match:
            ifalias = match.group(1)
            conf.close()
            return ifalias

    conf.close()
    return ''


def update_ifalias():
    """Update snmp ifAlias"""
    global pp

    for ifindex, ifname in get_links().items():
        ifalias = ifalias_frr(ifname)
        if not ifalias:
            ifalias = ifalias_conf(ifname)
            if not ifalias:
                ifalias = ifalias_sys(ifname)

        # print(ifindex, ifname, ifalias)
        pp.add_str(str(ifindex), ifalias)


# update_ifalias()
# sys.exit(0)


def main():
    """Feed the IF-MIB::ifAlias Oid and start listening for snmp passpersist"""
    global pp

    retry_timestamp = int(time.time())
    retry_counter = MAX_RETRY
    while retry_counter > 0:
        try:
            # Load helpers
            pp = snmp.PassPersist(OID_BASE)

            pp.start(update_ifalias, UPDATE)  # Shouldn't return (except if updater thread has died)

        except KeyboardInterrupt:
            print("Exiting on user request.")
            sys.exit(0)

        time.sleep(10)

        # Errors frequency detection
        now = int(time.time())
        if (now - 3600) > retry_timestamp:  # If the previous error is older than 1H
            retry_counter = MAX_RETRY  # Reset the counter
        else:
            retry_counter -= 1  # Else countdown
        retry_timestamp = now

    sys.exit(1)


if __name__ == "__main__":
    main()

# EOF
