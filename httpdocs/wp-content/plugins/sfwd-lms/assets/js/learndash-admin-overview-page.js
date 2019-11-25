if ( 'undefined' === typeof window.learndash ) {

	/**
	 * @namespace learndash
	 */
	window.learndash = {};
}

if ( 'undefined' === typeof window.learndash.admin ) {

	/**
	 * @namespace learndash.admin
	 */
	window.learndash.admin = {};
}

/**
 * @namespace learndash.admin.overview
 */
window.learndash.admin.overview = {

	toggleAccordion: function( e ) {
		if ( 'BUTTON' === e.target.tagName && e.target.classList.contains( 'ld-bootcamp__accordion--toggle' ) ||
			'SPAN' === e.target.tagName && e.target.classList.contains( 'ld-bootcamp__accordion--toggle-indicator' ) ) {
			e.preventDefault();

			var parentAccordionItem = e.target.closest( '.ld-bootcamp__accordion--single' );
			if ( ! parentAccordionItem ) {
				return;
			}

			var toggle = parentAccordionItem.querySelector( '.ld-bootcamp__accordion--toggle' );
			if ( ! toggle ) {
				return;
			}

			var ariaSelected = ( 'true' === toggle.getAttribute( 'aria-selected' ) );
			var accordionContent = parentAccordionItem.querySelector( '.ld-bootcamp__accordion--content' );

			toggle.setAttribute( 'aria-selected', ! ariaSelected );
			toggle.setAttribute( 'aria-expanded', ! ariaSelected );
			accordionContent.setAttribute( 'aria-hidden', ariaSelected );

			if ( 'true' === toggle.getAttribute( 'aria-selected' ) ) {
				this.maybeLoadVideos( parentAccordionItem );
			}
		}
	},
	openFirstIncompleteAccordionPanel() {
		var accordionPanels = document.querySelectorAll( '.ld-bootcamp__accordion--single' );
		var incompletePanels = [];
		accordionPanels.forEach( function( panel ) {
			if ( panel.classList.contains( '-completed' ) ) {
				// Change button text.
				var markCompleteButton = panel.querySelector('.ld-bootcamp__mark-complete--toggle' );
				if ( markCompleteButton && markCompleteButton.innerHTML !== LearnDashOverviewPageData['mark_incomplete'] ) {
					markCompleteButton.innerHTML = LearnDashOverviewPageData['mark_incomplete'];
				};
				return;
			}
			incompletePanels.push( panel );
		});

		var toggle = incompletePanels[0].querySelector('.ld-bootcamp__accordion--toggle');
		var accordionContent = incompletePanels[0].querySelector( '.ld-bootcamp__accordion--content' );

		if ( ! toggle || ! accordionContent ) {
			return;
		}

		toggle.setAttribute( 'aria-selected', 'true' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		accordionContent.setAttribute( 'aria-hidden', 'false' );
		accordionContent.focus();

		this.maybeLoadVideos( incompletePanels[0] );
	},
	maybeLoadVideos: function( accordion ) {
		var iframes = accordion.querySelectorAll( '.ld-bootcamp__embed iframe' );

		if ( ! iframes ) {
			return;
		}

		iframes.forEach( function( iframe ) {
			if ( ! iframe.dataset.src ) {
				return;
			}
			iframe.setAttribute( 'src', iframe.dataset.src );
		});
	},
	toggleBootcamp: function( e ) {
		if ( 'BUTTON' === e.target.tagName && e.target.classList.contains( 'ld-bootcamp--toggle' ) ) {
			e.preventDefault();

			var divToToggle = document.querySelector( '.ld-bootcamp' );
			var showBootcampButton = document.getElementById( 'ld-bootcamp--show' );

			if ( divToToggle && showBootcampButton ) {
				if ( e.target.id === 'ld-bootcamp--hide' ) {
					divToToggle.style.display = 'none';
					showBootcampButton.style.display = 'block';
				}

				if ( e.target.id === 'ld-bootcamp--show' ) {
					divToToggle.style.display = 'block';
					showBootcampButton.style.display = 'none';
				}

				this.saveToggleBootcampState( e, divToToggle );
				scroll( 0, 0 );
			}
		}
	},
	saveToggleBootcampState: function( e, toggle ) {
		if ( ! toggle ) {
			return;
		}
		var toggleState = toggle.style.display === 'none' ? 'hide' : 'show';
		var ajaxRequest = new XMLHttpRequest();
		ajaxRequest.open('POST', window.ajaxurl, true);
		ajaxRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		ajaxRequest.onerror = function() {
			console.log( 'Ajax request failed.' );
		};
		ajaxRequest.send( 'action=save_bootcamp_toggle_state&nonce=' + e.target.dataset.nonce + '&state=' + toggleState );
	},
	markComplete: function( e ) {
			if ( 'BUTTON' === e.target.tagName && ( e.target.classList.contains( 'ld-bootcamp__mark-complete--toggle' ) || e.target.classList.contains( 'ld-bootcamp__mark-complete--toggle-indicator' ) ) ) {
				e.preventDefault();

				var classToToggle = e.target.closest( '.ld-bootcamp__accordion--single' );
				if ( classToToggle ) {
					classToToggle.classList.contains( '-completed' ) ?
					classToToggle.classList.remove( '-completed' ) :
					classToToggle.classList.add( '-completed' );
				}

				// Change button text.
				markCompleteButton = classToToggle.querySelector( '.ld-bootcamp__mark-complete--toggle' );
				LearnDashOverviewPageData['mark_complete'] === markCompleteButton.innerHTML ?
						markCompleteButton.innerHTML = LearnDashOverviewPageData['mark_incomplete'] :
						markCompleteButton.innerHTML = LearnDashOverviewPageData['mark_complete'];

				this.saveMarkCompleteState( e.target, classToToggle.classList.contains( '-completed' ) );

				// Collapse the section when it is marked complete.
				if ( classToToggle.classList.contains( '-completed' ) ) {
					this.collapseAccordion( e );
					this.openFirstIncompleteAccordionPanel();
				}
			}
	},
	saveMarkCompleteState: function( section, state ) {
		var ajaxRequest = new XMLHttpRequest();
		ajaxRequest.open( 'POST', window.ajaxurl, true );
		ajaxRequest.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );
		ajaxRequest.onerror = function() {
			console.log( 'Ajax request failed.' );
		};
		ajaxRequest.send( 'action=save_bootcamp_mark_complete_state&nonce=' + section.dataset.nonce + '&id=' + section.dataset.id + '&state=' + state );
	},
	collapseAccordion: function( e ) {
		var parentAccordionItem = e.target.closest( '.ld-bootcamp__accordion--single' );
		if  ( ! parentAccordionItem ) {
			return;
		}

		var toggle = parentAccordionItem.querySelector( '.ld-bootcamp__accordion--toggle' );
		if ( ! toggle ) {
			return;
		}

		// Prevent unnecesary DOM manipulation.
		if ( 'false' === toggle.getAttribute( 'aria-selected' ) ) {
			return;
		}

		var accordionContent = parentAccordionItem.querySelector( '.ld-bootcamp__accordion--content' );
		toggle.setAttribute( 'aria-selected', 'false' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		accordionContent.setAttribute( 'aria-hidden', 'true' );
	},
	maybeDisplayShowBootcampButton: function() {
		var showBootcampButton = document.getElementById( 'ld-bootcamp--show' );
		var bootcampVisibility = document.querySelector( '.ld-bootcamp' );

		if ( showBootcampButton && bootcampVisibility ) {
			bootcampVisibility.style.display === 'block' ? showBootcampButton.style.display = 'none' : showBootcampButton.style.display = 'block';
		}
	},
	searchSupportSite: function( e ) {
		if ( 'FORM' === e.target.tagName && e.target.id === 'ld-overview--search-documentation-form' ) {
			e.preventDefault();

			var searchTerm = document.getElementById( 'ld-overview--search-term' );
			if ( searchTerm && searchTerm.value.length > 3 ) {
				window.open( 'https://www.learndash.com/support/docs/?s=' + searchTerm.value, '_blank' );
			}
		}
	},
	eventListeners: function() {
			document.querySelector( '.ld-bootcamp' ).addEventListener( 'click', learndash.admin.overview.toggleAccordion.bind( learndash.admin.overview ) );
			document.querySelector( 'body' ).addEventListener( 'click', learndash.admin.overview.toggleBootcamp.bind( learndash.admin.overview ) );
			document.querySelector( 'body' ).addEventListener( 'click', learndash.admin.overview.markComplete.bind( learndash.admin.overview ) );
			document.querySelector( 'body' ).addEventListener( 'submit', learndash.admin.overview.searchSupportSite.bind( learndash.admin.overview ) );
	}
};

document.addEventListener( 'DOMContentLoaded', learndash.admin.overview.eventListeners );
document.addEventListener( 'DOMContentLoaded', learndash.admin.overview.openFirstIncompleteAccordionPanel.bind( learndash.admin.overview ) );
document.addEventListener( 'DOMContentLoaded', learndash.admin.overview.maybeDisplayShowBootcampButton.bind( learndash.admin.overview ) );
