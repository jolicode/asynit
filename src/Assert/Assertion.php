<?php

declare(strict_types=1);

namespace Asynit\Assert;

use Asynit\Runner\TestStorage;
use Asynit\Test;
use bovigo\assert\Assertion as BaseAssertion;
use bovigo\assert\AssertionFailure;
use bovigo\assert\predicate\Predicate;
use SebastianBergmann\Exporter\Exporter;

class Assertion extends BaseAssertion
{
    private mixed $value;

    private Exporter $exporter;

    /**
     * constructor.
     */
    public function __construct($value, Exporter $exporter)
    {
        parent::__construct($value, $exporter);

        $this->value = $value;
        $this->exporter = $exporter;
    }

    public function evaluate(Predicate $predicate, ?string $description = null): bool
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

        TestStorage::get()?->addAssertion($this->describeSuccess($predicate, $description));

        return $result;
    }

    /**
     * creates failure description when value failed the test with given predicate.
     *
     * @param Predicate $predicate   predicate that failed
     * @param string    $description additional description for failure message
     */
    private function describeSuccess(Predicate $predicate, ?string $description = null): string
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
