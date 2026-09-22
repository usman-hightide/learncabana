tinyMCEPreInit.mceInit['elementorwpeditor'].plugins = tinyMCEPreInit.mceInit['elementorwpeditor'].plugins || "";
tinyMCEPreInit.mceInit['elementorwpeditor'].plugins += ",aich_classic_plugin";

tinyMCEPreInit.mceInit['elementorwpeditor'].toolbar1 = tinyMCEPreInit.mceInit['elementorwpeditor'].toolbar1 || "";
tinyMCEPreInit.mceInit['elementorwpeditor'].toolbar1 += ",aich_classic_plugin_text";

tinyMCEPreInit.qtInit['elementorwpeditor'].buttons = tinyMCEPreInit.qtInit['elementorwpeditor'].buttons || "";
tinyMCEPreInit.qtInit['elementorwpeditor'].buttons += ",aich_classic_plugin_text";

jQuery(document).ready(function () {
    tinymce.PluginManager.add('aich_classic_plugin', function (editor, url) {
        let menu = [];

        for (let i = 0; i < aich_ajax.prompts.length; i++) {
            menu.push(
                {
                    text: aich_ajax.prompts[i]['group_language'],
                    disabled: true,
                    classes: 'wp-ai-co-pilot',
                }
            );
            for (let j = 0; j < aich_ajax.prompts[i]['new_prompt'].length; j++) {
                menu.push({
                    text: aich_ajax.prompts[i]['new_prompt'][j].prompt_title,
                    classes: 'wp-ai-co-pilot',
                    image: aich_ajax.prompts[i]['new_prompt'][j].wp_ai_prompt_icon !== undefined ? aich_ajax.prompts[i]['new_prompt'][j].wp_ai_prompt_icon.url : aich_ajax.plugin_url + 'admin/images/logo.png',
                    onclick: async function (e) {
                        let selectedContent = editor.selection.getContent({format: 'text'});
                        let content = editor.getContent();
                        let withoutHtmlTags = new DOMParser().parseFromString(content, 'text/html').documentElement.textContent;
                        localStorage.setItem('selectedText', selectedContent);
                        localStorage.setItem('all_content', withoutHtmlTags);
                        if (!openai_is_configured()) {
                            globalMessage('Please configure open AI successfully.');
                            return;
                        }

                        if (openai_is_pro_configured()) {
                            await Swal.fire({
                                title: 'Confirmation',
                                showCancelButton: true,
                                text: 'Interested in Simplifying Content Creation with Ready-made Templates?',
                                showDenyButton: true,
                                confirmButtonText: 'Yes',
                                denyButtonText: `Stay here`,
                                cancelButtonText: `Cancel`,
                                customClass: {
                                    container: 'choose_template',
                                }
                            }).then( async (result) => {
                                if (result.isConfirmed) {
                                    let promptTitle = e.target.innerText;
                                    promptTitle = string_to_slug(promptTitle);
                                    promptTitle = string_to_slug(promptTitle);
                                    jQuery('.templates_wrapper').find(`.${promptTitle}`).trigger('click');
                                    jQuery('.app_wrapper').fadeIn('slow');
                                } else if (result.isDenied) {

                                    let selectedContent = editor.selection.getContent({format: 'text'});

                                    if (selectedContent === '') {
                                        globalMessage('Please select text from editor');
                                        return;
                                    }

                                    const loaderId = aich_add_container('below');
                                    editor.selection.collapse();
                                    let loader = tinymce.activeEditor.dom.select('#' + loaderId);

                                    let generatedContent = '';
                                    if(aich_ajax.prompts[i]['new_prompt'][j].ai_model === 'dalee' || aich_ajax.prompts[i]['new_prompt'][j].ai_model === 'pixabay' || aich_ajax.prompts[i]['new_prompt'][j].ai_model === 'stable-diffusion-v1-6' || aich_ajax.prompts[i]['new_prompt'][j].ai_model === 'stable-diffusion-xl-1024-v1-0' || aich_ajax.prompts[i]['new_prompt'][j].ai_model === 'pexels' || aich_ajax.prompts[i]['new_prompt'][j].ai_model === 'unsplash') {
                                        generatedContent = await send_request_to_generate_image(j, selectedContent, aich_ajax.prompts[i]['prompt_language'], aich_ajax.prompts[i]['new_prompt'][j]);
                                        generatedContent = `<img src="${generatedContent}" alt='generated-image' />`;
                                    } else {
                                        try {
                                            generatedContent = await send_request_to_generate_text(j, selectedContent, aich_ajax.prompts[i]['prompt_language'], aich_ajax.prompts[i]['new_prompt'][j]);
                                        } catch (error) {
                                            let errorObj = JSON.parse(error.message);
                                            globalMessage(errorObj.message);
                                            return;
                                        }
                                    }                                    tinymce.dom.DomQuery(loader).removeAttr('class');
                                    tinymce.dom.DomQuery(loader).removeAttr('id');
                                    tinymce.dom.DomQuery(loader).html(generatedContent);

                                    editor.undoManager.add();
                                } else if( result.isDismissed) {
                                    return true;
                                }
                            });
                        }else {
                            let selectedContent = editor.selection.getContent({format: 'text'});

                            if (selectedContent === '') {
                                globalMessage('Please select text from block');
                                return;
                            }

                            const loaderId = aich_add_container('below');
                            editor.selection.collapse();
                            let loader = tinymce.activeEditor.dom.select('#' + loaderId);

                            let generatedContent = '';
                            generatedContent = await send_request_to_generate_text(j, selectedContent, aich_ajax.prompts[i]['prompt_language'], aich_ajax.prompts[i]['new_prompt'][j]);

                            tinymce.dom.DomQuery(loader).removeAttr('class');
                            tinymce.dom.DomQuery(loader).removeAttr('id');
                            tinymce.dom.DomQuery(loader).html(generatedContent);

                            editor.undoManager.add();
                        };
                    }
                });
            }
        }
        // Generate image
        const send_request_to_generate_image = async function (requestType, text, selectedLanguage, prompt) {
            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            const post_id = urlParams.get('post');
            let promptobj = {
                'prompt_title': prompt.prompt_title,
                'form_data': [text],
                'prompt_content': prompt.prompt_content,
                'ai_model': prompt.ai_model,
                'prompt': prompt.prompt_content,
                'max_tokens': prompt.word_count,
                'post_id': post_id,
                'media_type': prompt.pixabay_media_type,
                'additional_data': {
                    'toneOfVoice': '',
                    'aiAudience': '',
                }
            };

            const response = await fetch('/wp-json/wp-ai-co-pilot-pro/generate/v1/generate-image', {
                method: 'POST',
                body: JSON.stringify(promptobj)
            });

            if (!response.ok) {
                globalMessage(response.text());
                return;
            }

            const data = await response.json();

            let parseData = JSON.parse(data.contents);

            return parseData[0].text;
        }

        editor.addButton('aich_classic_plugin_text',
          {
              title: 'WP AI Co Pilot',
              image: aich_ajax.plugin_url + 'admin/images/logo.png',
              tooltip: 'Generate content by WP AI Co-pilot',
              addClasses: 'wp-ai-co-pilot',
              type: 'menubutton',
              menu: menu
          }
        );

        // Insert content to elementor editor
        jQuery(document).on('click', '.generate-item__bottom__icons .tooltip-box .action-btn.insert_content', function () {
            let content = jQuery(this).data('content');
            let type = jQuery(this).data('type');

            if (type === 'image') {
                content = `<img src='${content}' alt='generated-content' />`;
            }

            const loaderId = aich_add_container('below');
            editor.selection.collapse();
            let loader = tinymce.activeEditor.dom.select('#' + loaderId);

            tinymce.dom.DomQuery(loader).removeAttr('class');
            tinymce.dom.DomQuery(loader).removeAttr('id');
            tinymce.dom.DomQuery(loader).html(content);

            editor.undoManager.add();

            jQuery('.single_template .sidebar_inner_wrap .back_btn_trigger').trigger('click');
            jQuery('.app_wrapper .close').trigger('click');
        });

        function openai_is_configured() {
            return aich_ajax.api_key_is_valid;
        }

        function openai_is_pro_configured() {
            return aich_ajax.pro_activated;
        }
        function string_to_slug (str) {
            str = str.replace(/^\s+|\s+$/g, ''); // trim
            str = str.toLowerCase();

            // remove accents, swap ñ for n, etc
            let from = "àáãäâèéëêìíïîòóöôùúüûñç·/_,:;";
            let to   = "aaaaaeeeeiiiioooouuuunc------";

            for (let i=0, l=from.length ; i<l ; i++) {
                str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
            }

            str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
                .replace(/\s+/g, '-') // collapse whitespace and replace by -
                .replace(/-+/g, '-'); // collapse dashes

            return str;
        }
        function aich_add_container(placement) {
            let dom = tinymce.activeEditor.dom;
            const loadingSpinnerId = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);

            let selectionRange = editor.selection.getRng();
            if (placement === 'below') {
                let selectedNode = editor.selection.getEnd();

                let spinnerHtml = createLoadingSpinner(
                    selectedNode,
                    placement,
                    loadingSpinnerId,
                )
                let spinnerDom = tinymce.dom.DomQuery(spinnerHtml)[0];

                let parentNode = selectionRange.endContainer.parentNode;
                if (parentNode.tagName.toLowerCase() === 'li') {
                    tinymce.dom.DomQuery(selectedNode).after(spinnerDom);
                } else if (selectedNode.textContent) {
                    selectionRange.collapse(false);
                    selectionRange.insertNode(spinnerDom);
                    editor.selection.collapse();
                } else {
                    tinymce.dom.DomQuery(selectedNode).after(spinnerDom);
                }

            } else { // above
                let selectedNode = editor.selection.getStart();
                let spinnerHtml = createLoadingSpinner(
                    selectedNode,
                    placement,
                    loadingSpinnerId,
                )
                let spinnerDom = tinymce.dom.DomQuery(spinnerHtml)[0];

                let parentNode = selectionRange.startContainer.parentNode;
                if (parentNode.tagName.toLowerCase() === 'li') {
                    tinymce.dom.DomQuery(selectedNode).before(spinnerDom);
                } else if (selectedNode.textContent) {
                    selectionRange.collapse(true);
                    selectionRange.insertNode(spinnerDom);
                    editor.selection.collapse();
                } else {
                    tinymce.dom.DomQuery(selectedNode).before(spinnerDom);
                }
            }

            editor.undoManager.add();

            return loadingSpinnerId;
        }

        function globalMessage(message) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: message,
            });
        }

        // Generate text function
        const send_request_to_generate_text = async function (requestType, text, selectedLanguage, prompt) {
            const response = await fetch('/wp-json/ai-content-helper/openai/v1/generated-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': aich_ajax.nonce,
                },
                body: JSON.stringify({
                    requestType,
                    'text': [text],
                    language: selectedLanguage,
                    promptItem: prompt
                })
            });

            if (!response.ok) {
                globalMessage('There is something wrong with the settings, please fix it.');
                return;
            }
            const data = await response.json();
            return data.text
        }

        const createLoadingSpinner = function (selectedNode, placement, loadingSpinnerId) {
            let spinnerHtml = '';
            if (selectedNode.tagName.toLowerCase() === 'li') {
                spinnerHtml = `<${selectedNode.tagName} id="${loadingSpinnerId}" class="aich-loading">&nbsp;</${selectedNode.tagName}>`;
            } else {
                spinnerHtml = `<p id="${loadingSpinnerId}" class="aich-loading">&nbsp;</p>`;
            }

            jQuery('.aich-loading').css("width", "45px")
            return spinnerHtml;
        }
    });
});

