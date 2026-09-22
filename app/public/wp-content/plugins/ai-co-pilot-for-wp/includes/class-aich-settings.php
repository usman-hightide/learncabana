<?php
require_once(__DIR__ . '/class-ach-promptmanager.php');
/**
 * Register plugin settings panels.
 *
 * This class defines all code necessary to manage plugin settings.
 *
 * @since      1.0.0
 * @package    Wp_AICH
 * @subpackage Wp_AICH/includes
 * @author     BOOM DEVS <contact@wpmessiah.com>
 */
class Wp_AHC_Settings
{

    public static $plugin_file_url = WP_AHC_URL;

    /**
     * Plugin settings prefix.
     *
     * @var string
     */
    public static $prefix = WP_AHC_SLUG;

    /**
     * Plugin settings slug.
     *
     * @var string
     */
    public static $slug = WP_AHC_SLUG;
    public static $languages = [
        'bs'          => 'Bosnian',
        'ee_TG'       => 'Ewe (Togo)',
        'ms'          => 'Malay',
        'kam_KE'      => 'Kamba (Kenya)',
        'mt'          => 'Maltese',
        'ha'          => 'Hausa',
        'es_HN'       => 'Spanish (Honduras)',
        'ml_IN'       => 'Malayalam (India)',
        'ro_MD'       => 'Romanian (Moldova)',
        'kab_DZ'      => 'Kabyle (Algeria)',
        'he'          => 'Hebrew',
        'es_CO'       => 'Spanish (Colombia)',
        'my'          => 'Burmese',
        'es_PA'       => 'Spanish (Panama)',
        'az_Latn'     => 'Azerbaijani (Latin)',
        'mer'         => 'Meru',
        'en_NZ'       => 'English (New Zealand)',
        'xog_UG'      => 'Soga (Uganda)',
        'sg'          => 'Sango',
        'fr_GP'       => 'French (Guadeloupe)',
        'sr_Cyrl_BA'  => 'Serbian (Cyrillic, Bosnia and Herzegovina)',
        'hi'          => 'Hindi',
        'fil_PH'      => 'Filipino (Philippines)',
        'lt_LT'       => 'Lithuanian (Lithuania)',
        'si'          => 'Sinhala',
        'en_MT'       => 'English (Malta)',
        'si_LK'       => 'Sinhala (Sri Lanka)',
        'luo_KE'      => 'Luo (Kenya)',
        'it_CH'       => 'Italian (Switzerland)',
        'teo'         => 'Teso',
        'mfe'         => 'Morisyen',
        'sk'          => 'Slovak',
        'uz_Cyrl_UZ'  => 'Uzbek (Cyrillic, Uzbekistan)',
        'sl'          => 'Slovenian',
        'rm_CH'       => 'Romansh (Switzerland)',
        'az_Cyrl_AZ'  => 'Azerbaijani (Cyrillic, Azerbaijan)',
        'fr_GQ'       => 'French (Equatorial Guinea)',
        'kde'         => 'Makonde',
        'sn'          => 'Shona',
        'cgg_UG'      => 'Chiga (Uganda)',
        'so'          => 'Somali',
        'fr_RW'       => 'French (Rwanda)',
        'es_SV'       => 'Spanish (El Salvador)',
        'mas_TZ'      => 'Masai (Tanzania)',
        'en_MU'       => 'English (Mauritius)',
        'sq'          => 'Albanian',
        'hr'          => 'Croatian',
        'sr'          => 'Serbian',
        'en_PH'       => 'English (Philippines)',
        'ca'          => 'Catalan',
        'hu'          => 'Hungarian',
        'mk_MK'       => 'Macedonian (Macedonia)',
        'fr_TD'       => 'French (Chad)',
        'nb'          => 'Norwegian Bokmål',
        'sv'          => 'Swedish',
        'kln_KE'      => 'Kalenjin (Kenya)',
        'sw'          => 'Swahili',
        'nd'          => 'North Ndebele',
        'sr_Latn'     => 'Serbian (Latin)',
        'el_GR'       => 'Greek (Greece)',
        'hy'          => 'Armenian',
        'ne'          => 'Nepali',
        'el_CY'       => 'Greek (Cyprus)',
        'es_CR'       => 'Spanish (Costa Rica)',
        'fo_FO'       => 'Faroese (Faroe Islands)',
        'pa_Arab_PK'  => 'Punjabi (Arabic, Pakistan)',
        'seh'         => 'Sena',
        'ar_YE'       => 'Arabic (Yemen)',
        'ja_JP'       => 'Japanese (Japan)',
        'ur_PK'       => 'Urdu (Pakistan)',
        'pa_Guru'     => 'Punjabi (Gurmukhi)',
        'gl_ES'       => 'Galician (Spain)',
        'zh_Hant_HK'  => 'Chinese (Traditional Han, Hong Kong SAR China)',
        'ar_EG'       => 'Arabic (Egypt)',
        'nl'          => 'Dutch',
        'th_TH'       => 'Thai (Thailand)',
        'es_PE'       => 'Spanish (Peru)',
        'fr_KM'       => 'French (Comoros)',
        'nn'          => 'Norwegian Nynorsk',
        'kk_Cyrl_KZ'  => 'Kazakh (Cyrillic, Kazakhstan)',
        'kea'         => 'Kabuverdianu',
        'lv_LV'       => 'Latvian (Latvia)',
        'kln'         => 'Kalenjin',
        'tzm_Latn'    => 'Central Morocco Tamazight (Latin)',
        'yo'          => 'Yoruba',
        'gsw_CH'      => 'Swiss German (Switzerland)',
        'ha_Latn_GH'  => 'Hausa (Latin, Ghana)',
        'is_IS'       => 'Icelandic (Iceland)',
        'pt_BR'       => 'Portuguese (Brazil)',
        'cs'          => 'Czech',
        'en_PK'       => 'English (Pakistan)',
        'fa_IR'       => 'Persian (Iran)',
        'zh_Hans_SG'  => 'Chinese (Simplified Han, Singapore)',
        'luo'         => 'Luo',
        'ta'          => 'Tamil',
        'fr_TG'       => 'French (Togo)',
        'kde_TZ'      => 'Makonde (Tanzania)',
        'mr_IN'       => 'Marathi (India)',
        'ar_SA'       => 'Arabic (Saudi Arabia)',
        'ka_GE'       => 'Georgian (Georgia)',
        'mfe_MU'      => 'Morisyen (Mauritius)',
        'id'          => 'Indonesian',
        'fr_LU'       => 'French (Luxembourg)',
        'de_LU'       => 'German (Luxembourg)',
        'ru_MD'       => 'Russian (Moldova)',
        'cy'          => 'Welsh',
        'zh_Hans_HK'  => 'Chinese (Simplified Han, Hong Kong SAR China)',
        'te'          => 'Telugu',
        'bg_BG'       => 'Bulgarian (Bulgaria)',
        'shi_Latn'    => 'Tachelhit (Latin)',
        'ig'          => 'Igbo',
        'ses'         => 'Koyraboro Senni',
        'ii'          => 'Sichuan Yi',
        'es_BO'       => 'Spanish (Bolivia)',
        'th'          => 'Thai',
        'ko_KR'       => 'Korean (South Korea)',
        'ti'          => 'Tigrinya',
        'it_IT'       => 'Italian (Italy)',
        'shi_Latn_MA' => 'Tachelhit (Latin, Morocco)',
        'pt_MZ'       => 'Portuguese (Mozambique)',
        'ff_SN'       => 'Fulah (Senegal)',
        'haw'         => 'Hawaiian',
        'zh_Hans'     => 'Chinese (Simplified Han)',
        'so_KE'       => 'Somali (Kenya)',
        'bn_IN'       => 'Bengali (India)',
        'en_UM'       => 'English (U.S. Minor Outlying Islands)',
        'to'          => 'Tonga',
        'id_ID'       => 'Indonesian (Indonesia)',
        'uz_Cyrl'     => 'Uzbek (Cyrillic)',
        'en_GU'       => 'English (Guam)',
        'es_EC'       => 'Spanish (Ecuador)',
        'en_US_POSIX' => 'English (United States, Computer)',
        'sr_Latn_BA'  => 'Serbian (Latin, Bosnia and Herzegovina)',
        'is'          => 'Icelandic',
        'luy'         => 'Luyia',
        'tr'          => 'Turkish',
        'en_NA'       => 'English (Namibia)',
        'it'          => 'Italian',
        'da'          => 'Danish',
        'bo_IN'       => 'Tibetan (India)',
        'vun_TZ'      => 'Vunjo (Tanzania)',
        'ar_SD'       => 'Arabic (Sudan)',
        'uz_Latn_UZ'  => 'Uzbek (Latin, Uzbekistan)',
        'az_Latn_AZ'  => 'Azerbaijani (Latin, Azerbaijan)',
        'de'          => 'German',
        'es_GQ'       => 'Spanish (Equatorial Guinea)',
        'ta_IN'       => 'Tamil (India)',
        'de_DE'       => 'German (Germany)',
        'fr_FR'       => 'French (France)',
        'rof_TZ'      => 'Rombo (Tanzania)',
        'ar_LY'       => 'Arabic (Libya)',
        'en_BW'       => 'English (Botswana)',
        'asa'         => 'Asu',
        'zh'          => 'Chinese',
        'ha_Latn'     => 'Hausa (Latin)',
        'fr_NE'       => 'French (Niger)',
        'es_MX'       => 'Spanish (Mexico)',
        'bem_ZM'      => 'Bemba (Zambia)',
        'zh_Hans_CN'  => 'Chinese (Simplified Han, China)',
        'bn_BD'       => 'Bengali (Bangladesh)',
        'pt_GW'       => 'Portuguese (Guinea-Bissau)',
        'om'          => 'Oromo',
        'jmc'         => 'Machame',
        'de_AT'       => 'German (Austria)',
        'kk_Cyrl'     => 'Kazakh (Cyrillic)',
        'sw_TZ'       => 'Swahili (Tanzania)',
        'ar_OM'       => 'Arabic (Oman)',
        'et_EE'       => 'Estonian (Estonia)',
        'or'          => 'Oriya',
        'da_DK'       => 'Danish (Denmark)',
        'ro_RO'       => 'Romanian (Romania)',
        'zh_Hant'     => 'Chinese (Traditional Han)',
        'bm_ML'       => 'Bambara (Mali)',
        'ja'          => 'Japanese',
        'fr_CA'       => 'French (Canada)',
        'naq'         => 'Nama',
        'zu'          => 'Zulu',
        'en_IE'       => 'English (Ireland)',
        'ar_MA'       => 'Arabic (Morocco)',
        'es_GT'       => 'Spanish (Guatemala)',
        'uz_Arab_AF'  => 'Uzbek (Arabic, Afghanistan)',
        'en_AS'       => 'English (American Samoa)',
        'bs_BA'       => 'Bosnian (Bosnia and Herzegovina)',
        'am_ET'       => 'Amharic (Ethiopia)',
        'ar_TN'       => 'Arabic (Tunisia)',
        'haw_US'      => 'Hawaiian (United States)',
        'ar_JO'       => 'Arabic (Jordan)',
        'fa_AF'       => 'Persian (Afghanistan)',
        'uz_Latn'     => 'Uzbek (Latin)',
        'en_BZ'       => 'English (Belize)',
        'nyn_UG'      => 'Nyankole (Uganda)',
        'ebu_KE'      => 'Embu (Kenya)',
        'te_IN'       => 'Telugu (India)',
        'cy_GB'       => 'Welsh (United Kingdom)',
        'uk'          => 'Ukrainian',
        'nyn'         => 'Nyankole',
        'en_JM'       => 'English (Jamaica)',
        'en_US'       => 'English (United States)',
        'fil'         => 'Filipino',
        'ar_KW'       => 'Arabic (Kuwait)',
        'af_ZA'       => 'Afrikaans (South Africa)',
        'en_CA'       => 'English (Canada)',
        'fr_DJ'       => 'French (Djibouti)',
        'ti_ER'       => 'Tigrinya (Eritrea)',
        'ig_NG'       => 'Igbo (Nigeria)',
        'en_AU'       => 'English (Australia)',
        'ur'          => 'Urdu',
        'fr_MC'       => 'French (Monaco)',
        'pt_PT'       => 'Portuguese (Portugal)',
        'pa'          => 'Punjabi',
        'es_419'      => 'Spanish (Latin America)',
        'fr_CD'       => 'French (Congo - Kinshasa)',
        'en_SG'       => 'English (Singapore)',
        'bo_CN'       => 'Tibetan (China)',
        'kn_IN'       => 'Kannada (India)',
        'sr_Cyrl_RS'  => 'Serbian (Cyrillic, Serbia)',
        'lg_UG'       => 'Ganda (Uganda)',
        'gu_IN'       => 'Gujarati (India)',
        'ee'          => 'Ewe',
        'nd_ZW'       => 'North Ndebele (Zimbabwe)',
        'bem'         => 'Bemba',
        'uz'          => 'Uzbek',
        'sw_KE'       => 'Swahili (Kenya)',
        'sq_AL'       => 'Albanian (Albania)',
        'hr_HR'       => 'Croatian (Croatia)',
        'mas_KE'      => 'Masai (Kenya)',
        'el'          => 'Greek',
        'ti_ET'       => 'Tigrinya (Ethiopia)',
        'es_AR'       => 'Spanish (Argentina)',
        'pl'          => 'Polish',
        'en'          => 'English',
        'eo'          => 'Esperanto',
        'shi'         => 'Tachelhit',
        'kok'         => 'Konkani',
        'fr_CF'       => 'French (Central African Republic)',
        'fr_RE'       => 'French (Réunion)',
        'mas'         => 'Masai',
        'rof'         => 'Rombo',
        'ru_UA'       => 'Russian (Ukraine)',
        'yo_NG'       => 'Yoruba (Nigeria)',
        'dav_KE'      => 'Taita (Kenya)',
        'gv_GB'       => 'Manx (United Kingdom)',
        'pa_Arab'     => 'Punjabi (Arabic)',
        'es'          => 'Spanish',
        'teo_UG'      => 'Teso (Uganda)',
        'ps'          => 'Pashto',
        'es_PR'       => 'Spanish (Puerto Rico)',
        'fr_MF'       => 'French (Saint Martin)',
        'et'          => 'Estonian',
        'pt'          => 'Portuguese',
        'eu'          => 'Basque',
        'ka'          => 'Georgian',
        'rwk_TZ'      => 'Rwa (Tanzania)',
        'nb_NO'       => 'Norwegian Bokmål (Norway)',
        'fr_CG'       => 'French (Congo - Brazzaville)',
        'cgg'         => 'Chiga',
        'zh_Hant_TW'  => 'Chinese (Traditional Han, Taiwan)',
        'sr_Cyrl_ME'  => 'Serbian (Cyrillic, Montenegro)',
        'lag'         => 'Langi',
        'ses_ML'      => 'Koyraboro Senni (Mali)',
        'en_ZW'       => 'English (Zimbabwe)',
        'ak_GH'       => 'Akan (Ghana)',
        'vi_VN'       => 'Vietnamese (Vietnam)',
        'sv_FI'       => 'Swedish (Finland)',
        'to_TO'       => 'Tonga (Tonga)',
        'fr_MG'       => 'French (Madagascar)',
        'fr_GA'       => 'French (Gabon)',
        'fr_CH'       => 'French (Switzerland)',
        'de_CH'       => 'German (Switzerland)',
        'es_US'       => 'Spanish (United States)',
        'ki'          => 'Kikuyu',
        'my_MM'       => 'Burmese (Myanmar [Burma])',
        'vi'          => 'Vietnamese',
        'ar_QA'       => 'Arabic (Qatar)',
        'ga_IE'       => 'Irish (Ireland)',
        'rwk'         => 'Rwa',
        'bez'         => 'Bena',
        'ee_GH'       => 'Ewe (Ghana)',
        'kk'          => 'Kazakh',
        'as_IN'       => 'Assamese (India)',
        'ca_ES'       => 'Catalan (Spain)',
        'kl'          => 'Kalaallisut',
        'fr_SN'       => 'French (Senegal)',
        'ne_IN'       => 'Nepali (India)',
        'km'          => 'Khmer',
        'ms_BN'       => 'Malay (Brunei)',
        'ar_LB'       => 'Arabic (Lebanon)',
        'ta_LK'       => 'Tamil (Sri Lanka)',
        'kn'          => 'Kannada',
        'ur_IN'       => 'Urdu (India)',
        'fr_CI'       => 'French (Côte d’Ivoire)',
        'ko'          => 'Korean',
        'ha_Latn_NG'  => 'Hausa (Latin, Nigeria)',
        'sg_CF'       => 'Sango (Central African Republic)',
        'om_ET'       => 'Oromo (Ethiopia)',
        'zh_Hant_MO'  => 'Chinese (Traditional Han, Macau SAR China)',
        'uk_UA'       => 'Ukrainian (Ukraine)',
        'fa'          => 'Persian',
        'mt_MT'       => 'Maltese (Malta)',
        'ki_KE'       => 'Kikuyu (Kenya)',
        'luy_KE'      => 'Luyia (Kenya)',
        'kw'          => 'Cornish',
        'pa_Guru_IN'  => 'Punjabi (Gurmukhi, India)',
        'en_IN'       => 'English (India)',
        'kab'         => 'Kabyle',
        'ar_IQ'       => 'Arabic (Iraq)',
        'ff'          => 'Fulah',
        'en_TT'       => 'English (Trinidad and Tobago)',
        'bez_TZ'      => 'Bena (Tanzania)',
        'es_NI'       => 'Spanish (Nicaragua)',
        'uz_Arab'     => 'Uzbek (Arabic)',
        'ne_NP'       => 'Nepali (Nepal)',
        'fi'          => 'Finnish',
        'khq'         => 'Koyra Chiini',
        'gsw'         => 'Swiss German',
        'zh_Hans_MO'  => 'Chinese (Simplified Han, Macau SAR China)',
        'en_MH'       => 'English (Marshall Islands)',
        'hu_HU'       => 'Hungarian (Hungary)',
        'en_GB'       => 'English (United Kingdom)',
        'fr_BE'       => 'French (Belgium)',
        'de_BE'       => 'German (Belgium)',
        'saq'         => 'Samburu',
        'be_BY'       => 'Belarusian (Belarus)',
        'sl_SI'       => 'Slovenian (Slovenia)',
        'sr_Latn_RS'  => 'Serbian (Latin, Serbia)',
        'fo'          => 'Faroese',
        'fr'          => 'French',
        'xog'         => 'Soga',
        'fr_BF'       => 'French (Burkina Faso)',
        'tzm'         => 'Central Morocco Tamazight',
        'sk_SK'       => 'Slovak (Slovakia)',
        'fr_ML'       => 'French (Mali)',
        'he_IL'       => 'Hebrew (Israel)',
        'ha_Latn_NE'  => 'Hausa (Latin, Niger)',
        'ru_RU'       => 'Russian (Russia)',
        'fr_CM'       => 'French (Cameroon)',
        'teo_KE'      => 'Teso (Kenya)',
        'seh_MZ'      => 'Sena (Mozambique)',
        'kl_GL'       => 'Kalaallisut (Greenland)',
        'fi_FI'       => 'Finnish (Finland)',
        'kam'         => 'Kamba',
        'es_ES'       => 'Spanish (Spain)',
        'af'          => 'Afrikaans',
        'asa_TZ'      => 'Asu (Tanzania)',
        'cs_CZ'       => 'Czech (Czech Republic)',
        'tr_TR'       => 'Turkish (Turkey)',
        'es_PY'       => 'Spanish (Paraguay)',
        'tzm_Latn_MA' => 'Central Morocco Tamazight (Latin, Morocco)',
        'lg'          => 'Ganda',
        'ebu'         => 'Embu',
        'en_HK'       => 'English (Hong Kong SAR China)',
        'nl_NL'       => 'Dutch (Netherlands)',
        'en_BE'       => 'English (Belgium)',
        'ms_MY'       => 'Malay (Malaysia)',
        'es_UY'       => 'Spanish (Uruguay)',
        'ar_BH'       => 'Arabic (Bahrain)',
        'kw_GB'       => 'Cornish (United Kingdom)',
        'ak'          => 'Akan',
        'chr'         => 'Cherokee',
        'dav'         => 'Taita',
        'lag_TZ'      => 'Langi (Tanzania)',
        'am'          => 'Amharic',
        'so_DJ'       => 'Somali (Djibouti)',
        'shi_Tfng_MA' => 'Tachelhit (Tifinagh, Morocco)',
        'sr_Latn_ME'  => 'Serbian (Latin, Montenegro)',
        'sn_ZW'       => 'Shona (Zimbabwe)',
        'or_IN'       => 'Oriya (India)',
        'ar'          => 'Arabic',
        'as'          => 'Assamese',
        'fr_BI'       => 'French (Burundi)',
        'jmc_TZ'      => 'Machame (Tanzania)',
        'chr_US'      => 'Cherokee (United States)',
        'eu_ES'       => 'Basque (Spain)',
        'saq_KE'      => 'Samburu (Kenya)',
        'vun'         => 'Vunjo',
        'lt'          => 'Lithuanian',
        'naq_NA'      => 'Nama (Namibia)',
        'ga'          => 'Irish',
        'af_NA'       => 'Afrikaans (Namibia)',
        'kea_CV'      => 'Kabuverdianu (Cape Verde)',
        'es_DO'       => 'Spanish (Dominican Republic)',
        'lv'          => 'Latvian',
        'kok_IN'      => 'Konkani (India)',
        'de_LI'       => 'German (Liechtenstein)',
        'fr_BJ'       => 'French (Benin)',
        'az'          => 'Azerbaijani',
        'guz_KE'      => 'Gusii (Kenya)',
        'rw_RW'       => 'Kinyarwanda (Rwanda)',
        'mg_MG'       => 'Malagasy (Madagascar)',
        'km_KH'       => 'Khmer (Cambodia)',
        'gl'          => 'Galician',
        'shi_Tfng'    => 'Tachelhit (Tifinagh)',
        'ar_AE'       => 'Arabic (United Arab Emirates)',
        'fr_MQ'       => 'French (Martinique)',
        'rm'          => 'Romansh',
        'sv_SE'       => 'Swedish (Sweden)',
        'az_Cyrl'     => 'Azerbaijani (Cyrillic)',
        'ro'          => 'Romanian',
        'so_ET'       => 'Somali (Ethiopia)',
        'en_ZA'       => 'English (South Africa)',
        'ii_CN'       => 'Sichuan Yi (China)',
        'fr_BL'       => 'French (Saint Barthélemy)',
        'hi_IN'       => 'Hindi (India)',
        'gu'          => 'Gujarati',
        'mer_KE'      => 'Meru (Kenya)',
        'nn_NO'       => 'Norwegian Nynorsk (Norway)',
        'gv'          => 'Manx',
        'ru'          => 'Russian',
        'ar_DZ'       => 'Arabic (Algeria)',
        'ar_SY'       => 'Arabic (Syria)',
        'en_MP'       => 'English (Northern Mariana Islands)',
        'nl_BE'       => 'Dutch (Belgium)',
        'rw'          => 'Kinyarwanda',
        'be'          => 'Belarusian',
        'en_VI'       => 'English (U.S. Virgin Islands)',
        'es_CL'       => 'Spanish (Chile)',
        'bg'          => 'Bulgarian',
        'mg'          => 'Malagasy',
        'hy_AM'       => 'Armenian (Armenia)',
        'zu_ZA'       => 'Zulu (South Africa)',
        'guz'         => 'Gusii',
        'mk'          => 'Macedonian',
        'es_VE'       => 'Spanish (Venezuela)',
        'ml'          => 'Malayalam',
        'bm'          => 'Bambara',
        'khq_ML'      => 'Koyra Chiini (Mali)',
        'bn'          => 'Bengali',
        'ps_AF'       => 'Pashto (Afghanistan)',
        'so_SO'       => 'Somali (Somalia)',
        'sr_Cyrl'     => 'Serbian (Cyrillic)',
        'pl_PL'       => 'Polish (Poland)',
        'fr_GN'       => 'French (Guinea)',
        'bo'          => 'Tibetan',
        'om_KE'       => 'Oromo (Kenya)',
    ];
    public $default_prompts = [];
    public $ai_model_option = [];

