#!/usr/bin/perl

use File::Basename;
use File::Copy;
use Getopt::Std;
use Digest::SHA qw(sha256_hex);

usage() unless (@ARGV);

my %options=();
getopts("fhse:", \%options);

usage() if defined $options{h};

for my $file (@ARGV)
{
	my $from = $file;
	my $dir  = dirname($file);
	my $to;
	my $ext  = '';
	my $mib  = '';
	my $lines = 0;

	if (defined $options{e} && $options{e}) {
		$ext = '.'.$options{e};
	}
	
	if (open (FILEIN, $file))
	{
		while (<FILEIN>)
		{
			next if (/^\s*\-\-/ || /^\s*$/); # Skip comments and empty lines
			next if (/FORCE\-INCLUDE/); 		 # Skip some special words

			$mib .= $_;
			if ($mib =~ /\s*(\S+)\s*DEFINITIONS\s*\:\:\=\s*BEGIN/)
			{
				$to = $1;
				last;
			} elsif (/\s*OBJECT\s+IDENTIFIER/) {
				#	Break on OBJECT IDENTIFIER
				last;
			} elsif ($lines > 500) {
				#	Break limit line count
				last;
			}
			$lines++;
		}

		close (FILEIN);

		if (defined $to)
		{
 			if (basename($from) eq basename($to.$ext))
			{
				print "skipping $file -- name is already correct\n";
			} else {
				if ($dir)
				{
					$to = $dir . '/' . $to . $ext;
				}
				if (-s $to && !defined $options{f})
				{
					my $same = compare_files($file, $to); # check if files same

					if ($same && !defined $options{s}) {
						# Same files, overwrite
            move($from, $to);
						print "renamed '$file' -> $to (overwritten)\n";
          } else {
						print "skipping $file -- file $to exist. Use -f flag for override\n";
					}
				} elsif (defined $options{s}) {
					system("/usr/bin/svn mv $from $to");
					#print "/usr/bin/svn mv $from $to\n";
				} else {
					move($from, $to);
					print "renamed '$file' -> $to\n";
				}
			}
		}
		else
		{
			warn "no definition found inside $file\n";
		}
	}
	else
	{
		warn "skipping $file -- unable to open ($!)";
	}
}

sub usage
{
	print <<END;
usage: $0 [OPTION] <MIB1> [MIB2 .. MIBn]

  Renames one or more MIB files to match the definition inside the file.

OPTIONS

  -h display this help and exit
  -s use 'svn mv' instead system mv command
  -e 'ext' use this file extension for renamed MIBs, by default without extension

END

	exit 0;
}

sub compare_files
{
  my ($file1,$file2)=@_;
 
  open(my $fh1,"< ",$file1) or die $!;
  open(my $fh2,"<",$file2) or die $!;

  my $sha=Digest::SHA->new(256);
  $sha->addfile($fh1); #Reads file.
  my $hex1=$sha->hexdigest; #40 byte hex string.

	$sha->reset;
  $sha->addfile($fh2);
  my $hex2=$sha->hexdigest;

  close($fh1);
  close($fh2);

  #print "hex1: $hex1, hex2: $hex2\n";
  return $hex1 eq $hex2;
}
