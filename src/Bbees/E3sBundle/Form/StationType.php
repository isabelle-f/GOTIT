<?php

/*
 * This file is part of the E3sBundle.
 *
 * Authors : see information concerning authors of GOTIT project in file AUTHORS.md
 *
 * E3sBundle is free software : you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * 
 * E3sBundle is distributed in the hope that it will be useful,but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with E3sBundle.  If not, see <https://www.gnu.org/licenses/>
 * 
 */

namespace Bbees\E3sBundle\Form;

use Bbees\E3sBundle\Entity\Pays;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Form;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;


class StationType extends AbstractType
{
  /**
   * Dynamically handles country selection to build a matching list of municipalities.
   * See : https://symfony.com/doc/3.4/form/dynamic_form_modification.html#dynamic-generation-for-submitted-forms
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('codeStation')
      ->add('nomStation')
      ->add('infoDescription')
      ->add(
        'paysFk',
        EntityType::class,
        array(
          'class' => 'BbeesE3sBundle:Pays',
          'query_builder' => function (EntityRepository $er) {
            return $er->createQueryBuilder('pays')
              ->orderBy('pays.nomPays', 'ASC');
          },
          'placeholder' => 'Choose a Country',
          'choice_label' => 'nom_pays',
          'multiple' => false
        )
      )
      ->add('communeFk', HiddenType::class) // Placeholder, replaced by eventlisteners below
      ->add('habitatTypeVocFk', EntityType::class, array(
        'class' => 'BbeesE3sBundle:Voc', 'placeholder' => 'Choose an Habitat Type',
        'query_builder' => function (EntityRepository $er) {
          return $er->createQueryBuilder('voc')
            ->where('voc.parent LIKE :parent')
            ->setParameter('parent', 'habitatType')
            ->orderBy('voc.libelle', 'ASC');
        },
        'choice_translation_domain' => true, 'choice_label' => 'libelle', 'multiple' => false, 'expanded' => false
      ))
      ->add('pointAccesVocFk', EntityType::class, array(
        'class' => 'BbeesE3sBundle:Voc', 'placeholder' => 'Choose an Access Point',
        'query_builder' => function (EntityRepository $er) {
          return $er->createQueryBuilder('voc')
            ->where('voc.parent LIKE :parent')
            ->setParameter('parent', 'pointAcces')
            ->orderBy('voc.libelle', 'ASC');
        },
        'choice_translation_domain' => true, 'choice_label' => 'libelle', 'multiple' => false, 'expanded' => false
      ))
      ->add('latDegDec', NumberType::class, array('required' => true,  'scale' => 6))
      ->add('longDegDec', NumberType::class, array('required' => true,  'scale' => 6))
      ->add('precisionLatLongVocFk', EntityType::class, array(
        'class' => 'BbeesE3sBundle:Voc', 'placeholder' => 'Choose a GPS Distance Quality',
        'query_builder' => function (EntityRepository $er) {
          return $er->createQueryBuilder('voc')
            ->where('voc.parent LIKE :parent')
            ->setParameter('parent', 'precisionLatLong')
            ->orderBy('voc.libelle', 'ASC');
        },
        'choice_translation_domain' => true, 'choice_label' => 'libelle', 'multiple' => false, 'expanded' => false
      ))
      ->add('altitudeM')
      ->add('commentaireStation')
      ->add('dateCre', DateTimeType::class, array('required' => false, 'widget' => 'single_text', 'format' => 'Y-MM-dd HH:mm:ss', 'html5' => false))
      ->add('dateMaj', DateTimeType::class, array('required' => false,  'widget' => 'single_text', 'format' => 'Y-MM-dd HH:mm:ss', 'html5' => false,))
      ->add('userCre', HiddenType::class, array())
      ->add('userMaj', HiddenType::class, array());

    // Edits Commune option list according to currently selected country
    $formModifier = function (Form $form, Pays $country = null) {
      $municipalities = null === $country ? [] : $country->getCommunes()->toArray();

      $form->add('communeFk', EntityType::class, array(
        'class' => 'BbeesE3sBundle:Commune',
        'query_builder' => function (EntityRepository $er) {
          return $er->createQueryBuilder('commune')
            ->orderBy('commune.codeCommune', 'ASC');
        },
        'choice_label' => 'code_commune',
        'choices' => $municipalities,
        'multiple' => false,
        'expanded' => false,
        'placeholder' => 'Choose a Commune'
      ));
    };

    // Update Commune option list on form initialization
    $builder->addEventListener(
      FormEvents::PRE_SET_DATA,
      function (FormEvent $event) use ($formModifier) {
        $data = $event->getData();
        $formModifier($event->getForm(), $data->getPaysFk());
      }
    );

    // Update Commune option list when selected country changes
    $builder->get('paysFk')->addEventListener(
      FormEvents::POST_SUBMIT,
      function (FormEvent $event) use ($formModifier) {
        $country = $event->getForm()->getData();
        $formModifier($event->getForm()->getParent(), $country);
      }
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'Bbees\E3sBundle\Entity\Station'
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPrefix()
  {
    return 'bbees_e3sbundle_station';
  }
}
