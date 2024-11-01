(function ($) {

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	

	let tooltips = [
		{ label: 'Enable DNS Url for DNS Prefetch', tooltip: 'Enabling DNS Prefetch for URLs speeds up webpage loading by anticipating and caching domain name resolutions, enhancing overall browsing performance.' },
		{ label: 'Enable CDN', tooltip: 'Enabling a Content Delivery Network (CDN) accelerates website performance by distributing content across geographically dispersed servers, reducing latency and improving user experience.' },
		{ label: 'Enable JS minification', tooltip: 'Enabling JavaScript (JS) minification reduces file sizes by removing unnecessary characters, enhancing website loading speed and improving overall performance.' },
		{ label: 'Enable Cache', tooltip: 'Create HTML Cache of the website for faster load times.' },
		{ label: 'Enable Urls for deffering', tooltip: 'Defer loading of CSS and JavaScript files until after the initial HTML document is loaded.' },
		{ label: 'Add font url for preloading', tooltip: 'Fonts are used to display text on a web page. Preloading fonts can speed up the rendering of web pages.' },
		{ label: 'Convert all images to WebP', tooltip: 'Convert all images to WebP format to reduce the size of images and improve the page load time.' },
		{ label: 'Compress all images', tooltip: 'Compress all images to reduce the size of images, height and width will remain same. It improves the page load time.' },
		{ label: 'Resize all images', tooltip: 'Resize all images to reduce the size of images, height and width changes as per aspect ratio.It improve the page load time.' },
	]

	const pages = ['turboboost_frontend', 'my_turboboost_setting', 'turboboost_media', 'turboboost_cache']

	function update_settings_tooltips() {

		var currentUrl = window.location.href;
		console.log(currentUrl);
		// Display the URL in the console
		let urlParams = new URL (currentUrl);

		// Get the value of the 'page' parameter
		var pageValue = urlParams.searchParams.get('page');

		if(pages.includes(pageValue)) {
			console.log('pageValue: ', pageValue);
			wpTableThead = document.querySelectorAll('.form-table th');
			console.log('wpTableThead: ', wpTableThead);
			wpTableThead.forEach(th => {
				let tooltip = tooltips.find(tooltip => tooltip.label == th.innerText);
				console.log('tooltip: ', tooltip);
				if(tooltip) {
					th.innerHTML += tooltipHTML(tooltip.tooltip);
				}
			});
		}

		function tooltipHTML(tooltip) {
			return `<div class="turbo-tooltip-container">
					<div class="turbo-tooltip-icon"></div>
					<div class="turbo-tooltip-content">
						<p>${tooltip}</p>
					</div>
				</div>`;

		}
		//code here
		const nytestElements = document.querySelectorAll('.nytest');

  // Define the click event handler function
  function handleClick() {
    this.classList.toggle('nytest--active');
    // Add your additional logic here
  }
  
  // Add click event listener to each element with class 'nytest'
  nytestElements.forEach(element => {
    element.addEventListener('click', handleClick);
  });
		//
	}
	$(window).on('load', function() {
		update_settings_tooltips();
		
		console.log('needle: ');
		const rotateElements = document.querySelectorAll('.rotate');
		rotateElements.forEach(element => {
			const angle = element.dataset.angle || '0deg';
			element.style.setProperty('--angle', angle);
		});
	   });
	

})(jQuery);

var needle = $('needle');
var el = $('el');
console.log('needle: ', needle);
var measureDeg = function() {
  // matrix-to-degree conversion from https://css-tricks.com/get-value-of-css-rotation-through-javascript/
  var st = window.getComputedStyle(needle, null);
  var tr = st.getPropertyValue("-webkit-transform") ||
           st.getPropertyValue("-moz-transform") ||
           st.getPropertyValue("-ms-transform") ||
           st.getPropertyValue("-o-transform") ||
           st.getPropertyValue("transform") ||
           "fail...";

  var values = tr.split('(')[1];
      values = values.split(')')[0];
      values = values.split(',');
  var a = values[0];
  var b = values[1];
  var c = values[2];
  var d = values[3];

  var scale = Math.sqrt(a*a + b*b);

  // arc sin, convert from radians to degrees, round
  var sin = b/scale;
  var angle = Math.round(Math.atan2(b, a) * (180/Math.PI));
  
  el.set('data-value', angle);
};

var periodicalID = measureDeg.periodical(10);