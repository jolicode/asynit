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
 * This version installs a single process wide buffer whose handler runs on every output call, from the fiber
 * that made it. Each piece of output is then routed to the buffer registered for that fiber.
 */
final class OutputBuffer
{
    /**
     * ob_start()'s $chunk_size, expressed by what it buys us rather than by its value.
     *
     * PHP flushes the buffer as soon as an output call brings it to at least $chunk_size bytes, so the
     * smallest possible value makes the handler run once per echo/print, receiving whatever that single call
     * wrote. It is not "once per character": one 100 KB echo is still one invocation. That is what lets us
     * attribute output to a fiber, since the handler runs in the fiber that produced it.
     */
    private const FLUSH_PER_OUTPUT_CALL = 1;

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

        ob_start(static function (string $output, int $phase): string {
            $buffer = self::current();

            // Output produced outside a test, by PHPUnit's own printer for instance, is passed through.
            if (null === $buffer) {
                return $output;
            }

            $buffer->captured .= $output;

            return '';
        }, self::FLUSH_PER_OUTPUT_CALL);
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
