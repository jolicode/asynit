<?php

declare(strict_types=1);

namespace Asynit\Rector;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Turns a class marked with asynit's #[TestCase] into a PHPUnit test case: the attribute is what asynit used
 * to discover tests, PHPUnit discovers them by the base class instead.
 *
 * The assertion traits go away with it, since PHPUnit's TestCase already carries the assertions.
 */
final class AsynitTestCaseToPHPUnitRector extends AbstractRector
{
    private const TEST_CASE_ATTRIBUTE = 'Asynit\Attribute\TestCase';

    private const OBSOLETE_TRAITS = [
        'Asynit\Assert\AssertCaseTrait',
        'Asynit\Assert\AssertWebCaseTrait',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace asynit\'s #[TestCase] attribute with extending PHPUnit\'s TestCase',
            [
                new CodeSample(
                    <<<'PHP'
                        #[\Asynit\Attribute\TestCase]
                        class ApiTest
                        {
                            use \Asynit\Assert\AssertCaseTrait;
                        }
                        PHP,
                    <<<'PHP'
                        class ApiTest extends \PHPUnit\Framework\TestCase
                        {
                        }
                        PHP,
                ),
            ],
        );
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Class_
    {
        if (!$this->removeTestCaseAttribute($node)) {
            return null;
        }

        // A class that already extends something is left alone: silently rewriting its parent would be worse
        // than telling the user to look at it.
        if (null === $node->extends) {
            $node->extends = new FullyQualified('PHPUnit\Framework\TestCase');
        }

        foreach ($node->stmts as $key => $stmt) {
            if (!$stmt instanceof TraitUse) {
                continue;
            }

            $stmt->traits = array_values(array_filter(
                $stmt->traits,
                fn (Node\Name $trait): bool => !\in_array($trait->toString(), self::OBSOLETE_TRAITS, true),
            ));

            if ([] === $stmt->traits) {
                unset($node->stmts[$key]);
            }
        }

        $node->stmts = array_values($node->stmts);

        return $node;
    }

    private function removeTestCaseAttribute(Class_ $class): bool
    {
        $found = false;

        foreach ($class->attrGroups as $groupKey => $attrGroup) {
            foreach ($attrGroup->attrs as $attrKey => $attribute) {
                if (self::TEST_CASE_ATTRIBUTE !== $attribute->name->toString()) {
                    continue;
                }

                $found = true;
                unset($attrGroup->attrs[$attrKey]);
            }

            $attrGroup->attrs = array_values($attrGroup->attrs);

            if ([] === $attrGroup->attrs) {
                unset($class->attrGroups[$groupKey]);
            }
        }

        $class->attrGroups = array_values($class->attrGroups);

        return $found;
    }
}
