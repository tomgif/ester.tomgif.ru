<?php

namespace App\Service;

use DateTime;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RefreshTokenService
{
    private JWTTokenManagerInterface $tokenManager;
    private RefreshTokenManagerInterface $refreshTokenManager;
    private ValidatorInterface $validator;
    private string $refreshTokenParameterName;
    private int $ttl;

    public function __construct(
        JWTTokenManagerInterface     $tokenManager,
        RefreshTokenManagerInterface $refreshTokenManager,
        ValidatorInterface           $validator,
        string                       $refreshTokenParameterName,
        int                          $ttl
    )
    {
        $this->tokenManager = $tokenManager;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->validator = $validator;
        $this->refreshTokenParameterName = $refreshTokenParameterName;
        $this->ttl = $ttl;
    }

    #[ArrayShape([
        'token' => 'string',
        'refresh_token' => 'string'
    ])]
    public function getTokens(UserInterface $user): array
    {
        $token = $this->tokenManager->create($user);

        $refreshToken = $this->refreshTokenManager
            ->create()
            ->setUsername(
                $user->getUserIdentifier()
            )->setRefreshToken()
            ->setValid(
                (new DateTime)
                    ->modify('+' . $this->ttl . ' seconds')
            );

        $valid = false;

        while ($valid === false) {
            $valid = true;

            $errors = $this->validator
                ->validate($refreshToken);

            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    if ('refreshToken' === $error->getPropertyPath()) {
                        $valid = false;
                        $refreshToken->setRefreshToken();
                    }
                }
            }
        }

        $this->refreshTokenManager
            ->save($refreshToken);

        return [
            'token' => $token,
            $this->refreshTokenParameterName => $refreshToken->getRefreshToken()
        ];
    }
}