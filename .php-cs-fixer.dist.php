<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE.md  ISC License
 */

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects -- This configuration defines a fixer and returns the tool configuration.

namespace SoftCreatR\CodingStandards;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Separates completed control structures from the following statement.
 *
 * Token matching leaves closures, strings, nested closing braces and connected
 * else/catch/finally branches unchanged. Trailing comments stay with their block.
 */
final class SoftCreatRBlankLineAfterControlStructureFixer extends AbstractFixer implements
    WhitespacesAwareFixerInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'SoftCreatR/blank_line_after_control_structure';
    }

    /**
     * Runs after the standard brace and blank-line normalization rules.
     */
    public function getPriority(): int
    {
        return -30;
    }

    /**
     * @inheritDoc
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'A blank line must follow a completed control structure before another statement.',
            [new CodeSample("<?php\nif (\$ready) {\n    work();\n}\n\$result = nextTask();\n")],
        );
    }

    /**
     * @inheritDoc
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_IF, T_FOR, T_FOREACH, T_WHILE, T_DO, T_SWITCH, T_TRY]);
    }

    /**
     * Finds completed brace-based control structures and updates whitespace only.
     */
    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            if (!$tokens[$index]->equals('}')) {
                continue;
            }

            $start = $tokens->findBlockStart(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
            $head = $tokens->getPrevMeaningfulToken($start);

            if ($head !== null && $tokens[$head]->equals(')')) {
                $head = $tokens->getPrevMeaningfulToken(
                    $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $head),
                );
            }

            if (
                $head === null
                || !$tokens[$head]->isGivenKind([
                    T_IF,
                    T_ELSEIF,
                    T_ELSE,
                    T_FOR,
                    T_FOREACH,
                    T_WHILE,
                    T_DO,
                    T_SWITCH,
                    T_TRY,
                    T_CATCH,
                    T_FINALLY,
                ])
            ) {
                continue;
            }

            $end = $index;
            $next = $tokens->getNextMeaningfulToken($end);

            // A do/while statement ends at the semicolon after its condition.
            if ($tokens[$head]->isGivenKind(T_DO) && $next !== null && $tokens[$next]->isGivenKind(T_WHILE)) {
                $condition = $tokens->getNextMeaningfulToken($next);
                $end = $tokens->getNextMeaningfulToken(
                    $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $condition),
                );
                $next = $tokens->getNextMeaningfulToken($end);
            }

            if (
                $next === null
                || $tokens[$next]->equalsAny(['}', ';', ')', ']', ','])
                || $tokens[$next]->isGivenKind([
                    T_ELSE,
                    T_ELSEIF,
                    T_CATCH,
                    T_FINALLY,
                    T_CLOSE_TAG,
                    T_CASE,
                    T_DEFAULT,
                ])
            ) {
                continue;
            }

            // Preserve an inline comment attached to the closing brace.
            while (isset($tokens[$end + 1])) {
                $candidate = $end + 1;

                if ($tokens[$candidate]->isWhitespace() && !str_contains($tokens[$candidate]->getContent(), "\n")) {
                    ++$candidate;
                }

                if (!isset($tokens[$candidate]) || !$tokens[$candidate]->isComment()) {
                    break;
                }

                $end = $candidate;
            }

            $whitespace = $tokens[$end + 1]->isWhitespace() ? $tokens[$end + 1]->getContent() : '';

            if (preg_match('/\n[\t ]*\r?\n/', $whitespace)) {
                continue;
            }

            $lineEnding = $this->whitespacesConfig->getLineEnding();
            $preceding = $tokens[$index - 1]->isWhitespace() ? $tokens[$index - 1]->getContent() : '';
            $indentSource = str_contains($whitespace, "\n") ? $whitespace : $preceding;
            $indent = str_contains($indentSource, "\n")
                ? substr($indentSource, strrpos($indentSource, "\n") + 1)
                : '';
            $tokens->ensureWhitespaceAtIndex($end + 1, 0, $lineEnding . $lineEnding . $indent);
        }
    }
}

$finder = Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tools', __DIR__ . '/tests'])
    ->append([__FILE__]);

return (new Config())
    ->registerCustomFixers([new SoftCreatRBlankLineAfterControlStructureFixer()])
    ->setRiskyAllowed(false)
    ->setRules([
        '@PER-CS' => true,
        'SoftCreatR/blank_line_after_control_structure' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => false,
        ],
        'header_comment' => [
            'header' => "Mime Detector for PHP.\n\n"
                . '@license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE.md  ISC License',
            'comment_type' => 'PHPDoc',
            'location' => 'after_open',
            'separate' => 'both',
        ],
        'no_unused_imports' => true,
        'phpdoc_order' => [
            'order' => ['param', 'return', 'throws'],
        ],
        'phpdoc_line_span' => [
            'const' => 'multi',
            'method' => 'multi',
            'property' => 'multi',
        ],
        'phpdoc_separation' => [
            'skip_unlisted_annotations' => false,
        ],
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'continue',
                'do',
                'for',
                'foreach',
                'if',
                'return',
                'switch',
                'throw',
                'try',
                'while',
            ],
        ],
        'single_line_empty_body' => false,
    ])
    ->setFinder($finder);
