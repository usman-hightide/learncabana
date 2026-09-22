<?php

/**
 * Page 4 of setup wizard template file
 *
 * @var array<string, mixed> $templateDetails
 * @var \StellarWP\LearnDashCloud\Modules\DesignWizard $designWizard
 */

?>
<div class="design-wizard layout-2">
    <div class="header">
        <div class="logo">
            <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
            <img src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/learndash.svg'); ?>" alt="LearnDash" >
        </div>
        <div class="exit">
            <span class="text"><?php esc_html_e('Exit to Setup', 'learndash-cloud'); ?></span> <img
                src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/design-wizard/svg/exit.svg') ?>"
            >
        </div>
    </div>
    <div class="content">
        <?php // @phpstan-ignore-next-line?>
        <?php $designWizard->renderTemplate('live-preview', compact('templateDetails', 'designWizard')) ?>
    </div>
    <div class="footer">
        <div class="back">
            <img
                class="icon"
                src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/design-wizard/svg/back.svg') ?>"
            > <span class="text"><?php esc_html_e('Back', 'learndash-cloud'); ?></span>
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
                class="button init-button next-button"
            ><?php esc_html_e('Save & Continue', 'learndash-cloud'); ?></a>
        </div>
    </div>
</div>
