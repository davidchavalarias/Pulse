<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// cette fonction  affiche pour chaque stream, par ordre chronologique et par cluster
// le titre de l'article le plus pertinent
include("library/fonctions_php.php");
include("library/globepulse_library.php");
include("parametre.php");
$raphael=TRUE;
include("include/header.php");


//////// pré calculs ////////
$dbh = new PDO("sqlite:".$sqlite_database);    
$stream_size=array();

// liste des streams
    $sql = "SELECT stream_id,count(*) FROM clusters GROUP BY stream_id";;
    foreach ($dbh->query($sql) as $row)
        {       
        $stream_size[$row['0']]=$row['1'];
        }
$stream_id=array_keys($stream_size);

print_r($stream_id);
// pour chaque stream
foreach ($stream_id as $stream){
    $periods=array();
    pt('Stream number '.$stream);
    //on fait la liste des périodes par ordre croissant
    $sql = "SELECT period,cluster_univ_id FROM clusters where stream_id=".$stream." group by period";;
    foreach ($dbh->query($sql) as $row)
        {       
        $periods[]=$row['period'];
        }
    usort($periods, "cmp");
    
    pta($periods);
    
    foreach ($periods as $period) {
       
    // pour chaque période on sélectionne le cluster
        $sql = "SELECT cluster_univ_id FROM clusters where stream_id=" . $stream . " and period='" . $period . "' group by cluster_univ_id";        
        foreach ($dbh->query($sql) as $cluster) {
            // pour chaque cluster on fait la liste des articles
            $paper_list=array();
              $sql_paper_list = "SELECT article_id, weight FROM projection where cluster_univ_id=" .$cluster['cluster_univ_id'];
              foreach ($dbh->query($sql_paper_list) as $paper) {
                  $paper_list[$paper['article_id']]=$paper['weight'];
              }
              $best_paper_id=array_search(max($paper_list), $paper_list);
              $sql_best_paper = "SELECT data FROM headline where id=" .$best_paper_id;
              foreach ($dbh->query($sql_best_paper) as $best_paper) {
                  pt($period.' - '.$best_paper['data']);
              }
              
        }
        
    }
    
    $sql = "SELECT cluster_univ_id FROM clusters where stream_id=".$stream;;
    foreach ($dbh->query($sql) as $row)
        {       
        //$stream_size[$row['0']]=$row['1'];
        }
    
}


function cmp($period1, $period2)
{
    $period1=split('_',$period1);
    $period1=$period1[0];
    $period2=split('_',$period2);
    $period2=$period2[0];
    
    if ($period1== $period2) {
        return 0;
    }
    return ($period1 < $period2) ? -1 : 1;
}
?>
