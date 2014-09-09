<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */
namespace Eltrino\DiamanteDeskBundle\Form\Type;

use Eltrino\DiamanteDeskBundle\Form\DataTransformer\PriorityTransformer;
use Eltrino\DiamanteDeskBundle\Form\DataTransformer\SourceTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Eltrino\DiamanteDeskBundle\Form\DataTransformer\StatusTransformer;
use Eltrino\DiamanteDeskBundle\Ticket\Model\Priority;

class CreateTicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'branch',
            'entity',
            array(
                'label' => 'Branch',
                'class' => 'EltrinoDiamanteDeskBundle:Branch',
                'property' => 'name',
                'empty_value' => 'Choose branch...'
            )
        );

        $builder->add(
            'subject',
            'text',
            array(
                'label' => 'Subject',
                'required' => true,
            )
        );

        $builder->add(
            'description',
            'textarea',
            array(
                'label' => 'Description',
                'required' => true,
                'attr'  => array(
                    'class' => 'diam-ticket-description'
                ),
            )
        );

        $statusTransformer = new StatusTransformer();
        $statusOptions = $statusTransformer->getOptions();

        $builder->add(
            $builder->create('status', 'choice',
                array(
                    'label' => 'Status',
                    'required' => true,
                    'choices' => $statusOptions
                ))
                ->addModelTransformer($statusTransformer)
        );

        $builder->add(
            'files',
            'file',
            array(
                'label' => 'File',
                'required' => true,
                'attr' => array(
                    'multiple' => 'multiple'
                )
            )
        );

        $priorityTransformer = new PriorityTransformer();
        $priorities = $priorityTransformer->getOptions();

        $builder->add(
            $builder->create(
                'priority',
                'choice',
                array(
                    'label'    => 'Priority',
                    'required' => true,
                    'choices'  => $priorities,
                )
            )
            ->addModelTransformer($priorityTransformer)
        );

        $sourceTransformer = new SourceTransformer();
        $sources = $sourceTransformer->getOptions();

        $builder->add(
            $builder->create(
                'source',
                'choice',
                array(
                    'label'    => 'Source',
                    'required' => true,
                    'choices'  => $sources,
                )
            )
                ->addModelTransformer($sourceTransformer)
        );

        $builder->add(
            'reporter',
            'oro_user_select',
            array(
                'required' => true
            )
        );

        $builder->add(
            'assignee',
            'oro_user_select',
            array(
                'required' => false
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Eltrino\DiamanteDeskBundle\Form\Command\CreateTicketCommand',
                'intention' => 'ticket',
                'cascade_validation' => true
            )
        );
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'diamante_ticket_form';
    }
}
