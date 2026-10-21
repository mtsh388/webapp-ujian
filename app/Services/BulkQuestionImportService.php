<?php

namespace App\Services;

use App\Models\Subject;
use App\Repositories\QuestionBankRepositoryInterface;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class BulkQuestionImportService
{
    public function __construct(
        protected QuestionBankRepositoryInterface $repository,
        protected QuestionCodeGenerator $codeGenerator
    )
    {
    }

    public function preview(UploadedFile $file): array
    {
        $isLegacyFormat = $this->isLegacyTemplateFormat($file);
        $questions = $this->parseDocument($file);

        if (empty($questions)) {
            throw new RuntimeException(
                'Tidak ditemukan soal pada file Word. Pastikan setiap soal menggunakan format "SOAL 1", "SOAL 2", dan seterusnya.'
            );
        }

        $this->validateQuestions($questions);

        $multipleChoice = 0;
        $trueFalse = 0;
        $previewQuestions = [];

        foreach ($questions as $index => $question) {
            if ($question['type'] === 'multiple_choice') {
                $multipleChoice++;
            } else {
                $trueFalse++;
            }

            $options = [];
            foreach (($question['options'] ?? []) as $key => $option) {
                $optionHtml = $option['html'] ?? htmlspecialchars((string) ($option['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $optionHtml = preg_replace(
                    '/<img src="__WORD_[^"]+"[^>]*>/u',
                    '<span class="badge bg-label-info">[Gambar]</span>',
                    $optionHtml
                );

                $options[] = [
                    'key' => $key,
                    'html' => $optionHtml,
                    'is_correct' => $key === ($question['answer'] ?? null),
                    'image_count' => count($option['images'] ?? []),
                ];
            }

            $questionHtml = preg_replace(
                '/<img src="__WORD_[^"]+"[^>]*>/u',
                '<span class="badge bg-label-info">[Gambar]</span>',
                (string) $question['question']
            );

            $explanationHtml = preg_replace(
                '/<img src="__WORD_[^"]+"[^>]*>/u',
                '<span class="badge bg-label-info">[Gambar]</span>',
                (string) $question['explanation']
            );

            $previewQuestions[] = [
                'number' => $index + 1,
                'type' => $question['type'],
                'question' => $questionHtml,
                'explanation' => $explanationHtml,
                'answer' => $question['answer'],
                'options' => $options,
                'image_count' => count($question['question_images'] ?? []),
            ];
        }

        return [
            'total' => count($previewQuestions),
            'multiple_choice' => $multipleChoice,
            'true_false' => $trueFalse,
            'format' => $isLegacyFormat ? 'template' : 'flexiquiz',
            'conversion_notes' => $isLegacyFormat ? [] : [
                'Soal Benar/Salah dengan beberapa pernyataan dipecah menjadi satu soal per pernyataan agar tetap kompatibel dengan penilaian ujian yang ada.',
                'Soal dengan lebih dari satu jawaban benar diubah menjadi beberapa soal Benar/Salah per pilihan. Periksa kembali hasil konversi sebelum mengonfirmasi impor.',
                'Teks bacaan yang terdeteksi disertakan pada pertanyaan terkait. Format ini tidak mengubah skema jawaban yang sudah digunakan sistem.',
            ],
            'questions' => $previewQuestions,
        ];
    }

    public function import(
        UploadedFile $file,
        int          $subjectId,
        string       $difficulty,
        float        $defaultScore
    ): array
    {
        $subject = Subject::query()->findOrFail($subjectId);

        $questions = $this->parseDocument($file);

        if (empty($questions)) {
            throw new RuntimeException(
                'Tidak ditemukan soal pada file Word. Pastikan setiap soal menggunakan format "SOAL 1", "SOAL 2", dan seterusnya.'
            );
        }

        $this->validateQuestions($questions);

        $storedFiles = [];

        try {
            return DB::transaction(function () use (
                $questions,
                $subject,
                $difficulty,
                $defaultScore,
                &$storedFiles
            ) {
                $imported = 0;
                $multipleChoice = 0;
                $trueFalse = 0;

                foreach ($questions as $question) {
                    $code = $this->codeGenerator->generate($subject);

                    $questionBank = $this->repository->create([
                        'subject_id' => $subject->id,
                        'code' => $code,
                        'type' => $question['type'],
                        'question' => $question['question'],
                        'explanation' => $question['explanation'],
                        'difficulty' => $difficulty,
                        'default_score' => $defaultScore,
                        'is_active' => true,
                    ]);

                    $questionMediaMap = $this->storeQuestionMedia(
                        $questionBank->id,
                        $question['question_images'],
                        $storedFiles
                    );

                    $questionContent = $this->replaceMediaTokens(
                        $question['question'],
                        $questionMediaMap
                    );

                    $explanationContent = $this->replaceMediaTokens(
                        $question['explanation'],
                        $questionMediaMap
                    );

                    if (
                        $questionContent !== $question['question'] ||
                        $explanationContent !== $question['explanation']
                    ) {
                        $questionBank->update([
                            'question' => $questionContent,
                            'explanation' => $explanationContent,
                        ]);
                    }

                    if ($question['type'] === 'multiple_choice') {
                        $this->storeMultipleChoiceOptions(
                            $questionBank->id,
                            $question,
                            $storedFiles
                        );

                        $multipleChoice++;
                    }

                    if ($question['type'] === 'true_false') {
                        $this->storeTrueFalseOptions(
                            $questionBank->id,
                            $question
                        );

                        $trueFalse++;
                    }

                    $imported++;
                }

                return [
                    'imported' => $imported,
                    'multiple_choice' => $multipleChoice,
                    'true_false' => $trueFalse,
                ];
            });
        } catch (\Throwable $e) {
            foreach ($storedFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }

    protected function isLegacyTemplateFormat(UploadedFile $file): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            return false;
        }

        try {
            $documentXml = $zip->getFromName('word/document.xml');

            if (!$documentXml) {
                return false;
            }

            $text = html_entity_decode(strip_tags($documentXml), ENT_QUOTES | ENT_XML1, 'UTF-8');

            return preg_match(
                '/SOAL(?:\s+\d+)?\s*\[(?:PILIHAN_GANDA|BENAR_SALAH)\]/iu',
                $text
            ) === 1;
        } finally {
            $zip->close();
        }
    }

    protected function parseDocument(
        UploadedFile $file
    ): array
    {
        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException(
                'File Word tidak dapat dibuka.'
            );
        }

        try {
            $documentXml = $zip->getFromName(
                'word/document.xml'
            );

            $relationsXml = $zip->getFromName(
                'word/_rels/document.xml.rels'
            );

            if (!$documentXml || !$relationsXml) {
                throw new RuntimeException(
                    'Struktur file Word tidak valid.'
                );
            }

            $relations = $this->parseRelations(
                $relationsXml
            );

            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;

            if (!$dom->loadXML($documentXml)) {
                throw new RuntimeException(
                    'Dokumen Word tidak dapat dibaca.'
                );
            }

            $xpath = new DOMXPath($dom);

            $xpath->registerNamespace(
                'w',
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
            );

            $xpath->registerNamespace(
                'a',
                'http://schemas.openxmlformats.org/drawingml/2006/main'
            );

            $xpath->registerNamespace(
                'r',
                'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
            );

            $bodyNodes = $xpath->query(
                '/w:document/w:body/*'
            );

            $blocks = [];

            foreach ($bodyNodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }

                if ($node->localName === 'p') {
                    $content = $this->extractParagraphContent(
                        $node,
                        $xpath,
                        $zip,
                        $relations
                    );

                    $blocks[] = [
                        'type' => 'paragraph',
                        'text' => $content['text'],
                        'html' => $content['html'],
                        'images' => $content['images'],
                    ];

                    continue;
                }

                if ($node->localName === 'tbl') {
                    $blocks[] = $this->extractTableContent(
                        $node,
                        $xpath,
                        $zip,
                        $relations
                    );
                }
            }

            $hasLegacyHeaders = collect($blocks)->contains(function (array $block): bool {
                return ($block['type'] ?? null) === 'paragraph'
                    && $this->parseQuestionHeader($this->normalizeText((string) ($block['text'] ?? ''))) !== null;
            });

            return $hasLegacyHeaders
                ? $this->buildQuestions($blocks)
                : $this->buildFlexiQuizQuestions($blocks);
        } finally {
            $zip->close();
        }
    }

    protected function extractParagraphContent(
        DOMElement $paragraph,
        DOMXPath   $xpath,
        ZipArchive $zip,
        array      $relations
    ): array
    {
        $text = '';
        $html = '';
        $images = [];

        $runs = $xpath->query(
            './w:r | ./w:hyperlink/w:r',
            $paragraph
        );

        foreach ($runs as $run) {
            if (!$run instanceof DOMElement) {
                continue;
            }

            $runProperties = $xpath->query(
                './w:rPr',
                $run
            )->item(0);

            $isSuperscript = false;
            $isSubscript = false;

            if ($runProperties instanceof DOMElement) {
                $vertAlign = $xpath->query(
                    './w:vertAlign',
                    $runProperties
                )->item(0);

                if ($vertAlign instanceof DOMElement) {
                    $value = $vertAlign->getAttributeNS(
                        'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                        'val'
                    );

                    $isSuperscript = $value === 'superscript';
                    $isSubscript = $value === 'subscript';
                }
            }

            $textNodes = $xpath->query(
                './w:t | ./w:tab | ./w:br',
                $run
            );

            foreach ($textNodes as $node) {
                if ($node->localName === 't') {
                    $value = $node->nodeValue ?? '';

                    $text .= $value;

                    $escapedValue = htmlspecialchars(
                        $value,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    );

                    if ($isSuperscript) {
                        $html .= '<sup>' . $escapedValue . '</sup>';
                    } elseif ($isSubscript) {
                        $html .= '<sub>' . $escapedValue . '</sub>';
                    } else {
                        $html .= $escapedValue;
                    }

                    continue;
                }

                if ($node->localName === 'tab') {
                    $text .= ' ';
                    $html .= ' ';

                    continue;
                }

                if ($node->localName === 'br') {
                    $text .= "\n";
                    $html .= '<br>';

                    continue;
                }
            }

            $drawingNodes = $xpath->query(
                './/a:blip',
                $run
            );

            foreach ($drawingNodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }

                $relationId = $node->getAttributeNS(
                    'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
                    'embed'
                );

                if (
                    !$relationId ||
                    !isset($relations[$relationId])
                ) {
                    continue;
                }

                $mediaPath = $relations[$relationId];

                $imageContent = $zip->getFromName(
                    $mediaPath
                );

                if ($imageContent === false) {
                    continue;
                }

                $extension = strtolower(
                    pathinfo(
                        $mediaPath,
                        PATHINFO_EXTENSION
                    )
                );

                $images[] = [
                    'content' => $imageContent,
                    'extension' => $extension ?: 'png',
                    'original_name' => basename($mediaPath),
                    'position' => strlen($text),
                ];
            }
        }

        return [
            'text' => $text,
            'html' => $html,
            'images' => $images,
        ];
    }


    protected function extractTableContent(
        DOMElement $table,
        DOMXPath   $xpath,
        ZipArchive $zip,
        array      $relations
    ): array
    {
        $rows = $xpath->query(
            './w:tr',
            $table
        );

        $html = '<table class="table table-bordered question-word-table">';
        $html .= '<tbody>';

        $images = [];

        foreach ($rows as $row) {
            $html .= '<tr>';

            $cells = $xpath->query(
                './w:tc',
                $row
            );

            foreach ($cells as $cell) {
                $colspan = $this->getTableColspan(
                    $cell,
                    $xpath
                );

                $paragraphs = $xpath->query(
                    './w:p',
                    $cell
                );

                $cellParts = [];

                foreach ($paragraphs as $paragraph) {
                    $content = $this->extractParagraphContent(
                        $paragraph,
                        $xpath,
                        $zip,
                        $relations
                    );

                    $cellParts[] = $this->convertParagraphToHtml(
                        $content,
                        $images
                    );
                }

                if (empty($cellParts)) {
                    $cellParts[] = '';
                }

                $html .= '<td';

                if ($colspan > 1) {
                    $html .= ' colspan="' . $colspan . '"';
                }

                $html .= '>';
                $html .= implode('<br>', $cellParts);
                $html .= '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return [
            'type' => 'table',
            'html' => $html,
            'images' => $images,
        ];
    }

    protected function normalizeFormattedHtml(
        string $html
    ): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $html = preg_replace(
            '/[ \t]+/u',
            ' ',
            $html
        );

        $html = preg_replace(
            '/ *\n */u',
            "\n",
            $html
        );

        return $html;
    }

    protected function extractHtmlByTextRange(
        string $text,
        string $html,
        int    $start,
        int    $end
    ): string
    {
        if ($html === '' || $end <= $start) {
            return '';
        }

        $dom = new DOMDocument();
        $previousErrorState = libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="question-import-fragment">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorState);

        if (!$loaded) {
            return '';
        }

        $container = $dom->getElementById('question-import-fragment');

        if (!$container instanceof DOMElement) {
            return '';
        }

        $offset = 0;

        $extractNode = function (\DOMNode $node) use (
            &$extractNode,
            &$offset,
            $start,
            $end,
            $dom
        ): ?\DOMNode {
            if ($node->nodeType === XML_TEXT_NODE) {
                $value = $node->nodeValue ?? '';
                $nodeStart = $offset;
                $nodeEnd = $nodeStart + strlen($value);
                $offset = $nodeEnd;

                $segmentStart = max($start, $nodeStart);
                $segmentEnd = min($end, $nodeEnd);

                if ($segmentEnd <= $segmentStart) {
                    return null;
                }

                return $dom->createTextNode(
                    substr(
                        $value,
                        $segmentStart - $nodeStart,
                        $segmentEnd - $segmentStart
                    )
                );
            }

            if ($node->nodeType !== XML_ELEMENT_NODE) {
                return null;
            }

            if (strtolower($node->nodeName) === 'br') {
                $nodeStart = $offset;
                $offset++;

                return $nodeStart >= $start && $nodeStart < $end
                    ? $node->cloneNode(false)
                    : null;
            }

            $clone = $node->cloneNode(false);

            foreach ($node->childNodes as $child) {
                $childClone = $extractNode($child);

                if ($childClone !== null) {
                    $clone->appendChild($childClone);
                }
            }

            return $clone->hasChildNodes() ? $clone : null;
        };

        $fragment = $dom->createDocumentFragment();

        foreach ($container->childNodes as $child) {
            $clone = $extractNode($child);

            if ($clone !== null) {
                $fragment->appendChild($clone);
            }
        }

        return $dom->saveHTML($fragment) ?: '';
    }

    protected function convertParagraphToHtml(
        array $content,
        array &$images
    ): string
    {
        $html = (string)($content['html'] ?? '');
        $paragraphImages = $content['images'] ?? [];

        if (empty($paragraphImages)) {
            return $this->normalizeFormattedHtml(
                $html
            );
        }

        $text = (string)($content['text'] ?? '');

        usort(
            $paragraphImages,
            function (array $a, array $b): int {
                return ((int)($a['position'] ?? 0))
                    <=> ((int)($b['position'] ?? 0));
            }
        );

        $htmlOutput = $html;

        foreach ($paragraphImages as $image) {
            $token = '__WORD_IMAGE_' .
                Str::uuid()->toString() .
                '__';

            $image['token'] = $token;

            unset($image['position']);

            $images[] = $image;

            $htmlOutput .=
                '<img src="' .
                $token .
                '" class="img-fluid question-word-image" alt="">';
        }

        return $this->normalizeFormattedHtml(
            $htmlOutput
        );
    }

    protected function getTableColspan(
        DOMElement $cell,
        DOMXPath   $xpath
    ): int
    {
        $gridSpan = $xpath->query(
            './w:tcPr/w:gridSpan',
            $cell
        )->item(0);

        if (!$gridSpan instanceof DOMElement) {
            return 1;
        }

        $value = $gridSpan->getAttributeNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'val'
        );

        $value = (int)$value;

        return $value > 0 ? $value : 1;
    }

    protected function parseRelations(
        string $xml
    ): array
    {
        $dom = new DOMDocument();

        if (!$dom->loadXML($xml)) {
            throw new RuntimeException(
                'Relasi media Word tidak dapat dibaca.'
            );
        }

        $relations = [];

        foreach ($dom->documentElement->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $id = $node->getAttribute('Id');
            $target = $node->getAttribute('Target');

            if (!$id || !$target) {
                continue;
            }

            $lowerTarget = strtolower($target);

            if (
                str_starts_with(
                    $lowerTarget,
                    'http://'
                ) ||
                str_starts_with(
                    $lowerTarget,
                    'https://'
                )
            ) {
                continue;
            }

            $relations[$id] =
                $this->normalizeZipPath($target);
        }

        return $relations;
    }

    protected function normalizeZipPath(
        string $target
    ): string
    {
        $target = str_replace(
            '\\',
            '/',
            $target
        );

        $target = ltrim(
            $target,
            '/'
        );

        $target = preg_replace(
            '#^\.\./#',
            '',
            $target
        );

        if (
            !str_starts_with(
                $target,
                'word/'
            )
        ) {
            $target = 'word/' . $target;
        }

        return $target;
    }

    protected function buildQuestions(
        array $blocks
    ): array
    {
        $questions = [];
        $current = null;
        $currentOption = null;
        $inExplanation = false;

        foreach ($blocks as $block) {
            $blockType = $block['type'] ?? null;

            if ($blockType === 'table') {
                if ($current === null) {
                    continue;
                }

                $tableHtml = $block['html'] ?? '';
                $tableImages = $block['images'] ?? [];

                if ($inExplanation) {
                    $current['explanation'] =
                        $this->appendHtml(
                            $current['explanation'],
                            $tableHtml
                        );

                    $current['question_images'] =
                        array_merge(
                            $current['question_images'],
                            $tableImages
                        );

                    continue;
                }

                if ($currentOption !== null) {
                    $current['options'][$currentOption]['text'] =
                        $this->appendHtml(
                            $current['options'][$currentOption]['text'],
                            $tableHtml
                        );

                    $current['options'][$currentOption]['images'] =
                        array_merge(
                            $current['options'][$currentOption]['images'],
                            $tableImages
                        );

                    continue;
                }

                $current['question'] =
                    $this->appendHtml(
                        $current['question'],
                        $tableHtml
                    );

                $current['question_images'] =
                    array_merge(
                        $current['question_images'],
                        $tableImages
                    );

                continue;
            }

            if ($blockType !== 'paragraph') {
                continue;
            }

            $rawText = (string)($block['text'] ?? '');
            $text = $this->normalizeText($rawText);
            $images = $block['images'] ?? [];

            if (
                $text === '' &&
                empty($images)
            ) {
                continue;
            }

            $header = $this->parseQuestionHeader($text);

            if ($header !== null) {
                if ($current !== null) {
                    $current['type'] =
                        $this->detectQuestionType(
                            $current
                        );

                    $questions[] = $current;
                }

                $current = [
                    'type' => $header['type'],
                    'question' => $this->escapeTextAsHtml(
                        $header['question']
                    ),
                    'question_images' => $images,
                    'options' => [],
                    'answer' => null,
                    'explanation' => '',
                ];

                $currentOption = null;
                $inExplanation = false;

                continue;
            }

            if ($current === null) {
                continue;
            }

            if (
                preg_match(
                    '/^\s*JAWABAN\s*[:.]?\s*([A-D])(?:\s+(.*))?$/iu',
                    $text,
                    $matches
                )
            ) {
                $current['answer'] =
                    strtoupper(
                        trim($matches[1])
                    );

                $remaining = isset($matches[2])
                    ? $this->normalizeText($matches[2])
                    : '';

                $currentOption = null;
                $inExplanation = false;

                if ($remaining !== '') {
                    if (
                        preg_match(
                            '/^PEMBAHASAN\s*[:.]?\s*(.*)$/iu',
                            $remaining,
                            $explanationMatches
                        )
                    ) {
                        $current['explanation'] =
                            $this->escapeTextAsHtml(
                                $explanationMatches[1]
                            );

                        $inExplanation = true;
                    }
                }

                continue;
            }

            if (
                preg_match(
                    '/^\s*PEMBAHASAN\s*[:.]?\s*(.*)$/iu',
                    $text,
                    $matches
                )
            ) {
                $current['explanation'] =
                    $this->escapeTextAsHtml(
                        $matches[1]
                    );

                $currentOption = null;
                $inExplanation = true;

                continue;
            }

            if (
                $current['type'] === null ||
                $current['type'] === 'multiple_choice'
            ) {
                $optionResult = $this->extractOptions(
                    $rawText,
                    $images,
                    (string) ($block['html'] ?? '')
                );

                if (
                    !empty(
                    $optionResult['options']
                    )
                ) {
                    $current['type'] =
                        'multiple_choice';

                    foreach (
                        $optionResult['options'] as $option
                    ) {
                        $current['options'][$option['key']] = [
                            'key' => $option['key'],
                            'text' => $option['text'],
                            'html' => $option['html'],
                            'images' => $option['images'],
                        ];

                        $currentOption =
                            $option['key'];
                    }

                    if (
                        !empty(
                        $optionResult['before_images']
                        )
                    ) {
                        $current['question_images'] =
                            array_merge(
                                $current['question_images'],
                                $optionResult['before_images']
                            );
                    }

                    $inExplanation = false;

                    continue;
                }
            }

            if ($inExplanation) {
                $formattedImages = [];
                $formattedHtml = $this->convertParagraphToHtml(
                    $block,
                    $formattedImages
                );

                $current['explanation'] =
                    $this->appendHtml(
                        $current['explanation'],
                        $formattedHtml
                    );

                if (!empty($formattedImages)) {
                    $current['question_images'] = array_merge(
                        $current['question_images'],
                        $formattedImages
                    );
                }

                continue;
            }

            if ($currentOption !== null) {
                $formattedImages = [];
                $formattedHtml = $this->convertParagraphToHtml(
                    $block,
                    $formattedImages
                );

                $current['options'][$currentOption]['text'] =
                    $this->appendHtml(
                        $current['options'][$currentOption]['text'],
                        $formattedHtml
                    );

                if (!empty($formattedImages)) {
                    $current['options'][$currentOption]['images'] = array_merge(
                        $current['options'][$currentOption]['images'],
                        $formattedImages
                    );
                }

                continue;
            }

            $formattedImages = [];
            $formattedHtml = $this->convertParagraphToHtml(
                $block,
                $formattedImages
            );

            $current['question'] =
                $this->appendHtml(
                    $current['question'],
                    $formattedHtml
                );

            if (!empty($formattedImages)) {
                $current['question_images'] = array_merge(
                    $current['question_images'],
                    $formattedImages
                );
            }
        }

        if ($current !== null) {
            $current['type'] =
                $this->detectQuestionType(
                    $current
                );

            $questions[] = $current;
        }

        return $questions;
    }

    protected function buildFlexiQuizQuestions(array $blocks): array
    {
        $paragraphs = [];

        foreach ($blocks as $block) {
            if (($block['type'] ?? null) !== 'paragraph') {
                continue;
            }

            $text = $this->normalizeText((string) ($block['text'] ?? ''));

            if ($text === '' || preg_match('/^FlexiQuiz\s*-\s*Editor/i', $text)) {
                continue;
            }

            $paragraphs[] = $block;
        }

        $questions = [];
        $context = [];
        $count = count($paragraphs);
        $index = 0;

        while ($index < $count) {
            $text = $this->normalizeText((string) ($paragraphs[$index]['text'] ?? ''));

            if (preg_match('/^Bacalah\b/iu', $text)) {
                $context = [];
                $seenSource = false;

                while ($index < $count) {
                    $candidate = $this->normalizeText((string) ($paragraphs[$index]['text'] ?? ''));

                    if (preg_match('/^Sumber(?:\s+teks)?\s*:/iu', $candidate)) {
                        $seenSource = true;
                        $context[] = $paragraphs[$index];
                        $index++;
                        continue;
                    }

                    if ($this->isFlexiQuizQuestionCandidate($paragraphs, $index)) {
                        break;
                    }

                    if (!preg_match('/^FlexiQuiz\s*-\s*Editor/i', $candidate)) {
                        $context[] = $paragraphs[$index];
                    }

                    $index++;
                }

                if ($index >= $count) {
                    break;
                }

                $text = $this->normalizeText((string) ($paragraphs[$index]['text'] ?? ''));
            }

            if (!$this->isFlexiQuestionCandidate($paragraphs, $index)) {
                $index++;
                continue;
            }

            $stemBlock = $paragraphs[$index];
            $stemText = $text;
            $stemHtml = $this->flexiBlockHtml($stemBlock, $stemImages);
            $contextHtml = '';
            $contextImages = [];

            foreach ($context as $contextBlock) {
                $contextHtml = $this->appendHtml(
                    $contextHtml,
                    $this->flexiBlockHtml($contextBlock, $contextImages)
                );
            }

            $questionHtml = $contextHtml !== ''
                ? $this->appendHtml($contextHtml, $stemHtml)
                : $stemHtml;

            $questionImages = array_merge($contextImages, $stemImages ?? []);
            $index++;

            $marker = '';
            $markerIndex = $index;

            for ($lookahead = $index; $lookahead < min($count, $index + 3); $lookahead++) {
                $markerText = $this->normalizeText((string) ($paragraphs[$lookahead]['text'] ?? ''));

                if (preg_match('/jawaban\s+benar\s+lebih\s+dari\s+satu|pilih\s+semua\s+jawaban/i', $markerText)) {
                    $marker = 'multiple_select';
                    $markerIndex = $lookahead;
                    break;
                }

                if (preg_match('/tentukan.*benar\s+atau\s+salah|pilih\s+jawaban\s+benar\s+atau\s+salah|benar\s+atau\s+salah\s+pada\s+setiap\s+pernyataan/i', $markerText)) {
                    $marker = 'statement_true_false';
                    $markerIndex = $lookahead;
                    break;
                }

                if ($this->isFlexiQuizQuestionStem($markerText)) {
                    break;
                }
            }

            if ($marker !== '') {
                $index = $markerIndex + 1;
                $items = [];
                $answerText = '';

                while ($index < $count) {
                    $itemBlock = $paragraphs[$index];
                    $itemText = $this->normalizeText((string) ($itemBlock['text'] ?? ''));

                    if ($this->isFlexiQuestionCandidate($paragraphs, $index) && !$this->containsFlexiAnswer($itemText)) {
                        break;
                    }

                    [$itemPrefix, $itemAnswer] = $this->splitFlexiAnswer($itemText);

                    if ($itemPrefix !== '') {
                        $items[] = [
                            'text' => $itemPrefix,
                            'block' => $itemBlock,
                        ];
                    }

                    if ($itemAnswer !== null) {
                        $answerText = $itemAnswer;
                        $index++;
                        break;
                    }

                    $index++;
                }

                if ($answerText === '' || empty($items)) {
                    throw new RuntimeException(
                        'Format TKA/FlexiQuiz tidak dapat membaca kunci jawaban setelah soal: ' . strip_tags($stemText)
                    );
                }

                $truthValues = $this->parseFlexiTruthValues($answerText);

                if (
                    $marker === 'multiple_select'
                    && count($truthValues) > 1
                    && count($this->splitFlexiAnswerItems($answerText)) <= 1
                ) {
                    $marker = 'statement_true_false';
                }

                if ($marker === 'statement_true_false') {
                    $truthValues = $this->parseFlexiTruthValues($answerText);
                    $items = $this->splitFlexiCombinedStatements($items, count($truthValues));

                    if (count($truthValues) !== count($items)) {
                        throw new RuntimeException(
                            'Jumlah jawaban Benar/Salah tidak sesuai dengan jumlah pernyataan pada soal: ' . strip_tags($stemText)
                        );
                    }

                    foreach ($items as $itemIndex => $item) {
                        $itemImages = [];
                        $itemHtml = $this->flexiBlockHtml($item['block'], $itemImages);
                        $statementHtml = $this->appendHtml(
                            $questionHtml,
                            $itemHtml
                        );

                        $questions[] = $this->makeFlexiTrueFalseQuestion(
                            $statementHtml,
                            $truthValues[$itemIndex] ? 'A' : 'B',
                            array_merge($questionImages, $itemImages)
                        );
                    }

                    continue;
                }

                foreach ($items as $item) {
                    $itemImages = [];
                    $itemHtml = $this->flexiBlockHtml($item['block'], $itemImages);
                    $normalizedAnswer = $this->normalizeFlexiComparable($answerText);
                    $normalizedOption = $this->normalizeFlexiComparable($item['text']);
                    $isCorrect = $normalizedOption !== ''
                        && str_contains($normalizedAnswer, $normalizedOption);

                    if (!$isCorrect) {
                        foreach ($this->splitFlexiAnswerItems($answerText) as $answerItem) {
                            $normalizedAnswerItem = $this->normalizeFlexiComparable($answerItem);

                            if ($normalizedAnswerItem !== '' && (
                                str_contains($normalizedAnswerItem, $normalizedOption)
                                || str_contains($normalizedOption, $normalizedAnswerItem)
                            )) {
                                $isCorrect = true;
                                break;
                            }
                        }
                    }

                    $prompt = $this->appendHtml(
                        $questionHtml,
                        '<p>Apakah pernyataan berikut termasuk jawaban yang benar?</p>'
                    );
                    $prompt = $this->appendHtml(
                        $prompt,
                        '<blockquote>' . $itemHtml . '</blockquote>'
                    );

                    $questions[] = $this->makeFlexiTrueFalseQuestion(
                        $prompt,
                        $isCorrect ? 'A' : 'B',
                        array_merge($questionImages, $itemImages)
                    );
                }

                continue;
            }

            $options = [];
            $answerText = '';
            $optionImages = [];
            $optionCount = 0;

            while ($index < $count && $optionCount < 4) {
                $optionBlock = $paragraphs[$index];
                $optionText = $this->normalizeText((string) ($optionBlock['text'] ?? ''));

                if ($this->isFlexiQuestionCandidate($paragraphs, $index) && !$this->containsFlexiAnswer($optionText)) {
                    break;
                }

                [$optionPrefix, $itemAnswer] = $this->splitFlexiAnswer($optionText);

                if ($optionPrefix !== '') {
                    $optionImagesForBlock = [];
                    $optionHtml = $this->flexiBlockHtml($optionBlock, $optionImagesForBlock);
                    if ($itemAnswer !== null) {
                        $optionHtml = $this->escapeTextAsHtml($optionPrefix);
                    }

                    $key = chr(65 + $optionCount);
                    $options[$key] = [
                        'key' => $key,
                        'text' => $optionPrefix,
                        'html' => $optionHtml,
                        'images' => $optionImagesForBlock,
                    ];
                    $optionImages = array_merge($optionImages, $optionImagesForBlock);
                    $optionCount++;
                }

                if ($itemAnswer !== null) {
                    $answerText = $itemAnswer;
                    $index++;
                    break;
                }

                $index++;
            }

            if (count($options) !== 4) {
                throw new RuntimeException(
                    'Format TKA/FlexiQuiz memerlukan empat pilihan jawaban untuk soal pilihan ganda: ' . strip_tags($stemText)
                );
            }

            if ($answerText === '') {
                if ($index < $count) {
                    [$nextPrefix, $nextAnswer] = $this->splitFlexiAnswer(
                        $this->normalizeText((string) ($paragraphs[$index]['text'] ?? ''))
                    );

                    if ($nextAnswer !== null) {
                        $answerText = $nextAnswer;
                        $index++;
                    }
                }
            }

            $answerKey = $this->matchFlexiAnswerKey($answerText, $options);

            if ($answerKey === null) {
                throw new RuntimeException(
                    'Kunci jawaban tidak cocok dengan pilihan pada soal: ' . strip_tags($stemText)
                );
            }

            $questions[] = [
                'type' => 'multiple_choice',
                'question' => $questionHtml,
                'question_images' => $questionImages,
                'options' => $options,
                'answer' => $answerKey,
                'explanation' => '',
            ];
        }

        if (empty($questions)) {
            throw new RuntimeException(
                'Tidak ditemukan soal pada format TKA/FlexiQuiz. Pastikan setiap soal diikuti pilihan jawaban dan bagian Jawaban.'
            );
        }

        return $questions;
    }

    protected function isFlexiQuestionCandidate(array $paragraphs, int $index): bool
    {
        $text = $this->normalizeText((string) ($paragraphs[$index]['text'] ?? ''));

        if (!$this->isFlexiQuizQuestionStem($text)) {
            return false;
        }

        $limit = min(count($paragraphs), $index + 7);

        for ($lookahead = $index + 1; $lookahead < $limit; $lookahead++) {
            $candidate = $this->normalizeText((string) ($paragraphs[$lookahead]['text'] ?? ''));

            if ($this->containsFlexiAnswer($candidate)) {
                return true;
            }

            if (preg_match('/jawaban\s+benar\s+lebih\s+dari\s+satu|pilih\s+semua\s+jawaban|tentukan.*benar\s+atau\s+salah|pilih\s+jawaban\s+benar\s+atau\s+salah|benar\s+atau\s+salah\s+pada\s+setiap\s+pernyataan/i', $candidate)) {
                for ($answerLookahead = $lookahead + 1; $answerLookahead < $limit; $answerLookahead++) {
                    if ($this->containsFlexiAnswer($this->normalizeText((string) ($paragraphs[$answerLookahead]['text'] ?? '')))) {
                        return true;
                    }
                }
            }

            if ($this->isFlexiQuizQuestionStem($candidate) && !$this->containsFlexiAnswer($candidate)) {
                break;
            }
        }

        return false;
    }

    protected function isFlexiQuizQuestionStem(string $text): bool
    {
        if ($text === '' || preg_match('/^Bacalah\b|^Teks\s+\d+\b|^Sumber\s*:/iu', $text)) {
            return false;
        }

        if (preg_match('/\?|\.\.\.\s*$/u', $text)) {
            return true;
        }

        return preg_match(
            '/^(Apa|Apakah|Mengapa|Bagaimana|Siapa|Kepada siapa|Kata manakah|Kalimat manakah|Pernyataan manakah|Informasi yang|Nada dan suasana|Berdasarkan teks|Tentukanlah|Tentukan|Pesan apa|Apa sajakah|Apa saja|Alat apa|Hal apa|Yang menjadi|Yang paling tepat|Makna dari|Apa makna|Manakah|Informasi yang tidak tepat|Apa yang)/iu',
            $text
        ) === 1;
    }

    protected function flexiBlockHtml(array $block, array &$images): string
    {
        $images = [];
        $html = $this->convertParagraphToHtml($block, $images);

        if ($html === '') {
            $html = $this->escapeTextAsHtml((string) ($block['text'] ?? ''));
        }

        return $html;
    }

    protected function splitFlexiAnswer(string $text): array
    {
        if (!preg_match('/\bJawaban\s*:\s*/iu', $text, $matches, PREG_OFFSET_CAPTURE)) {
            return [$this->normalizeText($text), null];
        }

        $offset = $matches[0][1];
        $prefix = $this->normalizeText(substr($text, 0, $offset));
        $answer = $this->normalizeText(substr($text, $offset + strlen($matches[0][0])));

        return [$prefix, $answer];
    }

    protected function containsFlexiAnswer(string $text): bool
    {
        return preg_match('/\bJawaban\s*:/iu', $text) === 1;
    }

    protected function parseFlexiTruthValues(string $answer): array
    {
        preg_match_all('/\b(Benar|Salah)\b/iu', $answer, $matches);

        return array_map(
            fn (string $value): bool => mb_strtolower($value) === 'benar',
            $matches[1] ?? []
        );
    }

    protected function splitFlexiCombinedStatements(array $items, int $expected): array
    {
        if (count($items) !== 1 || $expected < 2) {
            return $items;
        }

        $text = trim((string) ($items[0]['text'] ?? ''));
        $firstWord = strtok($text, " \t\n\r");

        if (!$firstWord) {
            return $items;
        }

        preg_match_all(
            '/(?<!\pL)' . preg_quote($firstWord, '/') . '(?!\pL)/iu',
            $text,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        if (count($matches[0] ?? []) !== $expected) {
            $parts = preg_split('/(?:\r?\n|[;|])+/u', $text) ?: [];

            if (count($parts) === $expected) {
                return array_map(function (string $part) use ($items): array {
                    $part = trim($part);
                    $block = $items[0]['block'];
                    $block['text'] = $part;
                    $block['html'] = $this->escapeTextAsHtml($part);
                    $block['images'] = [];

                    return [
                        'text' => $part,
                        'block' => $block,
                    ];
                }, $parts);
            }

            return $items;
        }

        $offsets = array_map(
            fn (array $match): int => (int) $match[1],
            $matches[0]
        );
        $parts = [];

        for ($i = 0; $i < count($offsets); $i++) {
            $start = $offsets[$i];
            $end = $offsets[$i + 1] ?? strlen($text);
            $part = trim(substr($text, $start, $end - $start));

            if ($part === '') {
                continue;
            }

            $block = $items[0]['block'];
            $block['text'] = $part;
            $block['html'] = $this->escapeTextAsHtml($part);
            $block['images'] = [];

            $parts[] = [
                'text' => $part,
                'block' => $block,
            ];
        }

        return count($parts) === $expected ? $parts : $items;
    }

    protected function splitFlexiAnswerItems(string $answer): array
    {
        $answer = preg_replace('/^\s*[-•]\s*/mu', '', $answer);
        $items = preg_split('/\s*[-•]\s*/u', $answer) ?: [];

        return array_values(array_filter(
            array_map(fn (string $item): string => trim($item), $items),
            fn (string $item): bool => $item !== ''
        ));
    }

    protected function normalizeFlexiComparable(string $text): string
    {
        $text = mb_strtolower(strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $text = preg_replace('/[^\pL\pN]+/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    protected function matchFlexiAnswerKey(string $answer, array $options): ?string
    {
        $answer = trim($answer);

        if (preg_match('/^[A-D]$/iu', $answer)) {
            return strtoupper($answer);
        }

        $normalizedAnswer = $this->normalizeFlexiComparable($answer);

        foreach ($options as $key => $option) {
            $normalizedOption = $this->normalizeFlexiComparable((string) ($option['text'] ?? ''));

            if ($normalizedAnswer !== '' && $normalizedAnswer === $normalizedOption) {
                return $key;
            }
        }

        foreach ($options as $key => $option) {
            $normalizedOption = $this->normalizeFlexiComparable((string) ($option['text'] ?? ''));

            if ($normalizedAnswer !== '' && $normalizedOption !== '' && (
                str_contains($normalizedAnswer, $normalizedOption)
                || str_contains($normalizedOption, $normalizedAnswer)
            )) {
                return $key;
            }
        }

        return null;
    }

    protected function makeFlexiTrueFalseQuestion(string $question, string $answer, array $images): array
    {
        return [
            'type' => 'true_false',
            'question' => $question,
            'question_images' => $images,
            'options' => [],
            'answer' => $answer,
            'explanation' => '',
        ];
    }

    protected function parseQuestionHeader(
        string $text
    ): ?array
    {
        if (
            !preg_match(
                '/^SOAL(?:\s+\d+)?\s*(?:\[(PILIHAN_GANDA|BENAR_SALAH)\])?\s*[:.]?\s*(.*?)$/iu',
                $text,
                $matches
            )
        ) {
            return null;
        }

        $type = null;

        if (!empty($matches[1])) {
            $type = match (
            strtoupper(
                trim($matches[1])
            )
            ) {
                'PILIHAN_GANDA' => 'multiple_choice',
                'BENAR_SALAH' => 'true_false',
                default => null,
            };
        }

        return [
            'type' => $type,
            'question' => $this->normalizeText(
                $matches[2] ?? ''
            ),
        ];
    }

    protected function detectQuestionType(
        array $question
    ): ?string
    {
        if (
            isset($question['type']) &&
            in_array(
                $question['type'],
                [
                    'multiple_choice',
                    'true_false',
                ],
                true
            )
        ) {
            return $question['type'];
        }

        if (!empty($question['options'])) {
            return 'multiple_choice';
        }

        if (
            !empty($question['answer']) &&
            in_array(
                $question['answer'],
                ['A', 'B'],
                true
            )
        ) {
            return 'true_false';
        }

        return null;
    }


    protected function extractOptions(
        string $text,
        array  $images,
        string $html = ''
    ): array
    {
        preg_match_all(
            '/(?:^|\s)([A-D])\s*[\.\)]\s*/iu',
            $text,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        if (empty($matches[1])) {
            return [
                'options' => [],
                'before_images' => $images,
            ];
        }

        $options = [];
        $beforeImages = [];
        $count = count($matches[1]);
        $markers = [];

        for ($i = 0; $i < $count; $i++) {
            $key = strtoupper(
                trim(
                    (string)$matches[1][$i][0]
                )
            );

            $markerOffset = (int)$matches[0][$i][1];

            $markerLength = strlen(
                (string)$matches[0][$i][0]
            );

            $contentStart = $markerOffset + $markerLength;

            $contentEnd = $i + 1 < $count
                ? (int)$matches[0][$i + 1][1]
                : strlen($text);

            $markers[] = [
                'key' => $key,
                'start' => $contentStart,
                'end' => $contentEnd,
            ];
        }

        foreach ($markers as $marker) {
            $optionText = substr(
                $text,
                $marker['start'],
                $marker['end'] - $marker['start']
            );

            $optionText = $this->normalizeText($optionText);

            $optionImages = [];
            $optionHtml = $this->extractHtmlByTextRange(
                $text,
                $html,
                $marker['start'],
                $marker['end']
            );

            if ($optionHtml === '') {
                $optionHtml = $this->escapeTextAsHtml($optionText);
            }

            foreach ($images as $image) {
                $position = (int)($image['position'] ?? 0);

                if (
                    $position >= $marker['start'] &&
                    $position < $marker['end']
                ) {
                    $image = $this->stripImagePosition($image);

                    $token = '__WORD_OPTION_IMAGE_' .
                        Str::uuid()->toString() .
                        '__';

                    $image['token'] = $token;

                    $optionImages[] = $image;

                    $optionHtml .=
                        '<img src="' .
                        $token .
                        '" class="img-fluid question-word-image" alt="">';
                }
            }

            $options[$marker['key']] = [
                'key' => $marker['key'],
                'text' => $optionText,
                'html' => $optionHtml,
                'images' => $optionImages,
            ];
        }

        $firstStart = $markers[0]['start'];

        foreach ($images as $image) {
            $position = (int)($image['position'] ?? 0);

            if ($position < $firstStart) {
                $beforeImages[] =
                    $this->stripImagePosition($image);
            }
        }

        return [
            'options' => array_values($options),
            'before_images' => $beforeImages,
        ];
    }


    protected function stripImagePosition(
        array $image
    ): array
    {
        unset($image['position']);

        return $image;
    }

    protected function validateQuestions(
        array $questions
    ): void
    {
        foreach ($questions as $index => $question) {
            $number =
                ((int)$index) + 1;

            $type =
                $question['type'] ?? null;

            if (
                !in_array(
                    $type,
                    [
                        'multiple_choice',
                        'true_false',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    "Jenis soal nomor {$number} tidak dapat ditentukan. Gunakan pilihan A-D untuk Pilihan Ganda atau JAWABAN: A/B untuk Benar/Salah."
                );
            }

            $questionText =
                trim(
                    strip_tags(
                        html_entity_decode(
                            (string)(
                                $question['question'] ?? ''
                            ),
                            ENT_QUOTES | ENT_HTML5,
                            'UTF-8'
                        )
                    )
                );

            if (
                $questionText === '' &&
                empty($question['question_images'])
            ) {
                throw new RuntimeException(
                    "Soal nomor {$number} tidak memiliki isi."
                );
            }

            if ($type === 'multiple_choice') {
                foreach (
                    ['A', 'B', 'C', 'D'] as $key
                ) {
                    if (
                        !isset(
                            $question['options'][$key]
                        )
                    ) {
                        throw new RuntimeException(
                            "Soal nomor {$number} tidak memiliki pilihan {$key}."
                        );
                    }

                    $option = $question['options'][$key];

                    $optionText = trim(
                        strip_tags(
                            html_entity_decode(
                                (string)($option['text'] ?? ''),
                                ENT_QUOTES | ENT_HTML5,
                                'UTF-8'
                            )
                        )
                    );

                    $optionHtml = trim(
                        (string)($option['html'] ?? '')
                    );

                    $optionImages = $option['images'] ?? [];

                    if (
                        $optionText === '' &&
                        $optionHtml === '' &&
                        empty($optionImages)
                    ) {
                        throw new RuntimeException(
                            "Pilihan {$key} pada soal nomor {$number} kosong."
                        );
                    }

                }

                $unexpectedOptions =
                    array_diff(
                        array_keys(
                            $question['options']
                        ),
                        ['A', 'B', 'C', 'D']
                    );

                if (!empty($unexpectedOptions)) {
                    throw new RuntimeException(
                        "Soal nomor {$number} memiliki pilihan jawaban yang tidak didukung."
                    );
                }

                if (
                    !in_array(
                        $question['answer'] ?? null,
                        ['A', 'B', 'C', 'D'],
                        true
                    )
                ) {
                    throw new RuntimeException(
                        "Jawaban soal nomor {$number} tidak valid."
                    );
                }

                continue;
            }

            if (
                !empty($question['options'])
            ) {
                throw new RuntimeException(
                    "Soal nomor {$number} bertipe Benar/Salah tetapi memiliki pilihan A-D."
                );
            }

            if (
                !in_array(
                    $question['answer'] ?? null,
                    ['A', 'B'],
                    true
                )
            ) {
                throw new RuntimeException(
                    "Jawaban soal nomor {$number} untuk jenis Benar/Salah harus A atau B."
                );
            }
        }
    }

    protected function normalizeText(
        ?string $text
    ): string
    {
        $text = $text ?? '';

        $text = str_replace(
            [
                "\xC2\xA0",
                "\xE2\x80\x8B",
                "\xE2\x80\x8C",
                "\xE2\x80\x8D",
                "\xE2\x81\xA0",
                "\xEF\xBB\xBF",
            ],
            ' ',
            $text
        );

        $text = preg_replace(
            '/[\x{200B}-\x{200D}\x{2060}\x{FEFF}]/u',
            '',
            $text
        );

        $text = preg_replace(
            '/[ \t]+/u',
            ' ',
            $text
        );

        $text = preg_replace(
            '/ *\n */u',
            "\n",
            $text
        );

        return trim($text);
    }

    protected function escapeTextAsHtml(
        ?string $text
    ): string
    {
        $text = $this->normalizeText($text);

        if ($text === '') {
            return '';
        }

        return nl2br(
            htmlspecialchars(
                $text,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )
        );
    }

    protected function appendHtml(
        string $current,
        string $append
    ): string
    {
        $current = trim($current);
        $append = trim($append);

        if ($current === '') {
            return $append;
        }

        if ($append === '') {
            return $current;
        }

        return $current .
            '<br>' .
            $append;
    }

    protected function storeMultipleChoiceOptions(
        int   $questionBankId,
        array $question,
        array &$storedFiles
    ): void
    {
        $sortOrder = 1;

        foreach (['A', 'B', 'C', 'D'] as $key) {
            $option = $question['options'][$key];

            $initialContent = trim(
                (string)($option['html'] ?? '')
            );

            if ($initialContent === '') {
                $initialContent = trim(
                    (string)($option['text'] ?? '')
                );
            }

            if ($initialContent === '') {
                $initialContent = ' ';
            }

            $optionModel = $this->repository->createOption([
                'question_bank_id' => $questionBankId,
                'option_key' => $option['key'],
                'option_text' => $initialContent,
                'is_correct' => (
                    $option['key'] === $question['answer']
                ),
                'sort_order' => $sortOrder,
            ]);

            $mediaMap = $this->storeOptionMedia(
                $optionModel->id,
                $option['images'] ?? [],
                $storedFiles
            );

            $optionContent = $this->replaceMediaTokens(
                $initialContent,
                $mediaMap
            );

            $optionModel->update([
                'option_text' => $optionContent,
            ]);

            $sortOrder++;
        }
    }


    protected function storeTrueFalseOptions(
        int   $questionBankId,
        array $question
    ): void
    {
        $options = [
            [
                'key' => 'A',
                'text' => 'Benar',
            ],
            [
                'key' => 'B',
                'text' => 'Salah',
            ],
        ];

        $sortOrder = 1;

        foreach ($options as $option) {
            $this->repository->createOption([
                'question_bank_id' => $questionBankId,
                'option_key' => $option['key'],
                'option_text' => $option['text'],
                'is_correct' => (
                    $option['key'] ===
                    $question['answer']
                ),
                'sort_order' => $sortOrder,
            ]);

            $sortOrder++;
        }
    }

    protected function storeQuestionMedia(
        int   $questionBankId,
        array $images,
        array &$storedFiles
    ): array
    {
        $mediaMap = [];

        foreach ($images as $index => $image) {
            $extension =
                strtolower(
                    $image['extension'] ?: 'png'
                );

            $filename =
                Str::uuid() . '.' . $extension;

            $path =
                "question-banks/{$questionBankId}/{$filename}";

            Storage::disk('public')->put(
                $path,
                $image['content']
            );

            $storedFiles[] = $path;

            $this->repository->createQuestionMedia([
                'question_bank_id' =>
                    $questionBankId,
                'type' => 'image',
                'disk' => 'public',
                'path' => $path,
                'original_name' =>
                    $image['original_name'] ??
                    $filename,
                'mime_type' =>
                    $this->getMimeType(
                        $extension
                    ),
                'file_size' =>
                    strlen($image['content']),
                'source' => 'upload',
                'external_url' => null,
                'sort_order' => $index,
            ]);

            if (
                !empty(
                $image['token']
                )
            ) {
                $mediaMap[$image['token']] = Storage::disk(
                    'public'
                )->url($path);
            }
        }

        return $mediaMap;
    }

    protected function storeOptionMedia(
        int   $questionOptionId,
        array $images,
        array &$storedFiles
    ): array
    {
        $mediaMap = [];

        foreach ($images as $index => $image) {
            $extension =
                strtolower(
                    $image['extension'] ?: 'png'
                );

            $filename =
                Str::uuid() . '.' . $extension;

            $path =
                "question-options/{$questionOptionId}/{$filename}";

            Storage::disk('public')->put(
                $path,
                $image['content']
            );

            $storedFiles[] = $path;

            $this->repository->createOptionMedia([
                'question_option_id' =>
                    $questionOptionId,
                'type' => 'image',
                'disk' => 'public',
                'path' => $path,
                'original_name' =>
                    $image['original_name'] ??
                    $filename,
                'mime_type' =>
                    $this->getMimeType(
                        $extension
                    ),
                'file_size' =>
                    strlen($image['content']),
                'source' => 'upload',
                'external_url' => null,
                'sort_order' => $index,
            ]);

            if (
                !empty(
                $image['token']
                )
            ) {
                $mediaMap[$image['token']] = Storage::disk(
                    'public'
                )->url($path);
            }
        }

        return $mediaMap;
    }

    protected function replaceMediaTokens(
        string $content,
        array  $mediaMap
    ): string
    {
        if (
            $content === '' ||
            empty($mediaMap)
        ) {
            return $content;
        }

        return str_replace(
            array_keys($mediaMap),
            array_values($mediaMap),
            $content
        );
    }

    protected function getMimeType(
        string $extension
    ): string
    {
        return match (
        strtolower($extension)
        ) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'tif', 'tiff' => 'image/tiff',
            default => 'application/octet-stream',
        };
    }
}
