<?php

declare(strict_types=1);

namespace Asynit\Rector;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * #[OnCreate] was asynit's own "run this once the test case exists" hook, and the runner handed it the HTTP
 * client configuration. PHPUnit's #[Before] takes its place, and the configuration now comes from the
 * environment, so the parameter goes away.
 */
final class OnCreateToBeforeRector extends AbstractRector
{
    private const ON_CREATE_ATTRIBUTE = 'Asynit\Attribute\OnCreate';

    private const CONFIGURATION_TYPE = 'Asynit\Attribute\HttpClientConfiguration';

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace asynit\'s #[OnCreate] hook with PHPUnit\'s #[Before], dropping the injected configuration',
            [
                new CodeSample(
                    <<<'PHP'
                        #[\Asynit\Attribute\OnCreate]
                        public function setUpClient(\Asynit\Attribute\HttpClientConfiguration $configuration): void
                        {
                        }
                        PHP,
                    <<<'PHP'
                        #[\PHPUnit\Framework\Attributes\Before]
                        public function setUpClient(): void
                        {
                        }
                        PHP,
                ),
            ],
        );
    }

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?ClassMethod
    {
        if (!$this->replaceAttribute($node)) {
            return null;
        }

        $node->params = array_values(array_filter(
            $node->params,
            fn (Node\Param $param): bool => !$this->isConfigurationParam($param),
        ));

        return $node;
    }

    private function replaceAttribute(ClassMethod $classMethod): bool
    {
        $found = false;

        foreach ($classMethod->attrGroups as $groupKey => $attrGroup) {
            foreach ($attrGroup->attrs as $attrKey => $attribute) {
                if (self::ON_CREATE_ATTRIBUTE !== $attribute->name->toString()) {
                    continue;
                }

                $found = true;
                unset($attrGroup->attrs[$attrKey]);
            }

            $attrGroup->attrs = array_values($attrGroup->attrs);

            if ([] === $attrGroup->attrs) {
                unset($classMethod->attrGroups[$groupKey]);
            }
        }

        if (!$found) {
            return false;
        }

        $classMethod->attrGroups = array_values($classMethod->attrGroups);
        $classMethod->attrGroups[] = new AttributeGroup([
            new Attribute(new FullyQualified('PHPUnit\Framework\Attributes\Before')),
        ]);

        return true;
    }

    private function isConfigurationParam(Node\Param $param): bool
    {
        $type = $param->type;

        if ($type instanceof Node\NullableType) {
            $type = $type->type;
        }

        return $type instanceof Node\Name && self::CONFIGURATION_TYPE === $type->toString();
    }
}
