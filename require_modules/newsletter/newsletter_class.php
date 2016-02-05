<?
class Admin_Newsletter extends NObject{
    
  public static function show(){
  
  	if( isset($_GET['ajax_action_section']) AND $_GET['ajax_action_section'] == 'save_description'){
  		dibi::query("
  			UPDATE [newsletter_emails] 
  			SET description = %s",$_POST['description'],"WHERE id_newsletter_emails = %i",$_POST['id_newsletter_emails']);
  		exit;
  	}
  	MT::addTemplate(APP_DIR.'/require_modules/newsletter/default.phtml','newsletter');
  	
  	$ds = NewsletterModel::getDatasource();
  	
  	if(isset($_GET['id_newsletter_delete'])){
  		NewsletterModel::delete($_GET['id_newsletter_delete']);
  		header('location: admin.php?section=newsletter');
  		exit;	
  	}
  	
  	if(isset($_GET['export'])){
  		$dse = clone $ds;
  		MT::addVar('newsletter', 'export', $dse->where('active = 1 AND id_newsletter_emails IN %l', $_GET['id'])->fetchAll());
  	}
  	
  	if(@$_GET['order_by'] == 'email'){
  		$ds->orderBy('email');	
  	}else{
  		$ds->orderBy('adddate');
  	}
  	
  	
  	//$vp = new MyPaginator($ds, 10);
  	
  	MT::addVar('newsletter', 'emails', $ds->fetchAll());
//  	MT::addVar('newsletter', 'vp', $vp);
  }

}