// Generate button

tinyMCEPreInit.mceInit['elementorwpeditor'].plugins += ",wp_ai_co_pilot_pro_classic";

tinyMCEPreInit.mceInit['elementorwpeditor'].toolbar1 = tinyMCEPreInit.mceInit['elementorwpeditor'].toolbar1 || "";
tinyMCEPreInit.mceInit['elementorwpeditor'].toolbar1 += ",wp_ai_co_pilot_pro_classic_text";

tinyMCEPreInit.qtInit['elementorwpeditor'].buttons = tinyMCEPreInit.qtInit['elementorwpeditor'].buttons || "";
tinyMCEPreInit.qtInit['elementorwpeditor'].buttons += ",wp_ai_co_pilot_pro_classic_text";

jQuery(document).ready(function () {
    function openai_is_pro_configured() {
        return aich_ajax.pro_activated;
    }
    tinymce.PluginManager.add('wp_ai_co_pilot_pro_classic', function (editor, url) {
        if(openai_is_pro_configured()) {
            editor.addButton('wp_ai_co_pilot_pro_classic_text',
              {
                  title: 'WP AI Co-Pilot Generate button',
                  image: aich_ajax.plugin_url + 'admin/images/logo.png',
                  tooltip: 'WP AI Co-Pilot Generate button',
                  class: 'wp-ai-co-pilot',
                  onclick: function() {
                      let selectedContent = editor.selection.getContent({format: 'text'});
                      let content = editor.getContent();
                      let withoutHtmlTags = new DOMParser().parseFromString(content, 'text/html').documentElement.textContent;
                      localStorage.setItem('selectedText', selectedContent);
                      localStorage.setItem('all_content', withoutHtmlTags);
                      jQuery('.app_wrapper').fadeIn();
                  }
              }
            );
        }
    });
});
