<?php

namespace Sdz\BlogBundle\Akismet;

use Ornicar\AkismetBundle\Akismet\AkismetInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class AkismetValidator extends ConstraintValidator
{
  private $akismet;
  private $isAuthenticated;

  public function __construct(AkismetInterface $akismet, AuthorizationChecker $securityContext)
  {
    $this->akismet = $akismet;

    // On détermine si l'utilisateur courant est identifié
    // Si c'est le cas, on n'utilisera pas akismet
    $this->isAuthenticated = $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED');
  }

  public function validate($commentaire, Constraint $constraint)
  {
    if ($this->isAuthenticated) {
      return;
    }

    $isSpam = $this->akismet->isSpam(array(
      'comment_author'  => $commentaire->getAuteur() ?: $commentaire->getUser()->getUsername(),
      'comment_content' => $commentaire->getContenu()
    ));

    if ($isSpam) {
      $this->context->addViolationAt('contenu', $constraint->message);
    }
  }
}
