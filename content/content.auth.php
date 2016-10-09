<?php 
	require_once(TOOLKIT . '/class.administrationpage.php');
	
	class contentExtensionGoogle_apiAuth extends AdministrationPage {

		public function __construct(){
			parent::__construct();

			$driver = Symphony::ExtensionManager()->create('google_api');


			$client = $driver->getClient();

			if (isset($_GET['code'])) {
				// $client->authenticate($_GET['code']);
				$accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    			file_put_contents($driver->credentialsPath, json_encode($accessToken));

				$redirect = SYMPHONY_URL . '/system/preferences';
				header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
				die;
			} else {
				die;
			}


		}
	
	}