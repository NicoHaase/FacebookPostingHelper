<?php
namespace FacebookPostingHelper;

use Piwik\Ini\IniReader;
use Piwik\Ini\IniWriter;
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
     * @var string[]
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
        $reader = new IniReader();
        $this->config = $reader->readFile($this->configFilename);
    }

    public function writeConfig()
    {
        $writer = new IniWriter();
        $writer->writeToFile($this->configFilename, $this->config);
    }

    private function getFromConfig($section, $key, $showError = false)
    {
        if(isset($this->config[$section]) && isset($this->config[$section][$key])) {
            return $this->config[$section][$key];
        }
        if($showError) {
            throw new Exception(sprintf('Missing key %s in section %s', $key, $section));
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

    public function post($postMessage, $link = null, $caption = null)
    {
        $appSession = $this->getAppSession();
        $postData = array(
            'access_token' => $this->getPageAccessToken(),
            'message' => $postMessage
        );

        if(is_null($link) === false) {
            $postData['link'] = $link;
        }

        if(is_null($caption) === false) {
            $postData['caption'] = $caption;
        }

        $request = new FacebookRequest($appSession, 'POST', '/' . $this->getPageId() . '/feed', $postData);
        $page_post = $request->execute()
            ->getGraphObject()
            ->asArray();
        return $page_post['id'];
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
            if(!isset($this->config['authorization']) || !is_array($this->config['authorization'])) {
                $this->config['authorization'] = array();
            }
            $this->config['authorization']['pageAccessToken'] = $pageAccessToken;
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