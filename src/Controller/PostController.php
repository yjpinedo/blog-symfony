<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use PhpParser\Node\Expr\New_;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class PostController extends AbstractController
{
    /**
     * @Route("/post", name="post-index")
     */
    public function index(ManagerRegistry $doctrine, PaginatorInterface $paginator, Request $request): Response
    {
        $entityManager = $doctrine->getManager();
        $posts = $entityManager->getRepository(Post::class)->findAllPosts();

        $postPaginate = $paginator->paginate(
            $posts, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            3 /*limit per page*/
        );

        return $this->render('post/index.html.twig', [
            'posts' => $postPaginate,
        ]);
    }

    /**
     * @Route("/post/create", name="create-post")
     */
    public function create(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $entityManager = $doctrine->getManager();
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user) {

                $post->setUser($user);
                $image = $form->get('image')->getData();

                if ($image) {

                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                    try {
                        $image->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        $this->addFlash('danger', $e->getMessage());
                        return $this->redirectToRoute('create-post');
                    }

                    $post->setImage($newFilename);
                }

                $entityManager->persist($post);
                $entityManager->flush();

                $this->addFlash('success', 'Registered post successfully');
            } else {
                $this->addFlash('danger', 'Not registered post');
            }
            return $this->redirectToRoute('create-post');
        }

        return $this->render('post/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/post/show/{id}", name="show-post")
     */
    public function show($id, ManagerRegistry $doctrine)
    {
        return $this->render('post/show.html.twig', [
            'post' => $doctrine->getRepository(Post::class)->find($id),
        ]);
    }

    /**
     * @Route("/post/edit/{id}", name="edit-post")
     */
    public function edit($id, ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $post = $doctrine->getRepository(Post::class)->find($id);
        $entityManager = $doctrine->getManager();

        $post->setImage(
            new File($this->getParameter('images_directory') . '/' . $post->getImage())
        );

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user) {

                $post->setUser($user);
                $image = $form->get('image')->getData();

                if ($image) {

                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                    try {
                        $image->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        $this->addFlash('danger', $e->getMessage());
                        return $this->redirectToRoute('create-post');
                    }

                    $post->setImage($newFilename);
                }

                $entityManager->persist($post);
                $entityManager->flush();

                $this->addFlash('success', 'Registered post successfully');
            } else {
                $this->addFlash('danger', 'Not registered post');
            }
            return $this->redirectToRoute('show-post', ['post' => $post]);
        }

        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/post/my-post", name="my-post")
     */
    public function myPost(ManagerRegistry $doctrine)
    {
        return $this->render('post/my-post.html.twig', [
            'posts' => $doctrine->getRepository(Post::class)->findBy(['user' => $this->getUser()]),
        ]);
    }
}
