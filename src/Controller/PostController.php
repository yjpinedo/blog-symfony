<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PostController extends AbstractController
{
    /**
     * @Route("/post", name="post")
     */
    public function index(ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $posts = $entityManager->getRepository(Post::class)->findAll();
        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }


    /**
     * @Route("/post/create", name="create-post")
     */
    public function create(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
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
}
