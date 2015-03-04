<?php


namespace UBC\Exam\MainBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class HasPermission extends Constraint
{
    public $message = 'You don\'t have permission to edit this user with role "%role%"';

    public function validatedBy()
    {
        return 'has_permission';
    }
}