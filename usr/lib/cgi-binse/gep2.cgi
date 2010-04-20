#!/usr/bin/perl -w

use Se;

# Algorythme global
# =================
if (!defined ($pid = fork)) {
  die "Impossible de « forker » : $!\n";
} elsif (! $pid) {
  &traitement_fond;
  exit(0);
} else {
  &traitement;
  exit(0);
}

sub traitement {

  open ENCOURS, '>/tmp/EnCours.temp';
  print ENCOURS '1';
  close ENCOURS;

  # Initialisation des variables
  # ----------------------------
  # Uid de départ
  $uidNumber = getFirstFreeUid(1001);
  # Gid de départ
  $gidNumber = getFirstFreeGid(2000);

  &entete(STDOUT);

    if (isAdmin() ne 'Y') {
      print
        "<p><strong>Erreur :</strong> vous n'avez pas les droits nécessaires",
        " pour effectuer l'importation !</p>";
      &pdp(STDOUT);
      exit(0);
    }

  # Récupération, Capitalisation et modification du préfixe
  $prefix = param('prefix') or $prefix = '';
  $prefix =~ tr/a-z/A-Z/;
  $prefix .= '_' unless ($prefix eq '');
  # Récupération de la valeur du flag ANNUELLE
  $annuelle = param('annuelle');

  open INT, '>/tmp/gepInterface.temp' or warn 'Impossible d\'écrire dans /tmp !';
  print INT "$prefix:$annuelle";
  close INT;

  # Écriture des fichiers dans /tmp
  # ===============================
  foreach $fichier (keys(%formatTxt)) {
    # Vérification du passage d'un fichier
    # ------------------------------------
    $nom = param("$fichier");
    if ($nom eq '') {
      print STDOUT "Pas de fichier fourni pour ",
	"<span class=\"filename\">$fichier</span> !<br>\n" if ($debug > 1);
      $atLeastOneNotOk = 1;
    } else {
      $fileHandle = upload("$fichier");
      open ( FICHTMP ,">/tmp/ApacheCgi.temp");
      while (<$fileHandle>) {
	print FICHTMP;
      }
      close FICHTMP;
      # Appel de la fonction d'ecriture du fichier vérifié et nettoyé (utf8)
      # --------------------------------------------------------------------
      $res = txtVerif($fichier);
      $atLeastOneNotOk = 1 if $res;
      print "Format du fichier <span class=\"filename\">$fichier</span> erroné<br>\n" if $res;
      unless ($res) {
 	$ok{$fichier} = 1;
 	$atLeastOneOk = 1;
      }
    }
  }

  # Rapport concernant la validité des fichiers
  # ===========================================
  unless ($atLeastOneOk) {
    print "<strong>Aucun fichier valide n'a été fourni !</strong>\n";
    pdp(STDOUT);
    exit 0;
  }

  if ($debug > 1 && $atLeastOneOk) {
    print
      "<h2>Fichiers fournis et valides</h2>\n",
      "<ul style=\"color: green\">\n";
    foreach $fichier (keys(%formatTxt)) {
      print "<li><span class=\"filename\" style=\"color: #404044\">$fichier</span></li>\n" if $ok{$fichier};
    }
    print "</ul>\n";
  }
  if ($debug > 1 && $atLeastOneNotOk) {
    print
      "<h2>Fichiers non fournis ou invalides</h2>\n",
      "<ul style=\"color: red\">\n";
    foreach $fichier (keys(%formatTxt)) {
      print
	"<li><span class=\"filename\" style=\"color: #404044\">",
	"$fichier</span></li>\n" unless $ok{$fichier};
    }
    print "</ul>\n";
  }

  # Suppression des pages html résultats antérieures
  # ------------------------------------------------
  unlink <$documentRoot/$webDir/result*>
    or warn "Le serveur Web n'a pas les droits suffisants",
      "sur le répertoire '$documentRoot/$webDir/result*'.";

  # Écriture du fichier html provisoire de résultat final
  # -----------------------------------------------------
  open (RES, ">$documentRoot/$webDir/result.$pid.html")
    or die "Le serveur Web n'a pas les droits suffisants sur le répertoire '$documentRoot/$webDir/result*'.";
  &entete(RES);
  print RES
    p('<span style="text-align: center; font-weight: bold">Traitement en cours...</span>');
  &pdp(RES);
  close RES;

  print "<h2>Création des entrées <span class=\"abbrev\">ldap</span> suivantes</h2>\n" if $debug;
  if ($ok{'f_ele'} or $ok{'f_wind'}) {
    print
      "<strong>Comptes utilisateur :</strong>\n",
      "<ul style=\"color: green\">\n" if $debug;
    if ($ok{'f_ele'}) {
      print "<li><span class=\"filename\" style=\"color: #404044\">Élèves</span></li>\n" if $debug;
      $createEleves = 1;
    }
    if ($ok{'f_wind'}) {
      print "<li><span class=\"filename\" style=\"color: #404044\">Profs</span></li>\n" if $debug;
      $createProfs = 1;
    }
    print "</ul>\n";
  }
  if ($ok{'f_div'} or $ok{'f_ele'} or $ok{'f_men'}) {
    print
      "<strong>Groupes :</strong>\n",
      "<ul style=\"color: green\">\n" if $debug;
    if ($ok{'f_div'} or $ok{'f_ele'}) {
      print "<li><span class=\"filename\" style=\"color: #404044\">Classes</span></li>\n" if $debug;
      print "<li><span class=\"filename\" style=\"color: #404044\">Équipes</span></li>\n" if $debug;
      $createClasses = 1; $createEquipes = 1;
    }
    if ($ok{'f_men'}) {
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Cours</span></li>\n",
	"<li><span class=\"filename\" style=\"color: #404044\">Matières</span></li>\n" if $debug;
      $createCours = 1; $createMatieres = 1;
    }
    print "</ul>\n";
  }

  if ($atLeastOneNotOk) {
    print "<h2>Problèmes liés à l'absence ou à l'invalidité de certains fichiers</h2>\n" if $debug;
    if (! $ok{'f_ele'} or ! $ok{'f_wind'}) {
      print
	"<strong>Pas de création des comptes utilisateur :</strong>\n",
	"<ul style=\"color: red\">\n" if $debug;
      print "<li><span class=\"filename\" style=\"color: #404044\">Élèves</span></li>\n"
	if (! $ok{'f_ele'} and  $debug);
      print "<li><span class=\"filename\" style=\"color: #404044\">Profs</span></li>\n"
	if (! $ok{'f_wind'} and $debug);
      print "</ul>\n";
    }
    if (! $ok{'f_div'} or ! $ok{'f_ele'} or ! $ok{'f_men'}) {
      print
	"<strong>Pas de création des groupes :</strong>\n",
	"<ul style=\"color: red\">\n" if $debug;
      print "<li><span class=\"filename\" style=\"color: #404044\">Classes</span></li>\n",
	"<li><span class=\"filename\" style=\"color: #404044\">Équipes</span></li>\n"
	  if (! $ok{'f_div'} and ! $ok{'f_ele'} and $debug);
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Cours</span></li>\n",
	"<li><span class=\"filename\" style=\"color: #404044\">Matières</span></li>\n"
	  if (! $ok{'f_men'} and $debug);
      print "</ul>\n";
    }
    if ((! $ok{'f_div'} and ($createClasses or $createEquipes)))
#	or (! $ok{'f_tmt'} and $createMatieres)
#	or (! $ok{'f_gro'} and $createCours))
      {
	print
	  "<strong>Pas de description disponible pour les groupes ",
	  "(utilisation du mnémonique) :</strong>\n",
	  "<ul style=\"color: red\">\n" if $debug;
	if (! $ok{'f_div'}) {
	  print "<li><span class=\"filename\" style=\"color: #404044\">Classes</span></li>\n"
	    if ($createClasses and $debug);
	  print "<li><span class=\"filename\" style=\"color: #404044\">Équipes</span></li>\n"
	    if ($createEquipes and $debug);
	}
	print
	  "<li><span class=\"filename\" style=\"color: #404044\">Matières</span></li>\n",
	  " (limitation due à l'importation texte)."
	    if ($createMatieres and $debug);
	print
	  "<li><span class=\"filename\" style=\"color: #404044\">Cours</span></li>\n",
	  " (limitation due à l'importation texte)."
	    if ($createCours and $debug);
	print "</ul>\n";
      }
    if (($createCours and ! $ok{'f_ele'}) or ($createClasses and ! $ok{'f_ele'})) {
      print
	"<strong>Pas de membres pour les groupes :</strong>\n",
	"<ul style=\"color: red\">\n" if $debug;
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Cours</span></li>\n"
	  if ($createCours and $ok{'f_ele'} and $debug);
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Équipes</span></li>\n"
	  if ($createEquipes and ! $ok{'f_wind'} and $debug);
      print "</ul>\n";
    }
  }

  print
    "<div style=\"font-size: large; text-align: left; padding: 1em;",
    " background-color: lightgrey\">Le traitement pouvant être particulièrement long,",
    " il va maintenant continuer en tâche de fond.<br>\n",
    'Le rapport final d\'importation sera accessible à l\'adresse :<br>',
    "<div style=\"text-align: center; font-family: monospace\">",
    "<a href=\"$hostname/$webDir/result.$pid.html\">",
    "$hostname/$webDir/result.$pid.html</a></div>\n",
    "Une fois le traitement terminé, utilisez l'annuaire pour vérifier la validité des résultats.",
    "</div>\n";

  &pdp(STDOUT);

  unlink('/tmp/EnCours.temp');

}

