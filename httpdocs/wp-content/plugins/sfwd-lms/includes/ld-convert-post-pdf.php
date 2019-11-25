<?php
/**
 * Generate PDF
 *
 * Originally by Redcocker 2012/3/5
 * License: GPL v2
 * http://www.near-mint.com/blog/
 *
 * @since 2.1.0
 *
 * @package LearnDash\PDF
 */


if ( ! function_exists( 'learndash_get_thumb_path' ) ) {

	/**
	 * Get featured image of certificate post
	 * 
	 * @param  int 		$post_id
	 * @return string 	full image path
	 */
	function learndash_get_thumb_path( $post_id ) {
		$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );

		if ( $thumbnail_id ) {
			$img_path = get_post_meta( $thumbnail_id, '_wp_attached_file', true );
			$upload_url = wp_upload_dir();
			$img_full_path = $upload_url['basedir'] . '/' . $img_path;
			return $img_full_path;
		}
	}
}



if ( ! function_exists( 'post2pdf_conv_post_to_pdf' ) ) {

	/**
	 * Convert Post to PDF
	 */
	function post2pdf_conv_post_to_pdf() {

		/**
		 * Callback for image align center
		 * 
		 * @param  array $matches array with strings to search and replace.
		 * @return array $matches
		 */
		function post2pdf_conv_image_align_center( $matches ) {
			$tag_begin = '<p class="post2pdf_conv_image_align_center">';
			$tag_end = '</p>';

			return $tag_begin . $matches[1] . $tag_end;
		}



		/**
		 * Callback for images without width and height attribute
		 * 
		 * @param  array $matches array with strings to search and replace.
		 * @return array $matches
		 */
		function post2pdf_conv_img_size( $matches ) {
			$size = null;

			if ( strpos( $matches[2], site_url() ) === false ) {
				return $matches[1] . $matches[5];
			}

			$image_path = ABSPATH . str_replace( site_url() . '/', '', $matches[2] );
			
			if ( file_exists( $image_path ) ) {
				$size = getimagesize( $image_path );
			} else {
				return $matches[1] . $matches[5];
			}

			return $matches[1] . ' ' . $size[3] . $matches[5];
		}

		$post_id = 0;
		$target_post_id = $post_id;
		$get_by_http_request = 0;
		$filename_type = 'title';
		$config_lang = 'eng';
		$post2pdf_conv_setting_opt = Array('font_path' => 0);

		$subsetting_enable = $filters = $header_enable = $footer_enable = $monospaced_font = $font = $font_size = $wrap_title = '';
		$ratio = 1.25;
		$shortcode = 'parse';

		ob_start();

		if ( ! empty( $_GET['id'] ) ) {
			$post_id = intval( $_GET['id'] );
		}

		if ( $target_post_id != 0 ) {
			$post_id = $target_post_id;
		}

		$post_data = get_post( $post_id );
		if ( ! $post_data ) {
			wp_die( esc_html__( 'Post does not exist.', 'learndash' ) );
		}

		$title = $post_data->post_title;
		// For qTranslate
		if ( function_exists( 'qtrans_use' ) && ! empty( $this->q_config['language'] ) ) {
			$title = qtrans_use( $this->q_config['language'], $title, false );
		}

		$title = strip_tags( $title );

		$permalink = get_permalink( $post_data->ID );
		$author_data = get_userdata( $post_data->post_author );

		if ( $author_data->display_name ) {
			$author = $author_data->display_name;
		} else {
			$author = $author_data->user_nicename;
		}

		$tag = array();
		$tags = '';
		$tags_data = wp_get_post_tags( $post_data->ID );
		
		if ( $tags_data ) {
			foreach ( $tags_data as $val ) {
				$tag[] = $val->name;
			}
			$tags = implode( ' ', $tag );
		}

		if ( $get_by_http_request == 1 ) {
			$permalink_url = get_permalink( $post_id );
			$response_data = wp_remote_get( $permalink_url );
			$content = preg_replace( '|^.*?<!-- post2pdf-converter-begin -->(.*?)<!-- post2pdf-converter-end -->.*?$|is', '$1', $response_data['body'] );
		} else {
			$content = $post_data->post_content;
			
			// For qTranslate
			if ( function_exists( 'qtrans_use' ) && ! empty( $this->q_config['language'] ) ) {
				$content = qtrans_use( $this->q_config['language'], $content, true );
			}
		}

		if ( ! empty( $_GET['lang'] ) ) {
			$config_lang_tmp = substr( esc_html( $_GET['lang'] ), 0, 3 );
			//if ( ( strlen( $config_lang_tmp ) == 3 ) && ( file_exists( dirname( __FILE__ ) . '/vendor/tcpdf/config/lang/' . $config_lang_tmp . '.php' ) ) ) {
			if ( ( strlen( $config_lang_tmp ) == 3 ) && ( file_exists( LEARNDASH_LMS_LIBRARY_DIR . '/tcpdf/config/lang/' . $config_lang_tmp . '.php' ) ) ) {	
				$config_lang = $config_lang_tmp;
			} 
		}

		if ( ! empty( $_GET['file'] ) ) {
			$filename_type = $_GET['file'];
		}

		if ( $filename_type == 'title' && $target_post_id == 0 ) {
			$filename = $post_data->post_title;
			
			// For qTranslate
			if ( function_exists( 'qtrans_use' ) && ! empty( $this->q_config['language'] ) ) {
				$filename = qtrans_use( $this->q_config['language'], $filename, false );
			}
		} else {
			$filename = $post_id;
		}

		$filename = substr( $filename, 0, 255 );

		$chached_filename = '';

		if ( $target_post_id != 0 ) {
			$filename = WP_CONTENT_DIR . '/tcpdf-pdf/' . $filename;
		}
		
		// For qTranslate
		if ( function_exists( 'qtrans_use' ) && ! empty( $this->q_config['language'] ) ) {
			$filename = $filename . '_' . $this->q_config['language'];
		}

		if ( ! empty( $_GET['font'] ) ) {
			$font = esc_html( $_GET['font'] );
		}

		if ( ! empty( $_GET['monospaced'] ) ) {
			$monospaced_font = esc_html( $_GET['monospaced'] );
		}

		if ( ! empty( $_GET['fontsize'] ) ) {
			$font_size = intval( $_GET['fontsize'] );
		}

		if ( ! empty( $_GET['subsetting'] ) &&( $_GET['subsetting'] == 1 || $_GET['subsetting'] == 0 ) ) {
			$subsetting_enable = $_GET['subsetting'];
		}

		if ( $subsetting_enable == 1 ) {
			$subsetting = 'true';
		} else {
			$subsetting = 'false';
		}

		if ( ! empty( $_GET['ratio'] ) ) {
			$ratio = floatval( $_GET['ratio'] );
		}

		if ( ! empty( $_GET['header'] ) ) {
			$header_enable = $_GET['header'];
		}

		if ( ! empty( $_GET['logo'] ) ) {
			$logo_enable = $_GET['logo'];
		}

		if ( ! empty( $_GET['logo_file'] ) ) {
			$logo_file = esc_html( $_GET['logo_file'] );
		}

		if ( ! empty( $_GET['logo_width'] ) ) {
			$logo_width = intval( $_GET['logo_width'] );
		}

		if ( ! empty( $_GET['wrap_title'] ) ) {
			$wrap_title = $_GET['wrap_title'];
		}

		if ( ! empty( $_GET['footer'] ) ) {
			$footer_enable = $_GET['footer'];
		}

		if ( ! empty( $_GET['filters'] ) ) {
			$filters = $_GET['filters'];
		}

		if ( ! empty( $_GET['shortcode'] ) ) {
			$shortcode = esc_html( $_GET['shortcode'] );
		}

		if ( $target_post_id != 0 ) {
			$destination = 'F';
		} else {
			$destination = 'I';
		}
		// Delete shortcode for POST2PDF Converter
		$content = preg_replace( '|\[pdf[^\]]*?\].*?\[/pdf\]|i', '', $content );
		
		// For WP-Syntax, WP-CodeBox(GeSHi) and WP-GeSHi-Highlight -- syntax highlighting with clean, small and valid (X)HTML
		if ( function_exists( 'wp_syntax_highlight' ) || function_exists( 'wp_codebox_before_filter' ) || function_exists( 'wp_geshi_main' ) ) {
			$content = preg_replace_callback( "/<pre[^>]*?lang=['\"][^>]*?>(.*?)<\/pre>/is", array($this, post2pdf_conv_sourcecode_wrap_pre_and_esc), $content );
		}
		
		// For CodeColorer(GeSHi)
		if ( class_exists( 'CodeColorerLoader' ) ) {
			$content = preg_replace_callback( "/<code[^>]*?lang=['\"][^>]*?>(.*?)<\/code>/is", array($this, post2pdf_conv_sourcecode_wrap_pre_and_esc), $content );
		}
		
		// For WP Code Highlight
		if ( function_exists( 'wp_code_highlight_filter' ) ) {
			$content = wp_code_highlight_filter( $content );
			$content = preg_replace( '/<pre[^>]*?>(.*?)<\/pre>/is', '<pre style="word-wrap:break-word; color: #406040; background-color: #F1F1F1; border: 1px solid #9F9F9F;">$1</pre>', $content );
		}
		
		// Parse shortcode before applied WP default filters
		if ( $shortcode == 'parse' && $get_by_http_request != 1 ) {
			
			// For WP SyntaxHighlighter
			if ( function_exists( 'wp_sh_add_extra_bracket' ) ) {
				$content = wp_sh_add_extra_bracket( $content );
			}
			
			if ( function_exists( 'wp_sh_do_shortcode' ) ) {
				$content = wp_sh_do_shortcode( $content );
			}
			
			// For SyntaxHighlighter Evolved			
			if ( class_exists( 'SyntaxHighlighter' ) ) {
				global $SyntaxHighlighter;
				if ( method_exists( 'SyntaxHighlighter', 'parse_shortcodes' ) && method_exists( 'SyntaxHighlighter', 'shortcode_hack' ) ) {
					$content = $SyntaxHighlighter->parse_shortcodes( $content );
				}
			}
			
			// For SyntaxHighlighterPro
			if ( class_exists( 'GoogleSyntaxHighlighterPro' ) ) {
				global $googleSyntaxHighlighter;
				if ( method_exists( 'GoogleSyntaxHighlighterPro', 'bbcode' ) ) {
					$content = $googleSyntaxHighlighter->bbcode( $content );
				}
			}
			
			// For CodeColorer(GeSHi)
			if ( class_exists( 'CodeColorerLoader' ) ) {
				$content = preg_replace_callback( "/\[cc[^\]]*?lang=['\"][^\]]*?\](.*?)\[\/cc\]/is", array($this, post2pdf_conv_sourcecode_wrap_pre_and_esc), $content );
			}
		} else if ( $get_by_http_request != 1 ) {
			
			// For WP SyntaxHighlighter
			if ( function_exists( 'wp_sh_strip_shortcodes' ) ) {
				$content = wp_sh_strip_shortcodes( $content );
			}
			
			// For SyntaxHighlighterPro
			if ( class_exists( 'GoogleSyntaxHighlighterPro' ) ) {
				global $googleSyntaxHighlighter;
				if ( method_exists( 'GoogleSyntaxHighlighterPro', 'bbcode_strip' ) ) {
					$content = $googleSyntaxHighlighter->bbcode_strip( $content );
				}
			}
			
			// For CodeColorer(GeSHi)
			if ( class_exists( 'CodeColorerLoader' ) ) {
				$content = preg_replace_callback( "/\[cc[^\]]*?lang=['\"][^\]]*?\](.*?)\[\/cc\]/is", array($this, post2pdf_conv_sourcecode_esc), $content );
			}
		}
		
		// Apply WordPress default filters to title and content
		if ( $filters == 1 && $get_by_http_request != 1 ) {
			
			if ( has_filter( 'the_title', 'wptexturize' ) ) {
				$title = wptexturize( $title );
			}

			if ( has_filter( 'the_title', 'convert_chars' ) ) {
				$title = convert_chars( $title );
			}

			if ( has_filter( 'the_title', 'trim' ) ) {
				$title = trim( $title );
			}

			if ( has_filter( 'the_title', 'capital_P_dangit' ) ) {
				$title = capital_P_dangit( $title );
			}

			if ( has_filter( 'the_content', 'wptexturize' ) ) {
				$content = wptexturize( $content );
			}

			if ( has_filter( 'the_content', 'convert_smilies' ) ) {
				$content = convert_smilies( $content );
			}

			if ( has_filter( 'the_content', 'convert_chars' ) ) {
				$content = convert_chars( $content );
			}

			if ( has_filter( 'the_content', 'wpautop' ) ) {
				$content = wpautop( $content );
			}

			if ( has_filter( 'the_content', 'shortcode_unautop' ) ) {
				$content = shortcode_unautop( $content );
			}

			if ( has_filter( 'the_content', 'prepend_attachment' ) ) {
				$content = prepend_attachment( $content );
			}

			if ( has_filter( 'the_content', 'capital_P_dangit' ) ) {
				$content = capital_P_dangit( $content );
			}
		}

		// Include TCPDF
		if ( !class_exists( 'TCPDF' ) ) {
			//require_once dirname( __FILE__ ) . '/vendor/tcpdf/config/lang/' . $config_lang . '.php';
			require_once LEARNDASH_LMS_LIBRARY_DIR . '/tcpdf/config/lang/' . $config_lang . '.php';
			
			//require_once dirname( __FILE__ ) . '/vendor/tcpdf/tcpdf.php';
			require_once LEARNDASH_LMS_LIBRARY_DIR . '/tcpdf/tcpdf.php';
		}
		
		$learndash_certificate_options = get_post_meta( $post_data->ID, 'learndash_certificate_options', true);
		if (!is_array($learndash_certificate_options))
			$learndash_certificate_options = array($learndash_certificate_options);
	
		if ( !isset( $learndash_certificate_options['pdf_page_format'] ) )
			$learndash_certificate_options['pdf_page_format'] = PDF_PAGE_FORMAT;

		if ( !isset( $learndash_certificate_options['pdf_page_orientation'] ) )
			$learndash_certificate_options['pdf_page_orientation'] = PDF_PAGE_ORIENTATION;
		
		// Create a new object
		//$pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, false );
		
		$tcpdf_params = array(
			'orientation'	=>	$learndash_certificate_options['pdf_page_orientation'], 
			'unit'			=>	PDF_UNIT, 
			'format'		=>	$learndash_certificate_options['pdf_page_format'], 
			'unicode'		=>	true, 
			'encoding'		=>	'UTF-8', 
			'diskcache'		=>	false, 
			'pdfa'			=>	false,
			'margins'		=>	array(
				'top'		=>	PDF_MARGIN_TOP,
				'right'		=>	PDF_MARGIN_RIGHT,
				'bottom'	=>	PDF_MARGIN_BOTTOM,
				'left'		=>	PDF_MARGIN_LEFT
			)
		);

		// Added to let external manipulate the TCPDF parameters. 
		// @since 2.4.7
		$tcpdf_params = apply_filters('learndash_certificate_params', $tcpdf_params, $post_id );
		
		$pdf = new TCPDF( 
			$tcpdf_params['orientation'],
			$tcpdf_params['unit'],
			$tcpdf_params['format'],
			$tcpdf_params['unicode'],
			$tcpdf_params['encoding'],
			$tcpdf_params['diskcache'],
			$tcpdf_params['pdfa']
		);
		
		// Added to let external manipulate the $pdf instance. 
		// @since 2.4.7
		do_action( 'learndash_certification_created', $pdf, $post_id );
		
		// Set document information
		$pdf->SetCreator( PDF_CREATOR );
		$pdf->SetAuthor( $author );
		$pdf->SetTitle( $title . get_option( 'blogname' ) );
		$pdf->SetSubject( strip_tags( get_the_category_list( ',', '', $post_id ) ) );
		$pdf->SetKeywords( $tags );
		
		// Set header data
		if ( mb_strlen( $title, 'UTF-8' ) < 42 ) {
			$header_title = $title;
		} else {
			$header_title = mb_substr( $title, 0, 42, 'UTF-8' ) . '...';
		}

		if ( $header_enable == 1 ) {
			if ( $logo_enable == 1 && $logo_file ) {
				$pdf->SetHeaderData( $logo_file, $logo_width, $header_title, 'by ' . $author . ' - ' . $permalink );
			} else {
				$pdf->SetHeaderData( '', 0, $header_title, 'by ' . $author . ' - ' . $permalink );
			}
		}
		
		// Set header and footer fonts
		if ( $header_enable == 1 ) {
			$pdf->setHeaderFont( Array($font, '', PDF_FONT_SIZE_MAIN) );
		}

		if ( $footer_enable == 1 ) {
			$pdf->setFooterFont( Array($font, '', PDF_FONT_SIZE_DATA) );
		}
		
		// Remove header/footer
		if ( $header_enable == 0 ) {
			$pdf->setPrintHeader( false );
		}

		if ( $header_enable == 0 ) {
			$pdf->setPrintFooter( false );
		}
		
		// Set default monospaced font
		$pdf->SetDefaultMonospacedFont( $monospaced_font );
		
		// Set margins
		//$pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
		$pdf->SetMargins( $tcpdf_params['margins']['left'], $tcpdf_params['margins']['top'], $tcpdf_params['margins']['right'] );

		if ( $header_enable == 1 ) {
			$pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
		}

		if ( $footer_enable == 1 ) {
			$pdf->SetFooterMargin( PDF_MARGIN_FOOTER );
		}
		
		// Set auto page breaks
		//$pdf->SetAutoPageBreak( true, PDF_MARGIN_BOTTOM );
		$pdf->SetAutoPageBreak( true, $tcpdf_params['margins']['bottom'] );
				
		// Set image scale factor
		$pdf->setImageScale( $ratio );
		
		// Set some language-dependent strings
		$pdf->setLanguageArray( $l );
		
		// Set fontsubsetting mode
		$pdf->setFontSubsetting( $subsetting );
		
		// Set font
		$pdf->SetFont( $font, '', $font_size, true );
		
		// Add a page
		$pdf->AddPage();
		
		// Added to let external manipulate the $pdf instance. 
		// @since 2.4.7
		do_action( 'learndash_certification_after', $pdf, $post_id );
		
		// Create post content to print
		if ( $wrap_title == 1 ) {
			if ( mb_strlen( $title, 'UTF-8' ) < 33 ) {
				$title = $title;
			} else {
				$title = mb_substr( $title, 0, 33, 'UTF-8' ) . '<br />' . mb_substr( $title, 33, 222, 'UTF-8' );
			}
		}
		
		// Parse shortcode after applied WP default filters
		if ( $shortcode == 'parse' && $get_by_http_request != 1 ) {
			
			// For WP QuickLaTeX
			if ( function_exists( 'quicklatex_parser' ) ) {
				$content = quicklatex_parser( $content );
			}
			
			// For WP shortcode API
			$content = do_shortcode( $content );
		} else if ( $get_by_http_request != 1 ) {
			
			// For WP shortcode API
			$content = strip_shortcodes( $content );
		}
		
		// Convert relative image path to absolute image path
		$content = preg_replace( "/<img([^>]*?)src=['\"]((?!(http:\/\/|https:\/\/|\/))[^'\"]+?)['\"]([^>]*?)>/i", '<img$1src="' . site_url() . '/$2"$4>', $content );
		
		// Set image align to center
		$content = preg_replace_callback( "/(<img[^>]*?class=['\"][^'\"]*?aligncenter[^'\"]*?['\"][^>]*?>)/i", 'post2pdf_conv_image_align_center', $content );
		
		// Add width and height into image tag
		$content = preg_replace_callback( "/(<img[^>]*?src=['\"]((http:\/\/|https:\/\/|\/)[^'\"]*?(jpg|jpeg|gif|png))['\"])([^>]*?>)/i", 'post2pdf_conv_img_size', $content );
		
		// For WP QuickLaTeX
		if ( function_exists( 'quicklatex_parser' ) ) {
			$content = preg_replace_callback( '/(<p class="ql-(center|left|right)-displayed-equation" style="line-height: )([0-9]+?)(px;)(">)/i', array($this, post2pdf_conv_qlatex_displayed_equation), $content );
			$content = str_replace( '<p class="ql-center-picture">', '<p class="ql-center-picture" style="text-align: center;"><span class="ql-right-eqno"> &nbsp; <\/span><span class="ql-left-eqno"> &nbsp; <\/span>', $content );
		}
		
		// For common SyntaxHighlighter
		$content = preg_replace( "/<pre[^>]*?class=['\"][^'\"]*?brush:[^'\"]*?['\"][^>]*?>(.*?)<\/pre>/is", '<pre style="word-wrap:break-word; color: #406040; background-color: #F1F1F1; border: 1px solid #9F9F9F;">$1</pre>', $content );
		$content = preg_replace( "/<script[^>]*?type=['\"]syntaxhighlighter['\"][^>]*?>(.*?)<\/script>/is", '<pre style="word-wrap:break-word; color: #406040; background-color: #F1F1F1; border: 1px solid #9F9F9F;">$1</pre>', $content );
		$content = preg_replace( "/<pre[^>]*?name=['\"]code['\"][^>]*?>(.*?)<\/pre>/is", '<pre style="word-wrap:break-word; color: #406040; background-color: #F1F1F1; border: 1px solid #9F9F9F;">$1</pre>', $content );
		$content = preg_replace( "/<textarea[^>]*?name=['\"]code['\"][^>]*?>(.*?)<\/textarea>/is", '<pre style="word-wrap:break-word; color: #406040; background-color: #F1F1F1; border: 1px solid #9F9F9F;">$1</pre>', $content );
		$content = preg_replace( '/\n/', '<br/>', $content ); //"\n" should be treated as a next line
		
		// For WP-SynHighlight(GeSHi)
		if ( function_exists( 'wp_synhighlight_settings' ) ) {
			$content = preg_replace( "/<pre[^>]*?class=['\"][^>]*?>(.*?)<\/pre>/is", '<pre style="word-wrap:break-word; color: #406040; background-color: #F1F1F1; border: 1px solid #9F9F9F;">$1</pre>', $content );
			$content = preg_replace( '|<div[^>]*?class="wp-synhighlighter-outer"><div[^>]*?class="wp-synhighlighter-expanded"><table[^>]*?><tr><td[^>]*?><a[^>]*?></a><a[^>]*?class="wp-synhighlighter-title"[^>]*?>[^<]*?</a></td><td[^>]*?><a[^>]*?><img[^>]*?/></a>[^<]*?<a[^>]*?><img[^>]*?/></a>[^<]*?<a[^>]*?><img[^>]*?/></a>[^<]*?</td></tr></table></div>|is', '', $content );
		}
		
		// For other sourcecode
		$content = preg_replace( '/<pre[^>]*?><code[^>]*?>(.*?)<\/code><\/pre>/is', '<pre style="word-wrap:break-word; color: #406040; background-color: #F1F1F1; border: 1px solid #9F9F9F;">$1</pre>', $content );
		
		// For blockquote
		$content = preg_replace( '/<blockquote[^>]*?>(.*?)<\/blockquote>/is', '<blockquote style="color: #406040;">$1</blockquote>', $content );
		
		// Combine title with content
		$formatted_title = '<h1 style="text-align:center;">' . $title . '</h1>';
		
		//$formatted_post = $formatted_title . '<br/><br/>' . $content;    (Title will not appear on PDF)
		$formatted_post = '<br/><br/>' . $content;
		$formatted_post = preg_replace( '/(<[^>]*?font-family[^:]*?:)([^;]*?;[^>]*?>)/is', '$1' . $font . ',$2', $formatted_post );
		
		// get featured image
		$postid = get_the_id(); //Get current post id
		$img_file = learndash_get_thumb_path( $postid ); //The same function from theme's[twentytwelve here] function.php

		//Only print image if it exists
		if ( $img_file != '' ) {
			
			//Print BG image
			$pdf->setPrintHeader( false );
			
			// get the current page break margin
			$bMargin = $pdf->getBreakMargin();
			
			// get current auto-page-break mode
			$auto_page_break = $pdf->getAutoPageBreak();
			
			// disable auto-page-break
			$pdf->SetAutoPageBreak( false, 0 );
			
			// Get width and height of page for dynamic adjustments
			$pageH = $pdf->getPageHeight();
			$pageW = $pdf->getPageWidth();
			
			//Print the Background
			$pdf->Image( $img_file, $x = '0', $y = '0', $w = $pageW, $h = $pageH, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false, $fitonpage = false, $alt = false, $altimgs = array() );
			
			// restore auto-page-break status
			$pdf->SetAutoPageBreak( $auto_page_break, $bMargin );
			
			// set the starting point for the page content
			$pdf->setPageMark();
		}
		
		// Print post
		$pdf->writeHTMLCell( $w = 0, $h = 0, $x = '', $y = '', $formatted_post, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true );
		
		// Set background
		$pdf->SetFillColor( 255, 255, 127 );
		$pdf->setCellPaddings( 0, 0, 0, 0 );
		// Print signature

		ob_clean();
		
		// Output pdf document
		$pdf->Output( $filename . '.pdf', $destination );

		if ( $target_post_id != 0 ) {
			wp_die( wp_kses_post( __( '<strong>Generating completed successfully.</strong><br /><br />Post/Page title: ', 'learndash' ) ) . $title . wp_kses_post( __( '<br />Output path: ', 'learndash' ) ) . WP_CONTENT_DIR . '/tcpdf-pdf/' . $target_post_id . '.pdf' . wp_kses_post( __( '<br /><br />Go back to ', 'learndash' ) ) . '<a href="' . site_url() . '/wp-admin/options-general.php?page=post2pdf-converter-options">' . wp_kses_post( __( 'the setting panel</a>.', 'learndash' ) ), esc_html__( 'POST2PDF Converter', 'learndash' ) );
		}
	}
}
