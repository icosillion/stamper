<?php


namespace Icosillion\Stamper;


use DOMNode;
use Gt\Dom\Document;
use Gt\Dom\Element;
use Gt\Dom\Text;

class Adopter
{
    /**
     * @param Document $doc
     * @param DOMNode $foreignElement
     * @return DOMNode
     *
     * Basic adoption to a document. Super WIP
     */
    public function adopt(Document $doc, DOMNode $foreignElement) {
        if ($foreignElement instanceof Element) {
            $element = $doc->createElement($foreignElement->tagName);
            // Apply attrs
            foreach ($foreignElement->attributes as $attribute) {
                $element->setAttribute($attribute->name, $attribute->value);
            }

            // Do the same for children
            foreach ($foreignElement->childNodes as $child) {
                $element->appendChild($this->adopt($doc, $child));
            }

            return $element;
        }

        if ($foreignElement instanceof Text) {
            return $doc->createTextNode($foreignElement->textContent);
        }

        // Catch all if we've missed a type
        return $foreignElement->cloneNode(true);
    }
}
