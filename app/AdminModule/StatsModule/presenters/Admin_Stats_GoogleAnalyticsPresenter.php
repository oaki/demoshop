<?php

/**
 * Description of Admin_StatsGoogleAnalyticsPresenter
 *
 * @author oaki
 */
class Admin_Stats_GoogleAnalyticsPresenter extends BasePresenter {

	public function actionDefault(){
		$google_config = NEnvironment::getConfig()->google;
		
		require_once LIBS_DIR.'/google-api-php-client/src/apiClient.php';
		require_once LIBS_DIR.'/google-api-php-client/src/contrib/apiOauth2Service.php';
		require_once LIBS_DIR.'/google-api-php-client/src/contrib/apiAnalyticsService.php';

		$client = new apiClient();
		$client->setApplicationName('Google+ PHP Starter Application');
		// Visit https://code.google.com/apis/console?api=plus to generate your
		// client id, client secret, and to register your redirect uri.
//		$client->setClientId( $google_config['client_id'] );
//		$client->setClientSecret( $google_config['client_secret'] );
		$client->setRedirectUri( $google_config['redirect_url'] );
		
//		$client->setDeveloperKey('AIzaSyCrViGDrmXAiLsQAoW1aOzkHddH9gHYzzs');
		
//		[8] => Array
//        (
//            [title] => www.propagacnepredmety.sk
//            [entryid] => http://www.google.com/analytics/feeds/accounts/ga:43556790
//            [accountId] => 17205615
//            [accountName] => www.vizion.sk
//            [profileId] => 43556790
//            [webPropertyId] => UA-17205615-3
//            [tableId] => ga:43556790
//        )
		
		
		$ga = new apiAnalyticsService( $client);
		
		
		if (isset($_GET['code'])) {
			$ga->authenticate();
			$_SESSION['token'] = $client->getAccessToken();
			header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
		}

		if (isset($_SESSION['token'])) {
			$client->setAccessToken($_SESSION['token']);
		}

		if ($client->getAccessToken()) {
			$activities = $plus->activities->listActivities('me', 'public');
			print 'Your Activities: <pre>' . print_r($activities, true) . '</pre>';

			// The access token may have been updated.
			$_SESSION['token'] = $client->getAccessToken();
		} else {
			$authUrl = $client->createAuthUrl();
			print "<a class='login' href='$authUrl'>Connect Me!</a>";
		}
//		$_SESSION['token'] = $client->getAccessToken();

		
		$data = $ga->data_ga;
		$d = $data->get('17205615', 
				date('Y-m-d',time()-60*60*24*40), 
				date('Y-m-d',time()-60*60*24*1),
				'ga:visits,ga:pageviews'
		);
		print_r($d);
		
		exit;

	}
	


	public function renderDefault() {
		
	}

}