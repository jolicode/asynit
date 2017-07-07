<?php

declare(strict_types=1);

namespace Asynit\Assert;

use function bovigo\assert\{
    assert,
    assertEmpty,
    assertFalse,
    assertNotEmpty,
    assertNotNull,
    assertNull,
    assertTrue
};
use function bovigo\assert\predicate\{
    contains,
    doesNotContain,
    doesNotEndWith,
    doesNotHaveKey,
    doesNotMatch,
    doesNotStartWith,
    each,
    endsWith,
    equals,
    hasKey,
    isExistingDirectory,
    isExistingFile,
    isGreaterThan,
    isGreaterThanOrEqualTo,
    isInstanceOf,
    isLessThan,
    isLessThanOrEqualTo,
    isNonExistingDirectory,
    isNonExistingFile,
    isNotEqualTo,
    isNotInstanceOf,
    isNotOfSize,
    isNotOfType,
    isNotSameAs,
    isOfSize,
    isOfType,
    isSameAs,
    matches,
    startsWith
};

trait AssertCaseTrait
{
    /**
     * Asserts that an array has a specified key.
     *
     * @param mixed             $key
     * @param array|\ArrayAccess $array
     * @param string            $message
     */
    public static function assertArrayHasKey($key, $array, $message = '')
    {
        assert($array, hasKey($key), $message);
    }

