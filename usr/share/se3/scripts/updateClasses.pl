#!/usr/bin/perl -w

# $Id$


# Encodage UTF-8
# Met a jour l'arborescence des partages Classes
# en definissant les acl basees sur les posixGroup Equipe_* et Classe_*
#
# syntaxe : updateClasses.pl -e|-c ALL|Classe|login
#     - ALL       : pour passer en revue toutes les classes (les dossiers manquant sont crees, aucun dossier n'est supprime)
#     - NomClasse : par exemple 1-S1 pour la Classe Classe_1-S1 (si la classe n'existe pas dans l'annuaire, le rep Classe est supprime (renomme .Classe_1-S1) )
#     - eleve : login d'un eleve, la Classe est lue dans l'annuaire
#                            Si   le dossier de l'eleve est absent il est cree ( si .eleve  existe, il est restaure)
#                            Si   le dossier de l'eleve existait dans une autre classe, il est deplace et renomme archive les droits sont mis &#224; jour
#                            Si   l'eleve n'est inscrit dans aucune classe, son dossier eleve est renomme .eleve    s'il existait dans l'aborescence classes...
#    D.B.                    Si   l'eleve a un dossier dans 2 classes, il est deplace de dans la nouvelle et renomme Archive
#    D.B.       
#       Jean Le Bail ( jean.lebail@etab.ac-caen.fr ) 10 juillet 2007
#       Denis Bonnenfant (denis.bonnenfant@diderot.org) 7 octobre 2007 : inversion des noms et petites modifs
#       Denis Bonnenfant (denis.bonnenfant@diderot.org) 3 septembre 2008 : Création du réeprtoire élève avant de dmigrer les dossiers de l'année d'avant
#       
#      renomme si necessaire les repertoires prenom.nom en nom.prenom afin de permettre une visualisation dans l'ordre des listes de classes


# supprime les Warnings du module Se.pm
BEGIN { $SIG{'__WARN__'} = sub { warn $_[0] if $DOWARN } }
use Se;
$DOWARN = 1; # Warnings actives a nouveau

# fonction qui teste le type de login et qui renvoie nom.prenom dans le cas d'un login prenom.nom, ou sinon le login
# si la fonction est appelle avec un login, elle cherche si il y a un répertoire à inverser
# sinon renvoie le login

sub Invert_Login {
   my $login = $_[0];
   my @NOM = split(/\./, $login);
   if ( @NOM == 2 ) {
    # on inverse
     my $eleve =  @NOM[1] . "." . @NOM[0];
     my $res = $lcs_ldap->search(base => "$groupsDn",
         scope    => 'one',
         filter   => "(&(cn=Classe_*)(memberUid=$login))");
     warn $res->error if $res->code;
     if ( ($res->entries) == 1 ) {
       # c'est un login
       my @REP = <$PathClasses/Classe_*/$login>;
       if ( @REP > 0 ) {
         foreach my $rep (@REP) {
             my $tmpClasse = $rep;
             $tmpClasse =~ s!^$PathClasses/Classe_(.+)/$login$!$1!;
             print "Inversion de $login -> $eleve<br>\n";     
             system("/bin/mv '$PathClasses/Classe_$tmpClasse/$login' '$PathClasses/Classe_$tmpClasse/$eleve'") == 0 or warn "  Erreur: /bin/mv '$PathClasses/Classe_$tmpClasse/$login' '$PathClasses/Classe_$tmpClasse/$eleve'\n";
             print "classe : $tmpClasse\n"; 
             print "inversion de " . $PathClasses . "/Classe_" . $tmpClasse . "/" . $eleve . " avec " . $eleve . " faite<br>\n";
         }
       }     
     }
     return $eleve; 
   } else {
     return $login;
   }
}

