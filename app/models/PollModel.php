<?php
class PollModel extends NObject{
	public static function add($values){
		dibi::query("INSERT INTO poll ",$values);
		return dibi::insertId();
	}

	public static function getFluent(){
		return dibi::select('*')
			->from('poll');
	}

	public static function get($id_poll){
		return self::getFluent()->where('id_poll = %i',$id_poll)->fetch();
	}

	public static function delete($id_poll){
		dibi::query("DELETE FROM poll_answer WHERE id_poll=%i",$id_poll);
		dibi::query("DELETE FROM poll WHERE id_poll=%i",$id_poll);
	}

	public static function edit($values, $id_poll){
		dibi::query("UPDATE poll SET ",$values," WHERE id_poll=%i",$id_poll);
	}



	/*
	 * ANSWER
	 */
	public static function getAnswerFluent( $id_poll ){
		return dibi::select('*')
			->from('poll_answer')
				->where('id_poll = %i',$id_poll);
	}

	public static function addAnswer($values){
		$id_poll = $values['id_poll'];
		$sequence = dibi::fetchSingle("SELECT MAX(sequence)+1 FROM  poll_answer WHERE id_poll=%i",$id_poll);
		$values['sequence'] = $sequence;
		dibi::query("INSERT INTO poll_answer ",$values);
	}

	public static function editAnswer($values, $id_poll_answer){
		dibi::query("UPDATE poll_answer SET ",$values," WHERE id_poll_answer=%i",$id_poll_answer);
	}

	public static function deleteAnswer($id_poll_answer){
		dibi::query("DELETE FROM poll_answer WHERE id_poll_answer=%i",$id_poll_answer);
	}
	

}