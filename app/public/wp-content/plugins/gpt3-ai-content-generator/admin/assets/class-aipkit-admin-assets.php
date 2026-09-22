<?php

namespace WPAICG\Admin\Assets;

use WPAICG\aipkit_dashboard;
use WPAICG\AIPKit_Providers;
use WPAICG\AIPKit_Role_Manager;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\AutoGPT\Helpers\AIPKit_AutoGPT_Prompt_Definitions;
use WPAICG\Chat\Frontend\Assets as ChatFrontendAssetsOrchestrator;
use WPAICG\ContentWriter\AIPKit_Content_Writer_Prompts;
use WPAICG\Includes\AIPKit_Shared_Assets_Manager;
use WPAICG\PostEnhancer\Ajax\AIPKit_Enhancer_Actions_Ajax_Handler;
use WPAICG\Utils\AIPKit_Admin_Header_Action_Buttons;

if (! defined('ABSPATH')) {
    exit;
}

abstract class AIPKit_Admin_Asset_Base
{
    protected $version;

    public function __construct()
    {
        $this->version = self::plugin_version();
    }

    protected static function plugin_version(): string
    {
        return defined('WPAICG_VERSION') ? (string) WPAICG_VERSION : '1.9.15';
    }

    protected static function asset_version(string $relative_path, ?string $fallback = null): string
    {
        $fallback = $fallback ?: self::plugin_version();
        $full_path = WPAICG_PLUGIN_DIR . ltrim($relative_path, '/');

        if (file_exists($full_path)) {
            $mtime = filemtime($full_path);
            if (is_int($mtime) && $mtime > 0) {
                return (string) $mtime;
            }
        }

        return $fallback;
    }

    protected static function script_deps(): array
    {
        return ['wp-i18n', 'aipkit_markdown-it'];
    }

    protected function is_aipkit_page($screen = null): bool
    {
        $screen = $screen ?: get_current_screen();

        return $screen && strpos($screen->id, 'page_wpaicg') !== false;
    }

    protected function ensure_shared_vendor_assets(): void
    {
        if (! wp_script_is('aipkit_markdown-it', 'registered') && class_exists(AIPKit_Shared_Assets_Manager::class)) {
            AIPKit_Shared_Assets_Manager::register($this->version);
        }
    }

    protected function register_style_bundle(string $handle, string $file, array $deps = ['dashicons'], ?string $version = null): void
    {
        if (! wp_style_is($handle, 'registered')) {
            wp_register_style(
                $handle,
                WPAICG_PLUGIN_URL . 'dist/css/' . ltrim($file, '/'),
                $deps,
                $version ?: $this->version
            );
        }
    }

    protected function enqueue_style_handle(string $handle): void
    {
        if (! wp_style_is($handle, 'enqueued')) {
            wp_enqueue_style($handle);
        }
    }

    protected function register_script_bundle(string $handle, string $file, array $deps = [], ?string $version = null): void
    {
        $this->ensure_shared_vendor_assets();

        if (! wp_script_is($handle, 'registered')) {
            wp_register_script(
                $handle,
                WPAICG_PLUGIN_URL . 'dist/js/' . ltrim($file, '/'),
                ! empty($deps) ? $deps : self::script_deps(),
                $version ?: $this->version,
                true
            );
        }
    }

    protected function enqueue_script_handle(string $handle, bool $translations = true): void
    {
        if (! wp_script_is($handle, 'enqueued')) {
            wp_enqueue_script($handle);

            if ($translations) {
                wp_set_script_translations($handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
            }
        }
    }

    protected function register_admin_main_css(?string $version = null): void
    {
        $this->register_style_bundle('aipkit-admin-main-css', 'admin-main.bundle.css', ['dashicons'], $version);
    }

    protected function enqueue_admin_main_css(?string $version = null): void
    {
        $this->register_admin_main_css($version);
        $this->enqueue_style_handle('aipkit-admin-main-css');
    }

    protected function register_admin_main_script(?string $version = null): void
    {
        $this->register_script_bundle('aipkit-admin-main', 'admin-main.bundle.js', self::script_deps(), $version);
    }

    protected function enqueue_admin_main_script(?string $version = null): void
    {
        $this->register_admin_main_script($version);
        $this->enqueue_script_handle('aipkit-admin-main');
    }

    protected function register_public_main_script(?string $version = null): void
    {
        $this->register_script_bundle('aipkit-public-main', 'public-main.bundle.js', self::script_deps(), $version);
    }

    protected function enqueue_public_main_script(?string $version = null): void
    {
        $this->register_public_main_script($version);
        $this->enqueue_script_handle('aipkit-public-main');
        if (class_exists(AIPKit_Shared_Assets_Manager::class)) {
            AIPKit_Shared_Assets_Manager::attach_public_asset_urls('aipkit-public-main');
        }
    }

    protected function ensure_dashboard_core_data(): void
    {
        if (class_exists(DashboardAssets::class) && method_exists(DashboardAssets::class, 'localize_core_data')) {
            DashboardAssets::localize_core_data($this->version);
        }
    }

    protected static function is_script_localized(string $handle, string $object_name): bool
    {
        $data = wp_scripts()->get_data($handle, 'data');

        return is_string($data) && strpos($data, "var {$object_name} =") !== false;
    }

    protected static function dashboard_texts(): array
    {
        $path = WPAICG_PLUGIN_DIR . 'admin/data/dashboard-localized-texts.php';

        return file_exists($path) ? require $path : [];
    }
}

class DashboardAssets extends AIPKit_Admin_Asset_Base
{
    private static $is_core_data_localized = false;

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_core_dashboard_assets']);
    }

    public function enqueue_core_dashboard_assets($hook_suffix)
    {
        $screen = get_current_screen();
        $is_dashboard_screen = $screen && (
            strpos($screen->id, 'page_wpaicg') !== false ||
            $screen->id === 'toplevel_page_wpaicg' ||
            strpos($screen->id, 'aipkit-role-manager') !== false
        );

        if (! $is_dashboard_screen) {
            return;
        }

        $this->enqueue_admin_main_css(self::asset_version('dist/css/admin-main.bundle.css', $this->version));
        $this->enqueue_admin_main_script(self::asset_version('dist/js/admin-main.bundle.js', $this->version));
        self::localize_core_data($this->version);
    }

