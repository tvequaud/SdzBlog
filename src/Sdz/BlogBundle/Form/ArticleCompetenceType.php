<?php

namespace Sdz\BlogBundle\Form;

use Sdz\BlogBundle\Entity\ArticleCompetence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Sdz\BlogBundle\Entity\Competence;


class ArticleCompetenceType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('competence', EntityType::class, array(
        'class'   => Competence::class,
      ))
      ->add('niveau', ChoiceType::class, array(
        'choices' => ArticleCompetence::getNiveaux(),
      ))
    ;
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => ArticleCompetence::class,
    ));
  }

  public function getName()
  {
    return 'sdz_blogbundle_articlecompetencetype';
  }
}
