#! /usr/bin/perl -w
# Modules
use strict;
use Getopt::Long;
###########################
# mrtg-eth.pl             #
my $version="1.5.5";      #
# Mario Witte             #
# mario.witte@chengfu.net #
###########################

################################################################################
# Configuration                                                                #
my $ssh="/usr/bin/ssh";                   # Path to ssh                        #
my $ssh_opt="-o 'BatchMode yes' ";        # ssh-Options                        #
$ssh_opt.="-o 'StrictHostKeyChecking no'";#                                    #
my $devinfo="/proc/net/dev";              # Where to read device info from     #
my $in_pos=0;                             # Position of bytes_in in $devinfo   #
my $out_pos=8;                            # Position of bytes_out in $devinfo  #
my $reverse=0;                            # reverse in/out bytes in output     #
################################################################################

# Declare some variables
my $help=0;
my $helptext;
my $device;
my $remote_host;
my $identity_file;
my $remote_user;
my $remote_port;
my $ssh_protocol;
my %devinfo;

# Read Commandline parameters
&GetOptions(	"device=s"	=>	\$device,
		"remotehost:s"	=>	\$remote_host,
		"identity:s"	=>	\$identity_file,
		"login:s"	=>	\$remote_user,
		"port:i"	=>	\$remote_port,
		"protocol:i"	=>	\$ssh_protocol,
		"pos_in:i"	=>	\$in_pos,
		"pos_out:i"	=>	\$out_pos,
		"t"	=>	\$reverse,
		"help"	=>	\$help);

# If requested or no parameters given display help
if (!$device) { $help=1; $helptext=""; }

# Check if devicename is valid 
if (($device) && ($device=~/^-/)) { 
	$help=1;
	$helptext.="'$device' doesn't look like a device name\n"; 
} # end if $device

# Check if remotehost is valid
if (($remote_host) && ($remote_host=~/^-.{0,3}/)) {
	$help=1;
	$helptext.="'$remote_host' doesn't seem to be a hostname\n";
} # end if $remote

# Open help if requested/needed
if ($help==1) {
	&help("$helptext");
	exit;
}

if( $ssh_protocol ) {
	if( $ssh_protocol == 1 or $ssh_protocol == 2 ) {
		$ssh_opt .= " -$ssh_protocol";
	}
}

if( $identity_file ) {
	$ssh_opt .= " -i $identity_file";
	$ENV{'SSH_AUTH_SOCK'} = '';
}

if( $remote_user ) {
	$ssh_opt .= " -l $remote_user";
}

if( $remote_port ) {
	$ssh_opt .= " -p $remote_port";
}

# Read statistics
if ($remote_host) { # remote host given, connect via ssh
	my $ssh_cmd = "$ssh $ssh_opt $remote_host cat $devinfo";
	open (DEV, "$ssh_cmd|");
} else { # read from localhost
	open (DEV, "< $devinfo");
}

map { @{$devinfo{$1}} = split /\s+/, $2 if( m/^\s*(.*):\s*(.*)$/); } <DEV>;
close DEV;

if (scalar keys %devinfo == 0) { &help("Could not read device info"); exit; }

if( ! defined $devinfo{$device} ) { &help("device $device not found"); exit; }

my $bytesin = $devinfo{$device}->[$in_pos];
my $bytesout = $devinfo{$device}->[$out_pos];

# Print Bytes per second to stdout
if ($reverse == 0) { print $bytesin . "\n"; }
print $bytesout . "\n";
if ($reverse == 1) { print $bytesin . "\n"; }

# Exit
exit;


# Subs
sub help($) {
	if ($_[0]) { print "There were errors:\n $_[0]\n"; }
	print "mrtg-eth.pl version $version - mario.witte\@chengfu.net\n";
	print "\n";
	print "Usage: mrtg-eth.pl -d device [-r host [-l login] [-i identity] [--port port] [--protocol 1|2]] [--pos_in n] [--pos_out n] [-t] [-b]\n"; 
	print "\n";
	print "Options:\n";
	print "\t-d device   - Device to be monitored (e.g. eth0, ippp1)\n";
	print "\t-r host     - If set, will try to connect to remote\n";
	print "\t              host via ssh (SSH)\n";
	print "\t-l login    - user on remote host (SSH)\n";
	print "\t-i identity - use this private-key to connect to remote host (SSH)\n";
	print "\t--protocol  - use Protocol 1 or 2 to connect to remote host (SH)\n";
	print "\t--port p    - remote-sshd listens on port p (SSH)\n";
	print "\t\n";
	print "\t--pos_in n  - Position of bytes_in in $devinfo\n";
	print "\t--pos_out n - Position of bytes_out in $devinfo\n";
	print "\t\n";
	print "\t-t          - reverse in/out bytes in output\n";
	print "\n";
	print "Options marked with '(SSH)' are only useful when connecting\n";
	print "to a remote host using SSH\n";
	print "\n";
} # end sub help
