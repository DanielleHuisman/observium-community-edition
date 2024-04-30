#!/usr/bin/env python
"""
 Observium

   This file is part of Observium.

 @package    observium
 @subpackage poller
 @copyright  (C) 2013-2014 Job Snijders, (C) 2014-2024 Observium Limited
"""

"""
 observium-wrapper A small tool which wraps around the Observium poller/discovery/billing
                   and tries to guide the relevant process with a more modern
                   approach with a Queue and workers

 Original:      Job Snijders <job.snijders@atrato.com>
 Rewritten:     Most code parts rewritten since 2014 by Observium developers

 Usage:         This program accepts process name as first command line argument,
                that should run simultaneously. For other options see help -h

                In /etc/cron.d/observium set this poller entry:
                */5 *  * * *   root /opt/observium/observium-wrapper poller >> /dev/null 2>&1

                In /etc/cron.d/observium set this discovery entries:
                34 */6 * * *   root /opt/observium/observium-wrapper discovery >> /dev/null 2>&1
                */5 *  * * *   root /opt/observium/observium-wrapper discovery --host new >> /dev/null 2>&1

                In /etc/cron.d/observium set this billing entries (if billing used):
                */5 *  * * *   root /opt/observium/observium-wrapper billing >> /dev/null 2>&1

 Ubuntu/Debian: sudo apt install python3-pymysql
 Other:         Read: https://pypi.org/project/pymysql/

"""

"""
    Import required modules
"""

try:
    # Import time module first for more accurate start time
    import time

    # start time
    s_time = time.time()
except ImportError:
    print("ERROR: missing python module: time")
    exit(2)

try:
    # Required modules
    import threading
    import sys
    import subprocess
    import os
    import json
    import stat
    # import traceback
except ImportError:
    print("ERROR: missing one or more of the following python modules:")
    print("threading, sys, subprocess, os, json, stat")
    sys.exit(2)


def new_except_hook(exctype, value, traceback):
    """
        Register global exepthook for ability stop execute wrapper by Ctrl+C
        See: https://stackoverflow.com/questions/6598053/python-global-exception-handling
    """

    if exctype == KeyboardInterrupt:
        print("\n\nExiting by CTRL+C.\n\n")
        sys.exit(2)
    else:
        sys.__excepthook__(exctype, value, traceback)


sys.excepthook = new_except_hook


class Colors:
    HEADER = '\033[95m'
    OKBLUE = '\033[94m'
    OKGREEN = '\033[92m'
    WARNING = '\033[93m'
    FAIL = '\033[91m'
    ENDC = '\033[0m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'


def logfile(log):
    """
        Definition for writing msg to log file
    """

    log = "[%s] %s(%s): %s/%s: %s\n" % (
        time.strftime("%Y/%m/%d %H:%M:%S %z"), scriptname, os.getpid(), config['install_dir'], scriptname, log)

    # https://jira.observium.org/browse/OBS-2631
    # if the log file is a "character special device file" or a "FIFO (named pipe)" we must use mode 'w'
    try:
        fstat = os.stat(log_path).st_mode
        if stat.S_ISCHR(fstat) or stat.S_ISFIFO(fstat):
            fmode = 'w'
        else:
            fmode = 'a'
    except OSError:
        print("\nLog file %s doesn't exist.\n" % log_path)
        return

    try:
        with open(log_path, fmode) as f:
            f.write(log)
    except IOError:
        print("\nLog file %s is not writeable.\n" % log_path)


# base script name
scriptname = os.path.basename(sys.argv[0])

# major/minor python version: (2,7), (3,2), etc
python_version = sys.version_info[:2]

if python_version < (3, 0):
    try:
        import Queue
    except ImportError:
        print("ERROR: missing python module: Queue")
        sys.exit(2)
    try:
        import MySQLdb

        db_version = "MySQLdb " + MySQLdb.__version__
    except ImportError:
        print("ERROR: missing python module: MySQLdb")
        print("On Ubuntu: apt-get install python-mysqldb")
        print("On RHEL/CentOS: yum install MySQL-python")
        print("On FreeBSD: cd /usr/ports/*/py-MySQLdb && make install clean")
        sys.exit(2)
else:
    try:
        import queue as Queue
    except ImportError:
        print("ERROR: missing python module: queue")
        sys.exit(2)

    # MySQLdb not available for python3
    # http://stackoverflow.com/questions/4960048/python-3-and-mysql
    try:
        import pymysql as MySQLdb

        MySQLdb.install_as_MySQLdb()
        import MySQLdb

        db_version = "pymysql " + MySQLdb.__version__
    except ImportError:
        print("ERROR: missing python module: pymysql")
        print(" On Ubuntu >= 16.04 and Debian >= 9.0:")
        print("            sudo apt install python3-pymysql")
        print(" On older Ubuntu/Debian:")
        print("            sudo apt-get install python3-setuptools")
        print("            sudo easy_install3 pip")
        print("            sudo pip3 install PyMySQL")
        # FIXME. I not know how install on RHEL and FreeBSD
        print(" On other OSes install pip for python3 and then run from root user:")
        print("            pip3 install PyMySQL")
        # print(" On RHEL/CentOS: yum install MySQL-python")
        # print(" On FreeBSD: cd /usr/ports/*/py-MySQLdb && make install clean")
        sys.exit(2)

