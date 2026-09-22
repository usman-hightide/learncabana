/**
 * User Report org filters — Province + District Manager + Area Manager.
 * Ensures courses_overview REST calls include filter query params
 * (page URL alone is not enough — Tin Canny loads data via a separate AJAX URL).
 */
(function ($) {
	'use strict';

	var lastFetchKey = null;

	function readQueryParam(name) {
		var re = new RegExp('[?&]' + name + '=([^&]*)');
		var match = window.location.search.match(re);
		if (!match) {
			return '';
		}
		try {
			return decodeURIComponent(match[1].replace(/\+/g, ' '));
		} catch (e) {
			return match[1] || '';
		}
	}

	function cfg() {
		return window.lcProvinceReporting || {};
	}

	function getActiveTab() {
		var hidden = document.getElementById('reporting-group-selector-tab');
		if (hidden && hidden.value) {
			return String(hidden.value).replace(/^#/, '');
		}
		var match = window.location.search.match(/[?&]tab=([^&]*)/);
		if (match) {
			try {
				return decodeURIComponent(match[1]);
			} catch (e) {
				return match[1];
			}
		}
		return 'courseReportTab';
	}

	function isUserReportTab() {
		return getActiveTab() === 'userReportTab';
	}

	function normalizeId(val) {
		if (val === undefined || val === null) {
			return '';
		}
		val = String(val);
		if (!val || val === 'all' || val === '0') {
			return '';
		}
		return val;
	}

	function getSelectValue(id, configKey, urlKey) {
		var select = document.getElementById(id);
		if (select && select.value && select.value !== 'all') {
			return String(select.value);
		}
		if (window.reportingApiSetup) {
			var setupKey = {
				province: 'isolated_province',
				districtManager: 'isolated_district_manager',
				areaManager: 'isolated_area_manager'
			}[configKey];
			if (setupKey && reportingApiSetup[setupKey]) {
				var fromSetup = normalizeId(reportingApiSetup[setupKey]);
				if (fromSetup) {
					return fromSetup;
				}
			}
		}
		var fromConfig = normalizeId(cfg()[configKey]);
		if (fromConfig) {
			return fromConfig;
		}
		return normalizeId(readQueryParam(urlKey));
	}

	function getSelectedProvince() {
		return getSelectValue('reporting-province-selector', 'province', 'province');
	}

	function getSelectedDistrictManager() {
		return getSelectValue('reporting-district-manager-selector', 'districtManager', 'district_manager');
	}

	function getSelectedAreaManager() {
		return getSelectValue('reporting-area-manager-selector', 'areaManager', 'area_manager');
	}

	function getOrgFiltersForApi() {
		if (!isUserReportTab()) {
			return { province: '', district_manager: '', area_manager: '' };
		}
		return {
			province: getSelectedProvince(),
			district_manager: getSelectedDistrictManager(),
			area_manager: getSelectedAreaManager()
		};
	}

	function appendParam(path, key, value) {
		path = typeof path === 'string' ? path : '';
		if (!value) {
			return path;
		}
		// Replace existing value if present.
		var re = new RegExp('([?&])' + key + '=[^&]*');
		if (re.test(path)) {
			return path.replace(re, '$1' + key + '=' + encodeURIComponent(value));
		}
		var pair = key + '=' + encodeURIComponent(value);
		if (!path || path === '/') {
			return '/?' + pair;
		}
		if (path.indexOf('?') === -1) {
			return path.replace(/\/?$/, '/') + '?' + pair;
		}
		if (path.slice(-1) === '?' || path.slice(-1) === '&') {
			return path + pair;
		}
		return path + '&' + pair;
	}

	function stripOrgParams(path) {
		path = typeof path === 'string' ? path : '';
		return path
			.replace(/([?&])province=[^&]*/g, '$1')
			.replace(/([?&])district_manager=[^&]*/g, '$1')
			.replace(/([?&])area_manager=[^&]*/g, '$1')
			.replace(/([?&])tab=[^&]*/g, '$1')
			.replace(/[?&]$/, '')
			.replace(/\?&/, '?')
			.replace(/&&/g, '&');
	}

	/**
	 * Exposed for early inline bootstrap on reporting_js_handle.
	 */
	window.lcOrgAppendFilters = function (path) {
		path = stripOrgParams(path || '');
		var f = getOrgFiltersForApi();
		if (f.province) {
			path = appendParam(path, 'province', f.province);
		}
		if (f.district_manager) {
			path = appendParam(path, 'district_manager', f.district_manager);
		}
		if (f.area_manager) {
			path = appendParam(path, 'area_manager', f.area_manager);
		}
		if (f.province || f.district_manager || f.area_manager) {
			path = appendParam(path, 'tab', 'userReportTab');
		}
		return path;
	};

	function rebuildAreaManagerOptions(dmId, preferAmId) {
		var select = document.getElementById('reporting-area-manager-selector');
		if (!select) {
			return;
		}
		var map = cfg().areaManagersByDm || {};
		var key = dmId && dmId !== 'all' ? String(dmId) : 'all';
		var list = map[key];
		if (!list || !list.length) {
			list = map.all || [];
		}

		var current = preferAmId || select.value || '';
		select.innerHTML = '';
		var allOpt = document.createElement('option');
		allOpt.value = 'all';
		allOpt.textContent = 'All Area Managers';
		select.appendChild(allOpt);

		var found = false;
		for (var i = 0; i < list.length; i++) {
			var item = list[i];
			var opt = document.createElement('option');
			opt.value = String(item.id);
			opt.textContent = item.name;
			if (String(item.id) === String(current)) {
				opt.selected = true;
				found = true;
			}
			select.appendChild(opt);
		}
		if (!found) {
			select.value = 'all';
		}
	}

	function syncOrgFilterUi() {
		var show = isUserReportTab();
		var items = document.querySelectorAll('.lc-org-filter-item');
		for (var i = 0; i < items.length; i++) {
			items[i].style.display = show ? '' : 'none';
		}

		['reporting-province-selector', 'reporting-district-manager-selector', 'reporting-area-manager-selector'].forEach(function (id) {
			var el = document.getElementById(id);
			if (el) {
				el.disabled = !show;
			}
		});

		if (show) {
			rebuildAreaManagerOptions(getSelectedDistrictManager(), getSelectedAreaManager());
		}

		if (window.reportingApiSetup) {
			var f = getOrgFiltersForApi();
			reportingApiSetup.isolated_province = f.province;
			reportingApiSetup.isolated_district_manager = f.district_manager;
			reportingApiSetup.isolated_area_manager = f.area_manager;
		}
	}

	function patchApi() {
		if (!window.uoReportingAPI || typeof uoReportingAPI.reportingApiCall !== 'function') {
			return false;
		}

		// Prefer full patch from companion file; keep bootstrap if already set.
		if (uoReportingAPI.__lcOrgFiltersPatched) {
			syncOrgFilterUi();
			return true;
		}

		syncOrgFilterUi();

		var original = uoReportingAPI.reportingApiCall.bind(uoReportingAPI);
		uoReportingAPI.reportingApiCall = function (endpoint, path) {
			path = path || '';
			if (endpoint === 'courses_overview') {
				path = window.lcOrgAppendFilters(path);
			} else {
				path = stripOrgParams(path);
			}
			return original(endpoint, path);
		};
		uoReportingAPI.__lcOrgFiltersPatched = true;
		uoReportingAPI.__lcOrgFiltersBootstrapped = true;
		return true;
	}

	function fetchKey() {
		var f = getOrgFiltersForApi();
		return getActiveTab() + ':' + (f.province || 'all') + ':' + (f.district_manager || 'all') + ':' + (f.area_manager || 'all');
	}

	function maybeRefetchForTab() {
		syncOrgFilterUi();
		if (!window.dataObject || typeof dataObject.getData !== 'function') {
			return;
		}
		var key = fetchKey();
		if (lastFetchKey === null) {
			lastFetchKey = key;
			return;
		}
		if (key === lastFetchKey) {
			return;
		}
		lastFetchKey = key;
		dataObject.dataObjectPopulated = false;
		dataObject.getData();
	}

	function bindEvents() {
		$(document).on('click', '.uo-admin-reporting-tabs a, .tclr-admin-nav-items a', function () {
			window.setTimeout(maybeRefetchForTab, 50);
		});

		$(document).on('change', '#reporting-district-manager-selector', function () {
			rebuildAreaManagerOptions(this.value, 'all');
		});

		var tabField = document.getElementById('reporting-group-selector-tab');
		if (tabField && window.MutationObserver) {
			var observer = new MutationObserver(function () {
				maybeRefetchForTab();
			});
			observer.observe(tabField, { attributes: true, attributeFilter: ['value'] });
		}
	}

	function boot() {
		syncOrgFilterUi();
		bindEvents();

		if (patchApi()) {
			lastFetchKey = fetchKey();
			return;
		}
		var tries = 0;
		var timer = window.setInterval(function () {
			tries += 1;
			if (patchApi() || tries > 40) {
				window.clearInterval(timer);
				lastFetchKey = fetchKey();
			}
		}, 100);
	}

	// Run ASAP (header) so first getData() is patched; also on DOM ready for UI.
	boot();
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			syncOrgFilterUi();
			patchApi();
		});
	}
})(jQuery);
