<?php

declare(strict_types=1);

namespace Asynit\Assert;

use Asynit\Attribute\OnCreate;
use Asynit\Test;
use function bovigo\assert\counting;
use function bovigo\assert\exporter;
use function bovigo\assert\predicate\contains;
use function bovigo\assert\predicate\containsSubset;
use function bovigo\assert\predicate\doesNotContain;
use function bovigo\assert\predicate\doesNotEndWith;
use function bovigo\assert\predicate\doesNotHaveKey;
use function bovigo\assert\predicate\doesNotMatch;
use function bovigo\assert\predicate\doesNotStartWith;
use function bovigo\assert\predicate\each;
use function bovigo\assert\predicate\endsWith;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\hasKey;
use function bovigo\assert\predicate\isEmpty;
use function bovigo\assert\predicate\isExistingDirectory;
use function bovigo\assert\predicate\isExistingFile;
use function bovigo\assert\predicate\isFalse;
use function bovigo\assert\predicate\isGreaterThan;
use function bovigo\assert\predicate\isGreaterThanOrEqualTo;
use function bovigo\assert\predicate\isInstanceOf;
use function bovigo\assert\predicate\isLessThan;
use function bovigo\assert\predicate\isLessThanOrEqualTo;
use function bovigo\assert\predicate\isNonExistingDirectory;
use function bovigo\assert\predicate\isNonExistingFile;
use function bovigo\assert\predicate\isNotEmpty;
use function bovigo\assert\predicate\isNotEqualTo;
use function bovigo\assert\predicate\isNotInstanceOf;
use function bovigo\assert\predicate\isNotNull;
use function bovigo\assert\predicate\isNotOfSize;
use function bovigo\assert\predicate\isNotOfType;
use function bovigo\assert\predicate\isNotSameAs;
use function bovigo\assert\predicate\isNull;
use function bovigo\assert\predicate\isOfSize;
use function bovigo\assert\predicate\isOfType;
use function bovigo\assert\predicate\isSameAs;
use function bovigo\assert\predicate\isTrue;
use function bovigo\assert\predicate\matches;
use function bovigo\assert\predicate\matchesFormat;
use bovigo\assert\predicate\Predicate;
use function bovigo\assert\predicate\startsWith;

trait AssertCaseTrait
{
    protected Test $test;

    #[OnCreate]
    public function setUpTest(Test $test): void
    {
        $this->test = $test;
    }

    /**
     * Asserts that an array has a specified key.
     *
     * @param mixed              $key
     * @param array|\ArrayAccess $array
     * @param string             $message
     */
    public function assertArrayHasKey($key, $array, $message = null)
    {
        $this->assert($array, hasKey($key), $message);
    }

    /**
     * Asserts that an array does not have a specified key.
     *
     * @param mixed              $key
     * @param array|\ArrayAccess $array
     * @param string             $message
     */
    public function assertArrayNotHasKey($key, $array, $message = null)
    {
        $this->assert($array, doesNotHaveKey($key), $message);
    }

    /**
     * Asserts that a haystack contains a needle.
     *
     * Please note that setting $ignoreCase, $checkForObjectIdentity or
     * $checkForNonObjectIdentity to a non-default value will cause a fallback
     * to PHPUnit's constraint.
     *
     * @param mixed  $needle
     * @param mixed  $haystack
     * @param string $message
     */
    public function assertContains($needle, $haystack, $message = null)
    {
        $this->assert($haystack, contains($needle), $message);
    }

    /**
     * Asserts that a haystack does not contain a needle.
     *
     * Please note that setting $ignoreCase, $checkForObjectIdentity or
     * $checkForNonObjectIdentity to a non-default value will cause a fallback
     * to PHPUnit's constraint.
     *
     * @param mixed  $needle
     * @param mixed  $haystack
     * @param string $message
     */
    public function assertNotContains($needle, $haystack, $message = null)
    {
        $this->assert($haystack, doesNotContain($needle), $message);
    }

    /**
     * Asserts that a haystack contains only values of a given type.
     *
     * @param string             $type
     * @param array|\Traversable $haystack
     * @param bool               $isNativeType
     * @param string             $message
     *
     * @since  1.1.0
     */
    public function assertContainsOnly($type, $haystack, $isNativeType = null, $message = null)
    {
        if (null === $isNativeType) {
            $isNativeType = self::isNativeType($type);
        }

        if (false === $isNativeType) {
            $this->assertContainsOnlyInstancesOf($type, $haystack, $message);
        } else {
            $this->assert($haystack, each(isOfType($type)), $message);
        }
    }

