<?php
/**
 * Live preview template
 *
 * @var array<string> $templateDetails
 */

?>

<div class="preview">
    <div class="text-wrapper"><?php esc_html_e('Loading', 'learndash-cloud'); ?>...</div>
    <div class="iframe-wrapper">
        <iframe
            class="ld-site-preview"
            id="ld-site-preview"
            src="<?php echo esc_url($templateDetails['preview_url']); ?>"
            frameborder="0"
        ></iframe>
    </div>
</div>
