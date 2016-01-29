<?php

namespace Sdz\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;

use Sdz\BlogBundle\Entity\Article;
use Sdz\BlogBundle\Entity\Commentaire;

use Sdz\BlogBundle\Form\ArticleType;
use Sdz\BlogBundle\Form\ArticleEditType;

use Sdz\BlogBundle\Bigbrother\BigbrotherEvents;
use Sdz\BlogBundle\Bigbrother\MessagePostEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;


class BlogController extends Controller
{
  public function indexAction($page)
  {
    // On récupère le nombre d'article par page depuis un paramètre du conteneur
    // cf app/config/parameters.yml
    $nbParPage = $this->container->getParameter('sdzblog.nombre_par_page');

    // On récupère les articles de la page courante
    $articles = $this->getDoctrine()
                     ->getManager()
                     ->getRepository('SdzBlogBundle:Article')
                     ->getArticles($nbParPage, $page);

    // On passe le tout à la vue
    return $this->render('SdzBlogBundle:Blog:index.html.twig', array(
      'articles' => $articles,
      'page'     => $page,
      'nb_page'  => ceil(count($articles) / $nbParPage) ?: 1
    ));
  }

  public function voirAction(Article $article, Form $form = null)
  {
    // On récupère l'EntityManager
    $em = $this->getDoctrine()->getManager();

    // On récupère la liste des commentaires
    // On n'a pas joint les commentaires depuis l'article car il faut de toute façon
    // refaire une jointure pour avoir les utilisateurs des commentaires
    $commentaires = $em->getRepository('SdzBlogBundle:Commentaire')
                       ->getByArticle($article->getId());

    // On crée le formulaire d'ajout de commentaire pour le passer à la vue
    if (null === $form) {
      $form = $this->getCommentaireForm($article);
    }

    // Puis modifiez la ligne du render comme ceci, pour prendre en compte les variables :
    return $this->render('SdzBlogBundle:Blog:voir.html.twig', array(
      'article'      => $article,
      'form'         => $form->createView(),
      'commentaires' => $commentaires
    ));
  }

  /**
   * @Security("has_role('ROLE_AUTEUR')")
   */
  public function ajouterAction(Request $request)
  {
    $article = new Article;
    if ($this->getUser()) {
      // On définit le User par défaut dans le formulaire (utilisateur courant)
      $article->setUser($this->getUser());
    }

    // On crée le formulaire grâce à l'ArticleType
    $form = $this->createForm(ArticleType::class, $article);

    // On vérifie qu'elle est de type POST
    if ($request->getMethod() == 'POST') {
      // On fait le lien Requête <-> Formulaire
      $form->handleRequest($request);

      // On vérifie que les valeurs rentrées sont correctes
      if ($form->isValid()) {
        // --- Début de notre fonctionnalité BigBrother ---
        // On crée l'évènement
        $event = new MessagePostEvent($article->getContenu(), $this->getUser());

        // On déclenche l'évènement
        $this->get('event_dispatcher')
             ->dispatch(BigbrotherEvents::onMessagePost, $event);

        // On récupère ce qui a été modifié par le ou les listener(s), ici le message
        $article->setContenu($event->getMessage());
        // --- Fin de notre fonctionnalité BigBrother ---

        // --- Dans le cas où vous avez un champ "articleCompetences" dans le formulaire - 1/2 ---
        // Cette ligne est nécessaire pour qu'on puisse enregistrer en bdd en deux étapes :
        // * D'abord l'article tout seul (c'est pour ça qu'on enlève les articleCompetences)
        // * Puis les articleCompetences, juste après, car on a besoin de l'id de l'$article
        //   Or cet id n'est attribué qu'au flush, car on utilise l'AUTOINCREMENT de MySQL !
        $article->getArticleCompetences()->clear();
        // --- Fin du cas 1/2 ---

        // On enregistre l'objet $article dans la base de données
        $em = $this->getDoctrine()->getManager();
        $em->persist($article);
        $em->flush();

        // --- Dans le cas où vous avez un champ "articleCompetences" dans le formulaire - 2/2 ---
        // Maintenant que l'artiche est enregistré et dispose d'un id,
        // On parcourt les articleCompetences pour leur ajouter l'article et les persister manuellement
        // (rappelez-vous, c'est articleCompetence la propriétaire dans sa relation avec Article !)
        foreach ($form->get('articleCompetences')->getData() as $ac) {
          $ac->setArticle($article);
          $em->persist($ac);
        }
        $em->flush();
        // --- Fin du cas 2/2 ---

        // On définit un message flash
        $this->get('session')->getFlashBag()->add('info', 'Article bien ajouté');

        // On redirige vers la page de visualisation de l'article nouvellement créé
        return $this->redirectToRoute('sdzblog_blog_voir', array('slug' => $article->getSlug()));
      }
    }

    // À ce stade :
    // - soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
    // - soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

    return $this->render('SdzBlogBundle:Blog:ajouter.html.twig', array(
      'form' => $form->createView(),
    ));
  }

