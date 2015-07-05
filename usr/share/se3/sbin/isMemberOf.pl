#!/usr/bin/perl


## $Id$ ##


use Net::LDAP;
use POSIX;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 1);
($valueToSearch, $dnToSearchIn) = @ARGV;

$attribute = typeOfGroup($dnToSearchIn);

$res = $ldap->search(
		      base     => "$dnToSearchIn",
		      scope    => 'base',
		      attrs    => ['$attribute'],
		      filter   => 'objectClass=*'
		     );

foreach $entry ($res->entries) {
  @members  = $entry->get_value($attribute);
}
foreach $membre (@members) {
  $found = 1 if ($membre =~ /(\w*=|^)$valueToSearch(,|$)/);
}

$ldap->unbind();

die("$valueToSearch non trouvé dans $dnToSearchIn.\n") if (!defined($found));

exit 0;

sub typeOfGroup {
  $dnToSearchIn = shift @_;
  $ldap = Net::LDAP->new(
			 "$slapdIp",
			 port    => "$slapdPort",
			 debug   => "$slapdDebug",
			 timeout => "$slapdTimeout",
			 version => "$slapdVersion"
			);
  $ldap->bind(); # Anonymous BIND
  
  $res = $ldap->search(
		       base     => "$dnToSearchIn",
		       scope    => 'base',
		       attrs    => ['objectClass'],
		       filter   => 'objectClass=*'
		      );
  
  foreach $entry ($res->entries) {
    @classes  = $entry->get_value('objectClass');
  }
  foreach $classe (@classes) {
    $type = $classe if ($classe =~ /group/i);
  }
  die ("Erreur de recherche sur $dnToSearchIn.\n") if (!defined($type));
  
  if ($type =~ /posixGroup/i) {
    $attribute = 'memberUid';
  } elsif ($type =~ /groupOfNames/i) {
    $attribute = 'member'
  } else {
    die ("Erreur de recherche sur $dnToSearchIn.\n");
  }

  return $attribute;
}