"""
    Parse Arguments
    Attempt to use argparse module.  Probably want to use this moving forward
    especially as more features want to be added to this wrapper.
    and
    Take the amount of threads we want to run in parallel from the commandline
    if none are given or the argument was garbage, fall back to default of 8
"""
workers_arg = True  # need for check compatibility with old syntax
try:
    import argparse

    parser = argparse.ArgumentParser(description='Process Wrapper for Observium')
    parser.add_argument('process', nargs='?', default='poller',
                        help='Process name, one of \'poller\', \'discovery\', \'billing\'.')
    # parser.add_argument('process', nargs='?', default='poller', choices=['poller', 'discovery', 'billing'], help='Process name, one of \'poller\', \'discovery\', \'billing\'.')
    parser.add_argument('-w', '--workers', nargs='?', type=int, default=0,
                        help='Number of threads to spawn. Default: CPUs x 2')
    parser.add_argument('--timeout', nargs='?', type=int, default=0,
                        help='Hard timeout for run poller/discovery for each device in seconds (greater than 90s).')
    parser.add_argument('-p', '--poller_id', nargs='?', type=int, default=-1,
                        help='Specify poller_id for a partitioned poller. Usually not needed for partitioned operation.')
    parser.add_argument('-s', '--stats', action='store_true', help='Store total polling times to RRD.', default=False)
    parser.add_argument('-i', '--instances', nargs='?', type=int, default=-1, help='Process instances count.')
    parser.add_argument('-n', '--number', nargs='?', type=int, default=-1,
                        help='Instance id (number), must start from 0 and to be less than instances count.')
    parser.add_argument('-g', '--include-groups', nargs='*', type=int,
                        help='List of device group IDs to restrict polling/discovery these groups only. List is separated '
                             'by spaces (-g 3 4 5).')
    parser.add_argument('-e', '--exclude-groups', nargs='*', type=int,
                        help='List of device group IDs to exclude from polling/discovery.')
    parser.add_argument('-d', '--debug', action='store_true',
                        help='Enable debug output. WARNING, do not use this option unless you know what it does. This '
                             'generates a lot of very large files in TEMP dir.', default=False)
    parser.add_argument('-t', '--test', action='store_true',
                        help='Do not spawn processes, just test DB connection, config, etc.', default=False)
    parser.add_argument('--host', help='Process hostname wildcard.')

    # Parse passed arguments
    args = parser.parse_args()
    # print(args)

    # for compatibility with old passed argument with worker
    try:
        # poller-wrapper.py 16
        workers = int(args.process)
        workers_arg = False
        process = 'poller'
        print("WARNING! Using number of threads without command argument (-w or --workers) is deprecated! \n"
              "Please replace your poller wrapper command line with:")
        print("observium-wrapper poller -w %s" % workers)
    except ValueError:
        # observium-wrapper poller -w 16
        process = args.process
        workers = int(args.workers)

    poller_id = int(args.poller_id)
    instances_count = int(args.instances)
    instance_number = int(args.number)
    stats = args.stats
    debug = args.debug
    test = args.test
except ImportError:
    # FIXME, do not use this compatibility, just end execution here
    print("WARNING: missing the argparse python module:")
    print("On Ubuntu: apt-get install libpython%s.%s-stdlib" % python_version)
    print("On RHEL/CentOS: yum install python-argparse")
    print("On Debian: apt-get install python-argparse")
    print("Continuing with basic argument support.")
    poller_id = -1
    instances_count = 1
    instance_number = 0
    stats = False
    debug = False
    test = False
    try:
        # poller-wrapper.py 16
        workers = int(sys.argv[1])
        workers_arg = False
        process = 'poller'
    except ValueError:
        # observium-wrapper poller -w 16
        process = sys.argv[1]
        if not process:
            process = 'poller'
        workers = 0

"""
    Allowed process list, if not one of this exit with error
"""

process_list = ['poller', 'discovery', 'billing']
if process not in process_list:
    print("ERROR: Incorrect process name '%s' passed, should be one of: %s" % (process, process_list))
    sys.exit(2)

# entity name for log and messages
if process == 'billing':
    entity = 'bill'
else:
    entity = 'device'

# full process name, ie: 'observium-wrapper poller'
processname = scriptname + ' ' + process

"""
    Fetch configuration details from the config_to_json.php script
"""

ob_install_dir = os.path.dirname(os.path.realpath(__file__))
config_file = ob_install_dir + '/config.php'


def get_config_data():
    config_cmd = ['/usr/bin/env', 'php', '%s/config_to_json.php' % ob_install_dir]
    # limit requested options only required (skip huge definitions)
    config_options = ['db_user', 'db_pass', 'db_host', 'db_name', 'db_port', 'db_socket',
                      'db_ssl', 'db_ssl_verify', 'db_ssl_key', 'db_ssl_cert', 'db_ssl_ca', 'db_ssl_ca_path', 'db_ssl_ciphers',
                      'install_dir', 'rrd_dir', 'temp_dir', 'log_dir', 'mib_dir',
                      'rrdcached', 'rrdtool', 'rrd',
                      'poller-wrapper', 'poller_id', 'poller_name']
    config_cmd.append('-o')
    config_cmd.append(','.join(config_options))
    try:
        proc = subprocess.Popen(config_cmd, stdout=subprocess.PIPE, stdin=subprocess.PIPE)
    except:
        print("ERROR: Could not execute: %s" % config_cmd)
        sys.exit(2)
    return proc.communicate()[0].decode('utf-8')  # decode required in python3


try:
    with open(config_file) as f:
        pass
