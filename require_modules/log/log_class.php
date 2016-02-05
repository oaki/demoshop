<?
class Log extends NObject {
	
	function __construct() {
		if(isset($_GET['delete_all'])){
			self::deleteAll();
			header('location: admin.php');
			exit;
		}
	}
	
	public static function addGlobalLog($type = 0, $value = NULL ){
		$arr = array(
			'HTTP_USER_AGENT' => @$_SERVER['HTTP_USER_AGENT'],
			'HTTP_REFERER' => @$_SERVER['HTTP_REFERER'],
			'HTTP_COOKIE' => @$_SERVER['HTTP_COOKIE'],
			'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
			'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
			'REQUEST_URI' => $_SERVER['REQUEST_URI'],
			'POST' => http_build_query($_POST),
			'type' => $type,
			'value'=>$value,
		);
		dibi::query("INSERT INTO [log_user_global]", $arr);
	}
	
	public static function addLog($name_modul, $description) {
		$last_query = dibi::$sql;
		$session = NEnvironment::getSession('Authentication');
		$arr = array (
			'id_user' => @$session['id_user'], 
			'name_modul' => get_class ( $name_modul ), 
			'description' => $description, 
			'value' => @$value, 
			'query' => $last_query, 
			'date' => new DibiDateTime, 
			'ip' => $_SERVER ['REMOTE_ADDR'], 
		);
		
		dibi::query ( "INSERT INTO log ", $arr );
	
	}
	
	
	function getLogDatasource(){
		return dibi::dataSource("SELECT * FROM log");
	}
	
	function showLog() {
		$session = NEnvironment::getSession('Log');
		
		if (isset ( $_GET ['order_by'] )) {
			if ($_GET ['order_by'] != "date" and $_GET ['order_by'] != "id_user" and $_GET ['order_by'] != "description" and $_GET ['order_by'] != "value" and $_GET ['order_by'] != "value" and $_GET ['order_by'] != "id_type_modul" and $_GET ['order_by'] != "ip") {
				$session ['log_order_by'] = "date";
			} else {
				$session ['log_order_by'] = $_GET ['order_by'];
			}
			;
		}
		if (! isset ( $session ['log_order_by'] ))
			$session ['log_order_by'] = "date";
			
		$source = dibi::dataSource("
			SELECT log.*, user.login  
			FROM log LEFT JOIN user USING(id_user) 
			WHERE
				%if",(@$_GET['input_id_user']!=''),"login LIKE %s", '%'.@$_GET['input_id_user'].'%'," AND %end
		      	%if",(@$_GET['input_date']!=''),"[date] > %s", @$_GET['input_date']," AND %end
			    %if",(@$_GET['input_description']!=''),"description LIKE %s", '%'.@$_GET['input_description'].'%'," AND %end
			    %if",(@$_GET['input_value'] != ''),"value LIKE %s", '%'.@$_GET['input_value'].'%'," AND %end
			    %if",(@$_GET['input_id_type_modul']!=''),"name_modul LIKE %s", '%'.@$_GET['input_id_type_modul'].'%'," AND %end
			    ip LIKE %s", '%'.@$_GET['input_ip'].'%'," 
		    
		    ORDER BY ".$session ['log_order_by']." DESC");
	
		
		$p = new MyPaginator($source);
		
		MT::addTemplate(dirname(__FILE__).'/log.phtml', 'log');
		MT::addVar('log', 'paginator',$p);
		MT::addVar('log', 'list',$p->getDataSourceItem()->fetchAll());
		
	}		
	
	static function deleteAll(){
		dibi::query("DELETE FROM [log]");
	}
}
