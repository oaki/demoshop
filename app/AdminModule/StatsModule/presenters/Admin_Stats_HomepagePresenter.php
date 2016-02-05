<?php

/**
 * Description of Admin_SettingPresenter
 *
 * @author oaki
 */


class Admin_Stats_HomepagePresenter extends Admin_Stats_BasePresenter {

	
	public function actionGoogle() {
		
		$oAnalytics = new GoogleAnalytics('servis@vizion.sk', 't8KmiMfc5ehBtA');
		$oAnalytics->setProfileByName('www.propagacnepredmety.sk');
		$oAnalytics->setDateRange(date('Y-m-d',time()-60*60*24*40), date('Y-m-d',time()-60*60*24*1));
		$this->template->visitors = $oAnalytics->getVisitors();
		$this->template->keywords = $oAnalytics->getData(array(   'dimensions' => 'ga:keyword',
												'metrics'    => 'ga:visits',
												'sort'       => 'ga:keyword'));
		print_r($this->template->keywords);
//		echo '<pre>';
//		// print out visitors for given period
//		print_r($oAnalytics->getVisitors());
//
//		// print out pageviews for given period
//		print_r($oAnalytics->getPageviews());
//
//		// use dimensions and metrics for output
//		// see: http://code.google.com/intl/nl/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
//		print_r($oAnalytics->getData(array(   'dimensions' => 'ga:keyword',
//												'metrics'    => 'ga:visits',
//												'sort'       => 'ga:keyword')));
	}

	public function renderKeywords() {
		$stats = \Stats\StatsModel::init();
//		$stats->getKeywordsFromPage( 'http://www.vizion.sk/produkty/');
		$google_stats = $stats->getKeywordsFromGoogle( 'http://www.matrace-rosty.sk');
//		print_r($google_stats);exit; 
		$pages = array();
//		dump($google_stats['result']);exit;
		foreach($google_stats['keywords'] as $k1=>$keyword){
			
			foreach($keyword['google_first_urls'] as $k2=>$url){
				
				try{
					$google_stats['keywords'][$k1]['google_first_urls'][$k2] = $stats->getInfoFromMyPage('http://'.$url);
				}catch( Exception $e){
					echo $url.'|'.$e->getMessage()."<br>";
				}
			}
		}
		
		
		print_r($google_stats);
		exit;
	}
	

}