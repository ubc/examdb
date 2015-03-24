<?php

namespace UBC\Exam\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ExamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('campus')
            ->add('faculty')
            ->add('dept')
            ->add('subject_code')
            ->add('year')
            ->add('term')
            ->add('type')
//            ->add('comments')
            ->add('cross_listed')
            ->add('legal_content_owner')
            ->add('legal_uploader')
//            ->add('legal_date')
            ->add('legal_agreed')
            ->add('access_level')
//            ->add('created')
//            ->add('modified')
//            ->add('downloads')
//            ->add('uploaded_by')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'UBC\Exam\MainBundle\Entity\Exam'
        ));
    }

    public function getName()
    {
        return 'ubc_exam_mainbundle_exam';
    }
}
