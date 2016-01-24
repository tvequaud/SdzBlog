<?php
// src/Sdz/UserBundle/Controller/SecurityController.php;

namespace Sdz\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\UserBundle\Controller\SecurityController as BaseController;


class SecurityController extends BaseController
{
  /**
   * On modifie la façon dont est choisie la vue lors du rendu du formulaire de connexion
   *
   * Je sais que c'est cette méthode qu'il faut hériter car j'ai été voir le contrôleur d'origine du bundle :
   * https://github.com/FriendsOfSymfony/FOSUserBundle/blob/master/Controller/SecurityController.php
   */
  protected function renderLogin(array $data)
  {
    // Sur la page du formulaire de connexion, on utilise la vue classique "login"
    // Cette vue hérite du layout et ne peut donc être utilisée qu'individuellement
    if ($this->get('request_stack')->getCurrentRequest()->attributes->get('_route') == 'SdzUserBundle:Security:login') {
      $view = 'login';
    } else {
      // Mais sinon, il s'agit du formulaire de connexion intégré au menu, on utilise la vue "login_content"
      // car il ne faut pas hériter du layout !
      $view = 'login_content';
    }

//     $engine = $this->container->getParameter('fos_user.template.engine');
    $engine = 'twig';
    $template = sprintf('FOSUserBundle:Security:%s.html.%s', $view, $engine);

    return $this->get('templating')->renderResponse($template, $data);
  }
}
