<?php

   /**
   
   * barre du haut 
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs  jLCF >:>  jean-luc.chretien@tice.ac-caen.fr
   * @auteurs  oluve  olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs Olivier LECLUSE

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   
   */

   /**

   * @Repertoire: /
   * file: barre.php

  */	

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<title>Barre</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="elements/style_sheets/sambaedu.css" />
<script type="text/javascript" language="JavaScript">
<!--
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_findObj(n, d) { //v4.0
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && document.getElementById) x=document.getElementById(n); return x;
}

function MM_nbGroup(event, grpName) { //v3.0
  var i,img,nbArr,args=MM_nbGroup.arguments;
  if (event == "init" && args.length > 2) {
    if ((img = MM_findObj(args[2])) != null && !img.MM_init) {
      img.MM_init = true; img.MM_up = args[3]; img.MM_dn = img.src;
      if ((nbArr = document[grpName]) == null) nbArr = document[grpName] = new Array();
      nbArr[nbArr.length] = img;
      for (i=4; i < args.length-1; i+=2) if ((img = MM_findObj(args[i])) != null) {
        if (!img.MM_up) img.MM_up = img.src;
        img.src = img.MM_dn = args[i+1];
        nbArr[nbArr.length] = img;
    } }
  } else if (event == "over") {
    document.MM_nbOver = nbArr = new Array();
    for (i=1; i < args.length-1; i+=3) if ((img = MM_findObj(args[i])) != null) {
      if (!img.MM_up) img.MM_up = img.src;
      img.src = (img.MM_dn && args[i+2]) ? args[i+2] : args[i+1];
      nbArr[nbArr.length] = img;
    }
  } else if (event == "out" ) {
    for (i=0; i < document.MM_nbOver.length; i++) {
      img = document.MM_nbOver[i]; img.src = (img.MM_dn) ? img.MM_dn : img.MM_up; }
  } else if (event == "downn") {
    if ((nbArr = document[grpName]) != null)
      for (i=0; i < nbArr.length; i++) { img=nbArr[i]; img.src = img.MM_up; img.MM_dn = 0; }
    document[grpName] = nbArr = new Array();
    for (i=2; i < args.length-1; i+=2) if ((img = MM_findObj(args[i])) != null) {
      if (!img.MM_up) img.MM_up = img.src;
      img.src = img.MM_dn = args[i+1];
      nbArr[nbArr.length] = img;
  } }
}

function auth_popup() {
                                window.focus();
                               auth_popupWin = window.open("aide.php","auth_se3","scrollbars=yes,resizable=yes,width=700,height=400");
                               //auth_popupWin = window.open("aide.php","auth_se3","width=600,height=400");
                                auth_popupWin.focus();
                        }
//-->
</script>
</head>

