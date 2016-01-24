<?php

namespace Sdz\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Sdz\BlogBundle\Entity\Commentaire;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder->add('contenu', TextareaType::class);
      $builder->addEventListener(
        FormEvents::PRE_SET_DATA,
        function (FormEvent $event) {
          if (null === $event->getData()->getUser()) {
            $event->getForm()->add('auteur', TextType::class);
          }
        }
      );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
      $resolver->setDefaults(array(
        'data_class' => Commentaire::class,
      ));
    }

    public function getName()
    {
        return 'sdz_blogbundle_commentairetype';
    }
}
