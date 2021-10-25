<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class PostController extends AbstractController
{
    private PostRepository $postRepository;
    private CommentRepository $commentRepository;
    private ValidatorInterface $validator;

    public function __construct(
        PostRepository     $postRepository,
        CommentRepository  $commentRepository,
        ValidatorInterface $validator,
    )
    {
        $this->postRepository = $postRepository;
        $this->commentRepository = $commentRepository;
        $this->validator = $validator;
    }

    /**
     * @throws NoResultException
     */
    #[Route('/api/post/{slug}', name: 'blog_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        if ($post->getIsPublished() || $this->isGranted('ROLE_EDITOR')) {
            return $this->json($post);
        }

        throw new NoResultException();
    }


    #[Route('/api/post/list', name: 'post_list', methods: ['GET'], priority: 2)]
    public function list(): Response
    {
        if ($this->isGranted('ROLE_EDITOR')) {
            $list = $this->postRepository->findAllPostsShort();
        } else {
            $list = $this->postRepository->findPublishedPostsShort();
        }

        return $this->json($list);
    }

    /**
     * @throws Exception
     */
    #[Route('/api/post/add', name: 'post_add', methods: ['PUT'])]
    #[IsGranted('ROLE_EDITOR')]
    public function create(Request $request): Response
    {
        $post = (new Post)
            ->setTitle($request->get('title'))
            ->setDescription($request->get('description'))
            ->setSlug($request->get('slug'))
            ->setContent($request->get('content'));

        $violations = $this->validator->validate($post);

        if (count($violations) === 0) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            return $this->json([
                'success' => true,
                'slug' => $post->getSlug()
            ]);
        }

        throw new Exception('Post create error');
    }

    /**
     * @throws Exception
     */
    #[Route('/api/post/edit/{post_id}', name: 'post_edit', methods: ['POST'])]
    #[Entity('post', options: ['id' => 'post_id'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Post $post, Request $request): Response
    {
        $isPublished = filter_var(
            $request->get('isPublished'),
            FILTER_VALIDATE_BOOLEAN
        );

        $post->setTitle($request->get('title'))
            ->setSlug($request->get('slug'))
            ->setContent($request->get('content'))
            ->setIsPublished($isPublished);

        if ($isPublished) {
            $post->setPublishedAt(new DateTimeImmutable);
        }

        $violations = $this->validator->validate($post);
        if (count($violations) === 0) {
            $entityManager = $this->getDoctrine()
                ->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->json(['success' => true, 'post' => $post]);
        }

        throw new Exception('Post edit error');
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('/api/post/delete/{post_id}', name: 'post_delete', methods: ['DELETE'])]
    #[Entity('post', options: ['id' => 'post_id'])]
    #[IsGranted('ROLE_EDITOR')]
    public function delete(Post $post): Response
    {
        $this->postRepository
            ->removeSinglePost($post);

        return $this->json(['success' => true]);
    }

    /**
     * @throws Exception
     */
    #[Route('/api/post/{post_id}/comment', name: 'post_add_comment', methods: ['PUT'])]
    #[Entity('post', options: ['id' => 'post_id'])]
    #[IsGranted('ROLE_USER')]
    public function addComment(Post $post, Request $request): Response
    {
        $comment = (new Comment)
            ->setContent($request->get('content'))
            ->setUser($this->getUser())
            ->setPost($post);

        if ($parentId = $request->get('parentId')) {
            $parent = $this->commentRepository->find($parentId);
            $comment->setParent($parent);
        }

        $violations = $this->validator->validate($comment);

        if (count($violations) === 0) {
            $entityManager = $this->getDoctrine()
                ->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->json(['success' => true]);
        }

        throw new Exception('Comment add error');
    }
}
