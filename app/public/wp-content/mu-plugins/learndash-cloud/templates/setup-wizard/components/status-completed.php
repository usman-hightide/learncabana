<div class="status-wrapper">
    <span class="status completed">
        <span class="text"><?php esc_html_e('Completed', 'learndash-cloud') ?></span>
        <span class="icon">
        <?php
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $icon_svg = file_get_contents(\StellarWP\LearnDashCloud\PLUGIN_PATH . '/images/completed.svg');

        if ($icon_svg) :
            echo wp_kses(
                $icon_svg,
                'svg'
            );
        endif; ?>
        </span>
    </span>
</div>
