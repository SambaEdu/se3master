#!/usr/bin/perl
# disk-linux.pl #####################################################
#
# MRTG Performance Enhancements v1.0.2
#
# This script grabs Linux Performance Monitoring (PM) data for MRTG 
#    on the partition utilization of the system.
# 
# Statistics args accepted:
# 1k-blocks, Used, Available, Use%
#
# Usage: disk-linux.pl host mount stat1 stat2 
# Example: ./disk-linux.pl myhost / Used Available
#
# By:
# Mark Miller Crave Technology markm@cravetechnology.com
# Bill Lynch Crave Technology billl@cravetechnology.com
#####################################################################

#####  Subroutines  #####

# Grab the PM data locally
sub localh {
$disk = `df -m`;
$uptime = `uptime`;
}

# Grab the PM data remotely
sub remoteh {
$disk = `/usr/local/bin/ssh $host df -m`;
$uptime = `/usr/local/bin/ssh $host uptime`;
}

sub Usage() {	#display correct usage info
  print "Usage: disk-linux.pl host mount stat1 stat2\n";
  print "      ex: ./disk-linux.pl myhost / Used Available\n";
}

sub Validate() {	#validate command line args
  if ((scalar(@ARGV) > 4) || ($ARGV[0] eq '')) {
     print "ERR: Must specify at least one stat to be monitored.\n";
     Usage;
     exit;
  }
}

sub parseuptime() {
# This sub returns only the number of days of uptime
#  A box with less than 1 day of uptime will show 0 days of uptime
$uptime=~s/,//g;
@utime = split /\s/, $uptime;
for ($i = 1; $i < 8; $i++) {
	if ($utime[$i] eq "days" || $utime[$i] eq "day(s)" || $utime[$i] eq "day") {
		$upout = $utime[$i-1]." jour(s)";
	}
}
if ($upout eq "") { $upout = "0 jour(s)"; }
}

#####  Main Program Begins Here  ######
Validate();

# Get the hostnames
$localhost = `/bin/hostname`;
chop($localhost);
$host = $ARGV[0];

# Determine the short hostname
@local = split(/\./,$localhost);
$shorthost = $local[0];

# assign command line args to variables
$mount = $ARGV[1];
$stat1 = $ARGV[2];
$stat2 = $ARGV[3];

# set vars ##########################################################
$count = 0;
$linenum = 0;

# Determine if the host is local or remote
if ($host eq $localhost) {
	localh();
} elsif ($host eq $shorthost) {
	localh();
} else {
	remoteh();
}

# parse df -k data
@lines=split /^/m, $disk;
foreach $line (@lines) {
  $count++;
  $line =~ /^\S+\s+\d+\s+\d+\s+\d+\s+\S+\s+(\S+)/;
  if ($1 eq $mount) {
    $linenum = $count-1;
  }
}
if ($linenum < 1) {
  print"ERR: Unknown mount point\n";
  Usage();
  exit;
}
$line = @lines[$linenum];
$line =~ /^\S+\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/;
$kbytes = $1;
$used = $2;
$avail = $3;
$usepercent = $4;

# output df -k data in MRTG format
if ($stat1 eq "1k-blocks") { print "$kbytes\n"; }
if ($stat1 eq "Used") { print "$used\n"; }
if ($stat1 eq "Available") { print "$avail\n"; }
if ($stat1 eq "Use%") { print "$usepercent\n"; }
if ($stat2 eq "1k-blocks") { print "$kbytes\n"; }
if ($stat2 eq "Used") { print "$used\n"; }
if ($stat2 eq "Available") { print "$avail\n"; }
if ($stat2 eq "Use%") { print "$usepercent\n"; }

# Parse uptime data
parseuptime();

# Print uptime data in MRTG format
print $upout."\n"; 
