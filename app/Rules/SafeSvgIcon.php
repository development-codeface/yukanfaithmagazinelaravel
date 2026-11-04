<?php

namespace App\Rules;

use Closure;
use DOMDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SafeSvgIcon implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid() || $value->getMimeType() !== 'image/svg+xml') {
            return;
        }

        if (! $this->isSafe(file_get_contents($value->getRealPath()))) {
            $fail('The icon must be a static SVG without scripts, external resources or embedded HTML.');
        }
    }

    private function isSafe(string $xml): bool
    {
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
            return false;
        }
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadXML($xml, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (! $loaded || $document->doctype || $document->documentElement?->localName !== 'svg') {
            return false;
        }
        if ((new \DOMXPath($document))->query('//processing-instruction()')->length > 0) {
            return false;
        }
        $allowed = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'defs', 'use', 'symbol', 'title', 'desc', 'style', 'linearGradient', 'radialGradient', 'stop', 'clipPath', 'mask', 'pattern', 'text', 'tspan'];
        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element->namespaceURI !== 'http://www.w3.org/2000/svg' || ! in_array($element->localName, $allowed, true)) {
                return false;
            }
            if ($element->localName === 'style' && ! $this->safeValue($element->textContent)) {
                return false;
            }
            foreach ($element->attributes as $attribute) {
                if (str_starts_with(strtolower($attribute->localName), 'on')
                    || ($attribute->namespaceURI === 'http://www.w3.org/XML/1998/namespace' && ! in_array($attribute->localName, ['space', 'lang'], true))) {
                    return false;
                }
                if (in_array(strtolower($attribute->localName), ['href', 'src'], true) && ! str_starts_with(trim($attribute->value), '#')) {
                    return false;
                }
                if (! $this->safeValue($attribute->value)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function safeValue(string $value): bool
    {
        // Only local fragment references such as url(#gradient) are needed for static icons.
        $value = preg_replace('~/\*.*?\*/~s', '', $value);
        $value = preg_replace('/url\(\s*[\'"]?#[a-zA-Z0-9_.:-]+[\'"]?\s*\)/i', '', $value);

        return ! preg_match('/url\s*\(|@|\\\\|expression\s*\(|javascript\s*:|behavior\s*:|-moz-binding/i', $value);
    }
}
