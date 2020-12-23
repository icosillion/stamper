<?php


namespace Icosillion\Stamper;


use Arrayy\Arrayy;
use Gt\Dom\Element;
use Gt\Dom\HTMLCollection;
use Gt\Dom\HTMLDocument;
use Spatie\Regex\Regex;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Stringy\create as s;

class Stamper
{
    private ExpressionLanguage $expressionLanguage;
    private array $componentRegistry = [];

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function registerComponent(string $name, string $path) {
        $this->componentRegistry[$name] = $path;
    }

    public function render(string $path, $context = []) {
        $document = new HTMLDocument(file_get_contents($path));

        $template = $document->getElementsByTagName('template')[0];
        $style = $document->getElementsByTagName('style')[0];

        $output = $this->walkNode($template, $this->wrapContext($context), $document);

        return [
            'html' => $output->innerHTML,
            'node' => $output->children[0],
            'style' => $style->value
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

    private function walkNode(Element $node, array $context, HTMLDocument $doc) {
        // Check if we're trying to render a custom component
        if (array_key_exists($node->tagName, $this->componentRegistry)) {
            // TODO Support if / for / interpolation

            // Get Props
            $props = [];
            foreach ($node->attributes as $attribute) {
                if (s($attribute->name)->startsWith('data-')) {
                    $props[$attribute->name] = $attribute->value; // TODO evaluate value
                }
            }

            // Get Children
            $children = $node->children;

            // Load Component
            $output = $this->render($this->componentRegistry[$node->tagName], [
                'props' => $props,
                'children' => $children
            ]);

            return (new Adopter())->adopt($doc, $output['node']);
        }

        // Handle if constructs
        $ifAttribute = $node->getAttribute('s-if');
        if ($ifAttribute) {
            if ($this->expressionLanguage->evaluate($ifAttribute, $context)) {
                $node->removeAttribute('s-if');
                return $node;
            }

            return null;
        }

        // Handle for constructs
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

        // Handle Text content
        if (count($node->children) === 0) {
            $text = $node->textContent;
            if ($text !== '') {
                $match = Regex::matchAll('/{{(.*)}}/', $text);
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

        // Handle children
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
}