    public static function localize_core_data(string $plugin_version)
    {
        if (self::$is_core_data_localized) {
            return;
        }

        if (! wp_script_is('aipkit_markdown-it', 'registered') && class_exists(AIPKit_Shared_Assets_Manager::class)) {
            AIPKit_Shared_Assets_Manager::register($plugin_version);
        }

        if (! wp_script_is('aipkit-admin-main', 'registered')) {
            wp_register_script(
                'aipkit-admin-main',
                WPAICG_PLUGIN_URL . 'dist/js/admin-main.bundle.js',
                self::script_deps(),
                self::asset_version('dist/js/admin-main.bundle.js', $plugin_version),
                true
            );
        }

        if (self::is_script_localized('aipkit-admin-main', 'aipkit_dashboard')) {
            self::$is_core_data_localized = true;
            return;
        }

        $openai_models = [];
        $openrouter_models = [];
        $google_models = [];
        $azure_deployments = [];
        $claude_models = [];
        $deepseek_models = [];
        $xai_models = [];
        $ollama_models = [];
        $google_image_models = [];
        $openrouter_image_models = [];
        $xai_image_models = [];
        $recommended_models = [];
        $provider_status = [];
        $default_models = [];
        $current_provider = 'openai';

        if (class_exists(AIPKit_Providers::class)) {
            $openai_models = AIPKit_Providers::get_openai_models();
            $openrouter_models = AIPKit_Providers::get_openrouter_models();
            $google_models = AIPKit_Providers::get_google_models();
            $azure_deployments = AIPKit_Providers::get_azure_deployments();
            $claude_models = AIPKit_Providers::get_claude_models();
            $deepseek_models = AIPKit_Providers::get_deepseek_models();
            $xai_models = AIPKit_Providers::get_xai_models();
            $ollama_models = AIPKit_Providers::get_ollama_models();
            $google_image_models = AIPKit_Providers::get_google_image_models();
            $openrouter_image_models = AIPKit_Providers::get_openrouter_image_models();
            $xai_image_models = AIPKit_Providers::get_xai_image_models();
            $recommended_models = [
                'openai' => AIPKit_Providers::get_recommended_models('OpenAI'),
                'google' => AIPKit_Providers::get_recommended_models('Google'),
                'claude' => AIPKit_Providers::get_recommended_models('Claude'),
                'openrouter' => AIPKit_Providers::get_recommended_models('OpenRouter'),
                'deepseek' => AIPKit_Providers::get_recommended_models('DeepSeek'),
                'xai' => AIPKit_Providers::get_recommended_models('xAI'),
            ];

            $providers = AIPKit_Providers::get_all_providers();
            $current_provider = strtolower(AIPKit_Providers::get_current_provider());
            foreach (array_keys(AIPKit_Providers::get_provider_defaults_all()) as $provider_name) {
                $provider_data = AIPKit_Providers::get_provider_data($provider_name);
                $default_models[strtolower($provider_name)] = isset($provider_data['model'])
                    ? sanitize_text_field((string) $provider_data['model'])
                    : '';
            }
            $provider_status = [
                'openai' => ! empty($providers['OpenAI']['api_key']),
                'google' => ! empty($providers['Google']['api_key']),
                'claude' => ! empty($providers['Claude']['api_key']),
                'openrouter' => ! empty($providers['OpenRouter']['api_key']),
                'azure' => ! empty($providers['Azure']['api_key']) && ! empty($providers['Azure']['endpoint']),
                'ollama' => ! empty($providers['Ollama']['base_url']),
                'deepseek' => ! empty($providers['DeepSeek']['api_key']),
                'xai' => ! empty($providers['xAI']['api_key']),
                'replicate' => ! empty($providers['Replicate']['api_key']),
                'pinecone' => ! empty($providers['Pinecone']['api_key']),
                'qdrant' => ! empty($providers['Qdrant']['api_key']) && ! empty($providers['Qdrant']['url']),
                'chroma' => ! empty($providers['Chroma']['url']),
            ];
        }

        $embedding_provider_map = class_exists(AIPKit_Providers::class)
            ? AIPKit_Providers::get_embedding_provider_map('dashboard_ui')
            : [];
        $embedding_models = class_exists(AIPKit_Providers::class)
            ? AIPKit_Providers::get_embedding_models_by_provider('dashboard_ui')
            : [];
        $is_pro_plan = class_exists(aipkit_dashboard::class) ? aipkit_dashboard::is_pro_plan() : false;

        wp_localize_script('aipkit-admin-main', 'aipkit_dashboard', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aipkit_nonce'),
            'isProPlan' => $is_pro_plan,
            'isAdmin' => AIPKit_Role_Manager::user_can_manage_others_content(),
            'moduleScripts' => [
                'sources' => WPAICG_PLUGIN_URL . 'dist/js/admin-sources.bundle.js?ver=' . self::asset_version('dist/js/admin-sources.bundle.js', $plugin_version),
            ],
            'upgradeUrl' => admin_url('admin.php?page=wpaicg-pricing'),
            'adminUrl' => admin_url(),
            'main_provider' => $current_provider,
            'models' => [
                'openai' => $openai_models,
                'google' => $google_models,
                'claude' => $claude_models,
                'openrouter' => $openrouter_models,
                'azure' => $azure_deployments,
                'ollama' => $ollama_models,
                'deepseek' => $deepseek_models,
                'xai' => $xai_models,
            ],
            'defaultModels' => $default_models,
            'recommendedModels' => $recommended_models,
            'embeddingProviderMap' => $embedding_provider_map,
            'embeddingModels' => $embedding_models,
            'imageGeneratorModels' => [
                'openai' => class_exists(AIPKit_Providers::class) ? AIPKit_Providers::get_openai_image_models() : [],
                'google' => $google_image_models,
                'openrouter' => $openrouter_image_models,
                'xai' => $xai_image_models,
                'azure' => class_exists(AIPKit_Providers::class) ? AIPKit_Providers::get_azure_image_models() : [],
                'replicate' => class_exists(AIPKit_Providers::class) ? AIPKit_Providers::get_replicate_models() : [],
            ],
            'imageGeneratorVideoModels' => [
                'google' => class_exists(AIPKit_Providers::class) ? AIPKit_Providers::get_google_video_models() : [],
            ],
            'providerStatus' => $provider_status,
            'text' => self::dashboard_texts(),
            'currentUserId' => get_current_user_id(),
        ]);

        self::$is_core_data_localized = true;
    }
}

class SettingsAssets extends AIPKit_Admin_Asset_Base
{
    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_settings_assets']);
    }

    public function enqueue_settings_assets($hook_suffix)
    {
        $screen = get_current_screen();

        if (! $this->is_aipkit_page($screen)) {
            return;
        }

        $this->enqueue_admin_main_script();
        $this->ensure_dashboard_core_data();
    }
}

