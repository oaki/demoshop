<?php
class modul{
  private $db;
  function __construct($db){
    $this->db = $db;
  }
  
  
  
  function showModuls(){
    $s = $this->db->prepare("SELECT * FROM node WHERE id_menu_item=:1 ORDER BY sequence")->exe($_GET['id_menu_item']);

      ?><ul><?
      while($l = $s->fetchArray()){
        ?>
        <li>
          <?=$l['id_node'].$l['id_type_modul'];?>
        </li><?
      }
      ?></ul><?
  }
  
  function modulAction(){
    if(isset($_GET['id_menu_item']) AND !isset($_GET['id_type_modul'])){
      $s = $this->db->prepare("SELECT * FROM node WHERE id_menu_item=:1 ORDER BY sequence")->exe($_GET['id_menu_item']);
        if($s->numRows()==1){
         header("Location: admin.php?id_menu_item=".$_GET['id_menu_item']."&id_type_modul=".$s->result());
         exit;
      }
    }    
  }
  
  
}
?>