except IOError as e:
    print("ERROR: Oh dear... %s does not seem readable" % config_file)
    sys.exit(2)

try:
    config = json.loads(get_config_data())
except:
    print("ERROR: Could not load or parse observium configuration from config_to_json.php, are PATHs correct?")
    sys.exit(2)

if test:
    print(config)

# observium edition pro/community
edition = config['observium_edition']

db_username = config['db_user']
db_password = config['db_pass']
db_server = config['db_host']
db_dbname = config['db_name']
db_ssl = config['db_ssl']

try:
    db_port = int(config['db_port'])
except KeyError:
    db_port = 3306
try:
    db_socket = config['db_socket']
except KeyError:
    db_socket = False

poller_path = config['install_dir'] + '/poller.php'
discovery_path = config['install_dir'] + '/discovery.php'
alerter_path = config['install_dir'] + '/alerter.php'
billing_path = config['install_dir'] + '/poll-billing.php'

# rrdcached & remote rrd
try:
    rrdcached_address = config['rrdcached']
    remote_rrd = rrdcached_address.find('/') < 0  # unix:/file or /file
except KeyError:
    # rrdcached config not set, reset remote_rrd
    remote_rrd = False

try:
    temp_path = config['temp_dir']
except KeyError:
    temp_path = '/tmp'
try:
    rrd_path = config['rrd_dir']
except KeyError:
    rrd_path = config['install_dir'] + '/rrd'
try:
    log_path = config['log_dir'] + '/observium.log'
except KeyError:
    log_path = config['install_dir'] + '/logs/observium.log'

# Amount of threads
if process == 'poller':
    try:
        # use config option if set
        amount_of_workers = int(config['poller-wrapper']['threads'])
    except KeyError:
        amount_of_workers = 0
else:
    amount_of_workers = 0

if workers > 0 and (workers_arg or amount_of_workers < 1):
    # use amount threads passed as argument
    # always prefer cmd arg
    amount_of_workers = workers

if amount_of_workers < 1:
    if process == 'poller':
        max_workers = 256
    else:
        # set discovery workers limitation less than poller
        max_workers = 8

    try:
        # use threads count based on cpus count
        import multiprocessing

        cpu_count = multiprocessing.cpu_count()
        amount_of_workers = cpu_count * 2
        # Limit maximum amount of worker based on CPU count
        if amount_of_workers > max_workers:
            print("WARNING: Very high CPU core count, %s threads limited to %s (detected: %s). For more threads"
                  " please use configuration option $config['poller-wrapper']['threads']"
                  " or pass as argument -w [THREADS]" % (process, max_workers, amount_of_workers))
            amount_of_workers = max_workers

    except (ImportError, NotImplementedError):
        amount_of_workers = 8
        print("WARNING: used default thread count of %s. To change this use configuration options"
              " or pass as argument -w [THREADS]" % amount_of_workers)

if test:
    cpu_count = 'unknown'
    if 'cpu_count' not in locals():
        try:
            import multiprocessing

            cpu_count = multiprocessing.cpu_count()
        except (ImportError, NotImplementedError):
            cpu_count = 'unknown'

    print("Script: %s, Process: %s, Workers: %s, CPUs: %s, Instances: %s, InstanceID: %s" % (
        scriptname, process, amount_of_workers, cpu_count, instances_count, instance_number))
    print("Stats: %s, Remote RRD: %s, Debug: %s, Test: %s" % (stats, remote_rrd, debug, test))
    print("Versions:\n  Python - %s.%s.%s" % sys.version_info[:3])
    print("  DB - %s" % db_version)

if os.path.isfile(alerter_path):
    alerting = config['poller-wrapper']['alerter']
else:
    alerting = False

if not stats:
    try:
        stats = bool(config['poller-wrapper']['stats'])
    except KeyError:
        pass

# max running and max LA
try:
    max_running = int(config['poller-wrapper']['max_running'])
except:
    max_running = 3
if max_running < 1:
    max_running = 3

try:
    max_la = float(config['poller-wrapper']['max_la'])
except:
    max_la = 10
if max_la <= 0:
    max_la = 10

# poller/discovery timeouts per device
# Notes:
# subprocess.run() was added in 3.5
# subprocess.check_call() can migrate to subprocess.run(..., check=True)
# timeout param was added to subprocess.check_call() in 3.3
timeout = 0
if entity == 'device' and python_version >= (3, 3):
    # only for poller/discovery with python 3.3+

    if int(args.timeout) > 0:
        # timeout param passed from cmd line (can use any positive value)
        timeout = int(args.timeout)
    elif int(config['poller-wrapper'][process + '_timeout']) >= 90:
        # hard limit can't be less than 90s (prevent use random numbers as timeout)
        timeout = int(config['poller-wrapper'][process + '_timeout'])
if test:
    print("Timeout for kill process: %ss" % timeout)

# partitioned poller
poller_arg = False  # pass poller id to requested script
if poller_id < 0:
    # poller_id not passed from command line, use config or default
    try:
        poller_id = int(config['poller_id'])
    except:
        poller_id = 0
elif edition == 'community':
    poller_id = 0
else:
    # poller_arg = args.poller_id > 0
    poller_arg = True
    # poller_id = args.poller_id


"""
    Check mibs dir for stale .index files
"""

