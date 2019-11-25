/**
 * LearnDash Block ld-profile
 *
 * @since 2.5.9
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import {
	ldlms_get_custom_label,
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
	SelectControl,
	ToggleControl,
	TextControl
} = wp.components;

registerBlockType(
    'learndash/ld-profile',
    {
        title: __( 'LearnDash Profile', 'learndash' ),
		description: sprintf(_x("Displays user's enrolled %1$s, %2$s progress, %3$s scores, and achieved certificates.", 'placeholder: courses, course, quiz', 'learndash'), ldlms_get_custom_label('courses'), ldlms_get_custom_label('course'), ldlms_get_custom_label('quiz') ),
		icon: 'id-alt',
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
            per_page: {
				type: 'string',
				default: '',
            },
            orderby: {
				type: 'string',
				default: 'ID'
            },
            order: {
				type: 'string',
				default: 'DESC'
            },
            course_points_user: {
				type: 'boolean',
				default: 1
            },
            expand_all: {
				type: 'boolean',
				default: 0
            },
            profile_link: {
				type: 'boolean',
				default: 1
			},
			show_header: {
				type: 'boolean',
				default: 1
			},
			show_search: {
				type: 'boolean',
				default: 1
			},
			show_quizzes: {
				type: 'boolean',
				default: 1
			},
			preview_show: {
				type: 'boolean',
				default: 1
			},
			preview_user_id: {
				type: 'string',
				default: '',
			},
			example_show: {
				type: 'boolean',
				default: 0
			},
		},
        edit: function( props ) {
			const { attributes: { per_page, orderby, order, course_points_user, expand_all, profile_link, show_header, show_search, show_quizzes, preview_user_id, preview_show, example_show },
            	setAttributes } = props;

			const inspectorControls = (
				<InspectorControls>
					<PanelBody
						title={ __( 'Settings', 'learndash' ) }
					>
						<TextControl
							label={sprintf(_x('%s per page', 'placeholder: Lessons', 'learndash'), ldlms_get_custom_label('courses') ) }
							help={sprintf(_x('Leave empty for default (%d) or 0 to show all items.', 'placeholder: default per page', 'learndash'), ldlms_get_per_page('per_page') ) }
							value={per_page || ''}
							type={'number'}
							onChange={per_page => setAttributes({ per_page })}
						/>
						<SelectControl
							key="orderby"
							label={ __( 'Order by', 'learndash' ) }
							value={ orderby }
							options={ [
								{
									label: __('ID - Order by post id. (default)', 'learndash'),
									value: 'ID',
								},
								{
									label: __('Title - Order by post title', 'learndash'),
									value: 'title',
								},
								{
									label: __('Date - Order by post date', 'learndash'),
									value: 'date',
								},
								{
									label: __('Menu - Order by Page Order Value', 'learndash'),
									value: 'menu_order',
								}
							] }
							onChange={ orderby => setAttributes( { orderby } ) }
						/>
						<SelectControl
							key="order"
							label={ __( 'Order', 'learndash' ) }
							value={ order }
							options={ [
								{
									label: __('DESC - highest to lowest values (default)', 'learndash'),
									value: 'DESC',
								},
								{
									label: __('ASC - lowest to highest values', 'learndash'),
									value: 'ASC',
								},
							] }
							onChange={ order => setAttributes( { order } ) }
						/>
						<ToggleControl
							label={__('Show Search', 'learndash')}
							checked={!!show_search}
							onChange={show_search => setAttributes({ show_search })}
							help={__('LD30 template only', 'learndash')}
						/>
						<ToggleControl
							label={__('Show Profile Header', 'learndash')}
							checked={!!show_header}
							onChange={show_header => setAttributes({ show_header })}
						/>
						<ToggleControl
							label={sprintf(_x('Show Earned %s Points', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ) }
							checked={ !!course_points_user }
							onChange={ course_points_user => setAttributes( { course_points_user } ) }
						/>
						<ToggleControl
							label={ __('Show Profile Link', 'learndash') }
							checked={ !!profile_link }
							onChange={ profile_link => setAttributes( { profile_link } ) }
						/>
						<ToggleControl
							label={sprintf(_x('Show User Quiz Attempts', 'placeholder: Quiz', 'learndash'), ldlms_get_custom_label('quiz') ) }
							checked={ !!show_quizzes }
							onChange={ show_quizzes => setAttributes( { show_quizzes } ) }
						/>
						<ToggleControl
							label={sprintf(_x('Expand All %s Sections', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course'))}
							checked={!!expand_all}
							onChange={expand_all => setAttributes({ expand_all })}
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
					return <ServerSideRender
					block="learndash/ld-profile"
					attributes={ attributes }
					/>
				} else {
					return __( '[ld_profile] shortcode output shown here', 'learndash' );
				}
			}

			return [
				inspectorControls,
				do_serverside_render( props.attributes )
			];
        },

        save: props => {
			// Delete preview_user_id from props to prevent it being saved.
			delete (props.attributes.preview_user_id);
		}
	},
);
