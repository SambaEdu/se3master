#!/usr/bin/perl

## $Id$ ##


use Net::LDAP;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 0);

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

$res = $ldap->delete($dn);

die("Erreur LDAP : " . $res->error) if ($res->code ne 0);

$ldap = Net::LDAP->new(
		       "$slapdIp",
		       port    => "$slapdPort",
		       debug   => "$slapdDebug",
		       timeout => "$slapdTimeout",
		       version => "$slapdVersion"
		      );
$ldap->bind(); # Anonymous BIND
$res = $ldap->search(
		     base     => "$baseDn",
		     scope    => 'sub',
		     attrs    => ['dn'],
		     filter   => "(&(memberUid=$rdnValue)(objectClass=posixGroup))"
		    );

# DEBUG INFO
#############
# print "\nFiltre : (&(memberUid=$rdnValue)(objectClass=posixGroup))\n";

die("Erreur LDAP : " . $res->error) if ($res->code ne 0);

foreach $entry ($res->entries) {
  $dnWhereToDel = $entry->dn;
  # DEBUG INFO
  #############
  # print "dn : $dnWhereToDel\n";
  # FIN DEBUG
  ############
  # print "/usr/share/se3/sbin/groupDelEntry.pl $dn $dnWhereToDel\n";

  system("/usr/share/se3/sbin/groupDelEntry.pl \"$dn\" \"$dnWhereToDel\"");
}

$res2 = $ldap->search(
		     base     => "$baseDn",
		     scope    => 'sub',
		     attrs    => ['dn'],
		     filter   => "(&(member=$dn)(objectClass=groupOfNames))"
		    );

# DEBUG INFO
#############
# print "\n\nFiltre : (&(member=$dn)(objectClass=groupOfNames))\n";

die("Erreur LDAP : " . $res2->error) if ($res2->code ne 0);

foreach $entry2 ($res2->entries) {
  $dnWhereToDel2 = $entry2->dn;
  # DEBUG INFO
  #############
  # print "dn : $dnWhereToDel2\n";
  # foreach my $attr2 ($entry2->attributes) {
  #   foreach my $value2 ($entry2->get_value($attr2)) {
  #     print "$attr2 : $value2\n";
  #   }
  # }
  # print "/usr/share/se3/sbin/groupDelEntry.pl $dn $dnWhereToDel2\n";

  system("/usr/share/se3/sbin/groupDelEntry.pl $dn $dnWhereToDel2");
}

$res3 = $ldap->search(
		      base     => "$baseDn",
		      scope    => 'sub',
		      attrs    => ['dn'],
		      filter   => "owner=$dn"
		     );

# DEBUG INFO
#############
# print "\n\nFiltre : owner=$dn\n";

die("Erreur LDAP : " . $res3->error) if ($res3->code ne 0);

$ldap->bind(
	    $adminDn,
	    password => $adminPw
	   );

foreach $entry3 ($res3->entries) {
  $dnWhereToDel3 = $entry3->dn;
  # DEBUG INFO
  #############
  # print "dn : $dnWhereToDel3\n";
  # foreach my $attr3 ($entry3->attributes) {
  #   foreach my $value3 ($entry3->get_value($attr3)) {
  #     print "$attr3 : $value3\n";
  #   }
  # }
  # print "/usr/share/se3/sbin/groupDelEntry.pl $dn $dnWhereToDel3\n";


  $res = $ldap->modify(
		       $dnWhereToDel3,
		       delete => { owner => $dn }
		      );

  die("Erreur LDAP : " . $res->error . ".\n") if ($res->code ne 0);
  
}

$ldap->unbind();

exit 0;
