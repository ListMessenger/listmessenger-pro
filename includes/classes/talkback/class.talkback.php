<?php
/*
    ListMessenger TalkBack Client 2.2.0
    Copyright 2019 Silentweb [http://www.silentweb.ca]. All Rights Reserved.

    Re-Developed By:	Matt Simpson <msimpson@listmessenger.com>

    For the most recent version, visit the ListMessenger website:
    [http://www.listmessenger.com]

    License Information is found in licence.html
    $Id: class.talkback.php 481 2009-11-29 16:21:11Z matt.simpson $
*/
class TalkBack
{
    private $url;
    private $version = '2.2.3';
    private $dataArray = [];
    private $authInfo = false;
    private $responseBody = '';
    private $responseHeaders = '';
    private $errors = '';

    public function __construct($type = '', $dataArray = '', $authInfo = false)
    {
        switch ($type) {
            case 'registration' :
                $this->setURL('https://talkback.listmessenger.com/collector.php');
                break;
            case 'trip' :
                $this->setURL('https://talkback.listmessenger.com/tripwire.php');
                break;
            case 'version' :
            default:
                $this->setURL('https://talkback.listmessenger.com/version.php');
                break;
        }

        $this->setDataArray($dataArray);

        $this->authInfo = $authInfo;
    }

    public function setUrl($url = '')
    {
        $this->url = $url;

        return $this->url;
    }

    public function setDataArray($dataArray = [])
    {
        if (is_array($dataArray)) {
            $this->dataArray = $dataArray;
        }

        return $this->dataArray;
    }

    public function setAuthInfo($user = '', $pass = false)
    {
        if (is_array($user)) {
            $this->authInfo = $user;
        } else {
            $this->authInfo = [$user, $pass];
        }

        return $this->authInfo;
    }

    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function post()
    {
        $this->responseHeaders = '';
        $this->responseBody = '';

        if ($this->authInfo) {
            $auth = base64_encode("{$this->authInfo[0]}:{$this->authInfo[1]}");
        }

        $options = [
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => [
                'User-Agent: ListMessenger TalkBack Client '.$this->version,
            ],
            CURLOPT_URL => $this->url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_POSTFIELDS => http_build_query($this->dataArray),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }
}
