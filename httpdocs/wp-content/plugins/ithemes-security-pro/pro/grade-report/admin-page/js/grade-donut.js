"use strict";

var itsecGradeDonut = {
	radiusOuter: 1.0,
	radiusInner: 0.6,

	draw: function( svgID, slicesDataID ) {
		var svg = document.getElementById( svgID );
		var slices = [];
		var total = 0;

		jQuery('#' + slicesDataID).children().map( function () {
			var v = jQuery(this).data( 'value' );
			var f = parseFloat( v );

			if ( ! isNaN( f ) ) {
				var grade = jQuery(this).data( 'grade' );
				var color = jQuery('.itsec-card-security-score .itsec-letter-grade-legend .itsec-grade-' + grade).css( 'color' );

				slices.push({value: f, color: color});
				total += f;
			}
		} );

		var currentPercent = 0.65;

		slices.forEach( function( slice ) {
			var path = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
			var percent = slice.value / total;

			path.setAttribute( 'class', 'slice' );
			path.setAttribute( 'd', itsecGradeDonut.getSlicePathData( currentPercent, currentPercent + percent ) );
			path.setAttribute( 'fill', slice.color );
			svg.appendChild( path );

			currentPercent += percent;
		} );
	},

	getCoords: function( percent, radius ) {
		var x = radius * Math.cos( 2 * Math.PI * percent );
		var y = radius * Math.sin( 2 * Math.PI * percent );
		return {x: x, y: y};
	},

	pathMove: function( path, percent, radius ) {
		var coords = this.getCoords( percent, radius );
		path.data.push( 'M ' + coords.x + ' ' + coords.y );
		path.curPercent = percent;
	},

	pathArc: function( path, percent, radius ) {
		var distance = Math.abs( percent - path.curPercent );

		if ( distance > 0.5 ) {
			if ( radius < 1.0 ) {
				var largeArcFlag = 1;
				var sweepFlag = 0;
			} else {
				var largeArcFlag = 1;
				var sweepFlag = 1;
			}
		} else {
			if ( radius < 1.0 ) {
				var largeArcFlag = 0;
				var sweepFlag = 0;
			} else {
				var largeArcFlag = 0;
				var sweepFlag = 1;
			}
		}

		var coords = this.getCoords( percent, radius );

		path.data.push( 'A ' + radius + ' ' + radius + ' 0 ' + largeArcFlag + ' ' + sweepFlag + ' ' + coords.x + ' ' + coords.y );
		path.curPercent = percent;
	},

	pathLine: function( path, percent, radius ) {
		path.distance = 0;
		var coords = this.getCoords( percent, radius );
		path.data.push( 'L ' + coords.x + ' ' + coords.y );
		path.curPercent = percent;
	},

	getSlicePathData: function( startPercent, endPercent ) {
		var path = {
			data:       [],
			curPercent: 0,
		};

		this.pathMove( path, startPercent, this.radiusOuter );
		this.pathArc( path, endPercent, this.radiusOuter );
		this.pathLine( path, endPercent, this.radiusInner );
		this.pathArc( path, startPercent, this.radiusInner );
		this.pathLine( path, startPercent, this.radiusOuter );

		return path.data.join( ' ' );
	}
};
