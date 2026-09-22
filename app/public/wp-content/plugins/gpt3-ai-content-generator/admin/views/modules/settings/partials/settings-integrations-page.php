<?php
/**
 * Partial: Integrations Settings Page
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$current_elevenlabs_api_key = (string) ($elevenlabs_data['api_key'] ?? '');
$current_elevenlabs_default_voice = (string) ($elevenlabs_data['voice_id'] ?? '');
$current_elevenlabs_default_model = (string) ($elevenlabs_data['model_id'] ?? '');

$current_replicate_api_key = (string) ($replicate_data['api_key'] ?? '');
$current_pexels_api_key = (string) ($pexels_data['api_key'] ?? '');
$current_pixabay_api_key = (string) ($pixabay_data['api_key'] ?? '');
$current_pinecone_api_key = (string) ($pinecone_data['api_key'] ?? '');
$current_qdrant_url = (string) ($qdrant_data['url'] ?? '');
$current_qdrant_api_key = (string) ($qdrant_data['api_key'] ?? '');
$current_chroma_url = (string) ($chroma_data['url'] ?? '');
$current_chroma_api_key = (string) ($chroma_data['api_key'] ?? '');
$current_chroma_tenant = (string) ($chroma_data['tenant'] ?? ($chroma_defaults['tenant'] ?? 'default_tenant'));
$current_chroma_database = (string) ($chroma_data['database'] ?? ($chroma_defaults['database'] ?? 'default_database'));

$elevenlabs_voice_list = is_array($elevenlabs_voice_list ?? null) ? $elevenlabs_voice_list : [];
$elevenlabs_model_list = is_array($elevenlabs_model_list ?? null) ? $elevenlabs_model_list : [];
$replicate_model_list = is_array($replicate_model_list ?? null) ? $replicate_model_list : [];
$pinecone_index_list = is_array($pinecone_index_list ?? null) ? $pinecone_index_list : [];
$qdrant_collection_list = is_array($qdrant_collection_list ?? null) ? $qdrant_collection_list : [];
$chroma_collection_list = is_array($chroma_collection_list ?? null) ? $chroma_collection_list : [];

$normalize_synced_select_options = static function (array $items, ?array $value_keys = null, ?array $label_keys = null): array {
    $options = [];
    $value_keys = $value_keys ?: ['id', 'name', 'model', 'index_name', 'collection_name'];
    $label_keys = $label_keys ?: ['name', 'id', 'model', 'index_name', 'collection_name'];

    foreach ($items as $item) {
        $value = '';
        $label = '';

        if (is_array($item) || is_object($item)) {
            foreach ($value_keys as $key) {
                $candidate = is_array($item) ? ($item[$key] ?? null) : ($item->{$key} ?? null);
                if (is_scalar($candidate) && (string) $candidate !== '') {
                    $value = trim(wp_strip_all_tags((string) $candidate));
                    break;
                }
            }

            foreach ($label_keys as $key) {
                $candidate = is_array($item) ? ($item[$key] ?? null) : ($item->{$key} ?? null);
                if (is_scalar($candidate) && (string) $candidate !== '') {
                    $label = trim(wp_strip_all_tags((string) $candidate));
                    break;
                }
            }
        } elseif (is_scalar($item)) {
            $value = trim(wp_strip_all_tags((string) $item));
            $label = $value;
        }

        if ($value === '' && $label !== '') {
            $value = $label;
        }
        if ($label === '' && $value !== '') {
            $label = $value;
        }
        if ($value === '' || $label === '') {
            continue;
        }

        $dedupe_key = strtolower($value);
        if (!isset($options[$dedupe_key])) {
            $options[$dedupe_key] = [
                'value' => $value,
                'label' => $label,
            ];
        }
    }

    $options = array_values($options);
    usort($options, static function (array $a, array $b): int {
        return strcasecmp($a['label'], $b['label']);
    });

    return $options;
};

$replicate_synced_model_options = $normalize_synced_select_options($replicate_model_list);
$pinecone_synced_index_options = $normalize_synced_select_options($pinecone_index_list);
$qdrant_synced_collection_options = $normalize_synced_select_options($qdrant_collection_list);
$chroma_synced_collection_options = $normalize_synced_select_options(
    $chroma_collection_list,
    ['name', 'collection_name', 'id'],
    ['name', 'collection_name', 'id']
);
?>

<div class="aipkit_form-group aipkit_settings_simple_row aipkit_settings_simple_row--provider" id="aipkit_settings_integrations_provider_row">
    <label class="aipkit_form-label" for="aipkit_settings_integration_provider">
        <?php esc_html_e('Provider', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Choose integration to edit.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_settings_provider_row_content">
        <select
            id="aipkit_settings_integration_provider"
            class="aipkit_form-input"
            data-aipkit-picker-title="<?php esc_attr_e('Integration provider', 'gpt3-ai-content-generator'); ?>"
        >
            <option value="elevenlabs"><?php esc_html_e('ElevenLabs', 'gpt3-ai-content-generator'); ?></option>
            <option value="replicate"><?php esc_html_e('Replicate', 'gpt3-ai-content-generator'); ?></option>
            <option value="pinecone"><?php esc_html_e('Pinecone', 'gpt3-ai-content-generator'); ?></option>
            <option value="qdrant"><?php esc_html_e('Qdrant', 'gpt3-ai-content-generator'); ?></option>
            <option value="chroma"><?php esc_html_e('Chroma', 'gpt3-ai-content-generator'); ?></option>
            <option value="pexels"><?php esc_html_e('Pexels', 'gpt3-ai-content-generator'); ?></option>
            <option value="pixabay"><?php esc_html_e('Pixabay', 'gpt3-ai-content-generator'); ?></option>
        </select>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_elevenlabs_api_key_row" data-aipkit-integration-provider="elevenlabs" hidden>
    <label class="aipkit_form-label" for="aipkit_elevenlabs_api_key">
        <?php esc_html_e('ElevenLabs API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Required for ElevenLabs voices.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input
                type="password"
                id="aipkit_elevenlabs_api_key"
                name="elevenlabs_api_key"
                class="aipkit_form-input aipkit_autosave_trigger"
                value="<?php echo esc_attr($current_elevenlabs_api_key); ?>"
                placeholder="<?php esc_attr_e('Enter your ElevenLabs API key', 'gpt3-ai-content-generator'); ?>"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                data-lpignore="true"
                data-1p-ignore="true"
                data-form-type="other"
            />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="https://elevenlabs.io/app/settings/api-keys" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get Key', 'gpt3-ai-content-generator'); ?>
        </a>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_elevenlabs_voice_row" data-aipkit-integration-provider="elevenlabs" hidden>
    <label class="aipkit_form-label" for="aipkit_elevenlabs_voice_id">
        <?php esc_html_e('ElevenLabs Voices', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Sync voices.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_elevenlabs_voice_id" name="elevenlabs_voice_id" class="aipkit_form-input aipkit_autosave_trigger">
            <option value=""><?php esc_html_e('-- Select a Voice (Optional) --', 'gpt3-ai-content-generator'); ?></option>
            <?php
            if (!empty($elevenlabs_voice_list)) {
                foreach ($elevenlabs_voice_list as $voice) {
                    $voice_id = isset($voice['id']) ? (string) $voice['id'] : '';
                    if ($voice_id === '') {
                        continue;
                    }
                    $voice_name = isset($voice['name']) && $voice['name'] !== '' ? (string) $voice['name'] : $voice_id;
                    echo '<option value="' . esc_attr($voice_id) . '" ' . selected($current_elevenlabs_default_voice, $voice_id, false) . '>' . esc_html($voice_name) . '</option>';
                }
            } elseif ($current_elevenlabs_default_voice !== '') {
                echo '<option value="' . esc_attr($current_elevenlabs_default_voice) . '" selected>' . esc_html($current_elevenlabs_default_voice) . '</option>';
            }
            ?>
        </select>
        <button type="button" id="aipkit_sync_elevenlabs_voices" class="button button-secondary aipkit_btn aipkit_sync_btn" data-provider="ElevenLabs">
            <span class="aipkit_btn-text"><?php esc_html_e('Sync Voices', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner"></span>
        </button>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_elevenlabs_model_row" data-aipkit-integration-provider="elevenlabs" hidden>
    <label class="aipkit_form-label" for="aipkit_elevenlabs_tts_model_id">
        <?php esc_html_e('ElevenLabs Models', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Sync models.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_elevenlabs_tts_model_id" name="elevenlabs_model_id" class="aipkit_form-input aipkit_autosave_trigger">
            <option value=""><?php esc_html_e('-- Select a Model (Optional) --', 'gpt3-ai-content-generator'); ?></option>
            <?php
            if (!empty($elevenlabs_model_list)) {
                foreach ($elevenlabs_model_list as $model) {
                    $model_id = isset($model['id']) ? (string) $model['id'] : '';
                    if ($model_id === '') {
                        continue;
                    }
                    $model_name = isset($model['name']) && $model['name'] !== '' ? (string) $model['name'] : $model_id;
                    echo '<option value="' . esc_attr($model_id) . '" ' . selected($current_elevenlabs_default_model, $model_id, false) . '>' . esc_html($model_name) . '</option>';
                }
            } elseif ($current_elevenlabs_default_model !== '') {
                echo '<option value="' . esc_attr($current_elevenlabs_default_model) . '" selected>' . esc_html($current_elevenlabs_default_model) . '</option>';
            }
            ?>
        </select>
        <button type="button" id="aipkit_sync_elevenlabs_models_btn" class="button button-secondary aipkit_btn aipkit_sync_btn" data-provider="ElevenLabsModels">
            <span class="aipkit_btn-text"><?php esc_html_e('Sync Models', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner"></span>
        </button>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_replicate_api_key_row" data-aipkit-integration-provider="replicate" hidden>
    <label class="aipkit_form-label" for="aipkit_replicate_api_key">
        <?php esc_html_e('Replicate API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Use Replicate for image generation.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input
                type="password"
                id="aipkit_replicate_api_key"
                name="replicate_api_key"
                class="aipkit_form-input aipkit_autosave_trigger"
                value="<?php echo esc_attr($current_replicate_api_key); ?>"
                placeholder="<?php esc_attr_e('Enter your Replicate API key', 'gpt3-ai-content-generator'); ?>"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                data-lpignore="true"
                data-1p-ignore="true"
                data-form-type="other"
            />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="https://replicate.com/account/api-tokens" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get Key', 'gpt3-ai-content-generator'); ?>
        </a>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_replicate_model_list_row" data-aipkit-integration-provider="replicate" hidden>
    <label class="aipkit_form-label" for="aipkit_replicate_model">
        <?php esc_html_e('Replicate Models', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Sync models.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_replicate_model" class="aipkit_form-input" data-aipkit-empty-label="<?php esc_attr_e('No models synced yet.', 'gpt3-ai-content-generator'); ?>">
            <option value=""><?php esc_html_e('-- Select Synced Model --', 'gpt3-ai-content-generator'); ?></option>
            <?php foreach ($replicate_synced_model_options as $option) : ?>
                <option value="<?php echo esc_attr($option['value']); ?>"><?php echo esc_html($option['label']); ?></option>
            <?php endforeach; ?>
        </select>
        <button
            type="button"
            id="aipkit_sync_replicate_models_btn"
            class="button button-secondary aipkit_btn aipkit_sync_btn"
            data-provider="Replicate"
            title="<?php esc_attr_e('Sync available models from Replicate', 'gpt3-ai-content-generator'); ?>"
        >
            <span class="aipkit_btn-text"><?php esc_html_e('Sync Models', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner"></span>
        </button>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_replicate_safety_row" data-aipkit-integration-provider="replicate" hidden>
    <label class="aipkit_form-label" for="aipkit_replicate_disable_safety_checker">
        <?php esc_html_e('Disable Safety Checker', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Override image safety checks.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <label class="aipkit_settings_big_checkbox" for="aipkit_replicate_disable_safety_checker">
        <input
            type="checkbox"
            id="aipkit_replicate_disable_safety_checker"
            name="replicate_disable_safety_checker"
            class="aipkit_autosave_trigger"
            value="1"
            <?php checked($replicate_disable_safety_checker, true); ?>
        />
        <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
            <span class="dashicons dashicons-saved"></span>
        </span>
        <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
    </label>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_pinecone_api_key_row" data-aipkit-integration-provider="pinecone" hidden>
    <label class="aipkit_form-label" for="aipkit_pinecone_api_key">
        <?php esc_html_e('Pinecone API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Enter your Pinecone API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input
                type="password"
                id="aipkit_pinecone_api_key"
                name="pinecone_api_key"
                class="aipkit_form-input aipkit_autosave_trigger"
                value="<?php echo esc_attr($current_pinecone_api_key); ?>"
                placeholder="<?php esc_attr_e('Enter your Pinecone API key', 'gpt3-ai-content-generator'); ?>"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                data-lpignore="true"
                data-1p-ignore="true"
                data-form-type="other"
            />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="https://app.pinecone.io/" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get Key', 'gpt3-ai-content-generator'); ?>
        </a>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_pinecone_index_list_row" data-aipkit-integration-provider="pinecone" hidden>
    <label class="aipkit_form-label" for="aipkit_pinecone_default_index">
        <?php esc_html_e('Synced Indexes', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Synced Pinecone indexes.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_pinecone_default_index" class="aipkit_form-input" data-aipkit-empty-label="<?php esc_attr_e('No indexes synced yet.', 'gpt3-ai-content-generator'); ?>">
            <option value=""><?php esc_html_e('-- Select Synced Index --', 'gpt3-ai-content-generator'); ?></option>
            <?php foreach ($pinecone_synced_index_options as $option) : ?>
                <option value="<?php echo esc_attr($option['value']); ?>"><?php echo esc_html($option['label']); ?></option>
            <?php endforeach; ?>
        </select>
        <button
            type="button"
            id="aipkit_sync_pinecone_indexes_btn"
            class="button button-secondary aipkit_btn aipkit_sync_btn"
            data-provider="PineconeIndexes"
            title="<?php esc_attr_e('Sync available indexes from Pinecone', 'gpt3-ai-content-generator'); ?>"
        >
            <span class="aipkit_btn-text"><?php esc_html_e('Sync Indexes', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner"></span>
        </button>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_qdrant_url_row" data-aipkit-integration-provider="qdrant" hidden>
    <label class="aipkit_form-label" for="aipkit_qdrant_url">
        <?php esc_html_e('Qdrant URL', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Enter your Qdrant endpoint.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <input
            type="url"
            id="aipkit_qdrant_url"
            name="qdrant_url"
            class="aipkit_form-input aipkit_autosave_trigger"
            value="<?php echo esc_attr($current_qdrant_url); ?>"
            placeholder="<?php esc_attr_e('Enter your Qdrant URL', 'gpt3-ai-content-generator'); ?>"
        />
        <span class="aipkit_input-button-spacer"></span>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_qdrant_api_key_row" data-aipkit-integration-provider="qdrant" hidden>
    <label class="aipkit_form-label" for="aipkit_qdrant_api_key">
        <?php esc_html_e('Qdrant API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Enter your Qdrant API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input
                type="password"
                id="aipkit_qdrant_api_key"
                name="qdrant_api_key"
                class="aipkit_form-input aipkit_autosave_trigger"
                value="<?php echo esc_attr($current_qdrant_api_key); ?>"
                placeholder="<?php esc_attr_e('Enter your Qdrant API key', 'gpt3-ai-content-generator'); ?>"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                data-lpignore="true"
                data-1p-ignore="true"
                data-form-type="other"
            />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="https://cloud.qdrant.io/" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get Key', 'gpt3-ai-content-generator'); ?>
        </a>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_qdrant_collection_list_row" data-aipkit-integration-provider="qdrant" hidden>
    <label class="aipkit_form-label" for="aipkit_qdrant_default_collection">
        <?php esc_html_e('Collections', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Select a Qdrant collection.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_qdrant_default_collection" class="aipkit_form-input" data-aipkit-empty-label="<?php esc_attr_e('No collections synced yet.', 'gpt3-ai-content-generator'); ?>">
            <option value=""><?php esc_html_e('-- Select Collection --', 'gpt3-ai-content-generator'); ?></option>
            <?php foreach ($qdrant_synced_collection_options as $option) : ?>
                <option value="<?php echo esc_attr($option['value']); ?>"><?php echo esc_html($option['label']); ?></option>
            <?php endforeach; ?>
        </select>
        <button
            type="button"
            id="aipkit_sync_qdrant_collections_btn"
            class="button button-secondary aipkit_btn aipkit_sync_btn"
            data-provider="QdrantCollections"
            title="<?php esc_attr_e('Sync available collections from Qdrant', 'gpt3-ai-content-generator'); ?>"
        >
            <span class="aipkit_btn-text"><?php esc_html_e('Sync Collections', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner"></span>
        </button>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_chroma_url_row" data-aipkit-integration-provider="chroma" hidden>
    <label class="aipkit_form-label" for="aipkit_chroma_url">
        <?php esc_html_e('Chroma URL', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Enter your Chroma endpoint.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <input
            type="url"
            id="aipkit_chroma_url"
            name="chroma_url"
            class="aipkit_form-input aipkit_autosave_trigger"
            value="<?php echo esc_attr($current_chroma_url); ?>"
            placeholder="<?php esc_attr_e('https://api.trychroma.com', 'gpt3-ai-content-generator'); ?>"
        />
        <span class="aipkit_input-button-spacer"></span>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_chroma_api_key_row" data-aipkit-integration-provider="chroma" hidden>
    <label class="aipkit_form-label" for="aipkit_chroma_api_key">
        <?php esc_html_e('Chroma API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Required for Chroma Cloud or authenticated servers.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input
                type="password"
                id="aipkit_chroma_api_key"
                name="chroma_api_key"
                class="aipkit_form-input aipkit_autosave_trigger"
                value="<?php echo esc_attr($current_chroma_api_key); ?>"
                placeholder="<?php esc_attr_e('Enter your Chroma API key', 'gpt3-ai-content-generator'); ?>"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                data-lpignore="true"
                data-1p-ignore="true"
                data-form-type="other"
            />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="https://trychroma.com/" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get Key', 'gpt3-ai-content-generator'); ?>
        </a>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_chroma_tenant_row" data-aipkit-integration-provider="chroma" hidden>
    <label class="aipkit_form-label" for="aipkit_chroma_tenant">
        <?php esc_html_e('Tenant', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Use default_tenant for local Chroma.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <input
            type="text"
            id="aipkit_chroma_tenant"
            name="chroma_tenant"
            class="aipkit_form-input aipkit_autosave_trigger"
            value="<?php echo esc_attr($current_chroma_tenant); ?>"
            placeholder="<?php esc_attr_e('default_tenant', 'gpt3-ai-content-generator'); ?>"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="false"
        />
        <span class="aipkit_input-button-spacer"></span>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_chroma_database_row" data-aipkit-integration-provider="chroma" hidden>
    <label class="aipkit_form-label" for="aipkit_chroma_database">
        <?php esc_html_e('Database', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Use default_database for local Chroma.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <input
            type="text"
            id="aipkit_chroma_database"
            name="chroma_database"
            class="aipkit_form-input aipkit_autosave_trigger"
            value="<?php echo esc_attr($current_chroma_database); ?>"
            placeholder="<?php esc_attr_e('default_database', 'gpt3-ai-content-generator'); ?>"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="false"
        />
        <span class="aipkit_input-button-spacer"></span>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_chroma_collection_list_row" data-aipkit-integration-provider="chroma" hidden>
    <label class="aipkit_form-label" for="aipkit_chroma_default_collection">
        <?php esc_html_e('Collections', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Select a Chroma collection.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <select id="aipkit_chroma_default_collection" class="aipkit_form-input" data-aipkit-empty-label="<?php esc_attr_e('No collections synced yet.', 'gpt3-ai-content-generator'); ?>">
            <option value=""><?php esc_html_e('-- Select Collection --', 'gpt3-ai-content-generator'); ?></option>
            <?php foreach ($chroma_synced_collection_options as $option) : ?>
                <option value="<?php echo esc_attr($option['value']); ?>"><?php echo esc_html($option['label']); ?></option>
            <?php endforeach; ?>
        </select>
        <button
            type="button"
            id="aipkit_sync_chroma_collections_btn"
            class="button button-secondary aipkit_btn aipkit_sync_btn"
            data-provider="ChromaCollections"
            title="<?php esc_attr_e('Sync available collections from Chroma', 'gpt3-ai-content-generator'); ?>"
        >
            <span class="aipkit_btn-text"><?php esc_html_e('Sync Collections', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner"></span>
        </button>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_pexels_api_key_row" data-aipkit-integration-provider="pexels" hidden>
    <label class="aipkit_form-label" for="aipkit_pexels_api_key">
        <?php esc_html_e('Pexels API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Enter your Pexels API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input
                type="password"
                id="aipkit_pexels_api_key"
                name="pexels_api_key"
                class="aipkit_form-input aipkit_autosave_trigger"
                value="<?php echo esc_attr($current_pexels_api_key); ?>"
                placeholder="<?php esc_attr_e('Enter your Pexels API key', 'gpt3-ai-content-generator'); ?>"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                data-lpignore="true"
                data-1p-ignore="true"
                data-form-type="other"
            />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="https://www.pexels.com/api/" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get Key', 'gpt3-ai-content-generator'); ?>
        </a>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_pixabay_api_key_row" data-aipkit-integration-provider="pixabay" hidden>
    <label class="aipkit_form-label" for="aipkit_pixabay_api_key">
        <?php esc_html_e('Pixabay API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Enter your Pixabay API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input
                type="password"
                id="aipkit_pixabay_api_key"
                name="pixabay_api_key"
                class="aipkit_form-input aipkit_autosave_trigger"
                value="<?php echo esc_attr($current_pixabay_api_key); ?>"
                placeholder="<?php esc_attr_e('Enter your Pixabay API key', 'gpt3-ai-content-generator'); ?>"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                data-lpignore="true"
                data-1p-ignore="true"
                data-form-type="other"
            />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="https://pixabay.com/api/docs/" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get Key', 'gpt3-ai-content-generator'); ?>
        </a>
        <span class="aipkit_input-button-spacer"></span>
    </div>
</div>
