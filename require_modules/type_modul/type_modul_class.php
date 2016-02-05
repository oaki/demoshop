<?
/*----nahra vsetky globalne premenne z db-----*/
class type_modul{
  
  function __construct(){
  
  }  
  
  function showForm(){  
  	$session = NEnvironment::getSession('Authentication');  
      if($session["superadmin"]!=1){ 
      	header("Location: admin.php"); exit; 
      }
      try{
        if(isset($_POST['addModul'])){
          $this->addModul();
        }
        
        if(isset($_POST['load_modul'])){
          $this->loadModulFile();
        }
        
        if(isset($_GET['id_type_modul'])){
          $this->deleteModul();
        }
      }catch(Exception $e){?>
        <div style="border:2px solid red;padding:5px;">
          <?echo $e->getMessage();?>
        </div><?        
      }     
    ?>
  <form method="post" enctype="multipart/form-data" action="">
    <label>Nahratie nového modulu (.zip)</label> <input type="file" name="modul_file_zip" />
    <input type="submit" name="load_modul" value="Nahrať" />
  </form>
   <form id="formAddModul" action="" method="post" style="padding:5px 0px;">
      <label>Meno modulu</label>
      <input type="text" name="name" value="" />      
       Modul: <select name="dir">
        <?
        $dir = "moduls/";
        echo filetype($dir);
        if (is_dir($dir)) {
          if ($dh = opendir($dir)) {
              while (($file = readdir($dh)) !== false) {
                  if($file!="." AND $file!=".."){?> <option value="<?=$file?>"><?=$file?></option><?}                  
              }
              closedir($dh);
          }
        }
        ?>       
      </select>
      Zobraziť <select style="width:50px;" name="visible_for_user"><option value="1">áno</option><option value="0">nie</option></select>
      <input type="submit" class="sub" name="addModul" value="Pridat" />
    </form>

    <form action="" method="post">      
      <table id="highlight">
      <thead><tr><th></th><th>Názov</th><th>Modul</th><th>Zobraziť</th><th>Zmazať</th></tr></thead>
      <tbody>
      <?      
     $list = dibi::fetchAll("SELECT * FROM type_modul ORDER BY name");
      foreach($list as $l){
        ?><tr>
        <td><img src="/moduls/<?=$l['dir']?>/icon.gif" alt="<?=$l['name']?>" /></td><td><?=$l['name']?></td><td><?=$l['dir']?></td><td><?=$l['visible_for_user']?></td><td><a href="javascript:confl('Naozaj chcete odstrániť <?=$l['name']?>','?section=admin_modules&amp;id_type_modul=<?=$l['id_type_modul']?>');"><img src="<?=RELATIVE_PATH;?>templates/global_img/delete.gif" alt="Zmazat" /></a></td>
        </tr><?
      }
      ?>
      </tbody>
      </table>
      <script type="text/javascript">	
          	var th = new tableHighlighter( 'highlight' );	
      </script>
    </form>    
    <?
  }
  
  function addModul(){
    if($_POST['name']=="")$_POST['name']=$_POST['dir'];
    if(dibi::fetch("SELECT 1 FROM type_modul WHERE dir=%s",$_POST['dir']) != 1){    
     dibi::query("INSERT INTO type_modul ", array('name'=>$_POST['name'],'dir'=>$_POST['dir'],'visible_for_user'=>$_POST['visible_for_user']));
    }else{
      throw new Exception("Dany modul bud neexistuje alebo uz bol pridany");
    }        
  }
  

  function deleteModul(){
    $s = $this->db->prepare("SELECT * FROM type_modul WHERE id_type_modul=:1")->exe($_GET['id_type_modul']);
    if($s->numRows()==1){
      $this->db->prepare("DELETE FROM node WHERE id_type_modul=:1")->exe($_GET['id_type_modul']);
      $this->db->prepare("DELETE FROM type_modul WHERE id_type_modul=:1")->exe($_GET['id_type_modul']);
    }else{
      throw new Exception("Zadane nespravne id.");
    }
  }
  
  function loadModulFile(){
    if (is_uploaded_file($_FILES['modul_file_zip']["tmp_name"])){
      if(preg_match('/\\.(zip)$/i', $_FILES['modul_file_zip']['name'])){
        require_once 'classes/unzip_class.php';
        $zip = new zip();
        $zip->unzip($_FILES['modul_file_zip']["tmp_name"], "moduls/", $create_zip_name_dir=true, $overwrite=true);
      }else{
        throw new Exception("Musite nahravat zip subor");
      }
      
    }
    
  }
  
  
}
?>