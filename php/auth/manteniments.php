<?php
if($username=='xalvarez'){
ini_set('display_errors', 'On');
error_reporting(E_ALL);
}


require_once './init_cas_connect.php';
require_once './init_database_connect.php';

$db='';
$op=-1;
$oo=0;
$cad_link_sn='';
$cad_link_mant='';
$val=[];
$niu=0;
$acm=0;
if($_GET['db']){
	$db=$_GET['db'];
	$cad_link_sn=$cad_link_sn.'?db='.$db;
}
if($_GET['oo']){
	$oo=(int)$_GET['oo'];
	$cad_link_sn=$cad_link_sn.'&oo='.$oo;
	$cad_link_mant=$cad_link_mant.'&oo='.$oo;
}
if($_GET['op']){
	$op=(int)$_GET['op'];
	$cad_link_sn=$cad_link_sn.'&op='.$op;
	$cad_link_mant=$cad_link_mant.'&op='.$op;
}
if($_GET['go']){
	$go=(int)$_GET['go'];
	$cad_link_sn=$cad_link_sn.'&go='.$go;
	$cad_link_mant=$cad_link_mant.'&go='.$go;
}
if($_GET['gp']){
	$gp=(int)$_GET['gp'];
	$cad_link_sn=$cad_link_sn.'&gp='.$gp;
	$cad_link_mant=$cad_link_mant.'&gp='.$gp;
}
if($_GET['val']){
	$val=$_GET['val'];
	for($i=0;$i<count($val);$i++){
		$cad_link_mant=$cad_link_mant.'&val[]='.$val[$i];
		$cad_link_sn=$cad_link_sn.'&val[]='.$val[$i];
	}
}
if($_GET['src']){
	$src=(int)$_GET['src'];
}
if($_GET['niu']){
	$niu=$_GET['niu'];
}
if($_GET['acm']){
	$acm=$_GET['acm'];
}
if($_GET['ac']){
	$ac=$_GET['ac'];
	$accio=$_GET['ac'];
#	$accio=$ac;
}

require_once './init_fields_list.php';
require_once './init_accions_manteniment.php';

#$user_admin_db=1;
?>

<html>
<head>
		<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
		<title><?php echo $camps['nom_taula'];?> (PD-FIS)</title>
		<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

		<script>
		(function (w, doc,co) {
			var u = {},
				e,
				a = /\+/g,  // Regex for replacing addition symbol with a space
				r = /([^&=]+)=?([^&]*)/g,
				d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
				q = w.location.search.substring(1),
				v = '2.0.3';

			while (e = r.exec(q)) {
				u[d(e[1])] = d(e[2]);
			}

			if (!!u.jquery) {
				v = u.jquery;
			}

			doc.write('<script src="https://ajax.googleapis.com/ajax/libs/jquery/'+v+'/jquery.min.js">' + "<" + '/' + 'script>');
			co.log('\nLoading jQuery v' + v + '\n');
		})(window, document, console);
		</script>
		<script src="jquery.quicksearch.js"></script>
		<script>
			$(function () {
				/*
				Example 1
				*/
				$('input#id_search').quicksearch('table#table_example tbody tr');
			});
		</script>
<script>
function setScroll() {
    let scroll = window.scrollY;
    let scrollString = scroll.toString();
    localStorage.setItem("scrollPosition", scrollString);
}


function restoreScrollPos() {
    let posYString = localStorage.getItem("scrollPosition");
    let posY = parseInt(posYString);
    window.scroll(0, posY);
    return true;
}
</script>
</head>
<?php if($_POST['g_pos']){ ?>
<body  onload="restoreScrollPos()">
<?php }else{ ?>
 <body>
<?php } ?>
<?php

$titol='';
if ($result = $mysqli->query($query)) {
	if($row = $result->fetch_assoc()){
		if(count($val)>0){
			for($j=0;$j<count($val);$j++){
			for($i=0;$i<count($camps['where']);$i++){

				if($camps['where'][$i]==($j+1)){
					$titol=$titol."(".$val[$j].") - ";
					$titol=$titol.$row[$camps['expl_fields'][$i]];
				}
			}
			}
		}
	}
}