try:
    import glob

    # use this cleanup in discovery --host new (not in poller)
    if process == 'discovery':
        mib_indexes = glob.glob(config['mib_dir'] + '/.index')
        mib_indexes += glob.glob(config['mib_dir'] + '/*/.index')
        # mib_indexes += glob.glob('/var/lib/snmp/mib_indexes/*') # not permitted for wrapper process
        # print(mib_indexes)
        for mib_index in mib_indexes:
            # print(mib_index)
            try:
                os.remove(mib_index)
            except:
                # break loop because not permitted to remove files in mibs dir
                print("WARNING: .index files are found in mibs directories which can't be removed "
                      "(there aren't enough permissions)")
                print("         see: http://www.observium.org/docs/faq/"
                      "#all-my-hosts-seem-down-to-observium-snmp-doesnt-seem-to-work-anymore")
                logfile("WARNING: .index files are found in mibs directories which can't be removed "
                        "(there aren't enough permissions)")
                break

except:
    pass

# sys.exit(2)

real_duration = 0
per_device_duration = {}

devices_list = []

try:
    # set db connection params
    db_params = {'host': db_server,
                 'user': db_username,
                 'passwd': db_password,
                 'db': db_dbname,
                 'port': db_port}
    if bool(db_socket):
        db_params['unix_socket'] = db_socket
    if "pymysql" in db_version:
        # enable autocommit for pymysql lib
        db_params['autocommit'] = True
        # enable ssl options
        if db_ssl:
            db_params['ssl_ca'] = config['db_ssl_ca']
            db_params['ssl_verify_cert'] = config['db_ssl_verify']
            # FIXME. all options:
            #  ssl_ca, ssl_cert, ssl_disabled, ssl_key, ssl_key_password, ssl_verify_cert, ssl_verify_identity


    db = MySQLdb.connect(**db_params)
    if "MySQLdb" in db_version:
        # enable autocommit for MySQLdb lib
        db.autocommit(True)
    cursor = db.cursor()
except Exception as e:
    msg = str(e).strip("()")
    # if test:
    #     traceback.print_exc()
    print("ERROR: %s" % msg)
    logfile("ERROR: %s" % msg)
    sys.exit(2)

""" 
    This query specifically orders the results depending on the last_polled_timetaken variable
    Because this way, we put the devices likely to be slow, in the top of the queue
    thus greatening our chances of completing _all_ the work in exactly the time it takes to
    poll the slowest device! cool stuff he
    Additionally, if a hostname wildcard is passed, add it to the where clause.  This is
    important in cases where you have pollers distributed geographically and want to limit
    pollers to polling hosts matching their geographic naming scheme.
"""

stop_script = False  # trigger for stop execute script inside try
where_devices = "WHERE `disabled` != 1"  # Filter disabled devices by default

# Use include device groups
if args.include_groups is not None:
    # Fetch device_id from selected groups
    query = "SELECT `entity_id` FROM `group_table` WHERE `entity_type` = 'device' AND `entity_id` > 0 AND `group_id` IN (%s)"
    cursor.execute(query % ",".join(map(str, args.include_groups)))
    include_devices = []
    for row in cursor.fetchall():
        include_devices.append(row[0])
    # print(map(str, include_devices))
    # print(query % ",".join(map(str, args.include_groups)))
    where_devices += " AND `device_id` IN (%s)" % ",".join(map(str, include_devices))

# Use exclude device groups
if args.exclude_groups is not None:
    # Fetch device_id from selected groups
    query = "SELECT `entity_id` FROM `group_table` WHERE `entity_type` = 'device' AND `entity_id` > 0 AND `group_id` IN (%s)"
    cursor.execute(query % ",".join(map(str, args.exclude_groups)))
    exclude_devices = []
    for row in cursor.fetchall():
        exclude_devices.append(row[0])
    # print(map(str, exclude_devices))
    # print(query % ",".join(map(str, args.include_groups)))

    where_devices += " AND `device_id` NOT IN (%s)" % ",".join(map(str, exclude_devices))

# partitioned pollers
if poller_id > 0:
    poller_default = False  # this is partitioned poller

    print(Colors.OKBLUE + 'INFO: This is poller_id (' + str(poller_id) + ') using poller-restricted devices list' +
          Colors.ENDC)

    # Update remote poller timestamp at start of poller, for checks remote poller availability
    if process == 'poller':

        # poller table entry must be created at first run of remote poller
        cursor.execute(
            "SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`timestamp`) AS `lasttime` FROM `pollers` WHERE `poller_id` = %s",
            (poller_id,))
        row = cursor.fetchone()
        poller_db_last = int(row[0])  # poller entry exist, last updated seconds

        poller_query = 'UPDATE `pollers` SET `timestamp` = NOW() WHERE `poller_id` = %s' % (poller_id, )
        if test:
            print(poller_query)
            print(poller_db_last)
        elif poller_db_last >= 60:
            if poller_db_last >= 14400:
                # before update, check if poller was down for long time (force discovery)
                print("WARNING: Poller was unavailable for long time, need force discovery devices")
                cursor.execute("UPDATE `devices` SET `force_discovery` = 1 WHERE `poller_id` = %s",
                               (poller_id, ))

            cursor.execute(poller_query)

else:
    poller_default = True  # this is default poller
    print(Colors.OKBLUE + 'This is the default poller. Will only poll devices with no specified poller set.' +
          Colors.ENDC)
    # Set default value of 0 for process tables and the like
    poller_id = 0


# Always select devices by poller_id
where_devices += " AND `poller_id` = %s" % (poller_id, )

if test:
    print(where_devices)

