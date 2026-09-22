<?php

/**
 * Setup page template
 *
 * @var array<string, bool> $completed_steps
 * @var \StellarWP\LearnDashCloud\Modules\SetupWizard $setup_wizard
 * @var string $stripe_connect_url
 * @var string $dns_help_url
 * @var array<string> $nameservers
 * @var string $ip_address
 * @var array<string, string>  $overview_video
 * @var array<string, string>  $overview_article
 */

?>
<div class="wrap learndash-setup">
    <div class="logo">
        <img
            src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/learndash.svg'); ?>"
            alt="LearnDash"
        />
    </div>

    <div class="hero">
        <h1><?php esc_html_e('Set up your site', 'learndash-cloud'); ?></h1>
        <p class="tagline">
            <?php esc_html_e('Our set up wizard will help you get the most out of your site.', 'learndash-cloud') ?>
    </div>

    <div
        class="setup box"
        data-url="<?php echo esc_url(admin_url('admin.php?page=learndash-cloud-setup-wizard')) ?>"
        data-completed="<?php echo esc_attr((string) $completed_steps['site_setup']) ?>"
    >
        <div class="heading">
            <div class="title-wrapper">
                <h2><?php esc_html_e('Set up your site', 'learndash-cloud') ?></h2>
                <p class="description"><?php esc_html_e('This is where the fun begins.', 'learndash-cloud') ?></p>
            </div>
            <?php if (isset($completed_steps['site_setup']) && $completed_steps['site_setup']) :
                // @phpstan-ignore-next-line
                $setup_wizard->renderTemplate('components/status-completed');
            else :
                // @phpstan-ignore-next-line
                $setup_wizard->renderTemplate('components/status-time');
            endif; ?>
        </div>
        <div class="content">
            <div class="icon-wrapper">
                <div class="icon">
                    <img src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/setup.png') ?>">
                </div>
            </div>
            <div class="text-wrapper">
                <h3><?php esc_html_e('Site & Course Details', 'learndash-cloud') ?></h3>
                <p class="description"><?php esc_html_e('Tell us a little bit about your site.', 'learndash-cloud') ?>
                </p>
            </div>
            <div class="button-wrapper">
                <?php if (! isset($completed_steps['site_setup']) || ! $completed_steps['site_setup']) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=learndash-cloud-setup-wizard')) ?>">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div
        class="design box"
        data-url="<?php echo esc_url(admin_url('admin.php?page=learndash-cloud-design-wizard')) ?>"
        data-completed="<?php echo esc_attr((string) $completed_steps['design_setup']) ?>"
    >
        <div class="heading">
            <div class="title-wrapper">
                <h2><?php esc_html_e('Design your site', 'learndash-cloud') ?></h2>
                <p class="description"><?php esc_html_e('It\'s all about appearances.', 'learndash-cloud') ?></p>
            </div>
            <?php if (isset($completed_steps['design_setup']) && $completed_steps['design_setup']) :
                // @phpstan-ignore-next-line
                $setup_wizard->renderTemplate('components/status-completed');
            else :
                // @phpstan-ignore-next-line
                $setup_wizard->renderTemplate('components/status-time');
            endif; ?>
        </div>
        <div class="content">
            <div class="icon-wrapper">
                <div class="icon">
                    <img src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/design.png') ?>">
                </div>
            </div>
            <div class="text-wrapper">
                <h3><?php esc_html_e('Select A Starter Template', 'learndash-cloud') ?></h3>
                <p class="description">
                    <?php esc_html_e('Choose a design to start with and customize.', 'learndash-cloud') ?></p>
            </div>
            <div class="button-wrapper">
                <?php if (! isset($completed_steps['design_setup']) || ! $completed_steps['design_setup']) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=learndash-cloud-design-wizard')) ?>">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div
        class="payment box"
        data-url="<?php echo esc_url($stripe_connect_url) ?>"
        data-completed="<?php echo esc_attr((string) $completed_steps['payment_setup']) ?>"
    >
        <div class="heading">
            <div class="title-wrapper">
                <h2><?php esc_html_e('Configure payment', 'learndash-cloud') ?></h2>
                <p class="description"><?php esc_html_e('Don\'t leave money on the table.', 'learndash-cloud') ?></p>
            </div>
            <?php if (isset($completed_steps['payment_setup']) && $completed_steps['payment_setup']) :
                // @phpstan-ignore-next-line
                $setup_wizard->renderTemplate('components/status-completed');
            else :
                // @phpstan-ignore-next-line
                $setup_wizard->renderTemplate('components/status-time');
            endif; ?>
        </div>
        <div class="content">
            <div class="icon-wrapper">
                <div class="icon">
                    <img src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/payment.png') ?>">
                </div>
            </div>
            <div class="text-wrapper">
                <h3><?php esc_html_e('Set Up Stripe', 'learndash-cloud') ?></h3>
                <p class="description">
                    <?php esc_html_e('Charge credit cards and pay low merchant fees.', 'learndash-cloud') ?></p>
            </div>
            <div class="button-wrapper">
                <?php if (! isset($completed_steps['payment_setup']) || ! $completed_steps['payment_setup']) : ?>
                    <a
                        class="button button-stripe"
                        href="#"
                    ><?php esc_html_e('Connect Stripe', 'learndash-cloud'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="courses box">
        <div class="heading">
            <div class="title-wrapper">
                <h2><?php esc_html_e('Manage your courses', 'learndash-cloud') ?></h2>
                <p class="description"><?php esc_html_e('Get your coursework set up for success.', 'learndash-cloud') ?>
                </p>
            </div>
        </div>
        <div class="content">
            <h3><?php echo esc_html($overview_video['title']); ?></h3>
            <div class="overview-wrapper">
                <div
                    class="video"
                    data-youtube_id="<?php echo esc_attr($overview_video['youtube_id']) ?>"
                    data-type="<?php echo esc_attr($overview_video['type']); ?>"
                >
                    <div class="icon">
                        <span class="dashicons dashicons-arrow-right"></span>
                    </div>
                    <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
                    <img src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/overview.png') ?>">
                </div>
                <div class="overview">
                    <div class="time"><?php esc_html_e('2 Minutes', 'learndash-cloud') ?></div>
                    <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
                    <h2><?php $setup_wizard->renderTemplate('components/article-link', ['article' => $overview_article]); // @phpstan-ignore-line?></h2>
                    <h4><?php esc_html_e('Additional Resources', 'learndash-cloud') ?></h4>
                    <ul>
                        <?php // @phpstan-ignore-next-line?>
                        <?php foreach ($setup_wizard->support->getArticles('additional_resources') as $article) : ?>
                            <li>
                                <?php // @phpstan-ignore-next-line?>
                                <?php $setup_wizard->renderTemplate('components/article-link', compact('article')); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="guides">
                <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
                <?php foreach ($setup_wizard->support->getArticlesCategories(['additional_resources']) as $category_key => $category_title) : // @phpstan-ignore-line?>
                    <div class="<?php echo esc_attr($category_key); ?>">
                        <h4><?php echo esc_html($category_title); ?></h4>
                        <ul>
                            <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
                            <?php foreach ($setup_wizard->support->getArticles($category_key, ['additional_resources']) as $article) : // @phpstan-ignore-line?>
                                <li>
                                    <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
                                    <?php $setup_wizard->renderTemplate('components/article-link', compact('article')); // @phpstan-ignore-line?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="launch box">
        <div class="heading">
            <div class="title-wrapper">
                <h2><?php esc_html_e('Connect a Domain', 'learndash-cloud') ?></h2>
                <p class="description"><?php esc_html_e('It\'s all about appearances.', 'learndash-cloud') ?></p>
            </div>
            <?php if (isset($completed_steps['go_live']) && $completed_steps['go_live']) :
                // @phpstan-ignore-next-line
                $setup_wizard->renderTemplate('components/status-completed');
            else :
                // @phpstan-ignore-next-line
                $setup_wizard->renderTemplate('components/status-time');
            endif; ?>
        </div>
        <?php if (! isset($completed_steps['go_live']) || ! $completed_steps['go_live']) : ?>
            <div class="content">
                <div class="step step-1">
                    <p class="text">
                        <?php esc_html_e(
                            'Ready to go live with your own domain?
                        To either purchase a domain or connect a domain you already own,
                         please go to your LearnDash account and click "Sites → The name of your site → Domains"',
                            'learndash-cloud'
                        ) ?>
                    </p>
                    <p class="text">

                        <?php
                        // phpcs:disable
                        // translators: 1 = documentation URL.
                        printf(
                            __(
                                'To learn more about connecting a domain to your site
<a target="_blank" href="%s">read our documentation here</a>.',
                                'learndash-cloud'
                            ),
                            'https://www.learndash.com/support/docs/learndash-cloud/using-the-go-live-widget'
                        ) ?>
                    </p>
                </div>
                <div class="step step-2">
                    <a href="https://account.learndash.com/sites" class="button primary">Connect</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (! isset($completed_steps['go_live']) || ! $completed_steps['go_live']) : ?>
        <div class="help box">
            <p>
                <?php
                // phpcs:disable
                // translators: 1 = documentation URL, 2 = support link.
                printf(
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    __('Need any help? Check out our <a href="%1$s" target="_blank" rel="noreferrer noopener">guide on going live</a> or <a href="%2$s">reach out to us</a> and we\'ll be happy to help.', 'learndash-cloud'),
                    $dns_help_url,
                    add_query_arg(['page' => 'learndash-cloud-support'], admin_url('admin.php'))
                ); /* phpcs:enable */ ?>
            </p>
        </div>
    <?php endif; ?>
</div>
<div class="video-wrapper">
    <div class="background"></div>
    <div class="video">
        <div class="text-wrapper"><?php esc_html_e('Loading', 'learndash-cloud'); ?>...</div>
        <div class="buttons-wrapper">
            <div class="close">
                <span class="icon dashicons dashicons-no-alt"></span>
                <span class="text"><?php esc_html_e('Close', 'learndash-cloud'); ?></span>
            </div>
            <div class="clear"></div>
        </div>
        <div class="iframe-wrapper">
            <iframe
                class="video-iframe"
                id="video-iframe"
                width="516"
                height="315"
                src=""
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            ></iframe>
        </div>
    </div>
</div>
