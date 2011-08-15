<?php
// Calcul les coordonnées des cluster en x,y pour tous les streams et les mets en base.
// donne une représentation des streams avec Raphael


include("library/fonctions_php.php");
include("library/globepulse_library.php");
include("parametre.php");
$raphael=TRUE;
include("include/header.php");




//////// pré calculs ////////
$dbh = new PDO("sqlite:".$sqlite_database);    

$stream_size=array();
    $sql = "SELECT stream_id,count(*) FROM clusters GROUP BY stream_id";;
    foreach ($dbh->query($sql) as $row)
        {       
        $stream_size[$row['0']]=$row['1'];
        }
$stream_id=array_keys($stream_size);


        
///////////////// Module pour préparer la visu de phylo en Raphael
$all_period1=array();
$all_period2=array();

$sql = "SELECT period FROM clusters";;
    foreach ($dbh->query($sql) as $row)
        {       
        $per=  split('_', $row[period]);
        $all_period1[]=$per[0];
        $all_period2[]=$per[1];
        }

$dbh=NULL;

// données de périodes

$timespan=max($all_period2)-min($all_period1);    
$period_min=min($all_period1);
$ytrans=array();
$ytrans[]=0; // espace pour les noms de station
$phylo_structure=array(); // ensemble des branches phylogénétiques

// on calcul la taille de la feuille raphael ainsi que les phylos
for ($k=0;$k<count($stream_id);$k++){
    $phylo_structure[$stream_id[$k]]=create_phylo_structure($stream_id[$k],$sqlite_database);    // importe la branche et calcule la spatialisation        
    $ytrans[$k+1]=$ytrans[$k]+(max($phylo_structure[$stream_id[$k]]['y'])-1)*$branch_width;
}

//$phylo_structure=create_phylo_structure(11,$sqlite_database);    // importe la branche et calcule la spatialisation        
//phylo_plot($phylo_structure,$ytrans,$timespan,$period_min,$branch_width,$sqlite_database,$screen_width);  

    
/////////////////////////////////////////////
// Script de visu avec raphael
///////////////////////////////////////////


// on calcule la spatialisation de chaque branche et on la place
echo '
<script type="text/javascript" charset="utf-8">

        window.onload = function () {
            var R = Raphael("metro",'.$screen_width.','.($ytrans[count($stream_id)]).'), r = 5;
            d=200;            
            ';

for ($k=0;$k<count($stream_id);$k++){       
    phylo_plot($phylo_structure[$stream_id[$k]],$ytrans[$k],$timespan,$period_min,$branch_width,$sqlite_database,$screen_width);    // génère le code raphael    
}


echo '
        };
    </script>';


echo '<div id="metro"></div>
    </body>';




?>
