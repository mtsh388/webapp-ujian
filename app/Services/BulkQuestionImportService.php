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
                $options[] = [
                    'key' => $key,
                    'html' => $option['html'] ?? htmlspecialchars((string) ($option['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'is_correct' => $key === ($question['answer'] ?? null),
                    'image_count' => count($option['images'] ?? []),
                ];
            }

            $previewQuestions[] = [
                'number' => $index + 1,
                'type' => $question['type'],
                'question' => $question['question'],
                'explanation' => $question['explanation'],
                'answer' => $question['answer'],
                'options' => $options,
                'image_count' => count($question['question_images'] ?? []),
            ];
        }

        return [
            'total' => count($previewQuestions),
            'multiple_choice' => $multipleChoice,
            'true_false' => $trueFalse,
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

            return $this->buildQuestions($blocks);
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
        if ($html === '') {
            return '';
        }

        if ($start === 0 && $end >= strlen($text)) {
            return $html;
        }

        $plainText = strip_tags($html);

        $plainLength = strlen($plainText);

        if ($plainLength === 0) {
            return '';
        }

        $start = max(
            0,
            min($start, $plainLength)
        );

        $end = max(
            $start,
            min($end, $plainLength)
        );

        if ($start === 0 && $end === $plainLength) {
            return $html;
        }

        return $html;
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
                    $images
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
                $current['explanation'] =
                    $this->appendHtml(
                        $current['explanation'],
                        $this->escapeTextAsHtml($text)
                    );

                continue;
            }

            if ($currentOption !== null) {
                $current['options'][$currentOption]['text'] =
                    $this->appendHtml(
                        $current['options'][$currentOption]['text'],
                        $this->escapeTextAsHtml($text)
                    );

                if (!empty($images)) {
                    $current['options'][$currentOption]['images'] =
                        array_merge(
                            $current['options'][$currentOption]['images'],
                            $images
                        );
                }

                continue;
            }

            $current['question'] =
                $this->appendHtml(
                    $current['question'],
                    $this->escapeTextAsHtml($text)
                );

            if (!empty($images)) {
                $current['question_images'] =
                    array_merge(
                        $current['question_images'],
                        $images
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
        array  $images
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
            $optionHtml = $this->escapeTextAsHtml($optionText);

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
