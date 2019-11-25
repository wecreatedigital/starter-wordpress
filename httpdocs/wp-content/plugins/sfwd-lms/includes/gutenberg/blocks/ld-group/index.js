/**
 * LearnDash Block ld-group
 *
 * @since 2.5.9
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import {
    ldlms_get_integer_value
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
     PanelBody,
     TextControl,
     ToggleControl
 } = wp.components;

registerBlockType(
    'learndash/ld-group',
    {
        title: __( 'LearnDash Group', 'learndash' ),
        description: __( 'This block shows the content if the user is enrolled into the Group.', 'learndash'),
        icon: 'groups',
        category: 'learndash-blocks',
        supports: {
            customClassName: false,
        },
        attributes: {
            group_id: {
                type: 'string',
            },
            user_id: {
                type: 'string',
                default: '',
            },
            autop: {
                type: 'boolean',
                default: true
            },
        },
        edit: props => {
            const { attributes: { group_id, user_id, autop }, className, setAttributes } = props;

            const inspectorControls = (
                <InspectorControls>
                    <PanelBody
                        title={__('Settings', 'learndash')}
                    >
                        <TextControl
                            label={__('Group ID', 'learndash')}
                            help={__('Group ID (required)', 'learndash')}
                            value={group_id || ''}
                            onChange={group_id => setAttributes({ group_id })}
                        />
                        <TextControl
                            label={__('User ID', 'learndash')}
                            help={__('Enter specific User ID. Leave blank for current User.', 'learndash')}
                            value={user_id || ''}
                            onChange={user_id => setAttributes({ user_id })}
                        />
                        <ToggleControl
                            label={__('Auto Paragraph', 'learndash')}
                            checked={!!autop}
                            onChange={autop => setAttributes({ autop })}
                        />
                    </PanelBody>
                </InspectorControls>
            );

            let ld_block_error_message = '';
            let preview_group_id = ldlms_get_integer_value(group_id);
            if (preview_group_id == 0) {
                ld_block_error_message = __('Group ID is required.', 'learndash');
            }

            if (ld_block_error_message.length) {
                ld_block_error_message = (<span className="learndash-block-error-message">{ld_block_error_message}</span>);
            }

            const outputBlock = (
                <div className={className}>
                    <div className="learndash-block-inner">
                        {ld_block_error_message}
                        <InnerBlocks />
                    </div>
                </div>
            );

            return [
                inspectorControls,
                outputBlock
            ];
        },
        save: props => {
			return (
				<InnerBlocks.Content />
			);
		}
	},
);
