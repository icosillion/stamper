<?php


namespace Icosillion\Stamper;


use Icosillion\Stamper\Shunter\Shunter;
use Icosillion\Stamper\Tokenizer\Tokenizer;

class Interpo
{
    public function execute($code, Context $context) {
        // Tokenize
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize($code);

        // Shunt
        $shuntedTokens = (new Shunter())->shunt($tokens);

        // Evaluate
        $workingStack = [];
        for (;;) {
            if (count($shuntedTokens) === 0) {
                break;
            }

            $token = array_pop($shuntedTokens);
            if ($token->getType() === 'OP') {

            } else {
            }
        }

        return $shuntedTokens;
    }
}
