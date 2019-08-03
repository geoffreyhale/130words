<?php

namespace OneThirtyWordsBundle\Controller;

use OneThirtyWordsBundle\Entity\Category;
use OneThirtyWordsBundle\Entity\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class CategoryController extends Controller
{
    /**
     * @Route("/category/{id}", requirements={"id" = "\d+"}, name="getCategory")
     */
    public function getCategoryAction(Category $category)
    {
        if ($this->getUser() !== $category->getUser()) {
            throw new \Exception("ACCESS DENIED: This is not your category.");
        }

        $posts = $this->getDoctrine()->getManager()->getRepository(Post::class)
            ->findBy(
                array(
                    'category' => $category,
                ),
                array(
                    'date' => 'DESC',
                )
            );

        return $this->render('OneThirtyWordsBundle:Category:category.html.twig', array(
            'category' => $category,
            'posts' => $posts,
        ));
    }

    /**
     * @Route("/category/new", name="newCategory")
     */
    public function newCategoryAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $category = new Category();

        $form = $this->createFormBuilder($category)
            ->add('name', TextType::class)
            ->add('submit', SubmitType::class, array('label' => 'Create'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();
            $category->setUser($this->getUser());
            $em->persist($category);
            $em->flush();

            return $this->forward('OneThirtyWordsBundle:Category:getCategories');
        }

        return $this->render('OneThirtyWordsBundle:Category:new.html.twig', array(
            'form' => $form->createView(),
            'category' => $category,
        ));
    }

    /**
     * @Route("/category/{id}/edit", name="editCategory")
     */
    public function editCategoryAction(Request $request, Category $category)
    {
        if ($this->getUser() !== $category->getUser()) {
            throw new \Exception("ACCESS DENIED: This is not your category.");
        }

        $em = $this->getDoctrine()->getManager();

        $form = $this->createFormBuilder($category)
            ->add('name', TextType::class)
            ->add('hide', CheckboxType::class, array('required' => false))
            ->add('submit', SubmitType::class, array('label' => 'Update'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();
            $em->persist($category);
            $em->flush();

            return $this->redirectToRoute('getCategory', array(
                'id'  => $category->getId()
            ));
        }

        return $this->render('OneThirtyWordsBundle:Category:edit.html.twig', array(
            'form' => $form->createView(),
            'category' => $category,
        ));
    }

    /**
     * @Route("/categories", name="getCategories")
     */
    public function getCategoriesAction()
    {
        $categories = $this->getUser()->getCategories()->getValues();

        uasort($categories, function ($c1, $c2) {
            if ($c2->getName() == $c1->getName()) return 0;
            return $c2->getName() > $c1->getName() ? -1 : 1;
        });

        $categoriesViewArray = [];

        foreach($categories as $category) {
            $categoriesViewArray[$category->getId()] = array(
                'hide' => $category->isHide(),
                'id' => $category->getId(),
                'name' => $category->getName(),
                'post_count' => $category->getPosts()->count(),
            );
        }

        return $this->render('OneThirtyWordsBundle:Category:categories.html.twig', array(
            'categories' => $categoriesViewArray,
        ));
    }

    /**
     * @Route("/category/{id}/hide", name="hideCategory")
     */
    public function hideCategoryAction(Category $category)
    {
        if ($this->getUser() !== $category->getUser()) {
            throw new \Exception("ACCESS DENIED: This is not your category.");
        }

        $category->setHide(true);

        $em = $this->getDoctrine()->getManager();
        $em->flush($category);

        // TODO prefer ajax w/o navigation
        // $response = new Response();
        // $response->setStatusCode(Response::HTTP_OK);
        // return $response->send();

        return $this->redirect(
            $this->generateUrl(
                'getCategory',
                array('id'  => $category->getId())
            )
        );
    }

    /**
     * @Route("/category/{id}/unhide", name="unhideCategory")
     */
    public function unhideCategoryAction(Category $category)
    {
        if ($this->getUser() !== $category->getUser()) {
            throw new \Exception("ACCESS DENIED: This is not your category.");
        }

        $category->setHide(false);

        $em = $this->getDoctrine()->getManager();
        $em->flush($category);

        // TODO prefer ajax w/o navigation
        // $response = new Response();
        // $response->setStatusCode(Response::HTTP_OK);
        // return $response->send();

        return $this->redirect(
            $this->generateUrl(
                'getCategory',
                array('id'  => $category->getId())
            )
        );
    }
}
