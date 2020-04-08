#! /usr/bin/perl
# Michael Rave <michael@crossivity.com> - Open for ideas and fixes
# For Observium - Network management and monitoring
#
# Exim extended mailgrapher

use strict;

# Options
#
# $curState - file which stores the current mainlog state
# $statsFile - file which contains the current statistics
# $mainlog - exim mainlog file
# $prevMainlog - previous exim mainlog (rotated, not compressed)

my $curState = "/usr/share/eximstats/current_state";
my $statsFile = "/usr/share/eximstats/statistics";
my $mainlog = "/var/log/exim/main.log";
my $prevMainlog = `ls -Art /var/log/exim/main.log-* | tail -n 1`;

# The below variables define how emails are marked in the logs
my $receivedRule =  'T=local_mysql_delivery';
my $sentRule = 'T=remote_smtp H=';
my $spamRule = 'rejected after DATA: This message is classified as UBE';
my $virusRule = 'rejected after DATA: This message contains a virus';
my $bouncedRule = '<= <>';
my $greylistRule = 'will be GreyListed';
my $rejectedRule = '> rejected';
my $delayedRule = 'T=remote_smtp defer';

my $exim;
my %stats = ();
my $seek = 0;
my $seekError = 0;
my $inode = inode_number($mainlog);

# set values to defaults
$stats{"received"} = 0;
$stats{"sent"} = 0;
$stats{"spam"} = 0;
$stats{"virus"} = 0;
$stats{"bounced"} = 0;
$stats{"greylisted"} = 0;
$stats{"rejected"} = 0;
$stats{"delayed"} = 0;


# locate exim executable
$exim = `which exim 2>/dev/null`;
chomp($exim);
if ($? == 256) {
	$exim = `which exim4 2>/dev/null`;
} 
if ($? == 256) {
	print "exim not found.\n";
	exit;
}

if (-r $curState) {
	open CURSTATE, "< $curState";
	while (<CURSTATE>) {
		chomp;
		s/^\s*//;
    		s/\s*$//;
		next if /^$/;
    		if (/^inode=(\d+)$/) {
      			$inode = $1;
    		}
    		if (/^seek=(.*)$/) {
      			$seek = $1;
    		}
	}
	close CURSTATE;
}


open LOG, "< $mainlog" or die "cannot open exim log file $mainlog!";
if (!seek(LOG, $seek, 0)) {
	$seekError = 1;
}
close LOG;

if ($inode != inode_number($mainlog) or
    $seekError or
    $inode == inode_number($prevMainlog)) {
	# Logfile rotated, read previous mainlog
	read_log($prevMainlog, $seek, \%stats);
	$inode = inode_number($mainlog);
	$seek = 0;
}

$seek = read_log($mainlog, $seek, \%stats);

write_stats($statsFile, \%stats);

# create current state file
open CURSTATE, "> $curState";
print CURSTATE "inode=$inode\n";
print CURSTATE "seek=$seek\n";
close CURSTATE;

# functions
sub inode_number {
  my $file = shift;
  my ($dummy, $inode) = stat($file);
  return $inode;
}

sub read_log {
	my ($file, $seek, $stats) = @_;
	my $prevline = undef;
	my $line;
	my ($prevpos, $pos);
	local *LOG;

	open LOG, "< $file" or die "cannot open exim log file $file!";
	if (!seek(LOG, $seek, 0)) {
		close LOG;
		return $seek;
	}
  	
	while ($line = <LOG>) {
		if (defined $prevline) {

			if ($line =~ /$receivedRule/ && $receivedRule) {
				$$stats{"received"}++;
				next;
			} 
			
			if ($line =~ /$sentRule/ && $sentRule) {
				$$stats{"sent"}++;
                                next;
			} 

			if ($line =~ /$spamRule/ && $spamRule) {
				$$stats{"spam"}++;
                                next;
			} 

			if ($line =~ /$virusRule/ && $virusRule) {
				$$stats{"virus"}++;
                                next;
			} 
			
			if ($line =~ /$bouncedRule/ && $bouncedRule) {
                                $$stats{"bounced"}++;
                                next;
			} 
	
			if ($line =~ /$greylistRule/ && $greylistRule) {
                                $$stats{"greylisted"}++;
                                next;
                        } 

			if ($line =~ /$delayedRule/ && $delayedRule) {
                                $$stats{"delayed"}++;
                                next;
                        }

			if ($line =~ /$rejectedRule/ && $rejectedRule) {
                                $$stats{"rejected"}++;
                                next;
                        }
		}
		$prevline = $line;
		$prevpos = $pos;
		$pos = tell LOG;
	}
	close LOG;

	$prevpos = $seek unless defined $prevpos;

	return $prevpos;
}

sub write_stats {
	my ($file, $stats) = @_;
	local *STATS;

	open STATS, "> $file";
	print STATS <<EOF;
<<<app-exim>>>
received:$$stats{"received"}
sent:$$stats{"sent"}
spam:$$stats{"spam"}
virus:$$stats{"virus"}
bounced:$$stats{"bounced"}
greylisted:$$stats{"greylisted"}
rejected:$$stats{"rejected"}
delayed:$$stats{"delayed"}
EOF
	close STATS;
}

# debug
print $exim;
print $mainlog;
print $prevMainlog;
print $inode;