    public function __construct()
    {
        ksort(Wp_AHC_Settings::$languages);
        $prompts = new AICH_Prompt_Manager_Controller();
        $this->default_prompts = $prompts->default_prompts();

        $models = get_option('wp_ai_pilot_models');

        $models_arr = [];
        if ($models) {
            for ($i = 0; $i < count($models); $i++) {
                if (isset($models[$i])) {
                    $models_arr[$models[$i]] = $models[$i];
                }
            }
            $this->ai_model_option = $models_arr;
            $this->ai_model_option['gpt-4o'] = 'gpt-4o';
            $this->ai_model_option['gpt-4o-mini'] = 'gpt-4o-mini';
        }

        // Insert premium prompts
        apply_filters('wp_ai_co_pilot_add_premium_prompts', []);
    }

    /**
     * Generate settings with Codestar framework.
     */
    public function generate_settings()
    {
        // Create options
        CSF::createOptions(Wp_AHC_Settings::$prefix, array(
            'framework_title' => WP_AHC_SHORT_NAME . ' ' . __('Settings', 'wp-ai-co-pilot'),
            'footer_text'     => sprintf(
                __('Visit our plugin usage <a href="%s" target="_blank">documentation</a>', 'wp-ai-co-pilot'),
                esc_url('https://wpmessiah.com/docs/wp-ai-co-pilot/')
            ),
            'footer_credit'   => sprintf(
                __('A proud creation of <a href="%s" target="_blank">BOOM DEVS</a>', 'wp-ai-co-pilot'),
                esc_url('https://wpmessiah.com/')
            ),
            'menu_title' => __('WP AI Co-Pilot', 'wp-ai-co-pilot'),
            'menu_slug'  => 'aich-settings',
        ));

        $pro_global_settings = apply_filters('wp_ai_co_pilot_premium_settings', []);
        if (!$pro_global_settings) {
            $pro_global_settings = array(
                array(
                    'id'    => 'ai_global_number_of_choices',
                    'type'  => 'notice',
                    'style' => 'warning',
                    'title' => __('Number of choices', 'wp-ai-co-pilot'),
                    'content' => 'Available On <a href="https://wpaicopilot.com/" target="_blank"><b>Pro</b></a> Version!',
                ),
                array(
                    'id'    => 'wp_ai_image_size',
                    'type'  => 'notice',
                    'style' => 'warning',
                    'title' => __('Image size', 'wp-ai-co-pilot'),
                    'content' => 'Available On <a href="https://wpaicopilot.com/" target="_blank"><b>Pro</b></a> Version!',
                ),
                array(
                    'id'    => 'wp_ai_image_experiments',
                    'type'  => 'notice',
                    'style' => 'warning',
                    'title' => __('Image Quality', 'wp-ai-co-pilot'),
                    'content' => 'Available On <a href="https://wpaicopilot.com/" target="_blank"><b>Pro</b></a> Version!',
                ),
            );
        }

        $pixabay_settings = apply_filters('wp_ai_co_pilot_pixabay_settings', []);

        if (!$pixabay_settings) {
            $pixabay_settings = array(
                array(
                    'id'    => 'pixabay_api_key',
                    'type'  => 'notice',
                    'style' => 'warning',
                    'title' => __('Pixabay API Key', 'wp-ai-co-pilot'),
                    'content' => 'Available On <a href="https://wpaicopilot.com/" target="_blank"><b>Pro</b></a> Version!',
                )
            );
        }

        $stability_ai_settings = apply_filters('wp_ai_co_pilot_stability_ai_settings', []);

        if (!$stability_ai_settings) {
            $stability_ai_settings = array(
                array(
                    'id'    => 'stability_ai_api_key',
                    'type'  => 'notice',
                    'style' => 'warning',
                    'title' => __('Stability AI API Key', 'wp-ai-co-pilot'),
                    'content' => 'Available On <a href="https://wpaicopilot.com/" target="_blank"><b>Pro</b></a> Version!',
                )
            );
        }

        $pexels_settings = apply_filters('wp_ai_co_pilot_pexels_ai_settings', []);

        if (!$pexels_settings) {
            $pexels_settings = array(
                array(
                    'id'    => 'pexels_api_key',
                    'type'  => 'notice',
                    'style' => 'warning',
                    'title' => __('Pexels API Key', 'wp-ai-co-pilot'),
                    'content' => 'Available On <a href="https://wpaicopilot.com/" target="_blank"><b>Pro</b></a> Version!',
                )
            );
        }

        $unsplash_settings = apply_filters('wp_ai_co_pilot_unsplash_ai_settings', []);

        if (!$unsplash_settings) {
            $unsplash_settings = array(
                array(
                    'id'    => 'unsplash_api_key',
                    'type'  => 'notice',
                    'style' => 'warning',
                    'title' => __('Unsplash Access Key', 'wp-ai-co-pilot'),
                    'content' => 'Available On <a href="https://wpaicopilot.com/" target="_blank"><b>Pro</b></a> Version!',
                )
            );
        }

        $bulk_post_generator_settings = apply_filters('wp_ai_co_pilot_bulk_post_generator', []);

        if (!$bulk_post_generator_settings) {
            $bulk_post_generator_settings = array(
                array(
                    'id'    => 'bulk_post_generator_switcher',
                    'type'  => 'notice',
                    'style' => 'warning',
                    'title' => __('Balk Post Generator', 'wp-ai-co-pilot'),
                    'content' => 'Available On <a href="https://wpaicopilot.com/" target="_blank"><b>Pro</b></a> Version!',
                )
            );
        }

        // Setting Section
        CSF::createSection(Wp_AHC_Settings::$prefix, array(
            'title'  => __('Settings', 'wp-ai-co-pilot'),
            'fields' => array(
                array(
                    'id'    => 'api_key',
                    'type'  => 'text',
                    'title' => __('OpenAI API Key', 'wp-ai-co-pilot'),
                    'validate' => 'validate_api_key',
                    'desc' => "Get OpenAI key, please visit <a href='https://platform.openai.com/account/api-keys' target='_blank'>https://platform.openai.com/account/api-keys</a>",
                ),
                array(
                    'id' => 'select_post_type',
                    'type' => 'checkbox',
                    'title' => __('Select post types', 'wp-ai-co-pilot'),
                    'subtitle' => __('Select the post types which will have the WP AI CoPilot feature.', 'bwp-ai-co-pilot'),
                    'options' => 'post_types',
                    'class' => 'select_post',
                    'query_args' => array(
                        'orderby' => 'post_title',
                        'order' => 'ASC',
                    ),
                    'default' => array('post', 'page'),
                ),
                ...$pixabay_settings,
                ...$stability_ai_settings,
                ...$pexels_settings,
                ...$unsplash_settings,
                ...$bulk_post_generator_settings,

                array(
                    'id'          => 'ai_language',
                    'type'        => 'select',
                    'title'       => __('Language for text generation', 'wp-ai-co-pilot'),
                    'placeholder' => __('Select An Language', 'wp-ai-co-pilot'),
                    'chosen'      => true,
                    'ajax'        => true,
                    'multiple'    => true,
                    'options'     => Wp_AHC_Settings::$languages,
                    'default'     => array('en'),
                    'desc'    => 'The language of the text you want to generate.<br/>For consistent autocomplete results, make sure that the text you write in your post is written in the same language you picked here.'
                ),
                array(
                    'id'          => 'ai_model',
                    'type'        => 'select',
                    'title'       => __('OpenAI Preferred Model', 'wp-ai-co-pilot'),
                    'placeholder' => __('Select An Model', 'wp-ai-co-pilot'),
                    'chosen'      => true,
                    'ajax'        => true,
                    'options'     => $this->ai_model_option,
                    'default'     => 'gpt-3.5-turbo',
                    'desc'    => 'Some models are more capable than others. For example, the davinci model is more capable than the ada model, which is more capable than the babbage model, and so on. The davinci model is the most capable model, but it is also the most expensive model. The ada model is the least capable model, but it is also the least expensive model.'
                ),
                array(
                    'id'    => 'ai_temprature',
                    'type'  => 'slider',
                    'title' => __('Temperature', 'wp-ai-co-pilot'),
                    'min'     => 0,
                    'max'     => 1,
                    'step'    => 0.1,
                    'default' => 0.8,
                    'desc' => __('Control randomness: Lowering results in less random completions. As the temperature approaches zero, the model will become deterministic and repetitive. If it approaches one, the model will become more randomness and creative.', 'wp-ai-co-pilot')
                ),
                array(
                    'id'          => 'ai_token_length',
                    'type'        => 'slider',
                    'title'       => __('Max Tokens', 'wp-ai-co-pilot'),
                    'min'     => 0,
                    'max'     => 4000,
                    'step'    => 1,
                    'default' => 2000,
                    'desc'    => __("Set the maximum number of tokens to generate in a single request.", 'wp-ai-co-pilot')
                ),
                array(
                    'id'          => 'ai_top_prediction',
                    'type'        => 'slider',
                    'title'       => __('Top Prediction (Top-P)', 'wp-ai-co-pilot'),
                    'min'     => 0,
                    'max'     => 1,
                    'step'    => 0.1,
                    'default' => 1,
                    'desc'    => __("Adjust the Top-P (Top Prediction) parameter to control the diversity of the generated text.", 'wp-ai-co-pilot')
                ),

                array(
                    'id'          => 'ai_frequency_panalty',
                    'type'        => 'slider',
                    'title'       => __('Frequency Penalty', 'wp-ai-co-pilot'),
                    'min'     => 0,
                    'max'     => 2,
                    'step'    => 0.1,
                    'default' => 0,
                    'desc'    => __("Adjust the frequency penalty to control the frequency of words in the generated text.", 'wp-ai-co-pilot')
                ),
                array(
                    'id'          => 'ai_presence_panalty',
                    'type'        => 'slider',
                    'title'       => __('Presence Penalty', 'wp-ai-co-pilot'),
                    'min'     => 0,
                    'max'     => 2,
                    'step'    => 0.1,
                    'default' => 0,
                    'desc'    => __("Adjust the presence penalty to control the presence of words in the generated text.", 'wp-ai-co-pilot')
                ),
                ...$pro_global_settings
            )
        ));

        $pro_prompt_settings = apply_filters('wp_ai_co_pilot_premium_prompt_settings', []);

        if (!$pro_prompt_settings) {
            $pro_prompt_settings = array(
                array(
                    'type'     => 'callback',
                    'function' => function () {
                        echo '<img style="width: 100%" src=' . plugin_dir_url(__DIR__) . 'admin/images/prompts_settings.png' . ' />';
                    },
                )
            );
        }


        CSF::createSection(Wp_AHC_Settings::$prefix, array(
            'title'  => __('AI Content Generator', 'wp-ai-co-pilot'),
            'fields' => array(
                array(
                    'id'        => 'prompt_group',
                    'type'      => 'group',
                    'class'    => 'prompt_group',
                    'before'     => 'Prompts',
                    'fields'    => array(
                        array(
                            'id'      => 'group_language',
                            'type'    => 'text',
                            'class'   => 'group_label',
                            'default' => __('Please select prompt language', 'wp-ai-co-pilot')
                        ),

                        array(
                            'id'          => 'prompt_language',
                            'type'        => 'select',
                            'title'       => __('Prompt Language', 'wp-ai-co-pilot'),
                            'chosen'      => true,
                            'ajax'        => true,
                            'class'       => 'aich_select_language',
                            'placeholder' => __('Select An Language', 'wp-ai-co-pilot'),
                            'options'     => Wp_AHC_Settings::$languages,
                            'default'     => Wp_AHC_Settings::$prefix,
                            'desc'    => 'The language of the text you want to generate.<br/>For consistent autocomplete results, make sure that the text you write in your post is written in the same language you picked here.'
                        ),
                        array(
                            'id'     => 'new_prompt',
                            'type'   => 'repeater',
                            'class'  => 'aich_prompt_repeater',
                            'fields' => array(
                                array(
                                    'type'    => 'heading',
                                    'content' => '',
                                ),
                                array(
                                    'id'    => 'prompt_title',
                                    'type'  => 'text',
                                    'title' => __('Prompt Title', 'wp-ai-co-pilot')
                                ),

                                array(
                                    'id'    => 'prompt_content',
                                    'type'  => 'textarea',
                                    'title' => __('Prompt', 'wp-ai-co-pilot')
                                ),
                                array(
                                    'id'    => 'prompt_desc',
                                    'type'  => 'textarea',
                                    'title' => __('Description', 'wp-ai-co-pilot')
                                ),
                                array(
                                    'id'      => 'word_count',
                                    'type'    => 'number',
                                    'title'   => __('Number of words', 'wp-ai-co-pilot'),
                                    'default' => 400,
                                    'desc'    => 'Choose this option if you want to generate a fixed number of words, regardless of how long the selected text is. This is helpful for certain types of prompts, like generating a paragraph on a certain topic for example.',
                                ),
                                ...$pro_prompt_settings
                            ),
                        ),
                    ),
                    'default'   => $this->default_prompts
                ),
            )
        ));

        // Free Vs Pro
        $pro_global_settings = apply_filters('wp_ai_co_pilot_premium_settings', []);

        if (!$pro_global_settings) {
            CSF::createSection(Wp_AHC_Settings::$prefix, array(
                'title'  => __('Free Vs Pro', 'wp-ai-co-pilot'),
                'fields' => array(
                    array(
                        'type'    => 'subheading',
                        'content' => $this->Free_VS_Pro(),
                    ),
                ),
            ));
        }
    }

