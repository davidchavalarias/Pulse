<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

include("library/fonctions_php.php");
include("library/globepulse_library.php");
include("parametre.php");

// crée un ensemble de tables complémentaires.
$db = new PDO('sqlite:' . $sqlite_database);

if ($drop_table){
    $db->exec("DROP TABLE IF EXIST cluster_infos");        


pt("creation d'une table clustersinfo sans redondances");
//on crée un table pour les infos de clusters 
$db->exec("CREATE TABLE cluster_infos (cluster_univ_id NUMERIC,cluster_label TEXT,cluster_label_freq TEXT,
    period TEXT,start NUMERIC,end NUMERIC,stream_id NUMERIC,stream_label TEXT,supra_stream_id NUMERIC,
    supra_stream_label TEXT,activity NUMERIC,color NUMERIC,
    pos_x  NUMERIC,pos_y  NUMERIC,pos_x_phylo  NUMERIC,pos_y_phylo  NUMERIC)");    

// on la peuple avec les info déjà existantes
$sql="SELECT * FROM clusters GROUP BY cluster_univ_id";
foreach ($db->query($sql) as $ligne){ 
  $p = split('_', $ligne['period']);
  $sqltransfert="INSERT INTO cluster_infos (cluster_univ_id,cluster_label,
    period,start,end,stream_id,stream_label,supra_stream_id,
    supra_stream_label,pos_x,pos_y) VALUES (".$ligne['cluster_univ_id'].",'".$ligne['cluster_label']."','".
    $ligne['period']."',".$p[0].",".$p[1].",".$ligne['stream_id'].",'".$ligne['stream_label']
    ."',".$ligne['suprathematique'].",'".$ligne['suprathematique_label']."',".$ligne['pos_x'].",".$ligne['pos_y'].")";
    pt($sqltransfert);
    $db->exec($sqltransfert);       
}

}    

// calcul de l'activité des champs et d'un code couleur type hue
$sql_stream="SELECT stream_id FROM cluster_infos group by stream_id";
foreach ($db->query($sql_stream) as $stream_info) {
    $r_array = array();
    $hue_array = array();
    $sql = "SELECT cluster_univ_id,stream_id FROM cluster_infos where stream_id=" . $stream_info['stream_id'];    
    foreach ($db->query($sql) as $ligne) {
        // pour ajuster la taille à la popularité
        $sql_cluster_weight = "SELECT sum(weight),cluster_univ_id FROM projection where weight>0.6 AND cluster_univ_id=" . $ligne['cluster_univ_id'];
        
        //pt($sql_cluster_weight);
        //pt('cluster weight='.$cluster_weight['sum(weight)']);
        foreach ($db->query($sql_cluster_weight) as $cluster_weight) {
            $r_array[$cluster_weight['cluster_univ_id']] = 5 + $cluster_weight['sum(weight)'] / 100;
        }
    }

    $max = max($r_array);
    $min = min($r_array);
    foreach ($r_array as $clusterId => $value) {
        $sql_ins = "UPDATE OR REPLACE cluster_infos SET activity=" . map_proportional($value, $min, $max,.5*$r, 1.5 * $r) . ", color=" . (1 - map($value, $min, $max, .7, 1)) . " WHERE cluster_univ_id='" . $clusterId . "'";
        $db->exec($sql_ins);
    }
}





error();
pt('processing of terms occurrence per period !!! this takes time !!!');
$db->exec("CREATE TABLE periodBeginEnd (period TEXT, begin NUMERIC,end NUMERIC)");
$db->exec("CREATE TABLE IDPaperPeriodT ( file TEXT, id NUMERIC, period TEXT, [begin] NUMERIC, [end] NUMERIC, Expr1 NUMERIC, expr2 NUMERIC)");    
$db->exec("CREATE TABLE termsOccByPeriod (period TEXT , term TEXT, CompteDeid NUMERIC)");    

$sql='select period from clusters group by period';
foreach ($db->query($sql) as $ligne){   
    $p = split('_', $ligne['period']);
    pt($p[0]);
    $sql_period="INSERT into periodBeginEnd(period,begin,end) VALUES ('".$ligne['period']."','".$p[0]."','".$p[1]."')";
    pt($sql_period);
            $db->exec($sql_period);                      
     }
     
     
$db->exec('INSERT INTO IDPaperPeriodT ( file, id, period, [begin], [end], Expr1, expr2 ) SELECT ISIpubdate.file, ISIpubdate.id, periodBeginEnd.period, periodBeginEnd.begin, periodBeginEnd.end, [data]>=[begin] AS Expr1, [data]<=[end] AS expr2 FROM periodBeginEnd, ISIpubdate WHERE ((([data]>=[begin])) AND (([data]<=[end]))) ORDER BY ISIpubdate.id'); 
$db->exec('INSERT INTO termsOccByPeriod ( period, term, CompteDeid ) SELECT [period] AS per, [data] AS term, Count(ISIterms.id) AS CompteDeid FROM ISIterms INNER JOIN IDPaperPeriodT ON ISIterms.id = IDPaperPeriodT.id GROUP BY [period], [data] ORDER BY [period], Count(ISIterms.id) DESC');


pt('processing alternative labels');
// on calcul le label pour chaque cluster
$db->exec("ALTER TABLE clusters ADD cluster_label_freq TEXT");    

$sql="SELECT cluster_univ_id,period FROM clusters GROUP BY cluster_univ_id";
foreach ($db->query($sql) as $ligne){   
     $sql_terms = "SELECT term,weight FROM clusters WHERE cluster_univ_id=" .$ligne['cluster_univ_id'].' order by weight';
     pt($sql_terms);
            $term = '';
            $terms = array(); // terms avec leurs poids
            foreach ($db->query($sql_terms) as $row) {
                $sql_freq= 'SELECT CompteDeid FROM termsOccByPeriod WHERE term="' .$row['term'].'" AND period="'.$ligne['period'].'"';;
                foreach ($db->query($sql_freq) as $freq) {
                    $terms[$row['term']] = $row['weight']*$freq[CompteDeid];                
                }
                
            }
            uasort($terms, 'cmp');
            $term_only=array_keys($terms);
            $sql_ins="UPDATE OR REPLACE clusters SET cluster_label_freq='".$term_only[0].'-'.$term_only[1]."' WHERE cluster_univ_id=".$ligne['cluster_univ_id'];
            pt($sql_ins);
            $db->exec($sql_ins);   
            
}

 
?>
