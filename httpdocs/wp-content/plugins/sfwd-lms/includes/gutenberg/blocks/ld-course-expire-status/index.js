/**
 * LearnDash Block ld-course-expire-status
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
	ToggleControl,
	TextControl
} = wp.components;

registerBlockType(
    'learndash/ld-course-expire-status',
    {
		title: sprintf(_x('LearnDash %s Expire Status', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ),
		description: sprintf(_x('This block displays the user %s access expire date.', 'placeholders: course', 'learndash'), ldlms_get_custom_label('course') ),
		icon: 'clock',
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
            course_id: {
				type: 'string',
				default: ''
            },
            user_id: {
				type: 'string',
				default: ''
            },
			label_before: {
				type: 'string',
				default: '',
			},
			label_after: {
				type: 'string',
				default: '',
			},
			autop: {
				type: 'boolean',
				default: true
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
			example_show: {
				type: 'boolean',
				default: 0
			},
			meta: {
				type: 'object',
			}
		},
        edit: function( props ) {
			let { attributes: { course_id }, className } = props;
			const { attributes: { user_id, label_before, label_after, autop, preview_course_id, preview_user_id, preview_show, example_show },
            	setAttributes } = props;

			const inspectorControls = (
				<InspectorControls>
					<PanelBody
						title={ __( 'Settings', 'learndash' ) }
					>
						<TextControl
							label={sprintf(_x('%s ID', 'Course ID', 'learndash'), ldlms_get_custom_label('course') ) }
							help={sprintf(_x('Enter single %1$s ID. Leave blank if used within a %2$s.', 'placeholders: course, course', 'learndash'), ldlms_get_custom_label('course'), ldlms_get_custom_label('course') ) }
							value={course_id || ''}
							onChange={course_id => setAttributes({ course_id })}
						/>
						<TextControl
							label={__('User ID', 'learndash')}
							help={__('Enter specific User ID. Leave blank for current User.', 'learndash')}
							value={user_id || ''}
							onChange={user_id => setAttributes({ user_id })}
						/>

						<TextControl
							label={__('Label Before', 'learndash')}
							help={__('The label prefix shown before the access expires', 'learndash')}
							value={label_before || ''}
							onChange={label_before => setAttributes({ label_before })}
						/>
						<TextControl
							label={__('Label After', 'learndash')}
							help={__('The label prefix shown after access has expired', 'learndash')}
							value={label_after || ''}
							onChange={label_after => setAttributes({ label_after })}
						/>
						<ToggleControl
							label={__('Auto Paragraph', 'learndash')}
							checked={!!autop}
							onChange={autop => setAttributes({ autop })}
						/>

					</PanelBody>
					<PanelBody
						title={ __( 'Preview', 'learndash' ) }
						initialOpen={ false }
					>
						<ToggleControl
							label={ __('Show Preview', 'learndash') }
							checked={ !!preview_show }
							onChange={ preview_show => setAttributes( { preview_show } ) }
						/>
						<TextControl
							label={sprintf(_x('%s ID', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course'))}
							help={sprintf(_x('Enter a %s ID to test preview', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course'))}
							value={preview_course_id || ''}
							type={'number'}
							onChange={preview_course_id => setAttributes({ preview_course_id })}
						/>
						<TextControl
							label={ __( 'User ID', 'learndash' ) }
							help={ __('Enter a User ID to test preview', 'learndash') }
							value={ preview_user_id || '' }
							type={ 'number' }
							onChange={ preview_user_id => setAttributes( { preview_user_id } ) }
						/>
					</PanelBody>
				</InspectorControls>
			);

			function do_serverside_render( attributes ) {
				if ( attributes.preview_show == true ) {
					// We add the meta so the server knowns what is being edited.
					attributes.meta = ldlms_get_post_edit_meta();

					return <ServerSideRender
						block="learndash/ld-course-expire-status"
						attributes={attributes}
					/>
				} else {
					return __( '[ld_course_expire_status] shortcode output shown here', 'learndash' );
				}
			}

			return [
				inspectorControls,
				do_serverside_render( props.attributes )
			];
        },

        save: props => {
			// Delete meta from props to prevent it being saved.
			delete (props.attributes.meta);

			// Delete preview_user_id from props to prevent it being saved.
			delete (props.attributes.preview_user_id);
		}
	},
);
