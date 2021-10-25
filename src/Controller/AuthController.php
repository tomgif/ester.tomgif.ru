<?php

namespace App\Controller;

use App\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    #[Route('/api/auth/register', name: 'auth_register', methods: ['PUT'])]
    public function register(Request $request, UserPasswordHasherInterface $encoder): Response
    {
        $email = $request->get('email');
        $password = $request->get('password');

        $user = new User($email);
        $password = $encoder->hashPassword($user, $password);
        $user->setPassword($password);

        $errors = $this->validator
            ->validate($user);

        if ($errors->count() > 0) {
            return $this->json($errors);
        }

        $entityManager = $this->getDoctrine()
            ->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'user' => $user->getEmail()
        ]);
    }

    #[Route('/api/auth/check', name: 'auth_check', methods: ['GET'])]
    public function isUnique(Request $request): Response
    {
        $email = $request->get('email');
        $user = new User($email);

        $errors = $this->validator->validate($user, [
            new UniqueEntity('email')
        ]);

        return $this->json(['isUnique' => $errors->count() === 0]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/api/auth/user', name: 'auth_user', methods: ['GET'])]
    public function user(): Response
    {
        $user = $this->getUser();

        return $this->json([
            'user' => [
                'email' => $user->getUserIdentifier(),
                'scope' => $user->getRoles()
            ],
        ]);
    }
}
