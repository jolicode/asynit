<?php

declare(strict_types=1);

namespace Asynit\Rector;

use PhpParser\Node;
use PhpParser\Token;
use Rector\NodeTypeResolver\Node\AttributeKey;

/**
 * php-parser cannot preserve formatting when a list loses its last element (see the "Support removing single
 * node" TODO in PrettyPrinterAbstract::pArray()): it prints the node owning the list from scratch instead, and
 * the blank lines between its statements, which the AST does not carry, are lost with it.
 *
 * So the list is emptied on the original node as well, for the printer to see nothing changed there, and the
 * removed code is blanked out of the original tokens the printer copies from.
 *
 * @internal
 */
final class EmptyList
{
    /**
     * @param Token[] $tokens
     */
    public static function clear(Node $node, string $subNodeName, array $tokens): void
    {
        $original = $node->getAttribute(AttributeKey::ORIGINAL_NODE);

        if (!$original instanceof Node) {
            $node->{$subNodeName} = [];

            return;
        }

        $removed = $node->{$subNodeName};

        if (!\is_array($removed) || !($first = reset($removed)) instanceof Node || !($last = end($removed)) instanceof Node) {
            throw new \LogicException(\sprintf('%s::$%s is not a list of nodes.', $node::class, $subNodeName));
        }

        $start = $first->getStartTokenPos();
        $end = $last->getEndTokenPos();

        while (isset($tokens[$end + 1]) && ($tokens[$end + 1]->is(\T_WHITESPACE) || $tokens[$end + 1]->is(','))) {
            ++$end;
        }

        // Inside parentheses the list goes down to nothing, so the line break opening it has to go too.
        $opening = $start - 1;

        while ($opening > 0 && $tokens[$opening]->is(\T_WHITESPACE)) {
            --$opening;
        }

        if ($tokens[$opening]->is('(')) {
            $start = $opening + 1;
        }

        for ($position = $start; $position <= $end; ++$position) {
            $tokens[$position]->text = '';
        }

        $node->{$subNodeName} = [];
        $original->{$subNodeName} = [];
    }
}
