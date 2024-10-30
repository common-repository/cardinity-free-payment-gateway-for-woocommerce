const blockData = window.wc.wcSettings.getSetting( 'cardinity_data', {} );

const label = window.wp.htmlEntities.decodeEntities( blockData.title ) || window.wp.i18n.__( 'Cardinity', 'wc-cardinity' );
const Content = () => {
    return window.wp.htmlEntities.decodeEntities( blockData.description || '' );
};


const Block_Gateway = {
    name: 'cardinity',
    label: label,
    content: wp.element.RawHTML({
        children: blockData.description
    }),
    edit: window.wp.element.createElement( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: blockData.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );
