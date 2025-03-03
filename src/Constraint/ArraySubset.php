<?php

declare(strict_types=1);

namespace DMS\PHPUnitExtensions\ArraySubset\Constraint;

use ArrayAccess;
use ArrayObject;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\SebastianBergmann\Comparator\ComparisonFailure as Phar_ComparisonFailure;
use PHPUnit\Util\Exporter;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Traversable;

use function array_replace_recursive;
use function class_exists;
use function is_array;
use function iterator_to_array;
use function var_export;

/**
 * Constraint that asserts that the array it is evaluated for has a specified subset.
 *
 * Uses array_replace_recursive() to check if a key value subset is part of the
 * subject array.
 */
final class ArraySubset extends Constraint
{
    /**
     * @var iterable|mixed[]
     */
    private $subset;

    /**
     * @var bool
     */
    private $strict;

    /**
     * @param mixed[] $subset
     */
    public function __construct(iterable $subset, bool $strict = false)
    {
        $this->strict = $strict;
        $this->subset = $subset;
    }

    /**
     * Evaluates the constraint for parameter $other
     *
     * If $returnResult is set to false (the default), an exception is thrown
     * in case of a failure. null is returned otherwise.
     *
     * If $returnResult is true, the result of the evaluation is returned as
     * a boolean value instead: true in case of success, false in case of a
     * failure.
     *
     * @param mixed[]|ArrayAccess $other
     *
     * @return mixed[]|bool|null
     *
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException|Exception
     */
    public function evaluate($other, string $description = '', bool $returnResult = false): ?bool
    {
        //type cast $other & $this->subset as an array to allow
        //support in standard array functions.
        $other        = $this->toArray($other);
        $this->subset = $this->toArray($this->subset);
        $patched      = array_replace_recursive($other, $this->subset);
        if ($this->strict) {
            $result = $other === $patched;
        } else {
            $result = $other == $patched;
        }

        if ($returnResult) {
            return $result;
        }

        if ($result) {
            return null;
        }

        // Support use of this library when running PHPUnit as a Phar.
        if (class_exists(Phar_ComparisonFailure::class) === true) {
            $class = Phar_ComparisonFailure::class;
        } else {
            $class = ComparisonFailure::class;
        }

        $f = new $class(
            $patched,
            $other,
            var_export($patched, true),
            var_export($other, true)
        );
        $this->fail($other, $description, $f);
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @throws InvalidArgumentException|Exception
     */
    public function toString(): string
    {
        return 'has the subset ' . Exporter::export($this->subset);
    }

    /**
     * Returns the description of the failure
     *
     * The beginning of failure messages is "Failed asserting that" in most
     * cases. This method should return the second part of that sentence.
     *
     * @param mixed $other evaluated value or object
     *
     * @throws InvalidArgumentException|Exception
     */
    protected function failureDescription($other): string
    {
        return 'an array ' . $this->toString();
    }

    /**
     * @param mixed[]|iterable $other
     *
     * @return mixed[]
     */
    private function toArray(iterable $other): array
    {
        if (is_array($other)) {
            return $other;
        }

        if ($other instanceof ArrayObject) {
            return $other->getArrayCopy();
        }

        if ($other instanceof Traversable) {
            return iterator_to_array($other);
        }

        // Keep BC even if we know that array would not be the expected one
        return (array) $other;
    }
}
