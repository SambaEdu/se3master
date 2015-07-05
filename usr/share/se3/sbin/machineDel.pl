#!/usr/bin/perl

# $Id$ #

##### Script utilisé pour virer les machines des l'annuaire

use Net::Domain;
use Unicode::String qw(latin1 utf8);
use Net::LDAP;
use POSIX;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 1);
($machine_uid, $ipAddress) = @ARGV;
$machine = $machine_uid;
chop($machine);
# print "$machine\n";
# print "$machine_uid\n";

# Recherche LDAP de la machine dans la branche ou=Computers
# ---------------------------------------------------------
$ldap = Net::LDAP->new(
   "$slapdIp",
   port => "$slapdPort",
   debug => "$slapdDebug",
   timeout => "$slapdTimeout",
   version => "$slapdVersion"
);
$ldap->bind(); # Anonymous BIND
$res = $ldap->search(
   base => "cn=$machine,$computersDn",
   scope => 'base',
   attrs => ['cn'],
   filter => "cn=$machine"
);
print "$machine n'existe pas dans $computersDn\n"  if $res->code;
#print "res->code = ". $res->code .", res->entries0 = ". ($res->entries)[0] ."\n";
if (($res->entries)[0]) {
   $cn = ($res->entries)[0]->get_value('cn');
   print "entree cn=$cn existante\n"
}
# base => 'uid='.$machine_uid.','.$ComputersDn,
$res = $ldap->search(
   base => 'uid='.$machine_uid.','.$computersDn,
   scope => 'base',
   filter => 'uid=*'
);
print "$machine_uid n'existe pas dans $computersDn\n"  if $res->code;
if (($res->entries)[0]) {
   $uid = ($res->entries)[0]->get_value('uid');
   print "entree uid=$uid existante \n"
}
$ldap->unbind();

if ($uid) {
print "on supprime l'entree machine existante en \"uid=$machine_uid,$computersDn\"\n";

system("/usr/share/se3/sbin/entryDel.pl \"uid=$machine_uid,$computersDn\"");
}
# $cn=1;
if ($cn) {
# on vire l'entree machine existante
print "on supprime l'entree machine existante \"cn=$machine,$computersDn\"\n";
system("/usr/share/se3/sbin/entryDel.pl \"cn=$machine,$computersDn\"");
}

$res = $ldap->search(
   base => "$parcsDn",
   scope => 'base',
   attrs => ['cn'],
   filter => 'member=cn='.$machine.','.$computerDn
);
print "$machine n'est pas dans un parc\n"  if $res->code;
if (($res->entries)[0]) {
   $cn = ($res->entries)[0]->get_value('cn');
   print "entree cn=$cn existante\n"
}


# print "ip=$ip\n";
# print "mac=$mac\n";
#print "arp=$arp\n";
$ldap = Net::LDAP->new(
"$slapdIp",
port => "$slapdPort",
debug => "$slapdDebug",
timeout => "$slapdTimeout",
version => "$slapdVersion"
);
$ldap->bind(
$adminDn,
password => $adminPw
);
# Ajout
# -----
$res = $ldap->add(
"cn=$machine,$computersDn",
attrs => [
cn => $machine,
objectClass => 'top',
objectClass => 'ipHost',
objectClass => 'ieee802Device',
objectClass => 'organizationalRole',	
ipHostNumber => $ipAddress,
macAddress => $mac
]);
die("Erreur lors de l'ajout de l'entrée dans l'annuaire.\n") if ($res->code() != 0);

# Déconnexion
# -----------
$ldap->unbind;

exit 0;