    public static function get_settings()
    {
        return get_option(Wp_AHC_Settings::$prefix);
    }


    protected function Free_VS_Pro()
    {
        ob_start();
?>
        <div class="wp_ai_co_pilot_main_wrapper">
            <div class="wp_ai_co_pilot_header_wrapper">
                <div class="container">
                    <div class="title">
                        <h1>Jasper-like Content Creation using the <br> Best AI plugin for WordPress</h1>
                    </div>
                    <div class="text">
                        <p>Effortless, Engaging Content Creation Awaits You.</p>
                    </div>
                    <div class="header_btn">
                        <div class="left_btn">
                            <a class="button button-primary" target="_blank" href="https://wpaicopilot.com/">View all pro features</a>
                        </div>
                        <!-- <div class="right_btn">
                            <a class="button button-secondary" target="_blank" href="https://wpmessiah.com/products/wordpress-table-of-contents/">Get Pro Now</a>
                        </div> -->
                    </div>
                </div>
            </div>
            <div class="wp_ai_co_pilot_money_back_guarantee_wrapper">
                <div class="container">
                    <div class="money_back_guarantee_logo">
                        <img src="<?php echo self::$plugin_file_url . 'admin/images/money-back-logo-1.png' ?>" alt="money-back-logo">
                    </div>
                    <div class="money_back_guarantee_text">
                        <h3>14 Days Money Back Guarantee!</h3>
                        <p>Your satisfaction is guaranteed under our 100% No-Risk Double Guarantee. We will<br> happily <a target="_blank" href="https://wpaicopilot.com/refund-policy/">refund</a> 100% of your money if you don’t think our plugin works well within 14 days.</p>
                    </div>
                    <div class="money_back_guarantee_btn">
                        <a class="button button-primary" target="_blank" href="https://wpaicopilot.com/#pricing">Buy Now</a>
                    </div>
                </div>
            </div>
            <div class="wp_ai_co_pilot_pricing_wrapper">
                <div class="container">
                    <div class="wp_ai_co_pilot_pricing_content">
                        <div class="wp_ai_co_pilot_pricing_content_header">
                            <span>Get a quote</span>
                            <h2>Compare Plan</h2>
                            <p>It’s all here. You can compare the features before moving on to the pro version by checking out the comparison chart.</p>
                        </div>
                        <div class="wp_ai_co_pilot_pricing_content_table">
                            <table class="pricing-table">
                                <thead>
                                    <tr>
                                        <th>Feature</th>
                                        <th>Free</th>
                                        <th>Premium</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Jasper AI-like templates.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>Premium Templates.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>Ready-to-use high-quality prompts.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>Depending on needs, multi-output.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>Previous generated content view option.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>Easily navigable and super fast.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>Dedicated backend Into Post Editor.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>Unlimited Customization Option.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>Editable voice tune on every output.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>Audience options for every option.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>Premium support.</td>
                                        <td class="cross">X</td>
                                        <td><span class="tick">✓</span></td>
                                    </tr>
                                    <tr>
                                        <td>& more...</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wp_ai_co_pilot_money_back_guarantee_wrapper">
                <div class="container">
                    <div class="money_back_guarantee_logo">
                        <img src="<?php echo self::$plugin_file_url . 'admin/images/money-back-logo-2.png' ?>" alt="money-back-logo">
                    </div>
                    <div class="money_back_guarantee_text">
                        <h3>Buy now to avoid disappointment.</h3>
                        <p>Create content like a pro with the best AI content writer plugin for WordPress. Create, edit,<br> and publish in one place, and enjoy effortless control at your fingertips.</p>
                    </div>
                    <div class="money_back_guarantee_btn">
                        <a class="button button-primary" target="_blank" href="https://wpaicopilot.com/#pricing">Get it Now</a>
                    </div>
                </div>
            </div>
            <div class="wp_ai_co_pilot_testimonial_wrapper">
                <div class="container">
                    <div class="wp_ai_co_pilot_testimonial_content">
                        <div class="wp_ai_co_pilot_testimonial_content_header">
                            <span>Testimonials</span>
                            <h2>What People Say</h2>
                            <p>We're dedicated to providing the best possible experience for our customers.<br> Here's what a few of them have to say about us</p>
                        </div>
                        <div class="testimonial-cards">
                            <div class="card">
                                <div class="logo">
                                    <img src="<?php echo self::$plugin_file_url . 'admin/images/McKeown.jpg' ?>" alt="Greg McKeown">
                                </div>
                                <div class="content">
                                    <p>"As a beta user of this plugin, I must say I'm thoroughly impressed. It has completely transformed the content creation process on my WP site. I highly recommend it."</p>
                                </div>
                                <div class="details">
                                    <div class="name">
                                        <p>Greg McKeown</p>
                                        <span> Blogger</span>
                                    </div>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="logo">
                                    <img src="<?php echo self::$plugin_file_url . 'admin/images/Rubin.jpg' ?>" alt="Gretchen Rubin">
                                </div>
                                <div class="content">
                                    <p>"With this plugin, I no longer need to juggle between different AI tools. This plugin has simplified everything, and I couldn't be happier."</p>
                                </div>
                                <div class="details">
                                    <div class="name">
                                        <p>Gretchen Rubin</p>
                                        <span> Blogger</span>
                                    </div>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="logo">
                                    <img src="<?php echo self::$plugin_file_url . 'admin/images/Bailey.jpg' ?>" alt="Matt Bailey">
                                </div>
                                <div class="content">
                                    <p>"This plugin is a game-changer for content creation on WordPress. No need for extra AI tools. Incredible results and seamless integration. Don't miss out."</p>
                                </div>
                                <div class="details">
                                    <div class="name">
                                        <p>Matt Bailey</p>
                                        <span>Digital Marketer</span>
                                    </div>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wp_ai_co_pilot_template_mode_wrapper">
                <div class="container">
                    <div class="template_mode_text">
                        <h3>Unlock the Power of Template Mode with WP AI Co-Pilot.</h3>
                        <p>Simplify Content Creation and Boost Efficiency on Your WordPress Site.</p>
                        <iframe width="768" height="520" src="https://www.youtube.com/embed/RSCM9f2tYiw?autoplay=1&mute=1&loop=1&controls=0&showinfo=0&playlist=RSCM9f2tYiw" frameborder="0" allow="autoplay" allowfullscreen></iframe>
                    </div>
                    <!-- <div class="template_mode_text">
                        <h3>The process of creating custom templates using the WP AI Copilot Pro.</h3>
                        <iframe width="768" height="520" src="https://www.youtube.com/embed/Uafkk0ldVnA"  frameborder="0" allowfullscreen></iframe>
                    </div>
                    <div class="template_mode_text">
                        <h3>See How to create a Blog post using WP AI Co-Pilot Pro.</h3>
                        <iframe width="768" height="520" src="https://www.youtube.com/embed/66ZaEIvtPw4"  frameborder="0" allowfullscreen></iframe>
                    </div> -->
                </div>
            </div>
            <div class="wp_ai_co_pilot_money_back_guarantee_wrapper">
                <div class="container">
                    <div class="money_back_guarantee_logo">
                        <img src="<?php echo self::$plugin_file_url . 'admin/images/money-back-logo-3.png' ?>" alt="money-back-logo">
                    </div>
                    <div class="money_back_guarantee_text">
                        <h3>Supercharge your content creation with WP AI Co-Pilot Pro.</h3>
                        <p>Take action now to unlock the extraordinary benefits of WP AI Co-Pilot Pro. Don't wait,<br> Your content creation journey will never be the same. Get started today.</p>
                    </div>
                    <div class="money_back_guarantee_btn">
                        <a class="button button-primary" target="_blank" href="https://wpaicopilot.com/#pricing">Upgrade Now</a>
                    </div>
                </div>
            </div>

        </div>
<?php
        return ob_get_clean();
    }
}
