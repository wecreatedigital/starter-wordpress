/**
 * LearnDash Block ld-courseinfo
 *
 * @since 2.5.9
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import {
	ldlms_get_post_edit_meta,
	ldlms_get_custom_label,
} from '../ldlms.js';

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
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl
} = wp.components;

registerBlockType(
    'learndash/ld-courseinfo',
    {
		title: sprintf( _x( 'LearnDash %s Info [courseinfo]', 'placeholder: Course', 'learndash' ), ldlms_get_custom_label( 'course' ) ),
		description: sprintf(_x('This block displays %s related information', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ),
		icon: 'analytics',
		category: 'learndash-blocks',
		supports: {
			customClassName: false,
		},
        attributes: {
            show: {
                type: 'string',
            },
            course_id: {
				type: 'string',
				default: '',
            },
            user_id: {
				type: 'string',
				default: '',
			},
			format: {
				type: 'string',
			},
			decimals: {
				type: 'string',
			},
			preview_show: {
				type: 'boolean',
				default: 1
			},
			preview_course_id: {
				type: 'string',
				default: '',
			},
			preview_user_id: {
				type: 'string',
				default: '',
			},
			meta: {
				type: 'object',
			}
        },
        edit: props => {
			const { attributes: { course_id, show, user_id, format, decimals, preview_show, preview_user_id },
            	className, setAttributes } = props;

			const field_show = (
				<SelectControl
					key="show"
					label={__('Show', 'learndash')}
					options={[
						{
							label: __('Title', 'learndash'),
							value: 'course_title',
						},
						{
							label: sprintf(_x('Earned %s Points', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'course_points',
						},
						{
							label: sprintf(_x('Total User %s Points', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'user_course_points',
						},
						{
							label: __('Completed On (date)', 'learndash'),
							value: 'completed_on',
						},
						{
							label: __('Cumulative Score', 'learndash'),
							value: 'cumulative_score',
						},
						{
							label: __('Cumulative Points', 'learndash'),
							value: 'cumulative_points',
						},
						{
							label: __('Possible Cumulative Total Points', 'learndash'),
							value: 'cumulative_total_points',
						},
						{
							label: __('Cumulative Percentage', 'learndash'),
							value: 'cumulative_percentage',
						},
						{
							label: __('Cumulative Time Spent', 'learndash'),
							value: 'cumulative_timespent',
						},
						{
							label: __('Aggregate Percentage', 'learndash'),
							value: 'aggregate_percentage',
						},
						{
							label: __('Aggregate Score', 'learndash'),
							value: 'aggregate_score',
						},
						{
							label: __('Aggregate Points', 'learndash'),
							value: 'aggregate_points',
						},
						{
							label: __('Possible Aggregate Total Points', 'learndash'),
							value: 'aggregate_total_points',
						},
						{
							label: __('Aggregate Time Spent', 'learndash'),
							value: 'aggregate_timespent',
						},
					]}
					onChange={show => setAttributes({ show })}
				/>
			);
			const field_course_id = (
				<TextControl
					label={sprintf(_x('%s ID', 'Course ID', 'learndash'), ldlms_get_custom_label('course'))}
					help={sprintf(_x('Enter single %1$s ID. Leave blank if used within a %2$s.', 'placeholders: course, course', 'learndash'), ldlms_get_custom_label('course'), ldlms_get_custom_label('course'))}
					value={course_id || ''}
					onChange={course_id => setAttributes({ course_id })}
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

			let field_format = '';
			if (show == 'completed_on') {
				field_format = (
					<TextControl
						label={__('Format', 'learndash')}
						help={__('This can be used to change the date format. Default: "F j, Y, g:i a.', 'learndash')}
						value={format || ''}
						onChange={format => setAttributes({ format })}
					/>
				);
			}

			let field_decimals = '';
			if ( (show == 'course_points') || (show == 'user_course_points') ) {
				field_decimals = (
					<TextControl
						label={__('Decimals', 'learndash')}
						help={__('Number of decimal places to show. Default is 2.', 'learndash')}
						value={decimals || ''}
						onChange={decimals => setAttributes({ decimals })}
					/>
				);
			}

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
						title={ __( 'Settings', 'learndash' ) }
					>
						{ field_course_id }
						{ field_user_id }
						{ field_show }
						{ field_format }
						{ field_decimals }
					</PanelBody>
					{ panel_preview }
				</InspectorControls>
			);

			function do_serverside_render(attributes) {
				if (attributes.preview_show == true) {
					// We add the meta so the server knowns what is being edited.
					attributes.meta = ldlms_get_post_edit_meta();

					return <ServerSideRender
						block="learndash/ld-courseinfo"
						attributes={attributes}
					/>
				} else {
					return __('[courseinfo] shortcode output shown here', 'learndash');
				}
			}

			return [
				inspectorControls,
				do_serverside_render(props.attributes)
			];
        },
		save: function (props) {
			// Delete meta from props to prevent it being saved.
			delete(props.attributes.meta);
		}
	},
);
