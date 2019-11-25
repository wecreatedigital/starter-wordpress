/**
 * LearnDash Block ld-usermeta
 *
 * @since 2.5.9
 * @package LearnDash
 */

/**
 * Internal block libraries
 */
const { __, _x, sprintf } = wp.i18n;
const {
	registerBlockType,
} = wp.blocks;

const {
	InspectorControls,
} = wp.editor;

const {
	ServerSideRender,
	Tooltip,
	PanelBody,
	PanelRow,
	RangeControl,
	FormToggle,
	SelectControl,
	ToggleControl,
	TextControl
} = wp.components;

registerBlockType(
	'learndash/ld-usermeta',
	{
		title: __('LearnDash User meta', 'learndash'),
		description: __('This block displays User meta field', 'learndash'),
		icon: 'id',
		category: 'learndash-blocks',
		example: {
			attributes: {
				example_show: 1,
			},
		},
		supports: {
			customClassName: false,
		},
		attributes: {
			field: {
				type: 'string',
			},
			user_id: {
				type: 'string',
				default: '',
			},
			preview_show: {
				type: 'boolean',
				default: 1
			},
			preview_user_id: {
				type: 'string',
				default: '',
			},

		},
		edit: props => {
			const { attributes: { field, user_id, preview_show, preview_user_id },
				className, setAttributes } = props;

			const field_field = (
				<SelectControl
					key="field"
					label={__('Field', 'learndash')}
					options={[
						{
							label: __('User Login', 'learndash'),
							value: 'user_login',
						},
						{
							label: __('User Display Name', 'learndash'),
							value: 'display_name',
						},
						{
							label: __('User Nicename', 'learndash'),
							value: 'user_nicename',
						},
						{
							label: __('User First Name', 'learndash'),
							value: 'first_name',
						},
						{
							label: __('User Last Name', 'learndash'),
							value: 'last_name',
						},
						{
							label: __('User Nickname', 'learndash'),
							value: 'nickname',
						},
						{
							label: __('User Email', 'learndash'),
							value: 'user_email',
						},
						{
							label: __('User URL', 'learndash'),
							value: 'user_url',
						},
						{
							label: __('User Description', 'learndash'),
							value: 'description',
						},
					]}
					onChange={field => setAttributes({ field })}
				/>
			);

			const field_user_id = (
				<TextControl
					label={__('User ID', 'learndash')}
					help={__('Enter specific User ID. Leave blank for current User.', 'learndash')}
					value={user_id || ''}
					onChange={user_id => setAttributes({ user_id })}
				/>
			);

			const panel_preview = (
				<PanelBody
					title={__('Preview', 'learndash')}
					initialOpen={false}
				>
					<ToggleControl
						label={__('Show Preview', 'learndash')}
						checked={!!preview_show}
						onChange={preview_show => setAttributes({ preview_show })}
					/>
					<TextControl
						label={__('User ID', 'learndash')}
						help={__('Enter a User ID to test preview', 'learndash')}
						value={preview_user_id || ''}
						type={'number'}
						onChange={preview_user_id => setAttributes({ preview_user_id })}
					/>
				</PanelBody>
			);

			const inspectorControls = (
				<InspectorControls>
					<PanelBody
						title={__('Settings', 'learndash')}
					>
						{field_user_id}
						{field_field}
					</PanelBody>
					{panel_preview}
				</InspectorControls>
			);

			function do_serverside_render(attributes) {
				if (attributes.preview_show == true) {
					return <ServerSideRender
						block="learndash/ld-usermeta"
						attributes={attributes}
					/>
				} else {
					return __('[usermeta] shortcode output shown here', 'learndash');
				}
			}

			return [
				inspectorControls,
				do_serverside_render(props.attributes)
			];
		},

		save: props => {
			// Delete preview_user_id from props to prevent it being saved.
			delete (props.attributes.preview_user_id);
		}
	},
);