if instances_count > 1 and instance_number >= 0 and (instance_number < instances_count):
    # Use distributed wrapper
    if process == 'billing':
        # billing
        query = """SELECT `bill_id` FROM (SELECT @rownum :=0) r,
                   (
                      SELECT @rownum := @rownum +1 AS rownum, bill_id
                      FROM `bills`
                      ORDER BY `bill_id` ASC
                  ) temp
                WHERE MOD(temp.rownum, %s) = %s""" % (instances_count, instance_number)
    else:
        # poller or discovery
        query = """SELECT `device_id` FROM (SELECT @rownum :=0) r,
                   (
                      SELECT @rownum := @rownum +1 AS rownum, device_id
                      FROM `devices`
                      %s
                      ORDER BY `device_id` ASC
                  ) temp
                WHERE MOD(temp.rownum, %s) = %s""" % (where_devices, instances_count, instance_number)

    if test:
        print(query)

    cursor.execute(query)

    # Increase maximum allowed running wrapper processes by instances count
    max_running *= instances_count
else:
    # set instances count and number for single process wrapper
    instances_count = 1
    instance_number = 0

    if process == 'billing':
        # Normal billing poll
        query = """SELECT `bill_id`
                   FROM   `bills`"""
        order = " ORDER BY `bill_id` ASC"
        query = query + order
        if test:
            print(query)
        cursor.execute(query)

    else:
        # Normal poller/discovery
        query = """SELECT `device_id`
                   FROM   `devices`
                   %s""" % (where_devices, )
        if process == 'discovery':
            order = " ORDER BY `last_discovered_timetaken` DESC"
        else:
            order = " ORDER BY `last_polled_timetaken` DESC"

        # skip down devices in discovery
        if process == 'discovery':
            query = query + " AND `status` = 1"

        try:
            # Query with hosts specified
            host_wildcard = args.host.replace('*', '%')

            # expand process name for do not calculate count inside main processes
            processname = processname + ' --host ' + host_wildcard

            if host_wildcard != 'new':
                wc_query = query + " AND `hostname` LIKE %s " + order
                cursor.execute(wc_query, (host_wildcard,))
                if test:
                    print(wc_query)
        except:
            # Query without hosts specified
            query = query + order
            if test:
                print(query)
            cursor.execute(query)


"""
   Common functions
"""

# Open dev null handle
if not test:
    FNULL = open(os.devnull, 'w')


def defined(var):
    return var in vars() or var in globals()


def external_php(path, host='', extra=None, stdout=None, tmout=timeout):
    command_args = ['/usr/bin/env', 'php', path]

    if debug:
        command_args.append('-d')
    else:
        # always quiet
        command_args.append('-q')

    if extra is not None:
        command_args.extend(extra)

    if host:
        command_args.append('-h')
        command_args.append(host)

    if test:
        # debug
        print(' '.join(command_args))
        return 0

    if stdout is None and defined('FNULL'):
        stdout = FNULL

    start = time.time()
    if tmout > 0:
        # append timeout param for python 3.3+ and when option set
        try:
            subprocess.check_call(map(str, command_args),
                                  stdout=stdout, stderr=subprocess.STDOUT, timeout=tmout)
        except subprocess.TimeoutExpired:
            print("WARNING: %s for host %s ran too long, stopped by timeout (%ss)." % (process, host, tmout))
            logfile("WARNING: %s for host %s ran too long, stopped by timeout (%ss)." % (process, host, tmout))
    else:
        subprocess.check_call(map(str, command_args), stdout=stdout, stderr=subprocess.STDOUT)

    return time.time() - start


"""
    Do initial operations before main poller/discovery
"""
if process == 'discovery' and instance_number == 0:
    if not defined('host_wildcard'):
        """
            Since ./discovery -h all additionally do db schema update,
            also do this here before wrap discovery processes.
            Check if this is first process instance and --host parameter not passed
        """
        print("INFO: starting discovery.php for update")
        # os.system("/usr/bin/env php %s %s >> /dev/null 2>&1" % (discovery_path, command_options))
        runtime = external_php(discovery_path, extra=['-u', '-a'], tmout=0)
        print("INFO: finished discovery.php after %.3fs for update" % (runtime, ))
    elif host_wildcard == 'new':
        # new devices discovery just pass ./discovery.php -h new, do not spawn processes
        print("INFO: starting %s for %s on poller id %s" % (process, host_wildcard, poller_id))
        # print("/usr/bin/env php %s %s >> /dev/null 2>&1" % (discovery_path, command_options))
        # os.system("/usr/bin/env php %s %s >> /dev/null 2>&1" % (discovery_path, command_options))
        if poller_arg:
            # append poller id arg when requested
            runtime = external_php(discovery_path, host_wildcard, extra=['-p', poller_id], tmout=0)
        else:
            runtime = external_php(discovery_path, host_wildcard, tmout=0)
        print("INFO: finished %s after %.3fs for %s on poller id %s" % (process, runtime, host_wildcard, poller_id))

        stop_script = True  # exit other discovery

if process == 'poller' and instance_number == 0 and poller_default and config['distributed']:
    print("INFO: starting poller.php for external pollers")
    runtime = external_php(poller_path, 'pollers', tmout=0)
    print("INFO: finished poller.php after %.3fs for external pollers" % (runtime,))


# stop script execute after try
if stop_script:
    sys.exit(0)

for row in cursor.fetchall():
    devices_list.append(int(row[0]))

if test:
    print(devices_list)
# sys.exit(2)

"""
    Get current wrapper process info and remove stale db entries
"""

