// The coloured logo tile of each platform: [background, letters, text colour?]. Used by Integrations and by a connection's Settings page.
export const TILE = {
  shopify: ['#5e8e3e', 'S'], woocommerce: ['#7f54b3', 'Woo'], wordpress: ['#21759b', 'WP'], magento: ['#f26322', 'M'],
  hubspot: ['#ff7a59', 'HS'], mailchimp: ['#ffe01b', 'MC', '#241c15'], klaviyo: ['#232426', 'K'], brevo: ['#0b996e', 'B'], stripe: ['#635bff', 'St'],
  bigcommerce: ['#34313f', 'BC'], ecwid: ['#0f4c81', 'Ec'], ghost: ['#15171a', 'G'], squarespace: ['#111111', 'Sq'], webflow: ['#146ef5', 'Wf'], wix: ['#0c6efc', 'Wix'],
  pipedrive: ['#017737', 'Pd'], salesforce: ['#00a1e0', 'SF'], activecampaign: ['#356ae6', 'AC'], close: ['#4a4de6', 'Cl'],
  facebook: ['#1877f2', 'f'], instagram: ['#d62976', 'Ig'], linkedin: ['#0a66c2', 'in'], whatsapp: ['#25d366', 'Wa'], messenger: ['#0084ff', 'Ms'], x: ['#000000', 'X'],
  mailerlite: ['#09c269', 'ML'], kit: ['#fb6970', 'Kit'], square: ['#3e4348', 'Sq'], paddle: ['#2b3a67', 'Pa'], chargebee: ['#ff3300', 'Cb'], gocardless: ['#ffdb00', 'GC', '#1b1b1b'],
}
export const tileStyle = (key) => ({ background: TILE[key]?.[0] || '#546e7a', color: TILE[key]?.[2] || '#fff' })
export const tileText = (key) => TILE[key]?.[1] || '?'
