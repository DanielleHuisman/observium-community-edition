#!/usr/bin/perl -w

# Script to add a DS to an rrd

# How to us:
#   1. take a backup
#   2. add_ds_to_rrd.pl <rrd directory> <filename> <DS defintion>

# Example: how to add a DS to all the file vmstat.rrd in /home/users/hobbit/data/rrd
#    add_ds_to_rrd.pl /home/users/hobbit/data/rrd vmstat.rrd cpu_pc:GAUGE:600:1:U

# Written bij Stef Coene (http://www.docum.org/foswiki/bin/view/Xymon/WebHome)
# Use at own risk

use strict;

#my $rrdtool = '/usr/bin/rrdtool';
my $rrdtool = 'rrdtool';

my $rrddir  = $ARGV[0] or die "no rrd dir" ;
my $file    = $ARGV[1] or die "no file specification" ;
my $DStoadd = $ARGV[2] or die "no DS definition" ;

my ($dsname, $dstype, $dshb, $dsmin, $dsmax, $undef) = split(/:/, $DStoadd);
die "illegal source format\n" unless defined $dsmax and not defined $undef;

use File::Find ();

# Traverse desired filesystems
our @FILE ;
File::Find::find({wanted => \&wanted}, "$rrddir");

sub wanted {
   if ( ( -f $File::Find::name ) and
        ( $File::Find::name =~ /\/$file\z/s ) ) {
      our @FILE ; # Importeren van @FILE
      push (@FILE, $File::Find::name) ;
   }
}

foreach my $infile (@FILE) {
   my $outfile = $infile . "." . $$ ;
   print "$infile  ->  $outfile\n" ;

   open(IN, "$rrdtool dump $infile|") or die "$!";
   open(OUT, "|$rrdtool restore - $outfile") or die "$!";

   while (<IN>) {
      # Define new data source
      m#<!-- Round Robin Archives --># and do {
         print OUT <<".";
<ds>
   <name> $dsname </name>
   <type> $dstype </type>
   <minimal_heartbeat> $dshb </minimal_heartbeat>
   <min> $dsmin </min>
   <max> $dsmax </max>

   <!-- PDP Status -->
   <last_ds> U </last_ds>
   <value> 0.0000000000e+00 </value>
   <unknown_sec> 0 </unknown_sec>
</ds>

.
      };

      # Add empty entry to the values
      m#</cdp_prep># and do {
      print OUT <<"."
<ds><primary_value> NaN </primary_value> <secondary_value> NaN </secondary_value> <value> NaN </value>  <unknown_datapoints> 0 </unknown_datapoints></ds>
.
      };

      # Add empty entries to the database
      s#</row>#<v> NaN </v></row>#;

      print OUT $_;
   }

   close(IN) or die "$!";
   close(OUT) or die "$!";

   unlink $infile ;
   rename $outfile, $infile ;
}
