<?php


	/** 

	* Deploie des devoirs ou documents aux utilisateur
	
	* @Version $Id$
	* @Projet LCS-SE3
   
	* @Auteurs Jean Gourdin
   
	* @Licence Distribue selon les termes de la licence GPL
    
    	*/

	/**

	* file: distribution.php
	* @Repertoire: echanges/

	*/


require("entete.inc.php");
require("ldap.inc.php");
require("fonc_outils.inc.php");

require_once ("lang.inc.php");
bindtextdomain('se3-echange',"/var/www/se3/locale");
textdomain ('se3-echange');

// recuperer les parametres passes par POST
foreach ($_POST as $cle=>$val) {
    $$cle = $val;
}

// Aide en ligne pour un devoir
if ($devoir) {
	//aide
	$_SESSION["pageaide"]="L%27interface_prof#Ressources_et_partages";
}

$login=isauth();
$id_prof=$login;
$now =date("Y-m-d");
$table="devoirs";
$fichiers= array();


echo "<body >
<h1>".gettext("Distribution de document(s)")." <font size=-2>(<em>".gettext("par")." $login,".gettext(" le ").affiche_date($now)."</em>)</font></h1>
<hr>\n";

// nombre de documents : $nombre=$_POST['nombre'];
// recup type de la distrib : $type=$_POST['type'];
// recup type des liste de classes ou d'eleves --> cf chaque cas
// $classes=$_POST['classes'];   OU   $liste_classe=$_POST['liste_classe'];

//Suppression des espaces dans l'identifiant
//(utilise par la suite pour la creation du dossier):
//$id_devoir=preg_replace("/ /","_","$id_devoir");
$id_devoir=strtr(preg_replace("/�/","AE",preg_replace("/�/","ae",preg_replace("/�/","OE",preg_replace("/�/","oe","$id_devoir"))))," '���������������������զ����ݾ�������������������������������","__AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz");


// VERIFICATION
// verifier si l'id_devoir d'un devoir non archive est deja attribue
if ($devoir) {
 // verification de l'id_devoir
  $req =" SELECT id_devoir FROM $table WHERE BINARY id_devoir='$id_devoir' ";
  // echo $req;
  $res=mysqli_query($GLOBALS["___mysqli_ston"], $req);
  $nb=mysqli_num_rows($res);
  if ($nb >0) {
     die ("<h4>".gettext("L'identifiant du devoir a d&#233;j&#224; &#233;t&#233; utilis&#233; !")."<br>".gettext("Veuillez en choisir un autre.")."</h4>\n");
  }
 }

if ($devoir) {
  // traitement de la date de retour
  $date_retour= "$jour_retour/$mois_retour/$an_retour";
  echo "<h3>".gettext("Distribution du devoir")." <em>$id_devoir</em> (".gettext("pour le")." $date_retour)</h3>\n";
  }
else
  echo "<h3>".gettext("Distribution ").($nombre==1?gettext("du fichier"):gettext("des fichiers"))."</h3>\n";

// stockage des fichiers uploades  dans /tmp avant distribution
for ($i=1; $i<= $nombre; $i++) {
  $ff="fich$i";
    // === DEBUG ==
    // echo "fichier $_FILES[$ff]['name'] <br>";
    // print_r($_FILES);
    $destination="/tmp/".$_FILES[$ff]['name'];
  if (move_uploaded_file($_FILES[$ff]['tmp_name'], $destination)) {
  	if ($f = @fopen( $destination, "r")) {
    		$taille=$_FILES[$ff]['size'];
  		//== DEBUG ==
  
    		// echo "ouverture du fichier $ff de taille $taille";
    		$contenu=fread($f, $taille);
    		$donnees=addslashes($contenu);
    		$nom = $_FILES[$ff]['name'] ;
    		//$chemin="/tmp/$nom";
    		system("mkdir -p /tmp/$login");
    		$chemin="/tmp/$login/$nom";

    		$f1 = fopen($chemin,"w");
    		if ($f1)
      			echo "<lI>$nom</li>";
    		fputs($f1, $contenu);
    		fclose($f1);
    		$fichiers[] = $nom;
 	}
  }	
 else
   echo "<li>".gettext("pas de fichier n�")." $i ".gettext("choisi (ou fichier vide ..)")."</li>";
}
// VERIFICATION : si aucun fichier n'a ete choisi, s'arreter avec message
$nb_fichiers=sizeof($fichiers);
if ($nb_fichiers==0)
  die (gettext("Echec de la distribution, recommencez .."));

// declarations globales
$tab_eleves=array();      // tableau associatif : nom-classe/groupe --> liste des eleves
$liste_eleves = "";