    /**
     * Asserts that an array does not have a specified key.
     *
     * @param mixed             $key
     * @param array|\ArrayAccess $array
     * @param string            $message
     */
    public static function assertArrayNotHasKey($key, $array, $message = '')
    {
        assert($array, doesNotHaveKey($key), $message);
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
    public static function assertContains($needle, $haystack, $message = '')
    {
        assert($haystack, contains($needle), $message);
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
    public static function assertNotContains($needle, $haystack, $message = '')
    {
        assert($haystack, doesNotContain($needle), $message);
    }

    /**
     * Asserts that a haystack contains only values of a given type.
     *
     * @param  string             $type
     * @param  array|\Traversable  $haystack
     * @param  bool               $isNativeType
     * @param  string             $message
     * @since  1.1.0
     */
    public static function assertContainsOnly($type, $haystack, $isNativeType = null, $message = '')
    {
        if (null === $isNativeType) {
            $isNativeType = self::isNativeType($type);
        }

        if (false === $isNativeType) {
            self::assertContainsOnlyInstancesOf($type, $haystack, $message);
        } else {
            assert($haystack, each(isOfType($type)), $message);
        }
    }

    /**
     * Asserts that a haystack contains only instances of a given classname
     *
     * @param  string            $classname
     * @param  array|\Traversable $haystack
     * @param  string            $message
     * @since  1.1.0
     */
    public static function assertContainsOnlyInstancesOf($classname, $haystack, $message = '')
    {
        assert($haystack, each(isInstanceOf($classname)), $message);
    }

    /**
     * Asserts that a haystack does not contain only values of a given type.
     *
     * @param  string             $type
     * @param  array|\Traversable  $haystack
     * @param  bool               $isNativeType
     * @param  string             $message
     * @since  1.1.0
     */
    public static function assertNotContainsOnly($type, $haystack, $isNativeType = null, $message = '')
    {
        if (null === $isNativeType) {
            $isNativeType = self::isNativeType($type);
        }

        assert(
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
    public static function assertCount($expectedCount, $haystack, $message = '')
    {
        assert($haystack, isOfSize($expectedCount), $message);
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param int    $expectedCount
     * @param mixed  $haystack
     * @param string $message
     */
    public static function assertNotCount($expectedCount, $haystack, $message = '')
    {
        assert($haystack, isNotOfSize($expectedCount), $message);
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
    public static function assertEquals($expected, $actual, $message = '', $delta = 0.0)
    {
        assert($actual, equals($expected, $delta), $message);
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
    public static function assertNotEquals($expected, $actual, $message = '', $delta = 0.0)
    {
        assert($actual, isNotEqualTo($expected, $delta), $message);
    }

    /**
     * Asserts that a variable is empty.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertEmpty($actual, $message = '')
    {
        assertEmpty($actual, $message);
    }

    /**
     * Asserts that a variable is not empty.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotEmpty($actual, $message = '')
    {
        assertNotEmpty($actual, $message);
    }

    /**
     * Asserts that a value is greater than another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertGreaterThan($expected, $actual, $message = '')
    {
        assert($actual, isGreaterThan($expected), $message);
    }

    /**
     * Asserts that a value is greater than or equal to another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertGreaterThanOrEqual($expected, $actual, $message = '')
    {
        assert($actual, isGreaterThanOrEqualTo($expected), $message);
    }

    /**
     * Asserts that a value is smaller than another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertLessThan($expected, $actual, $message = '')
    {
        assert($actual, isLessThan($expected), $message);
    }

    /**
     * Asserts that a value is smaller than or equal to another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertLessThanOrEqual($expected, $actual, $message = '')
    {
        assert($actual, isLessThanOrEqualTo($expected), $message);
    }

    /**
     * Asserts that a file exists.
     *
     * @param string $filename
     * @param string $message
     */
    public static function assertFileExists($filename, $message = '')
    {
        assert($filename, isExistingFile()->or(isExistingDirectory()), $message);
    }

    /**
     * Asserts that a file does not exist.
     *
     * @param string $filename
     * @param string $message
     */
    public static function assertFileNotExists($filename, $message = '')
    {
        assert($filename, isNonExistingFile()->and(isNonExistingDirectory()), $message);
    }

    /**
     * Asserts that a condition is true.
     *
     * @param bool   $condition
     * @param string $message
     */
    public static function assertTrue($condition, $message = '')
    {
        assertTrue($condition, $message);
    }

    /**
     * Asserts that a condition is false.
     *
     * @param bool   $condition
     * @param string $message
     */
    public static function assertFalse($condition, $message = '')
    {
        assertFalse($condition, $message);
    }

    /**
     * Asserts that a variable is not null.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotNull($actual, $message = '')
    {
        assertNotNull($actual, $message);
    }

    /**
     * Asserts that a variable is null.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNull($actual, $message = '')
    {
        assertNull($actual, $message);
    }

    /**
     * Asserts that a variable is finite.
     *
     * @param  mixed   $actual
     * @param  string  $message
     * @since  1.1.0
     */
    public static function assertFinite($actual, $message = '')
    {
        assert($actual, 'is_finite', $message);
    }

    /**
     * Asserts that a variable is infinite.
     *
     * @param  mixed   $actual
     * @param  string  $message
     * @since  1.1.0
     */
    public static function assertInfinite($actual, $message = '')
    {
        assert($actual, 'is_infinite', $message);
    }

    /**
     * Asserts that a variable is nan.
     *
     * @param  mixed   $actual
     * @param  string  $message
     * @since  1.1.0
     */
    public static function assertNan($actual, $message = '')
    {
        assert($actual, 'is_nan', $message);
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
    public static function assertSame($expected, $actual, $message = '')
    {
        assert($actual, isSameAs($expected), $message);
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
    public static function assertNotSame($expected, $actual, $message = '')
    {
        assert($actual, isNotSameAs($expected), $message);
    }

    /**
     * Asserts that a variable is of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertInstanceOf($expected, $actual, $message = '')
    {
        assert($actual, isInstanceOf($expected), $message);
    }

    /**
     * Asserts that a variable is not of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotInstanceOf($expected, $actual, $message = '')
    {
        assert($actual, isNotInstanceOf($expected), $message);
    }

    /**
     * Asserts that a variable is of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertInternalType($expected, $actual, $message = '')
    {
        assert($actual, isOfType($expected), $message);
    }

    /**
     * Asserts that a variable is not of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotInternalType($expected, $actual, $message = '')
    {
        assert($actual, isNotOfType($expected), $message);
    }

    /**
     * Asserts that a string matches a given regular expression.
     *
     * @param string $pattern
     * @param string $string
     * @param string $message
     */
    public static function assertRegExp($pattern, $string, $message = '')
    {
        assert($string, matches($pattern), $message);
    }

    /**
     * Asserts that a string does not match a given regular expression.
     *
     * @param string $pattern
     * @param string $string
     * @param string $message
     */
    public static function assertNotRegExp($pattern, $string, $message = '')
    {
        assert($string, doesNotMatch($pattern), $message);
    }

    /**
     * Asserts that a string starts with a given prefix.
     *
     * @param  string  $prefix
     * @param  string  $string
     * @param  string  $message
     * @since  1.1.0
     */
    public static function assertStringStartsWith($prefix, $string, $message = '')
    {
        assert($string, startsWith($prefix), $message);
    }

    /**
     * Asserts that a string starts not with a given prefix.
     *
     * @param  string  $prefix
     * @param  string  $string
     * @param  string  $message
     * @since  1.1.0
     */
    public static function assertStringStartsNotWith($prefix, $string, $message = '')
    {
        assert($string, doesNotStartWith($prefix), $message);
    }

    /**
     * Asserts that a string ends with a given suffix.
     *
     * @param  string  $suffix
     * @param  string  $string
     * @param  string  $message
     * @since  1.1.0
     */
    public static function assertStringEndsWith($suffix, $string, $message = '')
    {
        assert($string, endsWith($suffix), $message);
    }

    /**
     * Asserts that a string ends not with a given suffix.
     *
     * @param  string  $suffix
     * @param  string  $string
     * @param  string  $message
     * @since  1.1.0
     */
    public static function assertStringEndsNotWith($suffix, $string, $message = '')
    {
        assert($string, doesNotEndWith($suffix), $message);
    }

    private static function isNativeType($type) : bool
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
                'scalar'
            ],
            true
        );
    }
}
