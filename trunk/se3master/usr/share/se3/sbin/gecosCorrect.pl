#!/usr/bin/perl

## $Id$ ##


use Net::LDAP;
use Encode::compat;
use Encode qw(encode decode);
#use Text::Unaccent;

require '/etc/SeConfig.ph';

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

$res = $ldap->search(
		     base   => "$peopleRdn,$baseDn",
		     scope  => 'one',
		     filter => 'uid=*'
		    );
warn $res->error if $res->code != LDAP_SUCCESS;

foreach $entry ($res->entries) {
  
  $dn = $entry->dn;
  #$cn    = unac_string('utf8', ($entry->get_value('cn')));
  $cn1    = $entry->get_value('cn');
  $cn=unac(`echo "$cn1" | iconv -f utf8 -t iso8859-1`);
  chomp ($cn);
  #$gecos = unac_string('utf8', ($entry->get_value('gecos')));
  $gecos1 = $entry->get_value('gecos');
  $gecos=unac(`echo "$gecos1" | iconv -f utf8 -t iso8859-1`);
  chomp($gecos);
  $newGecos = "$cn,$gecos";

  
  unless ($gecos =~ /^$cn/) {
    $res = $ldap->modify(
			 $dn,
			 replace => {
				     gecos => "$newGecos",
				    }
			);
  }
  warn $res->error if $res->code != LDAP_SUCCESS;
}

sub unac($uid)
{
my ( $uid ) = @_;
# Nettoyage des caract\xe8res accentu\xe9s de l'uid
  $uid =~ tr/\x80-\xbf//;
  $uid =~ tr/\xc0-\xc5/AAAAAA/;
  $uid =~ tr/\xc6//;
  $uid =~ tr/\xc7-\xcf/CEEEEIIII/;
  $uid =~ tr/\xd0//;
  $uid =~ tr/\xd1-\xd6/NOOOOO/;
  $uid =~ tr/\xd7//;
  $uid =~ tr/\xd8-\xdc/OUUUU/;
  $uid =~ tr/\xdd-\xdf//;
  $uid =~ tr/\xe0-\xe5/aaaaaa/;
  $uid =~ tr/\xe6//;
  $uid =~ tr/\xe7-\xef/ceeeeiiii/;
  $uid =~ tr/\xf0//;
  $uid =~ tr/\xf1-\xf6/nooooo/;
  $uid =~ tr/\xf7//;
  $uid =~ tr/\xf8-\xfc/ouuuu/;
  $uid =~ tr/\xfd-\xff//;
  $uid =~ tr/\x0a//;
  $uid =~ tr/\x0d//;
  return $uid;
}
