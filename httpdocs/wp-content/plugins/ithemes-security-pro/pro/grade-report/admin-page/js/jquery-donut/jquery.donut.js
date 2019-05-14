(function ($) {
	$.fn.donut = function() {
		return this.each(function() {
			var ctx = this.getContext('2d');
			var segments = [];

			$(this).children().map(function () {
				var v = $(this).data('value');
				var f = parseFloat(v);

				if (!isNaN(f)) {
					var grade = $(this).data('grade');
					var color = $('.itsec-card-security-score .itsec-letter-grade-legend .itsec-grade-' + grade).css('color');

					segments.push({percent: f, color: color});
				}
			});


			var canvasWidth = this.width;
			var canvasHeight = this.height;
			var xCenter = Math.floor(canvasWidth / 2);
			var yCenter = Math.floor(canvasHeight / 2);
			var radius = Math.floor(Math.min(canvasWidth, canvasHeight) / 2);
			var innerRadius = radius - 40;

			//Reset the canvas
			ctx.clearRect(0, 0, canvasWidth, canvasHeight);
			ctx.restore();
			ctx.save();


			var i,
				total = 0;

			for (i = 0; i < segments.length; i++) {
				total = total + parseFloat(segments[i].percent);
			}

			var percentByDegree = 360 / total,
				degToRad = Math.PI / 180,
				currentAngle = 235,
				startAngle = 235,
				endAngle,
				innerStart,
				innerEnd;

			ctx.translate(xCenter, yCenter);
			//Turn the chart around so the segments start from 12 o'clock
			ctx.rotate(270 * degToRad);

			for (i = 0; i < segments.length; i++) {
				startAngle = currentAngle * degToRad;
				endAngle = (currentAngle + (segments[i].percent * percentByDegree)) * degToRad;

				ctx.beginPath();
				ctx.strokeStyle = '#444';
				ctx.arc(0, 0, radius - 1, startAngle, endAngle, false);
				ctx.arc(0, 0, innerRadius + 1, endAngle, startAngle, true);
				ctx.closePath();
				ctx.stroke();

				ctx.beginPath();
				ctx.fillStyle = segments[i].color;
				ctx.arc(0, 0, radius, startAngle, endAngle, false);
				ctx.arc(0, 0, innerRadius, endAngle, startAngle, true);
				ctx.closePath();
				ctx.fill();

				currentAngle = currentAngle + (segments[i].percent * percentByDegree);
			}
		});
	};
} (jQuery));
