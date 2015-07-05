#!/usr/bin/perl


## $Id$ ##


require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 1);
($uid, $cn) = @ARGV;
$uidDn = "uid=$uid,$peopleDn";
$cnDn  = "cn=$cn,$groupsDn";

$res = system("/usr/share/se3/sbin/groupDelEntry.pl \"$uidDn\" \"$cnDn\"");

exit O;
