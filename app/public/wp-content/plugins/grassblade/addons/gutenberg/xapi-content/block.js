(function (blocks, i18n, element, InspectorControls, ServerSideRender) {
    "use strict";

    const { registerBlockType } = wp.blocks;
    const { useBlockProps } = window.wp.blockEditor;

    var el = element.createElement;
    const { __ } = wp.i18n;

    blocks.registerBlockType('grassblade/xapi-content', {

        /* This configures how the content field will work, and sets up the necessary elements */
        edit: function (props) {

            const uniqueId = `grassblade_xapi_content_${props.clientId}`;
            const blockProps = useBlockProps({ key: uniqueId });

            jQuery(document).ready(function () {
                if (typeof jQuery(`.block-editor-block-inspector select#${uniqueId}`).select2 == "function") {
                    const select2Element = jQuery(`.block-editor-block-inspector select#${uniqueId}`).select2();

                    select2Element.on('change', function (e) {
                        changeContent(e);
                    });
                }
            });

            function changeContent(event) {
                props.setAttributes({ content_id: event.target.value })

                var completion = jQuery(`#${uniqueId}`).find(':selected').attr('data-completion-tracking');
                if (completion == 'true') {
                    props.setAttributes({ check_completion: __("Completion Tracking Enabled.", "grassblade") })
                } else {
                    props.setAttributes({ check_completion: __("Completion Tracking Disabled.", "grassblade") })
                }
            } // end of changeContent function r

            var postSelections = [];

            postSelections.push(el("option", { key: "none", value: "", hidden: true }, __("Select Content", "grassblade")));
            jQuery.each(gb_block_data.post_content, function (key, value) {
                postSelections.push(el("option", { key: value.id, value: value.id, "data-completion-tracking": value.completion_tracking }, value.post_title));
            });

            const controls = [
                el(
                    InspectorControls,
                    { key: "grassblade/xapi-content/ic" },
                    el(
                        "div",
                        { style: { padding: "15px" } },
                        el(
                            "hr",
                            null
                        ),
                        el("span", { style: { fontWeight: 600, width: '100%' } }, __("Add to Page", "grassblade") + ":  ",
                            el("a", { href: gb_block_data.admin_url + 'post-new.php?post_type=gb_xapi_content' }, __("Add New", "grassblade")),),
                        el("br"),
                        el("br"),
                        el("select", { key: uniqueId, value: props.attributes.content_id, onChange: changeContent, style: { width: '100%' }, id: uniqueId, className: 'show_xapi_content_select2' }, postSelections),
                        el("a", { href: gb_block_data.admin_url + 'post.php?action=edit&message=1&post=' + props.attributes.content_id }, (props.attributes.content_id) ? __("Edit", "grassblade") : props.attributes.content_id),
                        el("br"),
                        el("br"),
                        el("a", { href: gb_block_data.admin_url + 'post.php?action=edit&message=1&post=' + props.attributes.content_id }, props.attributes.check_completion),
                        el("div", { id: "gb_meta_box_extra_message", style: { display: ((gb_block_data.extra_message.length == 0 || typeof props.attributes.check_completion == "string" && props.attributes.check_completion.search("Disabled") > 0) ? "none" : "block") } }, gb_block_data.extra_message),
                    ),
                ),
            ];
            return [
                controls,
                el(
                    "div", // Wrap the block in a div with blockProps
                    { ...blockProps }, // Spread blockProps here
                    el(ServerSideRender, {
                        block: "grassblade/xapi-content",
                        key: "grassblade/xapi-content",
                        attributes: props.attributes
                    })
                )
            ];

        },
        save: function (props) {
            return null;
        }

    });

})(window.wp.blocks, window.wp.i18n, window.wp.element, wp.blockEditor.InspectorControls, window.wp.serverSideRender);