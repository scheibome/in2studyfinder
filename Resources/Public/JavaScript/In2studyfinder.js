/**
 * In2studyfinder module
 *
 * @module In2studyfinder
 */
(function In2studyfinder() {

	/**
	 * @type {*|jQuery|HTMLElement}
	 */
	var dom = $('.in2studyfinder');
	var readFilterFromUrl = false;

	function Start() {

		/**
		 * initialize stuff
		 */
		this.Init = function() {

			var chosen = new window.In2studyfinder.Chosen(dom);
			var filterHandling = new window.In2studyfinder.FilterHandling(dom);
			var urlHandling = new window.In2studyfinder.UrlHandling(dom);
			var uiBehaviour = new window.In2studyfinder.UiBehaviour(dom);

			chosen.initializeChosen();
			filterHandling.init();
			uiBehaviour.checkboxHandling();

			if (readFilterFromUrl) {
				readFilterFromUrl = false;
				var paginationPage = urlHandling.loadSelectedOptionsFromUrl();
				filterHandling.filterChanged(paginationPage);
			}
		};
	}

	// export to global scope
	if (!window.In2studyfinder) {
		window.In2studyfinder = {};
	}

	window.In2studyfinder.Start = Start;
})();

jQuery(document).ready(function() {
	var start = new window.In2studyfinder.Start();
	start.Init();
});
