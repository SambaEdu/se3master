#!/usr/bin/perl


## $Id$ ##


use Net::LDAP;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 1);
($uid, $password) = @ARGV;
$dn = "uid=$uid,$peopleDn";
# Generation du mot de passe crypte
$crypt = `/usr/sbin/slappasswd -h {MD5} -s '$password'`;
($lmPassword, $ntPassword) = mkNtPasses($password);

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
		     base   => "$baseDn",
		     scope  => 'sub',
		     filter => "(&(uid=$uid)(objectClass=sambaSamAccount))"
		    );
warn $res->error if $res->code;
#print ($res->entries)[0];
if (($res->entries)[0]) {
$res = $ldap->modify(
		     $dn,
		     replace => {
				 userPassword => $crypt,
				 sambaNTPassword   => $ntPassword,
				 sambaLMPassword   => $lmPassword,
				 shadowLastChange  => time 				}
		    );
} else {
$res = $ldap->modify(
		     $dn,
		     replace => {
				 userPassword => $crypt,
				 ntPassword   => $ntPassword,
				 lmPassword   => $lmPassword,
				 shadowLastChange  => time
				}
		    );
}

$res->code && die("Erreur LDAP : " . $res->code . " => " . $res->error . ".\n");
if ($uid eq "admin") {
system("htpasswd -bm /var/www/se3/setup/.htpasswd admin $password");

}

exit 0;

sub mkNtPasses {

  my ($password) = shift @_;

  $sambaPasses = `/usr/share/se3/sbin/mkntpwd '$password'`;
  $sambaPasses =~ /(.*):(.*)/;
  $lmPassword = $1;
  $ntPassword = $2;

  @data = ($lmPassword, $ntPassword);

  return @data;

}

