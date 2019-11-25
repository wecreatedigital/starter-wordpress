<?php
//============================================================+
// File name   : tcpdf_config.php
// Begin       : 2004-06-11
// Last Update : 2012-2-1 by redcocker
//
// Description : Configuration file for TCPDF.
// Author      : Nicola Asuni - Tecnick.com LTD - Manor Coach House, Church Hill, Aldershot, Hants, GU12 4RQ, UK - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2004-2012  Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with TCPDF.  If not, see <http://www.gnu.org/licenses/>.
//
// See LICENSE.TXT file for more information.
//============================================================+

/**
 * Configuration file for TCPDF.
 * @author Nicola Asuni
 * @package com.tecnick.tcpdf
 * @version 4.9.005
 * @since 2004-10-27
 */

// Deny access to this file directly Add by redcocker 2011/12/27
if (!empty($_SERVER['SCRIPT_FILENAME']) && 'tcpdf_config.php' == basename($_SERVER['SCRIPT_FILENAME']))
	wp_die(__("You are not allowed to access this file.", "learndash"));

// If you define the constant K_TCPDF_EXTERNAL_CONFIG, the following settings will be ignored.

if ( ! defined( 'K_TCPDF_EXTERNAL_CONFIG' ) ) {

	if ( ! defined( 'K_PATH_MAIN' ) ) {
		// DOCUMENT_ROOT fix for IIS Webserver.
		if ( ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) ) || ( empty( $_SERVER['DOCUMENT_ROOT'] ) ) ) {
			if ( isset( $_SERVER['SCRIPT_FILENAME'] ) ) {
				$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr( $_SERVER['SCRIPT_FILENAME'], 0, 0-strlen( $_SERVER['PHP_SELF'] ) ) );
			} else if( isset( $_SERVER['PATH_TRANSLATED'] ) ) {
				$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr( str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED'] ), 0, 0-strlen( $_SERVER['PHP_SELF'] ) ) );
			} else {
				// Define here your DOCUMENT_ROOT path if the previous fails (e.g. '/var/www')
				$_SERVER['DOCUMENT_ROOT'] = '/';
			}
		}

		// Automatic calculation for the following K_PATH_MAIN constant.
		$k_path_main = str_replace( '\\', '/', realpath( substr( dirname( __FILE__ ), 0, 0-strlen( 'config' ) ) ) );
		if ( substr( $k_path_main, -1 ) != '/' ) {
			$k_path_main .= '/';
		}

		/**
		 * Installation path (/var/www/tcpdf/).
		 * By default it is automatically calculated but you can also set it as a fixed string to improve performances.
		 */
		define( 'K_PATH_MAIN', $k_path_main );
	} else {
		$k_path_main = K_PATH_MAIN;
	}

	if ( ! defined( 'K_PATH_URL' ) ) {
		// Automatic calculation for the following K_PATH_URL constant.
		$k_path_url = $k_path_main; // default value for console mode.
		if ( isset( $_SERVER['HTTP_HOST'] ) && ( ! empty( $_SERVER['HTTP_HOST'] ) ) ) {
			if ( isset( $_SERVER['HTTPS'] ) && ( ! empty( $_SERVER['HTTPS'] ) ) && strtolower( $_SERVER['HTTPS'] ) != 'off' ) {
				$k_path_url = 'https://';
			} else {
				$k_path_url = 'http://';
			}
			$k_path_url .= $_SERVER['HTTP_HOST'];
			$k_path_url .= str_replace( '\\', '/', substr( K_PATH_MAIN, ( strlen( $_SERVER['DOCUMENT_ROOT'] ) - 1 ) ) );
		}

		/**
		 * URL path to tcpdf installation folder (http://localhost/tcpdf/).
		 * By default it is automatically calculated but you can also set it as a fixed string to improve performances.
		 */
		define( 'K_PATH_URL', $k_path_url );
	} else {
		$k_path_url = 'K_PATH_URL';
	}

	$post2pdf_conv_setting_opt = get_option( 'post2pdf_conv_setting_opt', array() );

	/**
	 * Path for PDF fonts
	 * use K_PATH_MAIN.'fonts/old/' for old non-UTF8 fonts
	 */
	if ( ! defined( 'K_PATH_FONTS' ) ) {
		if ( ( isset( $post2pdf_conv_setting_opt['font_path'] ) ) && ( $post2pdf_conv_setting_opt['font_path'] == 1 ) ) {
			define( 'K_PATH_FONTS', WP_CONTENT_DIR . '/tcpdf-fonts/' );
		} else {
			define( 'K_PATH_FONTS', K_PATH_MAIN . 'fonts/' );
		}
	}

	/**
	 * Cache directory for temporary files (full path)
	 */
	if ( ! defined( 'K_PATH_CACHE' ) ) {
		define( 'K_PATH_CACHE', K_PATH_MAIN . 'cache/' );
	}

	/**
	 * Cache directory for temporary files (url path)
	 */
	if ( ! defined( 'K_PATH_URL_CACHE' ) ) {
		define( 'K_PATH_URL_CACHE', K_PATH_URL . 'cache/' );
	}

	/**
	 * Images directory
	 */
	if ( ! defined( 'K_PATH_IMAGES' ) ) {
		if ( ( isset( $post2pdf_conv_setting_opt['logo_file'] ) ) && ( ! empty( $post2pdf_conv_setting_opt['logo_file'] ) ) && ( file_exists( WP_CONTENT_DIR . '/tcpdf-images/' . $post2pdf_conv_setting_opt['logo_file'] ) ) ) {
			define( 'K_PATH_IMAGES', WP_CONTENT_DIR . '/tcpdf-images/' );
		} else {
			define( 'K_PATH_IMAGES', K_PATH_MAIN . 'images/' );
		}
	}

	/**
	 * Blank image
	 */
	if ( ! defined( 'K_BLANK_IMAGE' ) ) {
		define( 'K_BLANK_IMAGE', K_PATH_IMAGES . '_blank.png' );
	}

	/**
	 * Page format
	 */
	if ( ! defined( 'PDF_PAGE_FORMAT' ) ) {
		define( 'PDF_PAGE_FORMAT', 'LETTER' );
	}

	/**
	 * Page orientation (P=portrait, L=landscape)
	 */
	if ( ! defined( 'PDF_PAGE_ORIENTATION' ) ) {
		define( 'PDF_PAGE_ORIENTATION', 'L' );
	}

	/**
	 * Document creator
	 */
	if ( ! defined( 'PDF_CREATOR' ) ) {
		define( 'PDF_CREATOR', 'TCPDF' );
	}

	/**
	 * Document author
	 */
	if ( ! defined( 'PDF_AUTHOR' ) ) {
		define( 'PDF_AUTHOR', 'TCPDF' );
	}

	/**
	 * Header title
	 */
	if ( ! defined( 'PDF_HEADER_TITLE' ) ) {
		define( 'PDF_HEADER_TITLE', 'TCPDF Example' );
	}

	/**
	 * Header description string
	 */
	if ( ! defined( 'PDF_HEADER_STRING' ) ) {
		define( 'PDF_HEADER_STRING', 'by Nicola Asuni - Tecnick.com\nwww.tcpdf.org' );
	}

	/**
	 * Image logo
	 */
	if ( ! defined( 'PDF_HEADER_LOGO' ) ) {
		define( 'PDF_HEADER_LOGO', 'tcpdf_logo.jpg' );
	}

	/**
	 * Header logo image width [mm]
	 */
	if ( ! defined( 'PDF_HEADER_LOGO_WIDTH' ) ) {
		define( 'PDF_HEADER_LOGO_WIDTH', 30 );
	}

	/**
	 *  Document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
	 */
	if ( ! defined( 'PDF_UNIT' ) ) {
		define( 'PDF_UNIT', 'mm' );
	}

	/**
	 * Header margin
	 */
	if ( ! defined( 'PDF_MARGIN_HEADER' ) ) {
		define( 'PDF_MARGIN_HEADER', 5 );
	}

	/**
	 * Footer margin
	 */
	if ( ! defined( 'PDF_MARGIN_FOOTER' ) ) {
		define( 'PDF_MARGIN_FOOTER', 10 );
	}

	/**
	 * Top margin
	 */
	if ( ! defined( 'PDF_MARGIN_TOP' ) ) {
		define( 'PDF_MARGIN_TOP', 27 );
	}

	/**
	 * Bottom margin
	 */
	if ( ! defined( 'PDF_MARGIN_BOTTOM' ) ) {
		define( 'PDF_MARGIN_BOTTOM', 25 );
	}

	/**
	 * Left margin
	 */
	if ( ! defined( 'PDF_MARGIN_LEFT' ) ) {
		define( 'PDF_MARGIN_LEFT', 15 );
	}

	/**
	 * Right margin
	 */
	if ( ! defined( 'PDF_MARGIN_RIGHT' ) ) {
		define( 'PDF_MARGIN_RIGHT', 15 );
	}

	/**
	 * Default main font name
	 */
	if ( ! defined( 'PDF_FONT_NAME_MAIN' ) ) {
		define( 'PDF_FONT_NAME_MAIN', 'helvetica' );
	}

	/**
	 * Default main font size
	 */
	if ( ! defined( 'PDF_FONT_SIZE_MAIN' ) ) {
		define( 'PDF_FONT_SIZE_MAIN', 10 );
	}

	/**
	 * Default data font name
	 */
	if ( ! defined( 'PDF_FONT_NAME_DATA' ) ) {
		define( 'PDF_FONT_NAME_DATA', 'helvetica' );
	}

	/**
	 * Default data font size
	 */
	if ( ! defined( 'PDF_FONT_SIZE_DATA' ) ) {
		define( 'PDF_FONT_SIZE_DATA', 8 );
	}

	/**
	 * Default monospaced font name
	 */
	if ( ! defined( 'PDF_FONT_MONOSPACED' ) ) {
		define( 'PDF_FONT_MONOSPACED', 'courier' );
	}

	/**
	 * Ratio used to adjust the conversion of pixels to user units
	 */
	if ( ! defined( 'PDF_IMAGE_SCALE_RATIO' ) ) {
		define( 'PDF_IMAGE_SCALE_RATIO', 1.25 );
	}

	/**
	 * Magnification factor for titles
	 */
	if ( ! defined( 'HEAD_MAGNIFICATION' ) ) {
		define( 'HEAD_MAGNIFICATION', 1.1 );
	}

	/**
	 * Height of cell repect font height
	 */
	if ( ! defined( 'K_CELL_HEIGHT_RATIO' ) ) {
		define( 'K_CELL_HEIGHT_RATIO', 1.25 );
	}

	/**
	 * Title magnification respect main font size
	 */
	if ( ! defined( 'K_TITLE_MAGNIFICATION' ) ) {
		define( 'K_TITLE_MAGNIFICATION', 1.3 );
	}

	/**
	 * Reduction factor for small font
	 */
	if ( ! defined( 'K_SMALL_RATIO' ) ) {
		define( 'K_SMALL_RATIO', 2/3 );
	}

	/**
	 * Set to true to enable the special procedure used to avoid the overlappind of symbols on Thai language
	 */
	if ( ! defined( 'K_THAI_TOPCHARS' ) ) {
		define( 'K_THAI_TOPCHARS', true );
	}

	/**
	 * If true allows to call TCPDF methods using HTML syntax.
	 * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
	 */
	if ( ! defined( 'K_TCPDF_CALLS_IN_HTML' ) ) {
		define( 'K_TCPDF_CALLS_IN_HTML', true );
	}
}

//============================================================+
// END OF FILE
//============================================================+
