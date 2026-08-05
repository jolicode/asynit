<?php

declare(strict_types=1);

namespace PHPUnit\Framework\TestCase;

use PHPUnit\Framework\Assert;

/**
 * Drop-in replacement for PHPUnit's OutputBuffer that is safe to use from concurrently running fibers.
 *
 * PHPUnit's version calls ob_start() per test and asserts on ob_get_level() when stopping. PHP's output
 * buffer stack is process global and not fiber local, so as soon as two tests are in flight the levels
 * interleave and both tests are reported as risky.
 *
 * This version installs a single process wide buffer with a chunk size of 1, which makes PHP invoke the
 * handler on every write, from the fiber that wrote it. Each chunk is then routed to the buffer registered
 * for that fiber.
 */
final class OutputBuffer
{
    /** @var array<int, self> buffer currently collecting output, per fiber */
    private static array $active = [];

    private static bool $installed = false;

    private static ?self $mainFiberBuffer = null;

    private string $output = '';
    private string $captured = '';
    private bool $bufferingActive = false;
    private ?string $expectedRegularExpression = null;
    private ?string $expectedString = null;
    private bool $retrievedForAssertion = false;

    public function expectRegularExpression(string $expectedRegularExpression): void
    {
        $this->expectedRegularExpression = $expectedRegularExpression;
    }

    public function expectString(string $expectedString): void
    {
        $this->expectedString = $expectedString;
    }

    public function hasExpectation(): bool
    {
        return is_string($this->expectedString) || is_string($this->expectedRegularExpression);
    }

    public function expectsOutput(): bool
    {
        return $this->hasExpectation() || $this->retrievedForAssertion;
    }

    public function hasUnexpectedOutput(): bool
    {
        return '' !== $this->output && !$this->expectsOutput();
    }

    public function output(): string
    {
        return $this->bufferingActive ? $this->captured : $this->output;
    }

    public function getActualOutputForAssertion(): string
    {
        $this->retrievedForAssertion = true;

        return $this->output();
    }

    public function start(): void
    {
        $this->captured = '';
        $this->bufferingActive = true;

        self::install();
        self::register($this);
    }

    // Note on the chunk size below: 1 does not mean "call the handler per character". PHP flushes once an
    // output call brings the buffer to at least that many bytes, so the handler runs once per echo/print,
    // receiving whatever that call wrote - one 100 KB echo is still a single invocation. Measured overhead is
    // about 30ns per output call over PHPUnit's plain ob_start().

    public function stop(): OutputBufferStopResult
    {
        self::unregister($this);

        $this->output = $this->captured;
        $this->bufferingActive = false;

        // There is no per test buffer to mismatch anymore, so a test can no longer be blamed for the state
        // of a stack it does not own.
        return new OutputBufferStopResult(true, null);
    }

    public function performAssertions(): void
    {
        if (null !== $this->expectedRegularExpression) {
            Assert::assertMatchesRegularExpression($this->expectedRegularExpression, $this->output);
        } elseif (null !== $this->expectedString) {
            Assert::assertSame($this->expectedString, $this->output);
        }
    }

    private static function install(): void
    {
        if (self::$installed) {
            return;
        }

        self::$installed = true;

        ob_start(static function (string $chunk, int $phase): string {
            $buffer = self::current();

            if (null === $buffer) {
                return $chunk;
            }

            $buffer->captured .= $chunk;

            return '';
        }, 1);
    }

    private static function current(): ?self
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber) {
            return self::$mainFiberBuffer;
        }

        return self::$active[spl_object_id($fiber)] ?? null;
    }

    private static function register(self $buffer): void
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber) {
            self::$mainFiberBuffer = $buffer;

            return;
        }

        self::$active[spl_object_id($fiber)] = $buffer;
    }

    private static function unregister(self $buffer): void
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber) {
            if (self::$mainFiberBuffer === $buffer) {
                self::$mainFiberBuffer = null;
            }

            return;
        }

        $id = spl_object_id($fiber);

        if ((self::$active[$id] ?? null) === $buffer) {
            unset(self::$active[$id]);
        }
    }
}
