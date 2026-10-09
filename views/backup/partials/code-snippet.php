<?php

/**
 * A code snippet styled as a terminal window, with a copy button that copies it without the prompts.
 *
 * @var string $snippetId    Unique id of the hidden copy source.
 * @var array  $snippetLines One entry per line: a list of [text, tone] pairs, tone being plain, keyword, value, comment or prompt.
 */

$snippetToneClasses = [
    'plain'   => 'wpstg-text-terminal-text',
    'keyword' => 'wpstg-text-sky-400',
    'value'   => 'wpstg-text-amber-300',
    'comment' => 'wpstg-text-terminal-muted wpstg-italic',
    'prompt'  => 'wpstg-text-terminal-prompt wpstg-select-none',
];

$snippetCopyText = implode("\n", array_map(function ($line) {
    return implode('', array_map(function ($token) {
        return $token[1] === 'prompt' ? '' : $token[0];
    }, $line));
}, $snippetLines));
?>
<textarea id="<?php echo esc_attr($snippetId); ?>" class="wpstg-hidden" readonly aria-hidden="true"><?php echo esc_textarea($snippetCopyText); ?></textarea>
<div class="wpstg-code-snippet wpstg-overflow-hidden wpstg-rounded-lg wpstg-bg-terminal-bg">
    <div class="wpstg-flex wpstg-items-center wpstg-justify-between wpstg-bg-terminal-header wpstg-px-3 wpstg-py-1">
        <?php require WPSTG_VIEWS_DIR . 'cli/_partials/terminal-dots.php'; ?>
        <button type="button" class="wpstg-p-1.5 wpstg-rounded-md wpstg-border-0 wpstg-bg-transparent wpstg-transition-all wpstg-duration-200 wpstg-text-terminal-muted hover:wpstg-bg-terminal-border hover:wpstg-text-terminal-text wpstg-cursor-pointer" data-wpstg-action="copy-text" data-wpstg-source="#<?php echo esc_attr($snippetId); ?>" title="<?php esc_attr_e('Copy snippet to clipboard', 'wp-staging'); ?>" aria-label="<?php esc_attr_e('Copy snippet to clipboard', 'wp-staging'); ?>">
            <?php require WPSTG_VIEWS_DIR . 'cli/_partials/icons/copy.php'; ?>
        </button>
    </div>
    <pre class="wpstg-m-0 wpstg-overflow-x-auto wpstg-bg-transparent wpstg-px-4 wpstg-py-3 wpstg-font-mono wpstg-text-xs wpstg-leading-relaxed"><code class="wpstg-block wpstg-bg-transparent wpstg-p-0"><?php
    foreach ($snippetLines as $snippetLine) {
        echo '<span class="wpstg-block wpstg-min-h-[1.25em] wpstg-whitespace-pre">';
        foreach ($snippetLine as $snippetToken) {
            echo '<span class="' . esc_attr($snippetToneClasses[$snippetToken[1]]) . '">' . esc_html($snippetToken[0]) . '</span>';
        }

        echo '</span>';
    }
    ?></code></pre>
</div>
