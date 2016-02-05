<?php
class Admin_Comment extends NObject {
	private static $bufferCommentTree;
	private static $bufferOutput;
	
	static public function action($redirect = true) {
		if (isset ( $_GET ['action'] )) {
			switch ($_GET ['action']) {
				case 'allow' :
					self::allow ( $_GET ['id_comment'] );
					break;
				
				case 'delete' :
					self::delete ( $_GET ['id_comment'] );
					break;
			}
			
			if($redirect)
				header('Location:admin.php?section=comment');
		}
	}
	
	static public function commentList() {
		$list = dibi::fetchAll ( "
		SELECT 
			comment.*, 
			menu_item.url_identifier AS menu_url_identifier,
			article.url_identifier,
			DATE_FORMAT(comment.adddate, '%d.%m.%Y %H:%i:%s') AS adddate_formated,
			article.title AS article_title 
		FROM 
			[comment] 
			JOIN article USING(id_node)
			LEFT JOIN [node] USING(id_node)
			LEFT JOIN [menu_item] USING(id_menu_item) 
		WHERE 
			comment.status=0
		ORDER BY comment.adddate
		" );
		
		MT::addTemplate ( dirname ( __FILE__ ) . '/admin_commentList.phtml', 'comment' );
		MT::addVar ( 'comment', 'list', $list );
	}
	
	static public function commentListForArticle($id_node) {
		$list = dibi::query ( "
		SELECT 
			comment.*,
			menu_item.url_identifier AS menu_url_identifier,
			article.url_identifier, 
			DATE_FORMAT(comment.adddate, '%d.%m.%Y %H:%i:%s') AS adddate_formated,
			article.title AS article_title 
		FROM 
			[comment] 
			JOIN [article] USING(id_node) 
			LEFT JOIN [node] USING(id_node)
			LEFT JOIN [menu_item] USING(id_menu_item) 
		WHERE
			article.id_node=%i",$id_node,"	 
		ORDER BY comment.adddate
		" )->fetchAssoc("comment_parent,#");
		
		
		return $list;
	}
	
	static public function getCommentListForArticle($id_node) {
		self::doCommentTree($id_node);
		MT::addTemplate ( dirname ( __FILE__ ) . '/Admin_commentlist_edit.phtml', 'comment' );
		MT::addVar ( 'comment', 'comments_buffer', self::$bufferOutput );
		
		
	}
	
	static function doCommentTree($id_node, $parent=NULL, $level = 0){
		if(empty(self::$bufferCommentTree))
			self::$bufferCommentTree = self::commentListForArticle($id_node);
			
		if(!empty(self::$bufferCommentTree[$parent])){
			foreach(self::$bufferCommentTree[$parent] as $l){
				self::$bufferOutput.='<tr>
					<td>
						'.$l['adddate_formated'].'
					</td>
					<td style="padding-left: '.($level*20).'px;">
						<b>Meno:</b> '.htmlspecialchars($l['name']).'<br />
						<b>Text: </b>'.htmlspecialchars($l['text']).'
					</td>
					<td>
						';
				if ($l['status']==0){
					self::$bufferOutput.='<a href="/admin.php?id_menu_item='.$_GET['id_menu_item'].'&id_type_modul='.$_GET['id_type_modul'].'&id_modul='.$_GET['id_modul'].'&id_comment='.$l['id_comment'].'&action=allow#comments">Povoliť</a> |';
				};
				
				self::$bufferOutput.='<a href="/admin.php?id_menu_item='.$_GET['id_menu_item'].'&id_type_modul='.$_GET['id_type_modul'].'&id_modul='.$_GET['id_modul'].'&id_comment='.$l['id_comment'].'&action=delete#comments">Zmazať</a> |
					<a target="_blank" href="/'.$l['menu_url_identifier'].'/'.$l['url_identifier'].'.html">Link</a>
						</td>
					</tr>';
				
				self::doCommentTree($id_node, $l['id_comment'], $level+1);
			}
		}
	}
	
	static private function allow($id_comment) {
		dibi::query ( "UPDATE comment SET status=1 WHERE id_comment=%i", $id_comment );
	}
	
	static private function delete($id_comment) {
		dibi::query ( "DELETE FROM comment WHERE id_comment=%i", $id_comment );
	}
}