class ChatAdminAssets extends AIPKit_Admin_Asset_Base
{
    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_chat_admin_assets']);
    }

    public function enqueue_chat_admin_assets($hook_suffix)
    {
        $screen = get_current_screen();

        if (! $this->is_aipkit_page($screen)) {
            return;
        }

        if (class_exists(ChatFrontendAssetsOrchestrator::class) && method_exists(ChatFrontendAssetsOrchestrator::class, 'register_public_chat_dependencies')) {
            ChatFrontendAssetsOrchestrator::register_public_chat_dependencies();
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();
        $this->ensure_dashboard_core_data();
        $this->localize_chat_data();
    }

    private function enqueue_styles(): void
    {
        $public_css_version = self::asset_version('dist/css/public-main.bundle.css', $this->version);
        $chat_css_version = self::asset_version('dist/css/admin-chat.bundle.css', $this->version);

        $this->register_admin_main_css(self::asset_version('dist/css/admin-main.bundle.css', $this->version));
        $this->register_style_bundle('aipkit-public-main-css', 'public-main.bundle.css', ['dashicons'], $public_css_version);
        $this->register_style_bundle(
            'aipkit-admin-chat-css',
            'admin-chat.bundle.css',
            ['aipkit-admin-main-css', 'aipkit-public-main-css'],
            $chat_css_version
        );

        $this->enqueue_style_handle('aipkit-public-main-css');
        $this->enqueue_style_handle('aipkit-admin-chat-css');
    }

    private function enqueue_scripts(): void
    {
        $admin_js_version = self::asset_version('dist/js/admin-main.bundle.js', $this->version);
        $public_js_version = self::asset_version('dist/js/public-main.bundle.js', $this->version);

        $this->enqueue_admin_main_script($admin_js_version);
        $this->enqueue_public_main_script($public_js_version);
    }

    private function localize_chat_data(): void
    {
        $public_js_version = self::asset_version('dist/js/public-main.bundle.js', $this->version);

        $this->register_public_main_script($public_js_version);
        $this->enqueue_public_main_script($public_js_version);

        if (self::is_script_localized('aipkit-public-main', 'aipkit_chat_config')) {
            return;
        }

        $vector_store_localization = AIPKit_Providers::get_vector_store_localization_payload('chat_admin_ui');
        $embedding_localization = AIPKit_Providers::get_embedding_localization_payload('chat_admin_ui', false);
        $dashboard_texts = self::dashboard_texts();
        $is_pro_plan = class_exists(aipkit_dashboard::class) ? aipkit_dashboard::is_pro_plan() : false;

        wp_localize_script('aipkit-public-main', 'aipkit_chat_config', [
            'nonce' => wp_create_nonce('aipkit_frontend_chat_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'requireConsentCompliance' => false,
            'openaiVectorStores' => $vector_store_localization['openaiVectorStores'],
            'pineconeIndexes' => $vector_store_localization['pineconeIndexes'],
            'qdrantCollections' => $vector_store_localization['qdrantCollections'],
            'chromaCollections' => $vector_store_localization['chromaCollections'],
            'embedding_provider_map' => $embedding_localization['embedding_provider_map'],
            'embedding_models_by_provider' => $embedding_localization['embedding_models_by_provider'],
            'isProPlan' => $is_pro_plan,
            'automationsNonce' => wp_create_nonce('aipkit_automated_tasks_manage_nonce'),
            'text' => array_merge($dashboard_texts, [
                'fullscreenError' => $dashboard_texts['fullscreenError'] ?? __('Error: Fullscreen functionality is unavailable.', 'gpt3-ai-content-generator'),
                'copySuccess' => $dashboard_texts['copySuccess'] ?? __('Copied!', 'gpt3-ai-content-generator'),
                'copyFail' => $dashboard_texts['copyFail'] ?? __('Failed to copy', 'gpt3-ai-content-generator'),
                'selectVectorStoreOpenAI' => __('Select OpenAI Store(s)', 'gpt3-ai-content-generator'),
                'selectVectorStorePinecone' => __('Select Pinecone Index', 'gpt3-ai-content-generator'),
                'selectVectorStoreQdrant' => __('Select Qdrant Collection', 'gpt3-ai-content-generator'),
                'selectVectorStoreChroma' => __('Select Chroma Collection', 'gpt3-ai-content-generator'),
                'selectEmbeddingProvider' => __('Select Embedding Provider', 'gpt3-ai-content-generator'),
                'selectEmbeddingModel' => __('Select Embedding Model', 'gpt3-ai-content-generator'),
                'noStoresFoundOpenAI' => __('No OpenAI Stores Found (Sync in AI Training)', 'gpt3-ai-content-generator'),
                'noIndexesFoundPinecone' => __('No Pinecone Indexes Found (Sync in AI Settings)', 'gpt3-ai-content-generator'),
                'noCollectionsFoundQdrant' => __('No Qdrant Collections Found (Sync in AI Settings)', 'gpt3-ai-content-generator'),
                'noCollectionsFoundChroma' => __('No Chroma Collections Found (Sync in AI Settings)', 'gpt3-ai-content-generator'),
                'noEmbeddingModelsFound' => __('No Models (Select Provider or Sync)', 'gpt3-ai-content-generator'),
            ]),
        ]);

        if (wp_script_is('aipkit-admin-main', 'enqueued')) {
            wp_add_inline_script(
                'aipkit-admin-main',
                'window.aipkit_index_content_nonce = "' . esc_js(wp_create_nonce('aipkit_chatbot_index_content_nonce')) . '";',
                'before'
            );
        }
    }
}

class RoleManagerAssets extends AIPKit_Admin_Asset_Base
{
    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_role_manager_assets']);
    }

    public function enqueue_role_manager_assets($hook_suffix)
    {
        $screen = get_current_screen();
        $is_role_manager = $screen && strpos($screen->id, 'page_aipkit-role-manager') !== false;

        if (! $is_role_manager) {
            return;
        }

        $this->enqueue_admin_main_css();
        $this->enqueue_admin_main_script();

        if (! self::is_script_localized('aipkit-admin-main', 'aipkit_role_manager_config')) {
            wp_localize_script('aipkit-admin-main', 'aipkit_role_manager_config', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aipkit_role_manager_nonce'),
                'text' => [
                    'success' => __('Permissions saved.', 'gpt3-ai-content-generator'),
                    'fail' => __('Failed to save permissions.', 'gpt3-ai-content-generator'),
                ],
            ]);
        }
    }
}