sub Cree_Rep { 
	  # fait les repertoires
	  # Recherche de l'Eleve dans les Classes
	  my $OldClasse = $_[0];
	  my $Classe = "";
  	  my $cnClasse = ""; 
	  my $res1 = $lcs_ldap->search(base => "$groupsDn",
	         scope    => 'one',
	         filter   => "(&(cn=Classe_*)(memberUid=$LOGIN))");
	  warn $res1->error if $res1->code;
	  
	  if ( ($res1->entries) == 1 ) {
	    $cnClasse = ($res1->entries)[0]->get_value('cn');
	    $Classe = $cnClasse;
	    if ( ! ($Classe =~ m/^Classe_/) ) {
	      warn "Bizarre : le nom '$cnClasse' de la classe ne commence pas par 'Classe_' !\n  Par prudence, pas de cr&#233;ation du dossier de l'&#233;l&#232;ve '$cnClasse/$ELEVE' !<br>\n";
	    } else {
	      $Classe =~ s/^Classe_// ;
	      if ($OldClasse ne "") {
	        if ("Classe_$OldClasse" ne $cnClasse) {
	          print "  Changement de classe de '$ELEVE' : Classe_$OldClasse -> $cnClasse.<br>\n";
		  if (! -d "$PathClasses/$cnClasse/$ELEVE") {
	          	system("/bin/mkdir  '$PathClasses/$cnClasse/$ELEVE'") == 0 or warn "Erreur: /bin/mkdir '$PathClasses/$cnClasse/$ELEVE'\n";
		  }
		  if (! -d "$PathClasses/$cnClasse/$ELEVE/Archives") {
	          	system("/bin/mkdir  '$PathClasses/$cnClasse/$ELEVE/Archives'") == 0 or warn "Erreur: /bin/mkdir '$PathClasses/$cnClasse/$ELEVE/Archives'\n";
		  }
		  if (! -d "$PathClasses/$cnClasse/$ELEVE/Archives/$ELEVE") {
		          system("/bin/mv -f '$PathClasses/Classe_$OldClasse/$ELEVE' '$PathClasses/$cnClasse/$ELEVE/Archives/'") == 0 or warn "Erreur: /bin/mv -f '$PathClasses/Classe_$OldClasse/$ELEVE' '$PathClasses/$cnClasse/$ELEVE/Archives'\n";;
		  } else {
			  system("/bin/rm -fr '$PathClasses/Classe_$OldClasse/$ELEVE'") == 0 or warn "  Erreur: rm -fr '$PathClasses/Classe_$OldClasse/$ELEVE'<br>\n";
		  }
	        }
	      }
	      if (! -d "$PathClasses/$cnClasse/$ELEVE") {
	        if ( -d "$PathClasses/$cnClasse/.$ELEVE") {
	          print "Restauration du dossier '$cnClasse/.$ELEVE'.<br>\n";
	          system("/bin/mv '$PathClasses/$cnClasse/.$ELEVE' '$PathClasses/$cnClasse/$ELEVE'") == 0 or warn "  Erreur: /bin/mv '$PathClasses/$cnClasse/.$ELEVE' '$PathClasses/$cnClasse/$ELEVE'<br>\n";
	        } else {
	          print "Cr&#233;ation du dossier '$cnClasse/$ELEVE'.\n";
	          system("/bin/mkdir '$PathClasses/$cnClasse/$ELEVE'") == 0 or warn "  Erreur: mkdir '$PathClasses/$cnClasse/$ELEVE'<br>\n";
	        }
	      }
	      if ( -d "$PathClasses/$cnClasse/$ELEVE") {
	        print "Mise en place des droits sur $cnClasse/$ELEVE.<br>\n";
	        	      #modif webdav
			if ( -e "/etc/apache2/sites-enabled/webdav") {
				system("/usr/bin/setfacl -R -P --set user::rwx,group::---,user:$LOGIN:rwx,user:www-data:r-x,default:user:www-data:r-x,group:Equipe_$Classe:rwx,group:admins:rwx,mask::rwx,other::---,default:user::rwx,default:group::---,default:group:Equipe_$Classe:rwx,default:group:admins:rwx,default:mask::rwx,default:other::---,default:user:$LOGIN:rwx $PathClasses/$cnClasse/$ELEVE") == 0 or warn "  Erreur: /usr/bin/setfacl -R -P --set user::rwx,group::---,user:$LOGIN:rwx,group:Equipe_$Classe:rwx,group:admins:rwx,mask::rwx,other::---,default:user::rwx,default:group::---,default:group:Equipe_$Classe:rwx,default:group:admins:rwx,default:mask::rwx,default:other::---,default:user:$LOGIN:rwx $PathClasses/$cnClasse/$ELEVE\n";
 
				 } 
			else { 
				system("/usr/bin/setfacl -R -P --set user::rwx,group::---,user:$LOGIN:rwx,group:Equipe_$Classe:rwx,group:admins:rwx,mask::rwx,other::---,default:user::rwx,default:group::---,default:group:Equipe_$Classe:rwx,default:group:admins:rwx,default:mask::rwx,default:other::---,default:user:$LOGIN:rwx $PathClasses/$cnClasse/$ELEVE") == 0 or warn "  Erreur: /usr/bin/setfacl -R -P --set user::rwx,group::---,user:$LOGIN:rwx,group:Equipe_$Classe:rwx,group:admins:rwx,mask::rwx,other::---,default:user::rwx,default:group::---,default:group:Equipe_$Classe:rwx,default:group:admins:rwx,default:mask::rwx,default:other::---,default:user:$LOGIN:rwx $PathClasses/$cnClasse/$ELEVE\n";

			}	 
	      # Modifie le groupe par defaut
	      system("chgrp admins $PathClasses/$cnClasse/$ELEVE");
	      }
	    }
	  } else {
	    if (($res1->entries)[0]) {
	      warn( "<div class='error_msg'>Erreur : '$ELEVE' est inscrit dans plusieurs Classes !</div><br>\n");
	    } else {
	      # L'eleve n'est inscrit dans aucune  classe
	      if ( $OldClasse ne "" ) {
	        print "$ELEVE n'est inscrit dans aucune classe : Renommage de 'Classe_$OldClasse/$ELEVE' en 'Classe_$OldClasse/.$ELEVE'.<br>\n";
	        system("/bin/mv '$PathClasses/Classe_$OldClasse/$ELEVE' '$PathClasses/Classe_$OldClasse/.$ELEVE'") == 0 or warn "Erreur : /bin/mv '$PathClasses/Classe_$OldClasse/$ELEVE' '$PathClasses/Classe_$OldClasse/.$ELEVE'<br>\n";
	      } else { 
	        warn( "<div class='error_msg'>Erreur : '$LOGIN' ne correspond pas  &#224 un eleve !</div><br>\n");
	      }
      
    	   }
      	}
return 0;
}


