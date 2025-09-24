<?php

namespace Jane\Component\JsonSchema\Guesser\Validator\Any;

use Jane\Component\JsonSchema\Guesser\Guess\ClassGuess;
use Jane\Component\JsonSchema\Guesser\Guess\Property;
use Jane\Component\JsonSchema\Guesser\Validator\ObjectCheckTrait;
use Jane\Component\JsonSchema\Guesser\Validator\ValidatorGuess;
use Jane\Component\JsonSchema\Guesser\Validator\ValidatorInterface;
use Jane\Component\JsonSchema\JsonSchema\Model\JsonSchema;
use Symfony\Component\Validator\Constraints\NotNull;

class NotNullValidator implements ValidatorInterface
{
    use ObjectCheckTrait;

    public function supports($object): bool
    {
        if (\get_class($object) === JsonSchema::class) {
            $oneOf = $object->getOneOf();

            if ($oneOf !== null && \count($oneOf) > 0) {
                return !$this->isObjectNullable($object);
            }

            return \is_array($object->getType()) ? !\in_array('null', $object->getType()) : 'null' !== $object->getType();
        }
        if (\get_class($object) === 'Jane\\Component\\OpenApi2\\JsonSchema\\Model\\Schema') {
            return $object->offsetExists('x-nullable') && \is_bool($object->offsetGet('x-nullable')) && $object->offsetGet('x-nullable');
        }
        if (\get_class($object) === 'Jane\\Component\\OpenApi3\\JsonSchema\\Model\\Schema') {
            return method_exists($object, 'getNullable') && !($object->getNullable() ?? false);
        }

        return false;
    }

    /**
     * @param JsonSchema          $object
     * @param ClassGuess|Property $guess
     */
    public function guess($object, string $name, $guess): void
    {
        $guess->addValidatorGuess(new ValidatorGuess(NotNull::class, [
            'message' => 'This value should not be null.',
        ]));
    }

    protected function isObjectNullable($property): bool {
        $oneOf = $property->getOneOf();

        if ($oneOf !== null && \count($oneOf) > 0) {
            foreach ($oneOf as $oneOfProperty) {
                if (!($oneOfProperty instanceof JsonSchema)) {
                    continue;
                }
                if ($this->isObjectNullable($oneOfProperty)) {
                    return true;
                }
            }

            return false;
        }

        $type = $property->getType();

        return 'null' == $type || (\is_array($type) && \in_array('null', $type));
    }
}
