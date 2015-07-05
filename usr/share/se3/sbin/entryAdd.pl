#!/usr/bin/perl

## $Id$ ##

use Net::LDAP;
use Net::LDAP::Entry;

require '/etc/SeConfig.ph';

if ($#ARGV < 2) {
  print
    "Usage :\n",
    "\tentryAdd.pl distinguishedName (",
    "liste d'arguments 'attribut=valeurs')",
    "\n";
  die("Erreur d'argument\n");
}

($dn, @reste) = @ARGV;
$entryToAdd = Net::LDAP::Entry->new;
$entryToAdd->dn($dn);

$attrs = join ' ', @reste;

while (1) {
  $n=0;
  while ($attrs =~ /\=/g) {
    $n++;
  }
  last if $n == 1;
  $attrs =~ /^(.*)\s+(\w*)=(.*)$/;
  $attrs = $1;
  $entryToAdd->add($2 => $3);
}

($lastAttribute, $lastValue) = split /=/, $attrs;
$entryToAdd->add($lastAttribute => $lastValue);

# DEBUG INFO
#############
#print "\ndn : $dn\n";
#foreach my $attr ($entryToAdd->attributes) {
#  foreach my $value ($entryToAdd->get_value($attr)) {
#    print "$attr : $value\n";
#  }
#}

$ldap = Net::LDAP->new(
 		       "$slapdIp",
 		       port    => "$slapdPort",
 		       debug   => "$slapdDebug",
 		       timeout => "$slapdTimeout",
 		       version => "$slapdVersion"
 		      );
$ldap->bind(
 	    $adminDn,
 	    password => $adminPw
 	   );
$res = $ldap->add($entryToAdd);

die("Erreur LDAP : " . $res->error . ".\n") if ($res->code ne 0);

exit 0;
