#!/usr/bin/perl


## $Id$ ##


require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 0);
$uid = shift @ARGV;

$dn = 'uid=' . $uid . ',' . $peopleDn;
$uid =~ /^(\w*)\.(\w*)$/;

die if $uid eq '';

$res = system("/usr/share/se3/sbin/entryDel.pl \"$dn\"");

exit O;
