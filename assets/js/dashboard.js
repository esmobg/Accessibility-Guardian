/**
 * Accessibility Guardian - dashboard enhancements.
 *
 * Renders a lightweight inline SVG sparkline for the score history without
 * pulling in an external charting library.
 */
( function () {
	'use strict';

	function renderTrend( container ) {
		var raw = container.getAttribute( 'data-history' );
		if ( ! raw ) {
			return;
		}

		var points;
		try {
			points = JSON.parse( raw );
		} catch ( e ) {
			return;
		}

		if ( ! points || points.length === 0 ) {
			container.textContent = container.getAttribute( 'data-empty' ) || 'No history yet.';
			return;
		}

		var width = 100;
		var height = 40;
		var max = 100;
		var step = points.length > 1 ? width / ( points.length - 1 ) : width;

		var coords = points.map( function ( point, index ) {
			var x = index * step;
			var y = height - ( ( point.score / max ) * height );
			return x.toFixed( 1 ) + ',' + y.toFixed( 1 );
		} );

		var svgNs = 'http://www.w3.org/2000/svg';
		var svg = document.createElementNS( svgNs, 'svg' );
		svg.setAttribute( 'viewBox', '0 0 ' + width + ' ' + height );
		svg.setAttribute( 'preserveAspectRatio', 'none' );
		svg.setAttribute( 'class', 'ag-trend__svg' );
		svg.setAttribute( 'role', 'img' );

		var last = points[ points.length - 1 ];
		svg.setAttribute( 'aria-label', 'Latest accessibility score ' + last.score + ' out of 100' );

		var polyline = document.createElementNS( svgNs, 'polyline' );
		polyline.setAttribute( 'points', coords.join( ' ' ) );
		polyline.setAttribute( 'fill', 'none' );
		polyline.setAttribute( 'stroke', 'currentColor' );
		polyline.setAttribute( 'stroke-width', '2' );
		polyline.setAttribute( 'vector-effect', 'non-scaling-stroke' );

		svg.appendChild( polyline );
		container.appendChild( svg );

		var caption = document.createElement( 'p' );
		caption.className = 'ag-trend__caption';
		caption.textContent = last.score + ' / 100';
		container.appendChild( caption );
	}

	function init() {
		var trend = document.getElementById( 'ag-trend' );
		if ( trend ) {
			renderTrend( trend );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
