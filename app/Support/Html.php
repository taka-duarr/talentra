<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Minimal allow-list sanitizer for admin-authored material and question HTML.
 */
class Html
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'ul', 'ol', 'li', 'h2', 'h3', 'h4',
        'blockquote', 'code', 'pre', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'img', 'a', 'span', 'hr',
    ];

    private const ALLOWED_ATTRIBUTES = ['href', 'src', 'alt', 'title', 'colspan', 'rowspan', 'width', 'height'];

    public static function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);

        foreach (iterator_to_array($xpath->query('//*')) as $node) {
            /** @var DOMElement $node */
            if ($node->getAttribute('id') === 'root' && $node->parentNode === $document) {
                continue;
            }

            if (! in_array(strtolower($node->nodeName), self::ALLOWED_TAGS, true)) {
                // Drop dangerous elements entirely, unwrap everything else.
                if (in_array(strtolower($node->nodeName), ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button'], true)) {
                    $node->parentNode?->removeChild($node);

                    continue;
                }

                while ($node->firstChild) {
                    $node->parentNode?->insertBefore($node->firstChild, $node);
                }
                $node->parentNode?->removeChild($node);

                continue;
            }

            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->nodeName);
                $value = trim($attribute->nodeValue ?? '');

                $isUnsafeUrl = in_array($name, ['href', 'src'], true)
                    && ! preg_match('~^(https?://|/|#|data:image/(png|jpe?g|gif|webp);base64,)~i', $value);

                if (! in_array($name, self::ALLOWED_ATTRIBUTES, true) || $isUnsafeUrl) {
                    $node->removeAttribute($attribute->nodeName);
                }
            }

            if (strtolower($node->nodeName) === 'a') {
                $node->setAttribute('rel', 'noopener nofollow');
                $node->setAttribute('target', '_blank');
            }
        }

        $root = $document->getElementById('root');
        $output = '';
        foreach ($root?->childNodes ?? [] as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }
}
