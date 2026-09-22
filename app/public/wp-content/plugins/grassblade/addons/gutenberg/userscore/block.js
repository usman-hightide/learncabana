(function (blocks, i18n, element, InspectorControls, ServerSideRender) {
    "use strict";

    const { registerBlockType } = wp.blocks;
    const { useBlockProps } = window.wp.blockEditor;
    const { __ } = wp.i18n;

    var el = element.createElement;

    blocks.registerBlockType('grassblade/userscore', {

        /* This configures how the content field will work, and sets up the necessary elements */
        edit: function (props) {
            const blockProps = useBlockProps({ key: `grassblade_userscore_${props.clientId}` });
            jQuery(document).ready(function () {
                if (typeof jQuery('.block-editor-block-inspector select#grassblade_xapi_content_score').select2 == "function") {
                    const select2Element = jQuery('.block-editor-block-inspector select#grassblade_xapi_content_score').select2();

                    select2Element.on('change', function (e) {
                        changeContent(e);
                    });
                }
            });

            function changeContent(event) {

                props.setAttributes({ content_id: event.target.value })

            } // end of changeContent function

            function changeShow(event) {

                props.setAttributes({ show: event.target.value })

            } // end of changeShow function

            function changeAdd(event) {

                props.setAttributes({ add: event.target.value })

            } // end of changeAdd function

            function setLabel(event) {

                props.setAttributes({ label: event.target.value })

            } // end of setLabel function

            var postSelections = [];

            postSelections.push(el("option", { key: "all", value: "" }, __("All Contents", "grassblade")));
            jQuery.each(gb_block_data.post_content, function (key, value) {
                postSelections.push(el("option", { key: value.id, value: value.id }, value.post_title));
            });

            var scoreSelections = [];

            scoreSelections.push(el("option", { key: "total_score", value: "total_score" }, __("Total Score", "grassblade")));
            scoreSelections.push(el("option", { key: "average_percentage", value: "average_percentage" }, __("Average Percentage", "grassblade")));

            var addSelections = [];
            addSelections.push(el("option", { key: "none", value: "" }, __("No Selection", "grassblade")));
            addSelections.push(el("option", { key: "badgeos_points", value: "badgeos_points" }, __("Badgeos Points", "grassblade")));

            var shortcode = " [grassblade_user_score ";

            if (typeof props.attributes.content_id != "undefined" && props.attributes.content_id != "")
                shortcode += " content_id=" + props.attributes.content_id;

            if (typeof props.attributes.show != "undefined" && props.attributes.show != "")
                shortcode += " show='" + props.attributes.show + "'";
            else
                shortcode += " show='total_score'";

            if (typeof props.attributes.add != "undefined" && props.attributes.add != "")
                shortcode += " add='" + props.attributes.add + "'";

            shortcode += " ]";

            const controls = [
                el(
                    InspectorControls,
                    { key: "grassblade/userscore/ic" },
                    el(
                        "div",
                        { style: { padding: "15px" } },
                        el(
                            "hr",
                            null
                        ),
                        el("span", { style: { fontWeight: 600, width: '100%' } }, __("Label", "grassblade") + ":"),
                        el("input", { key: "label", onChange: setLabel, type: "text", style: { width: '100%' }, value: props.attributes.label }),
                        el("br"),
                        el("br"),
                        el("span", { style: { fontWeight: 600, width: '100%' } }, __("xAPI Content", "grassblade") + ":"),
                        el("select", { key: "xapi_contents", value: props.attributes.content_id, onChange: changeContent, style: { width: '100%' }, id: "grassblade_xapi_content_score" }, postSelections),
                        el("br"),
                        el("br"),
                        el("span", { style: { fontWeight: 600, width: '100%' } }, __("Score", "grassblade") + ":"),
                        el("select", { key: "show", value: props.attributes.show, onChange: changeShow, style: { width: '100%' } }, scoreSelections),
                        el("br"),
                        el("br"),
                        el("span", { style: { fontWeight: 600, width: '100%' } }, __("Add", "grassblade") + ":"),
                        el("select", { key: "add", value: props.attributes.add, onChange: changeAdd, style: { width: '100%' } }, addSelections),

                        el("br"),
                        el("br"),
                        el("label", null, el("b", null, __("Alternatively, you can use this shortcode", "grassblade") + ":")),
                        el("br"),
                        el("textarea", { key: "shortcode", rows: "2", cols: "30", readOnly: true, value: shortcode }),
                    ),
                ),
            ];
            return [
                controls,
                el(
                    "div", // Wrap the block in a div with blockProps
                    { ...blockProps }, // Spread blockProps here
                    el(ServerSideRender, {
                        block: "grassblade/userscore",
                        key: "grassblade/userscore",
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