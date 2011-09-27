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


error();
pt('processing of terms occurrence per period');
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

 
?>