class PostEnhancerAssets extends AIPKit_Admin_Asset_Base
{
    private const BULK_ASSISTANT_MODULE = 'bulk_assistant';
    private const ROW_ASSISTANT_MODULE = 'row_assistant';
    private const CLASSIC_EDITOR_ASSISTANT_MODULE = 'classic_editor_assistant';
    private const BLOCK_EDITOR_ASSISTANT_MODULE = 'block_editor_assistant';

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_post_enhancer_assets']);
    }

    public function enqueue_post_enhancer_assets($hook_suffix)
    {
        $screen = get_current_screen();
        $is_aipkit_page = $this->is_aipkit_page($screen);
        $is_post_edit_screen = in_array($hook_suffix, ['post.php', 'post-new.php'], true);

        $ui_post_types = get_post_types(['show_ui' => true]);
        unset($ui_post_types['attachment']);

        $supported_post_types = apply_filters('aipkit_post_enhancer_post_types', array_keys($ui_post_types));
        $current_post_type = isset($screen->post_type) ? (string) $screen->post_type : '';
        $is_post_list_screen = $screen && $screen->base === 'edit' && in_array($current_post_type, $supported_post_types, true);
        $can_enqueue_for_aipkit_page = $is_aipkit_page && AIPKit_Role_Manager::user_can_access_any_module([
            'content-writer',
            'settings',
            self::BULK_ASSISTANT_MODULE,
            self::ROW_ASSISTANT_MODULE,
            self::CLASSIC_EDITOR_ASSISTANT_MODULE,
            self::BLOCK_EDITOR_ASSISTANT_MODULE,
        ]);
        $can_enqueue_for_post_edit = $is_post_edit_screen && AIPKit_Role_Manager::user_can_access_any_module([
            self::CLASSIC_EDITOR_ASSISTANT_MODULE,
            self::BLOCK_EDITOR_ASSISTANT_MODULE,
        ]);
        $can_enqueue_for_post_list = $is_post_list_screen && $this->can_access_list_screen_tools($current_post_type);

        if ($can_enqueue_for_post_list || $can_enqueue_for_post_edit || $can_enqueue_for_aipkit_page) {
            $this->enqueue_scripts();
        }

        if ($can_enqueue_for_post_list || $can_enqueue_for_post_edit) {
            $this->enqueue_styles();
        }
    }

    private function can_access_list_screen_tools(string $post_type): bool
    {
        return AIPKit_Role_Manager::user_can_access_any_module([
            self::BULK_ASSISTANT_MODULE,
            self::ROW_ASSISTANT_MODULE,
        ]);
    }

    private function enqueue_styles(): void
    {
        $this->register_admin_main_css();
        $this->register_style_bundle(
            'aipkit-admin-post-enhancer-css',
            'admin-post-enhancer.bundle.css',
            ['aipkit-admin-main-css']
        );
        $this->enqueue_style_handle('aipkit-admin-post-enhancer-css');
    }

    private function enqueue_scripts(): void
    {
        $this->enqueue_admin_main_script();
        $this->ensure_dashboard_core_data();

        if (self::is_script_localized('aipkit-admin-main', 'aipkit_post_enhancer')) {
            return;
        }

        $default_ai_config = AIPKit_Providers::get_default_provider_config();
        $default_ai_params = AIPKIT_AI_Settings::get_ai_parameters();
        $vector_store_localization = AIPKit_Providers::get_vector_store_localization_payload('post_enhancer_ui');
        $embedding_localization = AIPKit_Providers::get_embedding_localization_payload('post_enhancer_ui', false);
        $enhancer_actions = get_option('aipkit_enhancer_actions', []);
        $enhancer_prompt_items = class_exists(AIPKit_AutoGPT_Prompt_Definitions::class)
            ? AIPKit_AutoGPT_Prompt_Definitions::get_post_enhancer_prompt_items(true)
            : [];

        if (empty($enhancer_actions) && class_exists(AIPKit_Enhancer_Actions_Ajax_Handler::class)) {
            $enhancer_actions = (new AIPKit_Enhancer_Actions_Ajax_Handler())->get_default_actions_public();
        }

        wp_localize_script('aipkit-admin-main', 'aipkit_post_enhancer', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce_generate_title' => wp_create_nonce('aipkit_generate_title_nonce'),
            'nonce_update_title' => wp_create_nonce('aipkit_update_title_nonce'),
            'nonce_generate_excerpt' => wp_create_nonce('aipkit_generate_excerpt_nonce'),
            'nonce_update_excerpt' => wp_create_nonce('aipkit_update_excerpt_nonce'),
            'nonce_generate_meta' => wp_create_nonce('aipkit_generate_meta_nonce'),
            'nonce_update_meta' => wp_create_nonce('aipkit_update_meta_nonce'),
            'nonce_generate_tags' => wp_create_nonce('aipkit_generate_tags_nonce'),
            'nonce_update_tags' => wp_create_nonce('aipkit_update_tags_nonce'),
            'nonce_process_text' => wp_create_nonce('aipkit_process_enhancer_text_nonce'),
            'nonce_manage_templates' => wp_create_nonce('aipkit_content_writer_template_nonce'),
            'nonce_manage_actions' => wp_create_nonce('aipkit_enhancer_actions_nonce'),
            'nonce_prompt_library' => wp_create_nonce('aipkit_nonce'),
            'default_ai_provider' => $default_ai_config['provider'] ?? 'N/A',
            'default_ai_model' => $default_ai_config['model'] ?? 'N/A',
            'default_ai_params' => $default_ai_params,
            'prompt_items' => $enhancer_prompt_items,
            'openai_vector_stores' => $vector_store_localization['openai_vector_stores'],
            'pinecone_indexes' => $vector_store_localization['pinecone_indexes'],
            'qdrant_collections' => $vector_store_localization['qdrant_collections'],
            'chroma_collections' => $vector_store_localization['chroma_collections'],
            'embeddingProviderMap' => $embedding_localization['embeddingProviderMap'],
            'embeddingModelsByProvider' => $embedding_localization['embeddingModelsByProvider'],
            'actions' => $enhancer_actions,
            'can_manage_actions' => AIPKit_Role_Manager::user_can_access_module(AIPKit_Enhancer_Actions_Ajax_Handler::MODULE_SLUG),
            'max_actions' => AIPKit_Enhancer_Actions_Ajax_Handler::MAX_ACTIONS,
            'parse_html_formats' => (bool) apply_filters('aipkit_enhancer_enable_formatting', true),
            'text' => [
                'modal_title_title' => __('Title Suggestions', 'gpt3-ai-content-generator'),
                'loading_title' => __('Generating Title Suggestions...', 'gpt3-ai-content-generator'),
                'updating_title' => __('Updating Title...', 'gpt3-ai-content-generator'),
                'error_loading_title' => __('Error loading title suggestions.', 'gpt3-ai-content-generator'),
                'error_updating_title' => __('Error updating title.', 'gpt3-ai-content-generator'),
                'no_suggestions_title' => __('No title suggestions generated or AI Error.', 'gpt3-ai-content-generator'),
                'select_title' => __('Click a title to apply:', 'gpt3-ai-content-generator'),
                'modal_title_excerpt' => __('Excerpt Suggestions', 'gpt3-ai-content-generator'),
                'loading_excerpt' => __('Generating Excerpt Suggestions...', 'gpt3-ai-content-generator'),
                'updating_excerpt' => __('Updating Excerpt...', 'gpt3-ai-content-generator'),
                'error_loading_excerpt' => __('Error loading excerpt suggestions.', 'gpt3-ai-content-generator'),
                'error_updating_excerpt' => __('Error updating excerpt.', 'gpt3-ai-content-generator'),
                'no_suggestions_excerpt' => __('No excerpt suggestions generated or AI Error.', 'gpt3-ai-content-generator'),
                'select_excerpt' => __('Click an excerpt to apply:', 'gpt3-ai-content-generator'),
                'modal_title_meta' => __('Meta Description Suggestions', 'gpt3-ai-content-generator'),
                'loading_meta' => __('Generating Meta Descriptions...', 'gpt3-ai-content-generator'),
                'updating_meta' => __('Updating Meta Description...', 'gpt3-ai-content-generator'),
                'error_loading_meta' => __('Error loading meta description suggestions.', 'gpt3-ai-content-generator'),
                'error_updating_meta' => __('Error updating meta description.', 'gpt3-ai-content-generator'),
                'no_suggestions_meta' => __('No meta description suggestions generated or AI Error.', 'gpt3-ai-content-generator'),
                'select_meta' => __('Click a meta description to apply:', 'gpt3-ai-content-generator'),
                'modal_title_tags' => __('Tag Suggestions', 'gpt3-ai-content-generator'),
                'loading_tags' => __('Generating Tag Suggestions...', 'gpt3-ai-content-generator'),
                'updating_tags' => __('Updating Tags...', 'gpt3-ai-content-generator'),
                'error_loading_tags' => __('Error loading tag suggestions.', 'gpt3-ai-content-generator'),
                'error_updating_tags' => __('Error updating tags.', 'gpt3-ai-content-generator'),
                'no_suggestions_tags' => __('No tag suggestions generated or AI Error.', 'gpt3-ai-content-generator'),
                'select_tags' => __('Click a tag set to apply:', 'gpt3-ai-content-generator'),
                /* translators: 1: provider label, 2: model label, 3: temperature value. */
                'loading_info_template' => __('Using <strong>%1$s</strong> (Model: <strong>%2$s</strong>, Temp: %3$s)', 'gpt3-ai-content-generator'),
                'close' => __('Close', 'gpt3-ai-content-generator'),
                'config_modal_title' => __('Configure AI Actions', 'gpt3-ai-content-generator'),
                'customize_actions' => __('Customize menu...', 'gpt3-ai-content-generator'),
                'assistant_menu_title' => __('Assistant Menu', 'gpt3-ai-content-generator'),
                'assistant_menu_description' => __('Customize editor Assistant menu items.', 'gpt3-ai-content-generator'),
                'assistant_menu_items' => __('Menu items', 'gpt3-ai-content-generator'),
                'add_action' => __('Add Item', 'gpt3-ai-content-generator'),
                'new_action' => __('New menu item', 'gpt3-ai-content-generator'),
                'move_up' => __('Move up', 'gpt3-ai-content-generator'),
                'move_down' => __('Move down', 'gpt3-ai-content-generator'),
                'delete_action' => __('Delete', 'gpt3-ai-content-generator'),
                'action_label' => __('Action Label', 'gpt3-ai-content-generator'),
                'action_prompt' => __('Action Prompt', 'gpt3-ai-content-generator'),
                'insert_position' => __('Position', 'gpt3-ai-content-generator'),
                'replace_selection' => __('Replace selection', 'gpt3-ai-content-generator'),
                'insert_after' => __('Insert after', 'gpt3-ai-content-generator'),
                'insert_before' => __('Insert before', 'gpt3-ai-content-generator'),
                'reset_actions' => __('Reset', 'gpt3-ai-content-generator'),
                'confirm_reset_actions' => __('Reset all actions to the default set? This will replace current customizations.', 'gpt3-ai-content-generator'),
                'actions_reset' => __('Actions reset to defaults.', 'gpt3-ai-content-generator'),
                'save_action' => __('Save', 'gpt3-ai-content-generator'),
                'saving_action' => __('Saving...', 'gpt3-ai-content-generator'),
                'confirm_delete_action' => __('Are you sure you want to delete this action? This cannot be undone.', 'gpt3-ai-content-generator'),
                'deleting_action' => __('Deleting...', 'gpt3-ai-content-generator'),
                'action_deleted' => __('Action deleted.', 'gpt3-ai-content-generator'),
                'action_saved' => __('Action saved.', 'gpt3-ai-content-generator'),
                'loading_actions' => __('Loading actions...', 'gpt3-ai-content-generator'),
                'loading_failed' => __('Failed to load actions.', 'gpt3-ai-content-generator'),
                'label_required' => __('Label is required.', 'gpt3-ai-content-generator'),
                'prompt_required' => __('Prompt is required.', 'gpt3-ai-content-generator'),
                'max_actions_reached' => __('Maximum actions reached.', 'gpt3-ai-content-generator'),
                'saving_order' => __('Saving order...', 'gpt3-ai-content-generator'),
                'order_saved' => __('Order saved.', 'gpt3-ai-content-generator'),
                'resetting_actions' => __('Resetting...', 'gpt3-ai-content-generator'),
                'error' => __('Error', 'gpt3-ai-content-generator'),
                'save' => __('Save', 'gpt3-ai-content-generator'),
                /* translators: %s: placeholder token that will be replaced by the selected text. */
                'prompt_placeholder_info' => __('Use %s as a placeholder for the selected text.', 'gpt3-ai-content-generator'),
            ],
        ]);
    }
}