pid = os.getpid()
ppid = os.getppid()
uid = os.getuid()
la = os.getloadavg()
try:
    command = subprocess.check_output('ps -ww -o args -p %s' % pid, shell=True, universal_newlines=True).splitlines()[1]
except:
    command = scriptname

"""
    Search if same poller wrapper processes running
    Protect from race condition
"""

ps_count = 0

p_query = """SELECT `process_id`, `process_pid`, `process_ppid`, `process_uid`, `process_command`, `process_start`
             FROM   `observium_processes`
             WHERE  `process_name` = %s AND `poller_id` = %s"""
try:
    cursor.execute(p_query, (processname, poller_id))
    for row in cursor.fetchall():
        # print(row)
        test_running = False
        test_id, test_pid, test_ppid, test_uid, test_command, test_start = row
        try:
            test_ps = subprocess.check_output('ps -ww -o ppid,uid,args -p %s' % test_pid, shell=True,
                                              universal_newlines=True).splitlines()[1]
            # print(test_ps)
            test_ps = test_ps.split(None, 2)
            # print(test_ps)
            # print("PPID: %s, %s" % (test_ppid, int(test_ps[0])))
            # print(" UID: %s, %s" % (test_uid, int(test_ps[1])))
            # print("name: %s, %s" % (processname, test_ps[2]))
            test_running = (test_ppid == int(test_ps[0])) and (test_uid == int(test_ps[1])) and (
                    scriptname in test_ps[2])
        except:
            # not exist pid
            pass

        # print("Test: %s" % (test_running))
        if not test_running:
            # process not exist, remove stale db entry
            try:
                cursor.execute("""DELETE FROM `observium_processes` WHERE `process_id` = %s""", (test_id,))
                # db.commit()
                # print("Removed stale DB entry %s" % test_id)
            except:
                pass
        else:
            ps_count += 1
            # print("Count: %s" % (ps_count))

except:
    try:
        # FIXME. Remove this compatibility, always use from db process counts!
        ps_list = subprocess.check_output('ps ax | grep %s | grep -v grep' % scriptname, shell=True,
                                          universal_newlines=True)
        # divide by 2 because cron starts 2 processes (/bin/sh and main process)
        ps_count = len(ps_list.splitlines()) / 2
        ps_count -= 1  # decrease current process
    except:
        # Skip searching, something wrong
        pass

# This prevents race and too high LA on server.
# Default is 4 processes and 10 load average.
# More than 4 already running poller-wrapper it's big trouble!
if ps_count > max_running and la[1] >= max_la:
    print("URGENT: %s not started because already running %s processes, load average (5min) %.2f" % (
        processname, ps_count, la[1]))
    logfile("URGENT: %s not started because already running %s processes, load average (5min) %.2f" % (
        processname, ps_count, la[1]))
    sys.exit(2)

# Increase count by current wrapper process
ps_count += 1

# write into db current process info
p_query = """INSERT INTO `observium_processes` (`process_pid`,`process_ppid`,`process_name`,`process_uid`,`process_command`,`process_start`,`device_id`,`poller_id`)
             VALUES (%s,%s,%s,%s,%s,%s,'0',%s)"""
try:
    cursor.execute(p_query, (pid, ppid, processname, uid, command, s_time, poller_id))
    process_id = db.insert_id()
    # db.commit()
except:
    process_id = None
    pass


if test:
    print("Already running %s processes: %s, load average (5min) %.2f" % (processname, ps_count, la[1]))
    # time.sleep(30) # delays for 30 seconds
    if process_id is not None:
        p_query = """DELETE FROM `observium_processes` WHERE `process_id` = %s"""
        cursor.execute(p_query, (process_id,))
        # db.commit()
    sys.exit(2)


def update_wrapper_times(rrd_file, count, runtime, workers):
    """
        Create/update poller stat times
    """

    if not remote_rrd:
        rrd_file = rrd_path + '/' + rrd_file
    # always create rrd (with no-overwrite) for remote rrdcached
    if remote_rrd or not os.path.isfile(rrd_file):
        # Create RRD
        rrd_dst = ':GAUGE:' + str(config['rrd']['step'] * 2) + ':0:U'
        cmd_create = config['rrdtool'] + ' create ' + rrd_file + ' DS:devices' + rrd_dst + ' DS:totaltime'
        cmd_create += rrd_dst +' DS:threads' + rrd_dst
        cmd_create += ' --step ' + str(config['rrd']['step']) + ' ' + ' '.join(config['rrd']['rra'].split())
        if remote_rrd:
            # --no-overwrite available since 1.4.3
            cmd_create += ' --no-overwrite --daemon ' + rrdcached_address + ' >/dev/null 2>&1'
        else:
            logfile(cmd_create)
        os.system(cmd_create)
        if debug:
            print("DEBUG: " + cmd_create)

    cmd_update = config['rrdtool'] + ' update ' + rrd_file + ' N:%s:%s:%s' % (count, runtime, workers)
    if remote_rrd:
        cmd_update += ' --daemon ' + rrdcached_address
    os.system(cmd_update)
    if debug:
        print("DEBUG: " + cmd_update)


