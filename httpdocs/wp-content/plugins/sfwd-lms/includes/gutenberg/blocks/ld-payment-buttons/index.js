/**
 * LearnDash Block ld-payment-buttons
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
    ldlms_get_integer_value,
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
    'learndash/ld-payment-buttons',
    {
        title: __( 'LearnDash Payment Buttons', 'learndash' ),
        description: sprintf(_x('This block the %s payment buttons', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ),
        icon: 'cart',
        category: 'learndash-blocks',
        supports: {
            customClassName: false,
        },
        attributes: {
            course_id: {
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
            meta: {
                type: 'object',
            }
        },
        edit: props => {
            const { attributes: { course_id, preview_show, preview_course_id },
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
                    </PanelBody>
                </InspectorControls>
            );

            function do_serverside_render(attributes) {
                if (attributes.preview_show == true) {
                    let ld_block_error_message = '';
                    let preview_course_id = ldlms_get_integer_value(course_id);

                    if (preview_course_id === 0) {
                        preview_course_id = ldlms_get_post_edit_meta('course_id');
                        preview_course_id = ldlms_get_integer_value(preview_course_id);

                        if (preview_course_id == 0) {
                            ld_block_error_message = sprintf(_x('%1$s ID is required when not used within a %2$s.', 'placeholders: Course, Course', 'learndash'), ldlms_get_custom_label('course'), ldlms_get_custom_label('course'));
                        }
                    }

                    if (ld_block_error_message.length) {
                        ld_block_error_message = (<span className="learndash-block-error-message">{ld_block_error_message}</span>);

                        const outputBlock = (
                            <div className={className}>
                                <div className="learndash-block-inner">
                                    {ld_block_error_message}
                                </div>
                            </div>
                        );
                        return outputBlock;
                    } else {
                        // We add the meta so the server knowns what is being edited.
                        attributes.meta = ldlms_get_post_edit_meta();

                        return <ServerSideRender
                            block="learndash/ld-payment-buttons"
                            attributes={attributes}
                        />
                    }
                } else {
                    return __('[learndash_payment_buttons] shortcode output shown here', 'learndash');
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
