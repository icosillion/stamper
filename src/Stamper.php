<?php


namespace Icosillion\Stamper;


use Arrayy\Arrayy;
use Gt\Dom\Element;
use Gt\Dom\HTMLCollection;
use Gt\Dom\HTMLDocument;
use Spatie\Regex\Regex;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Stringy\create as s;

const BOOLEAN_ATTRIBUTES = [
    "allowfullscreen",
    "allowpaymentrequest",
    "async",
    "autofocus",
    "autoplay",
    "checked",
    "controls",
    "default",
    "disabled",
    "formnovalidate",
    "hidden",
    "ismap",
    "itemscope",
    "loop",
    "multiple",
    "muted",
    "nomodule",
    "novalidate",
    "open",
    "playsinline",
    "readonly",
    "required",
    "reversed",
    "selected",
    "truespeed"
];

class Stamper
{
    private ExpressionLanguage $expressionLanguage;
    private array $componentRegistry = [];
    private $lastResult;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->register('isset', function ($key) {
            return "(array_key_exists('$key', \$arguments))";
        }, function ($arguments, $key) {
            if (!is_string($key)) {
                return $key;
            }

            return array_key_exists($key, $arguments);
        });
        $countFn = ExpressionFunction::fromPhp('count');
        $this->expressionLanguage->register('count', $countFn->getCompiler(), $countFn->getEvaluator());
    }

    public function registerComponent(string $name, string $path) {
        $this->componentRegistry[$name] = $path;
    }

    public function render(string $path, $context = [], StyleBucket $styleBucket = null, GlobalsBucket $globalsBucket = null) {
        if ($styleBucket === null) {
            $styleBucket = new StyleBucket();
        }
        
        if ($globalsBucket === null) {
            $globalsBucket = new GlobalsBucket();
        }

        $document = new HTMLDocument(file_get_contents($path));

        $template = $document->getElementsByTagName('template')[0];
        $style = $document->getElementsByTagName('style');
        if (count($style) !== 0) {
            $styleBucket->setStyle($path, $style[0]->innerText);
        }

        $config = $document->getElementsByTagName('config');
        if (count($config) !== 0) {
            // TODO: Don't use eval?
            $configData = eval("return " . $config[0]->innerText . ";");

            if (array_key_exists('globals', $configData)) {
                $globalsBucket->pushGlobals($configData['globals']);
            }
        }

        $output = $this->walkNode($template, new State($this->wrapContext($context), $document, $styleBucket));

        return [
            'html' => $output->innerHTML,
            'node' => $output->children[0],
            'stylesheet' => $styleBucket->buildStyleSheet(),
            'globals' => $globalsBucket->getGlobals()
        ];
    }

    private function wrapContext(array $context) {
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = new Arrayy($value);
            }
        }

        return $context;
    }

    private function walkNode(?Element $node, State $state) {
        // Check if we're trying to render a custom component
        $node = $this->apply($node, $state, [$this, 'handleCustomComponent']);

        // Handle if constructs
        $node = $this->apply($node, $state, [$this, 'handleIf']);

        // Handle else constructs
        $node = $this->apply($node, $state, [$this, 'handleElse']);

        // Handle for constructs
        $node = $this->apply($node, $state, [$this, 'handleFor']);

        // Handle boolean attrs
        $node = $this->apply($node, $state, [$this, 'handleBooleanAttrs']);

        // Interpolate Attrs
        $node = $this->apply($node, $state, [$this, 'handleInterpolateAttrs']);

        // Handle Text content
        $node = $this->apply($node, $state, [$this, 'handleTextContent']);

        // Handle children
        $node = $this->apply($node, $state, [$this, 'handleChildren']);

        return $node;
    }

    private function interpolateText(string $text, array $context): string {
        $match = Regex::matchAll('/{{(.*)}}/', $text);
        if ($match->hasMatch()) {
            foreach ($match->results() as $result) {
                $expression = $result->group(1);

                $replacement = $this->expressionLanguage->evaluate($expression, $context);
                $text = (string) s($text)->replace($result->result(), $replacement);
            }
        }

        return $text;
    }

    private function apply($input, State $state, callable $function) {
        if ($input === null) {
            return null;
        }

        if (is_array($input)) {
            return array_map(function ($item) use ($function, $state) {
                return $function($item, $state);
            }, $input);
        }

        return $function($input, $state);
    }

    private function handleBooleanAttrs(Element $node, State $state) {
        foreach ($node->attributes as $attr) {
            if (in_array($attr->name, BOOLEAN_ATTRIBUTES)) {
                if ($attr->value === 'false') {
                    $node->removeAttribute($attr->name);
                }
            }
        }

        return $node;
    }

    private function handleInterpolateAttrs(Element $node, State $state) {
        foreach ($node->attributes as $attr) {
            if (!s($attr->name)->startsWith('s-')) {
                $attr->value = $this->interpolateText($attr->value, $state->context);
            }
        }

        return $node;
    }

    private function handleIf(Element $node, State $state) {
        $ifAttribute = $node->getAttribute('s-if');
        if ($ifAttribute) {
            $this->lastResult = $this->expressionLanguage->evaluate($ifAttribute, $state->context);
            if ($this->lastResult) {
                $node->removeAttribute('s-if');
                return $node;
            }

            return null;
        }

        return $node;
    }

    private function handleElse(Element $node, State $state) {
        $elseAttribute = $node->hasAttribute('s-else');
        if ($elseAttribute) {
            $node->removeAttribute('s-else');
            return $this->lastResult ? null : $node;
        }

        return $node;
    }

    private function handleFor(Element $node, State $state) {
        $forAttribute = $node->getAttribute('s-for');
        if ($forAttribute) {
            $match = Regex::matchAll('/(.*)\bas\b(.*)$/', $forAttribute);
            if (!$match) {
                // TODO error
                return $node;
            }

            $result = $match->results()[0];

            $variable = trim($result->group(2));
            $expression = trim($result->group(1));

            $collection = $this->expressionLanguage->evaluate($expression, $state->context);
            $outputNodes = [];
            $templateNode = $node->cloneNode(true);
            $templateNode->removeAttribute('s-for');
            foreach ($collection as $item) {
                $outputNodes[] = $this->walkNode(
                    $templateNode->cloneNode(true),
                    $state->withAdditionalContext([$variable => $item])
                );
            }

            return $outputNodes;
        }

        return $node;
    }

    private function handleTextContent(Element $node, State $state) {
        if (count($node->children) === 0) {
            $text = $node->textContent;
            if ($text !== '') {
                $match = Regex::matchAll('/{{(.*?)}}/', $text);
                if ($match->hasMatch()) {
                    foreach ($match->results() as $result) {
                        $expression = $result->group(1);

                        $replacement = $this->expressionLanguage->evaluate($expression, $state->context);
                        if ($replacement instanceof HTMLCollection) {
                            $node->textContent = '';
                            foreach ($replacement as $child) {
                                $childClone = (new Adopter())->adopt($state->doc, $child);
                                $node->appendChild($childClone);
                            }
                        } else {
                            $text = (string) s($text)->replace($result->result(), $replacement);
                        }
                    }
                }

                $node->textContent = $text;
            }
        }

        return $node;
    }

    private function handleChildren(Element $node, State $state) {
        if ($node->hasChildNodes()) {
            foreach ($node->children as $child) {
                $newChild = $child->cloneNode(true);
                $newChild = $this->walkNode($newChild, $state);

                if ($newChild === null) {
                    $node->removeChild($child);
                } else if (is_array($newChild)) {
                    foreach ($newChild as $subChild) {
                        $node->insertBefore($subChild, $child);
                    }

                    $node->removeChild($child);
                } else {
                    $node->replaceChild($newChild, $child);
                }
            }
        }

        return $node;
    }

    private function handleCustomComponent(Element $node, State $state) {
        if (array_key_exists($node->tagName, $this->componentRegistry)) {
            // Handle for
            $node = $this->apply($node, $state, [$this, 'handleFor']);

            // Handle boolean attrs
            $node = $this->apply($node, $state, [$this, 'handleBooleanAttrs']);

            // Interpolate non-data Attrs
            $node = $this->apply($node, $state, [$this, 'handleInterpolateAttrs']);

            // Handle If
            $node = $this->apply($node, $state, [$this, 'handleIf']);

            // Handle Else
            $node = $this->apply($node, $state, [$this, 'handleElse']);

            return $this->apply($node, $state, function ($node, $state) {
                // Get Props
                $props = [];
                foreach ($node->attributes as $attribute) {
                    if (s($attribute->name)->startsWith('data-s-')) {
                        $props[(string) s($attribute->name)->removeLeft('data-s-')] = $this->expressionLanguage->evaluate($attribute->value, $state->context);
                    } else {
                        $props[(string) s($attribute->name)->removeLeft('data-')] = $attribute->value;
                    }
                }

                // Get Children
                $children = count($node->children) === 0 ? $node->textContent : $node->children;

                // Load Component
                if (array_key_exists($node->tagName, $this->componentRegistry)) {
                    $output = $this->render($this->componentRegistry[$node->tagName], [
                        'props' => $props,
                        'children' => $children
                    ], $state->styleBucket);

                    return (new Adopter())->adopt($state->doc, $output['node']);
                }

                return $node;
            });
        }

        return $node;
    }
}
