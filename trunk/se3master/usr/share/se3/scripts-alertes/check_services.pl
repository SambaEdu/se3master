#!/usr/bin/perl
#
# Script takes as argument list of pairs host:service, ie
# ./check_service.pl host1:serwis1,host2:serwis2
# Returns:
#  0, if all pairs host:service has correct status
#  2, if any of pair host:service has wrong status (U/W/C)
#  3, if any of pair host:service was not found in status file
#
# Hostname can contain only letters/digits/_/-
# Service name can contain only letters/digits/_/-/ /.
#
# Version 1.0, 04.10.2006, Mariusz Preiss
#
# command in services.cfg looks like
#  check_command   check_services.pl!host1:PING,host2:FTP
#  (warning confirmations has to be sent also)
# in checkcommand.cfg following section has to be added
#  define command{
#       command_name    check_services.pl
#       command_line    $USER1$/check_services.pl $ARG1$
#       }
#


# path to the status file
$stat_filename = "/usr/local/nagios/var/status.dat";

# we read file with blocks
use English;
$INPUT_RECORD_SEPARATOR = "}";

# lets check the status file
open DANE, "< $stat_filename" or die "Cannot access file $stat_filename\n";

# status checking function of service on host, or host itself
# Status' (current_state) in status.dat file
# Serwisy
#  0 - OK
#  1 - Warning
#  2 - Critical
#  3 - Unknown
#  4 - Dependent
sub check_status($$) {
  seek(DANE,0,0);
  my ($host, $service) = @_;

  while(<DANE>) {
    # we identify block using hostname and service description
    if($_ =~ /\sservice_description=$service\n/i and
       $_ =~ /\shost_name=${host}\n/i and
       $_ =~ /\scurrent_state=(\d+)\n/i) {
      return $1;
    }
  }
  return -1;
}


# if the argument list is empty, we can leave
my $args = join(' ',@ARGV);
if($args !~ /^([\d\w\-]+:[\d\w\.\-\s]+,?)+$/) {
  die "Wrong arguments in check_service.pl script";
}

# we are preparing variable, which will contain exit status
my $error = 0;

# we are preparing variables for loop below
my @args = split(',',join(' ',@ARGV));
foreach(@args) {
  if($_ =~ /([\d\w\-]+):([\d\w\.\-\s]+)/) {
    my $host_service = "${1}:${2}";
    my $status = check_status($1,$2);
    if($status > 0) {
      $error = 2;
    }
    elsif($status == -1) {
      # there is no such pair host:service
      $error = 3;
      print "Error - pair $host_service not found in status.dat file!\n";
      exit $error;
    }
  }
}

close DANE;
if($error > 0) {
  print "Critical error!\n";
}
else {
  print "Status OK\n";
}
exit $error;
