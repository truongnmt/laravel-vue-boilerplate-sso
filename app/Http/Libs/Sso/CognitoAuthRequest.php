<?php

namespace App\Libs\Sso;

use Psr\Http\Message\ResponseInterface;

//TODO: delete

/**
 * Class CognitoAuthRequest
 * @package App\Libs\Sso
 */
class CognitoAuthRequest
{
    /** @var array Cognito設定 */
    private $config;

    /** @var string Cognito Domain */
    private $domain;

    /**
     * CognitoAuthRequest constructor.
     */
    public function __construct()
    {
        $this->config = config('sso');
        $this->domain = 'https://github.com/login/oauth/authorize';
    }

    /**
     * @param $parameters
     * @return ResponseInterface
     */
    public function invokeTokenRequest(array $parameters): ResponseInterface
    {
        $client = new \GuzzleHttp\Client();
        return $client->request('POST', 'https://github.com/login/oauth/access_token', [
            'form_params' => [
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->config['app_client_id'],
                'client_secret' => $this->config['app_client_secret'],
                'redirect_uri'  => 'http://localhost:8000/sso/callback',
                'code'          => $parameters['loginResult']['code'],
            ]
        ]);
    }

    /**
     * @param array $parameters
     * @return ResponseInterface
     */
    public function invokeUserInfoRequest(array $parameters): ResponseInterface
    {
        $client = new \GuzzleHttp\Client();
        $headers = [
            'Authorization' => 'Bearer ' . $parameters['access_token'],
            'Accept'        => 'application/json',
        ];
        return $client->request('GET', $this->domain . 'oauth2/userInfo', [
            'headers' => $headers
        ]);
    }

    /**
     * @param array $parameters
     * @return string
     */
    public function buildLogoutRequest(array $parameters): string
    {
        return $this->domain . 'logout?' .
            \http_build_query([
                //'logout_uri' => $parameters['appUrl'] .'/sso/cognito/logout',  //pathは適宜調整
                'redirect_uri'  => $parameters['appUrl'] .'/sso/cognito/callback',     //pathは適宜調整
                'scope'         => 'aws.cognito.signin.user.admin+email+openid+phone+profile',
                'client_id'    => $this->config['app_client_id'],
                'state'         => $parameters['state'],
                'response_type' => 'code',
            ]);
    }

    /**
     * @param array $parameters
     * @return Response
     */
    public function invokeLogoutRequest(array $parameters): Response
    {
        return Http::get($this->domain . 'logout', [
            'client_id'    => $this->config['app_client_id'],
            //'logout_uri' => $parameters['appUrl'] .'/sso/cognito/logout',  //pathは適宜調整
            'redirect_uri'  => $parameters['appUrl'] .'/sso/cognito/callback',     //pathは適宜調整
            'response_type' => 'code',
            'state'         => $parameters['state'],
            'scope'         => 'aws.cognito.signin.user.admin+email+openid+phone+profile',
        ]);
    }
}
