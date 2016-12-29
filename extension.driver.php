<?php
	
	require_once( EXTENSIONS . '/google_api' . '/vendor/autoload.php');

	Class extension_google_api extends Extension {

		var $credentialsPath;
		var $secretPath;
		var $scopes;

		function __construct(){
			$this->credentialsPath = MANIFEST . '/google_credentials.json';
			$this->secretPath = MANIFEST . '/google_client_secret.json';
		}
		
		/*------------------------------------------------------------------------------------------------*/
		/*  Delegates  */
		/*------------------------------------------------------------------------------------------------*/

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'appendPreferences'
				),
				array(
					'page' => '/system/preferences/',
					'delegate' => 'Save',
					'callback' => 'savePreferences'
				),
			);
		}

		/*------------------------------------------------------------------------------------------------*/
		/*  System preferences  */
		/*------------------------------------------------------------------------------------------------*/


		public function appendPreferences($context){
			$group = new XMLElement('fieldset',null,array('class'=>'settings'));
			$group->appendChild(new XMLElement('legend', 'Google API'));
	
			// Application Client
			$group->appendChild(new XMLElement('h3', 'Google API',array('style'=>'margin-bottom: 5px;')));
			$group->appendChild(new XMLElement('p','You need to provide an to provide the relevant details to connect with the google api. You can set the credentials by saving the json file as `manifest/google_client_secret.json`. After you save the scope an authorization link will appear below.',array('class'=>'help')));
			
			$label = Widget::Label();
					$input = Widget::Input("settings[google_api][scope]", (string)Symphony::Configuration()->get('scope', "google_api"), 'text');
					$label->setValue(__('Client Scope') . $input->generate());
			$group->appendChild($label);

			if (Symphony::Configuration()->get('scope', "google_api") && !file_exists($this->credentialsPath)){
			// if (Symphony::Configuration()->get('client_id', "google_api") && !(Symphony::Configuration()->get('token', "google_api"))){
				$client = $this->getClient();
				$authUrl = $client->createAuthUrl();

				$group->appendChild(new XMLElement('p',"Please <a href='{$authUrl}'>Authenticate here</a> to create a site-wide token",array('class'=>'help')));
			}

			// Append preferences
			$context['wrapper']->appendChild($group);
		}
	
		/*------------------------------------------------------------------------------------------------*/
		/*  Utilities  */
		/*------------------------------------------------------------------------------------------------*/


		public function getClient($scope=null,$withToken = true){

			if (!isset($scope)) 
				$scope = explode(',',Symphony::Configuration()->get('scope', "google_api"));

			$client = new Google_Client();
			$client->setScopes($scope);
			$client->setApplicationName('Symphony Google API');
			$client->setAuthConfig($this->secretPath);

			// Visit https://console.developers.google.com/ to generate your
			// client id, client secret, and to register your redirect uri.
			// $client->setClientId(Symphony::Configuration()->get('client_id', "google_api"));
			// $client->setClientSecret(Symphony::Configuration()->get('secret', "google_api"));
			// $client->setRedirectUri('http://dev.forward.com/symphony/extension/google_api/auth/');
			$client->setRedirectUri(SYMPHONY_URL . '/extension/google_api/auth/');
			// $client->setDeveloperKey(Symphony::Configuration()->get('developer_key', "google_api"));
			$client->setAccessType('offline');

			//if token exists
			if($withToken){
				if (file_exists($this->credentialsPath)) {
					$accessToken = json_decode(file_get_contents($this->credentialsPath), true);
					$client->setAccessToken($accessToken);

					if ($client->isAccessTokenExpired() || true) {
						$refreshTokenSaved = $client->getRefreshToken(); 
						$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

						$accessTokenUpdated = $client->getAccessToken();
						$accessTokenUpdated['refresh_token'] = $refreshTokenSaved;

						file_put_contents($this->credentialsPath, json_encode($accessTokenUpdated));
					}
				} 
				// $client->setAccessToken(Symphony::Configuration()->get('token', "google_api"));
			}

			return $client;
		}

	}