class ImageGeneratorAssets extends AIPKit_Admin_Asset_Base
{
    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_image_generator_assets']);
    }

    public function enqueue_image_generator_assets($hook_suffix)
    {
        $screen = get_current_screen();

        if (! $this->is_aipkit_page($screen)) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();
        $this->ensure_dashboard_core_data();
        $this->localize_data();
    }

    private function enqueue_styles(): void
    {
        $this->register_style_bundle(
            'aipkit-public-image-generator-css',
            'public-image-generator.bundle.css',
            [],
            self::asset_version('dist/css/public-image-generator.bundle.css', $this->version)
        );
        $this->enqueue_style_handle('aipkit-public-image-generator-css');
    }

    private function enqueue_scripts(): void
    {
        $this->enqueue_admin_main_script();
        $public_js_version = self::asset_version('dist/js/public-image-generator.bundle.js', $this->version);
        $this->register_script_bundle(
            'aipkit-public-image-generator-js',
            'public-image-generator.bundle.js',
            ['wp-i18n'],
            $public_js_version
        );
        $this->enqueue_script_handle('aipkit-public-image-generator-js');
    }

    private function localize_data(): void
    {
        $public_js_version = self::asset_version('dist/js/public-image-generator.bundle.js', $this->version);
        $this->register_script_bundle(
            'aipkit-public-image-generator-js',
            'public-image-generator.bundle.js',
            ['wp-i18n'],
            $public_js_version
        );

        if (self::is_script_localized('aipkit-public-image-generator-js', 'aipkit_image_generator_config_public')) {
            return;
        }

        wp_localize_script(
            'aipkit-public-image-generator-js',
            'aipkit_image_generator_config_public',
            AIPKit_Shared_Assets_Manager::get_public_image_generator_config()
        );
    }
}

class AIPKit_Content_Writer_Assets extends AIPKit_Admin_Asset_Base
{
    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_content_writer_assets']);
    }

    public function enqueue_content_writer_assets($hook_suffix)
    {
        $screen = get_current_screen();

        if (! $this->is_aipkit_page($screen)) {
            return;
        }

        $this->enqueue_admin_main_css();
        $this->enqueue_admin_main_script();
        $this->ensure_dashboard_core_data();
        $this->localize_data();
    }

    private function localize_data(): void
    {
        if (self::is_script_localized('aipkit-admin-main', 'aipkit_content_writer_config')) {
            return;
        }

        wp_localize_script('aipkit-admin-main', 'aipkit_content_writer_config', [
            'default_prompts' => [
                'title' => AIPKit_Content_Writer_Prompts::get_default_title_prompt(),
                'content' => AIPKit_Content_Writer_Prompts::get_default_content_prompt(),
                'meta' => AIPKit_Content_Writer_Prompts::get_default_meta_prompt(),
                'keyword' => AIPKit_Content_Writer_Prompts::get_default_keyword_prompt(),
                'image' => AIPKit_Content_Writer_Prompts::get_default_image_prompt(),
                'featured_image' => AIPKit_Content_Writer_Prompts::get_default_featured_image_prompt(),
                'image_title' => AIPKit_Content_Writer_Prompts::get_default_image_title_prompt(),
                'image_alt_text' => AIPKit_Content_Writer_Prompts::get_default_image_alt_text_prompt(),
                'image_caption' => AIPKit_Content_Writer_Prompts::get_default_image_caption_prompt(),
                'image_description' => AIPKit_Content_Writer_Prompts::get_default_image_description_prompt(),
                'image_title_update' => AIPKit_Content_Writer_Prompts::get_default_image_title_prompt_update(),
                'image_alt_text_update' => AIPKit_Content_Writer_Prompts::get_default_image_alt_text_prompt_update(),
                'image_caption_update' => AIPKit_Content_Writer_Prompts::get_default_image_caption_prompt_update(),
                'image_description_update' => AIPKit_Content_Writer_Prompts::get_default_image_description_prompt_update(),
            ],
        ]);
    }
}

class AIPKit_Vector_Post_Processor_Assets extends AIPKit_Admin_Asset_Base
{
    public const MODULE_SLUG = 'vector_content_indexer';

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', function () {
            if (! AIPKit_Role_Manager::user_can_access_module(self::MODULE_SLUG)) {
                return;
            }

            $general = get_option('aipkit_training_general_settings', []);
            $show = $general['show_index_button'] ?? true;

