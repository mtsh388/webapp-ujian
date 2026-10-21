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
        protected QuestionCodeGenerator           $codeGenerator
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
        $multipleSelect = 0;
        $trueFalse = 0;
        $statementTrueFalse = 0;
        $previewQuestions = [];

        foreach ($questions as $index => $question) {
            if ($question['type'] === 'multiple_choice') {
                $multipleChoice++;
            } elseif ($question['type'] === 'multiple_select') {
                $multipleSelect++;
            } elseif ($question['type'] === 'statement_true_false') {
                $statementTrueFalse++;
            } else {
                $trueFalse++;
            }

            $answerKeys = $question['answer_keys']
                ?? (isset($question['answer']) ? [$question['answer']] : []);

            $options = [];
            foreach (($question['options'] ?? []) as $key => $option) {
                $optionHtml = $option['html'] ?? htmlspecialchars((string)($option['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $optionHtml = $this->renderPreviewImages(
                    $optionHtml,
                    $option['images'] ?? []
                );

                $options[] = [
                    'key' => $key,
                    'html' => $optionHtml,
                    'is_correct' => in_array($key, $answerKeys, true),
                    'image_count' => count($option['images'] ?? []),
                ];
            }

            $questionHtml = $this->renderPreviewImages(
                (string)$question['question'],
                $question['question_images'] ?? []
            );

            $explanationHtml = $this->renderPreviewImages(
                (string)$question['explanation'],
                $question['question_images'] ?? []
            );

            $previewQuestions[] = [
                'number' => $index + 1,
                'type' => $question['type'],
                'question' => $questionHtml,
                'explanation' => $explanationHtml,
                'answer' => $question['answer'] ?? null,
                'answer_keys' => $answerKeys,
                'options' => $options,
                'image_count' => count($question['question_images'] ?? []),
            ];
        }

        return [
            'total' => count($previewQuestions),
            'multiple_choice' => $multipleChoice,
            'multiple_select' => $multipleSelect,
            'true_false' => $trueFalse,
            'statement_true_false' => $statementTrueFalse,
            'format' => $isLegacyFormat ? 'template' : 'flexiquiz',
            'conversion_notes' => $isLegacyFormat ? [] : [
                'Soal Benar/Salah dengan beberapa pernyataan tetap menjadi satu soal. Setiap pernyataan memiliki pilihan Benar dan Salah.',
                'Soal dengan lebih dari satu jawaban benar diubah menjadi beberapa soal Benar/Salah per pilihan. Periksa kembali hasil konversi sebelum mengonfirmasi impor.',
                'Soal dengan dua atau tiga pilihan juga dikonversi menjadi soal Benar/Salah per pilihan karena bank soal saat ini menggunakan empat pilihan untuk Pilihan Ganda.',
                'Teks bacaan yang terdeteksi disertakan pada pertanyaan terkait. Format ini tidak mengubah skema jawaban yang sudah digunakan sistem.',
            ],
            'questions' => $previewQuestions,
        ];
    }

    /**
     * Render extracted DOCX images directly in the import preview. The parser
     * uses temporary tokens in HTML until images are stored during confirmation.
     */
    protected function renderPreviewImages(string $html, array $images): string
    {
        foreach ($images as $image) {
            $token = (string)($image['token'] ?? '');
            $content = $image['content'] ?? null;

            if ($token === '' || !is_string($content) || $content === '') {
                continue;
            }

            $extension = strtolower((string)($image['extension'] ?? 'png'));
            $mimeType = $this->getMimeType($extension);
            $dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($content);

            $html = str_replace(
                'src="' . $token . '"',
                'src="' . $dataUri . '"',
                $html
            );
        }

        return $html;
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
                $multipleSelect = 0;
                $trueFalse = 0;
                $statementTrueFalse = 0;

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

                    if (in_array($question['type'], ['multiple_choice', 'multiple_select', 'statement_true_false'], true)) {
                        $this->storeMultipleChoiceOptions(
                            $questionBank->id,
                            $question,
                            $storedFiles
                        );

                        if ($question['type'] === 'multiple_select') {
                            $multipleSelect++;
                        } elseif ($question['type'] === 'statement_true_false') {
                            $statementTrueFalse++;
                        } else {
                            $multipleChoice++;
                        }
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
                    'multiple_select' => $multipleSelect,
                    'true_false' => $trueFalse,
                    'statement_true_false' => $statementTrueFalse,
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

            $xpath->registerNamespace(
                'm',
                'http://schemas.openxmlformats.org/officeDocument/2006/math'
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
                        'is_list' => $content['is_list'] ?? false,
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

            $hasExplicitHeaders = false;

            foreach ($blocks as $block) {
                if (
                    ($block['type'] ?? null) === 'paragraph' &&
                    $this->parseQuestionHeader(
                        $this->normalizeText((string)($block['text'] ?? ''))
                    ) !== null
                ) {
                    $hasExplicitHeaders = true;
                    break;
                }
            }

            if ($hasExplicitHeaders) {
                $questions = $this->buildQuestions($blocks);
                $parserMode = 'explicit_headers';
            } else {
                $hasFlexiQuizQuestions = false;

                foreach ($blocks as $block) {
                    if (
                        ($block['type'] ?? null) === 'paragraph' &&
                        $this->isFlexiQuizQuestionStem(
                            $this->normalizeText((string)($block['text'] ?? ''))
                        )
                    ) {
                        $hasFlexiQuizQuestions = true;
                        break;
                    }
                }

                if ($hasFlexiQuizQuestions) {
                    $questions = $this->buildFlexiQuizQuestions($blocks);
                    $parserMode = 'flexiquiz';
                } else {
                    $questions = $this->buildNativeDocumentQuestions($blocks);
                    $parserMode = 'native_document';
                }
            }

            if (!empty($questions)) {
                $firstQuestion = $questions[0];
                $firstQuestionText = trim(strip_tags(html_entity_decode(
                    (string)($firstQuestion['question'] ?? ''),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                )));

                if (
                    $firstQuestionText === '' &&
                    empty($firstQuestion['question_images']) &&
                    !preg_match('/<img\b[^>]*>/iu', (string)($firstQuestion['question'] ?? ''))
                ) {
                    $blockDiagnostics = [];

                    foreach (array_slice($blocks, 0, 18) as $index => $block) {
                        $blockDiagnostics[] = [
                            'index' => $index,
                            'type' => (string)($block['type'] ?? ''),
                            'text' => mb_substr((string)($block['text'] ?? ''), 0, 240),
                            'image_count' => count($block['images'] ?? []),
                            'html_text' => mb_substr(
                                trim(strip_tags((string)($block['html'] ?? ''))),
                                0,
                                160
                            ),
                        ];
                    }

                    throw new RuntimeException(
                        'DIAGNOSTIK DOCX - isi soal pertama hilang saat parsing. Mode parser: ' .
                        $parserMode .
                        '. Blok sumber: ' .
                        json_encode(
                            $blockDiagnostics,
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        )
                    );
                }
            }

            return $questions;
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

        // Office Math (Equation) is stored in m:oMath/m:oMathPara,
        // not in ordinary w:r/w:t nodes. Include it in document order so
        // inline equations remain positioned correctly in the question,
        // options, and the "Jawaban:" paragraph.
        $runs = $xpath->query(
            './w:r | ./w:hyperlink/w:r | ./m:oMath | ./m:oMathPara',
            $paragraph
        );

        foreach ($runs as $run) {
            if (!$run instanceof DOMElement) {
                continue;
            }

            if (in_array($run->localName, ['oMath', 'oMathPara'], true)) {
                $math = $this->extractOfficeMath($run);

                $text .= $math['text'];
                $html .= $math['html'];

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

            // Word exporters may store answer choices inside grouped shapes or
            // text boxes nested in a drawing. These text nodes are not direct
            // children of w:r, so the normal run extraction above misses them.
            $textBoxNodes = $xpath->query(
                './/w:txbxContent//w:t',
                $run
            );

            foreach ($textBoxNodes as $textBoxNode) {
                $value = $textBoxNode->nodeValue ?? '';
                if ($value === '') {
                    continue;
                }

                $text .= $value;
                $html .= htmlspecialchars(
                    $value,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                );
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
            'is_list' => $xpath->query('./w:pPr/w:numPr', $paragraph)->length > 0,
        ];
    }


    /**
     * Convert a subset of Office Math Markup Language (OMML) into readable
     * text for answer matching and HTML for the import preview.
     *
     * Fractions are normalized as "numerator/denominator" so a key such as
     * 2/3 matches an option authored as the same Word Equation, instead of
     * being flattened into "23".
     */
    protected function extractOfficeMath(DOMElement $mathNode): array
    {
        return $this->renderOfficeMathNode($mathNode);
    }

    protected function renderOfficeMathNode(\DOMNode $node): array
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $value = $node->nodeValue ?? '';

            return [
                'text' => $value,
                'html' => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ];
        }

        if (!$node instanceof DOMElement) {
            return ['text' => '', 'html' => ''];
        }

        $name = $node->localName;

        if ($name === 't') {
            $value = $node->textContent;

            return [
                'text' => $value,
                'html' => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ];
        }

        // Property nodes only describe equation formatting; they do not
        // contribute visible content.
        if (str_ends_with($name, 'Pr')) {
            return ['text' => '', 'html' => ''];
        }

        if ($name === 'f') {
            $numeratorNode = null;
            $denominatorNode = null;

            foreach ($node->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }

                if ($child->localName === 'num') {
                    $numeratorNode = $child;
                } elseif ($child->localName === 'den') {
                    $denominatorNode = $child;
                }
            }

            $numerator = $numeratorNode
                ? $this->renderOfficeMathChildren($numeratorNode)
                : ['text' => '', 'html' => ''];
            $denominator = $denominatorNode
                ? $this->renderOfficeMathChildren($denominatorNode)
                : ['text' => '', 'html' => ''];

            return [
                'text' => $numerator['text'] . '/' . $denominator['text'],
                'html' => '<span class="question-word-math-fraction" style="display:inline-flex;flex-direction:column;align-items:center;justify-content:center;vertical-align:middle;line-height:1.05;text-align:center;margin:0 0.08em;white-space:nowrap;">' .
                    '<span class="question-word-math-numerator" style="display:block;padding:0 0.12em 0.08em;">' .
                    $numerator['html'] .
                    '</span><span class="question-word-math-divider" aria-hidden="true" style="display:block;align-self:stretch;border-top:1px solid currentColor;height:0;min-width:0.8em;"></span><span class="question-word-math-denominator" style="display:block;padding:0.08em 0.12em 0;">' .
                    $denominator['html'] .
                    '</span></span>',
            ];
        }

        if ($name === 'sSup' || $name === 'sSub') {
            $baseNode = null;
            $scriptNode = null;

            foreach ($node->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }

                if ($child->localName === 'e') {
                    $baseNode = $child;
                } elseif ($child->localName === ($name === 'sSup' ? 'sup' : 'sub')) {
                    $scriptNode = $child;
                }
            }

            $base = $baseNode
                ? $this->renderOfficeMathChildren($baseNode)
                : ['text' => '', 'html' => ''];
            $script = $scriptNode
                ? $this->renderOfficeMathChildren($scriptNode)
                : ['text' => '', 'html' => ''];

            $tag = $name === 'sSup' ? 'sup' : 'sub';

            return [
                'text' => $base['text'] . $script['text'],
                'html' => $base['html'] . '<' . $tag . '>' . $script['html'] . '</' . $tag . '>',
            ];
        }

        if ($name === 'rad') {
            $degreeNode = null;
            $expressionNode = null;

            foreach ($node->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }

                if ($child->localName === 'deg') {
                    $degreeNode = $child;
                } elseif ($child->localName === 'e') {
                    $expressionNode = $child;
                }
            }

            $degree = $degreeNode
                ? $this->renderOfficeMathChildren($degreeNode)
                : ['text' => '', 'html' => ''];
            $expression = $expressionNode
                ? $this->renderOfficeMathChildren($expressionNode)
                : ['text' => '', 'html' => ''];

            return [
                'text' => ($degree['text'] !== '' ? $degree['text'] : '√') . $expression['text'],
                'html' => '<span class="question-word-math-radical">' .
                    ($degree['html'] !== '' ? '<sup>' . $degree['html'] . '</sup>' : '') .
                    '√' . $expression['html'] . '</span>',
            ];
        }

        return $this->renderOfficeMathChildren($node);
    }

    protected function renderOfficeMathChildren(\DOMNode $node): array
    {
        $text = '';
        $html = '';

        foreach ($node->childNodes as $child) {
            $part = $this->renderOfficeMathNode($child);
            $text .= $part['text'];
            $html .= $part['html'];
        }

        return ['text' => $text, 'html' => $html];
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


    /**
     * Parses the source-style TKA DOCX without requiring users to add
     * artificial SOAL/A-D/JAWABAN markers. The document's own "Jawaban:"
     * lines delimit question records; the last up-to-four text blocks before
     * each answer are treated as answer choices.
     */
    protected function buildNativeDocumentQuestions(array $blocks): array
    {
        $normalizedBlocks = [];
        $consumedAnswerListBlocks = [];

        foreach ($blocks as $blockIndex => $block) {
            if (isset($consumedAnswerListBlocks[$blockIndex])) {
                continue;
            }

            if (($block['type'] ?? null) !== 'paragraph') {
                $normalizedBlocks[] = $block;
                continue;
            }

            $text = (string)($block['text'] ?? '');
            if (preg_match('/^(.*?)\bJawaban\s*:\s*(.*)$/isu', $text, $matches)) {
                $before = trim($matches[1]);
                $answer = trim($matches[2]);

                // Multi-select keys can be a blank "Jawaban:" paragraph followed
                // by Word's numbered/bulleted list containing the correct options.
                if ($answer === '') {
                    $answerItems = [];

                    for ($nextIndex = $blockIndex + 1; isset($blocks[$nextIndex]); $nextIndex++) {
                        $nextBlock = $blocks[$nextIndex];

                        if (
                            ($nextBlock['type'] ?? null) !== 'paragraph' ||
                            empty($nextBlock['is_list'])
                        ) {
                            break;
                        }

                        $itemText = $this->normalizeText((string)($nextBlock['text'] ?? ''));
                        if ($itemText === '') {
                            continue;
                        }

                        $answerItems[] = preg_replace('/^\s*[-•]\s*/u', '', $itemText);
                        $consumedAnswerListBlocks[$nextIndex] = true;
                    }

                    $answer = implode("\n", $answerItems);
                }

                if ($before !== '') {
                    $beforeBlock = $block;
                    $beforeBlock['text'] = $before;
                    $beforeBlock['html'] = $this->escapeTextAsHtml($before);
                    $normalizedBlocks[] = $beforeBlock;
                }

                $answerBlock = [
                    'type' => 'paragraph',
                    'text' => 'Jawaban: ' . $answer,
                    'html' => $this->escapeTextAsHtml('Jawaban: ' . $answer),
                    'images' => [],
                ];
                $normalizedBlocks[] = $answerBlock;
                continue;
            }

            $normalizedBlocks[] = $block;
        }

        $questions = [];
        $segment = [];

        foreach ($normalizedBlocks as $block) {
            $text = $this->normalizeText((string)($block['text'] ?? ''));
            if (
                ($block['type'] ?? null) === 'paragraph' &&
                preg_match('/^Jawaban\s*:\s*(.*)$/isu', $text, $answerMatch)
            ) {
                $question = $this->buildNativeQuestionFromSegment(
                    $segment,
                    trim($answerMatch[1])
                );

                if ($question !== null) {
                    $questions[] = $question;
                }

                $segment = [];
                continue;
            }

            $segment[] = $block;
        }

        return $questions;
    }

    protected function buildNativeQuestionFromSegment(
        array  $segment,
        string $answerText
    ): ?array
    {
        $paragraphs = [];

        foreach ($segment as $block) {
            if (($block['type'] ?? null) === 'table') {
                $paragraphs[] = [
                    'type' => 'table',
                    'text' => trim(strip_tags((string)($block['html'] ?? ''))),
                    'html' => (string)($block['html'] ?? ''),
                    'images' => $block['images'] ?? [],
                ];
                continue;
            }

            if (($block['type'] ?? null) !== 'paragraph') {
                continue;
            }

            $text = $this->normalizeText((string)($block['text'] ?? ''));
            if ($text === '' && empty($block['images'])) {
                continue;
            }

            if (
                preg_match('/^\d+\s+of\s+\d+$/iu', $text) ||
                str_contains(mb_strtolower($text), 'simulasi pengenalan tka') ||
                str_contains(mb_strtolower($text), 'flexiquiz')
            ) {
                continue;
            }

            $paragraphs[] = $block;
        }

        if (count($paragraphs) < 2) {
            return null;
        }

        $optionBlocks = [];
        $stemBlocks = $paragraphs;

        // The source document has unlabelled choices. In some DOCX paragraphs,
        // equation choices or numeric choices are merged into one text run.
        $last = array_pop($stemBlocks);
        $lastText = $this->normalizeText((string)($last['text'] ?? ''));
        $splitLast = $this->splitNativeMergedOptions($lastText);

        if (count($splitLast) >= 2) {
            // A Word paragraph can contain both a figure and OCR text for
            // answer choices. Keep the figure with the question stem.
            if (!empty($last['images'])) {
                $last['text'] = '';
                $last['html'] = '';
                $stemBlocks[] = $last;
            }

            foreach ($splitLast as $text) {
                $optionBlocks[] = [
                    'type' => 'paragraph',
                    'text' => $text,
                    'html' => $this->escapeTextAsHtml($text),
                    'images' => [],
                ];
            }
        } else {
            $stemBlocks[] = $last;

            while (count($stemBlocks) > 0 && count($optionBlocks) < 4) {
                $candidate = array_pop($stemBlocks);
                $candidateText = $this->normalizeText((string)($candidate['text'] ?? ''));

                if ($candidateText === '' && empty($candidate['images'])) {
                    continue;
                }

                if (($candidate['type'] ?? null) === 'table') {
                    $stemBlocks[] = $candidate;
                    break;
                }

                $split = $this->splitNativeMergedOptions($candidateText);
                if (count($split) > 1) {
                    foreach (array_reverse($split) as $text) {
                        array_unshift($optionBlocks, [
                            'type' => 'paragraph',
                            'text' => $text,
                            'html' => $this->escapeTextAsHtml($text),
                            'images' => [],
                        ]);
                    }
                    break;
                }

                array_unshift($optionBlocks, $candidate);

                // The option rows in this source document are usually short.
                if (count($optionBlocks) >= 4) {
                    break;
                }

                if (
                    preg_match('/^(?:pilih semua jawaban benar|jawaban benar lebih dari satu|berikut ini|tentukan benar atau salah)/iu', $candidateText)
                ) {
                    array_shift($optionBlocks);
                    $stemBlocks[] = $candidate;
                    break;
                }
            }
        }

        if (count($optionBlocks) < 2) {
            return null;
        }

        $isMulti = false;
        foreach ($stemBlocks as $block) {
            $text = mb_strtolower((string)($block['text'] ?? ''));
            if (str_contains($text, 'pilih semua jawaban benar') || str_contains($text, 'jawaban benar lebih dari satu')) {
                $isMulti = true;
                break;
            }
        }

        // Preserve tables and images in the question stem.
        $questionHtml = '';
        $questionImages = [];
        foreach ($stemBlocks as $block) {
            if (($block['type'] ?? null) === 'table') {
                $questionHtml = $this->appendHtml($questionHtml, (string)($block['html'] ?? ''));
                $questionImages = array_merge($questionImages, $block['images'] ?? []);
                continue;
            }

            $formattedImages = [];
            $html = $this->convertParagraphToHtml($block, $formattedImages);
            $questionHtml = $this->appendHtml($questionHtml, $html);
            $questionImages = array_merge($questionImages, $formattedImages);
        }

        $options = [];
        foreach (array_values($optionBlocks) as $index => $block) {
            $key = chr(65 + $index);
            $optionImages = [];
            if (($block['type'] ?? null) === 'table') {
                $questionHtml = $this->appendHtml($questionHtml, (string)($block['html'] ?? ''));
                $questionImages = array_merge($questionImages, $block['images'] ?? []);
                continue;
            } else {
                $optionText = $this->normalizeText((string)($block['text'] ?? ''));
                $optionHtml = $this->convertParagraphToHtml($block, $optionImages);
            }

            $options[$key] = [
                'key' => $key,
                'text' => $optionText,
                'html' => $optionHtml !== '' ? $optionHtml : $this->escapeTextAsHtml($optionText),
                'images' => $optionImages,
            ];
        }

        // Recover stem text if Word's nested drawing/group layout caused the
        // regular HTML conversion to return no textual content.
        if (trim(strip_tags($questionHtml)) === '') {
            $optionTexts = array_map(
                fn($option) => $this->normalizeAnswerForMatching((string)($option['text'] ?? '')),
                $options
            );

            // Some Word exporters produce a segment where the regular stem
            // collector loses its text blocks. Retry against the full answer
            // segment, while excluding recognized choices and merged choice
            // rows such as "34563456".
            $fallbackParts = [];
            $fallbackSourceBlocks = !empty($stemBlocks) ? $stemBlocks : $segment;

            if (trim(implode('', array_map(
                    fn($block) => (string)($block['text'] ?? ''),
                    $fallbackSourceBlocks
                ))) === '') {
                $fallbackSourceBlocks = $segment;
            }

            foreach ($fallbackSourceBlocks as $block) {
                if (($block['type'] ?? null) === 'table') {
                    $tableText = trim(strip_tags((string)($block['html'] ?? '')));
                    if ($tableText !== '') {
                        $fallbackParts[] = (string)($block['html'] ?? '');
                    }
                    continue;
                }

                $text = $this->normalizeText((string)($block['text'] ?? ''));
                $lowerText = mb_strtolower($text);

                if (
                    $text === '' ||
                    str_contains($lowerText, 'simulasi pengenalan tka') ||
                    str_contains($lowerText, 'flexiquiz') ||
                    preg_match('/^jawaban\\s*:/iu', $text)
                ) {
                    continue;
                }

                $normalizedText = $this->normalizeAnswerForMatching($text);
                $splitChoices = $this->splitNativeMergedOptions($text);
                $isMergedChoiceRow = count($splitChoices) >= 2 &&
                    count(array_filter(
                        $splitChoices,
                        fn($choice) => in_array(
                            $this->normalizeAnswerForMatching($choice),
                            $optionTexts,
                            true
                        )
                    )) === count($splitChoices);

                if (
                    in_array($normalizedText, $optionTexts, true) ||
                    $isMergedChoiceRow
                ) {
                    continue;
                }

                $fallbackParts[] = $this->escapeTextAsHtml($text);
            }

            $questionHtml = implode('<br>', array_filter($fallbackParts, fn($part) => trim($part) !== ''));
        }

        // Recovery for DOCX layouts where the question stem is accidentally
        // classified as option A and the actual choices follow it. This can
        // happen when equations or inline math are split into separate runs.
        if (trim(strip_tags($questionHtml)) === '' && count($options) >= 3) {
            $firstOptionText = $this->normalizeText((string)($options['A']['text'] ?? ''));
            $looksLikeStem = mb_strlen($firstOptionText) >= 35 &&
                preg_match('/[?…]|\\b(?:nilai dari|suatu persamaan|perhatikan|diketahui|besar|tentukan)\\b/iu', $firstOptionText);

            if ($looksLikeStem) {
                $questionHtml = $this->escapeTextAsHtml($firstOptionText);
                unset($options['A']);
                $reindexedOptions = [];
                foreach (array_values($options) as $index => $option) {
                    $key = chr(65 + $index);
                    $option['key'] = $key;
                    $reindexedOptions[$key] = $option;
                }
                $options = $reindexedOptions;
            }
        }

        $normalizedAnswer = $this->normalizeAnswerForMatching($answerText);
        $answerKeys = [];

        foreach ($options as $key => $option) {
            $normalizedOption = $this->normalizeAnswerForMatching((string)$option['text']);
            if ($normalizedOption === '') {
                continue;
            }

            if (
                $normalizedAnswer !== '' &&
                ($normalizedAnswer === $normalizedOption || str_contains($normalizedAnswer, $normalizedOption))
            ) {
                $answerKeys[] = $key;
            }
        }

        if (!$isMulti && count($answerKeys) > 1) {
            // Avoid false matches such as "5" matching "15%".
            $exactMatches = [];
            foreach ($options as $key => $option) {
                if ($this->normalizeAnswerForMatching((string)$option['text']) === $normalizedAnswer) {
                    $exactMatches[] = $key;
                }
            }
            $answerKeys = count($exactMatches) === 1 ? $exactMatches : [];
        }

        return [
            'type' => $isMulti ? 'multiple_select' : 'multiple_choice',
            'question' => $questionHtml,
            'question_images' => $questionImages,
            'options' => $options,
            'answer' => $answerKeys[0] ?? null,
            'answer_keys' => $answerKeys,
            'explanation' => '',
        ];
    }

    protected function splitNativeMergedOptions(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        if (preg_match('/^(?:[1-6]){8}$/u', preg_replace('/\s+/u', '', $text), $m)) {
            $compact = preg_replace('/\s+/u', '', $text);
            if (substr($compact, 0, 4) === substr($compact, 4, 4)) {
                return str_split(substr($compact, 0, 4));
            }
        }

        $equationParts = preg_split('/(?=y\s*=)/iu', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($equationParts) >= 2) {
            return array_values(array_filter(array_map('trim', $equationParts)));
        }

        $lines = preg_split('/\r\n|\r|\n/u', $text);
        if (count($lines) > 1) {
            $lines = array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== ''));
            if (count($lines) >= 2 && count($lines) <= 4) {
                return $lines;
            }
        }

        return [$text];
    }

    protected function normalizeAnswerForMatching(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_strtolower($text);
        $text = preg_replace('/[\s\p{P}\p{Z}]+/u', '', $text);
        return trim($text);
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
                    '/^\s*JAWABAN\s*[:.]?\s*([A-D](?:\s*[,;]\s*[A-D])*)(?:\s+(.*))?$/iu',
                    $text,
                    $matches
                )
            ) {
                $answerKeys = preg_split('/\s*[,;]\s*/u', strtoupper(trim($matches[1])));
                $answerKeys = array_values(array_unique(array_filter($answerKeys, fn($key) => in_array($key, ['A', 'B', 'C', 'D'], true))));

                $current['answer_keys'] = $answerKeys;
                $current['answer'] = count($answerKeys) === 1 ? $answerKeys[0] : null;

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
                in_array($current['type'], ['multiple_choice', 'multiple_select'], true)
            ) {
                $optionResult = $this->extractOptions(
                    $rawText,
                    $images,
                    (string)($block['html'] ?? '')
                );

                if (
                    !empty(
                    $optionResult['options']
                    )
                ) {
                    if ($current['type'] === null) {
                        $current['type'] = 'multiple_choice';
                    }

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

            $text = $this->normalizeText((string)($block['text'] ?? ''));

            if ($text === '' || preg_match('/^FlexiQuiz\s*-\s*Editor/i', $text)) {
                continue;
            }

            if (
                preg_match(
                    '/^(.+?)(?=(?:Mengapa|Apa yang membuat)\b)/iu',
                    $text,
                    $sourceMatch,
                    PREG_OFFSET_CAPTURE
                )
                && preg_match('/^Sumber\s*:/iu', $text)
            ) {
                $splitOffset = strlen((string)($sourceMatch[1][0] ?? ''));
                $sourceText = trim(substr($text, 0, $splitOffset));
                $questionText = trim(substr($text, $splitOffset));

                if ($sourceText !== '' && $questionText !== '') {
                    $sourceBlock = $block;
                    $sourceBlock['text'] = $sourceText;
                    $sourceBlock['html'] = $this->escapeTextAsHtml($sourceText);
                    $questionBlock = $block;
                    $questionBlock['text'] = $questionText;
                    $questionBlock['html'] = $this->escapeTextAsHtml($questionText);
                    $questionBlock['images'] = [];
                    $paragraphs[] = $sourceBlock;
                    $paragraphs[] = $questionBlock;
                    continue;
                }
            }

            $paragraphs[] = $block;
        }

        $questions = [];
        $context = [];
        $count = count($paragraphs);
        $index = 0;

        while ($index < $count) {
            $text = $this->normalizeText((string)($paragraphs[$index]['text'] ?? ''));

            if (preg_match('/^Bacalah\b/iu', $text)) {
                $context = [];
                $seenSource = false;

                while ($index < $count) {
                    $candidate = $this->normalizeText((string)($paragraphs[$index]['text'] ?? ''));

                    if (preg_match('/^Sumber(?:\s+teks)?\s*:/iu', $candidate)) {
                        $seenSource = true;
                        $context[] = $paragraphs[$index];
                        $index++;
                        continue;
                    }

                    if ($this->isFlexiQuestionCandidate($paragraphs, $index)) {
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

                $text = $this->normalizeText((string)($paragraphs[$index]['text'] ?? ''));
            }

            if (!$this->isFlexiQuestionCandidate($paragraphs, $index)) {
                $index++;
                continue;
            }

            $stemBlock = $paragraphs[$index];
            $stemText = $text;
            $stemImages = [];
            $stemHtml = $this->flexiBlockHtml($stemBlock, $stemImages);
            $contextHtml = '';
            $contextImages = [];

            foreach ($context as $contextBlock) {
                $blockImages = [];
                $contextHtml = $this->appendHtml(
                    $contextHtml,
                    $this->flexiBlockHtml($contextBlock, $blockImages)
                );
                $contextImages = array_merge($contextImages, $blockImages);
            }

            $questionHtml = $contextHtml !== ''
                ? $this->appendHtml($contextHtml, $stemHtml)
                : $stemHtml;

            $questionImages = array_merge($contextImages, $stemImages ?? []);
            $index++;

            $marker = preg_match(
                '/tentukan.*benar\s+atau\s+salah|pilih\s+jawaban\s+benar\s+atau\s+salah|pilihan\s+benar\s+atau\s+salah|benar\s+atau\s+salah\s+pada\s+setiap\s+pernyataan/i',
                $stemText
            ) ? 'statement_true_false' : '';
            $markerIndex = $marker === 'statement_true_false' ? $index - 1 : $index;

            for ($lookahead = $index; $marker === '' && $lookahead < min($count, $index + 3); $lookahead++) {
                $markerText = $this->normalizeText((string)($paragraphs[$lookahead]['text'] ?? ''));

                if (preg_match('/jawaban\s+benar\s+lebih\s+dari\s+satu|pilih\s+semua\s+jawaban/i', $markerText)) {
                    $marker = 'multiple_select';
                    $markerIndex = $lookahead;
                    break;
                }

                if (preg_match('/tentukan.*benar\s+atau\s+salah|pilih\s+jawaban\s+benar\s+atau\s+salah|pilihan\s+benar\s+atau\s+salah|benar\s+atau\s+salah\s+pada\s+setiap\s+pernyataan/i', $markerText)) {
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
                    $itemText = $this->normalizeText((string)($itemBlock['text'] ?? ''));

                    if ($this->isFlexiQuestionCandidate($paragraphs, $index) && !$this->containsFlexiAnswer($itemText)) {
                        break;
                    }

                    [$itemPrefix, $itemAnswer] = $this->splitFlexiAnswer($itemText);

                    if ($itemPrefix !== '') {
                        $itemContentBlock = $itemBlock;

                        if ($itemAnswer !== null) {
                            $itemContentBlock['text'] = $itemPrefix;
                            $itemContentBlock['html'] = $this->escapeTextAsHtml($itemPrefix);
                        }

                        $items[] = [
                            'text' => $itemPrefix,
                            'block' => $itemContentBlock,
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

                    if (count($truthValues) !== count($items) || count($items) < 2) {
                        throw new RuntimeException(
                            'Jumlah jawaban Benar/Salah tidak sesuai dengan jumlah pernyataan pada soal: ' . strip_tags($stemText)
                        );
                    }

                    $statementOptions = [];
                    $answerKeys = [];

                    foreach ($items as $itemIndex => $item) {
                        $statementNumber = $itemIndex + 1;
                        $trueKey = 'S' . $statementNumber . 'T';
                        $falseKey = 'S' . $statementNumber . 'F';
                        $itemImages = [];
                        $itemHtml = $this->flexiBlockHtml($item['block'], $itemImages);
                        $statementText = $this->normalizeText((string)($item['text'] ?? ''));

                        $statementOptions[$trueKey] = [
                            'key' => $trueKey,
                            'text' => $statementText,
                            'html' => $itemHtml,
                            'images' => $itemImages,
                        ];

                        $statementOptions[$falseKey] = [
                            'key' => $falseKey,
                            'text' => $statementText,
                            'html' => $this->escapeTextAsHtml($statementText),
                            'images' => [],
                        ];

                        $answerKeys[] = $truthValues[$itemIndex] ? $trueKey : $falseKey;
                    }

                    $questions[] = [
                        'type' => 'statement_true_false',
                        'question' => $questionHtml,
                        'question_images' => $questionImages,
                        'options' => $statementOptions,
                        'answer_keys' => $answerKeys,
                        'answer' => null,
                        'explanation' => '',
                    ];

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
                $optionText = $this->normalizeText((string)($optionBlock['text'] ?? ''));

                if ($this->isFlexiQuestionCandidate($paragraphs, $index) && !$this->containsFlexiAnswer($optionText)) {
                    break;
                }

                [$optionPrefix, $itemAnswer] = $this->splitFlexiAnswer($optionText);

                if ($optionPrefix !== '') {
                    $key = chr(65 + $optionCount);
                    $optionLabelPattern = '/^' . preg_quote($key, '/') . '\\s*[.)、:：-]\\s*/u';
                    $hasOptionLabel = preg_match($optionLabelPattern, $optionPrefix) === 1;

                    if ($hasOptionLabel) {
                        $optionPrefix = preg_replace($optionLabelPattern, '', $optionPrefix, 1);
                    }

                    $optionImagesForBlock = [];
                    $optionHtml = $this->flexiBlockHtml($optionBlock, $optionImagesForBlock);

                    if ($itemAnswer !== null || $hasOptionLabel) {
                        $optionHtml = $this->escapeTextAsHtml($optionPrefix);
                    }

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

            if ($answerText === '') {
                if ($index < $count) {
                    [$nextPrefix, $nextAnswer] = $this->splitFlexiAnswer(
                        $this->normalizeText((string)($paragraphs[$index]['text'] ?? ''))
                    );

                    if ($nextAnswer !== null) {
                        $answerText = $nextAnswer;
                        $index++;
                    }
                }
            }

            if (count($options) < 2) {
                throw new RuntimeException(
                    'Pilihan jawaban tidak cukup untuk soal: ' . strip_tags($stemText)
                );
            }

            $answerKey = $this->matchFlexiAnswerKey($answerText, $options);

            if ($answerKey === null) {
                throw new RuntimeException(
                    'Kunci jawaban tidak cocok dengan pilihan pada soal: ' . strip_tags($stemText)
                );
            }

            if (count($options) === 4) {
                $questions[] = [
                    'type' => 'multiple_choice',
                    'question' => $questionHtml,
                    'question_images' => $questionImages,
                    'options' => $options,
                    'answer' => $answerKey,
                    'explanation' => '',
                ];
            } else {
                foreach ($options as $key => $option) {
                    $optionPrompt = $this->appendHtml(
                        $questionHtml,
                        '<p>Apakah pilihan berikut merupakan jawaban yang tepat?</p>'
                    );
                    $optionPrompt = $this->appendHtml(
                        $optionPrompt,
                        '<blockquote>' . $option['html'] . '</blockquote>'
                    );

                    $questions[] = $this->makeFlexiTrueFalseQuestion(
                        $optionPrompt,
                        $key === $answerKey ? 'A' : 'B',
                        array_merge($questionImages, $option['images'] ?? [])
                    );
                }
            }
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
        $text = $this->normalizeText((string)($paragraphs[$index]['text'] ?? ''));

        if (!$this->isFlexiQuizQuestionStem($text)) {
            return false;
        }

        $limit = min(count($paragraphs), $index + 10);

        for ($lookahead = $index + 1; $lookahead < $limit; $lookahead++) {
            $candidate = $this->normalizeText((string)($paragraphs[$lookahead]['text'] ?? ''));

            if ($this->containsFlexiAnswer($candidate)) {
                return true;
            }

            if ($this->isFlexiQuizQuestionStem($candidate)) {
                break;
            }

            if (preg_match('/jawaban\s+benar\s+lebih\s+dari\s+satu|pilih\s+semua\s+jawaban|tentukan.*benar\s+atau\s+salah|pilih\s+jawaban\s+benar\s+atau\s+salah|pilihan\s+benar\s+atau\s+salah|benar\s+atau\s+salah\s+pada\s+setiap\s+pernyataan/i', $candidate)) {
                for ($answerLookahead = $lookahead + 1; $answerLookahead < $limit; $answerLookahead++) {
                    if ($this->containsFlexiAnswer($this->normalizeText((string)($paragraphs[$answerLookahead]['text'] ?? '')))) {
                        return true;
                    }
                }
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
                '/^(Apa|Apakah|Mengapa|Bagaimana|Siapa|Kepada siapa|Kata manakah|Kalimat manakah|Pernyataan manakah|Informasi yang|Nada dan suasana|Berdasarkan teks|Setelah membaca|Tentukanlah|Tentukan|Pesan apa|Apa sajakah|Apa saja|Alat apa|Hal apa|Yang menjadi|Yang paling tepat|Makna dari|Apa makna|Manakah|Informasi yang tidak tepat|Apa yang)/iu',
                $text
            ) === 1;
    }

    protected function flexiBlockHtml(array $block, array &$images): string
    {
        $images = [];
        $html = $this->convertParagraphToHtml($block, $images);

        if ($html === '') {
            $html = $this->escapeTextAsHtml((string)($block['text'] ?? ''));
        }

        return $html;
    }

    protected function splitFlexiAnswer(string $text): array
    {
        if (!preg_match('/Jawaban\s*:\s*/iu', $text, $matches, PREG_OFFSET_CAPTURE)) {
            return [$this->normalizeText($text), null];
        }

        $offset = $matches[0][1];
        $prefix = $this->normalizeText(substr($text, 0, $offset));
        $answer = $this->normalizeText(substr($text, $offset + strlen($matches[0][0])));

        return [$prefix, $answer];
    }

    protected function containsFlexiAnswer(string $text): bool
    {
        return preg_match('/Jawaban\s*:/iu', $text) === 1;
    }

    protected function parseFlexiTruthValues(string $answer): array
    {
        preg_match_all('/\b(Benar|Salah)\b/iu', $answer, $matches);

        return array_map(
            fn(string $value): bool => mb_strtolower($value) === 'benar',
            $matches[1] ?? []
        );
    }

    protected function splitFlexiCombinedStatements(array $items, int $expected): array
    {
        if (count($items) !== 1 || $expected < 2) {
            return $items;
        }

        $text = trim((string)($items[0]['text'] ?? ''));
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
            fn(array $match): int => (int)$match[1],
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
            array_map(fn(string $item): string => trim($item), $items),
            fn(string $item): bool => $item !== ''
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
            $normalizedOption = $this->normalizeFlexiComparable((string)($option['text'] ?? ''));

            if ($normalizedAnswer !== '' && $normalizedAnswer === $normalizedOption) {
                return $key;
            }
        }

        foreach ($options as $key => $option) {
            $normalizedOption = $this->normalizeFlexiComparable((string)($option['text'] ?? ''));

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
                '/^SOAL(?:\s+\d+)?\s*(?:\[(PILIHAN_GANDA_KOMPLEKS|PILIHAN_GANDA|BENAR_SALAH)\])?\s*[:.]?\s*(.*?)$/iu',
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
                'PILIHAN_GANDA_KOMPLEKS' => 'multiple_select',
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
                    'multiple_select',
                    'statement_true_false',
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
                        'multiple_select',
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

            $hasInlineImage = preg_match('/<img\b[^>]*>/iu', (string)($question['question'] ?? '')) === 1;

            if (
                $questionText === '' &&
                empty($question['question_images']) &&
                !$hasInlineImage
            ) {
                $diagnostic = [
                    'question_html' => (string)($question['question'] ?? ''),
                    'question_images_count' => count($question['question_images'] ?? []),
                    'option_texts' => array_map(
                        fn($option) => (string)($option['text'] ?? ''),
                        $question['options'] ?? []
                    ),
                    'type' => $type,
                ];

                throw new RuntimeException(
                    "DIAGNOSTIK impor Word - soal nomor {$number} tidak memiliki isi. Data hasil parsing: " .
                    json_encode($diagnostic, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }

            if (in_array($type, ['multiple_choice', 'multiple_select', 'statement_true_false'], true)) {
                if (count($question['options'] ?? []) < 2) {
                    throw new RuntimeException("Soal nomor {$number} harus memiliki minimal dua pilihan.");
                }

                foreach (($question['options'] ?? []) as $key => $option) {

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

                $allowedOptionKeys = $type === 'statement_true_false'
                    ? array_filter(array_keys($question['options']), fn($key) => preg_match('/^S[1-9][0-9]*[TF]$/', (string) $key) === 1)
                    : array_intersect(array_keys($question['options']), ['A', 'B', 'C', 'D']);

                $unexpectedOptions = array_diff(array_keys($question['options']), $allowedOptionKeys);

                if (!empty($unexpectedOptions)) {
                    throw new RuntimeException(
                        "Soal nomor {$number} memiliki pilihan jawaban yang tidak didukung."
                    );
                }

                $answerKeys = $question['answer_keys']
                    ?? (isset($question['answer']) ? [$question['answer']] : []);

                if ($type === 'multiple_select' && count($answerKeys) < 2) {
                    throw new RuntimeException("Soal pilihan ganda kompleks nomor {$number} harus memiliki minimal dua kunci jawaban, misalnya JAWABAN: A, C.");
                }

                if ($type === 'statement_true_false') {
                    $groups = [];
                    foreach (array_keys($question['options'] ?? []) as $optionKey) {
                        if (preg_match('/^S([1-9][0-9]*)([TF])$/', (string) $optionKey, $matches)) {
                            $groups[$matches[1]][$matches[2]] = true;
                        }
                    }

                    foreach ($groups as $group) {
                        if (empty($group['T']) || empty($group['F'])) {
                            throw new RuntimeException("Setiap pernyataan pada soal nomor {$number} harus memiliki pilihan Benar dan Salah.");
                        }
                    }

                    if (count($groups) < 2 || count($answerKeys) !== count($groups)) {
                        throw new RuntimeException("Kunci jawaban soal pernyataan nomor {$number} tidak sesuai dengan jumlah pernyataan.");
                    }
                }

                if (array_diff($answerKeys, array_keys($question['options'] ?? []))) {
                    throw new RuntimeException("Kunci jawaban soal nomor {$number} tidak cocok dengan pilihan yang tersedia.");
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

        $answerKeys = $question['answer_keys']
            ?? (isset($question['answer']) ? [$question['answer']] : []);

        foreach ($question['options'] as $key => $option) {

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
                'is_correct' => in_array($option['key'], $answerKeys, true),
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
