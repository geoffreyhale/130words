<?php

namespace OneThirtyWordsBundle\Controller;

use OneThirtyWordsBundle\Entity\Category;
use OneThirtyWordsBundle\Entity\Post;
use OneThirtyWordsBundle\Repository\CategoryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class PostController extends Controller
{
    /**
     * @Route("/post/{post}", name="getPost", requirements={"post": "\d+"})
     */
    public function getPostAction(Post $post)
    {
        if ($this->getUser() !== $post->getUser()) {
            throw new \Exception("ACCESS DENIED: This is not your post.");
        }

        return $this->render('OneThirtyWordsBundle:Post:index.html.twig', array(
            'post' => $post,
        ));
    }

    /**
     * @Route("/posts", name="getPosts")
     */
    public function getPostsAction()
    {
        $posts = $this->getDoctrine()->getManager()->getRepository(Post::class)
            ->findBy(
                array(
                    'user' => $this->getUser(),
                ),
                array(
                    'date' => 'ASC',
                )
            );

        return $this->render('OneThirtyWordsBundle:Post:posts.html.twig', array(
            'posts' => array_reverse($posts),
        ));
    }

    /**
     * @Route("/post/new/{categoryId}", name="newPost", requirements={"categoryId": "\d+"})
     */
    public function newPostAction(Request $request, $categoryId=null)
    {
        $em = $this->getDoctrine()->getManager();

        $post = (new Post())
            ->setDate(new \DateTime)
            ->setUser($this->getUser())
        ;

        if ($categoryId) {
            $category = $this->getDoctrine()->getManager()->getRepository(Category::class)->findOneBy(array('id' => $categoryId));

            if ($this->getUser() !== $category->getUser()) {
                throw new \Exception("ACCESS DENIED: This is not your category.");
            }

            $post->setCategory($category);
        }

        $em->persist($post);
        $em->flush();

        return $this->redirect(
            $this->generateUrl(
                'editPost',
                array('id'  => $post->getId()),
                302
            )
        );
    }

    /**
     * @Route("/post/{id}/edit", name="editPost")
     */
    public function editPostAction(Request $request, Post $post)
    {
        if ($post->getUser() !== $this->getUser()) {
            throw new \Exception("ACCESS DENIED: This is not your post.");
        }

        if ($post->getDate()->format('Ymd') != (new \DateTime)->format('Ymd')) {
            throw new \Exception("CANNOT EDIT: You can only edit posts written today.");
        }

        $em = $this->getDoctrine()->getManager();

        $form = $this->createFormBuilder($post)
            ->add('body', TextareaType::class)
            ->add('category', EntityType::class, array(
                'class' => 'OneThirtyWordsBundle:Category',
                'query_builder' => function (CategoryRepository $cr) {
                    return $cr->createQueryBuilder('c')
                        ->where('c.user = :user')
//                        ->andWhere('c.hide = :hide')
                        ->orderBy('c.name')
                        ->setParameter('user', $this->getUser())
//                        ->setParameter('hide', 0)
                        ;
                },
                'choice_label' => 'name'
            ))
            ->add('submit', SubmitType::class, array('label' => 'Save'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setWordcount(str_word_count($post->getBody()));
            $em->persist($post);
            $em->flush();
        }

        return $this->render('OneThirtyWordsBundle:Post:edit.html.twig', array(
            'form' => $form->createView(),
            'post' => $post,
            'postSavedWordCount' => str_word_count($post->getBody()),
        ));
    }
}
