<?php
include("library/fonctions_php.php");
include("parametre.php");



mysql_connect( $server,$user,$password);if ($encodage=="utf-8") mysql_query("SET NAMES utf8;");
@mysql_select_db($database) or die( "Unable to select database");
//à préciser lorsqu'on est sur sciencemapping.com
if ($user!="root") mysql_query("SET NAMES utf8;");


$raphael=TRUE;
include("include/header.php");

if(isset( $_GET['id_cluster'])) $id_cluster = intval($_GET['id_cluster']); else die("<h1>Agrégat non spécifié.</h1>");
if(isset( $_GET['periode'])) $my_period=$_GET['periode']; else die("<h1>Agrégat non spécifié.</h1>");
$periode=$my_period;


///////////////// Module pour préparer la visu de phylo en Raphael

$sql="SELECT pseudo FROM cluster WHERE id_cluster=".$id_cluster." AND periode=\"".derange_periode($periode)."\" ";
$resultat=mysql_query($sql) or die ("Requête non executée.");
while ($partit=mysql_fetch_array($resultat)) {
    $id_partition=$partit[pseudo];
}

$phylo_structure=create_phylo_structure($id_partition);



$ymax=max($phylo_structure['y']);

// données de périodes
$period_uniques = array_unique($phylo_structure['period1']);
$timespan=max($period_uniques)-min($period_uniques);    
$period_min=min($period_uniques);
$myperiod1=split(' ',derange_periode($my_period));
$dt=$myperiod1[1]-$myperiod1[0];
$nb_stations=floor($timespan/$dt);
$myperiod1=$myperiod1[0];
$ytrans=50; // espace pour les noms de station
$raphael_height=200;

//for ($i=0;$i<count($phylo_structure['cluster_id']);$i++){
//    foreach ($phylo_structure['sons'][$i] as $value) {
//        pt($phylo_structure['cluster_id'][$i].'-'.$value);
//    }}  
    
