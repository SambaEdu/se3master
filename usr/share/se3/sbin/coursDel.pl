#!/usr/bin/perl

## $Id$ ##


use Net::LDAP;

require '/etc/SeConfig.ph';

$dn = shift @ARGV;
$rdnValue = (split /=/, (split /,/, $dn)[0])[1];

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
                    base     => "ou=groups,$baseDn",
                    scope    => 'sub',
                    attrs    => ['dn'],
                    filter   => "(&(cn=Cours*))"
                    );

foreach $entry ($res->entries) {
    $del = $ldap->delete($entry->dn);
}

exit O;