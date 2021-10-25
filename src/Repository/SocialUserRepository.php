<?php

namespace App\Repository;

use App\Entity\SocialUser;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SocialUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method SocialUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method SocialUser[]    findAll()
 * @method SocialUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SocialUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SocialUser::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function findOrCreate(?User $user, string $provider, string $externalId): void
    {
        $entity = $this->findOneBy(compact('externalId'));

        if ($entity === null) {
            $this->create($user, $provider, $externalId);
        } else {
            $entity->setUser($user);
            $this->_em->flush();
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function create(?User $user, string $provider, string $externalId): SocialUser
    {
        $socialUser = (new SocialUser($user))
            ->setProvider($provider)
            ->setExternalId($externalId)
            ->setOld($user);

        $this->_em->persist($socialUser);
        $this->_em->flush();

        return $socialUser;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function find2Remove(?User $user, string $provider)
    {
        $socialUser = $this->findOneBy(compact('user', 'provider'));

        if ($socialUser->getUser() === $socialUser->getOld()) {
            $this->_em->remove($socialUser);
        } else {
            $old = $socialUser->getOld();
            $socialUser->setUser($old);
        }

        $this->_em->flush();
    }
}
