<?php

namespace App\Support;

/**
 * Conservative HTML allowlist for policy/help CMS. Never render raw user HTML.
 * Scripts, SVG, embedded objects, images, inline styles and event attributes
 * are rejected/removed. External links are HTTPS only.
 */
final class SafeRichText
{
    private const TAGS = [
        'p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li',
        'strong', 'b', 'em', 'i', 'u', 'br',
        'a', 'blockquote',
    ];

    public static function clean(string $html): string
    {
        if (mb_strlen($html) > 60000) {
            throw new \InvalidArgumentException('Rich text is too long.');
        }
        if (! class_exists(\DOMDocument::class)) {
            throw new \RuntimeException('PHP DOM extension is required to sanitize rich text.');
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8"?><div id="safe-html-root">' . $html . '</div>',
                LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $root = $dom->getElementById('safe-html-root');
        if (! $root) {
            throw new \InvalidArgumentException('Invalid rich text input.');
        }
        self::sanitizeNode($root);
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        return $out;
    }

    private static function sanitizeNode(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment ||
                ($child instanceof \DOMElement && in_array(strtolower($child->tagName),
                    ['script','style','iframe','svg','object','embed','form','img','video','audio','link','meta','textarea','input','button'], true))) {
                $node->removeChild($child);
                continue;
            }
            if ($child instanceof \DOMElement) {
                self::sanitizeNode($child);
                $tag = strtolower($child->tagName);
                if (! in_array($tag, self::TAGS, true)) {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';
                while ($child->attributes->length) {
                    $child->removeAttributeNode($child->attributes->item(0));
                }
                if ($tag === 'a' && ($href !== '') &&
                    (preg_match('#^/(?!/)#', $href) ||
                    preg_match('#^https://[^[:space:]]+$#i', $href) ||
                    preg_match('/^mailto:[^@\s]+@[^@\s]+$/i', $href))) {
                    $child->setAttribute('href', $href);
                    if (preg_match('#^https://#i', $href)) {
                        $child->setAttribute('rel', 'noopener noreferrer');
                    }
                }
            } elseif (! ($child instanceof \DOMText)) {
                $node->removeChild($child);
            }
        }
    }
}
