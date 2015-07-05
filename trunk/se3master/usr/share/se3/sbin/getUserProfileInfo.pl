#!/usr/bin/perl


## $Id$ ##


use Net::LDAP;
require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 0);
$uid = shift @ARGV;

$dn = 'uid=' . $uid . ',' . $peopleDn;
$uid =~ /^(\w*)\.(\w*)$/;

die if $uid eq '';

  $ldap = Net::LDAP->new(
                         "$slapdIp",
                         port    => "$slapdPort",
                         debug   => "$slapdDebug",
                         timeout => "$slapdTimeout",
                         version => "$slapdVersion"
                        );
  $ldap->bind(); # Anonymous BIND
  $test = $ldap->search(
                       base     => "$baseDn",
                       scope    => 'sub',
                       attrs    => ['uid'],
                       filter   => "uid=$uid"
                      );

#die("resultat de la recherche : " . $test->error . ".\n") if ($res->code eq 0); 
#if ($test->count eq 1 ) {
foreach $entry ($test->entries) {
    $l = $entry->get_value(l);
    print "$l\n";         
 }
exit 0