if ($type==1) {
/* traitement de la distribution type I : distribution par classes
  *****************************************************************/
 if (! empty($liste_classe)) {
  $liste_classe=trim($liste_classe);
  $liste_classe=preg_replace("/#$/","",$liste_classe);
  $classes=preg_split("/#/",$liste_classe);
 }
 $n=sizeof($classes);  // nombre de classes+groupes
 // creation de la liste des classes
 for ($i=0; $i<$n; $i++) {
    $filtres[$i]="cn=$classes[$i]";       // echo "Classes : $classes[$i]<br>";
 }

echo "<h3>".gettext("Aux &#233;l&#232;ves des classes (ou groupes)")."</h3>\n";
// boucle sur les classes
for ($g=0; $g <$n ; $g++) {

  $classe=$classes[$g];       // ATTENTION ! nom de la gieme classe OU nom GROUPE
  $equipe="Equipe_".preg_replace("/Classe_/","",$classe); // Modif delineau (il y a surement mieux � faire)
  $liste_eleves_classe = "";  // liste des eleves par classe/groupe
  $eleves=array();            // tableau indice des eleves de la classe/groupe
  $uids = search_uids ($filtres[$g]);
  //  $eleves = search_people_groups ($uids,"","group");
  $membres = search_people_groups ($uids,"","group");


  // recherche des vrais eleves, fitrer les profs ..
  for ($p=0; $p < sizeof($membres); $p++) {
    $id_eleve = $membres[$p]["uid"];
    // est-ce un vrai eleve ? eliminons les profs faisant partie du groupe !!
    if (est_prof($id_eleve)) continue;
    $eleves[]=$membres[$p];        // $eleves tableau indice : num-eleve --> [iud,sexe,fullname]
    $liste_eleves .= "$id_eleve#";
    $liste_eleves_classe .= "$id_eleve#";
  }
  // distribution et affichage des resultats
  $tab_eleves[$classe]=$liste_eleves_classe ;         // <----  tableau des eleves a transformer en liste
  $nb_eleves=sizeof($eleves);
  echo "$classe  <font size=-1>[$nb_eleves ".($nb_eleves==1?gettext("&#233;l&#232;ve"):gettext("&#233;l&#232;ves"))."]</font><br>\n";

  for ($p=0; $p < $nb_eleves; $p++) {  // boucle sur les vrais eleves
    $id_eleve = $eleves[$p]["uid"];
    $param=params_eleve($id_eleve);

    // creation rep pour devoir et copies fichiers (inversion si n�cessaire)
    $rep= "/var/se3/Classes/".$param[classe]."/".inverse_login($id_eleve);    echo $rep."<br>";
    $cr=1;
    if (($devoir) and ("$id_devoir"!="")){
      $rep .= "/$id_devoir";
      //$ch ="/usr/bin/sudo /usr/share/se3/scripts/creer_rep_distrib.sh $login $id_eleve $rep";
      $ch ="/usr/bin/sudo /usr/share/se3/scripts/creer_rep_distrib.sh $login $id_eleve \"$rep\"";
      $cr= exec($ch) ;
    }
    if ($cr) {
      /// Le repertoire du devoir $rep a ete cree, boucle sur tous les fichiers a distribuer
      // $CR1 produit des cr1, pour verifier que tous les fichiers ont ete  distribuies
      $CR1=1;
      for ($i=0; $i< $nb_fichiers; $i++) {
        //$ch1 ="/usr/bin/sudo /usr/share/se3/scripts/copie_fich_distrib.sh $login $id_eleve $rep \"$fichiers[$i]\" ";
        $ch1 ="/usr/bin/sudo /usr/share/se3/scripts/copie_fich_distrib.sh $login $id_eleve \"$rep\" \"$fichiers[$i]\" $equipe "; // Modif delineau
        $cr1= exec($ch1) ;
        // on range $cr1 dans un tableau
        $tab_cr1[$i]=$cr1;
        $CR1 *= $cr1;
      }
       if ($CR1) {
         $im=($eleves[$p]["sexe"]=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
         "<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
         echo $im.$eleves[$p]["fullname"]."<br>\n";
       }
       else {
         // preciser les fichiers non distribues ??
         echo " ---> ".gettext("&#233;chec de la distribution pour ").$eleves[$p]["fullname"]."<br>\n";
       }
      }
       else {
       // verifier que le rep. existe deja, pour le meme devoir
       // cas (rare ?) d'eleves presents dans plusieurs groupes !
       // echo "<h4>Echec de la creation du repertoire $rep</h4>";
        echo "   ---> ".gettext("distribution d&#233;j&#224; effectu&#233;e pour ").$eleves[$p]["fullname"]."<br>\n";
      }
  }  // fin for $p
  echo "<p>";
}  // fin for $g

}  // FIN envoi de type I
else {

/* traitement de la distribution type II : choix d'eleves seulement
 * ici les profs ont deja ete filtres
 *******************************************************************/
// groupes d'eleves par classes :  $liste_classe=$_POST['liste_classe'];
// liste des classes : $liste_classe
  $classes=preg_split("/#/",$liste_classe);
  $n=sizeof($classes);
  echo "<h3>".gettext("Aux &#233;l&#232;ves s&#233;lectionn&#233;s dans les classes (ou groupes) :")." </h3>\n";

// $liste_eleves="";

 for ($g=0; $g<$n; $g++) {
 // boucle sur toutes les classes
  $classe=$classes[$g];           // nom de la gieme classe/groupe
  $equipe="Equipe_".preg_replace("/Classe_/","",$classe); // Modif delineau (il y a surement mieux � faire)
  $liste_eleves_classe = "";  // liste des eleves par classe/groupe
  $libelle_eleves="eleves".$g;

  $eleves=$_POST[$libelle_eleves];
  $nb_eleves=sizeof($eleves);

  echo "$classe  <font size='-1'>[".($nb_eleves==0?gettext("aucun &#233;l&#232;ve"):($nb_eleves==1?"$nb_eleves ".gettext("&#233;l&#232;ve"):"$nb_eleves ".gettext("&#233;l&#232;ves")))."]</font><br>\n";

  for ($p=0; $p < $nb_eleves; $p++) {    // dans le cas II, il s'agit toujours de vrais eleves dans ce groupe !!
    $id_eleve =  $eleves[$p];
    $param=params_eleve($id_eleve);

    $liste_eleves .= "$id_eleve#";
    $liste_eleves_classe .= "$id_eleve#";

    // creation rep pour devoir et copies fichiers
    $rep= "/var/se3/Classes/".$param[classe]."/".inverse_login($id_eleve);
    $cr=1;
    if (($devoir) and ("$id_devoir"!="")){
      $rep .= "/$id_devoir";
      //$ch ="/usr/bin/sudo /usr/share/se3/scripts/creer_rep_distrib.sh $login $id_eleve $rep";
      $ch ="/usr/bin/sudo /usr/share/se3/scripts/creer_rep_distrib.sh $login $id_eleve \"$rep\"";
      $cr= exec($ch) ;
    }
    if ($cr) {
       $CR1=1;
       for ($i=0; $i< $nb_fichiers; $i++) {
         // boucle sur tous les fichiers a distribuer
         //$ch1 ="/usr/bin/sudo /usr/share/se3/scripts/copie_fich_distrib.sh $login $id_eleve $rep \"$fichiers[$i]\" ";
         $ch1 ="/usr/bin/sudo /usr/share/se3/scripts/copie_fich_distrib.sh $login $id_eleve \"$rep\" \"$fichiers[$i]\" $equipe "; // Modif delineau
         $cr1= exec($ch1) ;
         // on range $cr1 dans un tableau
         $tab_cr1[$i]=$cr1;
         $CR1 *= $cr1;
      }
     if ($CR1) {
         $im=($param["sexe"]=="F"?"<img src=\"../annu/images/gender_girl.gif\" width=14 height=14 hspace=3 border=0>":
         "<img src=\"../annu/images/gender_boy.gif\" width=14 height=14 hspace=3 border=0>");
         echo $im.$param["nom"]."<br>\n";
      }
       else {
         echo " ---> ".gettext("&#233;chec de la distribution pour ").$param["nom"]."<br>\n";
      }
     }
    else {
    echo "   ---> ".gettext("distribution d&#233;j&#224; effectu&#233;e pour ").$param["nom"]."<br>\n";
    // echo "<h4>Echec de la cr&#233;ation du r&#233;pertoire $rep</h4>";
    }
   }  // fin for $p
   echo "<p>";
   $tab_eleves[$classe]=$liste_eleves_classe ;         // <----  tableau des eleves a transformer en liste
  }  // fin for $g
}   // FIN envoi de type II

//Nettoyage apres la fin des envois:
//for ($i=1; $i<= $nombre; $i++) {
for ($i=0; $i< $nombre; $i++) {
  if(file_exists("/tmp/$login/$fichiers[$i]")){
    //echo "<p>unlink(\"/tmp/$login/$fichiers[$i]\")</p>";
    unlink("/tmp/$login/$fichiers[$i]");
  }
}

/* Traitement des envois de DEVOIRS
 **********************************/
if ("$id_devoir"!="") {
  if (trim($nom_devoir) =="" )  $nom_devoir="devoir";          // nom du devoir a rendre
  echo "<p>".gettext("Afin de pouvoir ramasser leur devoir, ces &#233;l&#232;ves devront l'enregistrer :")."<br>";
  echo gettext("- dans un fichier nomm&#233; <strong>")." $nom_devoir</strong> <br>";
  echo gettext("- en utilisant une casse quelconque")."<br>";
  echo gettext("- le nom &#233;tant muni de l'extension usuelle li&#233;e au type du fichier")."<p>\n";
}
//if ($devoir and $cr and $cr1) {
if ($devoir) {
  $date_retour=$an_retour."-".$mois_retour."-".$jour_retour;   // date de retour
  $liste_eleves=tab_liste($tab_eleves);                        // calcul de la liste classes-eleves
  // construction de la requete d'enregistrement du devoir
  $req_devoir="INSERT INTO $table ";
  $req_devoir .=" (id_prof,id_devoir,nom_devoir,date_distrib,date_recup,description,liste_distrib) ";
  $req_devoir .=" VALUES ('$id_prof','$id_devoir','$nom_devoir','$now','$date_retour','$description','$liste_eleves') ";
  $ok = mysqli_query($GLOBALS["___mysqli_ston"], $req_devoir);
 }
// else
// echo "Echec de distribution du devoir $devoir";
include ("pdp.inc.php");
?>

