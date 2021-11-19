<?php

declare(strict_types=1);

namespace Asynit\Assert;

use Asynit\Test;
use bovigo\assert\Assertion as BaseAssertion;
use bovigo\assert\AssertionFailure;
use bovigo\assert\predicate\Predicate;
use SebastianBergmann\Exporter\Exporter;

class Assertion extends BaseAssertion
{
    /**
     * value to do the assertion on.
     *
     * @var mixed
     */
    private $value;

    /**
     * @var \SebastianBergmann\Exporter\Exporter
     */
    private Exporter $exporter;

    /**
     * @var Test
     */
    private Test $test;

    /**
     * constructor.
     *
     * @param mixed $value
     */
    public function __construct($value, Exporter $exporter, Test $test)
    {
        parent::__construct($value, $exporter);

        $this->value = $value;
        $this->exporter = $exporter;
        $this->test = $test;
    }

    /** @var Test */
    public static Test $currentTest;

    public function evaluate(Predicate $predicate, string $description = null): bool
    {
        try {
            $result = parent::evaluate($predicate, $description);
        } catch (AssertionFailure $e) {
            $message = $e->getMessage();

            foreach ($e->getTrace() as $call) {
                if (isset($call['file']) && false === strpos($call['file'], 'vendor')) {
                    break;
                }
            }

            $file = ltrim(str_replace(getcwd(), '', $call['file']), '/');

            $message .= sprintf(' in %s:%d', $file, $call['line']);

            throw new AssertionFailure($message);
        }

        $this->test->addAssertion($this->describeSuccess($predicate, $description));

        return $result;
    }

    /**
     * creates failure description when value failed the test with given predicate.
     *
     * @param \bovigo\assert\predicate\Predicate $predicate   predicate that failed
     * @param string                             $description additional description for failure message
     */
    private function describeSuccess(Predicate $predicate, string $description = null): string
    {
        if ($description) {
            return $description;
        }

        $predicateText = (string) $predicate;
        $description = sprintf(
            'Asserting that %s %s%s',
            $predicate->describeValue($this->exporter, $this->value),
            $predicateText,
            false !== strpos($predicateText, "\n") ? '' : '.'
        );

        return $description;
    }
}
