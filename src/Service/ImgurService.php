<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\File;

class ImgurService
{
    private $httpClient;
    private $clientId;

    public function __construct(HttpClientInterface $httpClient, string $clientId)
    {
        $this->httpClient = $httpClient;
        $this->clientId = $clientId;
    }

    public function uploadImage(string $imagePath): array
    {
        $response = $this->httpClient->request('POST', 'https://api.imgur.com/3/image', [
            'headers' => [
                'Authorization' => 'Client-ID ' . $this->clientId
            ],
            'multipart' => [
                [
                    'name' => 'image',
                    'contents' => fopen($imagePath, 'r'),
                ]
            ]
        ]);

        return $response->toArray();
    }
}
