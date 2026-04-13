<?php

namespace MartenaSoft\PageBundle\Validator;

use MartenaSoft\CommonLibrary\Dictionary\DictionaryMessage;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ValidParentType extends Constraint
{
    public string $message = DictionaryMessage::MESSAGE_ERROR_CREATING_WITH_TYPE;

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}