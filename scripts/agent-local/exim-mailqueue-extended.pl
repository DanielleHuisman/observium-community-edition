#! /usr/bin/perl
# Michael Rave <michael@crossivity.com> - Open for ideas and fixes
# For Observium - Network management and monitoring
#
# Exim extended mailgrapher

use strict;

# Options
#
# $stats - file which contains the current statistics

my $statsFile = "/usr/share/eximstats/statistics";

system("/usr/share/eximstats/exim_stats_process.pl > /dev/null 2>&1");

open STATS, "< $statsFile";
while (<STATS>) {
	print;
}
close STATS;
