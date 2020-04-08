#!/usr/bin/env python
import argparse
import collections
import re
logfile = "/opt/observium/logs/discovery_log"
topnumber = 10
def strip_ansi_codes(s):

    return re.sub(r'\x1b\[([0-9,A-Z]{1,2}(;[0-9]{1,2})?(;[0-9]{3})?)?[m|K]?', '', s)

parser = argparse.ArgumentParser(description='Process Observium logfile and print the slowest SNMP-commands')
parser.add_argument('--logfile', dest='logfile', action='store',
                    help='path to the logfile (default: /opt/observium/logs/discovery_log)')
parser.add_argument('topnumber', metavar='N', type=int, nargs='?', default=10,
                    help='show top n commands (default: 10)')
args = parser.parse_args()
if args.logfile:
        logfile = args.logfile
topnumber = args.topnumber

buf = collections.deque((), 4)
commandlist = []
with open(logfile) as log:
    for line in log:
        buf.append(line)
        if 'CMD RUNTIME' in line:
            snmp_result = '\n'.join(buf)
            commandresult = re.search(r'CMD\[(.*?)\]', snmp_result)
            runtimeresult = re.search(r'RUNTIME\[(.*?)s', line)
            command = commandresult.group(1)
            runtime = runtimeresult.group(1)
            runtime_stripped = strip_ansi_codes(runtime)
            commandlist.append((float(runtime_stripped), command))
            buf.clear()

sortedlist = list(reversed(sorted(commandlist)))
toplist = sortedlist[:topnumber]
print "Time - SNMP Command"
print "-------------------"
for row in toplist:
        print "%s - %s" % (row[0],row[1])
