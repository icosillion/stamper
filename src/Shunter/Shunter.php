<?php


namespace Icosillion\Stamper\Shunter;


use Icosillion\Stamper\Tokenizer\Token;

class Shunter
{
    /**
     * @param Token[] $tokens
     * @return Token[]
     */
    public function shunt(array $tokens) {
        $tstack = [];
        $opstack = [];

        foreach ($tokens as $token) {
            if ($token->getType() === 'OP') {
                if ($token->getValue() === ')') {
                    for (;;) {
                        /** @var Token $item */
                        $item = array_pop($opstack);

                        if ($item->getValue() === ')') {
                            break;
                        }

                        $tstack[] = $item;
                    }
                } else {
                    $opstack[] = $token;
                }
            } else {
                $tstack[] = $token;
            }
        }

        return array_merge($tstack, $opstack);
    }
}