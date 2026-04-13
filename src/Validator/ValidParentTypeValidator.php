<?php

namespace MartenaSoft\PageBundle\Validator;


use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\PageBundle\Entity\Page;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidParentTypeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidParentType) {
            throw new UnexpectedTypeException($constraint, ValidParentType::class);
        }

        if (!$value instanceof Page || $value->isOnMain() ) {
            return;
        }

        $parent = $value->getParent();
        $type = $parent?->getParentType();
        if ($type !== null && $type !== $value->getType()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ type }}', DictionaryPage::TYPES[$value->getType()])
                ->atPath('errors')
                ->addViolation();
        }
    }
}
