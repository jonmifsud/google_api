<?php 
	
	class contentExtensionGoogle_apiAuth extends AdministrationPage {

		public function __construct(){
			parent::__construct();

			$driver = Symphony::ExtensionManager()->create('google_api');

			$client = $driver->getClient();

			if (isset($_GET['code'])) {
				$client->authenticate($_GET['code']);
				Symphony::Configuration()->set('token', $client->getAccessToken(), "google_api");
				Symphony::Configuration()->write();

				$redirect = SYMPHONY_URL . '/system/preferences';
				header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
				die;
			} else {
				die;
			}


		}
	
	}