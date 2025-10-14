<?php

//First Block- Database Information
$servername = "localhost:3306";
$username = "xalvarez";
$password = "tY6x9#y4";
$database = "c0000182_pla_docent";

$mysqli = new mysqli($servername, $username, $password, $database);
if (!$mysqli->set_charset("utf8")) {
      printf("Error loading character set utf8: %s\n", $mysqli->error);
} else {
      #printf("Current character set: %s\n", $mysqli->character_set_name());
}

$query_prof = "select * from professors WHERE PERS_NIU='".$user_niu."'";

if ($result_prof = $mysqli->query($query_prof)) {
	#if ($result_prof = $mysqli->query($query_prof)){
		$row_list = $result_prof->fetch_assoc();
		$user_admin_unit = $row_list["admin_unit"];
		$user_admin_db = $row_list["admin_db"];
		$user_modif = $row_list["MODIF"];
		$user_validacio = $row_list["VALID"];
    	$user_unit = $row_list["CODI_UNITAT"];
		$user_mail = $row_list["PERS_MAIL"];
		$user_name = $row_list["PERS_NOMBRE"];	
		$user_cognom = $row_list["PERS_APELLIDO1"];		
	#}
}

if($user_admin_db==0){
  $query_params = "select * from general_parameters WHERE code=1";
  if ($result_params = $mysqli->query($query_params)) {
  	#if ($result_prof = $mysqli->query($query_prof)){
  		$row_params = $result_params->fetch_assoc();
      $web_state = $row_params["web_state"];
      if($web_state==1){
        header('Location: //fisica-administracio.uab.cat/imatges/gracies.png');
      }
  }
}

?>
