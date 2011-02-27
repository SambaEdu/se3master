#!/usr/bin/perl

# Version du 16/09/06
#   - Recherche des comptes pr�sents dans ou=People et membre d'aucun groupe.
#   - d�placement vers ou=Trash

use Se;
use Crypt::SmbHash;

# Connexion LDAP
# ==============
$lcs_ldap = Net::LDAP->new("$slapdIp");
$lcs_ldap->bind(
		dn       => $adminDn,
		password => $adminPw,
		version  => '3'
	       );

# Cr�ation de la poubelle le cas �ch�ant
# ======================================
$trashOuName = 'Trash';
$trashRdn    = "ou=$trashOuName";
$trashDn     = "$trashRdn,$baseDn";
$trashSearch = $lcs_ldap->search(base     => "$baseDn",
				 scope    => 'one',
				 filter   => "$trashRdn");
warn $trashSearch->error if $trashSearch->code;
unless (($trashSearch->entries)[0]) {
  # Cr�ation
  # --------
  @trashAttributes = ( 'objectClass', 'organizationalUnit',
		       'ou',          "$trashOuName" );
  $creationTrash = $lcs_ldap->add( "$trashDn",
				   attrs => \@trashAttributes );
  warn $creationTrash->error if $creationTrash->code;
}

# Recherche des utilisateurs concern�s et Action
# ==============================================
#
# Recherche de tous les utilisateurs
# ----------------------------------
$peoples = $lcs_ldap->search(base     => "$peopleDn",
			     scope    => 'one',
			     filter   => 'uid=*');
foreach $people ($peoples->entries) {
  $dn  = $people->dn;
  $uid = $people->get_value('uid');
  next if ($uid eq 'admin' or $uid eq 'webmaster.etab' or $uid eq 'wetab' or $uid eq 'etabw' or $uid eq 'ldapadm');
  # V�rification de l'appartenance � des groupes
  # --------------------------------------------
  $memberOfAGroupOfNames = $lcs_ldap->search(base     => "$groupsDn",
					     scope    => 'one',
					     filter   => "member=$dn");
  warn $memberOfAGroupOfNames->error if $memberOfAGroupOfNames->code;
  next if ($memberOfAGroupOfNames->entries)[0];
  $memberOfAPosixGroup   = $lcs_ldap->search(base     => "$groupsDn",
					     scope    => 'one',
					     filter   => "(&(!(cn=overfill))(memberUid=$uid))");
  warn $memberOfAPosixGroup->error if $memberOfAPosixGroup->code;
  next if ($memberOfAPosixGroup->entries)[0];
  # D�sactivation du compte SAMBA et d�placement le cas �ch�ant
  # -----------------------------------------------------------
  $sambaDesactiv = $lcs_ldap->modify(
    $dn,
    replace => {
      sambaacctFlags => '[UD         ]',

    }
  );
  warn $sambaDesactiv->error if $sambaDesactiv->code;
  $trashSearch = $lcs_ldap->search(base     => "$trashDn",
				   scope    => 'one',
				   filter   => "uid=$uid");
  if (($trashSearch->entries)[0]) {
    $emptyTrash = $lcs_ldap->delete(($trashSearch->entries)[0]);
    warn $emptyTrash->error if $emptyTrash->code;
  }
  $move2trash = $lcs_ldap->moddn( $dn,
				  newrdn      => "uid=$uid",
				  newsuperior => "$trashDn");
  warn $move2trash->error if $move2trash->code;
}

$lcs_ldap->unbind();

sub emptyTrash {
  # Vidage de la poubelle
  # ---------------------
  $trashSearch = $lcs_ldap->search(base     => "$trashDn",
				   scope    => 'one',
				   filter   => "uid=*");
  foreach $entry ($trashSearch->entries) {
    $emptyTrash = $lcs_ldap->delete($entry);
    warn $emptyTrash->error if $emptyTrash->code;
  }
}