if($camps['menu_info'][0]==1 or $camps['menu_info'][0]==12){
	$color='w3-green';
	$title='Manteniments';
}else if($camps['menu_info'][0]==2 or $camps['menu_info'][0]==22){
	$color='w3-blue';
	$title='Gestió del pla docent';
}else if($camps['menu_info'][0]==3 or $camps['menu_info'][0]==32 or $camps['menu_info'][0]==33){
	$color='w3-orange';
	$title='Anàlisi del pla docent';
}else if($camps['menu_info'][0]==4 or $camps['menu_info'][0]==42){
	$color='w3-red';
	$title='';
}else if($camps['menu_info'][0]==5 or $camps['menu_info'][0]==52){
	$color='w3-brown';
	$title='Comparació amb encàrrec docent';
}else if($camps['menu_info'][0]==6 ){
	$color='w3-brown';
	$title='Llistats';
}else if($camps['menu_info'][0]==7 ){
	$color='w3-blue';
	$title='Revisió del pla docent';
}else if($camps['menu_info'][0]==8 ){
	$color='w3-blue';
	$title='Revisió informació base de dades';
}else if($camps['menu_info'][0]==9 ){
	$color='w3-deep-purple';
	$title='Pla Docent Definitiu';
}

echo '<header class="w3-container '.$color.' w3-center">';
echo '<h1>'.$title.'</h1>';

if($camps['menu_info'][0]==1){
    echo '<h2>'.$camps['nom_taula'].'</h2>';
}
if($camps['menu_info'][0]==2 or $camps['menu_info'][0]==22 or $camps['menu_info'][0]==3 or $camps['menu_info'][0]==5 or $camps['menu_info'][0]==7252){
    echo '<h2>'.$camps['nom_taula'].'</h2>';
	if($titol!=''){
    	echo '<h2>'.$titol.'</h2>';
	}

}
echo '</header>';
?>


<div class="w3-bar w3-yellow W3-CENTER">
<?php
  for($i=0;$i<count($full_list);$i++){
	if($full_list[$i]['menu_info'][0]==$camps['menu_info'][0] and $camps['menu_info'][0]!=22){
  		echo '<a href="manteniments.php?db='.$full_list[$i]['menu_info'][2].'" class="w3-bar-item w3-button">'.$full_list[$i]['menu_info'][1].'</a>';
	}
  }
  if($user_admin_db){
  echo '<a href="menu.php" class="w3-bar-item w3-button w3-right">MENU PRINCIPAL</a>';
  }
?>
</div>

<?php
if($camps['menu_info'][0]==1){
	echo '<div class="w3-bar w3-green">';
}
if($camps['menu_info'][0]==2){
	echo '<div class="w3-bar w3-blue">';
}
if($camps['menu_info'][0]==3){
	echo '<div class="w3-bar w3-orange">';
}
#if($camps['menu_info'][0]==9){
#	echo '<div class="w3-bar w3-magenta">';
#}

?>

<div class="w3-bar <?php echo $color; ?>">
<?php if($camps['menu_info'][0]==1 or ($camps['menu_info'][0]==33 and $camps['menu_info'][2]=='PR_BDP') or ($camps['menu_info'][0]==33 and $camps['menu_info'][2]=='PR2_BDP') or ($camps['menu_info'][0]==33 and $camps['menu_info'][2]=='AS_BDP') or ($camps['menu_info'][0]==3 and $camps['menu_info'][2]=='GRASTIP')){
?>
  <li class="w3-bar-item w3-button w3-left"><a href="fitxa.php?ac=2&db=<?php echo $db; ?>" class="w3-bar-item w3-button">AFEGIR</a></li>
  <li class="w3-bar-item w3-button w3-left <?php if($ac==2){echo "w3-blue";} ?>"><a href="manteniments.php<?php echo $cad_link_sn; if($ac!=2){echo "&ac=2";} ?>" class="w3-bar-item w3-button">MODIFICAR</a></li>
  <li class="w3-bar-item w3-button w3-left <?php if($ac==3){echo "w3-blue";} ?>"><a href="manteniments.php<?php echo $cad_link_sn; if($ac!=3){echo "&ac=3";} ?>" class="w3-bar-item w3-button">ELIMINAR</a></li>

<?php
}
?>
<li class="w3-bar-item w3-button w3-left <?php if($ac==4){echo "w3-blue";} ?>"><a href="./manteniments_llistat.php?db=<?php echo $db;?>" class="w3-bar-item w3-button">IMPRIMIR</a></li>
<li class="w3-bar-item w3-button w3-left <?php if($ac==4){echo "w3-blue";} ?>"><a href="./manteniments_llistat_csv.php?db=<?php echo $db;?>" class="w3-bar-item w3-button">CSV</a></li>	
  <li class="w3-bar-item w3-button w3-right">BUSCAR <input type="text" name="search" value="<?php echo $src;?>" id="id_search" placeholder="Search" autofocus=""></li>