echo '
<script type="text/javascript" charset="utf-8">

        window.onload = function () {
            var R = Raphael("metro"), x =800, y ='.$raphael_height.', r = 5;
            d=200;            
            ';

// on trace les lignes


$nb_path=0;
for ($i=0;$i<count($phylo_structure['cluster_id']);$i++){
    foreach ($phylo_structure['sons'][$i] as $value) {
        $nb_path+=1;
        $index=array_search($value, $phylo_structure['cluster_id']);
        echo 'var x1_'.$nb_path.'='.($phylo_structure['x'][$i]-$period_min)*1/$timespan.'*(x-60)+40, y1_'.$nb_path.'='.$ytrans.'+(y-'.$ytrans.')*'.(($phylo_structure['y'][$i]-1)/$ymax).';
            '; 
        echo 'var x2_'.$nb_path.'='.($phylo_structure['x'][$index]-$period_min)*1/$timespan.'*(x-60)+40 , y2_'.$nb_path.'='.$ytrans.'+(y-'.$ytrans.')*'.(($phylo_structure['y'][$index]-1)/$ymax).';
            ';
        echo 'var S="M"+(x1_'.$nb_path.')+ "," + y1_'.$nb_path.' + "C"+(x1_'.$nb_path.'+30)+ "," + y1_'.$nb_path.'  + " " + (x2_'.$nb_path.'-30)+ "," + y2_'.$nb_path.'+ " " +(x2_'.$nb_path.')+ "," + y2_'.$nb_path.";                        
            ";
        
        echo 'var c'.$nb_path. '= R.path(S);';
        }
};


// on trace les balles
for ($i=0;$i<count($phylo_structure['cluster_id']);$i++){
        echo 'var x1_'.$i.'='.($phylo_structure['x'][$i]-$period_min)*1/$timespan.'*(x-60)+40, y1_'.$i.'='.$ytrans.'+(y-'.$ytrans.')*'.(($phylo_structure['y'][$i]-1)/$ymax).';
            ';        
    
    if (($id_cluster==$phylo_structure['cluster_id_local'][$i])&&($phylo_structure['period1'][$i]==$myperiod1)){
    echo '
            R.circle((x1_'.$i.'),y1_'.$i.', 2*r);
            var t = R.text(50,10,"'.$phylo_structure['label'][$i].'");
            var twidth = t.getBBox().width; 
            var trans=twidth*Math.cos(Math.pi-10);
            t.attr({ "text-anchor":"start","font-size":22,"font-weight":"bold","fill":"grey"});        
            var bal=R.ball(x1_'.$i.',y1_'.$i.', r, 0)                          
                .click(function (event) {window.open("index.php?id_cluster='.$phylo_structure['cluster_id_local'][$i].'&periode='.$phylo_structure['period1'][$i].'-'.$phylo_structure['period2'][$i].'","_self");});                  
                
        ';
    }else{
        echo '         
            var bal=R.ball(x1_'.$i.',y1_'.$i.', r, 0.5);                                    
            var t_'.$i.' = R.text(x1_'.$i.','.$ytrans.'-10, "'.$phylo_structure['label'][$i].'");           
            t_'.$i.'.attr({"text-anchor":"start","font-size":20});        
            t_'.$i.'.hide();
            var c_'.$i.'=R.circle((x1_'.$i.'),y1_'.$i.', 1.5*r).attr({fill: "red",opacity:0});';
        
            
        
            echo 'c_'.$i.'.mouseover(function (event) {t_'.$i.'.show();'.$showlinks.'});
            c_'.$i.'.mouseout(function (event) {t_'.$i.'.hide();});
            c_'.$i.'.click(function (event) {window.open("index.php?id_cluster='.$phylo_structure['cluster_id_local'][$i].'&periode='.$phylo_structure['period1'][$i].'-'.$phylo_structure['period2'][$i].'","_self");});               
        ';
    }
};

echo '
        };
    </script>';



echo '<div id="metro"></div>
    </body>';




////////////////  Fonctions
function array_search_filtered($array, $dim_filter, $dim_filter_val, $target_dim, $funct) {
// tri combiné sur les array multidimentionnels
// retourne un tableau des clef telles que:
// -1) $array[$dim_filter]=$dim_filter_val
// -2) $array[$target_dim]=max($array[$target_dim])  ou min selon $funct

    $array1 = array_keys($array[$dim_filter], $dim_filter_val); // toutes les clef qui ont une certaine valeur de dim
    $array_filtered = array();
    foreach ($array1 as $value) {
        $array_filtered[$value] = $array[$target_dim][$value];
    }
    if ($funct==='max'){
        $result = array_keys($array_filtered, max($array_filtered));        
    }else{
        $result = array_keys($array_filtered, min($array_filtered));        
    }
    return $result;
}

function array_search_filtered_sup($array, $dim_filter, $dim_filter_val, $target_dim, $funct) {
// tri combiné sur les array multidimentionnels
// retourne un tableau des clef telles que:
// -1) $array[$dim_filter]>=$dim_filter_val
// -2) $array[$target_dim]=max($array[$target_dim])  ou min selon $funct

    $array1 = array_find_sup($array[$dim_filter], $dim_filter_val); // toutes les clef qui ont une certaine valeur de dim
    $array_filtered = array();
    foreach ($array1 as $value) {
        $array_filtered[$value] = $array[$target_dim][$value];
    }
    $result = array_keys($array_filtered, max($array_filtered));
    return $result;
}

