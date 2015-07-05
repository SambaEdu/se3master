#!/usr/bin/perl


## $Id$ ##

use POSIX;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 1);
($machine, $parc) = @ARGV;

$res = system("/usr/share/se3/sbin/isMemberOf.pl $machine \"cn=$parc,$parcDn\" > /dev/null 2>&1");

die "$machine n'est pas dans le parc $parc.\n" if ($res ne '0');

exit 0;