sub Update_Eleve {
   $LOGIN = $_[0];
   $ELEVE = Invert_Login($LOGIN);
  my $rep = "";
  # Recherche du dossier Eleve
  my @REP = <$PathClasses/Classe_*/$ELEVE>;
  if ( @REP > 0 ) {
  foreach $rep (@REP) { 
    if ( $rep =~ m/Classe_grp_/ ) {
         print "Ancien groupe '$rep' ignor&#233;e.<br>\n"; 
    } else {
	 if ( $rep ne "" ) {
	    if ( $rep =~ m!^$PathClasses/Classe_.+/$ELEVE$! ) {
	      $rep =~ s!^$PathClasses/Classe_(.+)/$ELEVE$!$1!;
	    } else {
	      warn "Bizarre : Le r&#233;pertoire '$OldClasse' de l'ancienne classe de '$ELEVE' n'est pas de la forme '$PathClasses/Classe_*/$ELEVE' ! <br>\n";
	      $rep = "";  # On laisse tomber la gestion de l'ancien r&#233;pertoire
	    }
	}
	Cree_Rep($rep);
    }
  }
} else {
	 Cree_Rep("");
}  
return 0;
}
 

   
$PathClasses = '/var/se3/Classes';
die("Syntaxe : updateClasses.pl -c|-e ALL|Classe|eleve") if ($#ARGV != 1);
($option, $Classe) = @ARGV;

$lcs_ldap = Net::LDAP->new("$slapdIp");
$lcs_ldap->bind(
        dn       => $adminDn,
        password => $adminPw,
        version  => '3'); 

if ($option eq '-c') {
  if ($Classe eq 'ALL') {
    $FILTRE = "(cn=Classe_*)";
  } else {
    $FILTRE = "(cn=Classe_$Classe)";
  }

  $res = $lcs_ldap->search(base => "$groupsDn",
       scope    => 'one',
       filter   => "$FILTRE");
  die $res->error if $res->code;
  if (($res->entries)[0]) {
  # Au moins une classe a ete trouvee
    foreach $objClasse ($res->entries) {
      $cnClasse = $objClasse->get_value('cn');
      $Classe = $cnClasse;
      $Classe =~ s/^Classe_// ;
      print "<b>Mise &#224; jour du partage de la classe : $Classe</b><br>\n";
      #Verification l'existence du posixGroup Equipe_$Classe
      $resProfs = $lcs_ldap->search(base     => "$groupsDn",
           scope    => 'one',
           filter   => "(&(cn=Equipe_$Classe)(objectClass=posixGroup))");
      warn $resProfs->error if $resProfs->code;
      if (!($resProfs->entries)[0]) {
          warn "Erreur: Le posixGroup Equipe_$Classe n'existe pas!<br>\n";
      } else {
        if (! -d "$PathClasses/$cnClasse") {
          if (-d "$PathClasses/.$cnClasse") {
            print("<b> restauration du repertoire de la classe $Classe</b><br>\n");
	    system("/bin/mv $PathClasses/.$cnClasse $PathClasses/$cnClasse") == 0 or warn "Erreur: /bin/mv $PathClasses/.$cnClasse $PathClasses/$cnClasse\n";
          } else {
            print("<b> Cr&#233;ation du repertoire de la  classe $Classe</b><br>\n");
            system("/bin/mkdir $PathClasses/$cnClasse") == 0 or warn "Erreur: /bin/mkdir $PathClasses/$cnClasse\n";
          }
        }
        if ( -d "$PathClasses/$cnClasse") {

	  #test dossier echange
        if ( -d "$PathClasses/$cnClasse/_echange") {
			#system("getfacl $PathClasses/$cnClasse/_echange 2>/dev/null  | grep \"^group:$cnClasse:rwx\$\"");
			$etat = system("getfacl $PathClasses/$cnClasse/_echange 2>/dev/null | grep \"^group:$cnClasse:rwx\$\" >/dev/null");
			
			
	  } else {
		  $etat = 1;
		}
			if ( -e "/etc/apache2/sites-enabled/webdav") {
				$ret = system("setfacl -R -P --set user::rwx,group::---,user:www-data:r-x,default:user:www-data:r-x,group:Equipe_$Classe:rwx,group:admins:rwx,mask::rwx,other::---,default:user::rwx,default:group::---,default:group:Equipe_$Classe:rwx,default:group:admins:rwx,default:mask::rwx,default:other::--- $PathClasses/$cnClasse");
          
			}
			else { 
				$ret = system("setfacl -R -P --set user::rwx,group::---,group:Equipe_$Classe:rwx,group:admins:rwx,mask::rwx,other::---,default:user::rwx,default:group::---,default:group:Equipe_$Classe:rwx,default:group:admins:rwx,default:mask::rwx,default:other::--- $PathClasses/$cnClasse");
            } 
          $ret == 0 or warn "Erreur: setfacl $PathClasses/$cnClasse\n";
	  if ( $etat == 0 ) {
				system("/usr/share/se3/scripts/echange_classes.sh $cnClasse actif >/dev/null 2>/dev/null");
				} 
	  
	  
	  # Modifie le groupe par defaut
	  system("chgrp admins $PathClasses/$cnClasse");
          
	  print "  $cnClasse/_travail<br>\n";
          if ( ! -d "$PathClasses/$cnClasse/_travail") {
            system("/bin/mkdir $PathClasses/$cnClasse/_travail") == 0 or warn "Erreur: /bin/mkdir $PathClasses/$cnClasse/_travail\n";
          }
          if ( -d "$PathClasses/$cnClasse/_travail") {
			  			  
			  system("/usr/bin/setfacl -R -P -m group:Classe_$Classe:rx,default:group:$cnClasse:rx $PathClasses/$cnClasse/_travail") == 0 or warn "Erreur: /usr/bin/setfacl $PathClasses/$cnClasse/_travail<br>\n";
			  
            # Modifie le groupe par defaut
	    system("chgrp admins $PathClasses/$cnClasse/_travail");
	  }
          print "  $cnClasse/_profs<br>\n";
          if ( ! -d "$PathClasses/$cnClasse/_profs") {
            system("/bin/mkdir $PathClasses/$cnClasse/_profs") == 0 or warn "Erreur: /bin/mkdir $PathClasses/$cnClasse/_profs<br>\n";
          }
	  # Modifie le groupe par defaut
	  system("chgrp admins $PathClasses/$cnClasse/_profs");
          
	  # premiere passe : on analyse les repertoires
          @oldeleve = <$PathClasses/$cnClasse/*>;
            foreach $oldeleve (@oldeleve) { 
              if ( $oldeleve =~ m!^$PathClasses/$cnClasse/_! ) {
#                print "r&#233;pertoire '$oldeleve' ignor&#233;.<br>\n"; 
              } else {
                # D.B. On met à jour les anciens eleves de la classe
                $oldeleve =~ s!^$PathClasses/$cnClasse/!! ;
                $login = Invert_Login($oldeleve); 
                Update_Eleve($login) == 0 or warn " Erreur : impossible de mettre a jour pour $login<br\n>";
		# Modifie le groupe par defaut
	        if ( -d "$PathClasses/$cnClasse/$login") { 
		  system("chgrp admins $PathClasses/$cnClasse/$login"); 
      	        }
	      }
            }
        
	  # deuxieme passe : on cherche dans l'annuaire  
          @members = $objClasse->get_value('memberUid');
          foreach $member (@members) {
            # D.B. On met met a jour les eleves actuels de la classe pas encore faits
            $eleve = Invert_Login($member);  
            if ( ! -d "$PathClasses/$cnClasse/$eleve") {
              Update_Eleve($member) == 0 or warn " Erreur : impossible de mettre a jour pour $member<br>\n>";
	      # Modifie le groupe par defaut
	      if ( -d "$PathClasses/$cnClasse/$eleve") { 
		system("chgrp admins $PathClasses/$cnClasse/$eleve"); 
	      }
	    }
	  }
          #Retrait du droit w &#224; Equipe_$CLASSE et ajout de rx au groupe $cnClasse (Classe_ ) sur le dossier /var/se3/Classes/$cnClasse
          system("/usr/bin/setfacl -m group:Equipe_$Classe:rx,group:$cnClasse:rx $PathClasses/$cnClasse") == 0 or warn "Erreur: /usr/bin/setfacl $PathClasses/$cnClasse<br>\n";
        } 
      }
    }
  } elsif ( -d "$PathClasses/Classe_$Classe" ){
    if ( $Classe =~ m/grp_/ ) {
      print "Ancien groupe '$rep' ignor&#233;e. utilisez le menu groupe<br>\n"; 
    } else{
      # le répertoire existe, mais la classe non : on renomme en .Classe_truc, au cas ou
      print("Le groupe n'existe plus : Renommage de la classe $Classe. en .Classe_$Classe<br>\n");  
      system("/bin/mv $PathClasses/Classe_$Classe $PathClasses/.Classe_$Classe") == 0 or warn "Erreur: /bin/mv $PathClasses/Classe_$Classe $PathClasses/.Classe$Classe\n";
    }
  }    
} elsif ($option eq '-e')  {
   # on traite direct un eleve 
   Update_Eleve($Classe) == 0 or warn " Erreur : impossible de mettre à jour pour$Classe<br>\n>";
}
exit 0 ;