sub traitement_fond {

  # Attente de fin du traitement préparatoire
  sleep(3);
  $inc=0;
  while (1) {
    sleep 1;
    $inc++;
    if ($inc == 30) {
      # Fermeture des entrées/sorties standard
      close(STDIN); close(STDOUT);
      open RES, ">$documentRoot/$webDir/result.$$.html";
      &entete(RES);
      print RES
	"<strong>Le traitement préparatoire des fichiers texte semble avoir été interrompu.<br>",
	"Le traitement des fichiers prêts va tout de même se poursuivre.<br>",
	"ATTENTION : votre importation risque de ne pas être complète...<br></strong>";
      last;
    }
    if (! -f '/tmp/EnCours.temp') {
      # Fermeture des entrées/sorties standard
      close(STDIN); close(STDOUT);
      open RES, ">$documentRoot/$webDir/result.$$.html";
      &entete(RES);
      print RES
	"<strong>Le traitement préparatoire s'est terminé avec succès.</strong><br>";
      last;
    }
  }

  open INT, '</tmp/gepInterface.temp';
  $ligne = <INT>;
  ($prefix, $annuelle) = split /:/, $ligne;
  close INT;
  $prefix = '' unless $prefix;

  annuelle() if ($annuelle);

  # Création des entrées
  # ====================

  # Initialisation des variables
  # ----------------------------
  # Uid de départ
  $uidNumber = getFirstFreeUid(1001);
  # Gid de départ
  $gidNumber = getFirstFreeGid(2000);
  # Gid des utilisateurs LCS/SE3
  $gid = $defaultgid;
  unless
    (-f '/tmp/f_ele.temp'
     or -f '/tmp/f_wind.temp'
     or -f '/tmp/f_men.temp'
     or -f '/tmp/f_div.temp') {
      exit 0;
    }
  # Connexion LDAP
  # ==============
  $lcs_ldap = Net::LDAP->new("$slapdIp");
  $lcs_ldap->bind(
  		  dn       => $adminDn,
  		  password => $adminPw,
  		  version  => '3'
  		 );

  # Profs
  # -----
  if (-f '/tmp/f_wind.temp') {
    print RES "<h2>Création des comptes 'Profs'</h2>\n<table>\n";
    open PROFS, '</tmp/f_wind.temp';
    while (<PROFS>) {
      chomp($ligne = $_);
      ($numind, $nom, $prenom, $date, $sexe)  = (split /\|/, $ligne);
      $uniqueNumber = $numind;
      $res = processGepUser( $uniqueNumber, $nom, $prenom, $date, $sexe, 'undef' );
      print RES $res if ($res =~ /Cr/ or ($debug > 1 and $res !~ /Cr/));
      unless ($res =~ /conflits/) {
	# Ajoût de l'uid au groupe Profs
	$res = $lcs_ldap->search(base     => "$profsDn",
				 scope    => 'base',
				 filter   => "memberUid=$uid");
	unless (($res->entries)[0]) {
	  $res = $lcs_ldap->modify( $profsDn,
				    add => { 'memberUid' => $uid } );
	warn $res->error if $res->code;
	}
      }
    }
    print RES "</table>\n";
    close PROFS;
  }

  # Classes
  # -------
  if (-f '/tmp/f_div.temp') {
    print RES "<h2>Création des groupes 'Classe' et 'Equipe'</h2>\n<table>\n";
    open DIV, '</tmp/f_div.temp';
    while (<DIV>) {
      chomp($ligne = $_);
      ($divcod, $divlib, $profUniqueNumber) = (split/\|/, $ligne);
      $divcod =~ s/\s/_/;
      $divlib = normalize($divlib,4);
      $libelle{$divcod} = $divlib;
      $res = $lcs_ldap->search(base     => "$peopleDn",
			       scope    => 'one',
			       filter   => "employeeNumber=$profUniqueNumber");
      $profPrincUid = '';
      if (($res->entries)[0]) {
	$profPrincUid = (($res->entries)[0])->get_value('uid');
      }
      # Recherche de l'existence de la classe
      $res = $lcs_ldap->search(base     => "cn=Classe_$prefix$divcod,$groupsDn",
			       scope    => 'base',
			       filter   => "cn=*");
      if (($res->entries)[0]) {
	if (! (($res->entries)[0])->get_value('description') and $divlib) {
	  print RES "<tr><td><strong><tt>$divcod</tt> :</strong></td><td>Mise à jour de la description du groupe 'Classe' : <em>$divlib</em></td></tr>\n" if $debug > 1;
	  $res2 = $lcs_ldap->modify( "cn=Classe_$prefix$divcod,$groupsDn",
				     add => { description => $divlib } );
	  warn $res2->error if $res2->code;
	}
      } else {
	$gidNumber = getFirstFreeGid($gidNumber);
	@classEntry = (
		       'cn',          "Classe_$prefix$divcod",
		       'objectClass', 'top',
		       'objectClass', 'posixGroup',
		       'gidNumber',   $gidNumber,
		      );
	push @classEntry, ('description', $divlib) if $divlib;
	$res = $lcs_ldap->add( "cn=Classe_$prefix$divcod,$groupsDn",
			       attrs => \@classEntry );
	warn $res->error if $res->code;
	print RES "<tr><td><strong><tt>$divcod</tt> :</strong></td><td>Création du groupe 'Classe' <em>$divlib</em></td></tr>\n" if $debug;
      }
      # Recherche de l'existence de l'équipe
      $res = $lcs_ldap->search(base     => "cn=Equipe_$prefix$divcod,$groupsDn",
			       scope    => 'base',
			       filter   => "cn=*");
      if (($res->entries)[0]) {
	if (! (($res->entries)[0])->get_value('description') and $divlib) {
	  print RES "<tr><td><strong><tt>$divcod</tt> :</strong></td><td>Mise à jour de la description du groupe 'Equipe' : <em>$divlib</em></td></tr>\n" if $debug > 1;
	  $res2 = $lcs_ldap->modify( "cn=Equipe_$prefix$divcod,$groupsDn",
				     add => { description => $divlib } );
	  warn $res2->error if $res2->code;
	}
	if (! (($res->entries)[0])->get_value('owner') and $profPrincUid) {
	  print RES "<tr><td><strong><tt>$divcod</tt> :</strong></td><td>Mise à jour du propriétaire du groupe 'Equipe' : <em>$divlib</em></td></tr>\n" if $debug > 1;
	  $res2 = $lcs_ldap->modify( "cn=Equipe_$prefix$divcod,$groupsDn",
				     add => { owner => "uid=$profPrincUid,$peopleDn" } );
	  warn $res2->error if $res2->code;
	}
	next;
      } else {
	$gidNumber = getFirstFreeGid($gidNumber);
	@equipeEntry = (
		       'cn',          "Equipe_$prefix$divcod",
		       'objectClass', 'top',
		       'objectClass', 'posixGroup',
		       'gidNumber',   $gidNumber,
		      );

	push @equipeEntry, ('description', $divlib) if $divlib;
	#push @equipeEntry, ('owner', "uid=$profPrincUid,$peopleDn") if $profPrincUid;
	$res = $lcs_ldap->add( "cn=Equipe_$prefix$divcod,$groupsDn",
			       attrs => \@equipeEntry );
	warn $res->error if $res->code;
	print RES "<tr><td><strong><tt>$divcod</tt> :</strong></td><td>Création du groupe 'Equipe' <em>$divlib</em></td></tr>\n" if $debug > 1;
      }
    }
    print RES "</table>\n";
  }

  # Eleves
  # -----
  if (-f '/tmp/f_ele.temp') {
    print RES "<h2>Création des comptes 'Eleves'";
    print RES " <span style=\"font-size: small\">(et des groupes 'Classes' et 'Equipes' associés)</span>"
      unless (-f '/tmp/f_div.temp');
    print RES "</h2>\n<table>\n";
    open ELEVES, '</tmp/f_ele.temp';
    while (<ELEVES>) {
      chomp($ligne = $_);
      ($uniqueNumber, $nom, $prenom, $date, $sexe, $divcod)  = (split /\|/, $ligne);
      $divcod =~ s/\s/_/g;
      next if $divcod eq '';
      unless (-f '/tmp/f_div.temp') {
	# Création des classes
	$res = $lcs_ldap->search(base     => "cn=Classe_$prefix$divcod,$groupsDn",
				 scope    => 'base',
				 filter   => "cn=*");
	unless (($res->entries)[0]) {
	  $gidNumber = getFirstFreeGid($gidNumber);
	  @classEntry = (
			 'cn',          "Classe_$prefix$divcod",
			 'objectClass', 'top',
			 'objectClass', 'posixGroup',
			 'gidNumber',   $gidNumber,
			);
	  $res = $lcs_ldap->add( "cn=Classe_$prefix$divcod,$groupsDn",
				 attrs => \@classEntry );
	  warn $res->error if $res->code;
	}
	# Création des Équipes
	$res = $lcs_ldap->search(base     => "cn=Equipe_$prefix$divcod,$groupsDn",
				 scope    => 'base',
				 filter   => "cn=*");
	unless (($res->entries)[0]) {
	  @equipeEntry = (
			 'cn',          "Equipe_$prefix$divcod",
			 'objectClass', 'top',
			 'objectClass', 'PosixGroup', );
#			 'objectClass', 'groupOfNames', );
#leb			 'member',      '' );
	  $res = $lcs_ldap->add( "cn=Equipe_$prefix$divcod,$groupsDn",
				 attrs => \@equipeEntry );
	  warn $res->error if $res->code;
	}
      }
       $res = processGepUser($uniqueNumber, $nom, $prenom, $date, $sexe, 'undef');
      print RES $res if ($res =~ /Cr/ or ($debug > 1 and $res !~ /Cr/));
      unless ($res =~ /conflits/) {
	# Ajoût de l'uid au groupe Eleves
	$res = $lcs_ldap->search(base     => "$elevesDn",
				 scope    => 'base',
				 filter   => "memberUid=$uid");
	unless (($res->entries)[0]) {
	  $res = $lcs_ldap->modify(
				   $elevesDn,
				   add => { 'memberUid' => $uid }
				  );
	  warn $res->error if $res->code;
	}
	# Remplissage des classes
	$res = $lcs_ldap->search(base     => "cn=Classe_$prefix$divcod,$groupsDn",
				 scope    => 'base',
				 filter   => "memberUid=$uid");
	unless (($res->entries)[0]) {
	  $res = $lcs_ldap->modify(
				   "cn=Classe_$prefix$divcod,$groupsDn",
				   add => { 'memberUid' => $uid }
				  );
	  warn $res->error if $res->code;
	}
      }
    }
    print RES "</table>";
    close ELEVES;
  }

  # Analyse du fichier F_MEN
  # ------------------------
  if (-f '/tmp/f_men.temp') {
    open F_MEN, "</tmp/f_men.temp";
    print RES "<h2>Création des groupes 'Cours' et 'Matiere'</h2>\n<table>\n";
    while (<F_MEN>) {
      chomp ($ligne = $_);
      ($matimn, $elstco, $uniqueNumber) = (split/\|/, $ligne);
	  $matimn =~ s/\s/_/g;
	  $elstco =~ s/\s/_/g;
      # Génération du nom du cours (mnémoniqueMatière_codeGroupe)
      $cours = $matimn . '_' . $elstco;
      if ($uniqueNumber) {
	$res = $lcs_ldap->search(base     => "$peopleDn",
				 scope    => 'one',
				 filter   => "employeeNumber=$uniqueNumber");
	if (($res->entries)[0]) {
	  $profUid = (($res->entries)[0])->get_value('uid');
	}  else {
	  $profUid = '';
	}
      } else {
	$profUid = '';
      }
      $description = $matimn;
      if ($libelle{$elstco}) {
	$description .= " / " . $libelle{$elstco};
      } else {
	$description .= " / " . $elstco;
      }
      $res = $lcs_ldap->search(base     => "cn=Cours_$prefix$cours,$groupsDn",
			       scope    => 'base',
			       filter   => "objectClass=*");
      if (($res->entries)[0]) {
	# Mise à jour le cas échéant de la description
	if (($res->entries)[0]->get_value('description') =~ /$elstco/ and $description !~ /$elstco/) {
	  $res2 = $lcs_ldap->modify( "cn=Cours_$prefix$cours,$groupsDn",
				     replace => { description => $description } );
	  warn $res2->error if $res2->code;
	  print RES "<tr><td>Cours <span class=\"abbrev\">gep</span> <strong>$cours</strong> : </td><td>Mise à jour de la description du groupe 'Cours'</td></tr>\n" if $debug > 1;
	}
      } else {
	$gidNumber = getFirstFreeGid($gidNumber);
	@coursEntry = (
		       'cn',          "Cours_$prefix$cours",
		       'objectClass', 'top',
		       'objectClass', 'posixGroup',
		       'gidNumber',   $gidNumber,
		       'description', $description,
		      );
	push @coursEntry, ('memberUid', $profUid) if $profUid;
	$res = $lcs_ldap->add( "cn=Cours_$prefix$cours,$groupsDn",
			       attrs => \@coursEntry );
	warn $res->error if $res->code;
	print RES "<tr><td>Cours <strong>$cours</strong> : </td><td>Création du groupe 'Cours'</td></tr>\n" if $debug;
      }
      # Ajout du prof le cas échéant
      if ($profUid) {
	$res = $lcs_ldap->search(base     => "cn=Cours_$prefix$cours,$groupsDn",
				 scope    => 'base',
				 filter   => "memberUid=$profUid");
	if (! ($res->entries)[0]) {
	  $res = $lcs_ldap->modify( "cn=Cours_$prefix$cours,$groupsDn",
				    add => { memberUid => $profUid } );
	  warn $res->error if $res->code;
	}
      }
      # Ajout des autres membres du cours
#leb      $res = $lcs_ldap->search(base     => "cn=Classe_$prefix$elstco,$groupsDn",
#leb			       scope    => 'base',
#leb			       filter   => "cn=*");
#leb      if ($member{$elstco}) {
#leb	# Cas d'un groupe
#leb	chop($members = $member{$elstco});
#leb 	foreach $member (split / /, $members) {
#leb	  $res = $lcs_ldap->search(base     => "cn=Cours_$prefix$cours,$groupsDn",
#leb				   scope    => 'base',
#leb				   filter   => "memberUid=$member");
#leb	  if (! ($res->entries)[0]) {
#leb	    $res = $lcs_ldap->modify( "cn=Cours_$prefix$cours,$groupsDn",
#leb				      add => { memberUid => $member } );
#leb	    warn $res->error if $res->code;
#leb	  }
#leb	  print RES "<tr><td>Cours <strong>$cours</strong> : </td><td>Ajoût des élèves du groupe</td></tr>\n" if $debug > 1;
#leb	}
#leb      } else {
#leb	# cas d'une classe. C'EST TOUJOURS LE CAS D'UNE CLASSE !
 	$res = $lcs_ldap->search(base     => "cn=Classe_$prefix$elstco,$groupsDn",
 				 scope    => 'base',
 				 filter   => "objectClass=posixGroup"); #leb à la place de : filter   => "objectClass=*");
	if (($res->entries)[0]) {
	  @members = ($res->entries)[0]->get_value('memberUid');
	  foreach $member (@members) {
	    $res = $lcs_ldap->search(base     => "cn=Cours_$prefix$cours,$groupsDn",
				     scope    => 'base',
				     filter   => "memberUid=$member");
	    if (! ($res->entries)[0]) {
	      $res = $lcs_ldap->modify( "cn=Cours_$prefix$cours,$groupsDn",
					add => { memberUid => $member } );
	      warn $res->error if $res->code;
	    }
	  }
	}
#leb      }

      if ($profUid) {
	# Remplissage de l'équipe pédagogique de la classe
	$res = $lcs_ldap->search(base     => "cn=Equipe_$prefix$elstco,$groupsDn",
				 scope    => 'base',
				 filter   => "objectClass=posixGroup");
		#		 filter   => "objectClass=*");
	if (($res->entries)[0]) {
	  $res = $lcs_ldap->search(base     => "cn=Equipe_$prefix$elstco,$groupsDn",
				   scope    => 'base',
				   filter   => "memberUid=$profUid");
		#		   filter   => "member=uid=$profUid,$peopleDn");
	  unless (($res->entries)[0]) {
	    $res = $lcs_ldap->modify( "cn=Equipe_$prefix$elstco,$groupsDn",
				      add => { memberUid => $profUid } );
			#	      add => { member => "uid=$profUid,$peopleDn" } );
	    warn $res->error if $res->code;
	  }
	}
 	# Remplissage et/ou création du GroupOfNames Matiere
 	# Si la matière n'existe pas encore
 	$res = $lcs_ldap->search(base     => "cn=Matiere_$prefix$matimn,$groupsDn",
 				 scope    => 'base',
 				 filter   => "objectClass=posixGroup");
 		#		 filter   => "objectClass=*");
 	if (! ($res->entries)[0]) {
	  $gidNumber = getFirstFreeGid($gidNumber);
 	  @matiereEntry = (
 			   'cn',          "Matiere_$prefix$matimn",
 			   'objectClass', 'top',
		           'objectClass', 'posixGroup',
		           'gidNumber',   $gidNumber);
#	                   'objectClass', 'groupOfNames' );
#leb 			   'member',   '', );
 	  $res = $lcs_ldap->add( "cn=Matiere_$prefix$matimn,$groupsDn",
 				 attrs => \@matiereEntry );
 	}
 	# Avec ses membres
 	$res = $lcs_ldap->search(base     => "cn=Matiere_$prefix$elstco,$groupsDn",
 				 scope    => 'base',
				 filter   => "memberUid=$profUid");
		#		 filter   => "member=uid=$profUid,$peopleDn");
 	unless (($res->entries)[0]) {
	  $res = $lcs_ldap->modify( "cn=Matiere_$prefix$matimn,$groupsDn",
 				    add => { memberUid => $profUid } );
 		#		    add => { member => "uid=$profUid,$peopleDn" } );
 	}
      }
    }
    print RES "</table>\n";
    close F_MEN;
  }

  unlink </tmp/*.temp>;
  $lcs_ldap->unbind;
  &pdp(RES);
  close RES;

  system ("/usr/bin/lynx --dump $documentRoot/$webDir/result.$$.html | mail $melsavadmin -s 'Importation Texte'");

}
