<?php

namespace App\Controller;

use App\Repository\SocialUserRepository;
use App\Service\RefreshTokenService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SocialController extends AbstractController
{
    private SocialUserRepository $socialUserRepository;

    public function __construct(SocialUserRepository $socialUserRepository)
    {
        $this->socialUserRepository = $socialUserRepository;
    }

    #[Route('/api/auth/{social}/login', name: 'login_social')]
    #[IsGranted('ROLE_USER')]
    public function login(RefreshTokenService $refreshTokenService): Response
    {
        return $this->json(
            $refreshTokenService->getTokens(
                $this->getUser()
            )
        );
    }

    #[Route('/api/auth/{social}/start', name: 'connect_social_start')]
    public function start(string $social, ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient($social)
            ->redirect(['email']);
    }

    /**
     * @param string $social
     * @param ClientRegistry $clientRegistry
     * @return Response
     */
    #[Route('/api/auth/{social}/check', name: 'connect_social_check')]
    public function check(string $social, ClientRegistry $clientRegistry): Response
    {
        $user = $clientRegistry
            ->getClient($social)
            ->fetchUser();

        return $this->redirect(implode('?', [
                $this->getParameter('oauth_connect_uri'),
                http_build_query([
                    'provider' => $social,
                    'external' => $user->getId()
                ])
            ])
        );
    }

    #[Route('/api/auth/socials', name: 'get_socials')]
    #[IsGranted('ROLE_USER')]
    public function socials(): Response
    {
        $socials = $this->socialUserRepository
            ->findBy(['user' => $this->getUser()]);

        return $this->json(compact('socials'));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('/api/auth/connect', name: 'connect_social_user')]
    #[IsGranted('ROLE_USER')]
    public function connect(Request $request): Response
    {
        ['external' => $external, 'provider' => $provider] = $request->request->all();

        $this->socialUserRepository
            ->findOrCreate($this->getUser(), $provider, $external);

        return $this->json(['success' => true]);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('/api/auth/disconnect', name: 'disconnect_social_user')]
    #[IsGranted('ROLE_USER')]
    public function disconnect(Request $request): Response
    {
        ['provider' => $provider] = $request->request->all();

        $this->socialUserRepository
            ->find2Remove($this->getUser(), $provider);

        return $this->json(['success' => true]);
    }
}