            if ($show) {
                $post_types = ['post', 'page'];
                if (post_type_exists('product')) {
                    $post_types[] = 'product';
                }
                $post_types = apply_filters('aipkit_vector_post_processor_supported_post_types', $post_types);
                AIPKit_Admin_Header_Action_Buttons::register_button(
                    'aipkit_add_to_vector_store_btn',
                    __('Index', 'gpt3-ai-content-generator'),
                    [
                        'post_types' => array_values(array_unique(array_map('sanitize_key', (array) $post_types))),
                        'access_callback' => [__CLASS__, 'current_user_can_access_index_button'],
                    ]
                );
            }
        });
    }

    public static function current_user_can_access_index_button(string $post_type): bool
    {
        if (! AIPKit_Role_Manager::user_can_access_module(self::MODULE_SLUG)) {
            return false;
        }

        return self::current_user_can_edit_post_type($post_type);
    }

    private static function current_user_can_edit_post_type(string $post_type): bool
    {
        $post_type_object = get_post_type_object($post_type);
        $capability = 'edit_posts';

        if ($post_type_object && isset($post_type_object->cap->edit_posts)) {
            $capability = (string) $post_type_object->cap->edit_posts;
        }

        return current_user_can($capability);
    }

    public function enqueue_assets($hook_suffix)
    {
        $screen = get_current_screen();
        $is_post_list_screen = $screen && $screen->base === 'edit';

        if (! $is_post_list_screen || ! AIPKit_Role_Manager::user_can_access_module(self::MODULE_SLUG)) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();
        $this->ensure_dashboard_core_data();
        $this->localize_vpp_data((string) $screen->post_type);
    }

    private function enqueue_styles(): void
    {
        $this->register_admin_main_css();
        $this->register_style_bundle(
            'aipkit-admin-vector-post-processor-css',
            'admin-vector-post-processor.bundle.css',
            ['aipkit-admin-main-css']
        );
        $this->enqueue_style_handle('aipkit-admin-vector-post-processor-css');
    }

    private function enqueue_scripts(): void
    {
        $this->enqueue_admin_main_script();
    }

    private function localize_vpp_data(string $post_type): void
    {
        if (self::is_script_localized('aipkit-admin-main', 'aipkit_vpp_config')) {
            return;
        }

        $vector_store_localization = AIPKit_Providers::get_vector_store_localization_payload('vector_post_processor_ui');
        $embedding_localization = AIPKit_Providers::get_embedding_localization_payload('vector_post_processor_ui', false);

        wp_localize_script('aipkit-admin-main', 'aipkit_vpp_config', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce_index_posts' => wp_create_nonce('aipkit_index_posts_to_vector_store_nonce'),
            'nonce_openai_store_list' => wp_create_nonce('aipkit_vector_store_nonce_openai'),
            'post_type' => $post_type,
            'openai_vector_stores' => $vector_store_localization['openai_vector_stores'],
            'pinecone_indexes' => $vector_store_localization['pinecone_indexes'],
            'qdrant_collections' => $vector_store_localization['qdrant_collections'],
            'chroma_collections' => $vector_store_localization['chroma_collections'],
            'embeddingProviderMap' => $embedding_localization['embeddingProviderMap'],
            'embeddingModelsByProvider' => $embedding_localization['embeddingModelsByProvider'],
            'text' => [
                'modal_title' => __('Add Content to Vector Store', 'gpt3-ai-content-generator'),
                'provider_label' => __('Provider', 'gpt3-ai-content-generator'),
                'select_store' => __('Select OpenAI Store', 'gpt3-ai-content-generator'),
                'no_stores_found' => __('No OpenAI stores found. Create one in AI Training > Knowledge Base.', 'gpt3-ai-content-generator'),
                'loading_stores' => __('Loading stores...', 'gpt3-ai-content-generator'),
                'start_indexing' => __('Start Indexing', 'gpt3-ai-content-generator'),
                'processingButton' => __('Processing...', 'gpt3-ai-content-generator'),
                'close' => __('Close', 'gpt3-ai-content-generator'),
                'stop' => __('Stop', 'gpt3-ai-content-generator'),
                'stopping' => __('Stopping...', 'gpt3-ai-content-generator'),
                /* translators: 1: processed item count, 2: total item count. */
                'indexing_progress' => __('Processing: %1$d/%2$d', 'gpt3-ai-content-generator'),
                'indexing_complete' => __('Indexing complete!', 'gpt3-ai-content-generator'),
                'error_fetching_stores' => __('Error fetching vector stores.', 'gpt3-ai-content-generator'),
                'error_no_store_selected_vpp' => __('Please select an existing OpenAI store.', 'gpt3-ai-content-generator'),
                'error_no_posts_selected' => __('Please select at least one post to index.', 'gpt3-ai-content-generator'),
                'confirm_start_indexing' => __('Are you sure you want to index the selected content?', 'gpt3-ai-content-generator'),
                'status_preparing' => __('Preparing content...', 'gpt3-ai-content-generator'),
                /* translators: 1: current file number, 2: total file count. */
                'status_uploading' => __('Uploading file %1$s of %2$s...', 'gpt3-ai-content-generator'),
                'status_adding_files' => __('Adding files to vector store...', 'gpt3-ai-content-generator'),
                'status_error' => __('An error occurred.', 'gpt3-ai-content-generator'),
                /* translators: %d: number of selected items. */
                'items_selected_singular' => __('You have selected %d item to index.', 'gpt3-ai-content-generator'),
                /* translators: %d: number of selected items. */
                'items_selected_plural' => __('You have selected %d items to index.', 'gpt3-ai-content-generator'),
                'select_pinecone_index' => __('Select Pinecone Index', 'gpt3-ai-content-generator'),
                'loading_indexes' => __('Loading indexes...', 'gpt3-ai-content-generator'),
                'error_fetching_indexes' => __('Error fetching indexes.', 'gpt3-ai-content-generator'),
                'no_pinecone_indexes_found' => __('No Pinecone indexes found. Create one in AI Training or via Pinecone console.', 'gpt3-ai-content-generator'),
                'error_no_pinecone_index_selected' => __('Please select a Pinecone index.', 'gpt3-ai-content-generator'),
                'select_qdrant_collection' => __('Select Qdrant Collection', 'gpt3-ai-content-generator'),
                'no_qdrant_collections_found' => __('No Qdrant collections found. Create one in AI Training.', 'gpt3-ai-content-generator'),
                'error_no_qdrant_collection_selected' => __('Please select a Qdrant collection.', 'gpt3-ai-content-generator'),
                'select_chroma_collection' => __('Select Chroma Collection', 'gpt3-ai-content-generator'),
                'no_chroma_collections_found' => __('No Chroma collections found. Create one in Knowledge Base.', 'gpt3-ai-content-generator'),
                'error_no_chroma_collection_selected' => __('Please select a Chroma collection.', 'gpt3-ai-content-generator'),
                'target_label' => __('Target', 'gpt3-ai-content-generator'),
                'embedding_label' => __('Embedding', 'gpt3-ai-content-generator'),
                'embedding_provider_label' => __('Embedding Provider', 'gpt3-ai-content-generator'),
                'embedding_model_label' => __('Embedding Model', 'gpt3-ai-content-generator'),
                'select_model' => __('Select Model', 'gpt3-ai-content-generator'),
                'error_no_embedding_config' => __('Embedding provider and model are required.', 'gpt3-ai-content-generator'),
                'ensure_api_key_for_embedding' => __('Ensure API key is set for the selected embedding provider in AI Settings.', 'gpt3-ai-content-generator'),
                'status_pending' => __('Pending', 'gpt3-ai-content-generator'),
                'status_processing' => __('Processing', 'gpt3-ai-content-generator'),
                'status_completed' => __('Completed', 'gpt3-ai-content-generator'),
                'status_failed' => __('Failed', 'gpt3-ai-content-generator'),
                'status_stopped' => __('Stopped', 'gpt3-ai-content-generator'),
            ],
        ]);
    }
}

