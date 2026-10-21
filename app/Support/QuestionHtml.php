<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class QuestionHtml
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sup', 'sub',
        'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tfoot', 'tr',
        'th', 'td', 'span', 'div', 'blockquote', 'pre', 'code',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'img', 'a',
    ];

    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><div>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementsByTagName('div')->item(0);

        if (!$root) {
            return '';
        }

        $output = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= self::cleanNode($child);
        }

        return $output;
    }

    private static function cleanNode(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if (!$node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template'], true)) {
            return '';
        }

        $children = '';

        foreach (iterator_to_array($node->childNodes) as $child) {
            $children .= self::cleanNode($child);
        }

        if (!in_array($tag, self::ALLOWED_TAGS, true)) {
            return $children;
        }

        $attributes = '';

        if ($tag === 'a') {
            $href = $node->getAttribute('href');

            if (self::isSafeLink($href)) {
                $attributes .= ' href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" rel="noopener noreferrer"';
            }
        }

        if ($tag === 'img') {
            $src = $node->getAttribute('src');

            if (!self::isSafeImageSource($src)) {
                return '';
            }

            $attributes .= ' src="' . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';

            foreach (['alt', 'title', 'width', 'height'] as $attribute) {
                if ($node->hasAttribute($attribute)) {
                    $value = $node->getAttribute($attribute);

                    if (in_array($attribute, ['width', 'height'], true) && !ctype_digit($value)) {
                        continue;
                    }

                    $attributes .= ' ' . $attribute . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                }
            }
        }

        if (in_array($tag, ['td', 'th'], true)) {
            foreach (['colspan', 'rowspan'] as $attribute) {
                $value = $node->getAttribute($attribute);

                if ($value !== '' && ctype_digit($value) && (int) $value > 0 && (int) $value <= 100) {
                    $attributes .= ' ' . $attribute . '="' . (int) $value . '"';
                }
            }
        }

        if ($tag === 'br' || $tag === 'hr' || $tag === 'img') {
            return '<' . $tag . $attributes . '>';
        }

        return '<' . $tag . $attributes . '>' . $children . '</' . $tag . '>';
    }

    private static function isSafeLink(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || preg_match('/[\\x00-\\x20\\\\\\\\]/', $url)) {
            return false;
        }

        if (str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return !str_starts_with($url, '//');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }

    private static function isSafeImageSource(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || preg_match('/[\\x00-\\x20\\\\\\\\]/', $url) || str_starts_with($url, '//')) {
            return false;
        }

        // Allow safe embedded image data and HTTP(S) URLs only.
        if (preg_match('/^data:image\/(?:png|jpe?g|gif|webp);base64,[a-z0-9+\/]+=*$/i', $url)) {
            return true;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        // Relative paths are common for files saved under public/storage.
        if ($scheme === '') {
            return true;
        }

        return in_array($scheme, ['http', 'https'], true);
    }
}