    /**
     * Asserts that a haystack contains only instances of a given classname.
     *
     * @param string             $classname
     * @param array|\Traversable $haystack
     * @param string             $message
     *
     * @since  1.1.0
     */
    public function assertContainsOnlyInstancesOf($classname, $haystack, $message = null)
    {
        $this->assert($haystack, each(isInstanceOf($classname)), $message);
    }

    /**
     * Asserts that a haystack does not contain only values of a given type.
     *
     * @param string             $type
     * @param array|\Traversable $haystack
     * @param bool               $isNativeType
     * @param string             $message
     *
     * @since  1.1.0
     */
    public function assertNotContainsOnly($type, $haystack, $isNativeType = null, $message = null)
    {
        if (null === $isNativeType) {
            $isNativeType = self::isNativeType($type);
        }

        $this->assert(
            $haystack,
            each(false === $isNativeType ? isNotInstanceOf($type) : isNotOfType($type)),
            $message
        );
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param int    $expectedCount
     * @param mixed  $haystack
     * @param string $message
     */
    public function assertCount($expectedCount, $haystack, $message = null)
    {
        $this->assert($haystack, isOfSize($expectedCount), $message);
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param int    $expectedCount
     * @param mixed  $haystack
     * @param string $message
     */
    public function assertNotCount($expectedCount, $haystack, $message = null)
    {
        $this->assert($haystack, isNotOfSize($expectedCount), $message);
    }

    /**
     * Asserts that two variables are equal.
     *
     * Please note that setting $canonicalize or $ignoreCase to true will cause
     * a fallback to PHPUnit's constraint.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     * @param float  $delta
     */
    public function assertEquals($expected, $actual, $message = null, $delta = 0.0)
    {
        $this->assert($actual, equals($expected, $delta), $message);
    }

    /**
     * Asserts that two variables are not equal.
     *
     * Please note that setting $canonicalize or $ignoreCase to true will cause
     * a fallback to PHPUnit's constraint.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     * @param float  $delta
     */
    public function assertNotEquals($expected, $actual, $message = null, $delta = 0.0)
    {
        $this->assert($actual, isNotEqualTo($expected, $delta), $message);
    }

    /**
     * Asserts that a variable is empty.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public function assertEmpty($actual, $message = null)
    {
        $this->assert($actual, isEmpty(), $message);
    }

    /**
     * Asserts that a variable is not empty.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public function assertNotEmpty($actual, $message = null)
    {
        $this->assert($actual, isNotEmpty(), $message);
    }

    /**
     * Asserts that a value is greater than another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public function assertGreaterThan($expected, $actual, $message = null)
    {
        $this->assert($actual, isGreaterThan($expected), $message);
    }

    /**
     * Asserts that a value is greater than or equal to another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public function assertGreaterThanOrEqual($expected, $actual, $message = null)
    {
        $this->assert($actual, isGreaterThanOrEqualTo($expected), $message);
    }

    /**
     * Asserts that a value is smaller than another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public function assertLessThan($expected, $actual, $message = null)
    {
        $this->assert($actual, isLessThan($expected), $message);
    }

    /**
     * Asserts that a value is smaller than or equal to another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public function assertLessThanOrEqual($expected, $actual, $message = null)
    {
        $this->assert($actual, isLessThanOrEqualTo($expected), $message);
    }

    /**
     * Asserts that a file exists.
     *
     * @param string $filename
     * @param string $message
     */
    public function assertFileExists($filename, $message = null)
    {
        $this->assert($filename, isExistingFile()->or(isExistingDirectory()), $message);
    }

    /**
     * Asserts that a file does not exist.
     *
     * @param string $filename
     * @param string $message
     */
    public function assertFileNotExists($filename, $message = null)
    {
        $this->assert($filename, isNonExistingFile()->and(isNonExistingDirectory()), $message);
    }

    /**
     * Asserts that a condition is true.
     *
     * @param bool   $condition
     * @param string $message
     */
    public function assertTrue($condition, $message = null)
    {
        $this->assert($condition, isTrue(), $message);
    }

    /**
     * Asserts that a condition is false.
     *
     * @param bool   $condition
     * @param string $message
     */
    public function assertFalse($condition, $message = null)
    {
        $this->assert($condition, isFalse(), $message);
    }

    /**
     * Asserts that a variable is not null.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public function assertNotNull($actual, $message = null)
    {
        $this->assert($actual, isNotNull(), $message);
    }

    /**
     * Asserts that a variable is null.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public function assertNull($actual, $message = null)
    {
        $this->assert($actual, isNull(), $message);
    }

    /**
     * Asserts that a variable is finite.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public function assertFinite($actual, $message = null)
    {
        $this->assert($actual, 'is_finite', $message);
    }

    /**
     * Asserts that a variable is infinite.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public function assertInfinite($actual, $message = null)
    {
        $this->assert($actual, 'is_infinite', $message);
    }

    /**
     * Asserts that a variable is nan.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public function assertNan($actual, $message = null)
    {
        $this->assert($actual, 'is_nan', $message);
    }

    /**
     * Asserts that two variables have the same type and value.
     * Used on objects, it asserts that two variables reference
     * the same object.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public function assertSame($expected, $actual, $message = null)
    {
        $this->assert($actual, isSameAs($expected), $message);
    }

    /**
     * Asserts that two variables do not have the same type and value.
     * Used on objects, it asserts that two variables do not reference
     * the same object.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public function assertNotSame($expected, $actual, $message = null)
    {
        $this->assert($actual, isNotSameAs($expected), $message);
    }

    /**
     * Asserts that a variable is of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public function assertInstanceOf($expected, $actual, $message = null)
    {
        $this->assert($actual, isInstanceOf($expected), $message);
    }

    /**
     * Asserts that a variable is not of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public function assertNotInstanceOf($expected, $actual, $message = null)
    {
        $this->assert($actual, isNotInstanceOf($expected), $message);
    }

    /**
     * Asserts that a variable is of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public function assertInternalType($expected, $actual, $message = null)
    {
        $this->assert($actual, isOfType($expected), $message);
    }

    /**
     * Asserts that a variable is not of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public function assertNotInternalType($expected, $actual, $message = null)
    {
        $this->assert($actual, isNotOfType($expected), $message);
    }

    /**
     * Asserts that a string matches a given regular expression.
     *
     * @param string $pattern
     * @param string $string
     * @param string $message
     */
    public function assertRegExp($pattern, $string, $message = null)
    {
        $this->assert($string, matches($pattern), $message);
    }

    /**
     * Asserts that a string does not match a given regular expression.
     *
     * @param string $pattern
     * @param string $string
     * @param string $message
     */
    public function assertNotRegExp($pattern, $string, $message = null)
    {
        $this->assert($string, doesNotMatch($pattern), $message);
    }

    /**
     * Asserts that a string starts with a given prefix.
     *
     * @param string $prefix
     * @param string $string
     * @param string $message
     */
    public function assertStringStartsWith($prefix, $string, $message = null)
    {
        $this->assert($string, startsWith($prefix), $message);
    }

    /**
     * Asserts that a string starts not with a given prefix.
     *
     * @param string $prefix
     * @param string $string
     * @param string $message
     */
    public function assertStringStartsNotWith($prefix, $string, $message = null)
    {
        $this->assert($string, doesNotStartWith($prefix), $message);
    }

    /**
     * Asserts that a string ends with a given suffix.
     *
     * @param string $suffix
     * @param string $string
     * @param string $message
     */
    public function assertStringEndsWith($suffix, $string, $message = null)
    {
        $this->assert($string, endsWith($suffix), $message);
    }

    /**
     * Asserts that a string ends not with a given suffix.
     *
     * @param string $suffix
     * @param string $string
     * @param string $message
     */
    public function assertStringEndsNotWith($suffix, $string, $message = null)
    {
        $this->assert($string, doesNotEndWith($suffix), $message);
    }

    /**
     * Asserts that a string matches a given format string.
     *
     * @param string $format
     * @param string $string
     * @param string $message
     */
    public function assertStringMatchesFormat($format, $string, $message = '')
    {
        if (!function_exists('bovigo\assert\predicate\matchesFormat')) {
            throw new \Exception('The "matchesFormat" assertion exists since bovigo/assert 3.2. Please upgrade the library before using this function.');
        }

        $this->assert($string, matchesFormat($format), $message);
    }

    public function assertContainsSubset(array $other, array $subset, bool $strict = false, string $message = '')
    {
        $this->assert($subset, containsSubset($other, $strict), $message);
    }

    /**
     * @param $value
     */
    public function assert($value, callable $predicate, string $description = null): bool
    {
        return (new Assertion($value, exporter(), $this->test))
            ->evaluate(counting(Predicate::castFrom($predicate)), $description);
    }

    private static function isNativeType($type): bool
    {
        return \in_array(
            $type,
            [
                'numeric',
                'integer',
                'int',
                'float',
                'string',
                'boolean',
                'bool',
                'null',
                'array',
                'object',
                'resource',
                'scalar',
            ],
            true
        );
    }
}
