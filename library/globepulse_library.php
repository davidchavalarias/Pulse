<?php
////////////////  Fonctions
function phylo_plot($phylo_structure,$ytrans,$timespan,$period_min,$branch_width,$database,$screen_width){

  try
  {
    //open the database
    $db = new PDO('sqlite:'.$database);

    //create the database
    $db->exec("CREATE TABLE positions (cluster_univ_id TEXT, x_pos_phylo NUMERIC,y_pos_phylo NUMERIC)");    

    
  }
  catch(PDOException $e)
  {
    print 'Exception : '.$e->getMessage();
  }


// on écrit toutes les coordonnées des points
for ($i=0;$i<count($phylo_structure['cluster_univ_id']);$i++){    
        echo 'var x_'.$phylo_structure['cluster_univ_id'][$i].'='.map($phylo_structure['x'][$i],$period_min,($period_min+$timespan),40,($screen_width-40)).';';
        echo 'var y_'.$phylo_structure['cluster_univ_id'][$i].'='.$ytrans.'+('.$branch_width.')*'.(($phylo_structure['y'][$i]-1)).';
            ';        
        
        // calcul du nombre de clusters (à optimiser)
        $nbClusters=0;        
        $sql = "INSERT INTO positions(cluster_univ_id,x_pos_phylo,y_pos_phylo) VALUES ('".$phylo_structure['cluster_univ_id'][$i]."',".($phylo_structure['x'][$i]-$period_min)*1/$timespan."*(".$screen_width."-60)+40,".$ytrans.'+('.$branch_width.')*'.(($phylo_structure['y'][$i]-1)).")";      
        $db->exec($sql);
        
        
}


// on trace les lignes
for ($i=0;$i<count($phylo_structure['cluster_univ_id']);$i++){
    foreach ($phylo_structure['sons'][$i] as $value) {
         $index=array_search($value, $phylo_structure['cluster_univ_id']);
         echo 'var S_'.$phylo_structure['cluster_univ_id'][$i].'_'.$value.'="M"+(x_'.$phylo_structure['cluster_univ_id'][$i].')+ "," + y_'.$phylo_structure['cluster_univ_id'][$i].' + "C"+(x_'.$phylo_structure['cluster_univ_id'][$i].'+30)+ "," + y_'.$phylo_structure['cluster_univ_id'][$i].'  + " " + (x_'.$value.'-30)+ "," + y_'.$value.'+ " " +(x_'.$value.')+ "," + y_'.$value.";                        
            ";
        
        echo 'var c'.$phylo_structure['cluster_univ_id'][$i].'_'.$value. '= R.path(S_'.$phylo_structure['cluster_univ_id'][$i].'_'.$value.');';
        }
};


// on trace les balles
for ($i=0;$i<count($phylo_structure['cluster_univ_id']);$i++){    
        //var t2_'.$i.' = R.text(x_'.$i.',y_'.$i.',"'.$phylo_structure['counter'][$i].'-'.$phylo_structure['length_to_start'][$i].'");           
        //t2_'.$i.'.attr({"text-anchor":"start","font-size":20});        
            
        echo '         
            var bal_'.$phylo_structure['cluster_univ_id'][$i].'=R.ball(x_'.$phylo_structure['cluster_univ_id'][$i].',y_'.$phylo_structure['cluster_univ_id'][$i].', r, 0.5);                                    
            var t_'.$phylo_structure['cluster_univ_id'][$i].' = R.text(x_'.$phylo_structure['cluster_univ_id'][$i].',y_'.$phylo_structure['cluster_univ_id'][$i].'-20, "'.$phylo_structure['label'][$i].'");                           
            t_'.$phylo_structure['cluster_univ_id'][$i].'.attr({"text-anchor":"center","font-size":20});        
            t_'.$phylo_structure['cluster_univ_id'][$i].'.hide();
            var c_'.$phylo_structure['cluster_univ_id'][$i].'=R.circle((x_'.$phylo_structure['cluster_univ_id'][$i].'),y_'.$phylo_structure['cluster_univ_id'][$i].', 1.5*r).attr({fill: "red",opacity:0});';
        
            
        
            echo 'c_'.$phylo_structure['cluster_univ_id'][$i].'.mouseover(function (event) {t_'.$phylo_structure['cluster_univ_id'][$i].'.show();'.$showlinks.'});
            c_'.$phylo_structure['cluster_univ_id'][$i].'.mouseout(function (event) {t_'.$phylo_structure['cluster_univ_id'][$i].'.hide();});
            c_'.$phylo_structure['cluster_univ_id'][$i].'.click(function (event) {window.open("phylobranch.php?stream_id='.$phylo_structure['stream_id'].'&id_cluster='.$phylo_structure['local_id'][$i].'&periode='.$phylo_structure['period1'][$i].'-'.$phylo_structure['period2'][$i].'","_self");});               
        ';    
};
      
}

