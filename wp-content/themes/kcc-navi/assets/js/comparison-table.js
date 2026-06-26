(function () {
	"use strict";

	function setupTable(root) {
		var tbody = root.querySelector("[data-kcc-rows]");
		var sortSelect = root.querySelector("[data-kcc-sort]");
		var filters = Array.prototype.slice.call(
			root.querySelectorAll("[data-kcc-filter]"),
		);
		if (!tbody || !sortSelect) {
			return;
		}

		var allRows = Array.prototype.slice.call(tbody.querySelectorAll("tr"));

		// 昇順でソートすべきキー（小さいほど良い）
		var ascKeys = ["issue_fee", "annual_fee"];

		function numberAttr(row, key) {
			var raw = row.getAttribute("data-" + key);
			var value = parseFloat(raw);
			return isNaN(value) ? 0 : value;
		}

		function applySort() {
			var key = sortSelect.value;
			var asc = ascKeys.indexOf(key) !== -1;
			var sorted = allRows.slice().sort(function (a, b) {
				var av = numberAttr(a, key);
				var bv = numberAttr(b, key);
				return asc ? av - bv : bv - av;
			});
			sorted.forEach(function (row) {
				tbody.appendChild(row);
			});
		}

		function applyFilters() {
			var active = filters.filter(function (cb) {
				return cb.checked;
			});
			allRows.forEach(function (row) {
				var visible = active.every(function (cb) {
					var key = cb.getAttribute("data-kcc-filter");
					return row.getAttribute("data-" + key) === "1";
				});
				row.classList.toggle("kcc-comparison__row--hidden", !visible);
			});
		}

		sortSelect.addEventListener("change", applySort);
		filters.forEach(function (cb) {
			cb.addEventListener("change", applyFilters);
		});

		applySort();
		applyFilters();
	}

	function init() {
		var roots = document.querySelectorAll("[data-kcc-comparison]");
		Array.prototype.forEach.call(roots, setupTable);
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
