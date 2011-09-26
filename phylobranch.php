<?php
include("library/fonctions_php.php");
include("library/globepulse_library.php");
include("parametre.php");

$raphael=TRUE;
include("include/header.php");

if(isset( $_GET['stream_id'])) $stream_id=$_GET['stream_id']; else die("<h1>phylogenetic stream not specified.</h1>");
if(isset( $_GET['id_cluster'])) $id_cluster = intval($_GET['id_cluster']); else die("<h1>Agrégat non spécifié.</h1>");
if(isset( $_GET['periode'])) $my_period=$_GET['periode']; else die("<h1>periode non spécifié.</h1>");
$periode=$my_period;
$time_window=split('-', $periode);


///////////////// Module pour préparer la visu de phylo en Raphael


//$sqlite_database='secalim_query_secalimandco.db';
$phylo_structure=create_phylo_structure($stream_id,$sqlite_database);

//pta($phylo_structure);


$ymax=max($phylo_structure['y']);

// données de périodes
$period_uniques = array_unique($phylo_structure['period1']);
$timespan=max($period_uniques)-min($period_uniques);    
$period_min=min($period_uniques);
$myperiod1=split('-',$my_period);
$dt=$myperiod1[1]-$myperiod1[0];
$myperiod1=$myperiod1[0];
$ytrans=15; // espace pour les noms de station
$raphael_height=max($phylo_structure['y'])*$branch_width;

   
/////////////////////////////////////////////
// Script de visu avec raphael
///////////////////////////////////////////
// phylo_plot($phylo_structure,$ytrans,$timespan,$period_min,$branch_width,$sqlite_database,$screen_width,$r);    // génère le code raphael    

echo '
<script type="text/javascript" charset="utf-8">

        window.onload = function () {       
            var R = Raphael("metro",'.$screen_width.','.($raphael_height+100).'), x ='.$screen_width.', y ='.$raphael_height.', r = 5;
            d=200;            
            ';
       
    $terms=phylo_plot($phylo_structure,$ytrans,$timespan,$period_min,$branch_width,$sqlite_database,$screen_width,$r);    // génère le code raphael    

    $bottom=$branch_width*(1+max($phylo_structure['y']));
     echo '
            R.rect('.(5+30).',5, 20,20, 5);
            var bal=R.ball('.(30+15).',0+15, 8,.8)                          
                .click(function (event) {window.open("index.php","_self");});     
                
        ';

echo '

};
        

    </script>';


echo '<div id="metro"></div>';

echo '<div style="margin-right: 40px;margin-left: 40px;width:750px">';

// On cherches les articles les plus pertinents
$conn = new PDO('sqlite:'.$sqlite_database);

// on sélectionne les articles qui mentionnent au moins un terme du label
$sql = "select cluster_label from cluster where cluster_univ_id=".$id_cluster;      
foreach ($conn->query($sql) as $ligne){ 
    $labels=split('&',$ligne['cluster_label']);
    $label_filter="'".trim($labels[0])."' OR '".trim($labels[1])."'";
}
$sql = "select wos_id from articles2terms where terms_id=".$label_filter." group by wos_id";      
$id_filter=array();
foreach ($conn->query($sql) as $ligne){ 
    $id_filter[]=$ligne['wos_id'];
}

//pta($id_filter);

foreach($terms as $term=>$weight){
    $terms_string_filter.='"'.trim($term).'" OR ';
}
$terms_string_filter=substr($terms_string_filter,0,-3);


$sql = "select wos_id,count(*) from articles2terms where terms_id=".$label_filter.' OR '.$terms_string_filter." group by wos_id";      
$articles_scores=array();
foreach ($conn->query($sql) as $ligne){ 
    $articles_scores[$ligne['wos_id']]=$ligne['count(*)'];
}

arsort($articles_scores); // liste d'articles triés par ordre décroissant de termes communs avec le cluster
$articles_id=array_keys($articles_scores);

//pta($articles_scores);

