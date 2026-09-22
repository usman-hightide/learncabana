<?php

require_once(__DIR__ . '/class-aich-settings.php');

class AICH_Prompt_Manager_Controller
{
    public $default_prompts = [];

    public function get_prompt_by_lang_index($lang, $index)
    {
        $all_prompts = $this->get_prompt();
        $prepared_prompts = [];
        foreach ($all_prompts as $key => $prepare_prompts) {
            $prepared_prompts[$all_prompts[$key]['prompt_language']] = $all_prompts[$key]['new_prompt'];
        }
        return $prepared_prompts[$lang][$index];
    }

    public function get_prompt()
    {
        $prompt_by_lang = [];
        $settings = Wp_AHC_Settings::get_settings();
        $selected_languages = $settings['ai_language'];

        foreach ($settings['prompt_group'] as $key => $prompt) {
            if (in_array($prompt['prompt_language'], $selected_languages)) {
                $prompt_by_lang[] = $prompt;
            }
        }

        return $prompt_by_lang;
    }

    public function default_prompts()
    {
        $i = 0;
        $default_prompts = [];
        $languages_list = Wp_AHC_Settings::$languages;
        $prompts_list = array(
            'en' => [
                [
                    'prompt_title' => 'Write a paragraph on this',
                    'prompt_content' => "Write a paragraph on this topic: [[text_1]]. Format in HTML, using only these allowed tags:  <p> <strong> ",
                    'prompt_desc' => 'Use the same ideas using different words while maintaining the original meaning.',
                    'word' => [
                        'type' => 'fixed',
                        'value' => 300,
                    ],
                ],
                [
                    'prompt_title' => 'Continue this text',
                    'prompt_content' => "Continue this text: [[text_1]]. Format in HTML, using only these allowed tags: <p> <strong> <br>",
                    'prompt_desc' => 'Continue with the text you would like to continue with in the next paragraph.',
                    'word' => [
                        'type' => 'fixed',
                        'value' => 300,
                    ],

                ],
                [
                    'prompt_title' => 'Generate ideas on this',
                    'prompt_content' => "Generate a few ideas on that as bullet points: [[text_1]] Format in HTML, using only these allowed tags:  <li> <ul> <p> <strong>",
                    'prompt_desc' => "Unlock your creativity with our idea generator. Whether you're brainstorming for a project or seeking inspiration, this will help spark your imagination.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ]
                ],
                [
                    'prompt_title' => 'Write an article about this',
                    'prompt_content' => "Write a complete article about this:[[text_1]].  Format in HTML, using only these allowed tags: <h2><h3> <h4> <li> <ul> <p>  <strong>",
                    'prompt_desc' => "Optimize your blog posts to boost traffic and engagement by writing informative yet search engine-friendly posts. ",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 1500,
                    ],
                ],
                [
                    'prompt_title' => 'Generate a TL;DR',
                    'prompt_content' => "Generate a TL;DR of this text: [[text_1]]. Format in HTML, using only these allowed tags:  <p> <br> <strong> <li> <ul>",
                    'prompt_desc' => "Generate summarizes lengthy text into concise snippets. It saves time by providing a brief overview, allowing you to grasp the main points quickly.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 1500,
                    ],
                ],
                [
                    'prompt_title' => 'Summarize',
                    'prompt_content' => "Summarize this text: [[text_1]]. Format in HTML, using only these allowed tags:  <p> <strong> <li> <ul>",
                    'prompt_desc' => "Summarize any text short and concisely to make it easy to understand.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Summarize (concise)',
                    'prompt_content' => "Summarize this text in a concise way: [[text_1]]. Format in HTML, using only these allowed tags:  <p><strong> <li> <ul>",
                    'prompt_desc' => "Condense the text into a concise summary, capturing its essence in a brief",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Summarize (bullet points)',
                    'prompt_content' => "Summarize this text into bullet points: [[text_1]]. Format in HTML, using only these allowed tags:   <li> <ul> <p> <strong>",
                    'prompt_desc' => "Summarize a given text into concise bullet points for better understanding and quick reference.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Paraphrase',
                    'prompt_content' => "Paraphrase this text: [[text_1]] . Format in HTML, using only these allowed tags:  <p> <strong> ",
                    'prompt_desc' => "Use the same ideas using different words while maintaining the original meaning.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Paraphrase (sarcastic)',
                    'prompt_content' => "Paraphrase this text in a sarcastic way: [[text_1]]. Format in HTML, using only these allowed tags:  <p> <strong> ",
                    'prompt_desc' => "Use the same ideas using different words while maintaining the original meaning.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Paraphrase (humorous)',
                    'prompt_content' => "Paraphrase this text in a humorous way: [[text_1]].  Format in HTML, using only these allowed tags:  <p> <strong> <li> <ul>",
                    'prompt_desc' => "Use the same ideas using different words while maintaining the original meaning.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Generate subtitle',
                    'prompt_content' => "Generate a title for this text: [[text_1]] Format in HTML, using only these allowed tags:  <h2>",
                    'prompt_desc' => "Automatically generates accurate and synchronized subtitles for videos, making them more accessible and engaging for viewers.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Turn into advertisement',
                    'prompt_content' => "Turn the following text into a creative advertisement: [[text_1]] Format in HTML, using only these allowed tags:  <p> <strong> <li> <ul>",
                    'prompt_desc' => "Take your advertisement to the next level and make it successful.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Explain to a 5 years old kid',
                    'prompt_content' => "Explain this to a 5 years old kid: [[text_1]] . Format in HTML, using only these allowed tags:  <p>  <strong> <li> <ul>",
                    'prompt_desc' => "Explaining to a 5-year-old means using simple words. It's like telling a story that helps them understand things easily.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Find a matching quote',
                    'prompt_content' => "Find a matching quote for the following text: [[text_1]]. Format in HTML, using only these allowed tags:  <p> <strong> <li> <ul>",
                    'prompt_desc' => "Discover the perfect words that resonate with your thoughts and emotions. Explore our vast collection of quotes to find the perfect match for every occasion and mood.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Generate image idea',
                    'prompt_content' => "Describe an image that would match this text: [[text_1]]",
                    'prompt_desc' => "Discover a world of endless creativity with our image idea generator. Unlock inspiration and generate unique visuals for your next project.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Content Rewriter',
                    'prompt_content' => "Content rewriter on this: [[text_1]]  Format in HTML, using only these allowed tags:  <p> <strong> <li> <ul>",
                    'prompt_desc' => "Transform your written material effortlessly. Enhance readability and uniqueness by generating fresh variations with ease.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Write a testimonials / reviews on this',
                    'prompt_content' => "Create 5 creative customer reviews for a product on this: [[text_1]] ,Format in HTML, using only these allowed tags:  <p> <strong> ",
                    'prompt_desc' => "Dive into a collection of captivating testimonials and insightful reviews that will inspire and inform your decision-making process.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 600,
                    ],
                ],
                [
                    'prompt_title' => 'Write a blog title',
                    'prompt_content' => "Generate 10 catchy blog titles for: [[text_1]]. Format in HTML, using only these allowed tags:  <h1>",
                    'prompt_desc' => "Unleash the Power of Words to Craft a Compelling Blog Title that Grabs Readers' Attention",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 400,
                    ],
                ],
                [
                    'prompt_title' => 'Write FAQ',
                    'prompt_content' => "Generate list of 10 frequently asked questions based on: [[text_1]] Format in HTML, using only these allowed tags: <h3> <li> <ul> <p> <strong>",
                    'prompt_desc' => "Craft comprehensive FAQs to address common queries, providing users with clear and concise answers. Enhance user experience and satisfaction.",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 600,
                    ],
                ],
                [
                    'prompt_title' => 'Write FAQ Answer',
                    'prompt_content' => "Generate creative 5 answers to question: [[text_1]]. Format in HTML, using only these allowed tags: <h3> <li> <ul> <p> <strong> ",
                    'prompt_desc' => "Craft concise and informative FAQ answers to address common queries and enhance user understanding",
                    'word' => [
                        'type' => 'fixed',
                        'value' => 600,
                    ],
                ],
            ]
        );

        foreach ($prompts_list as $key => $single_prompt) {
            $default_prompts[] = array(
                'prompt_language' => $key,
                'group_language' => $languages_list[$key],
            );

            $prompt_by_lang = [];
            foreach ($single_prompt as $single_item_key => $single_item) {
                if ($default_prompts[$i]['prompt_language'] === $key) {
                    $prompt_by_lang[] = array(
                        'prompt_title' => $single_prompt[$single_item_key]['prompt_title'],
                        'prompt_content' => $single_prompt[$single_item_key]['prompt_content'],
                        'prompt_desc' => $single_prompt[$single_item_key]['prompt_desc'],
                        'word_count' => $single_prompt[$single_item_key]['word']['value'],
                    );
                }
            }

            $default_prompts[$i]['new_prompt'] = $prompt_by_lang;
            $i++;
        }

        $this->default_prompts = $default_prompts;
        return $default_prompts;
    }
}
