#!/usr/bin/perl
die("Erreur d'argument") if ($#ARGV != 1);

use Net::LDAP;

require '/etc/SeConfig.ph';

($uid, $action) = @ARGV;

$dn = 'uid=' . $uid . ',' . $peopleDn;
$uid =~ /^(\w*)\.(\w*)$/;

die if $uid eq '';
# on  ecrit dans la base ldap l'action a effectuer à  la prochaine connexion..

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
$res = $ldap->modify(
                     $dn,
                     replace => {
                                 l   =>  "$action"
                                }
                    );

$res->code && die("Erreur LDAP : " . $res->code . " => " . $res->error . ".\n");

exit O;