<body bgcolor="#6699cc" text="#000000" onLoad="MM_preloadImages('elements/images/logo-clique.gif','elements/images/logo-dessus.gif','elements/images/logo-normal.gif','elements/images/bt1_clique2.gif','elements/images/bt1_dessus2.gif','elements/images/bt1_abaisse2.gif','elements/images/bt2_clique2.gif','elements/images/bt2_dessus2.gif','elements/images/bt2_abaisse2.gif','elements/images/bt3_clique2.gif','elements/images/bt3_dessus2.gif','elements/images/bt3_abaisse2.gif','elements/images/bt4_clique2.gif','elements/images/bt4_dessus2.gif','elements/images/bt4_abaisse2.gif','elements/images/slis_clique.gif','elements/images/slis_dessus.gif','elements/images/slis_abaisse.gif')">
<div id="Layer1" style="position:absolute; left:0px; top:0; height:85; z-index:1">
  <table width="100%" border="0"><!-- height="86"-->
    <tr>
      <!--td><a href="http://wwdeb.crdp.ac-caen.fr/mediase3/index.php/Accueil" TARGET="_new" onClick="MM_nbGroup('down','group1','logo','elements/images/logo-clique.gif',1)" onMouseOver="MM_nbGroup('over','logo','elements/images/logo-dessus.gif','elements/images/logo-normal.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="logo" src="elements/images/logo-normal.gif" border="0" onLoad="" height="85"></a></td-->
      <td><a href="http://wwdeb.crdp.ac-caen.fr/mediase3/index.php/Accueil" TARGET="_blank" onClick="MM_nbGroup('down','group1','logo','elements/images/logo-clique.gif',1)" onMouseOver="MM_nbGroup('over','logo','elements/images/logo-dessus.gif','elements/images/logo-normal.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="logo" src="elements/images/logo-normal.gif" border="0" alt="Logo" height="85" /></a></td>
      <td width="420">
        <table border="0" cellpadding="0" cellspacing="0" width="420"><!-- height="85"-->
          <tr>
            <td width="100"><a href="contact.php" TARGET="main" onClick="MM_nbGroup('down','group1','contact','elements/images/bt1_clique2.gif',1)" onMouseOver="MM_nbGroup('over','contact','elements/images/bt1_dessus2.gif','elements/images/bt1_abaisse2.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="contact" src="elements/images/bt1_normal.gif" border="0" alt="Contact" width="100" height="85" /></a></td>
            <td width="100"><a href="aide.php"  onClick='auth_popup(); return false' TARGET='_blank' onMouseOver="MM_nbGroup('over','docenligne','elements/images/bt2_dessus2.gif','elements/images/bt2_abaisse2.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="docenligne" src="elements/images/bt2_normal.gif" border="0" alt="Aide" /></a></td>
            <td width="100"><a href="logout.php" TARGET="page" onClick="MM_nbGroup('down','group1','deconnexion','elements/images/bt3_clique2.gif',1)" onMouseOver="MM_nbGroup('over','deconnexion','elements/images/bt3_dessus2.gif','elements/images/bt3_abaisse2.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="deconnexion" src="elements/images/bt3_normal.gif" border="0" alt="Deconnexion" /></a></td>      <!--td width="100"><a href="contact.php" TARGET="main" onClick="MM_nbGroup('down','group1','contact','elements/images/bt1_clique2.gif',1)" onMouseOver="MM_nbGroup('over','contact','elements/images/bt1_dessus2.gif','elements/images/bt1_abaisse2.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="contact" src="elements/images/bt1_normal.gif" border="0" onLoad="" width="100" height="85"></a></td>
            <td width="100"><a href="aide.php"  onClick='auth_popup(); return false' TARGET='_blank' onMouseOver="MM_nbGroup('over','docenligne','elements/images/bt2_dessus2.gif','elements/images/bt2_abaisse2.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="docenligne" src="elements/images/bt2_normal.gif" border="0" onLoad=""></a></td>
            <td width="100"><a href="logout.php" TARGET="page" onClick="MM_nbGroup('down','group1','deconnexion','elements/images/bt3_clique2.gif',1)" onMouseOver="MM_nbGroup('over','deconnexion','elements/images/bt3_dessus2.gif','elements/images/bt3_abaisse2.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="deconnexion" src="elements/images/bt3_normal.gif" border="0" onLoad=""></a></td-->
<?php
    require ("config.inc.php");
    require ("functions.inc.php");
    if ($lcsIp != "") echo "<td width=\"120\"><a href=\"http://$lcsIp\" TARGET=\"_new\" onClick=\"MM_nbGroup('down','group1','lcs','elements/images/bt4_clique2.gif',1)\" onMouseOver=\"MM_nbGroup('over','lcs','elements/images/bt4_dessus2.gif','elements/images/bt4_abaisse2.gif',1)\" onMouseOut=\"MM_nbGroup('out')\"><img name=\"lcs\" src=\"elements/images/bt4_normal.gif\" border=\"0\" onLoad=\"\"></a></td>";

if ($slisip != "" && $slis_url != "") echo "<td width=\"120\"><a href=\"$slis_url\" TARGET=\"_new\" onClick=\"MM_nbGroup('down','group1','slis','elements/images/slis_clique.gif',1)\" onMouseOver=\"MM_nbGroup('over','slis','elements/images/slis_dessus.gif','elements/images/slis_abaisse.gif',1)\" onMouseOut=\"MM_nbGroup('out')\"><img name=\"slis\" src=\"elements/images/slis_normal.gif\" border=\"0\" onLoad=\"\"></a></td>";
?>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</div>
</body>
</html>
