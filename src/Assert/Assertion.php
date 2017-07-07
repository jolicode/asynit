<?php

declare(strict_types=1);

namespace Asynit\Assert;

use Asynit\Test;
use bovigo\assert\Assertion as BaseAssertion;
use bovigo\assert\predicate\Predicate;
use SebastianBergmann\Exporter\Exporter;

class Assertion extends BaseAssertion
{
    /**
     * value to do the assertion on
     *
     * @type  mixed
     */
    private $value;

    /**
     * @type  \SebastianBergmann\Exporter\Exporter
     */
    private $exporter;

    /**
     * constructor
     *
     * @param  mixed                                 $value
     * @param  \SebastianBergmann\Exporter\Exporter  $exporter
     */
    public function __construct($value, Exporter $exporter)
    {
        parent::__construct($value, $exporter);

        $this->value    = $value;
        $this->exporter = $exporter;
    }

    /**
     * @var Test
     */
    static public $currentTest;

    /**
     * @param Predicate   $predicate
     * @param string|null $description
     *
     * @return bool
     */
    public function evaluate(Predicate $predicate, string $description = null): bool
    {
        parent::evaluate($predicate, $description);

        static::$currentTest->addAssertion($this->describeSuccess($predicate, $description));

        return true;
    }

    /**
     * creates failure description when value failed the test with given predicate
     *
     * @param   \bovigo\assert\predicate\Predicate  $predicate    predicate that failed
     * @param   string                              $description  additional description for failure message
     * @return  string
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
            strpos($predicateText, "\n") !== false ? '' : '.'
        );

        return $description;
    }
}