def update_wrapper_count(rrd_file, count):
    """
        Create/update poller wrapper count
    """

    if not remote_rrd:
        rrd_file = rrd_path + '/' + rrd_file

    if remote_rrd or not os.path.isfile(rrd_file):
        # Create RRD
        rrd_dst = ':GAUGE:' + str(config['rrd']['step'] * 2) + ':0:U'
        cmd_create = config['rrdtool'] + ' create ' + rrd_file + ' DS:wrapper_count' + rrd_dst
        cmd_create += ' --step ' + str(config['rrd']['step']) + ' ' + ' '.join(config['rrd']['rra'].split())
        if remote_rrd:
            # --no-overwrite available since 1.4.3
            cmd_create += ' --no-overwrite --daemon ' + rrdcached_address + ' >/dev/null 2>&1'
        else:
            logfile(cmd_create)
        os.system(cmd_create)
        if debug:
            print("DEBUG: " + cmd_create)
    cmd_update = config['rrdtool'] + ' update ' + rrd_file + ' N:%s' % count
    if remote_rrd:
        cmd_update += ' --daemon ' + rrdcached_address
    os.system(cmd_update)
    if debug:
        print("DEBUG: " + cmd_update)


def printworker():
    """
        A seperate queue and a single worker for printing information to the screen prevents
        the good old joke:

            Some people, when confronted with a problem, think,
            "I know, I'll use threads," and then two they hav erpoblesms.
    """

    while True:
        worker_id, device_id, elapsed_time = print_queue.get()
        global real_duration
        global per_device_duration
        real_duration += elapsed_time
        per_device_duration[device_id] = elapsed_time

        if elapsed_time < 300:
            print("INFO: worker %s finished %s %s in %.2f seconds" % (worker_id, entity, device_id, elapsed_time))
        else:
            print("WARNING: worker %s finished %s %s in %.2f seconds" % (worker_id, entity, device_id, elapsed_time))
        print_queue.task_done()


def process_worker():
    """
        This class will fork off single instances of the poller.php process, record
        how long it takes, and push the resulting reports to the printer queue
    """

    command_paths = {
        'poller': poller_path,
        'discovery': discovery_path,
        'billing': billing_path
    }
    process_path = command_paths[process]

    command_out = FNULL  # set non-debug output to /dev/null

    while True:
        entity_id = process_queue.get()
        try:
            if debug:
                command_out = open(temp_path + '/observium_' + process + '_' + str(entity_id) + '.debug', 'a')
            # print(command_args) #debug
            if process == 'billing':
                # billing use -b [bill_id]
                pruntime = external_php(process_path, extra=['-b', entity_id], stdout=command_out, tmout=timeout)
            elif poller_arg:
                # add poller id arg
                pruntime = external_php(process_path, entity_id, extra=['-p', poller_id], stdout=command_out,
                                       tmout=timeout)
            else:
                # poller/discovery us -h [entity_id]
                pruntime = external_php(process_path, entity_id, stdout=command_out, tmout=timeout)

            if debug:
                command_out.close()

            # additional alerter process only after poller process
            if process == 'poller' and alerting:
                print("INFO: starting alerter.php for %s" % entity_id)
                # if debug:
                #     command_out = temp_path + '/observium_alerter_' + str(entity_id) + '.debug'
                # print(command_args) #debug
                aruntime = external_php(alerter_path, entity_id)
                print("INFO: finished alerter.php after %.3fs for %s" % (aruntime, entity_id))

            print_queue.put([threading.current_thread().name, entity_id, pruntime])
        except (KeyboardInterrupt, SystemExit):
            raise
        except:
            pass
        process_queue.task_done()


process_queue = Queue.Queue()
print_queue = Queue.Queue()

print("INFO: starting the %s at %s with %s threads" % (process, time.strftime("%Y/%m/%d %H:%M:%S"), amount_of_workers))
if debug:
    print("WARNING: DEBUG enabled, each %s %s store output to %s/observium_%s_id.debug (where id is %s_id)" % (
        entity, process, temp_path, process, entity))

for dev_id in devices_list:
    process_queue.put(dev_id)

for i in range(amount_of_workers):
    t = threading.Thread(target=process_worker)
    t.daemon = True
    t.start()

p = threading.Thread(target=printworker)
p.daemon = True
p.start()

try:
    process_queue.join()
    print_queue.join()
except (KeyboardInterrupt, SystemExit):
    raise

total_time = time.time() - s_time

la = os.getloadavg()  # get new load average after polling
msg = "processed %s %ss in %.3f seconds with %s threads, load average (5min) %.2f" % \
      (len(devices_list), entity, total_time, amount_of_workers, la[1])
print("INFO: %s %s\n" % (processname, msg))
logfile(msg)

show_stopper = False
exit_code = 0
if process != 'discovery' and total_time > 300:
    recommend = int(total_time / 300.0 * amount_of_workers + 1)
    print("WARNING: the process took more than 5 minutes to finish, you need faster hardware or more threads")
    print("INFO: in sequential style processing the elapsed time would have been: %s seconds" % real_duration)
    for device in per_device_duration:
        if per_device_duration[device] > 300:
            print("WARNING: device %s is taking too long: %s seconds" % (device, per_device_duration[device]))
            show_stopper = True
    if show_stopper:
        print("ERROR: Some devices are taking more than 300 seconds, the script cannot recommend you what to do.")
    else:
        print("WARNING: Consider setting a minimum of %d threads. (This does not constitute professional advice!)" %
              (recommend,))
    exit_code = 1

