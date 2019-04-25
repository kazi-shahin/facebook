<?php 
//composer require facebook/graph-sdk
require_once __DIR__ . '/vendor/autoload.php'; // change path as needed


if(isset($_SERVER['HTTPS'])){
    $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
}
else{
    $protocol = 'http';
}
$baseurl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$facebook = new \Facebook\Facebook([
  'app_id' => '',
  'app_secret' => '',
  'default_graph_version' => 'v2.10',
  //'default_access_token' => '{access-token}', // optional
]);
  $permissions = ['publish_actions','manage_pages','publish_pages','user_posts','user_managed_groups']; 
// Use one of the helper classes to get a Facebook\Authentication\AccessToken entity.
  $fb = $facebook->getRedirectLoginHelper();
  

//   $helper = $fb->getJavaScriptHelper();
//   $helper = $fb->getCanvasHelper();
//   $helper = $fb->getPageTabHelper();

if($_GET['auth-status'] == 'success' &&  $_GET['auth-from'] == 'facebook'){
	echo 'Facebook successfully!';
} 
elseif($_GET['from']=='fb'){
	try {

			$fb_tokens = array();
			$accessToken = $fb->getAccessToken();

			$oAuth2Client = $facebook->getOAuth2Client();

			$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);

			$fb_tokens['user_token'] = (string) $longLivedAccessToken;

			$facebook->setDefaultAccessToken($longLivedAccessToken);

			$response = $facebook->get('/me/accounts?fields=access_token');

			$graphEdge = $response->getGraphEdge();

			if ($graphEdge) :
				$page_tokens = array();

				foreach ($graphEdge as $graphNode) {
	  				$page = $graphNode->asArray();
	  				$page_id = $page['id'];
	  				$request = $facebook->get('/'.$page_id.'?fields=access_token');

					$result = $request->getGraphObject()->asArray();
					$pageToken = $result['access_token'];
					
					$page_tokens[$page_id] = $pageToken;
				}

				$fb_tokens['page_tokens'] = $page_tokens;

			endif;

			if (!empty($fb_tokens)) {
				$fb_access_tokens = json_encode($fb_tokens);
				header('Location: '.$baseurl.'?auth-status=success&auth-from=facebook');
			}

		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();
		  exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  exit;
		}

	// $me = $response->getGraphUser();
	// echo 'Logged in as ' . $me->getName();
}else{

	$fb_loginUrl = $fb->getLoginUrl($baseurl."?from=fb", $permissions);
	header('Location: '.$fb_loginUrl);
}