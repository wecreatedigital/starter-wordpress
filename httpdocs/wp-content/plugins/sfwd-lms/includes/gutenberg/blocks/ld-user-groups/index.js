/**
 * LearnDash Block ld-user-groups
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
    'learndash/ld-user-groups',
    {
        title: __( 'LearnDash User Groups', 'learndash' ),
        description: __( 'This block displays the list of groups users are assigned to as users or leaders.', 'learndash' ),
        icon: 'groups',
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

            }
        },
        edit: function (props) {
            const { attributes: { user_id, preview_user_id, preview_show },
                setAttributes } = props;

            const inspectorControls = (
                <InspectorControls>
                    <PanelBody
                        title={ __( 'Settings', 'learndash' ) }
                    >
                        <TextControl
                            label={ __( 'User ID', 'learndash' ) }
                            help={__('Enter specific User ID. Leave blank for current User.', 'learndash')}
                            value={ user_id || '' }
                            onChange={ user_id => setAttributes( { user_id } ) }
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
                if (attributes.preview_show == true) {
                    return <ServerSideRender
                        block="learndash/ld-user-groups"
                        attributes={attributes}
                    />
                } else {
                    return __('[user_groups] output shown here', 'learndash');
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

            // Delete preview_user_id from props to prevent it being saved.
            delete (props.attributes.preview_user_id);
		}
	},
);
