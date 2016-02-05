<?php
class Front_SearchingPresenter extends Front_BasePresenter{
	function renderDefault($query){
		$this->template->query = $query;
		if($query != ''){
			$vp = new VisualPaginator($this, 'vp');

			try{
				$datasource = ProductModel::searching($this->id_lang, $query);

				$paginator = $vp->getPaginator();
				$paginator->itemsPerPage = 2;
				$paginator->itemCount = $itemsCount = count($datasource);
				
				if($itemsCount == 0)
					throw new ProductException( _('Hľadaný výraz sa nenašiel.'));

				$this->template->searchingItems = $datasource->applyLimit($paginator->itemsPerPage, $paginator->offset)->fetchAll();

				//zisti pre kazdy clanok url_identifier


				foreach($this->template->searchingItems as $k=>$i){
					$this->template->searchingItems[$k]['url'] = $this->getPresenter()->link('Eshop:current', array('categories'=> $i['category_link_rewrite'] , 'url_identifier'=>$i['link_rewrite']));
					$image = FilesNode::getOneFirstFile('product', $i['id_product']);
					if($image){
						$image['thumbs'] = Files::gURL($image['src'], $image['ext'], 100, 70, 5);
						$this->template->searchingItems[$k]['image'] = $image;
					}
				}
				
			}catch(ProductException $e){
				$this->flashMessage($e->getMessage());
			}

		}else{
			$this->redirect('Homepage');
		}




//
//		try{
//
//			$this->template->searching_list = ProductModel::searching($this->lang, $query)->fetchAll();
//			if(empty($this->template->searching_list)){
//				$this->flashMessage('Hľadaný výraz sa nenašiel.');
//			}
//			foreach($this->template->searching_list as $k=>$l){
//
//				$this->template->searching_list[$k]['url'] = $this->link('List:current',
//					array('categories'=> MenuModel::getUrl($l['id_menu_item']) ,
//					'url_identifier'=>$l['url_identifier']) );
//
//			}
//
//		}catch(ProductException $e){
//			$this->flashMessage($e->getMessage());
//		}
//		
		
	}
}