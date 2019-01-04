<?php

namespace Vendor\ApiCommunicator\Client;

use Vendor\ApiCommunicator\Oauth\OAuthConsumer;
use Vendor\ApiCommunicator\Oauth\OAuthToken;
use Vendor\ApiCommunicator\Oauth\OAuthRequest;
use Vendor\ApiCommunicator\Oauth\OAuthSignatureMethod_HMAC_SHA1;

use Vendor\HttpClient\HttpClient;

class Client
{
    private $consumerKey;
    private $consumerSecret;
    private $consumerToken;
    private $consumerTokenSecret;
    private $baseUrl;
    private $client;

    public function __construct(
        $consumerKey,
        $consumerSecret,
        $consumerToken,
        $consumerTokenSecret
    ) {
        $this->baseUrl = getenv('API_BASE_URL');
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->consumerToken = $consumerToken;
        $this->consumerTokenSecret = $consumerTokenSecret;

        $this->client = (new HttpClient())
            ->setUrlBase($this->baseUrl)
            ->setUrlResource('resultado/cod/2')
            ->setEncoding('json');
            
        $this->setOauthRequestHeaders($this->client);
        
        $this->client->send();
        
        die(print_r(['<pre>',$this->client->formatHeaders(),$this->client->getResponse()]));
    }
    
    protected function setOauthRequestHeaders(HttpClient $client)
    {
        require __DIR__ . '/../OAuth/OAuth.php';
        
        $consumer = new \Vendor\ApiCommunicator\Oauth\OAuthConsumer($this->consumerKey, $this->consumerSecret);
        $token = new OAuthToken($this->consumerToken, $this->consumerTokenSecret);

        $request = OAuthRequest::from_consumer_and_token($consumer, $token, $client->getMethod(), $client->getUrl());
        
        $params =[];
        foreach ($params as $name => $value) {
            $request->set_parameter($name, $value);
        }

        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, $token);
        $this->client->setHeader('Authorization', $request->to_header());
        
        return $request->to_header();
    }
}