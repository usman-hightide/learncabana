<?php

require_once(__DIR__ . '/class-ai-content-helper-utils.php');
require_once plugin_dir_path(dirname(__FILE__)) . '/vendor/autoload.php'; // remove this line if you use a PHP Framework.
require_once(__DIR__ . '/class-aich-settings.php');
require_once(__DIR__ . '/class-aich-openai.php');
require_once(__DIR__ . '/class-ach-promptmanager.php');

class AI_Content_Helper_Ajax
{
    public function __construct()
    {
        add_action('rest_api_init', function () {
            register_rest_route('ai-content-helper/openai/v1', '/generated-content', array(
                'methods' => 'POST',
                'callback' => [$this, 'ach_openai_generate_content'],
                'permission_callback' => function () {
                    return is_user_logged_in() && current_user_can('edit_posts');
                }
            ));
        });
    }

    public function ach_openai_generate_content(WP_REST_Request $req)
    {
        $client = new GuzzleHttp\Client(['verify' => false ]);
        $body = $req->get_body();
        $prompt = json_decode($body, true);

        if ($prompt['requestType'] === '') {
            return new WP_Error('message', 'Invalid type', array('status' => 400));
        }

        if (empty($prompt['text'])) {
            return new WP_Error('message', 'Missing text parameter', array('status' => 400));
        }

        $filtered_prompt = AICH_Openai::prepare_prompt($prompt['promptItem']['prompt_content'], $prompt['text']);
        $settings = Wp_AHC_Settings::get_settings();
        $model = $settings['ai_model'];
        $temperature = sanitize_text_field($settings['ai_temprature']);
        $max_tokens = sanitize_text_field($prompt['promptItem']['word_count']);
        $top_p = sanitize_text_field($settings['ai_top_prediction']);
        $frequency_penalty = sanitize_text_field($settings['ai_frequency_panalty']);
        $presence_penalty = sanitize_text_field($settings['ai_presence_panalty']);

        $n = 1;

        if (WP_AICH_Utils::isProActivated()) {
            $tone_of_voice = array_key_exists("ai_global_tune_voice", $settings) ?? $settings['ai_global_tune_voice'];
            $audience = array_key_exists("ai_global_audience", $settings) ?? $settings['ai_global_audience'];

            if ($prompt['promptItem']['ai_tune_voice'] !== '') {
                $filtered_prompt .= ' Possible Tone of Voice for: ' . $prompt['promptItem']['ai_tune_voice'];
            } else {
                $filtered_prompt .= ' Possible Tone of Voice for: ' . $tone_of_voice;
            }

            if ($prompt['promptItem']['ai_audience'] !== '') {
                $filtered_prompt .= ' Possible Audience for: ' . $prompt['promptItem']['ai_audience'];
            } else {
                $filtered_prompt .= ' Possible Audience for: ' . $audience;
            }

            $model = $prompt['promptItem']['ai_model'] !== '' ? $prompt['promptItem']['ai_model'] : $settings['ai_model'];
            $n = $prompt['promptItem']['ai_number_of_choices'] !== '' ? $prompt['promptItem']['ai_number_of_choices'] : $settings['ai_global_number_of_choices'];
        }

        try {
            if (stripos($model, 'gpt') !== false) {
                $res = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $settings['api_key'],
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $filtered_prompt
                            ]
                        ],
                        'temperature' => floatval($temperature),
                        'max_tokens' => floatval($max_tokens),
                        'top_p' => floatval($top_p),
                        'frequency_penalty' => floatval($frequency_penalty),
                        'presence_penalty' => floatval($presence_penalty),
                    ],
                ]);
            } else {
                $res = $client->request('POST', 'https://api.openai.com/v1/engines/' . $model . '/completions', [
                    'body' => json_encode([
                        'prompt' => $filtered_prompt,
                        'temperature' => floatval($temperature),
                        'max_tokens' => floatval($max_tokens),
                        'top_p' => floatval($top_p),
                        'best_of' => intval(1),
                        'frequency_penalty' => floatval($frequency_penalty),
                        'presence_penalty' => floatval($presence_penalty),
                        'stop' => 'none',
                        'n' => intval($n !== '' ? $n : 1)
                    ]),
                    'headers' => [
                        'Authorization' => 'Bearer ' . $settings['api_key'],
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            return new WP_Error(
                'message',
                json_decode((string)$response->getBody())->error->message,
                array('status' => 500)
            );
        }

        $body = $res->getBody();
        $json = json_decode($body, true);

        $choices = $json['choices'];
        if (count($choices) == 0) {
            return new WP_Error(
                'message',
                __('No completions found, please try again using different text.', 'ai-content-helper'),
                array('status' => 400)
            );
        }

        if (stripos($model, 'gpt') !== false) {
            $resultText = trim($choices[0]['message']['content']);
        } else {
            $resultText = trim($choices[0]['text']);
        }

        return new WP_REST_Response([
            'text' => $resultText,
            'tokens' => $max_tokens
        ], 200);
    }
}

new AI_Content_Helper_Ajax();