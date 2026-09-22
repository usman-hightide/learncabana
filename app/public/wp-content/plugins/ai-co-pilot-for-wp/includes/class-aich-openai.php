<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/class-aich-settings.php');

class AICH_Openai
{

    public static function check_api_key_is_valid()
    {
        $api_key = Wp_AHC_Settings::get_settings();
        $client = new GuzzleHttp\Client(['verify' => false]);

        try {
            $res = $client->request('GET', 'https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key['api_key'],
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            return false;
        }

        if ($res->getStatusCode() !== 200) {
            return false;
        }

        $body = json_decode($res->getBody(), true);

        if (!isset($body['data'])) {
            return false;
        }

        // Models for free version
        $models = [
	        "gpt-3.5-turbo-16k-0613",
	        "gpt-3.5-turbo-16k",
	        "gpt-3.5-turbo",
	        "gpt-3.5-turbo-0301",
	        "gpt-4-0613",
	        "gpt-4",
	        "gpt-4-0314",
	        "gpt-3.5-turbo-0613"
        ];

        if (get_option('wp_ai_pilot_models') !== false) {
            update_option('wp_ai_pilot_models', $models);
        } else {
            add_option('wp_ai_pilot_models', $models);
        }

        return $models;
    }

    public static function prepare_prompt($prompt, $selected_text)
    {

        return preg_replace_callback('/\[\[text_(\d+)\]\]/', function ($matches) use ($selected_text) {
            $index = $matches[1] - 1;
            return $selected_text[$index] ?? '';
        }, $prompt);
    }

    public static function get_max_tokens_for_model($model)
    {
        if ($model == 'gpt-3.5-turbo') {
            return 4000;
        }

        return 2000;
    }
}

new AICH_Openai();
