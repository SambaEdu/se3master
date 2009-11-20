if(window.XMLHttpRequest) // FireFox
	xhr_object = new XMLHttpRequest();
else if(window.ActiveXObject) // IE
	xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
else { // Non supporte
	alert("Pas supporté par le navigateur");
	return;
}	

xhr_object.open("POST","ajaxphp.php", true);
xhr_object.onreadystatechange = function() {
	if(xhr_object.readyState == 4)
		eval(xhr_object.responseText);
}
xhr_object.setRequestHeader("Content-type", "application/x-www-form-urlencode");
var data = "";
xhr_object.send(data);
