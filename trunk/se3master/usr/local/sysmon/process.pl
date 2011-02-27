#!/usr/bin/perl
use POSIX;

#========================================================
# Memory an cpu usage for process script for mrtg
#
#    File: 	process.pl
#    Author: 	Chretien Jean-Luc
#               jeanluc.chretien@tice.ac-caen.fr
#
#    Version:	1.0
#
#    Date:	11/08/02
#    Purpose:   This script reports process Memory usage
#               for Men & Cpu to mrtg.
#
#    Usage:	/path/process.pl
#
#
#    Info:	Designed on Debian Woody
#--------------------------------------------------------

$path = "/usr/bin/";
$top = "top -b -n 1 | grep ";


sub Validate() {	#validate command line args
  if ((scalar(@ARGV) > 1) || ($ARGV[0] eq '')) {
     print "ERR: Must specify  one process to be monitored.\n";
     Usage;
     exit;
  }
}

#####  Main Program Begins Here  ######
Validate();
# assign command line args to variables
$process =  $ARGV[0];

# Grab the top data locally
$gettop = `$path$top $process`;

### DEBUG ###
# print $gettop."\n";
### DEBUG ###

# parse top data
@lines=split /^/m, $gettop;

foreach $line (@lines)  {
  @fields=split(/\s+/,$line);
  $getcpu = $getcpu+$fields[10];
  $getmem = $getmem+$fields[11];
}

#Print getcpu & getmem data for mrtg
print ceil($getcpu)."\n";
print ceil($getmem)."\n";

exit (0);
