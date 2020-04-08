#!/bin/sh

SCRIPT=`which $0`
SCRIPTDIR=`dirname $SCRIPT`
ABSPATH="`cd \"$SCRIPTDIR\" 2>/dev/null && pwd`"
CONF=$ABSPATH/freeradius.cnf

if [ ! -e $CONF ];then
  echo 'client="/usr/bin/radclient"' >$CONF
  echo 'secret="adminsecret"' >>$CONF
  echo 'port="18121"' >>$CONF
  echo ' # Timeout for each attempt in seconds' >>$CONF
  echo 'timeout=2' >>$CONF
  echo ' # Number of times to try' >>$CONF
  echo 'retries=3' >>$CONF
fi # if [ ! -e $CONF ];

msg1="Message-Authenticator = 0x00, FreeRADIUS-Statistics-Type = Authentication, Response-Packet-Type = Access-Accept"
msg2="Message-Authenticator = 0x00, FreeRADIUS-Statistics-Type = Accounting, Response-Packet-Type = Access-Accept"

. $CONF
echo '<<<freeradius>>>'
(echo "$msg1" | $client -x -t $timeout -r $retries 127.0.0.1:$port status $secret;echo "$msg2" | $client -x -t $timeout -r $retries 127.0.0.1:$port status $secret) | awk '/FreeRADIUS-Total/{print $1":"$3}'

