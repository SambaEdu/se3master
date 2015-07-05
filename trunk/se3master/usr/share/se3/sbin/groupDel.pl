#!/usr/bin/perl

## $Id$ ##

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 0);
$cn = shift @ARGV;
$dn = "cn=$cn,$groupsDn";

$res = system("/usr/share/se3/sbin/entryDel.pl \"$dn\"");

exit O;