function create_phylo_structure($partition_id,$database) {
        
    //Pour fabriquation de phylo avec Raphael. 
    //crée une structure de type multi_array décrivant une macro-branch de phylogénie avec les champs suivants
    // cluster_ids (identifiant unique d'un cluster),period1,period2, 
    // length_to_end (distance restant sur la sous chaine),length_from_start (distance parcourue depuis le début),fathers,sons     
    // x est la période et y est calculé de façon a optimiser le nombre de sous branches rectilignes et minimiser 
    
    // on sélectionne tous les clusters

    $dbh = new PDO("sqlite:".$database);    
    // calcul du nombre de clusters (à optimiser)
    $nbClusters=0;
    $sql = "SELECT * FROM clusters WHERE stream_id=". $partition_id." GROUP BY cluster_id,period";;
    foreach ($dbh->query($sql) as $row)
        {
        $nbClusters+=1;
        }        
    
    $phylo = array();
    $phylo['stream_id']=$partition_id;
    $listed_clusters=array();
    $count=0;   
    
    $sql="SELECT * FROM clusters WHERE stream_id=". $partition_id;    
    $counter=0;
    
    // on importe les données
     foreach ($dbh->query($sql) as $ligne){        
        $cluster_id_exist = array_search($ligne['cluster_univ_id'],$listed_clusters);
        if (is_bool($cluster_id_exist)) {//le cluster n'est pas encore répertorié
            array_push($listed_clusters,$ligne['cluster_univ_id']);
            
            $phylo['local_id'][] = $ligne['cluster_id']; // identifiant universel            
            $phylo['cluster_univ_id'][] = $ligne['cluster_univ_id']; // identifiant universel            
            $p = split('_', $ligne['period']);
            $phylo['period1'][] = $p[0];
            $phylo['period2'][] = $p[1];
            
            $phylo['label'][] = $ligne['cluster_label'];
            
            $phylo['length_to_end'][] = 0;
            $phylo['length_to_start'][] = 0;
            
            $phylo['exit'][] = 0; // marqueur utile pour la suite pour voir s'il le noeud doit encore être traité dans la spatialisation
            $phylo['count'][] = 0; // pour le debugg
            
            // on récupère pères et fils       
            $sql_sons="SELECT current_cluster_univ_id FROM `phylogeny` WHERE previous_cluster_univ_id=" . $ligne['cluster_univ_id'].'"';
            $sql_fathers="SELECT previous_cluster_univ_id FROM `phylogeny` WHERE current_cluster_univ_id=" . $ligne['cluster_univ_id'].'"';
                     
            $sons = array();
            $fathers = array();
            
            foreach ($dbh->query($sql_sons) as $row){  
                $sons[] = $row['current_cluster_univ_id'];
            }

            
            foreach ($dbh->query($sql_fathers) as $row){  
                $fathers[] = $row['previous_cluster_univ_id'];
            }
                                 
            $phylo['fathers'][] = $fathers;
            $phylo['sons'][] = $sons;
            $phylo['x'][]    = 0; // positions initialisées en 0
            $phylo['y'][] = 0;                        
            $count+=1;
           
        }
        
        }
    
    $period_uniques = $phylo['period1'];
    
    $period_uniques_reverse=array_unique($period_uniques);// périodes par ordre décroissant
    sort($period_uniques);
    $period_uniques=array_reverse($period_uniques); 
    ///$nb_periodes=$period_uniques[-1]-$period_uniques[0];   
    
    $clusters_processed = array();
    // On calcule pour chaque cluster sa distance à l'extremité de sa branche 
    foreach ($period_uniques_reverse as $current_period) {
        $clusters_rank = array_keys($phylo['period1'], $current_period);
        foreach ($clusters_rank as $cluster_rank) {
            $clusters_processed[$cluster_rank] = 0; // on initialise le marqueur de traitement de la spatialisation (pour plus tard)
            $length_to_end = 0;
            //pta($phylo['sons'][$cluster_rank]);
            if (!empty($phylo['sons'][$cluster_rank])) {                               
                for ($j = 0; $j < count($phylo['sons'][$cluster_rank]); $j++) {
                    if ($phylo['length_to_end'][array_search($phylo['sons'][$cluster_rank][$j], $phylo['cluster_univ_id'])] > $length_to_end - 1) {
                        $length_to_end = $phylo['length_to_end'][array_search($phylo['sons'][$cluster_rank][$j], $phylo['cluster_univ_id'])] + 1;
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
                    if ($phylo['length_to_start'][array_search($phylo['fathers'][$cluster_rank][$j], $phylo['cluster_univ_id'])] > $length_to_start - 1) {
                        $length_to_start = $phylo['length_to_start'][array_search($phylo['fathers'][$cluster_rank][$j], $phylo['cluster_univ_id'])] + 1;
                    }
                }
                $phylo['length_to_start'][$cluster_rank] = $length_to_start;
            }
        }unset($cluster_rank);
    }unset($current_period);

///////////////////////////////////////////
//Spatialisation de la phylo
///////////////////////////////////////////

    
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
                $index=array_search($value, $phylo['cluster_univ_id']);
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
                $index=array_search($value, $phylo['cluster_univ_id']);
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
    $dbh=NULL;
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

function map($x,$xmin,$xmax,$X,$Y){
    // normalise $x entre $X et $Y sachant que les valeurs parcourent $xmin,$xmax   
    if ($xmax==$xmin){
        return 40;
    }else{
        return $X+($x-$xmin)/($xmax-$xmin)*($Y-$X);
    }
    
}    
?>