</div>



<?php
echo "\xA";
echo "<div class='w3-responsive w3-white'><br>";
echo '<table id="table_example"  class="w3-table w3-striped w3-hoverable w3-border w3-card">';
echo "<thead>";
echo '<tr class="w3-black">';
for($i=0;$i<count($camps['select']);$i++){
	if($camps['select'][$i]!=0){
 		echo '<th style="padding: 8; margin:0; overflow: hidden;';
		if($camps['list_visible'][$i]==0 or ($camps['field_type'][$i]==100 and $user_admin_db==0)){
			echo ' display: none;';
		}
		echo '">';
		if($camps['ordre'][$i]===0){
			echo '<a href="manteniments.php?db='.$db.'&op='.($i+1).'&oo=1">(O)';
		}
		if($camps['ordre'][$i]===1){
			echo '<a href="manteniments.php?db='.$db.'&op='.($i+1).'&oo=2">(O ASC)';
		}
		if($camps['ordre'][$i]===2){
			echo '<a href="manteniments.php?db='.$db.'&op='.($i+1).'&oo=1">(O DESC)';
		}
		echo '</a>';
		if($camps['group'][$i]===0){
			echo '<a href="manteniments.php?db='.$db.'&gp='.($i+1).'&go=1">(G)';
		}
		if($camps['group'][$i]===1){
			echo '<a href="manteniments.php?db='.$db.'&gp='.($i+1).'&go=2">(G ASC)';
		}
		if($camps['group'][$i]===2){
			echo '<a href="manteniments.php?db='.$db.'&gp='.($i+1).'&go=1">(G DESC)';
		}
		echo '</a>';

		echo "</th>";
	}
}
if($ac==3 or $ac==2){
    echo '<th></th>';
}

#echo $query;

echo "</tr>\xA";

echo '<tr class="w3-black">';
for($i=0;$i<count($camps['select']);$i++){
	if($camps['select'][$i]!=0){
 		echo '<th style="padding: 8; margin:0; overflow: hidden;';
		if($camps['list_visible'][$i]==0 or ($camps['field_type'][$i]==100 and $user_admin_db==0)){
			echo ' display: none;';
		}
		echo '">';
		if($camps['ordre'][$i]===0){
			echo '<a href="manteniments.php?db='.$db.'&op='.($i+1).'&oo=1">';
		}
		if($camps['ordre'][$i]===1){
			echo '<a href="manteniments.php?db='.$db.'&op='.($i+1).'&oo=2">';
		}
		if($camps['ordre'][$i]===2){
			echo '<a href="manteniments.php?db='.$db.'&op='.($i+1).'&oo=1">';
		}
		echo $camps['encap'][$i];
		echo '</a>';
		echo "</th>";
	}
}
if($ac==3 or $ac==2){
    echo '<th>ACCIÓ</th>';
}

	 
echo "</tr>\xA";
echo "</thead>";
echo "<tbody>";
	
