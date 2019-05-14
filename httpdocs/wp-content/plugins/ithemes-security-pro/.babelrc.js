module.exports = {
	presets: ["@wordpress/default"],
	plugins: [
		['@wordpress/babel-plugin-import-jsx-pragma', {
			scopeVariable: 'createElement',
			source       : '@wordpress/element',
			isDefault    : false,
		}],
		['@babel/plugin-transform-react-jsx', {
			pragma: 'createElement',
		}],
		'@babel/plugin-proposal-class-properties',
		'@babel/plugin-syntax-dynamic-import',
	],
};
