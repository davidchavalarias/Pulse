<?php
// Calcul les coordonnées des cluster en x,y pour tous les streams et les mets en base.

include("library/fonctions_php.php");
include("library/globepulse_library.php");
include("parametre.php");
$raphael=TRUE;
include("include/header.php");

$sqlite_database='alimsec_Africa.db';

$dbh = new PDO("sqlite:".$sqlite_database);    
    // calcul du nombre de clusters (à optimiser)
    $nbClusters=0;
    $sql = "SELECT * FROM clusters GROUP BY cluster_id AND period";;
    foreach ($dbh->query($sql) as $row)
        {
        $nbClusters+=1;
        }

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


$sqlite_database='alimsec_Africa.db';
//$sqlite_database='secalim_query_secalimandco.db';


// données de périodes

$period_uniques = array_unique($phylo_structure['period1']);
$timespan=max($all_period2)-min($all_period1);    
$period_min=min($all_period1);
$ytrans=0; // espace pour les noms de station
$raphael_height=200;
$branch_width=40;// espace entre les branches 
$screen_width=1000;
$total_nb_nodes=0;


//$phylo_structure=create_phylo_structure(1,$sqlite_database);    // importe la branche et calcule la spatialisation    

//$conn = new PDO("sqlite:positions.sdb");
//
//for ($i=0;$i<count($phylo_structure['cluster_id']);$i++){    
//        // calcul du nombre de clusters (à optimiser)
//        $sql = "INSERT INTO spatialization (cluster_univ_id,x_pos_phylo,y_pos_phylo) VALUES ('".
//        $phylo_structure['period1'][$i]."_".$phylo_structure['period2'][$i]."_".$phylo_structure['cluster_id'][$i]."',".($phylo_structure['x'][$i]-$period_min)*1/$timespan."*(".$screen_width."-60)+40,".$ytrans.'+('.$branch_width.')*'.(($phylo_structure['y'][$i]-1)).")";      
//        $conn ->exec($sql); 
//        pt($sql);
//        
//}


//phylo_plot($phylo_structure,$ytrans,$timespan,$period_min,$branch_width);  

    
/////////////////////////////////////////////
// Script de visu avec raphael
///////////////////////////////////////////


// on calcule la spatialisation de chaque branche et on la place
echo '
<script type="text/javascript" charset="utf-8">

        window.onload = function () {
            var R = Raphael("metro",'.$screen_width.',800), x ='.$screen_width.', y ='.$raphael_height.', r = 5;
            d=200;            
            ';

for ($k=0;$k<count($stream_id);$k++){
    $phylo_structure=create_phylo_structure($stream_id[$k],$sqlite_database);    // importe la branche et calcule la spatialisation    
    phylo_plot($phylo_structure,$ytrans,$timespan,$period_min,$branch_width,$sqlite_database,$screen_width);    // génère le code raphael    
    $ytrans=$trans+(2+max($phylo_structure['y']))*$branch_width;
}


echo '
        };
    </script>';


echo '<div id="metro"></div>
    </body>';




?>
