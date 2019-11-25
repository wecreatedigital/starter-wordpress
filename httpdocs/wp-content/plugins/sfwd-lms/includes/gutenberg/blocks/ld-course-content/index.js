/**
 * LearnDash Block ld-course-content
 *
 * @since 2.5.9
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import {
	ldlms_get_custom_label,
	ldlms_get_post_edit_meta,
	ldlms_get_per_page,
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
    'learndash/ld-course-content',
    {
		title: sprintf(_x('LearnDash %s Content', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
		description: sprintf(_x('This block displays the %1$s Content table.', 'placeholders: Course', 'learndash'), ldlms_get_custom_label('course') ),
		icon: 'format-aside',
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
				default: '',
            },
            per_page: {
				type: 'string',
				default: '',
			},
			preview_show: {
				type: 'boolean',
				default: 1
			},
			preview_course_id: {
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
        edit: props => {
			const { attributes: { course_id, per_page, preview_show, preview_course_id, example_show },
            	className, setAttributes } = props;

			const inspectorControls = (
				<InspectorControls>
					<PanelBody
						title={ __( 'Settings', 'learndash' ) }
					>
						<TextControl
							label={sprintf(_x('%s ID', 'Course ID', 'learndash'), ldlms_get_custom_label('course') ) }
							help={sprintf(_x('Enter single %1$s ID. Leave blank if used within a %2$s.', 'placeholders: course, course', 'learndash'), ldlms_get_custom_label('course'), ldlms_get_custom_label('course') ) }
							value={ course_id || '' }
							onChange={ course_id => setAttributes( { course_id } ) }
						/>
						<TextControl
							label={sprintf(_x('%s per page', 'placeholder: Lessons', 'learndash'), ldlms_get_custom_label('lessons') ) }
							help={sprintf(_x('Leave empty for default (%d) or 0 to show all items.', 'placeholder: default per page', 'learndash'), ldlms_get_per_page( 'per_page' ) ) }
							value={ per_page || '' }
							type={ 'number' }
							onChange={ per_page => setAttributes( { per_page } ) }
						/>
					</PanelBody>
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
							label={sprintf(_x('%s ID', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ) }
							help={sprintf(_x('Enter a %s ID to test preview', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ) }
							value={preview_course_id || ''}
							type={'number'}
							onChange={preview_course_id => setAttributes({ preview_course_id })}
						/>
					</PanelBody>
				</InspectorControls>
			);

			function do_serverside_render( attributes ) {
				if ( attributes.preview_show == true ) {
					// We add the meta so the server knowns what is being edited.
					attributes.meta = ldlms_get_post_edit_meta();

					return <ServerSideRender
						block="learndash/ld-course-content"
						attributes={attributes}
					/>

				} else {
					return __('[course_content] shortcode output shown here', 'learndash');
				}
			}

			return [
				inspectorControls,
				do_serverside_render(props.attributes)
			];
		},

        save: props => {
			// Delete meta from props to prevent it being saved.
			delete (props.attributes.meta);
		},
	},
);
