<?php
    require("utils.php");
    require("simple_html_dom.php");

    // Procédure qui va permettre de mettre à jour les items contenus dans la base omeka,
    // à partir des métadonnées exposées dans le sudoc
    
    // On va récupérer le code du champ PPN
    $result = SQL("select id from elements where name like 'PPN'");
    $row = mysql_fetch_assoc($result);
    $id_ppn = $row["id"];
    
    // On va aller liste l'ensemble des PPN de la base
    $res_ppn = SQL("select record_id, text from element_texts where element_id=".$id_ppn." order by record_id");
   
		// Ou un seul. Utiliser cette requête pour ne mettre à jour qu'une notice
//		$res_ppn = SQL("select record_id, text from element_texts where text =  'PPN116388544'");
    while ($row_ppn = mysql_fetch_assoc($res_ppn))
    {
      $record_id = $row_ppn["record_id"];
      $ppn = $row_ppn["text"];
      $ppn = preg_replace("/^PPN/", "", $ppn);
      
      // On a ici les PPN des notices, on va pour chacune aller récupérer sur le sudoc les informations qui nous intéressent
      $url_bplus = "http://babordplus.univ-bordeaux.fr/notice.php?q=provenance:PPN".$ppn."&spec_expand=1&start=0&ct=bx3_alone&ce={UAI}0331766R";
      $url_sudoc = "http://www.sudoc.fr/".$ppn;
      $page = file_get_html($url_bplus);
      $notice = $page->find("div[class=sid-result-notice] div[class=sid-infos]", 0);
      $liste = $notice->find("dl", 0);
      $liste_plus = $page->find("div[id=sid-tab-fiche-technique] dl", 0);
      
      $code = "";
      
      $tableau_notice = Array();
      
      foreach ( $liste->find("dd,dt") as $elt_liste)
      {
          $valeur = $elt_liste->plaintext;
          $valeur = preg_replace("/&nbsp;/", " ", $valeur);
          $valeur = trim($valeur);
					$valeur = html_entity_decode($valeur);
          if (preg_match("/:$/", $valeur))
          {
              $code = $valeur;
              $code = preg_replace("/ ?:$/", "", $code);
          }
          else
          {
              // On est sur une valeur classique
              if (!isset($tableau_notice[$code]))
              {
                  $tableau_notice[$code] = Array();
              }
              $tableau_notice[$code][] = $valeur;
          }
      }

      $code = "";
      foreach ( $liste_plus->find("dd,dt") as $elt_liste)
      {
          $valeur = $elt_liste->plaintext;
          $valeur = preg_replace("/&nbsp;/", " ", $valeur);
          $valeur = trim($valeur);
					$valeur = html_entity_decode($valeur);
          if (preg_match("/:$/", $valeur))
          {
              $code = $valeur;
              $code = preg_replace("/ ?:$/", "", $code);
          }
          else
          {
              // On est sur une valeur classique
              if (!isset($tableau_notice[$code]))
              {
                  $tableau_notice[$code] = Array();
              }
              $tableau_notice[$code][] = $valeur;
          }
      }
      
      // $publisher
      $publisher = "";
      $publisher = getFieldBP("Lieu de publication", true);
      if ($publisher != "")
      {
          $publisher .= " : ";
      }
      $publisher .= getFieldBP("Éditeur", true);
      updateField("Publisher", $publisher);
      
      // $titre
      $titre = trim($notice->find("h5", 0)->plaintext);
      updateField("Title", $titre);

      // $creator
      if ($tab_auteur = getFieldBP("Auteur"))
      {
        updateField("Creator", $tab_auteur);
      }
      
      // $tab_contributeur
      if ($tab_contributeur  = getFieldBP("Contributeur"))
      {
        updateField("Contributor", $tab_contributeur);
      }
      
      // $date
      if ($date = getFieldBP("Date de publication", true))
      {
        updateField("Date", $date);
      }
      
      // $format
      if ($collation = getFieldBP("Collation", true))
      {
        updateField("Format", $collation);
      }
    }
    
    function getFieldBP($code, $unique = false)
    {
        global $tableau_notice;
        if (isset($tableau_notice[$code]))
        {
            if ($unique)
            {
                return $tableau_notice[$code][0];
            }
            else
            {
                return $tableau_notice[$code];
            }
        }
        else
        {
            return false;
        }
    }
    
    function updateField($code_dest, $source)
    {
      // Cette fonction va aller mettre à jour s'il existe, ou insérer sinon
      // le champ DC concerné
      // TODO : Voir comment gérer les champs multivalués
      global $record_id;
      
      // On va chercher l'identifiant du champ Creator
      $result = SQL("select id from elements where name like '$code_dest'");
      if (mysql_numrows($result) != 1)
      {
        print "On a un problème pour $code_dest";
      }
      $row = mysql_fetch_assoc($result);
      $element_id_dest = $row["id"];
      
      // On va regarder ce qu'on a pour le moment dans le champs concerné
      $res_existant = SQL("select * from element_texts where element_id=".$element_id_dest." and record_id = $record_id");
      $tab_existants = Array();
      while ($row_existant = mysql_fetch_assoc($res_existant))
      {
        $id_existant = $row_existant["id"];
        array_push($tab_existants, $id_existant);
      }
      
      // On a dans un tableau la liste des valeurs existantes, on va donc pouvoir les mettre à jour
      if (!is_array($source)) {
        $source = Array($source);
      }
      
      foreach ($source as $uneSource)
      {
        // On va faire la mise à jour
				$valeur = html_entity_decode($uneSource);
				$valeur = addslashes($valeur);
        if ($id_update = array_shift($tab_existants))
        {
          print "Mise à jour de $id_update\n";
          SQL("update element_texts set text='".$valeur."' where id = $id_update");
        }
        else
        {
          print "Insertion d'un élément\n";
          $sql = "insert into element_texts (`record_id`, `record_type_id`, `element_id`, `text`) values ('$record_id', '2', '$element_id_dest', '".$valeur."')"; 
          SQL($sql);
        }
      }
      
      // S'il reste des champs à la fin c'est qu'on en avait moins dans la notice B+ que
      // dans la notice source, on doit donc faire une mise à jour
			while ($id_delete = array_shift($tab_existants))
			{
				$sql = "delete from element_texts where id = $id_delete";
				SQL($sql);
			}
		}
?>
