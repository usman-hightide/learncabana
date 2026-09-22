( function( blocks, i18n, element, ServerSideRender ) {
  var el = element.createElement;
  var __ = i18n.__;

  var blockStyle = {
    backgroundColor: '#900',
    color: '#fff',
    padding: '20px',
  };

  blocks.registerBlockType( 'grassblade/admin-reports', {
    edit: function(props) {

      const blockProps = wp.blockEditor.useBlockProps({ key: `grassblade_admin_reports_${props.clientId}` });

      return el(
          "div", // Wrap the block in a div with blockProps
          { ...blockProps }, // Spread blockProps here
          el(ServerSideRender, {
              block: "grassblade/admin-reports",
              key: "grassblade/admin-reports",
              attributes: {}
          })
      )
    },
    save: function(props) {
      return null;
    },
  } );
} )( window.wp.blocks, window.wp.i18n, window.wp.element, window.wp.serverSideRender );