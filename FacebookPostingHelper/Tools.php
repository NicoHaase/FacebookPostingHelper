<?php
namespace FacebookPostingHelper;

use Config_Lite;
use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;

class FacebookPostingHelper
{

    /**
     *
     * @var string
     */
    private $configFilename;

    /**
     *
     * @var Config_Lite
     */
    private $config;

    public function __construct($fileName)
    {
        $this->configFilename = $fileName;
        $this->readConfig();
        FacebookSession::setDefaultApplication($this->getAppId(), $this->getAppSecret());
    }

    public function readConfig()
    {
        $this->config = new Config_Lite($this->configFilename);
    }

    public function writeConfig()
    {
        $this->config->save();
    }

    private function getFromConfig($section, $key, $showError = false)
    {
        if ($this->config->has($section, $key)) {
            return $this->config->get($section, $key);
        }
        if($showError) {
            throw new Exception(sprintf('Missing key %s in  section %s', $key, $section));
        }
    }

    public function getAppId()
    {
        return $this->getFromConfig('appDetails', 'appId', true);
    }

    public function getAppSecret()
    {
        return $this->getFromConfig('appDetails', 'appSecret', true);
    }

    public function getPageAccessToken()
    {
        return $this->getFromConfig('authorization', 'pageAccessToken');
    }

    public function getPageId()
    {
        return $this->getFromConfig('pageDetails', 'pageId', true);
    }

    public function getAppSession()
    {
        return FacebookSession::newAppSession();
    }

    public function loginValid()
    {
        $pageAccessToken = $this->getPageAccessToken();
        if(is_null($pageAccessToken) === true) {
            return false;
        }
        
        $appSession = $this->getAppSession();
        $request = new FacebookRequest($appSession, 'GET', '/debug_token', array(
            'input_token' => $pageAccessToken
        ));
        $data = $request->execute()
            ->getGraphObject()
            ->asArray();
        $isValid = $data['is_valid'];
        
        return $isValid;
    }

    public function post()
    {
        $appSession = $this->getAppSession();
        $postData = array(
            'access_token' => $this->getPageAccessToken(),
            'name' => 'Test',
            'message' => 'Message'
        );
        // 'link' => 'http://www.buchtips.net/',
        // 'caption' => 'Test'
        
        $request = new FacebookRequest($appSession, 'POST', '/' . $this->getPageId() . '/feed', $postData);
        $page_post = $request->execute()
            ->getGraphObject()
            ->asArray();
        echo '<pre>' . var_export($page_post, true) . '</pre>';
    }

    public function performLogin()
    {
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https://' : 'http://';
        $server = $_SERVER['HTTP_HOST'];
        $relativePath = $_SERVER['PHP_SELF'];
        $myUrl = $protocol . $server . $relativePath;
        
        session_start();
        $helper = new FacebookRedirectLoginHelper($myUrl);
        try {
            $session = $helper->getSessionFromRedirect();
        } catch (\Exception $ex) {}
        
        if (isset($session)) {
            $userAccessToken = $session->getToken();
            $pageAccessToken = $this->getPageAccessTokenFromUserAccessToken($userAccessToken);
            $this->config->set('authorization', 'pageAccessToken', $pageAccessToken);
            $this->writeConfig();
            return true;
        } else {
            header('Location: ' . $helper->getLoginUrl(array(
                'public_profile',
                'manage_pages',
                'publish_pages'
            )));
            return false;
        }
    }

    public function getPageAccessTokenFromUserAccessToken($userAccessToken)
    {
        $userSession = new FacebookSession($userAccessToken);
        $request = new FacebookRequest($userSession, 'GET', '/' . $this->getPageId(), array(
            'fields' => 'access_token'
        ));
        $access_token = $request->execute()
            ->getGraphObject()
            ->asArray();
        return $access_token['access_token'];
    }
}