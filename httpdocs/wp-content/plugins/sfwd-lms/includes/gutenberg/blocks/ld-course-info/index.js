/**
 * LearnDash Block ld-course-info
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
	RangeControl,
	SelectControl,
	ToggleControl,
	TextControl
} = wp.components;

registerBlockType(
    'learndash/ld-course-info',
    {
		title: sprintf(_x('LearnDash %s Info [ld_course_info]', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
		description: sprintf(_x('This block shows the %s and progress for the user.', 'placeholders: courses', 'learndash'), ldlms_get_custom_label('course') ),
		icon: 'analytics',
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
				default: 0,
			},
			registered_show: {
				type: 'boolean',
				default: true
			},
			registered_show_thumbnail: {
				type: 'boolean',
				default: true
			},
			registered_num: {
				type: 'string',
				default: '',
            },
			registered_orderby: {
				type: 'string',
				default: 'ID'
            },
			registered_order: {
				type: 'string',
				default: 'ASC'
            },
			progress_show: {
				type: 'boolean',
				default: true
			},
			progress_num: {
				type: 'string',
				default: '',
			},
			progress_orderby: {
				type: 'string',
				default: 'ID'
			},
			progress_order: {
				type: 'string',
				default: 'ASC'
			},
			quiz_show: {
				type: 'boolean',
				default: true
			},
			quiz_num: {
				type: 'string',
				default: '',
			},
			quiz_orderby: {
				type: 'string',
				default: 'taken'
			},
			quiz_order: {
				type: 'string',
				default: 'DESC'
			},
			preview_show: {
				type: 'boolean',
				default: true
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
			const { attributes: { user_id, registered_show, registered_show_thumbnail, registered_num, registered_orderby, registered_order, progress_show, progress_num, progress_orderby, progress_order, quiz_show, quiz_num, quiz_orderby, quiz_order, preview_user_id, preview_show },
            	setAttributes } = props;


			const panelbody_header = (
				<PanelBody
					title={__('Settings', 'learndash')}
				>
					<TextControl
						label={__('User ID', 'learndash')}
						help={__('Enter specific User ID. Leave blank for current User.', 'learndash')}
						value={user_id || ''}
						onChange={user_id => setAttributes({ user_id })}
					/>

					<ToggleControl
						label={sprintf(_x('Show Registered %s', 'placeholder: Courses', 'learndash'), ldlms_get_custom_label('courses') ) }
						checked={!!registered_show}
						onChange={registered_show => setAttributes({ registered_show })}
					/>
					<ToggleControl
						label={sprintf(_x('Show %s Progess', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ) }
						checked={!!progress_show}
						onChange={progress_show => setAttributes({ progress_show })}
					/>
					<ToggleControl
						label={sprintf(_x('Show %s Attempts', 'placeholder: Quiz', 'learndash'), ldlms_get_custom_label('quiz'))}
						checked={!!quiz_show}
						onChange={quiz_show => setAttributes({ quiz_show })}
					/>
				</PanelBody>
			);

			var panelbody_registered = ('');
			if ( registered_show === true ) {
				panelbody_registered = (
					<PanelBody
						title={sprintf(_x('Registered %s', 'placeholder: Courses', 'learndash'), ldlms_get_custom_label('courses') ) }
						initialOpen={false}
					>
						<ToggleControl
							label={__('Show Thumbnail', 'learndash')}
							checked={!!registered_show_thumbnail}
							onChange={registered_show_thumbnail => setAttributes({ registered_show_thumbnail })}
						/>
						<TextControl
							label={__('per page', 'learndash')}
							help={sprintf(_x('Leave empty for default (%d) or 0 to show all items.', 'placeholder: default per page', 'learndash'), ldlms_get_per_page('per_page'))}
							value={registered_num || ''}
							min={0}
							max={100}
							onChange={registered_num => setAttributes({ registered_num })}
						/>
						<SelectControl
							key="registered_orderby"
							label={__('Order by', 'learndash')}
							value={registered_orderby}
							options={[
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
							]}
							onChange={registered_orderby => setAttributes({ registered_orderby })}
						/>
						<SelectControl
							key="registered_order"
							label={__('Order', 'learndash')}
							value={registered_order}
							options={[
								{
									label: __('DESC - highest to lowest values (default)', 'learndash'),
									value: 'DESC',
								},
								{
									label: __('ASC - lowest to highest values', 'learndash'),
									value: 'ASC',
								},
							]}
							onChange={registered_order => setAttributes({ registered_order })}
						/>
					</PanelBody>
				)
			}

			var panelbody_progress = ('');
			if (progress_show === true) {
				panelbody_progress = (
					<PanelBody
						title={sprintf(_x('%s Progress', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ) }
						initialOpen={false}
					>
						<TextControl
							label={__('per page', 'learndash')}
							help={sprintf(_x('Leave empty for default (%d) or 0 to show all items.', 'placeholder: default per page', 'learndash'), ldlms_get_per_page('progress_num' ) ) }
							value={progress_num || ''}
							min={0}
							max={100}
							onChange={progress_num => setAttributes({ progress_num })}
						/>
						<SelectControl
							key="progress_orderby"
							label={__('Order by', 'learndash')}
							value={progress_orderby}
							options={[
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
							]}
							onChange={progress_orderby => setAttributes({ progress_orderby })}
						/>
						<SelectControl
							key="progress_order"
							label={__('Order', 'learndash')}
							value={progress_order}
							options={[
								{
									label: __('DESC - highest to lowest values (default)', 'learndash'),
									value: 'DESC',
								},
								{
									label: __('ASC - lowest to highest values', 'learndash'),
									value: 'ASC',
								},
							]}
							onChange={progress_order => setAttributes({ progress_order })}
						/>
					</PanelBody>
				);
			}

			var panelbody_quiz = ('');
			if ( quiz_show === true ) {
				panelbody_quiz = (
					<PanelBody
						title={sprintf(_x('%s Attempts', 'placeholder: Quiz', 'learndash'), ldlms_get_custom_label('quiz') ) }
						initialOpen={false}
					>
						<TextControl
							label={__('per page', 'learndash')}
							help={sprintf(_x('Leave empty for default (%d) or 0 to show all items.', 'placeholder: default per page', 'learndash'), ldlms_get_per_page('quiz_num') ) }
							value={quiz_num || ''}
							min={0}
							max={100}
							onChange={quiz_num => setAttributes({ quiz_num })}
						/>
						<SelectControl
							key="quiz_orderby"
							label={__('Order by', 'learndash')}
							value={quiz_orderby}
							options={[
								{
									label: __('Date Taken (default) - Order by date taken', 'learndash'),
									value: 'taken',
								},
								{
									label: __('Title - Order by post title', 'learndash'),
									value: 'title',
								},
								{
									label: __('ID - Order by post id. (default)', 'learndash'),
									value: 'ID',
								},								{
									label: __('Date - Order by post date', 'learndash'),
									value: 'date',
								},
								{
									label: __('Menu - Order by Page Order Value', 'learndash'),
									value: 'menu_order',
								}
							]}
							onChange={quiz_orderby => setAttributes({ quiz_orderby })}
						/>
						<SelectControl
							key="quiz_order"
							label={__('Order', 'learndash')}
							value={quiz_order}
							options={[
								{
									label: __('DESC - highest to lowest values (default)', 'learndash'),
									value: 'DESC',
								},
								{
									label: __('ASC - lowest to highest values', 'learndash'),
									value: 'ASC',
								},
							]}
							onChange={quiz_order => setAttributes({ quiz_order })}
						/>
					</PanelBody>
				);
			}

			const inspectorControls = (
				<InspectorControls>
					{ panelbody_header }
					{ panelbody_registered }
					{ panelbody_progress }
					{ panelbody_quiz }

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
					block="learndash/ld-course-info"
					attributes={ attributes }
					/>
				} else {
					return __( '[ld_course_info] shortcode output shown here', 'learndash' );
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
