<div class="status-wrapper">
    <span class="status time">
        <span class="text">5 Minutes</span>
        <span class="icon">
            <?php
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $icon_svg = file_get_contents(\StellarWP\LearnDashCloud\PLUGIN_PATH . '/images/time.svg');

            if ($icon_svg) {
                echo wp_kses(
                    $icon_svg,
                    'svg'
                );
            }
            ?>
        </span>
    </span>
</div>