$guarda_grup='X';
$acum_grups=array (0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
$num_elements_agrupament=0;
for($i=0;$i<count($camps['group']);$i++){
	if($camps['group'][$i]!=0){
			$num_elements_agrupament+=1;
	}
}

if ($result = $mysqli->query($query)) {
    while ($row = $result->fetch_assoc()) {
		$grup='';
		$nom_grup='';
		$vis_cols=0;
		for($i=0;$i<count($camps['group']);$i++){
			if($camps['group'][$i]!=0){
				$grup=$grup.$camps['encap'][$i].": ".$row[$camps['alias'][$i]];
				#$nom_grup=$nom_grup.$camps['encap'][$i].": ".$row[$camps['alias'][$i]];
				$nom_grup=$nom_grup.$row[$camps['alias'][$i]];
				if($camps['expl_fields'][$i]!=''){
					$nom_grup=$nom_grup." (".$row[$camps['expl_fields'][$i]].")";
				}
			}

			if($camps['list_visible'][$i]==1 or ($camps['field_type'][$i]==100 and $user_admin_db<>0)){
				$vis_cols+=1;
			}
		}
		if($ac==3 or $ac==2){
				$vis_cols+=1;
		}

		if($grup!=$guarda_grup and $num_elements_agrupament>0){
			if($guarda_grup!='X'){
				echo '<tr class="w3-green"  style="font-weight: bold; padding: 0; margin:0; overflow: hidden;';
				echo '">';
				for($i=0;$i<count($camps['acumulats']);$i++){
					echo '<td';
					if($camps['list_visible'][$i]==0 or ($camps['field_type'][$i]==100 and $user_admin_db==0) or ($camps['field_type'][$i]==90 and ($user_admin_unit==0 and $user_admin_db==0))){
						echo ' style="display: none; 90"';
					}
					echo '>';
					if($i==1){
						echo "<div hidden='hidden'>";
						echo $guarda_nom_grup;
						echo "</div>";
					}

					if($camps['acumulats'][$i]!=0){
						echo $acum_grups[$i];
					}
					echo "</td>";

				}
				if($ac==3 or $ac==2){
					echo '<td></td>';
				}

				echo '</tr>';
				echo '<tr class="w3-white" height=100><td></td></tr>';
			}
			$guarda_nom_grup=$nom_grup;
			$guarda_grup=$grup;
			echo '<tr class="w3-blue-gray" style="font-size:1.5vw">';
			echo "<td colspan='";
			echo $vis_cols;
			echo "'>";
			echo $nom_grup;
			echo "</td>";
			echo "</tr>";
			$acum_grups=array (0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
		}
		#echo '<tr class="w3-red">';
		if($row['DIF']!="DIF"){
			echo '<tr class="w3-hover-green">';
		}else{
 			echo '<tr class="w3-red">';
		}

		for($i=0;$i<count($camps['select']);$i++){
			if($camps['select'][$i]!==0){
				echo '<td';
				if($camps['list_visible'][$i]==0 or ($camps['field_type'][$i]==100 and $user_admin_db==0)  or ($camps['field_type'][$i]==90 and ($user_admin_unit==0 and $user_admin_db==0)) ){
					echo ' style="display: none;"';
				}
				echo '>';
				if($camps['link_fields'][$i]!=''){
					echo '<a href="'.$row[$camps['link_fields'][$i]].'">';
				}
				if($camps['field_type'][$i]==1){
					echo $row[$camps['alias'][$i]];
				}
				if($camps['field_type'][$i]==15){
					echo '<b>'.$row[$camps['alias'][$i]].'</b>';
				}
				if($camps['field_type'][$i]==10 or $camps['field_type'][$i]==100 or $camps['field_type'][$i]==90){
					#echo '<form method="POST" action="'.$row[$camps['link_fields'][$i]].$cad_link_mant.'">';
					echo '<form method="POST" action="'.$row[$camps['link_fields'][$i]].'">';
					echo '<input type="hidden" name="g_pos" value="1">';
					#echo '<div class="w3-yellow w3-button" onclick="setScroll()">'.$row[$camps['alias'][$i]].'</div>';
					echo '<input type="submit" class="w3-yellow w3-button" onclick="setScroll()" value="'.$row[$camps['alias'][$i]].'">';
					#echo '<input type="image" src="cancel.png" style="width:20px;height:20px;" onclick="setScroll()">';
					echo '</form>';
				}
				if($camps['field_type'][$i]==2){
					if($row['NIU_PROFESSOR']==$user_niu or ($user_admin_unit==1 or $user_admin_db==1)){
					echo '<form method="POST" action="manteniments.php'.$cad_link_sn.'">';
					echo '<input type="hidden" name="codi" value="'.$row[$camps['alias'][0]].'">';
					echo '<input type="hidden" name="accio" value=5>';
					echo '<input type="hidden" name="item_list" value='.$i.'>';
					if($row[$camps['alias'][$i]]==1){
						echo '<input type="hidden" name="sn_value" value="0">';
						echo '<input type="hidden" name="g_pos" value="1">';
						echo '<input type="image" src="ok.png" style="width:20px;height:20px;" onclick="setScroll()">';
					}else{
						echo '<input type="hidden" name="sn_value" value="1">';
						echo '<input type="hidden" name="g_pos" value="1">';
						echo '<input type="image" src="cancel.png" style="width:20px;height:20px;" onclick="setScroll()">';									}
					echo '</form>';
#					echo $row['NIU_PERS'];
#					echo $niu;
					}
				}
				if($camps['field_type'][$i]==22){
					if($row['NIU_PROFESSOR']==$user_niu or ($user_admin_unit==1 or $user_admin_db==1)){
					echo '<form method="POST" action="manteniments.php'.$cad_link_sn.'">';
					echo '<input type="hidden" name="codi" value="'.$row[$camps['alias'][0]].'">';
					echo '<input type="hidden" name="condicio" value="'.$row[$camps['link_fields'][$i]].'">';
					echo '<input type="hidden" name="accio" value=55>';
					echo '<input type="hidden" name="item_list" value='.$i.'>';
					if($row[$camps['alias'][$i]]==1){
						echo '<input type="hidden" name="sn_value" value="0">';
						echo '<input type="hidden" name="g_pos" value="1">';
						echo '<input type="image" src="ok.png" style="width:20px;height:20px;" onclick="setScroll()">';
					}else{
						echo '<input type="hidden" name="sn_value" value="1">';
						echo '<input type="hidden" name="g_pos" value="1">';
						echo '<input type="image" src="cancel.png" style="width:20px;height:20px;" onclick="setScroll()">';									}
					echo '</form>';
#					echo $row['NIU_PERS'];
#					echo $niu;
					}
				}

				if($camps['field_type'][$i]==3){
					if(floatval($row[$camps['alias'][$i]])>1){
						$pcnt=100;
					}else{
						$pcnt=floatval($row[$camps['alias'][$i]])*100;
					}
					echo '<div style="height: 20px;width: '.number_format($pcnt,1).'px;Background-color: #9f9;">'.number_format($pcnt,1).'%</div>';

				}
				if($camps['field_type'][$i]==4){
					echo '<form method="POST" action="manteniments.php'.$cad_link_sn.'">';
					echo '<input type="hidden" name="accio" value="3">';
					echo '<input type="hidden" name="codi" value="'.$row[$camps['alias'][0]].'">';
					echo '<input type="hidden" name="item_list" value='.$i.'>';
					if($row[$camps['alias'][$i]]=="M"){
						echo '<input type="hidden" name="estat" value="V">';
						echo '<input type="image" src="bm.png" style="width:20px;height:20px;" title="'.$row['MODIFICACIONS'].'">';
					}elseif($row[$camps['alias'][$i]]=="V") {
						echo '<input type="hidden" name="estat" value="N">';
						echo '<input type="image" src="bv.png" style="width:20px;height:20px;">';
					}else{
						echo '<input type="hidden" name="estat" value="M">';
						echo '<input type="image" src="bn.png" style="width:20px;height:20px;">';
					}
					echo '</form>';
				}
				if($camps['field_type'][$i]==44){
					echo '<form method="POST" action="manteniments.php'.$cad_link_sn.'">';
					echo '<input type="hidden" name="accio" value="333">';
					echo '<input type="hidden" name="id_grup" value="'.$row['ID_GRUP'].'">';
					echo '<input type="hidden" name="item_list" value='.$i.'>';
					if($row[$camps['alias'][$i]]=="M"){
						echo '<input type="hidden" name="estat" value="V">';
						echo '<input type="image" src="bm.png" style="width:20px;height:20px;" title="'.$row['MODIFICACIONS'].'">';
					}elseif($row[$camps['alias'][$i]]=="V") {
						echo '<input type="hidden" name="estat" value="N">';
						echo '<input type="image" src="bv.png" style="width:20px;height:20px;">';
					}else{
						echo '<input type="hidden" name="estat" value="M">';
						echo '<input type="image" src="bn.png" style="width:20px;height:20px;">';
					}
					echo '</form>';
				}
				
				if($camps['field_type'][$i]==5){
					echo "<a href='fitxa.php?db=".$db."&val[]=".$row[$camps['alias'][0]]."&ac=1'>";
					if($row[$camps['alias'][$i]]!=''){
						echo '<input type="image" src="bi.png" style="width:20px;height:20px;" title="'.$row[$camps['alias'][$i]].'">';
					}else{
						echo '<input type="image" src="big.png" style="width:20px;height:20px;" title="'.$row[$camps['alias'][$i]].'">';
					}
					echo '</a>';
				}
				if($camps['field_type'][$i]==7){
					echo '<form method="POST" action="manteniments.php'.$cad_link_sn.'">';
					echo '<input type="hidden" name="codi" value="'.$row[$camps['alias'][0]].'">';
					echo '<input type="hidden" name="niup" value="'.$row[$camps['alias'][1]].'">';
					echo '<input type="hidden" name="accio" value=7>';
					echo '<input type="hidden" name="item_list" value='.$i.'>';
					if($row[$camps['alias'][$i]]==1){
						echo '<input type="hidden" name="sn_value" value=1>';
						echo '<input type="image" src="responsable.png" style="width:30px;height:30px;" onclick="setScroll()">';
					}else{
						echo '<input type="hidden" name="sn_value" value=0>';
						echo '<input type="image" src="responsable_g.png" style="width:30px;height:30px;" onclick="setScroll()">';
					}
					echo '</form>';
				}

				if($camps['field_type'][$i]==8){
					echo '<input type="image" src="mail_icon.jpg" style="width:30px;height:30px;" onclick="setScroll()">';
				}
				if($camps['field_type'][$i]==9){
					#echo "<a href='".$camps['link_field'][$i]."'>";
					echo '<input type="image" src="info_card.jpg" style="width:30px;height:30px;" onclick="setScroll()">';
					#echo "</a>";
				}

				if($camps['link_fields'][$i]!=''){
					echo "</a>";
				}


				if($camps['expl_fields'][$i]!=''){
					#echo $camps['expl_fields'][$i];
					echo " (".$row[$camps['expl_fields'][$i]].")";
				}
				echo "</td>";

				if($camps['acumulats'][$i]!==0){
					$acum_grups[$i]+=floatval($row[$camps['alias'][$i]]);
				}
			}
		}

		if($ac==3){
	    	echo '<td>';
			$linkmod="manteniments.php?db=".$db;
			for($i=0;$i<count($camps['form_visible']);$i++){
				if($camps['form_visible'][$i]==2){
					$linkmod=$linkmod."&val[]=".$row[$camps['alias'][$i]];
				}
			}
			echo '<form method="POST" action="'.$linkmod.'&ac=4">';
			#echo '<form method="POST" action="manteniments.php?db='.$db.'">';
			#echo '<input type="hidden" name="codi" value="'.$row[$camps['alias'][0]].'">';
			echo '<input type="hidden" name="accio" value=4>';
			echo '<input type="submit" value="ELIMINAR '.$row[$camps['alias'][0]].'">';
			echo '</form>';
	    	echo '</td>
			';
		}
		if($ac==2){
	    	echo '<td>';
			#echo '<form method="POST" action="fitxa.php?db='.$db.'&codi='.$row[$camps['alias'][0]].'&ac=1">';
			$linkmod="fitxa.php?db=".$db;
			for($i=0;$i<count($camps['form_visible']);$i++){
				if($camps['form_visible'][$i]==2){
					$linkmod=$linkmod."&val[]=".$row[$camps['alias'][$i]];
				}
			}
			echo '<form method="POST" action="'.$linkmod.'&ac=1">';
			echo '<input type="submit" value="MODIFICAR '.$row[$camps['alias'][0]].'">';
			echo '</form>';
	    	echo '</td>
			';
		}

		echo "</tr>\xA";
	}
}
if($num_elements_agrupament>0){
echo '<tr class="w3-green">';
for($i=0;$i<count($camps['acumulats']);$i++){
		echo '<td';
		if($camps['list_visible'][$i]==0 or ($camps['field_type'][$i]==100 and $user_admin_db==0)  or ($camps['field_type'][$i]==90 and ($user_admin_unit==0 and $user_admin_db==0))){
			echo ' style="display: none;"';
		}
		echo '>';
		if($camps['acumulats'][$i]!=0){
			echo $acum_grups[$i];
		}
		echo "</td>";

}
if($ac==3 or $ac==2){
	echo '<td></td>';
}

echo '</tr>';
}

echo "</tbody>";
echo "</table>";
echo "</div>";
#echo $query;
?>

</body>
</html>
