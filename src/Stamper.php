<?php


namespace Icosillion\Stamper;


use Arrayy\Arrayy;
use Gt\Dom\Document;
use Gt\Dom\Element;
use Gt\Dom\HTMLCollection;
use Gt\Dom\HTMLDocument;
use Spatie\Regex\Regex;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Stringy\create as s;

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

    public function render(string $path, $context = [], StyleBucket $styleBucket = null) {
        if ($styleBucket === null) {
            $styleBucket = new StyleBucket();
        }

        $document = new HTMLDocument(file_get_contents($path));

        $template = $document->getElementsByTagName('template')[0];
        $style = $document->getElementsByTagName('style');
        if (count($style) !== 0) {
            $styleBucket->setStyle($path, $style[0]->innerText);
        }

        $output = $this->walkNode($template, $this->wrapContext($context), $document);

        return [
            'html' => $output->innerHTML,
            'node' => $output->children[0],
            'stylesheet' => $styleBucket->buildStyleSheet()
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

    private function walkNode(?Element $node, array $context, HTMLDocument $doc) {

        // Check if we're trying to render a custom component
        $node = $this->apply($node, $context, $doc, [$this, 'handleCustomComponent']);

        // Handle if constructs
        $node = $this->apply($node, $context, $doc, [$this, 'handleIf']);

        // Handle else constructs
        $node = $this->apply($node, $context, $doc, [$this, 'handleElse']);

        // Handle for constructs
        $node = $this->apply($node, $context, $doc, [$this, 'handleFor']);

        // Interpolate Attrs
        $node = $this->apply($node, $context, $doc, [$this, 'handleInterpolateAttrs']);

        // Handle Text content
        $node = $this->apply($node, $context, $doc, [$this, 'handleTextContent']);

        // Handle children
        $node = $this->apply($node, $context, $doc, [$this, 'handleChildren']);

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

    private function apply($input, array $context, Document $doc, callable $function) {
        if ($input === null) {
            return null;
        }

        if (is_array($input)) {
            return array_map(function ($item) use ($function, $context, $doc) {
                return $function($item, $context, $doc);
            }, $input);
        }

        return $function($input, $context, $doc);
    }

    private function handleInterpolateAttrs(Element $node, array $context, Document $doc) {
        foreach ($node->attributes as $attr) {
            if (!s($attr->name)->startsWith('s-')) {
                $attr->value = $this->interpolateText($attr->value, $context);
            }
        }

        return $node;
    }

    private function handleIf(Element $node, array $context, Document $doc) {
        $ifAttribute = $node->getAttribute('s-if');
        if ($ifAttribute) {
            $this->lastResult = $this->expressionLanguage->evaluate($ifAttribute, $context);
            if ($this->lastResult) {
                $node->removeAttribute('s-if');
                return $node;
            }

            return null;
        }

        return $node;
    }

    private function handleElse(Element $node, array $context, Document $doc) {
        $elseAttribute = $node->hasAttribute('s-else');
        if ($elseAttribute) {
            $node->removeAttribute('s-else');
            return $this->lastResult ? null : $node;
        }

        return $node;
    }

    private function handleFor(Element $node, array $context, Document $doc) {
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

            $collection = $this->expressionLanguage->evaluate($expression, $context);
            $outputNodes = [];
            $templateNode = $node->cloneNode(true);
            $templateNode->removeAttribute('s-for');
            foreach ($collection as $item) {
                $outputNodes[] = $this->walkNode($templateNode->cloneNode(true), array_merge($context, [$variable => $item]), $doc);
            }

            return $outputNodes;
        }

        return $node;
    }

    // TODO Fix this
    private function handleTextContent(Element $node, array $context, Document $doc) {
        if (count($node->children) === 0) {
            $text = $node->textContent;
            if ($text !== '') {
                $match = Regex::matchAll('/{{(.*?)}}/', $text);
                if ($match->hasMatch()) {
                    foreach ($match->results() as $result) {
                        $expression = $result->group(1);

                        $replacement = $this->expressionLanguage->evaluate($expression, $context);
                        if ($replacement instanceof HTMLCollection) {
                            $node->textContent = '';
                            foreach ($replacement as $child) {
                                $childClone = (new Adopter())->adopt($doc, $child);
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

    private function handleChildren(Element $node, array $context, Document $doc) {
        if ($node->hasChildNodes()) {
            foreach ($node->children as $child) {
                $newChild = $child->cloneNode(true);
                $newChild = $this->walkNode($newChild, $context, $doc);

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

    private function handleCustomComponent(Element $node, array $context, Document $doc) {
        if (array_key_exists($node->tagName, $this->componentRegistry)) {
            // TODO Support if / for / interpolation

            // Interpolate Attrs
            $this->handleInterpolateAttrs($node, $context, $doc);

            // Get Props
            $props = [];
            foreach ($node->attributes as $attribute) {
                if (s($attribute->name)->startsWith('data-')) {
                    $props[(string) s($attribute->name)->removeLeft('data-')] = $attribute->value; // TODO evaluate value
                }
            }

            // Get Children
            $children = count($node->children) === 0 ? $node->textContent : $node->children;

            // Load Component
            $output = $this->render($this->componentRegistry[$node->tagName], [
                'props' => $props,
                'children' => $children
            ]);

            return (new Adopter())->adopt($doc, $output['node']);
        }

        return $node;
    }
}
