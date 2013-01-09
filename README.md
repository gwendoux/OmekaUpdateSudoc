OmekaUpdateSudoc
================

Mise à jour d'omeka à partir des informations contenues dans le sudoc.

Ce script va travailler directement dans la base de données d'omeka (informations à configurer dans utils.php). Pour que le script fonctionne, la base doit contenir un champ nommé "PPN" qui contienne le PPN de la notice en cours. A partir de cette info, le script va aller interroger l'interface publique du sudoc et mettre à jour les champs de la base omeka avec les informations qu'il va y trouver.

Attention, si la notice initiale omeka contient par exemple 3 auteurs et que la notice sudoc n'en contient que 2, la notice finale ne contiendra que les 2 auteurs sudoc, les champs en trop étant vidés après la mise à jour.

Les champs spécifiques qui auraient pu être créés et qui ne sont pas mis à jour via cette moulinette ne seront pas écrasés.

Utilisation
===========
- Modifier le fichier utils.php pour que les premières lignes correspondent à votre configuration de base
- S'assurer que le champ contenant le PPN dans votre base s'appelle bien "PPN". Sinon, modifier le nom du champ à la ligne 17 du script update_metadatas_from_sudoc.php
- Lancer la commande php update_metadatas_from_sudoc.php

Avertissements
==============
Ce script a été développé dans le cadre de la mise en place du projet 1886 de l'université Bordeaux 3 (http://1886.u-bordeaux3.fr). Il est mis à disposition tel quel et sans aucune garantie de fonctionnement.

Il fonctionnait en décembre 2012 mais est très lié à l'interface public du sudoc et pourrait ne plus être opérationnel en cas de changement effectué par l'ABES sur cette interface. 

Dans tous les cas nous vous conseillons de faire tourner ce script sur une copie de votre base de données, il peut potentiellement vider certains champs. 

En cas de question autour de ce script, veuillez contacter Sylvain Machefert : smachefert@u-bordeaux3.fr
