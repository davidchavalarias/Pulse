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
$suprathematiques_label=array();

$stream_info=array();
    $sql = "SELECT suprathematique,suprathematique_label,stream_id,count(*) FROM clusters GROUP BY stream_id order by suprathematique";;
    foreach ($dbh->query($sql) as $row)
        {       
        $stream_info[$row['stream_id']]=$row['suprathematique'];
        $suprathematiques_label[$row['suprathematique']]=$row['suprathematique_label'];
        }
$stream_id=array_keys($stream_info);
        
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
$nb_bigstream=0;
$suprathematique=0;
for ($k=0;$k<count($stream_id);$k++){
    $phylo_structure[$stream_id[$k]]=create_phylo_structure($stream_id[$k],$sqlite_database);    // importe la branche et calcule la spatialisation           
    if (count($phylo_structure[$stream_id[$k]]['cluster_univ_id'])>$small_stream_filter){        
        $nb_bigstream+=1;
        $ytrans[$k+1]=$ytrans[$k]+(max($phylo_structure[$stream_id[$k]]['y'])-1)*$branch_width;
        if ($suprathematique!=$stream_info[$k]){
            $ytrans[$k+1]=$ytrans[$k+1]+$suprathematique_margin;
            $suprathematique=$stream_info[$k];
        }
    }else{
        $ytrans[$k+1]=$ytrans[$k];
    }
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
            var R = Raphael("metro",'.$screen_width.','.($ytrans[$nb_bigstream]+20).'), r = 8;
            d=200;            
            ';
$suprathematique=0;
for ($k=0;$k<count($stream_id);$k++){
    if (count($phylo_structure[$stream_id[$k]]['cluster_univ_id'])>$small_stream_filter){
//        if ($suprathematique!=$stream_info[$k]){
//            echo 'var streamlabel = R.text(10,'.($ytrans[$k]-$suprathematique_margin/2).',"' . $suprathematiques_label[$suprathematique].'");                
//            streamlabel.attr({ "text-anchor":"start","font-size":10,"font-weight":"bold","fill":"grey"});        
//            ';  
//            $suprathematique=$stream_info[$k];
//        }
        
            phylo_plot($phylo_structure[$stream_id[$k]],$ytrans[$k],$timespan,$period_min,$branch_width,$sqlite_database,$screen_width,$r_global);    // génère le code raphael    
    }
}


echo '
        };
    </script>';

echo '<div align=center><font size="6" face="arial" color="grey">Liste des thématiques</font>';
echo '<div id="metro" align=center></div>
    </body>';




?>
