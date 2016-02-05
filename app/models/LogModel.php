<?php 
class LogModel extends NObject{
	function getDataSource(){
		return dibi::dataSource("SELECT * FROM log");
	}
	
	public static function save( $set, $id_log ) {
		return dibi::query( "UPDATE log SET ", $set, "WHERE id_log = %i", $id_log );
	}
}