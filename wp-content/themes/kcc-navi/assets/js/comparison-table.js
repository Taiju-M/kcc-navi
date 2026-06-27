(function () {
	"use strict";

	function setupTable(root) {
		var grid = root.querySelector("[data-kcc-grid]");
		var sortSelect = root.querySelector("[data-kcc-sort]");
		var emptyMsg = root.querySelector("[data-kcc-empty]");
		var filters = Array.prototype.slice.call(
			root.querySelectorAll("[data-kcc-filter]"),
		);
		if (!grid || !sortSelect) {
			return;
		}

		var allCards = Array.prototype.slice.call(
			grid.querySelectorAll("[data-kcc-card]"),
		);

		// 昇順でソートすべきキー（小さいほど良い）
		var ascKeys = ["issue_fee", "annual_fee"];

		function numberAttr(card, key) {
			var raw = card.getAttribute("data-" + key);
			var value = parseFloat(raw);
			return isNaN(value) ? 0 : value;
		}

		function recalcRanks() {
			var rank = 0;
			allCards.forEach(function (card) {
				var badge = card.querySelector("[data-kcc-rank]");
				var hidden = card.classList.contains("kcc-card--hidden");
				if (badge) {
					badge.classList.remove(
						"kcc-card__rank--1",
						"kcc-card__rank--2",
						"kcc-card__rank--3",
					);
				}
				if (hidden) {
					card.classList.remove("kcc-card--top");
					if (badge) {
						badge.textContent = "";
					}
					return;
				}
				rank += 1;
				if (badge) {
					badge.textContent = String(rank);
					if (rank <= 3) {
						badge.classList.add("kcc-card__rank--" + rank);
					}
				}
				card.classList.toggle("kcc-card--top", rank <= 3);
			});
			if (emptyMsg) {
				emptyMsg.hidden = rank !== 0;
			}
		}

		function applySort() {
			var key = sortSelect.value;
			var asc = ascKeys.indexOf(key) !== -1;
			var sorted = allCards.slice().sort(function (a, b) {
				var av = numberAttr(a, key);
				var bv = numberAttr(b, key);
				return asc ? av - bv : bv - av;
			});
			sorted.forEach(function (card) {
				grid.appendChild(card);
			});
			allCards = sorted;
			recalcRanks();
		}

		function applyFilters() {
			var active = filters.filter(function (cb) {
				return cb.checked;
			});
			allCards.forEach(function (card) {
				var visible = active.every(function (cb) {
					var key = cb.getAttribute("data-kcc-filter");
					return card.getAttribute("data-" + key) === "1";
				});
				card.classList.toggle("kcc-card--hidden", !visible);
			});
			recalcRanks();
		}

		sortSelect.addEventListener("change", applySort);
		filters.forEach(function (cb) {
			cb.addEventListener("change", function () {
				var chip = cb.closest(".kcc-chip");
				if (chip) {
					chip.classList.toggle("is-active", cb.checked);
				}
				applyFilters();
			});
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