class AIPKit_Autogpt_Assets extends AIPKit_Admin_Asset_Base
{
    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_autogpt_assets']);
    }

    public function enqueue_autogpt_assets($hook_suffix)
    {
        $screen = get_current_screen();

        if (! $this->is_aipkit_page($screen)) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();
        $this->ensure_dashboard_core_data();
        $this->localize_data();
    }

    private function enqueue_styles(): void
    {
        $this->register_admin_main_css();
        $this->register_style_bundle(
            'aipkit-admin-autogpt-css',
            'admin-autogpt.bundle.css',
            ['aipkit-admin-main-css']
        );
        $this->enqueue_style_handle('aipkit-admin-autogpt-css');
    }

    private function enqueue_scripts(): void
    {
        $this->enqueue_admin_main_script();
    }

    private function localize_data(): void
    {
        if (self::is_script_localized('aipkit-admin-main', 'aipkit_automated_tasks_config')) {
            return;
        }

        $vector_store_localization = AIPKit_Providers::get_vector_store_localization_payload('autogpt_ui');
        $embedding_localization = AIPKit_Providers::get_embedding_localization_payload('autogpt_ui', false);
        $default_cw_prompts = [];
        $default_ce_prompts = class_exists(AIPKit_AutoGPT_Prompt_Definitions::class)
            ? AIPKit_AutoGPT_Prompt_Definitions::get_content_enhancement_defaults()
            : [];
        $default_cc_prompts = class_exists(AIPKit_AutoGPT_Prompt_Definitions::class)
            ? AIPKit_AutoGPT_Prompt_Definitions::get_comment_reply_defaults()
            : [];

        if (class_exists(AIPKit_Content_Writer_Prompts::class)) {
            $default_cw_prompts = [
                'title' => AIPKit_Content_Writer_Prompts::get_default_title_prompt(),
                'content' => AIPKit_Content_Writer_Prompts::get_default_content_prompt(),
                'meta' => AIPKit_Content_Writer_Prompts::get_default_meta_prompt(),
                'keyword' => AIPKit_Content_Writer_Prompts::get_default_keyword_prompt(),
                'image' => AIPKit_Content_Writer_Prompts::get_default_image_prompt(),
                'featured_image' => AIPKit_Content_Writer_Prompts::get_default_featured_image_prompt(),
            ];
        }

        $frequencies = [];
        foreach (wp_get_schedules() as $slug => $details) {
            $frequencies[$slug] = $details['display'];
        }

        wp_localize_script('aipkit-admin-main', 'aipkit_automated_tasks_config', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce_manage_tasks' => wp_create_nonce('aipkit_automated_tasks_manage_nonce'),
            'openai_vector_stores' => $vector_store_localization['openai_vector_stores'],
            'pinecone_indexes' => $vector_store_localization['pinecone_indexes'],
            'qdrant_collections' => $vector_store_localization['qdrant_collections'],
            'chroma_collections' => $vector_store_localization['chroma_collections'],
            'embedding_provider_map' => $embedding_localization['embedding_provider_map'],
            'embedding_models_by_provider' => $embedding_localization['embedding_models_by_provider'],
            'task_types' => [
                'content_indexing' => [
                    'label' => __('Index WordPress Content', 'gpt3-ai-content-generator'),
                    'category' => 'knowledge_base',
                    'description' => __('Index WordPress posts, pages, or products into a vector store for RAG.', 'gpt3-ai-content-generator'),
                ],
                'content_writing_bulk' => [
                    'label' => __('List', 'gpt3-ai-content-generator'),
                    'category' => 'content_creation',
                    'description' => __('Generate full articles from a list of titles and optional keywords.', 'gpt3-ai-content-generator'),
                ],
                'content_writing_csv' => [
                    'label' => __('CSV', 'gpt3-ai-content-generator'),
                    'category' => 'content_creation',
                    'description' => __('Generate articles by importing topics and metadata from a CSV file.', 'gpt3-ai-content-generator'),
                ],
                'content_writing_rss' => [
                    'label' => __('RSS', 'gpt3-ai-content-generator'),
                    'category' => 'content_creation',
                    'description' => __('Automatically generate articles from new items in one or more RSS feeds.', 'gpt3-ai-content-generator'),
                    'pro' => true,
                ],
                'content_writing_url' => [
                    'label' => __('URL', 'gpt3-ai-content-generator'),
                    'category' => 'content_creation',
                    'description' => __('Generate articles by scraping content from a list of URLs to use as context.', 'gpt3-ai-content-generator'),
                    'pro' => true,
                ],
                'content_writing_gsheets' => [
                    'label' => __('Google Sheets', 'gpt3-ai-content-generator'),
                    'category' => 'content_creation',
                    'description' => __('Generate articles from a list of topics in a Google Sheets spreadsheet.', 'gpt3-ai-content-generator'),
                    'pro' => true,
                ],
                'enhance_existing_content' => [
                    'label' => __('Update Existing Content', 'gpt3-ai-content-generator'),
                    'category' => 'content_enhancement',
                    'description' => __('Automatically update titles, excerpts, or meta descriptions for existing posts based on your custom prompts.', 'gpt3-ai-content-generator'),
                    'pro' => true,
                    'disabled' => false,
                ],
                'community_reply_comments' => [
                    'label' => __('Auto-Reply to Comments', 'gpt3-ai-content-generator'),
                    'category' => 'community_engagement',
                    'description' => __('Automatically generate and post replies to new comments.', 'gpt3-ai-content-generator'),
                    'disabled' => false,
                ],
            ],
            'default_cw_prompts' => $default_cw_prompts,
            'default_ce_prompts' => $default_ce_prompts,
            'default_cc_prompts' => $default_cc_prompts,
            'frequencies' => $frequencies,
            'text' => [
                'confirm_delete_task' => __('Are you sure you want to delete this automated task? This action cannot be undone.', 'gpt3-ai-content-generator'),
                'task_name_required' => __('Task name is required.', 'gpt3-ai-content-generator'),
                'task_type_required' => __('Task type is required.', 'gpt3-ai-content-generator'),
                'target_store_required' => __('Please select a target vector store/index.', 'gpt3-ai-content-generator'),
                'content_type_required' => __('Please select at least one content type.', 'gpt3-ai-content-generator'),
                'embedding_provider_required' => __('Embedding provider is required for this vector database.', 'gpt3-ai-content-generator'),
                'embedding_model_required' => __('Embedding model is required for this vector database.', 'gpt3-ai-content-generator'),
                'content_title_required_cw_task' => __('Please add a topic.', 'gpt3-ai-content-generator'),
                'csv_required_cw_task' => __('Please upload a CSV file.', 'gpt3-ai-content-generator'),
                'rss_required_cw_task' => __('Please add at least one RSS feed.', 'gpt3-ai-content-generator'),
                'url_required_cw_task' => __('Please add at least one URL.', 'gpt3-ai-content-generator'),
                'gsheets_id_required_cw_task' => __('Please add a Google Sheet ID.', 'gpt3-ai-content-generator'),
                'gsheets_credentials_required_cw_task' => __('Please add Google Sheets credentials.', 'gpt3-ai-content-generator'),
                'ai_config_required_cw_task' => __('AI Provider and Model are required for Content Writing task.', 'gpt3-ai-content-generator'),
                'saving_task' => __('Saving Task...', 'gpt3-ai-content-generator'),
                'deleting_task' => __('Deleting Task...', 'gpt3-ai-content-generator'),
                'running_task' => __('Initiating Run...', 'gpt3-ai-content-generator'),
                'pausing_task' => __('Pausing Task...', 'gpt3-ai-content-generator'),
                'resuming_task' => __('Resuming Task...', 'gpt3-ai-content-generator'),
                'save_task_button' => __('Save', 'gpt3-ai-content-generator'),
                'create_task_button' => __('Create Task', 'gpt3-ai-content-generator'),
                'edit_task_title' => __('Edit Automated Task', 'gpt3-ai-content-generator'),
                'create_task_title' => __('Create New Automated Task', 'gpt3-ai-content-generator'),
                'loading_stores' => __('Loading stores...', 'gpt3-ai-content-generator'),
                'loading_indexes' => __('Loading indexes...', 'gpt3-ai-content-generator'),
                'select_target_store' => __('-- Select Target Store --', 'gpt3-ai-content-generator'),
                'select_target_index' => __('-- Select Target Index --', 'gpt3-ai-content-generator'),
                'select_target_collection' => __('-- Select Target Collection --', 'gpt3-ai-content-generator'),
                'no_targets_found_configure' => __('No targets found. Configure in AI Training.', 'gpt3-ai-content-generator'),
                'loading_models' => __('Loading models...', 'gpt3-ai-content-generator'),
                'select_embedding_model' => __('-- Select Model --', 'gpt3-ai-content-generator'),
                'no_embedding_models_sync' => __('No models - Sync in AI Settings.', 'gpt3-ai-content-generator'),
                'loading_tasks' => __('Loading tasks...', 'gpt3-ai-content-generator'),
                'error_loading_tasks' => __('Error loading tasks:', 'gpt3-ai-content-generator'),
                'no_tasks_configured' => __('No automated tasks configured yet.', 'gpt3-ai-content-generator'),
                'edit_button' => __('Edit', 'gpt3-ai-content-generator'),
                'pause_button' => __('Pause', 'gpt3-ai-content-generator'),
                'resume_button' => __('Resume', 'gpt3-ai-content-generator'),
                'run_now_button' => __('Run Now', 'gpt3-ai-content-generator'),
                'task_not_active_run_title' => __('Task must be active to run', 'gpt3-ai-content-generator'),
                'delete_button' => __('Delete', 'gpt3-ai-content-generator'),
                'never_run' => __('Never', 'gpt3-ai-content-generator'),
                'not_scheduled' => __('Not Scheduled', 'gpt3-ai-content-generator'),
                'task_deleted_success' => __('Task deleted successfully.', 'gpt3-ai-content-generator'),
                'error_deleting_task' => __('Error deleting task:', 'gpt3-ai-content-generator'),
                'task_status_updated' => __('Task status updated to', 'gpt3-ai-content-generator'),
                'error_updating_status' => __('Error updating task status:', 'gpt3-ai-content-generator'),
                'task_run_initiated' => __('Task run initiated. Check queue below for progress.', 'gpt3-ai-content-generator'),
                'error_initiating_run' => __('Error initiating task run:', 'gpt3-ai-content-generator'),
                'loading_queue' => __('Loading queue items...', 'gpt3-ai-content-generator'),
                'error_loading_queue' => __('Error loading queue:', 'gpt3-ai-content-generator'),
                'queue_empty' => __('Task queue is currently empty.', 'gpt3-ai-content-generator'),
                'target_id_prefix' => __('Target ID:', 'gpt3-ai-content-generator'),
                'task_id_prefix' => __('Task ID:', 'gpt3-ai-content-generator'),
                'not_applicable' => __('N/A', 'gpt3-ai-content-generator'),
                'added_at_label' => __('Added', 'gpt3-ai-content-generator'),
                'scheduled_for_label' => __('Scheduled', 'gpt3-ai-content-generator'),
                'item_singular' => __('item', 'gpt3-ai-content-generator'),
                'item_plural' => __('items', 'gpt3-ai-content-generator'),
                'queue_summary_pending' => __('Pending', 'gpt3-ai-content-generator'),
                'queue_summary_running' => __('Running', 'gpt3-ai-content-generator'),
                'queue_summary_failed' => __('Failed', 'gpt3-ai-content-generator'),
                'page_label' => __('Page', 'gpt3-ai-content-generator'),
                'of_label' => __('of', 'gpt3-ai-content-generator'),
                'previous_button' => __('Previous', 'gpt3-ai-content-generator'),
                'next_button' => __('Next', 'gpt3-ai-content-generator'),
                'confirm_delete_queue_item' => __('Are you sure you want to remove this item from the queue?', 'gpt3-ai-content-generator'),
                /* translators: %s: queue status label. */
                'confirmDeleteQueueByStatus' => __('Are you sure you want to delete all %s items from the queue? This cannot be undone.', 'gpt3-ai-content-generator'),
                'confirmDeleteQueueAll' => __('Are you sure you want to delete ALL items from the queue? This cannot be undone.', 'gpt3-ai-content-generator'),
                'queue_item_deleted' => __('Queue item deleted.', 'gpt3-ai-content-generator'),
                'error_deleting_queue_item' => __('Error deleting item:', 'gpt3-ai-content-generator'),
                'errorDeletingAllItems' => __('Error deleting items:', 'gpt3-ai-content-generator'),
                'retry_button' => __('Retry', 'gpt3-ai-content-generator'),
                'item_marked_retry' => __('Item marked for retry. Queue processing will pick it up.', 'gpt3-ai-content-generator'),
                'error_retrying_item' => __('Error retrying item:', 'gpt3-ai-content-generator'),
                'task_singular' => __('task', 'gpt3-ai-content-generator'),
                'task_plural' => __('tasks', 'gpt3-ai-content-generator'),
            ],
        ]);
    }
}

