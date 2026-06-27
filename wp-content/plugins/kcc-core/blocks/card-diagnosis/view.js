(function () {
	"use strict";

	function initDiag(root) {
		var dataEl = root.querySelector("[data-kcc-diag-data]");
		if (!dataEl) {
			return;
		}

		var cards;
		try {
			cards = JSON.parse(dataEl.textContent);
		} catch (e) {
			return;
		}
		if (!Array.isArray(cards) || cards.length === 0) {
			return;
		}

		var form = root.querySelector("[data-kcc-diag-form]");
		var steps = Array.prototype.slice.call(
			root.querySelectorAll("[data-kcc-diag-step]"),
		);
		var result = root.querySelector("[data-kcc-diag-result]");
		var cardsWrap = root.querySelector("[data-kcc-diag-cards]");
		var noResult = root.querySelector("[data-kcc-diag-noresult]");
		var bar = root.querySelector("[data-kcc-diag-bar]");
		var restartBtn = root.querySelector("[data-kcc-diag-restart]");
		var total = steps.length;
		var current = 0;
		var answers = {};

		function showStep(index) {
			steps.forEach(function (step, i) {
				var active = i === index;
				step.hidden = !active;
				step.classList.toggle("is-active", active);
			});
			current = index;
			updateProgress();
		}

		function updateProgress() {
			if (!bar) {
				return;
			}
			var answered = Object.keys(answers).length;
			var pct = Math.round((answered / total) * 100);
			bar.style.width = pct + "%";
		}

		function maxCashback() {
			var m = 0;
			cards.forEach(function (c) {
				if (c.cashback > m) {
					m = c.cashback;
				}
			});
			return m || 1;
		}

		function maxPriority() {
			var m = 0;
			cards.forEach(function (c) {
				if (c.priority > m) {
					m = c.priority;
				}
			});
			return m || 1;
		}

		function maxFee() {
			var m = 0;
			cards.forEach(function (c) {
				var f = Math.max(c.issueFee, c.annualFee);
				if (isFinite(f) && f > m) {
					m = f;
				}
			});
			return m || 1;
		}

		function evaluate() {
			var maxCb = maxCashback();
			var maxPr = maxPriority();
			var maxF = maxFee();
			var scored = [];

			cards.forEach(function (c) {
				var reasons = [];

				// Hard filter: residence availability.
				if (answers.residence === "overseas" && !c.overseas) {
					return;
				}
				if (answers.residence === "japan" && !c.japan) {
					return;
				}
				if (answers.residence === "overseas") {
					reasons.push("海外在住でも発行可");
				}

				// Hard filter: physical form when explicitly required.
				if (answers.form === "physical" && !c.physical) {
					return;
				}
				if (answers.form === "physical" && c.physical) {
					reasons.push("物理カードあり");
				}

				// Hard filter: free issuance when required.
				if (answers.initial === "free" && c.issueFee > 0) {
					return;
				}
				if (answers.initial === "free") {
					reasons.push("カード作成費が無料");
				}

				// Soft scoring.
				var score = 0;
				var cbNorm = c.cashback / maxCb; // 0..1
				var feeNorm =
					isFinite(maxF) && maxF > 0
						? Math.max(c.issueFee, c.annualFee) / maxF
						: 0;
				var costScore = 1 - Math.min(feeNorm, 1); // higher = cheaper

				if (answers.priority === "cashback") {
					score += cbNorm * 60 + costScore * 15;
					if (cbNorm >= 0.8) {
						reasons.push("還元率が高水準（" + c.cashbackLabel + "）");
					}
				} else if (answers.priority === "cost") {
					score += costScore * 60 + cbNorm * 15;
					if (costScore >= 0.8) {
						reasons.push("維持コストが安い");
					}
				} else {
					score += cbNorm * 35 + costScore * 35;
				}

				// Trust preference.
				if (answers.trust === "verified") {
					if (c.verified) {
						score += 20;
						reasons.push("編集部が公式確認済み");
					} else {
						score -= 12;
					}
				} else if (c.verified) {
					score += 6;
				}

				// Priority as gentle tiebreaker.
				score += (c.priority / maxPr) * 10;

				scored.push({ card: c, score: score, reasons: reasons });
			});

			scored.sort(function (a, b) {
				if (b.score !== a.score) {
					return b.score - a.score;
				}
				return b.card.priority - a.card.priority;
			});

			return scored.slice(0, 3);
		}

		function escapeHtml(str) {
			return String(str)
				.replace(/&/g, "&amp;")
				.replace(/</g, "&lt;")
				.replace(/>/g, "&gt;")
				.replace(/"/g, "&quot;")
				.replace(/'/g, "&#39;");
		}

		function viz(card) {
			if (card.image) {
				return (
					'<div class="kcc-diag-card__viz kcc-diag-card__viz--img">' +
					'<img src="' +
					escapeHtml(card.image) +
					'" alt="' +
					escapeHtml(card.title) +
					'の券面" loading="lazy" decoding="async">' +
					"</div>"
				);
			}
			return (
				'<div class="kcc-diag-card__viz" style="--kcc-h: ' +
				(parseInt(card.hue, 10) || 220) +
				';">' +
				'<span class="kcc-diag-card__viz-name">' +
				escapeHtml(card.title) +
				"</span>" +
				"</div>"
			);
		}

		function renderResults(list) {
			cardsWrap.innerHTML = "";
			if (list.length === 0) {
				noResult.hidden = false;
				return;
			}
			noResult.hidden = true;

			list.forEach(function (item, i) {
				var c = item.card;
				var reasons = item.reasons.slice(0, 3);
				var reasonHtml = reasons
					.map(function (r) {
						return "<li>" + escapeHtml(r) + "</li>";
					})
					.join("");

				var cta = c.cta
					? '<a class="kcc-diag-card__cta" href="' +
						escapeHtml(c.cta) +
						'" target="_blank" rel="nofollow sponsored noopener">公式サイト<span aria-hidden="true">→</span></a>'
					: "";

				var article = document.createElement("article");
				article.className =
					"kcc-diag-card" + (i === 0 ? " kcc-diag-card--best" : "");
				article.innerHTML =
					'<div class="kcc-diag-card__badge">' +
					(i === 0 ? "最もおすすめ" : "マッチ " + (i + 1)) +
					"</div>" +
					viz(c) +
					'<h4 class="kcc-diag-card__name"><a href="' +
					escapeHtml(c.permalink) +
					'">' +
					escapeHtml(c.title) +
					"</a></h4>" +
					'<dl class="kcc-diag-card__spec"><dt>還元率</dt><dd>' +
					escapeHtml(c.cashbackLabel) +
					"</dd></dl>" +
					(reasonHtml
						? '<ul class="kcc-diag-card__reasons">' + reasonHtml + "</ul>"
						: "") +
					'<div class="kcc-diag-card__actions">' +
					cta +
					'<a class="kcc-diag-card__detail" href="' +
					escapeHtml(c.permalink) +
					'">詳細を見る</a></div>';

				cardsWrap.appendChild(article);
			});
		}

		function finish() {
			var list = evaluate();
			steps.forEach(function (step) {
				step.hidden = true;
				step.classList.remove("is-active");
			});
			renderResults(list);
			result.hidden = false;
			if (bar) {
				bar.style.width = "100%";
			}
			result.scrollIntoView({ behavior: "smooth", block: "nearest" });
		}

		form.addEventListener("change", function (e) {
			var input = e.target.closest("[data-kcc-diag-input]");
			if (!input) {
				return;
			}
			var step = input.closest("[data-kcc-diag-step]");
			var key = step.getAttribute("data-kcc-key");
			answers[key] = input.value;
			updateProgress();

			if (current < total - 1) {
				showStep(current + 1);
			} else {
				finish();
			}
		});

		form.addEventListener("click", function (e) {
			var back = e.target.closest("[data-kcc-diag-back]");
			if (!back) {
				return;
			}
			if (current > 0) {
				showStep(current - 1);
			}
		});

		if (restartBtn) {
			restartBtn.addEventListener("click", function () {
				answers = {};
				form.reset();
				result.hidden = true;
				showStep(0);
				root.scrollIntoView({ behavior: "smooth", block: "nearest" });
			});
		}

		showStep(0);
	}

	document.addEventListener("DOMContentLoaded", function () {
		Array.prototype.slice
			.call(document.querySelectorAll("[data-kcc-diag]"))
			.forEach(initDiag);
	});
})();
