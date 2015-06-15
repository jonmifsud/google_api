<?php
	
	// require_once( EXTENSIONS . '/google_api' . '/lib/google-api/autoload.php');

	function google_api_autoload($className) {

		$classPath = explode('_', $className);
		if ($classPath[0] != 'Google') {
			return;
		}
		if (count($classPath) > 3) {
			// Maximum class file path depth in this project is 3.
			$classPath = array_slice($classPath, 0, 3);
		}
		$filePath = EXTENSIONS . '/google_api' . '/lib/google-api/src/' . implode('/', $classPath) . '.php';
			if (file_exists($filePath)) {
			require_once($filePath);
		}
	}

	spl_autoload_register('google_api_autoload');

	Class extension_google_api extends Extension {


		
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
    		$group->appendChild(new XMLElement('p','You need to provide an to provide the relevant details to connect with the google api',array('class'=>'help')));
    							
			$div = new XMLElement('div',null,array('class'=>'group'));
			$label = Widget::Label();
					$input = Widget::Input("settings[google_api][client_id]", (string)Symphony::Configuration()->get('client_id', "google_api"), 'text');
					$label->setValue(__('Client ID') . $input->generate());
					$div->appendChild($label);
			
			$label = Widget::Label();
					$input = Widget::Input("settings[google_api][secret]", (string)Symphony::Configuration()->get('secret', "google_api"), 'password');
					$label->setValue(__('Client Secret') . $input->generate());
					$div->appendChild($label);
			$group->appendChild($div);
			
			$div = new XMLElement('div',null,array('class'=>'group'));
			$label = Widget::Label();
					$input = Widget::Input("settings[google_api][developer_key]", (string)Symphony::Configuration()->get('developer_key', "google_api"), 'text');
					$label->setValue(__('Developer Key') . $input->generate());
					$div->appendChild($label);
			
			
			$label = Widget::Label();
					$input = Widget::Input("settings[google_api][scope]", (string)Symphony::Configuration()->get('scope', "google_api"), 'text');
					$label->setValue(__('Client Scope') . $input->generate());
					$div->appendChild($label);
			$group->appendChild($div);

			if (Symphony::Configuration()->get('client_id', "google_api") && !(Symphony::Configuration()->get('token', "google_api")) || true ){
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

			// Visit https://console.developers.google.com/ to generate your
			// client id, client secret, and to register your redirect uri.
			$client->setClientId(Symphony::Configuration()->get('client_id', "google_api"));
			$client->setClientSecret(Symphony::Configuration()->get('secret', "google_api"));
			$client->setRedirectUri('http://dev.forward.com/symphony/extension/google_api/auth/');
			// $client->setRedirectUri(SYMPHONY_URL . '/extension/google_api/auth/');
			// $client->setDeveloperKey(Symphony::Configuration()->get('developer_key', "google_api"));
			$client->setAccessType('offline');

			//if token exists
			if($withToken){
				$client->setAccessToken(Symphony::Configuration()->get('token', "google_api"));
			}

			return $client;
    	}

	}
