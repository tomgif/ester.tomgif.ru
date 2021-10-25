<?php

namespace App\Security\Authenticator;

use App\Repository\SocialUserRepository;
use App\Repository\UserRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SocialAuthenticator extends OAuth2Authenticator
{
    protected ContainerBagInterface $params;
    protected ClientRegistry $clientRegistry;
    protected SocialUserRepository $socialUserRepository;
    protected UserRepository $userRepository;

    public function __construct(
        ContainerBagInterface $params,
        ClientRegistry        $clientRegistry,
        SocialUserRepository  $socialUserRepository,
        UserRepository        $userRepository
    )
    {
        $this->params = $params;
        $this->clientRegistry = $clientRegistry;
        $this->socialUserRepository = $socialUserRepository;
        $this->userRepository = $userRepository;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'login_social'
            && in_array($request->attributes->get('social'), $this->params->get('oauth_socials'));
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $social = $request->attributes->get('social');
        $client = $this->clientRegistry->getClient($social);

        $accessToken = $this->fetchAccessToken($client, [
            'redirect_uri' => $this->params->get('oauth_redirect_uri')
        ]);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $social) {
                $socialProviderUser = $client->fetchUserFromToken($accessToken)->toArray();
                $externalId = $socialProviderUser['id'];

                $entity = $this->socialUserRepository->findOneBy(compact('externalId'));

                if ($entity === null) {
                    $user = $this->userRepository
                        ->findOrCreate($socialProviderUser['email']);
                    $socialUser = $this
                        ->socialUserRepository->create($user, $social, $externalId);

                    return $socialUser->getUser();
                } else {
                    return $entity->getUser();
                }
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response(
            strtr(
                $exception->getMessageKey(),
                $exception->getMessageData()
            ),
            Response::HTTP_FORBIDDEN
        );
    }
}