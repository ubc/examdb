<?php

namespace UBC\Exam\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use UBC\Exam\MainBundle\Entity\Exam;

class ExamFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//            ->add('id', 'filter_number_range')
            ->add('campus', 'filter_choice', array('choices' => array('UBC' => 'Vancouver', 'UBCO' => 'Okanagan')))
            ->add('faculty', 'filter_text')
            ->add('dept', 'filter_text')
            ->add('subject_code', 'filter_text')
            ->add('year', 'filter_number')
            ->add('term', 'filter_choice', array('choices' => Exam::$TERMS))
            ->add('type', 'filter_choice', array('choices' => Exam::$TYPES))
//            ->add('comments', 'filter_text')
            ->add('cross_listed', 'filter_text')
            ->add('legal_content_owner', 'filter_text')
            ->add('legal_uploader', 'filter_text')
//            ->add('legal_date', 'filter_date_range')
            ->add('legal_agreed', 'filter_boolean')
            ->add('access_level', 'filter_choice', array('choices' => Exam::$ACCESS_LEVELS))
//            ->add('created', 'filter_date_range')
//            ->add('modified', 'filter_date_range')
//            ->add('downloads', 'filter_number_range')
        ;

        $listener = function(FormEvent $event)
        {
            // Is data empty?
            foreach ($event->getData() as $data) {
                if(is_array($data)) {
                    foreach ($data as $subData) {
                        if(!empty($subData)) return;
                    }
                }
                else {
                    if(!empty($data)) return;
                }
            }

            $event->getForm()->addError(new FormError('Filter empty'));
        };
        $builder->addEventListener(FormEvents::POST_BIND, $listener);
    }

    public function getName()
    {
        return 'ubc_exam_mainbundle_examfiltertype';
    }
}
