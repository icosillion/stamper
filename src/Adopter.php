<?php


namespace Icosillion\Stamper;


use Gt\Dom\Document;
use Gt\Dom\Element;

class Adopter
{
    /**
     * @param Document $doc
     * @param Element $foreignElement
     * @return Element
     *
     * Basic adoption to a document. Super WIP
     */
    public function adopt(Document $doc, Element $foreignElement) {
        $element = $doc->createElement($foreignElement->tagName);
        // Apply attrs
        foreach ($foreignElement->attributes as $attribute) {
            $element->setAttribute($attribute->name, $attribute->value);
        }

        // Do the same for children
        if (count($foreignElement->children) !== 0) {
            foreach ($foreignElement->children as $child) {
                $element->appendChild($this->adopt($doc, $child));
            }
        } else {
            $element->textContent = $foreignElement->textContent;
        }

        return $element;
    }
}
