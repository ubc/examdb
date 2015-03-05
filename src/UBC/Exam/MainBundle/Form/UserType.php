<?php

namespace UBC\Exam\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text', array('trim' => true))
            ->add('firstname', 'text', array('label' => 'First Name', 'trim' => true))
            ->add('lastname', 'text', array('label' => 'Last Name', 'trim' => true))
            ->add('roleString', 'text', array('label' => 'Role(s) (separate each with a comma)', 'trim' => true))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'UBC\Exam\MainBundle\Entity\User'
        ));
    }

    public function getName()
    {
        return 'ubc_exam_mainbundle_user';
    }
}
