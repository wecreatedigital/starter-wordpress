/**
 * LearnDash Block ld-course-progress
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
    InnerBlocks,
    InspectorControls,
} = wp.editor;

const {
    ServerSideRender,
    PanelBody,
    ToggleControl,
    TextControl
} = wp.components;

const el = wp.element.createElement;

const iconEl = el('svg', { width: 300, height: 300, viewBox: '0 0 50 10' },
  el('path', { d: "M47.1,0h-44c-1.7,0-3,1.3-3,3v4c0,1.7,1.3,3,3,3h44c1.7,0,3-1.3,3-3V3C50.1,1.3,48.7,0,47.1,0z M48.1,7c0,0.6-0.4,1-1,1h-12 V2h12c0.6,0,1,0.4,1,1V7z" } )
);

registerBlockType(
    'learndash/ld-course-progress',
    {
        title: sprintf(_x('LearnDash %s Progress', 'LearnDash Course Progress', 'learndash'), ldlms_get_custom_label('course') ),
        description: sprintf(_x('This block displays users progress bar for the %1$s.', 'placeholders: course', 'learndash'), ldlms_get_custom_label('course') ),
        icon: iconEl,
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
            preview_show: {
                type: 'boolean',
                default: 1
            },
            preview_user_id: {
                type: 'string',
            },
            preview_course_id: {
                type: 'string',
            },
            example_show: {
                type: 'boolean',
                default: 0
            },
        },
        edit: props => {
            let { attributes: { course_id }, className } = props;
            const { attributes: { user_id, preview_show, preview_user_id, preview_course_id, example_show },
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
                            label={sprintf(_x('%s ID', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course'))}
                            help={sprintf(_x('Enter a %s ID to test preview', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course'))}
                            value={preview_course_id || ''}
                            type={'number'}
                            onChange={preview_course_id => setAttributes({ preview_course_id })}
                        />
                        <TextControl
                            label={__('User ID', 'learndash')}
                            help={__('Enter a User ID to test preview', 'learndash')}
                            value={preview_user_id || ''}
                            type={'number'}
                            onChange={preview_user_id => setAttributes({ preview_user_id })}
                        />
                    </PanelBody>
                </InspectorControls>
            );

            function do_serverside_render(attributes) {
                if ( attributes.preview_show == true ) {
                    // We add the meta so the server knowns what is being edited.
                    attributes.meta = ldlms_get_post_edit_meta();

                    return <ServerSideRender
                        block="learndash/ld-course-progress"
                        attributes={attributes}
                    />
                } else {
                    return __('[learndash_course_progress] shortcode output shown here', 'learndash');
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
		}
	},
);