  /**
   * @Security("has_role('ROLE_AUTEUR')")
   */
  public function modifierAction(Article $article, Request $request)
  {
    // --- Dans le cas où vous avez un champ "articleCompetences" dans le formulaire - 1/3 ---
    // On place dans un tableau les articleCompetences que contient l'article avant la soumission du formulaire
    // Si certains d'entre eux n'existent plus après la soumission, il faudra donc les supprimer
    $listeAc = array();
    foreach ($article->getArticleCompetences() as $ac) {
      $listeAc[] = $ac;
    }
    // --- Fin du cas 1/3 ---

    // On utilise le ArticleEditType
    $form = $this->createForm(ArticleEditType::class, $article);
    if ($request->getMethod() == 'POST') {
      $form->handleRequest($request);

      if ($form->isValid()) {
        // --- Dans le cas où vous avez un champ "articleCompetences" dans le formulaire - 2/3 ---
        // Au même titre que dans l'action d'ajout d'un article, on doit enregistrer
        // l'article et ses articleCompetences en deux fois
        $article->getArticleCompetences()->clear();
        // --- Fin du cas 2/3 ---

        // On enregistre l'article
        $em = $this->getDoctrine()->getManager();
        $em->persist($article);
        $em->flush();

        // --- Dans le cas où vous avez un champ "articleCompetences" dans le formulaire - 3/3 ---
        // On enregistre les articleCompetences (propriétaire) maintenant que $article a un id
        foreach ($form->get('articleCompetences')->getData() as $ac) {
          $ac->setArticle($article);
          $em->persist($ac);
        }
        // Et on supprime les articleCompetences qui existaient au début mais plus maintenant
        foreach ($listeAc as $originalAc) {
          foreach ($form->get('articleCompetences')->getData() as $ac) {
            // Si $originalAc existe dans le formulaire, on sort de la boucle car pas besoin de la supprimer
            if ($originalAc == $ac) {
              continue 2;
            }
          }
          // $originalAc n'existe plus dans le formulaire, on la supprime
          $em->remove($originalAc);
        }
        $em->flush();
        // --- Fin du cas 3/3 ---

        // On définit un message flash
        $this->get('session')->getFlashBag()->add('info', 'Article bien modifié');

        return $this->redirectToRoute('sdzblog_blog_voir', array('slug' => $article->getSlug()));
      }
    }

    return $this->render('SdzBlogBundle:Blog:modifier.html.twig', array(
      'form'    => $form->createView(),
      'article' => $article
    ));
  }

  /**
   * @Security("has_role('ROLE_ADMIN')")
   */
  public function supprimerAction(Article $article, Request $request)
  {
    // On crée un formulaire vide, qui ne contiendra que le champ CSRF
    // Cela permet de protéger la suppression d'article contre cette faille
    $form = $this->createFormBuilder()->getForm();

    if ($request->getMethod() == 'POST') {
      $form->handleRequest($request);

      if ($form->isValid()) { // Ici, isValid ne vérifie donc que le CSRF
        // On supprime l'article
        $em = $this->getDoctrine()->getManager();
        $em->remove($article);
        $em->flush();

        // On définit un message flash
        $this->get('session')->getFlashBag()->add('info', 'Article bien supprimé');

        // Puis on redirige vers l'accueil
        return $this->redirectToRoute('sdzblog_blog_accueil');
      }
    }

    // Si la requête est en GET, on affiche une page de confirmation avant de supprimer
    return $this->render('SdzBlogBundle:Blog:supprimer.html.twig', array(
      'article' => $article,
      'form'    => $form->createView()
    ));
  }


  public function menuAction($nombre)
  {
    $repository = $this->getDoctrine()->getManager()->getRepository('SdzBlogBundle:Article');

    $liste = $repository->findBy(
      array(),                 // Pas de critère
      array('date' => 'desc'), // On tri par date décroissante
      $nombre,                 // On sélectionne $nombre articles
      0                        // A partir du premier
    );

    return $this->render('SdzBlogBundle:Blog:menu.html.twig', array(
      'liste_articles' => $liste // C'est ici tout l'intérêt : le contrôleur passe les variables nécessaires au template !
    ));
  }

  public function traductionAction($name)
  {
    return $this->render('SdzBlogBundle:Blog:traduction.html.twig', array(
      'name' => $name
    ));
  }

  public function feedAction()
  {
    $articles = $this->getDoctrine()
                     ->getManager()
                     ->getRepository('SdzBlogBundle:Article')
                     ->getArticles(10, 1);

    $lastArticle = current($articles->getIterator());

    return $this->render('SdzBlogBundle:Blog:feed.xml.twig', array(
      'articles'  => $articles,
      'buildDate' => $lastArticle->getDate()
    ));
  }
}