$articles_filtered=array();
$articles_filtered=array_intersect($articles_id, $id_filter);
$found_article=false;
$i=0;
foreach ($articles_filtered as $candidate_paper) {
    if (!$found_article) {        
        //pt('article candidat: '.$candidate_paper);
        $sql = "SELECT data FROM ISIpubdate where id=" . $candidate_paper;
        foreach ($conn->query($sql) as $paper_date) {
            //pt($time_window[0] . '-' . $paper_date['data'] . '-' . $time_window[1]);
            if (($time_window[0] < $paper_date['data']) && ($paper_date['data'] <= $time_window[1])) {
                $found_article = true;

                $best_paper_id = $candidate_paper;
            }
        }
        $i++;
    }
}
    $sql_best_paper = "SELECT data FROM headline where id=" . $best_paper_id;
foreach ($conn->query($sql_best_paper) as $best_paper) {
    $sql_best_paper_source = "SELECT data FROM sourceName where id=" . $best_paper_id;
    foreach ($conn->query($sql_best_paper_source) as $source) {
        $sql_best_paper_tail = "SELECT data FROM leadParagraph where id=" . $best_paper_id;
        foreach ($conn->query($sql_best_paper_tail) as $paragraph) {
            $sql_best_paper_date = "SELECT data FROM publicationDate where id=" . $best_paper_id;
            foreach ($conn->query($sql_best_paper_date) as $Pubdate) {
                pt('<p style="text-align: justify"><b>' . $period . '  <font size="3" face="arial">' . $best_paper['data'] .
                        ' </b><i>' . $source['data'] . '</i> <font size="1" face="arial">' . $Pubdate['data'] . '</font>' . '</font>' . '<br/><font size="2" face="arial" color="grey">'
                        . $paragraph['data'] . '</font><br/>');
            }
        }
    }
}

// et un article par meilleurs score JP
foreach ($conn->query($sql) as $cluster) {
    // pour chaque cluster on fait la liste des articles
    $paper_list = array();
    $sql_paper_list = "SELECT article_id, weight FROM projection where cluster_univ_id=" . $id_cluster;
    foreach ($conn->query($sql_paper_list) as $paper) {
        $paper_list[$paper['article_id']] = $paper['weight'];
    }

    $best_paper_id = array_search(max($paper_list), $paper_list);
    $sql_best_paper = "SELECT data FROM headline where id=" . $best_paper_id;
    foreach ($conn->query($sql_best_paper) as $best_paper) {
        $sql_best_paper_source = "SELECT data FROM sourceName where id=" . $best_paper_id;
        foreach ($conn->query($sql_best_paper_source) as $source) {
            $sql_best_paper_tail = "SELECT data FROM leadParagraph where id=" . $best_paper_id;
            foreach ($conn->query($sql_best_paper_tail) as $paragraph) {
                $sql_best_paper_date = "SELECT data FROM publicationDate where id=" . $best_paper_id;
                foreach ($conn->query($sql_best_paper_date) as $Pubdate) {
                    $sql_best_paper_date = "SELECT data FROM ISIterms where id=" . $best_paper_id;
                        //$isilist=array();
                        //foreach ($conn->query($sql_best_paper_date) as $isiterms) {
//                        $isilist[$isiterms['data']]=1;                                                            
//                        }
//                        $isikeywords='';
//                        $isiterms=array_keys($isilist);
//                        foreach($isiterms as $isiterm){
//                            $isikeywords.=$isiterm.', ';
//                        }
                    //<font size="2" face="arial"<i>'.$isikeywords.'</i></font>
                            
                        pt('<b>' . $period . '  <font size="3" face="arial">' . $best_paper['data'] .
                            ' </b><i>' . $source['data'] . '</i> <font size="1" face="arial">' . $Pubdate['data'] . '</font>' . '</font>' . '<br/>                            
                            <font size="2" face="arial" color="grey">'
                            . $paragraph['data'] . '</font></p>');
                }
            }
        }
    }
   }
          
    



echo '</body>';




?>
