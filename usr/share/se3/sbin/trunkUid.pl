#!/usr/bin/perl


## $Id$ ##

use Net::LDAP;

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
		     base   => "ou=People,$baseDn",
		     scope  => 'one',
		     filter => 'uid=*'
		    );

warn $res->error if $res->code != LDAP_SUCCESS;

foreach $entry ($res->entries) {
  
  $dn = $entry->dn;
  $uid = $entry->get_value('uid');

  if (length($uid) <= 20) {
    next;
  } else {
    $trunkedUid = trunk($uid);
    
    $res = $ldap->modify(
			$dn,
			replace => {
				    mail          => "$trunkedUid@$domain",
				    homeDirectory => "/home/$trunkedUid"
				   }
		       );
    
    warn $res->error if $res->code != LDAP_SUCCESS;
    
    $res = $ldap->moddn(
			$dn,
			newrdn      => "uid=$trunkedUid",
			deleteoldrdn => '1'
		       );
    
    warn $res->error if $res->code != LDAP_SUCCESS;
  }
  
}

$res = $ldap->search(
		     base   => "ou=Groups,$baseDn",
		     scope  => 'one',
		     filter => 'owner=*'
		    );

warn $res->error if $res->code != LDAP_SUCCESS;

foreach $entry ($res->entries) {
  
  $dn = $entry->dn;
  
  @owners= ();
  @trunkedOwners= ();
  
  @owners = $entry->get_value('owner');
  
  foreach $owner (@owners) {
    $res = $ldap->modify(
  		         $dn,
		         delete => { owner => $owner }
		        );
    warn $res->error if $res->code != LDAP_SUCCESS;
    $uid = extractUid($owner);
    if (length($uid) <= 20) {
      push @trunkedOwners, $owner;
    } else {
      $trunkedUid = trunk($uid);
      $trunkedOwner = concatUid($trunkedUid);
      push @trunkedOwners, $trunkedOwner;
    }
  }
  
  foreach $owner (@trunkedOwners) {
    $res = $ldap->modify(
  		         $dn,
		         add => { owner => $owner }
		        );
  
    warn $res->error if $res->code != LDAP_SUCCESS;
  }
  
}

$res = $ldap->search(
		     base   => "ou=Groups,$baseDn",
		     scope  => 'one',
		     filter => 'member=*'
		    );

warn $res->error if $res->code != LDAP_SUCCESS;

foreach $entry ($res->entries) {
  
  $dn = $entry->dn;
  
  @members = ();
  @trunkedMembers = ();
  
  @members = $entry->get_value('member');
  
  foreach $member (@members) {
    $res = $ldap->modify(
  		         $dn,
		         delete => { member => $member }
		        );
    warn $res->error if $res->code != LDAP_SUCCESS;
    $uid = extractUid($member);
    if (length($uid) <= 20) {
      push @trunkedMembers, $member;
    } else {
      $trunkedUid = trunk($uid);
      $trunkedMember = concatUid($trunkedUid);
      push @trunkedMembers, $trunkedMember;
    }
  }
  
  foreach $member (@trunkedMembers) {
    $res = $ldap->modify(
  		         $dn,
		         add => { member => $member }
		        );
  
    warn $res->error if $res->code != LDAP_SUCCESS;
  }
  
}

$res = $ldap->search(
		     base   => "ou=Groups,$baseDn",
		     scope  => 'one',
		     filter => 'memberUid=*'
		    );

warn $res->error if $res->code != LDAP_SUCCESS;

foreach $entry ($res->entries) {
  
  $dn = $entry->dn;

  @memberUids = ();
  @trunkedMemberUids = ();
  
  @memberUids = $entry->get_value('memberUid');
  
  foreach $memberUid (@memberUids) {
    $res = $ldap->modify(
		         $dn,
		         delete=> { memberUid => $memberUid }
		        );
  
    warn $res->error if $res->code != LDAP_SUCCESS;
    if (length($memberUid) <= 20) {
      push @trunkedMemberUids, $memberUid;
    } else {
      $trunkedUid = trunk($memberUid);
      push @trunkedMemberUids, $trunkedUid;
    }
  }
  
  foreach $memberUid (@trunkedMemberUids) {
    $res = $ldap->modify(
		         $dn,
		         add => { memberUid => $memberUid }
		        );
  
    warn $res->error if $res->code != LDAP_SUCCESS;
  }
  
}

sub trunk {
  
  my $uid = shift;
  if (length($uid) <= 20) {
    $trunkedUid = $uid;
  } else {
    $uid =~ /([\w\d-\._]{20})/;
    $trunkedUid = $1;
    #print "Truncating $uid to $trunkedUid !\n";
  }
  return $trunkedUid;
  
}

sub extractUid {
  my $member = shift;
  $member =~ /^uid=([\w\d-\._]+),/;
  my $uid = $1;
  return $uid;
}

sub concatUid {
  my $uid = shift;
  my $member = "uid=$uid,ou=People,$baseDn";
  return $member;
}
