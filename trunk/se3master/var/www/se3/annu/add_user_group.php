<?php

   /**
   
   * Ajoute des utilisateurs aux groupes dans l'annuaire
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: add_user_group.php
   */

  



  include "entete.inc.php";
  include "ldap.inc.php";
  include "ihm.inc.php";

  require_once ("lang.inc.php");
  bindtextdomain('se3-annu',"/var/www/se3/locale");
  textdomain ('se3-annu');

  //Aide
  $_SESSION["pageaide"]="Annuaire";
  
  echo "<h1>".gettext("Annuaire")."</h1>\n";
  
  aff_trailer ("3");

if (is_admin("Annu_is_admin",$login)=="Y") {
	
	$uid = $_GET[uid]; 
	if ($uid == "") {$uid = $_POST[uid];}
	$add_user_group = $_POST[add_user_group];
	$categorie = $_POST[categorie];
	$filtre = $_GET[filtre];
	if ($filtre == "") { $filtre = $_POST[filtre]; } 
	$new_categorie = $_POST[new_categorie];
	$classe_gr = $_POST[classe_gr];
	$matiere_gr = $_POST[matiere_gr];
	$cours_gr = $_POST[cours_gr];
	$autres_gr = $_POST[autres_gr];
	$equipe_gr = $_POST[equipe_gr];
        $remplacant = $_POST[remplacant];
	if ( !$add_user_group ) {
      		// Ajout de groupes
      		list($user, $groups)=people_get_variables($uid, true);
      		// Affichage du nom et de la description de l'utilisateur
      		echo "<H2>".$user["fullname"]."</H2>\n";
      		if ($user["description"]) echo $user["description"]."<BR>";

      		// Recherche si le user appartient a une categorie principale
      		if ( count($groups) ) {
        		for ($loop=0; $loop < count ($groups) ; $loop++) {
          			if ( ($groups[$loop]["cn"] == "Profs") || ($groups[$loop]["cn"] == "Eleves")|| ($groups[$loop]["cn"] == "Administratifs") ) {
            				$categorie =  $groups[$loop]["cn"];
          			}
        		}
      		}
      		
		// Affichage boite de reaffectation du groupe principal
      		if ( $categorie ) {
        		echo "<table>
        	        <tr>
        	          <td><u>".gettext("Membre de la cat&#233;gorie")."</u> :&nbsp;</td>
                	  <td>
                	    <form action=\"add_user_group.php?uid=$uid\" method=\"post\">
                	    <select name=\"new_categorie\">
             		\n";
        
			if ($categorie == gettext("Administratifs") ) {
          			echo "<option>".gettext("Administratifs")."</option>
                		<option>".gettext("Profs")."</option>
                		<option>".gettext("Eleves")."</option>\n";
        		} elseif ($categorie == gettext("Profs") ) {
          			echo "<option>".gettext("Profs")."</option>
                		<option>".gettext("Administratifs")."</option>
                		<option>".gettext("Eleves")."</option>\n";
        		} else {
          			echo "<option>".gettext("Eleves")."</option>
                		<option>".gettext("Profs")."</option>
                		<option>".gettext("Administratifs")."</option>\n";
        		}

        		echo"        </select>
                 	</td>
                	</tr>
             		</table><br>\n";
      		} else {
      			// Affichage du menu d'affectation de l'utilisateur a une categorie principal
        		echo "<table>
        	        <tr>
        	          <td><u>".gettext("Affectation de l'utilisateur &#224; une cat&#233;gorie")." </u> :&nbsp;</td>
        	          <td>
        	            <form action=\"add_user_group.php?uid=$uid\" method=\"post\">
        	            <select name=\"new_categorie\">
		              <option>".gettext("Eleves")."</option>
		              <option>".gettext("Profs")."</option>
        	              <option>".gettext("Administratifs")."</option>
        	            </select>
        	         </td>
        	        </tr>
        	     </table><br>\n";
      		}	
      		
		// Affichage des groupes secondaires
	      if ( count($groups) > 1  ) {
		        echo "<U>".gettext("Membre des groupes secondaires")."</U> :<BR><UL>\n";
        		for ($loop=0; $loop < count ($groups) ; $loop++) {
          		if ( ($groups[$loop]["cn"] != "Profs") && ($groups[$loop]["cn"] != "Eleves") && ($groups[$loop]["cn"] != "Administratifs") ) {
            			echo "<LI><A href=\"group.php?filter=".$groups[$loop]["cn"]."\">".$groups[$loop]["cn"]."</A>,<font size=\"-2\"> ".$groups[$loop]["description"];
            			$login=preg_split ("/[\,\]/",ldap_dn2ufn($groups[$loop]["owner"]),2);
            			if ($login[0] == $uid) echo "<strong><font color=\"#ff8f00\">&nbsp;(".gettext("professeur principal").")</font></strong>";
            			echo "</font></LI>\n";
            			// constitution d'un filtre pour exclure les groupes d'appartenance
            			// de la liste des groupes proposes
            			$filter = $filter."(!(cn=".$groups[$loop]["cn"]."))";
          		}
        	}
        	echo "</UL>";
      	}

	if ( $categorie ) {
      		// Etablissement des listes des groupes disponibles
      		$list_groups=search_groups("(&(cn=*) $filter )");
      		// Etablissement des sous listes de groupes :
      		$i = 0; $j =0; $k =0; $l = 0 ; $m = 0;
      		for ($loop=0; $loop < count ($list_groups) ; $loop++) {
                //echo "\$list_groups[$loop][\"cn\"]=".$list_groups[$loop]["cn"]."<br />";
                //echo "\$list_groups[$loop][\"cn\"]=".$list_groups[$loop]["cn"].": ($filtre) ";
         		if ( $filtre=="") {
        			// Cours
        			if ( preg_match ("/Cours_/", $list_groups[$loop]["cn"]) ) {
          				$cours[$i]["cn"] = $list_groups[$loop]["cn"];
          				$cours[$i]["description"] = $list_groups[$loop]["description"];
                        //echo " Cours<br />";
          				$i++;
          			// Classe
        			} elseif ( preg_match ("/Classe_/", $list_groups[$loop]["cn"])  ) {
          				$classe[$j]["cn"] = $list_groups[$loop]["cn"];
          				$classe[$j]["description"] = $list_groups[$loop]["description"];
                        //echo " Classe<br />";
          				$j++;
          			// Equipe
        			} elseif ( preg_match ("/Equipe_/", $list_groups[$loop]["cn"]) ) {
          				$equipe[$k]["cn"] = $list_groups[$loop]["cn"];
          				$equipe[$k]["description"] = $list_groups[$loop]["description"];
                        //echo " Equipe<br />";
          				$k++;
          			// Matiere
        			} elseif ( preg_match ("/Matiere_/", $list_groups[$loop]["cn"]) ) {
          				$matiere[$l]["cn"] = $list_groups[$loop]["cn"];
          				$matiere[$l]["description"] = $list_groups[$loop]["description"];
                        //echo " Matiere<br />";
          				$l++;
          			// Autres
	  			//} elseif ( !preg_match( "/^(Administratifs)|(Eleves)|(lcs-users)|(machines)|(overfil)|(Profs)$/",$list_groups[$loop]["cn"] )  ) {
	  			} elseif ( !preg_match( "/(^Administratifs$)|(^Eleves$)|(^lcs-users$)|(^machines$)|(^overfill$)|(^Profs$)/",$list_groups[$loop]["cn"] )  ) {
          				$autres[$m]["cn"] = $list_groups[$loop]["cn"];
          				$autres[$m]["description"] = $list_groups[$loop]["description"];
                        //echo " Autres<br />";
          				$m++;
        			}
                    /*
                    else {
                        echo " ???<br />";
                    }
                    */
			} else {
	  			// Cours
        			if ( preg_match ("/Cours_/", $list_groups[$loop]["cn"])  && preg_match("/$filtre/",$list_groups[$loop]["cn"])) {
          				$cours[$i]["cn"] = $list_groups[$loop]["cn"];
          				$cours[$i]["description"] = $list_groups[$loop]["description"];
          				$i++;
          			// Classe
        			} elseif ( preg_match ("/Classe_/", $list_groups[$loop]["cn"]) && preg_match("/$filtre/",$list_groups[$loop]["cn"]) ) {
          				$classe[$j]["cn"] = $list_groups[$loop]["cn"];
          				$classe[$j]["description"] = $list_groups[$loop]["description"];
          				$j++;
          			// Equipe
        			} elseif ( preg_match ("/Equipe_/", $list_groups[$loop]["cn"])&& preg_match("/$filtre/",$list_groups[$loop]["cn"]) ) {
          				$equipe[$k]["cn"] = $list_groups[$loop]["cn"];
          				$equipe[$k]["description"] = $list_groups[$loop]["description"];
          				$k++;
          			// Matiere
        			} elseif ( preg_match ("/Matiere_/", $list_groups[$loop]["cn"])&& preg_match("/$filtre/",$list_groups[$loop]["cn"]) ) {
          				$matiere[$l]["cn"] = $list_groups[$loop]["cn"];
          				$matiere[$l]["description"] = $list_groups[$loop]["description"];
          				$l++;
          			// Autres
	  			} elseif ( !preg_match( "/^(Administratifs)|(Eleves)|(lcs-users)|(machines)|(overfil)|(Profs)$/",$list_groups[$loop]["cn"] )  ) {
          				$autres[$m]["cn"] = $list_groups[$loop]["cn"];
          				$autres[$m]["description"] = $list_groups[$loop]["description"];
          				$m++;
        			}
			}
      		}
      		
		
		// Affichage des boites de selection des nouveaux groupes secondaires
      		?>
      
      		<h4><?php echo gettext("Ajouter aux groupes secondaires :"); ?></h4>
       		<?php echo gettext("Attention : Filtrage des groupes secondaires en bas de page !"); ?>
    
        	<table border="0" cellspacing="10">
  		  <thead>
		    <tr>
        	      <?php
        	        if ( $categorie == "Eleves" ) {
	        		  echo "<td>".gettext("Classes")."</td>";
                	} else { echo "<td>".gettext("Matieres")."</td>"; }
	        
			echo "<td>".gettext("Cours")."</td>";
                	if ( $categorie != "Eleves" ) {
	          		echo "<td>".gettext("Equipes")."</td>"; }
              		?>
	      		<td><?php echo gettext("Autres"); ?></td>
	    		</tr>
	  		</thead>
	  		<tbody>
 	    		<tr>
	      		<td valign="top">
                	<?php
                  	if ( $categorie == "Eleves" ) {
	            		echo "<select name= \"classe_gr[]\" size=\"10\" multiple=\"multiple\">\n";
                    		for ($loop=0; $loop < count ($classe) ; $loop++) {
                      			echo "<option value=".$classe[$loop]["cn"].">".$classe[$loop]["cn"];
                    		}
                  	} else {
	            		echo "<select name= \"matiere_gr[]\" size=\"10\" multiple=\"multiple\">\n";
                    		for ($loop=0; $loop < count ($matiere) ; $loop++) {
                      			echo "<option value=".$matiere[$loop]["cn"].">".$matiere[$loop]["cn"];
                    		}
                  	}
                	?>
	        	</select>
	      		</td>
	      		<td valign="top">
	        	<select name= "<?php echo "cours_gr[]"; ?>" size="10" multiple="multiple">
                	<?php
                  	for ($loop=0; $loop < count ($cours) ; $loop++) {
                    		echo "<option value=".$cours[$loop]["cn"].">".$cours[$loop]["cn"];
                  	}
                	?>
	        	</select>
	      		</td>
                	<?php
                  	if ( $categorie == "Profs" || $categorie == "Administratifs" || !$categorie) {
                    		echo "<td>\n";
	            		echo "<select name= \"equipe_gr[]\" size=\"10\" multiple=\"multiple\">\n";
                    		for ($loop=0; $loop < count ($equipe) ; $loop++) {
                      			echo "<option value=".$equipe[$loop]["cn"].">".$equipe[$loop]["cn"];
                    		}
                    		echo "</select></td>\n";
                  	}
                	?>
	      		  <td valign="top">
	        	  <select name= "<?php echo "autres_gr[]"; ?>" size="5" multiple="multiple">
                	  <?php
                  	  for ($loop=0; $loop < count ($autres) ; $loop++) {
                    		echo "<option value=".$autres[$loop]["cn"].">".$autres[$loop]["cn"];
                  	  }
                	  ?>
	        	  </select>
	      		  </td>
	    		</tr>
	    		<tr>
	      		  <td>
                	  <input type="reset" value="<?php echo gettext("R&#233;initialiser la s&#233;lection"); ?>">
            		  </td>
		   	<?php } else { ?>
			<table>
		  	<tr>
			<?php } ?>
	      		<td >
                	<input type="hidden" name="categorie" value="<?php echo $categorie ?>">
                	<input type="hidden" name="add_user_group" value="true">
                	<input type="submit" value="<?php echo gettext("Lancer la requ&#234;te"); ?>">
            		</td>
	    		</tr>
        		</table>
      			</form>
    
      			<?php
    			echo "<FORM action=\"add_user_group.php?uid=$uid&filtre=$filtre\" method=\"post\">\n";
        	        echo "<P>".gettext("Filtrer les groupes secondaires contenant :");
        	        echo "<INPUT TYPE=\"text\" NAME=\"filtre\"\n VALUE=\"$filtre\" SIZE=\"16\">";
			echo "    ";
	                echo "<input type=\"submit\" value=\"".gettext("Filtrer")."\">\n";
	                echo "</P></FORM>\n";
      
		    } else {
      			// Reaffectation de l'utilisateur dans une nouvelle categorie
      			if ( $categorie && ($categorie !=  $new_categorie) ) {
        			// Suppression de l'utilisateur de la categorie $categorie
        			exec ("/usr/share/se3/sbin/groupDelUser.pl $uid $categorie",$AllOutPut,$ReturnValue0);
        			// Affectation de l'utilisateur a la categorie $new_categorie
        			exec("/usr/share/se3/sbin/groupAddUser.pl $uid $new_categorie" ,$AllOutPut,$ReturnValue1);
        			if ( $ReturnValue0==0 && $ReturnValue1==0) {
          				echo gettext("L'utilisateur")." <a href='add_user_group.php?uid=$uid'>$uid</a> ".gettext("a &#233;t&#233; r&#233;affect&#233; de la cat&#233;gorie")." <b>$categorie</b> ".gettext("&#224; la cat&#233;gorie")." <b>$new_categorie</b>.</BR></BR>\n";
        			} else {
	  				echo "<br>"; 
          				echo "<div class=error_msg>".gettext("La r&#233;affectation de cat&#233;gorie ")." $categorie ".gettext("vers")." $new_categorie ".gettext(" de l'utilisateur ");
          				echo "<font color='#0080ff'>$uid</font> ".gettext(" &#224; &#233;chou&#233;e.<br> veuillez contacter")." <A HREF='mailto:$MelAdminLCS?subject=PB ".gettext("Reaffectation categorie")." $categorie ".gettext("vers")." $new_categorie ".gettext("de")." $uid'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
        			}
      			} elseif (!$categorie && $new_categorie ) {
        			exec("/usr/share/se3/sbin/groupAddUser.pl $uid $new_categorie" ,$AllOutPut,$ReturnValue);
        			if ( $ReturnValue==0 ) {
          				echo gettext("L'utilisateur")." <a href='people.php?uid=$uid'>$uid </a>".gettext(" a &#233;t&#233; affect&#233; &#224; la cat&#233;gorie")." <b>$new_categorie</b>.</BR></BR>\n";
        			} else {
          				echo "<div class=error_msg>";
          				echo gettext("L'affectation &#224; la cat&#233;gorie")." $new_categorie ".gettext(" de l'utilisateur");
          				echo "<font color='#0080ff'>$uid</font>".gettext(" a &#233;chou&#233;e, veuillez contacter")." <A HREF='mailto:$MelAdminLCS?subject=PB ".gettext("Affectation categorie")." $new_categorie ".gettext("de")." $uid'>".gettext("l'administrateur du syst&#232;me")."</A></div><BR>\n";
        			}
      			}
      			
			// Ajout des groupes secondaires
      			// Classe
			echo "<br>";
			echo gettext("L'utilisateur")." <a href='people.php?uid=$uid'>$uid</a> ";
			if (count($classe_gr) > 0 || count($matiere_gr) > 0 || count($cours_gr) > 0  || count($equipe_gr) > 0 || count($autres_gr) > 0 )
      				echo gettext("a &#233;t&#233; ajout&#233; dans les")." <a href='add_user_group.php?uid=$uid'>".gettext("groupes secondaires")."</a> :<BR>";
			else echo gettext("n'a &#233;t&#233; ajout&#233; dans aucun")." <a href='add_user_group.php?uid=$uid'>".gettext("groupe secondaire")."</a>.<BR>";
      			
			if (count($classe_gr) ) {
        			for ($loop=0; $loop < count ($classe_gr) ; $loop++) {
          				exec("/usr/share/se3/sbin/groupAddUser.pl $uid $classe_gr[$loop]" ,$AllOutPut,$ReturnValue);
          				echo $classe_gr[$loop]."&nbsp;";
          				if ($ReturnValue == 0 ) {
            					echo "<stong><strong>".gettext("R&#233;ussi")."</strong></strong><BR>";
          				} else { echo "<font color=\"orange\">".gettext("Echec")."</font><BR>"; $err++; }
        			}
      			}
      			
			// Matiere
      			if (count($matiere_gr) ) {
        			for ($loop=0; $loop < count ($matiere_gr) ; $loop++) {
          				exec("/usr/share/se3/sbin/groupAddUser.pl $uid $matiere_gr[$loop]" ,$AllOutPut,$ReturnValue);
          				echo $matiere_gr[$loop]."&nbsp;";
          				if ($ReturnValue == 0 ) {
            					echo "<strong>".gettext("R&#233;ussi")."</strong><BR>";
          				} else { echo "</strong><font color=\"orange\">".gettext("Echec")."</font></strong><BR>"; $err++; }
        			}
      			}
      			
			// Cours
      			if (count($cours_gr) ) {
        			for ($loop=0; $loop < count ($cours_gr) ; $loop++) {
          				exec("/usr/share/se3/sbin/groupAddUser.pl $uid $cours_gr[$loop]" ,$AllOutPut,$ReturnValue);
         			 	echo $cours_gr[$loop]."&nbsp;";
          				if ($ReturnValue == 0 ) {
            					echo "<strong>".gettext("R&#233;ussi")."</strong><BR>";
          				} else { echo "</strong><font color=\"orange\">".gettext("Echec")."</font></strong><BR>"; $err++; }
        			}
      			}
      			
			// Equipe
      			if (count($equipe_gr) ) {
        			for ($loop=0; $loop < count ($equipe_gr) ; $loop++) {
          				exec("/usr/share/se3/sbin/groupAddUser.pl $uid $equipe_gr[$loop]" ,$AllOutPut,$ReturnValue);
          				echo $equipe_gr[$loop]."&nbsp;";
          				if ($ReturnValue == 0 ) {
            					echo "<strong>".gettext("R&#233;ussi")."</strong><BR>";
          				} else { echo "</strong><font color=\"orange\">".gettext("Echec")."</font></strong><BR>"; $err++; }
        			}
      			}
      			
			// Autres
      			if (count($autres_gr) ) {
        			for ($loop=0; $loop < count ($autres_gr) ; $loop++) {
          				exec("/usr/share/se3/sbin/groupAddUser.pl $uid $autres_gr[$loop]" ,$AllOutPut,$ReturnValue);
          				echo $autres_gr[$loop]."&nbsp;";
          				if ($ReturnValue == 0 ) {
            					echo "<strong>".gettext("R&#233;ussi")."</strong><BR>";
          				} else { echo "</strong><font color=\"orange\">".gettext("Echec")."</font></strong><BR>"; $err++; }
        			}
      			}
      			
			// Compte rendu de la page remplacant.php (ajout aux groupes du prof remplac&#233;)
      			if ($_POST['remplacant']=="true" ) {
                        
                          // Prepositionnement variables
                          $mono_srv = false;
                          $multi_srv = false;
                          // Recherche de la nature mono ou multi serveur de la plateforme SE3
                          $master=search_machines ("(l=maitre)", "computers");
                          $slaves= search_machines ("(l=esclave)", "computers");
                          if ( count($master) == 0 ) {
                            echo "<P>".gettext("ERREUR : Il n'y a pas de serveur maitre d&#233clar&#233 dans l'annuaire ! <BR>Veuillez contacter le super utilisateur du serveur SE3.")."</P>";
                          } elseif (  count($master) == 1  && count($slaves) == 0 ) {
                             // Plateforme mono-serveur
                             $mono_srv = true;
                          } elseif (  count($master) == 1  && count($slaves) > 0  ) {
                             $multi_srv = true;
                          } // Fin Recherche de la nature mono ou multi serveur de la plateforme SE3
                          if ($mono_srv == "true") {
                            echo "<BR>".gettext(" Le rafraichissement des classes n'est plus n&#233;cessaire depuis la version 1.13 de SAMBAEDU. Le professeur a automatiquement les droits sur les dossiers Classes.");
                          }
                          if ($multi_srv == "true") {
                            echo "<BR>".gettext(" N'oubliez pas de")." <A HREF=\"../partages/synchro_folders_classes.php\">".gettext("rafraichir les classes")." </A>".gettext("pour attribuer les ACLS")."<BR>.";
                          }
                        }
      			if ($err ) {
        			echo "<div class=error_msg>";
        			echo gettext("Veuillez contacter")."<A HREF='mailto:$MelAdminLCS?subject=PB".gettext("Affectation de")." $uid ".gettext(" a des groupes secondaires !")."'>".gettext("l'administrateur du syst&#232;me")."</A>
              			</div><BR>\n";
      			}
    		}
  	} else {
    		echo "<div class=error_msg>".gettext("Cette application, n&#233;cessite les droits d'administrateur du serveur LCS !")."</div>";
  	}
  
include ("pdp.inc.php");
?>
