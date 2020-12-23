<?php

namespace Icosillion\Stamper\Tokenizer;

class Tokenizer
{
    private $operators = ['+', '-', '*', '/', '.', '<', '>', '!', '='];

    public function tokenize(string $source)
    {
        $cursor = new Cursor($source);
        $tokens = [];
        $state = new StateManager();
        $buffer = new StringBuffer();

        while ($cursor->isInBounds()) {
            if ($this->isWhitespace($cursor->current())) {
                $this->commitLiteral($state, $buffer, $tokens);
            } else if ($state->is('escape')) {
                $buffer->push($this->getEscapedValue($cursor->current()));
                $state->pop();
            } else if ($cursor->current() === '\'') {
                $this->commitLiteral($state, $buffer, $tokens);
                if ($state->is('string')) {
                    $state->pop();
                    $tokens[] = new Token('string', $buffer->getAndClear());
                } else {
                    $state->push('string');
                }
            } else if (\in_array($cursor->current(), $this->operators)) {
                $this->commitLiteral($state, $buffer, $tokens);
                $tokens[] = new Token('OP', $cursor->current());
            } else if ($cursor->current() === '\\') {
                $state->push('escape');
            } else {
                $buffer->push($cursor->current());
            }

            $cursor->next();
        }

        if (!$buffer->isEmpty()) {
            $this->commitLiteral($state, $buffer, $tokens);
        }

        return $tokens;
    }

    private function commitLiteral(StateManager $manager, StringBuffer $buffer, array &$tokens)
    {
        if (!$buffer->isEmpty()) {
            if (!$manager->is('base')) {
                throw new \RuntimeException('Invalid state');
            }

            $tokens[] = new Token('LITERAL', $buffer->getAndClear());
        }
    }

    private function getEscapedValue(string $character): string
    {
        switch ($character) {
            case 'n':
                return "\n";
            case 't':
                return "\t";
            case '\\':
                return '\\';
            case '"':
                return '"';
            default:
                throw new \RuntimeException("Unknown escape sequence \\$character");
        }
    }

    private function isWhitespace(string $character): bool
    {
        return $character === ' ' || $character === "\t" || $character === "\n" || $character === "\r";
    }
}