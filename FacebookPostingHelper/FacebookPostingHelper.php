<?php

namespace FacebookPostingHelper;

use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Matomo\Ini\IniReader;
use Matomo\Ini\IniWriter;

class FacebookPostingHelper
{

    /**
     * @var string
     */
    private $configFilename;

    /**
     * @var string[][]
     */
    private $config;

    /**
     * @param string $fileName
     */
    public function __construct($fileName)
    {
        $this->configFilename = $fileName;
        $this->readConfig();
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

    /**
     * @param string $section
     * @param string $key
     * @param bool $showError
     * @return string
     * @throws \Exception
     */
    private function getFromConfig($section, $key, $showError = false)
    {
        if (isset($this->config[$section]) && isset($this->config[$section][$key])) {
            return $this->config[$section][$key];
        }
        if ($showError) {
            throw new \Exception(sprintf('Missing key %s in section %s', $key, $section));
        }
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->getFromConfig('appDetails', 'appId', true);
    }

    /**
     * @return string
     */
    public function getAppSecret()
    {
        return $this->getFromConfig('appDetails', 'appSecret', true);
    }

    /**
     * @return string
     */
    public function getPageAccessToken()
    {
        return $this->getFromConfig('authorization', 'pageAccessToken');
    }

    /**
     * @return string
     */
    public function getPageId()
    {
        return $this->getFromConfig('pageDetails', 'pageId', true);
    }

    /**
     * @return Facebook
     */
    public function getAppSession()
    {
        return new Facebook([
            'app_id' => $this->getAppId(),
            'app_secret' => $this->getAppSecret(),
            'default_graph_version' => 'v7.0',
        ]);
    }

    /**
     * @return bool
     */
    public function loginValid()
    {
        $pageAccessToken = $this->getPageAccessToken();
        if (is_null($pageAccessToken) === true) {
            return false;
        }

        $appSession = $this->getAppSession();
        $appSession->setDefaultAccessToken($pageAccessToken);

        try {
            $request = $appSession->get('/debug_token');
        } catch (FacebookSDKException $exception) {
            return false;
        }

        return $request->getGraphNode()->getField('is_valid');
    }

    /**
     * @param string $postMessage
     * @param string $link
     * @param string $caption
     * @param string $linkName
     * @param string $imageUrl
     * @return string
     */
    public function post($postMessage, $link = null, $caption = null, $linkName = null, $imageUrl = null)
    {
        $appSession = $this->getAppSession();
        $appSession->setDefaultAccessToken($this->getPageAccessToken());
        $postData = array('message' => $postMessage);

        if (is_null($link) === false) {
            $postData['link'] = $link;
        }

        if (is_null($linkName) === false) {
            $postData['name'] = $linkName;
        }

        if (is_null($caption) === false) {
            $postData['caption'] = $caption;
        }

        if (is_null($imageUrl) === false) {
            $postData['picture'] = $imageUrl;
        }

        $postUrl = '/' . $this->getPageId() . '/feed';

        $response = $appSession->post($postUrl, $postData);
        return $response->getGraphNode()->getField('id');
    }

    private function isSecureRequest()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    }

    public function performLogin()
    {
        $protocol = $this->isSecureRequest() ? 'https://' : 'http://';
        $server = $_SERVER['HTTP_HOST'];
        $relativePath = $_SERVER['PHP_SELF'];
        $myUrl = $protocol . $server . $relativePath;

        session_start();
        $facebookSession = $this->getAppSession();
        $helper = $facebookSession->getRedirectLoginHelper();

        $userAccessToken = $helper->getAccessToken();

        if ($userAccessToken) {
            $oauth2Client = $facebookSession->getOAuth2Client();
            $pageAccessToken = $oauth2Client->getLongLivedAccessToken($userAccessToken);
            if (!isset($this->config['authorization']) || !is_array($this->config['authorization'])) {
                $this->config['authorization'] = array();
            }
            $this->config['authorization']['pageAccessToken'] = $pageAccessToken->getValue();
            $this->writeConfig();
            return true;
        } else {
            header('Location: ' . $helper->getLoginUrl($myUrl, array(
                    'public_profile',
                    'manage_pages',
                    'publish_pages'
                )));
            return false;
        }
    }
}