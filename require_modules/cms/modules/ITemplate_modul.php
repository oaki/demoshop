<?

interface ITemplateModul {
	
	public function add($id_node);
	
	public function action();
	
	public function delete($id_node);
	
	public function showForm();
	
	public function showTitle($id_node, $id_type_modul);
	
	public static function duplicate($id_node, $new_id_node);
	
	
}