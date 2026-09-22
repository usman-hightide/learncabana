(function (blocks, i18n, element, InspectorControls, ServerSideRender) {
    "use strict";

    const { registerBlockType } = wp.blocks;
    const { useBlockProps } = window.wp.blockEditor;
    const { __ } = wp.i18n;

    var el = element.createElement;

    blocks.registerBlockType('grassblade/leaderboard', {

        /* This configures how the content field will work, and sets up the necessary elements */
        edit: function (props) {
            const blockProps = useBlockProps({ key: `grassblade_leaderboard_${props.clientId}` });
            jQuery(document).ready(function () {
                if (typeof jQuery('.block-editor-block-inspector select#grassblade_xapi_content_leaderboard').select2 == "function") {
                    const select2Element = jQuery('.block-editor-block-inspector select#grassblade_xapi_content_leaderboard').select2();

                    select2Element.on('change', function (e) {
                        changeContent(e);
                    });
                }
            });

            var role = props.attributes.role;

            function changeContent(event) {

                props.setAttributes({ content_id: event.target.value })

            } // end of changeContent function

            function changeRole(event) {

                var roles = jQuery(event.target).parent().children("input:checkbox:checked").map(function () {
                    return this.value;
                }).get().join(",");
                props.setAttributes({ role: roles });

            } // end of changeRole function

            function changeScore(event) {

                props.setAttributes({ score: event.target.value })

            } // end of changeScore function

            function setLimit(event) {

                props.setAttributes({ limit: event.target.value })

            } // end of setLimit function

            var postSelections = [];

            postSelections.push(el("option", { key: "none", value: "", hidden: true }, __("Select Content", "grassblade")));
            jQuery.each(gb_block_data.post_content, function (key, value) {
                postSelections.push(el("option", { key: value.id, value: value.id }, value.post_title));
            });

            var roleSelections = [];

            roleSelections.push(el("input", { onChange: changeRole, type: "checkbox", value: "all" }), el("label", null, __("All Roles", "grassblade")), el("br"));
            jQuery.each(gb_block_data.roles, function (key, value) {
                var roles = props.attributes.role.split(",");
                var checked = roles.indexOf(key) < 0 ? "" : "checked";
                roleSelections.push(el("input", { onChange: changeRole, type: "checkbox", value: key, checked: checked }), el("label", null, value.name), el("br"));
            });

            var scoreSelections = [];
            scoreSelections.push(el("option", { key: "score", value: "score" }, __("Score", "grassblade")));
            scoreSelections.push(el("option", { key: "percentage", value: "percentage" }, __("Percentage", "grassblade")));

            const controls = [
                el(
                    InspectorControls,
                    { key: "grassblade/leaderboard/ic" },
                    el(
                        "div",
                        { style: { padding: "15px" } },
                        el(
                            "hr",
                            null
                        ),
                        el("span", { style: { fontWeight: 600, width: '100%' } }, __("Content", "grassblade") + ":"),
                        el("select", { value: props.attributes.content_id, onChange: changeContent, style: { width: '100%' }, id: 'grassblade_xapi_content_leaderboard' }, postSelections),
                        el("br"),
                        el("br"),
                        el("span", { style: { fontWeight: 600, width: '100%' } }, __("Role", "grassblade") + ":"),
                        el("br"),
                        el("span", { style: { width: '100%' } }, "(" + __("Who can see the leaderboard?", "grassblade") + ")"),
                        el("br"),
                        el("div", null, roleSelections),
                        el("br"),
                        el("span", { style: { fontWeight: 600, width: '100%' } }, __("Score Type", "grassblade") + ":"),
                        el("select", { value: props.attributes.score, onChange: changeScore, style: { width: '100%' } }, scoreSelections),
                        el("br"),
                        el("br"),
                        el("span", { style: { fontWeight: 600, width: '100%' } }, __("Limit", "grassblade") + ":"),
                        el("input", { onChange: setLimit, style: { width: '100%' }, type: "text", value: props.attributes.limit }),
                    ),
                ),
            ];

            return [
                controls,
                el(
                    "div", // Wrap the block in a div with blockProps
                    { ...blockProps }, // Spread blockProps here
                    el(ServerSideRender, {
                        block: "grassblade/leaderboard",
                        key: "grassblade/leaderboard",
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