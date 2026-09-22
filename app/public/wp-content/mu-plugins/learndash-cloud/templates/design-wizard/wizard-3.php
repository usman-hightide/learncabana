<?php

/**
 * Page 3 of the design wizard template
 *
 * @var array<string, mixed> $templateDetails
 * @var array<string, array<string, array<string, string>>> $palettes
 * @var \StellarWP\LearnDashCloud\Modules\DesignWizard $designWizard
 */

?>
<div class="design-wizard">
    <div class="sidebar">
        <div class="logo">
            <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
            <img src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/learndash.svg'); ?>" alt="LearnDash" >
        </div>
        <div class="header">
            <div class="title-wrapper">
                <h1 class="title">
                    <?php esc_html_e('Pick some colors', 'learndash-cloud'); ?>
                </h1>
                <div class="reset">
                    <button
                        href="#"
                        class="reset-palette-button"
                    >
                        <span class="dashicons dashicons-image-rotate"></span>
                    </button>
                </div>
            </div>
            <p class="description">
                <?php esc_html_e('Let\'s get you some starting colors. 
                You can always update, expand, and change these later.', 'learndash-cloud'); ?>
            </p>
            <div class="palettes">
                <?php foreach ($palettes as $palette_id => $palette) : ?>
                <div
                    class="palette"
                    data-id="<?php echo esc_attr($palette_id); ?>"
                >
                    <div class="colors">
                        <?php foreach ($palette['colors'] as $color) : ?>
                        <div
                            class="color"
                            style="background-color: <?php echo esc_attr($color) ?>;"
                        ></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="content">
        <div class="header">
            <div class="exit">
                <span class="text"><?php esc_html_e('Exit to Setup', 'learndash-cloud'); ?></span>
                <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
                <img src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/design-wizard/svg/exit.svg') ?>" >
            </div>
        </div>
        <?php // @phpstan-ignore-next-line?>
        <?php $designWizard->renderTemplate('live-preview', compact('templateDetails', 'designWizard')) ?>
        <div class="footer">
            <div class="back">
                <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
                <img class="icon" src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/design-wizard/svg/back.svg') ?>" > 
                <span class="text"><?php esc_html_e('Back', 'learndash-cloud'); ?></span>
            </div>
            <div class="steps">
                <ol class="list">
                    <li class="active"><span class="number">1</span> <span
                            class="text"><?php esc_html_e('Choose a template', 'learndash-cloud'); ?></span></li>
                    <li class="active"><span class="number">2</span> <span
                            class="text"><?php esc_html_e('Fonts', 'learndash-cloud'); ?></span></li>
                    <li class="active"><span class="number">3</span> <span
                            class="text"><?php esc_html_e('Colors', 'learndash-cloud'); ?></span></li>
                </ol>
            </div>
            <div class="buttons">
                <a
                    href="#"
                    class="button next-button"
                ><?php esc_html_e('Next', 'learndash-cloud'); ?></a>
            </div>
        </div>
    </div>
</div>
