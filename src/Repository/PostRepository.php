<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry    $registry
    )
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return Post[]
     */
    public function findPublishedPostsShort(): array
    {
        return $this->createQueryBuilder('p')
            ->select(['p.id', 'p.slug', 'p.title', 'p.is_published'])
            ->andWhere('p.is_page = FALSE')
            ->andWhere('p.is_published = TRUE')
            ->getQuery()
            ->getResult();
    }

    public function findAllPostsShort(): array
    {
        return $this->createQueryBuilder('p')
            ->select(['p.id', 'p.slug', 'p.title', 'p.is_published'])
            ->andWhere('p.is_page = FALSE')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function removeSinglePost(Post $post): void
    {
        $this->_em->remove($post);
        $this->_em->flush();
    }
}
