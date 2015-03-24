<?php


namespace UBC\Exam\MainBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class HasPermissionValidator extends ConstraintValidator
{
    private $authChecker = null;

    public function __construct($authChecker)
    {
        $this->authChecker = $authChecker;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value)) {
            $roles = explode(',', $value);
        } else {
            $roles = $value;
        }
        foreach ($roles as $role) {
            if ($this->authChecker == null || !$this->authChecker->isGranted($role)) {
                $this->context->addViolation(
                    $constraint->message,
                    array('%role%' => $role)
                );
            }
        }
    }
}