function create_phylo_structure($partition_id) {
    //Pour fabriquation de phylo avec Raphael. 
    //créer une structure de type multi_array décrivant une macro-branch de phylogénie avec les champs suivants
    // cluster_ids,period, length_to_end (distance restant sur la sous chaine),length_from_start (distance parcourue depuis le début),fathers,sons 
    // on sélectionne tous les clusters
    // X et Y donnent les dimensions de la feuilles sur laquelle est tracée la phylo

    $phylo = array();
    $listed_clusters=array();
    $resultat = mysql_query("SELECT * FROM cluster WHERE pseudo=" . $partition_id." GROUP BY id_cluster_univ") or die("Requête non executée.");
    $count=0;   
    
    // calcul du nombre de clusters
    $nbClusters=0;
    while ($ligne = mysql_fetch_array($resultat)) {
        $nbClusters+=1;
    }
    $resultat = mysql_query("SELECT * FROM cluster WHERE pseudo=" . $partition_id) or die("Requête non executée.");
    $counter=0;
    while ($ligne = mysql_fetch_array($resultat)) {
        $cluster_id_exist = array_search($ligne['id_cluster_univ'],$listed_clusters);
        if (is_bool($cluster_id_exist)) {//le cluster n'est pas encore répertorié
            array_push($listed_clusters,$ligne['id_cluster_univ']);
            $phylo['cluster_id'][] = $ligne['id_cluster_univ']; // identifiant universel
            $phylo['cluster_id_local'][] = $ligne['id_cluster']; // identifiant local
            
            $p = split(' ', $ligne['periode']);
            $phylo['period1'][] = $p[0];
            $phylo['period2'][] = $p[1];
            
            $phylo['label'][] = $ligne['label'];
            
            $phylo['length_to_end'][] = 0;
            $phylo['length_to_start'][] = 0;
            
            $phylo['exit'][] = 0; // marqueur utile pour la suite pour voir s'il le noeud doit encore être traité dans la spatialisation
            
            // on récupère pères et fils            
            $resultat_sons = mysql_query("SELECT id_cluster_2_univ FROM `phylo` WHERE id_cluster_1_univ=" . $ligne['id_cluster_univ']) or die("fils non récupérés.");
            $resultat_fathers = mysql_query("SELECT id_cluster_1_univ FROM `phylo` WHERE id_cluster_2_univ=" . $ligne['id_cluster_univ']) or die("fils non récupérés.");
            $sons = array();
            $fathers = array();
            while ($ligne_sons = mysql_fetch_array($resultat_sons)) {
                $sons[] = $ligne_sons['id_cluster_2_univ'];
            }

            while ($ligne_fathers = mysql_fetch_array($resultat_fathers)) {
                $fathers[] = $ligne_fathers['id_cluster_1_univ'];
            }
            $phylo['fathers'][] = $fathers;
            $phylo['sons'][] = $sons;
            $phylo['x'][]    = 0; // positions initialisées en 0
            $phylo['y'][] = 0;                        
            $count+=1;
           
        }
        
    }
    
    $period_uniques = $phylo['period1'];
    $period_uniques=array_unique($period_uniques);// périodes par ordre décroissant
    $period_uniques_reverse=array_reverse($period_uniques); 
    ///$nb_periodes=$period_uniques[-1]-$period_uniques[0];   
    
    $clusters_processed = array();
    // On calcule pour chaque cluster sa distance à l'extremité de sa branche (non utilisé pour le moment)
    foreach ($period_uniques_reverse as $current_period) {
        $clusters_rank = array_keys($phylo['period1'], $current_period);
        foreach ($clusters_rank as $cluster_rank) {
            $clusters_processed[$cluster_rank] = 0; // on initialise le marqueur de traitement de la spatialisation (pour plus tard)
            $length_to_end = 0;
            if (!empty($phylo['sons'][$cluster_rank])) {                               
                for ($j = 0; $j < count($phylo['sons'][$cluster_rank]); $j++) {
                    if ($phylo['length_to_end'][array_search($phylo['sons'][$cluster_rank][$j], $phylo['cluster_id'])] > $length_to_end - 1) {
                        $length_to_end = $phylo['length_to_end'][array_search($phylo['sons'][$cluster_rank][$j], $phylo['cluster_id'])] + 1;
                    }
                }
                $phylo['length_to_end'][$cluster_rank] = $length_to_end;
                
            }
        }unset($cluster_rank);       
    }unset($current_period);

    // On calcule pour chaque cluster sa distance au début sa branche 
    foreach ($period_uniques as $current_period) {
        $clusters_rank = array_keys($phylo['period1'], $current_period);
        foreach ($clusters_rank as $cluster_rank) {
            $length_to_start = 0;
            if (!empty($phylo['fathers'][$cluster_rank])) {
                for ($j = 0; $j < count($phylo['fathers'][$cluster_rank]); $j++) {
                    if ($phylo['length_to_start'][array_search($phylo['fathers'][$cluster_rank][$j], $phylo['cluster_id'])] > $length_to_start - 1) {
                        $length_to_start = $phylo['length_to_start'][array_search($phylo['fathers'][$cluster_rank][$j], $phylo['cluster_id'])] + 1;
                    }
                }
                $phylo['length_to_start'][$cluster_rank] = $length_to_start;
            }
        }unset($cluster_rank);
    }unset($current_period);
    
    $y_axis = array(); // donne l'épaisseur de la phylo par période (nombre de branches parallèles
    foreach ($period_uniques as $value) {
        $y_axis[$value] = 1;
    }unset($value);


    // on initialise le exit en choisissant l'un des noeuds extrêmes   
    $firstCandidates=array_search(max($phylo['length_to_start']),$phylo['length_to_start']);
    $phylo['exit'][$firstCandidates] = 1; // on marque comme une sortie le premier noeud, également un bout de chaine

    global $end_reached; // indique si on a atteint une extrémité de branche
    $end_reached=1;
    $to_process = true; // dit s'il reste des noeuds à traiter
    $direction=1; // dit si on parcours vers le haut ou le bas (0 bas, 1 haut)
    $directionChanged=0;// dit si on vient de changer de direction
    $stop=0; // si stop =2 on a changé deux fois de suite de direction et on doit s'arrêter
    $next_nodes= array_search_filtered($phylo,'exit',1,'length_to_start','max');  // noeud d'ou l'on vient. Initialisé à la même valeur que le premier noeud
    $next_nodes=$next_nodes[0];
    
    while ($to_process) {        
        $previous_node=$next_nodes;      
        if ($direction==1){// on remonte la phylo                
                $next_nodes =  array_search_filtered($phylo,'exit',max(1,max($phylo['exit'])),'length_to_start','max');                   
            if (count($next_nodes)==0){                                
                $direction=0;
                $directionChanged=1;
                $stop+=1;
            }else{
                $next_nodes=$next_nodes[0];            
                $directionChanged=0;
                $stop=0;
            }
        }else{// on redescend la phylo            
                $next_nodes = array_search_filtered($phylo,'exit',min(-1,min($phylo['exit'])),'length_to_end','max');    
                
                // on regarde s'il y a des pères                
//                $neighborsId=array();
//                foreach ($next_nodes as $value){
//                    $neighborsId[]=$phylo['cluster_id'][$next_nodes];
//                }unset($value);

            if (count($next_nodes)==0){                                
                $direction=1;
                $directionChanged=1;    
                
                $stop+=1;
            }else{
                $next_nodes=$next_nodes[0];
                $stop=0;
                $directionChanged=0;
            }
        }
        if (count($next_nodes)!=0) { // s'il reste des 'sorties'            
            //pt('processing node'.$phylo['label'][$next_nodes].'from periode'.$phylo['period1'][$next_nodes]);
            
            $phylo['x'][$next_nodes] = $phylo['period1'][$next_nodes];
            
            //$y_axis[$phylo['period1'][$next_nodes]] = $y_axis[$phylo['period1'][$next_nodes]] + 1;
            if ($end_reached==1){    
                $y_axis[$phylo['period1'][$next_nodes]] = $y_axis[$phylo['period1'][$next_nodes]] + 1;
                $end_reached=0;
            }else{                                
                foreach ($period_uniques as $period){
                    if (($period>=min($phylo['period1'][$next_nodes],$phylo['period1'][$previous_node]))&&($period<=max($phylo['period1'][$next_nodes],$phylo['period1'][$previous_node]))){
                        //pt($y_axis[$phylo['period1'][$previous_node]]);
                        $y_axis[$period]=$phylo['y'][$previous_node];                       
                    }                                        
                }
                
            }

            $m=min($phylo['exit']);
            $M=max($phylo['exit']);
            $periodRank=array_keys($period_uniques,$phylo['period1'][$next_nodes]);

            $current_sons = $phylo['sons'][$next_nodes];    
            
            if ($direction==0){
                $end_reached=1;
            }
                         
            foreach ($current_sons as $value) {
                $index=array_search($value, $phylo['cluster_id']);
                if ($clusters_processed[$index] == 0) {
                    if ($direction==0){
                        $phylo['exit'][$index] = $m-1;
                        $end_reached=0;
                    }else{
                        $phylo['exit'][$index] = -1;
                    }
                    
                }else{
                    if($end_reached){ 
                        $periodRankTemp=array_keys($period_uniques,$phylo['period1'][$index]);
                        for ($per=min($periodRank[0],$periodRankTemp[0]);$per<=max($periodRank[0],$periodRankTemp[0]);$per++){
                            if ($y_axis[$phylo['period1'][$next_nodes]]<$y_axis[$period_uniques[$per]]){
                                $y_axis[$phylo['period1'][$next_nodes]]=$y_axis[$period_uniques[$per]]+1;
                            }
                        }
                    }
                }
            }unset($value);
            
            
            $current_fathers = $phylo['fathers'][$next_nodes];                                  
            
            if ($direction==1){
                $end_reached=1;
            }
            
            
            
            foreach ($current_fathers as $value) {
                $index=array_search($value, $phylo['cluster_id']);
                if ($clusters_processed[$index] == 0) {
                    if ($direction==0){
                        $phylo['exit'][$index] =1;
                    }else{
                        $phylo['exit'][$index] = $M+1;
                        $end_reached=0;
                    }
                }else{
                    if($end_reached){ 
                        $periodRankTemp=array_keys($period_uniques,$phylo['period1'][$index]);
                        for ($per=min($periodRank[0],$periodRankTemp[0]);$per<=max($periodRank[0],$periodRankTemp[0]);$per++){
                            if ($y_axis[$phylo['period1'][$next_nodes]]<$y_axis[$period_uniques[$per]]){
                                $y_axis[$phylo['period1'][$next_nodes]]=$y_axis[$period_uniques[$per]]+1;
                            }
                        }
                    }
                }
            }unset($value);
            
            
//            if ($directionChanged){
//                $ylist=array_slice($y_axis,$phylo['period1'][$next_nodes],$phylo['length_to_end'][$next_nodes]+1);
//                $ybranch=max($ylist);
//            }
//            
       
            
            
            $phylo['y'][$next_nodes] = $y_axis[$phylo['period1'][$next_nodes]];
            $clusters_processed[$next_nodes] = 1;
            $counter+=1;
            $phylo['counter'][$next_nodes]=$counter;
            $phylo['exit'][$next_nodes] = 0;

        } else {
            if ($stop>2){
                $to_process = 0;
            }
            
        }        

    }
    return $phylo;
}

function pta($array){
    print_r($array);
    echo '<br/>';
}

function array_find_sup($array,$minvalue){
    // find all keys for which values are more or equal to $value
    $result=array();
    foreach ($array as $key=>$value){
        if ($minvalue<=$value){
            $result[]=$key;            
        }
    }
    return $result;
}

?>
