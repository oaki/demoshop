<?php
class Front_SitemapPresenter extends Front_BasePresenter{
	function renderDefault(){
		
		$this->template->sitemap = dibi::fetchAll("SELECT * FROM [category] WHERE active = 1");
	}	
}