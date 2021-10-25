<?php

namespace App\Security\Providers\Vk;

use JetBrains\PhpStorm\Pure;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Vk extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected string $version = '5.131';
    protected ?string $lang = null;
    protected array $fields = ['id'];

    public function getBaseAuthorizationUrl(): string
    {
        return 'https://oauth.vk.com/authorize';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://oauth.vk.com/access_token';
    }

    #[Pure] public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        $query = $this->buildQueryString([
            'fields' => $this->fields,
            'access_token' => $token->getToken(),
            'v' => $this->version,
            'lang' => $this->lang
        ]);

        return "https://api.vk.com/method/users.get?$query";
    }

    protected function getDefaultScopes(): array
    {
        return ['email'];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (empty($data['error'])) {
            return;
        }

        throw new IdentityProviderException($data['error'], 0, $data);
    }

    #[Pure] protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        ['response' => $response] = $response;

        $additional = $token->getValues();

        if (!empty($additional['email'])) {
            $response[0]['email'] = $additional['email'];
        }

        return new VkUser($response[0]);
    }
}