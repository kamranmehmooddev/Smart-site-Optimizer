/**
 * SmartSite Optimizer Admin JavaScript
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		
		// Clear cache button
		$('#sso-clear-cache, #sso-clear-all-cache').on('click', function(e) {
			e.preventDefault();
			
			if (!confirm('Are you sure you want to clear all caches?')) {
				return;
			}

			var $btn = $(this);
			$btn.prop('disabled', true).text('Clearing...');

			$.ajax({
				url: ssoAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'sso_clear_cache',
					nonce: ssoAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						alert('Cache cleared successfully!');
					} else {
						alert('Error: ' + response.data);
					}
				},
				error: function() {
					alert('An error occurred while clearing cache.');
				},
				complete: function() {
					$btn.prop('disabled', false).text($btn.attr('id') === 'sso-clear-cache' ? 'Clear All Cache' : 'Clear All Caches');
				}
			});
		});

		// Run optimization button
		$('#sso-run-optimization').on('click', function(e) {
			e.preventDefault();

			var $btn = $(this);
			$btn.prop('disabled', true).text('Running Optimization...');

			$.ajax({
				url: ssoAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'sso_run_optimization',
					nonce: ssoAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						alert('Optimization completed successfully!');
						location.reload();
					} else {
						alert('Error: ' + response.data);
					}
				},
				error: function() {
					alert('An error occurred during optimization.');
				},
				complete: function() {
					$btn.prop('disabled', false).text('Run One-Click Optimization');
				}
			});
		});

		// Load dashboard data
		if ($('.sso-dashboard').length) {
			loadDashboardData();
		}

		function loadDashboardData() {
			$.ajax({
				url: ssoAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'sso_get_dashboard_data',
					nonce: ssoAdmin.nonce
				},
				success: function(response) {
					if (response.success && response.data) {
						updateDashboardMetrics(response.data);
						renderVitalsChart(response.data.vitals);
						renderTopPages(response.data.top_pages);
					}
				}
			});
		}

		function updateDashboardMetrics(data) {
			if (data.vitals && data.vitals.length > 0) {
				var latest = data.vitals[0];
				$('#sso-performance-score').text(Math.round(latest.avg_score || 0));
				$('#sso-page-load-time').text(Math.round(latest.avg_lcp || 0) + 'ms');
			}
		}

		function renderVitalsChart(vitals) {
			if (!vitals || vitals.length === 0 || typeof Chart === 'undefined') {
				return;
			}

			var ctx = document.getElementById('sso-vitals-chart');
			if (!ctx) return;

			var labels = vitals.map(v => v.date).reverse();
			var lcpData = vitals.map(v => v.avg_lcp).reverse();
			var fidData = vitals.map(v => v.avg_fid).reverse();
			var clsData = vitals.map(v => v.avg_cls * 1000).reverse();

			new Chart(ctx, {
				type: 'line',
				data: {
					labels: labels,
					datasets: [
						{
							label: 'LCP (ms)',
							data: lcpData,
							borderColor: 'rgb(75, 192, 192)',
							tension: 0.1
						},
						{
							label: 'FID (ms)',
							data: fidData,
							borderColor: 'rgb(255, 99, 132)',
							tension: 0.1
						},
						{
							label: 'CLS (×1000)',
							data: clsData,
							borderColor: 'rgb(255, 205, 86)',
							tension: 0.1
						}
					]
				},
				options: {
					responsive: true,
					plugins: {
						legend: {
							position: 'top',
						}
					},
					scales: {
						y: {
							beginAtZero: true
						}
					}
				}
			});
		}

		function renderTopPages(pages) {
			if (!pages || pages.length === 0) {
				$('#sso-top-pages').html('<p>No data available yet.</p>');
				return;
			}

			var html = '<ul class="sso-pages-list">';
			pages.forEach(function(page) {
				html += '<li>';
				html += '<strong>' + page.page_url + '</strong><br>';
				html += 'Score: ' + Math.round(page.avg_score) + ' | Views: ' + page.views;
				html += '</li>';
			});
			html += '</ul>';

			$('#sso-top-pages').html(html);
		}

	});

})(jQuery);
