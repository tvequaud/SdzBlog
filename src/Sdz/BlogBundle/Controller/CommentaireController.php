<?php

namespace Sdz\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Sdz\BlogBundle\Entity\Article;
use Sdz\BlogBundle\Entity\Commentaire;
use Sdz\BlogBundle\Form\CommentaireType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;


class CommentaireController extends Controller
{
  public function ajouterAction(Article $article, Request $request)
  {
    $commentaire = new Commentaire;
    $commentaire->setArticle($article);
    $commentaire->setIp($request->server->get('REMOTE_ADDR'));

    $form = $this->getForm($article, $commentaire);

    // Avec la route que l'on a, nous sommes forcément en POST ici, pas besoin de le retester
    $form->handleRequest($request);
    if ($form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $em->persist($commentaire);
      $em->flush();

      $this->get('session')->getFlashBag()->add('info', 'Commentaire bien enregistré !');

      // On redirige vers la page de l'article, avec une ancre vers le nouveau commentaire
      return $this->redirect(sprintf('%s#comment%d', $this->generateUrl('sdzblog_blog_voir', array('slug' => $article->getSlug())), $commentaire->getId()));
    }

    $this->get('session')->getFlashBag()->add('error', 'Votre formulaire contient des erreurs');

    // On réaffiche le formulaire sans redirection (sinon on perd les informations du formulaire)
    return $this->forward('SdzBlogBundle:Blog:voir', array(
      'article' => $article,
      'form'    => $form,
    ));
  }

  /**
   * @Security("has_role('ROLE_ADMIN')")
   */
  public function supprimerAction(Commentaire $commentaire, Request $request)
  {
    // On crée un formulaire vide, qui ne contiendra que le champ CSRF
    // Cela permet de protéger la suppression d'article contre cette faille
    $form = $this->createFormBuilder()->getForm();

    if ($request->getMethod() == 'POST') {
      $form->handleRequest($request);

      if ($form->isValid()) { // Ici, isValid ne vérifie donc que le CSRF
        // On supprime l'article
        $em = $this->getDoctrine()->getManager();
        $em->remove($commentaire);
        $em->flush();

        // On définit un message flash
        $this->get('session')->getFlashBag()->add('info', 'Commentaire bien supprimé');

        // Puis on redirige vers l'accueil
        return $this->redirectToRoute('sdzblog_blog_voir', array('slug' => $commentaire->getArticle()->getSlug()));
      }
    }

    // Si la requête est en GET, on affiche une page de confirmation avant de supprimer
    return $this->render('SdzBlogBundle:Commentaire:supprimer.html.twig', array(
      'commentaire' => $commentaire,
      'form'        => $form->createView()
    ));
  }

  /**
   * Retourne le formulaire d'ajout d'un commentaire
   * @param Article $article
   * @return Form
   */
  protected function getForm(Article $article, Commentaire $commentaire = null)
  {
    if (null === $commentaire) {
      $commentaire = new Commentaire();
    }

    // Si l'utilisateur courant est identifié, on l'ajoute au commentaire
    if (null !== $this->getUser()) {
        $commentaire->setUser($this->getUser());
    }

    return $this->createForm(CommentaireType::class, $commentaire);
  }
}