"""
    Get and update poller wrapper stats
"""
if process == 'poller':
    # poller stats (always filled)
    poller_stats = {
        'workers_count': amount_of_workers,
        'instances_count': instances_count,
        'wrapper_processes': ps_count
    }

    # instances specific stats
    if stats:
        import socket

        localhost = socket.getfqdn()
        if localhost.find('.') == 0:
            localhost_t = subprocess.check_output(["/bin/hostname", "-f"], universal_newlines=True).strip()
            if localhost_t.find('.') > 0:
                localhost = localhost_t

        instance_stats = {
            'hostname': localhost,
            'instances_count': instances_count,  # this is need for check in calculate average
            'workers_count': amount_of_workers,
            'devices_count': len(devices_list),
            'devices_ids': devices_list,
            'last_starttime': '%.3f' % (s_time,),
            'last_runtime': '%.3f' % (total_time,),
            'last_status': exit_code,
            # 'last_message':    msg
        }

    instances_clean = []
    instances_total_time = total_time
    instances_total_devices = len(devices_list)
    i = 1

    if poller_default:
        poller_instance_base = 'poller_wrapper_instance_'
    else:
        poller_instance_base = 'poller_%s_instance_' % (poller_id, )


    query = "SELECT `attrib_type`, `attrib_value` FROM `observium_attribs` WHERE `attrib_type` LIKE '%s'" % (poller_instance_base + '%', )
    cursor.execute(query)

    for row in cursor.fetchall():
        # print(row)
        n = int(row[0].replace(poller_instance_base, ''))  # get stat for instance number n
        if n >= instances_count:
            # seems as old stats, instances count reduced
            instances_clean.append(row[0])
        elif n == instance_number:
            # current instance, skip
            pass
        else:
            instance = json.loads(row[1])
            if int(instance['instances_count']) == instances_count:
                # print(instance)
                i += 1
                instances_total_time += float(instance['last_runtime'])
                instances_total_devices += int(instance['devices_count'])
            else:
                instances_clean.append(row[0])

    instances_average_time = instances_total_time / i
    poller_stats['total_devices_count'] = instances_total_devices
    poller_stats['total_runtime'] = '%.3f' % instances_total_time
    poller_stats['average_runtime'] = '%.3f' % instances_average_time

    # print(poller_stats)
    # print(instance_stats)
    # print(instances_clean)

    # write instance and total stats into db
    if stats:
        cursor.execute("SELECT EXISTS (SELECT 1 FROM `observium_attribs` WHERE `attrib_type` = %s)",
                       (poller_instance_base + str(instance_number),))
        row = cursor.fetchone()
        if int(row[0]) > 0:
            cursor.execute("UPDATE `observium_attribs` SET `attrib_value` = %s WHERE `attrib_type` = %s",
                           (json.dumps(instance_stats), poller_instance_base + str(instance_number)))
            # db.commit()
            # print("Number of rows updated: %d" % cursor.rowcount)
        else:
            cursor.execute("INSERT INTO `observium_attribs` (`attrib_value`,`attrib_type`) VALUES (%s,%s)",
                           (json.dumps(instance_stats), poller_instance_base + str(instance_number)))
            # db.commit()

        # clean stale instance stats
        for row in instances_clean:
            cursor.execute("DELETE FROM `observium_attribs` WHERE `attrib_type` = %s", (row,))
            # db.commit()

    # Write poller statistics to RRD
    if not poller_default:
        # update partitioned poller stats
        if poller_db_last >= 0:
            cursor.execute("UPDATE `pollers` SET `poller_stats` = %s WHERE `poller_id` = %s",
                           (json.dumps(poller_stats), poller_id))
            # db.commit()
            # print("Number of rows updated: %d" % cursor.rowcount)

        # partitioned poller wrapper
        rrd_name_base = 'poller-wrapper-id' + str(poller_id)
    else:
        # update main poller 0 stats
        cursor.execute("SELECT EXISTS (SELECT 1 FROM `observium_attribs` WHERE `attrib_type` = %s)",
                       ('poller_wrapper_stats',))
        row = cursor.fetchone()
        if int(row[0]) > 0:
            cursor.execute("UPDATE `observium_attribs` SET `attrib_value` = %s WHERE `attrib_type` = %s",
                           (json.dumps(poller_stats), 'poller_wrapper_stats'))
            # db.commit()
            # print("Number of rows updated: %d" % cursor.rowcount)
        else:
            cursor.execute("INSERT INTO `observium_attribs` (`attrib_value`,`attrib_type`) VALUES (%s,%s)",
                           (json.dumps(poller_stats), 'poller_wrapper_stats'))
            # db.commit()

        # default poller wrapper
        rrd_name_base = 'poller-wrapper'

    rrd_path_total = rrd_name_base + '.rrd'
    rrd_path_count = rrd_name_base + '_count.rrd'

    update_wrapper_times(rrd_path_total, poller_stats['total_devices_count'], poller_stats['average_runtime'],
                         amount_of_workers)
    update_wrapper_count(rrd_path_count, poller_stats['wrapper_processes'])

    # Additionally per instance statistics
    if instances_count > 1:
        rrd_path_instance = rrd_name_base + '_' + str(instances_count) + '_' + str(instance_number) + '.rrd'
        update_wrapper_times(rrd_path_instance, instance_stats['devices_count'], instance_stats['last_runtime'],
                             amount_of_workers)


# Remove process info from DB
p_query = """DELETE FROM `observium_processes` WHERE `process_id` = %s"""
try:
    cursor.execute(p_query, (process_id,))
    # db.commit()
except:
    pass

# Close opened handles
db.close()
FNULL.close()

# Return exit code
sys.exit(exit_code)

# EOF
