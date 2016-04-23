<?php

namespace UBC\Exam\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use UBC\Exam\MainBundle\Entity\Exam;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\ChoiceFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\NumberFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\DateRangeFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\BooleanFilterType;

class ExamFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('id', NumberRangeFilterType::class)
            ->add('campus', ChoiceFilterType::class, array('choices' => Exam::$CAMPUSES, 'choices_as_values' => true))
            ->add('faculty', TextFilterType::class)
            ->add('dept', TextFilterType::class)
            ->add('subject_code', TextFilterType::class)
            ->add('year', NumberFilterType::class)
            ->add('term', ChoiceFilterType::class, array('choices' => array_flip(Exam::$TERMS), 'choices_as_values' => true))
            ->add('type', ChoiceFilterType::class, array('choices' => array_flip(Exam::$TYPES), 'choices_as_values' => true))
            //->add('comments', TextFilterType::class)
            ->add('cross_listed', TextFilterType::class)
            ->add('legal_content_owner', TextFilterType::class)
            ->add('legal_uploader', TextFilterType::class)
            //            ->add('legal_date', DateRangeFilterType::class)
            ->add('legal_agreed', BooleanFilterType::class)
            ->add('access_level', ChoiceFilterType::class, array('choices' => array_flip(Exam::$ACCESS_LEVELS), 'choices_as_values' => true))
            //->add('created', DateRangeFilterType::class)
            //->add('modified', DateRangeFilterType::class)
            //->add('downloads', NumberRangeFilterType::class)
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
        $builder->addEventListener(FormEvents::POST_SUBMIT, $listener);
    }

    public function getBlockPrefix()
    {
        return 'ubc_exam_mainbundle_examfiltertype';
    }
}
