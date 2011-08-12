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

$id_partition=1;
$sqlite_database='alimsec_Africa.db';
//$sqlite_database='secalim_query_secalimandco.db';

$ymax=max($phylo_structure['y']);

// données de périodes
$phylo_structure=create_phylo_structure($id_partition,$sqlite_database);

$period_uniques = array_unique($phylo_structure['period1']);
$timespan=max($period_uniques)-min($period_uniques);    
$period_min=min($period_uniques);
$dt=min($phylo_structure['period2'])-min($period_uniques);
$ytrans=0; // espace pour les noms de station
$raphael_height=200;
$branch_width=40;// espace entre les branches 

$total_nb_nodes=0;



//phylo_plot($phylo_structure,$ytrans,$timespan,$period_min,$branch_width);  

    
/////////////////////////////////////////////
// Script de visu avec raphael
///////////////////////////////////////////

echo '
<script type="text/javascript" charset="utf-8">

        window.onload = function () {
            var R = Raphael("metro",1100,800), x =1100, y ='.$raphael_height.', r = 5;
            d=200;            
            ';

for ($k=0;$k<count($stream_id);$k++){
    $phylo_structure=create_phylo_structure($stream_id[$k],$sqlite_database);    
    
    phylo_plot($phylo_structure,$ytrans,$timespan,$period_min,$branch_width);        
    $ytrans=$trans+(2+max($phylo_structure['y']))*$branch_width;
}


echo '
        };
    </script>';


echo '<div id="metro"></div>
    </body>';




?>
