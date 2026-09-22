<?php
/**
 * @var array<string, array<string, string>> $categories
 */

?>
<div class="wrap learndash-support">
    <div class="logo"><img
            src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/support/learndash-logo.svg'); ?>"
            alt="LearnDash"
        ></div>

    <div class="hero">
        <h1><?php esc_html_e('Support', 'learndash-cloud'); ?></h1>
        <p class="tagline"><?php esc_html_e('We\'re here to help you succeed.', 'learndash-cloud') ?></h2>
    </div>

    <div class="search box">
        <h2><?php esc_html_e('Got a question?', 'learndash-cloud') ?></h2>
        <div class="fields">
            <div class="form-wrapper">
                <form
                    method="POST"
                    name="search"
                    id="search-form"
                >
                    <input
                        type="text"
                        name="keyword"
                        placeholder="<?php esc_html_e('Search Our Knowledge Base', 'learndash-cloud') ?>"
                    >
                    <button
                        type="submit"
                        class="submit-button"
                    >
                        <span class="dashicons dashicons-search submit"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="answers box">
        <div class="headline-wrapper">
            <div class="headline">
                <h2><?php esc_html_e('Find The Answers', 'learndash-cloud') ?></h2>
                <p class="description">
                    <?php esc_html_e('We\'ve categorized our documentation 
                    to help your most pressing questions.', 'learndash-cloud') ?>
                </p>
            </div>
            <div class="buttons">
                <p><?php esc_html_e('Can\'t find what you\'re looking for?', 'learndash-cloud') ?></p>
                <button
                    class="button create-ticket"
                    >
                    <?php esc_html_e('Create A Support Ticket', 'learndash-cloud') ?>
                </button>
            </div>
        </div>

        <div class="grid">
            <?php foreach ($categories as $category_id => $category) : ?>
            <div
                class="item"
                id="item-<?php echo esc_attr($category['id']) ?>"
                data-id="<?php echo esc_attr($category['id']) ?>"
            >
                <div class="label-wrapper">
                    <span class="icon">
                        <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
                        <img src="<?php echo esc_url(\StellarWP\LearnDashCloud\PLUGIN_URL . '/images/support/' . $category['icon'] . '.png') ?>" alt="" >
                    </span>
                    <h3><?php echo esc_html($category['label']) ?></h3>
                    <span class="icon icon-external dashicons dashicons-external"></span>
                </div>
                <?php if (! empty($category['description'])) : ?>
                    <p class="description"><?php echo esc_html($category['description']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
