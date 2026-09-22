<?php

/**
 * Individual template look
 *
 * @var \StellarWP\LearnDashCloud\Modules\DesignWizard $designWizard
 * @var array<string> $templateDetails
 */

$preview_img = $designWizard->getTemplatePreviewImageUrl($templateDetails);
?>

<div
    class="template"
    data-id="<?php echo esc_attr($templateDetails['id']); ?>"
    data-theme_template_id="<?php echo esc_attr($templateDetails['theme_template_id']); ?>"
    data-preview_url="<?php echo esc_url($templateDetails['preview_url']); ?>"
>
    <figure>
        <div class="image-wrapper">
            <img
                src="<?php echo esc_url($preview_img); ?>"
                alt="<?php echo esc_attr($templateDetails['label']); ?>"
                loading="lazy"
            >
        </div>
        <figcaption>
            <div class="label">
                <?php echo esc_html($templateDetails['label']); ?>
            </div>
        </figcaption>
        <div class="actions">
            <?php if (! empty($templateDetails['preview_url'])) : ?>
                <button class="preview button"><?php esc_html_e('Preview', 'learndash-cloud'); ?></button>
            <?php endif; ?>

            <button class="select button"><?php esc_html_e('Select', 'learndash-cloud'); ?></button>
        </div>
    </figure>
</div>
