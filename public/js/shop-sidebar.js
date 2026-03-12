(function () {
	'use strict';

	if (!document.body.classList.contains('palaplast-hide-product-cat-filters')) {
		return;
	}

	var sidebarSelectors = [
		'#sidebar',
		'.sidebar',
		'.sidebar-container',
		'.shop-sidebar',
		'.woocommerce-sidebar',
		'.widget_price_filter',
		'.woocommerce-widget-layered-nav',
		'.woocommerce-widget-layered-nav-list',
		'.wpb_widgetised_column',
		'.widget.woocommerce'
	].join(',');

	function closestColumn(element) {
		return element.closest('.vc_column_container, .wpb_column, .vc_col-sm-3, .vc_col-sm-4, .vc_col-md-3, .vc_col-md-4, #secondary');
	}

	function expandSiblingColumns(hiddenColumns) {
		hiddenColumns.forEach(function (column) {
			var row = column.closest('.vc_row, .wpb_row, .row');
			if (!row) {
				return;
			}

			var siblings = row.querySelectorAll('.vc_column_container, .wpb_column, [class*="vc_col-"]');
			siblings.forEach(function (sibling) {
				if (hiddenColumns.indexOf(sibling) !== -1) {
					return;
				}

				sibling.classList.add('palaplast-filter-content-expanded');
				sibling.classList.remove(
					'vc_col-sm-3',
					'vc_col-sm-4',
					'vc_col-sm-8',
					'vc_col-sm-9',
					'vc_col-md-3',
					'vc_col-md-4',
					'vc_col-md-8',
					'vc_col-md-9',
					'col-md-3',
					'col-md-4',
					'col-md-8',
					'col-md-9'
				);

				sibling.classList.add('vc_col-sm-12', 'vc_col-md-12', 'col-md-12');
			});
		});
	}

	var sidebarNodes = Array.prototype.slice.call(document.querySelectorAll(sidebarSelectors));
	if (!sidebarNodes.length) {
		return;
	}

	var hiddenColumns = [];
	sidebarNodes.forEach(function (node) {
		var column = closestColumn(node);
		if (column && hiddenColumns.indexOf(column) === -1) {
			hiddenColumns.push(column);
		}
	});

	hiddenColumns.forEach(function (column) {
		column.classList.add('palaplast-filter-column-hidden');
	});

	expandSiblingColumns(hiddenColumns);
})();