class AIPKit_AI_Forms_Assets extends AIPKit_Admin_Asset_Base
{
    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_ai_forms_assets']);
    }

    public function enqueue_ai_forms_assets($hook_suffix)
    {
        $screen = get_current_screen();

        if (! $this->is_aipkit_page($screen)) {
            return;
        }

        $this->enqueue_admin_main_script();
        $this->ensure_dashboard_core_data();
        $this->localize_data();
    }

    private function localize_data(): void
    {
        if (self::is_script_localized('aipkit-admin-main', 'aipkit_ai_forms_config')) {
            return;
        }

        $vector_store_localization = AIPKit_Providers::get_vector_store_localization_payload('ai_forms_ui');
        $embedding_localization = AIPKit_Providers::get_embedding_localization_payload('ai_forms_ui', false);

        wp_localize_script('aipkit-admin-main', 'aipkit_ai_forms_config', [
            'nonce_manage_forms' => wp_create_nonce('aipkit_manage_ai_forms_nonce'),
            'current_user_id' => get_current_user_id(),
            'vectorStores' => $vector_store_localization['vectorStores'],
            'embeddingProviderMap' => $embedding_localization['embeddingProviderMap'],
            'embeddingModels' => $embedding_localization['embeddingModels'],
            'text' => [
                'savingForm' => __('Saving form...', 'gpt3-ai-content-generator'),
                'formSaved' => __('Form saved successfully!', 'gpt3-ai-content-generator'),
                'errorSavingForm' => __('Error saving form.', 'gpt3-ai-content-generator'),
                'generatingForm' => __('Generating form draft...', 'gpt3-ai-content-generator'),
                'formGenerated' => __('Form draft generated. Review and save it when ready.', 'gpt3-ai-content-generator'),
                'errorGeneratingForm' => __('Error generating form draft.', 'gpt3-ai-content-generator'),
                'loadingForms' => __('Loading forms...', 'gpt3-ai-content-generator'),
                'deletingForm' => __('Deleting form...', 'gpt3-ai-content-generator'),
                'deletingAllForms' => __('Deleting all forms...', 'gpt3-ai-content-generator'),
                'duplicatingForm' => __('Duplicating...', 'gpt3-ai-content-generator'),
                'formDeleted' => __('Form deleted.', 'gpt3-ai-content-generator'),
                'allFormsDeleted' => __('All forms deleted.', 'gpt3-ai-content-generator'),
                'errorDeletingForm' => __('Error deleting form.', 'gpt3-ai-content-generator'),
                'errorDeletingAllForms' => __('Error deleting all forms.', 'gpt3-ai-content-generator'),
                'errorDuplicatingForm' => __('Error duplicating form.', 'gpt3-ai-content-generator'),
                'confirmReplaceGeneratedDraft' => __('Generating a new draft will replace the current title, prompt, and fields in the editor. Continue?', 'gpt3-ai-content-generator'),
                'formTitleRequired' => __('Form title is required.', 'gpt3-ai-content-generator'),
                'promptTemplateRequired' => __('Prompt template is required.', 'gpt3-ai-content-generator'),
                'generatorPromptRequired' => __('Describe the AI task before generating a form draft.', 'gpt3-ai-content-generator'),
                'generatorModelRequired' => __('Select an engine and model before generating a form draft.', 'gpt3-ai-content-generator'),
                'confirmSaveEmptyForm' => __('This form currently has no fields. Saving now will remove previously configured fields. Do you want to continue?', 'gpt3-ai-content-generator'),
                'editFormTitle' => __('Edit AI Form', 'gpt3-ai-content-generator'),
                'createNewFormTitle' => __('Create New AI Form', 'gpt3-ai-content-generator'),
                'confirmDeleteElement' => __('Are you sure you want to delete this form element?', 'gpt3-ai-content-generator'),
                'noOptionsConfigured' => __('No options configured', 'gpt3-ai-content-generator'),
                'settingsLabel' => __('Label Text', 'gpt3-ai-content-generator'),
                'settingsFieldId' => __('Field Variable Name (for prompt)', 'gpt3-ai-content-generator'),
                'settingsFieldIdHelp' => __('Use as {your_variable_name} in the Prompt Template. Must be unique and contain only letters, numbers, underscores.', 'gpt3-ai-content-generator'),
                'settingsPlaceholder' => __('Placeholder Text', 'gpt3-ai-content-generator'),
                'settingsRequired' => __('Required Field', 'gpt3-ai-content-generator'),
                'settingsSelectOptions' => __('Options (Value|Text)', 'gpt3-ai-content-generator'),
                'settingsSelectOptionValue' => __('Value', 'gpt3-ai-content-generator'),
                'settingsSelectOptionText' => __('Display Text', 'gpt3-ai-content-generator'),
                'settingsAddOption' => __('Add Option', 'gpt3-ai-content-generator'),
                'settingsRemoveOption' => __('Remove Option', 'gpt3-ai-content-generator'),
                'settingsDoneButton' => __('Done', 'gpt3-ai-content-generator'),
                'errorUniqueFieldId' => __('Field Variable Name must be unique and valid (letters, numbers, underscores).', 'gpt3-ai-content-generator'),
            ],
        ]);
    }
}

class AIPKit_Woocommerce_Writer_Assets extends AIPKit_Admin_Asset_Base
{
    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets($hook_suffix)
    {
        $screen = get_current_screen();

        if (! $this->is_aipkit_page($screen) || ! class_exists(aipkit_dashboard::class)) {
            return;
        }

        $modules = aipkit_dashboard::get_module_settings();
        if (empty($modules['content_writer']) || ! class_exists('WooCommerce')) {
            return;
        }

        $this->enqueue_admin_main_css();
        $this->enqueue_admin_main_script();
        $this->ensure_dashboard_core_data();
    }
}
