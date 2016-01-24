<?php

namespace Sdz\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Sdz\BlogBundle\Entity\Article;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Sdz\UserBundle\Entity\User;
use Sdz\BlogBundle\Entity\Categorie;

class ArticleType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('date',        DateTimeType::class)
      ->add('titre',       TextType::class)
      ->add('contenu',     TextareaType::class)
      ->add('image',       ImageType::class, array('required' => false))
      ->add('categories',  EntityType::class, array(
        'class'    => Categorie::class,
        'choice_label' => 'nom',
        'multiple' => true
      ))
      /*
       * Rappel pour un champ de type collection :
       ** - 1er argument : nom du champ, ici "categories" car c'est le nom de l'attribut ;
       ** - 2e argument : type du champ, ici "collection" qui est une liste de quelque chose ;
       ** - 3e argument : tableau d'options du champ.
      */
      ->add('articleCompetences', CollectionType::class, array(
          'entry_type'         => ArticleCompetenceType::class,
          'allow_add'    => true,
          'allow_delete' => true,
          'by_reference' => false,
          'required'     => false
      ))
    ;

    // On ajoute une fonction qui va écouter l'évènement PRE_SET_DATA
    $builder->addEventListener(
      FormEvents::PRE_SET_DATA,    // Ici, on définit l'évènement qui nous intéresse
      function(FormEvent $event) { // Ici, on définit une fonction qui sera exécutée lors de l'évènement
        $article = $event->getData();
        // Cette condition est importante, on en reparle plus loin
        if (null === $article) {
          return; // On sort de la fonction lorsque $article vaut null
        }
        // 1. Si l'article n'est pas encore publié, on ajoute le champ publication
        if (false === $article->getPublication()) {
          $event->getForm()->add('publication', CheckboxType::class, null, array('required' => false));
        } else { // Sinon, on le supprime
          $event->getForm()->remove('publication');
        }

        // 2. Si un User est attaché à l'article, on n'affiche pas le champ auteur
        if (null === $article->getUser()) {
        	$event->getForm()->add('auteur', TextType::class);
        } else {
        	$event->getForm()->add('user', EntityType::class, array('class' => User::class));
        }
      }
    );
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => Article::class,
    ));
  }

  public function getName()
  {
    return 'sdz_blogbundle_articletype';
  }
}
