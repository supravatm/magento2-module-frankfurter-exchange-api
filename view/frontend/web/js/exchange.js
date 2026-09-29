define([
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    return function (externalConfig) {
        const config = $.extend({
            currenciesUrl: '/fx/api/currencies',
            rateUrl: '/fx/api/rate',
            historyUrl: '/fx/api/history',
            defaultFromCurrency: 'USD',
            defaultToCurrency: 'EUR',
            defaultHistoryDays: 30,
            currentDate: '',
        }, externalConfig);

        console.log(externalConfig);

        const $from = $('#from-currency');
        const $to = $('#to-currency');
        const $swap = $('#swap-currencies');

        const $rateBase = $('.rate-base');
        const $rateValue = $('.rate-value');
        const $rateUpdated = $('.rate-updated');

        const $message = $('#exchange-message');
        const $chart = $('#exchange-chart');
        const $chartStatus = $('#chart-status');

        let currentRate = null;
        let currentDays = config.defaultHistoryDays;

        /**
         * Initialize module.
         */
        function init() {
            $('.period-button').removeClass('active');
            $('.period-button[data-days="' + config.defaultHistoryDays + '"]').addClass('active');

            loadCurrencies();

            $from.on('change', refreshExchange);
            $to.on('change', refreshExchange);

            $swap.on('click', swapCurrencies);

            $('.period-button').on('click', function () {
                $('.period-button').removeClass('active');
                $(this).addClass('active');

                currentDays = parseInt(
                    $(this).data('days'),
                    10
                );

                loadHistory();
            });
        }

        /**
         * Load currencies.
         */
        function loadCurrencies() {
            setLoading(true);

            $.ajax({
                url: config.currenciesUrl,
                type: 'GET',
                dataType: 'json'
            })
                .done(function (response) {
                    if (!response.success) {
                        showError(response.message);
                        return;
                    }
                    populateCurrencies(response.currencies);
                    $from.prop('disabled', false);
                    $to.prop('disabled', false);
                    $swap.prop('disabled', false);
                    $from.val(config.defaultFromCurrency);
                    $to.val(config.defaultToCurrency);
                    refreshExchange();
                })
                .fail(function () {
                    showError(
                        $.mage.__('Unable to load currencies. Please try again.')
                    );

                })
                .always(function () {
                    setLoading(false);
                });
        }

        /**
         * Populate currency dropdowns.
         */
        function populateCurrencies(currencies) {
            $from.empty();
            $to.empty();
            $.each(currencies, function (index, currency) {
                const code = currency.iso_code;
                const name = currency.name;
                const label = code + ' - ' + name;

                $from.append(
                    $('<option>', {
                        value: code,
                        text: label
                    })
                );

                $to.append(
                    $('<option>', {
                        value: code,
                        text: label
                    })
                );
            });
        }

        /**
         * Refresh rate and history.
         */
        function refreshExchange() {
            const from = $from.val();
            const to = $to.val();

            if (!from || !to) {
                return;
            }

            clearMessage();

            if (from === to) {
                currentRate = 1;

                updateRateDisplay({
                    base: from,
                    quote: to,
                    rate: 1,
                    date: config.currentDate
                });

                loadHistory();

                return;
            }

            loadRate();
            loadHistory();
        }

        /**
         * Load current exchange rate.
         */
        function loadRate() {
            const from = $from.val();
            const to = $to.val();

            setRateLoading(true);

            $.ajax({
                url: config.rateUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    from: from,
                    to: to
                }
            })
                .done(function (response) {

                    if (!response.success) {
                        showError(response.message);
                        return;
                    }

                    currentRate = parseFloat(
                        response.data.rate
                    );

                    updateRateDisplay(response.data);
                })
                .fail(function () {
                    currentRate = null;

                    showError(
                        $.mage.__('Unable to load the current exchange rate.')
                    );
                })
                .always(function () {
                    setRateLoading(false);
                });
        }

        /**
         * Update current rate display.
         */
        function updateRateDisplay(data) {
            const rate = parseFloat(data.rate);

            $rateBase.text(
                '1 ' + data.base
            );

            $rateValue.text(
                '= ' + formatNumber(rate) + ' ' + data.quote
            );

            $rateUpdated.text(
                $.mage.__('Updated: ') + formatDate(data.date)
            );
        }

        /**
         * Swap currencies.
         */
        function swapCurrencies() {
            const from = $from.val();
            const to = $to.val();

            $from.val(to);
            $to.val(from);

            refreshExchange();
        }

        /**
         * Load historical rates.
         */
        function loadHistory() {
            const from = $from.val();
            const to = $to.val();

            if (!from || !to) {
                return;
            }

            $chartStatus.text($.mage.__('Loading historical data...'));

            $chart.empty();

            $.ajax({
                url: config.historyUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    from: from,
                    to: to,
                    days: currentDays
                }
            })
                .done(function (response) {

                    if (!response.success) {
                        $chartStatus.text(
                            response.message
                        );

                        return;
                    }

                    if (!response.rates.length) {
                        $chartStatus.text($.mage.__('No historical data available.'));
                        return;
                    }

                    $chartStatus.text('');

                    renderChart(response.rates);
                })
                .fail(function () {
                    $chartStatus.text($.mage.__('Unable to load historical data.'));
                });
        }

        /**
         * Render SVG line chart.
         */
        function renderChart(rates) {
            $chart.empty();

            const width = 700;
            const height = 340;

            const padding = {
                top: 30,
                right: 30,
                bottom: 50,
                left: 65
            };

            const chartWidth =
                width -
                padding.left -
                padding.right;

            const chartHeight =
                height -
                padding.top -
                padding.bottom;

            const values = rates.map(function (item) {
                return parseFloat(item.rate);
            });

            const min = Math.min.apply(null, values);
            const max = Math.max.apply(null, values);

            const range = max - min || 1;

            const points = rates.map(
                function (item, index) {

                    const x =
                        padding.left +
                        (
                            index /
                            Math.max(rates.length - 1, 1)
                        ) *
                        chartWidth;

                    const y =
                        padding.top +
                        (
                            (max - item.rate) /
                            range
                        ) *
                        chartHeight;

                    return {
                        x: x,
                        y: y,
                        date: item.date,
                        rate: parseFloat(item.rate)
                    };
                }
            );

            const svg = document.createElementNS(
                'http://www.w3.org/2000/svg',
                'svg'
            );

            svg.setAttribute(
                'viewBox',
                '0 0 ' + width + ' ' + height
            );

            svg.setAttribute(
                'role',
                'img'
            );

            svg.setAttribute(
                'aria-label',
                $.mage.__('Historical exchange rate chart')
            );

            /*
             * Grid lines
             */
            for (let i = 0; i <= 4; i++) {

                const y =
                    padding.top +
                    (chartHeight / 4) * i;

                const line = svgElement(
                    'line',
                    {
                        x1: padding.left,
                        y1: y,
                        x2: width - padding.right,
                        y2: y,
                        class: 'chart-grid-line'
                    }
                );

                svg.appendChild(line);

                const value =
                    max - (range / 4) * i;

                const label = svgElement(
                    'text',
                    {
                        x: padding.left - 10,
                        y: y + 4,
                        class: 'chart-axis-label',
                        'text-anchor': 'end'
                    }
                );

                label.textContent =
                    formatNumber(value);

                svg.appendChild(label);
            }

            /*
             * Line path
             */
            const pathData = points
                .map(function (point, index) {

                    return (
                        index === 0 ? 'M ' : 'L '
                    ) +
                        point.x +
                        ' ' +
                        point.y;

                })
                .join(' ');

            const path = svgElement(
                'path',
                {
                    d: pathData,
                    class: 'chart-line',
                    fill: 'none'
                }
            );

            svg.appendChild(path);

            /*
             * Last point
             */
            const lastPoint =
                points[points.length - 1];

            const circle = svgElement(
                'circle',
                {
                    cx: lastPoint.x,
                    cy: lastPoint.y,
                    r: 4,
                    class: 'chart-point'
                }
            );

            svg.appendChild(circle);

            /*
             * Date labels
             */
            const firstDate = rates[0].date;
            const lastDate =
                rates[rates.length - 1].date;

            const firstLabel = svgElement(
                'text',
                {
                    x: padding.left,
                    y: height - 15,
                    class: 'chart-axis-label'
                }
            );

            firstLabel.textContent =
                formatDate(firstDate);

            svg.appendChild(firstLabel);

            const lastLabel = svgElement(
                'text',
                {
                    x: width - padding.right,
                    y: height - 15,
                    class: 'chart-axis-label',
                    'text-anchor': 'end'
                }
            );

            lastLabel.textContent =
                formatDate(lastDate);

            svg.appendChild(lastLabel);

            $chart[0].appendChild(svg);
        }

        /**
         * Create SVG element.
         */
        function svgElement(name, attributes) {
            const element =
                document.createElementNS(
                    'http://www.w3.org/2000/svg',
                    name
                );

            Object.keys(attributes).forEach(
                function (key) {
                    element.setAttribute(
                        key,
                        attributes[key]
                    );
                }
            );

            return element;
        }


        /**
         * Format number.
         */
        function formatNumber(value) {
            return Number(value).toLocaleString(
                undefined,
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 6
                }
            );
        }

        /**
         * Format date.
         */
        function formatDate(dateString) {
            const date = new Date(dateString + 'T00:00:00');

            return date.toLocaleDateString(
                config.locale,
                {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                }
            );
        }

        /**
         * Show error.
         */
        function showError(message) {
            $message.text(message);
        }

        /**
         * Clear error.
         */
        function clearMessage() {
            $message.text('');
        }

        /**
         * Loading state.
         */
        function setLoading(loading) {
            if (loading) {
                $message.text(
                    $.mage.__('Loading currencies...')
                );
            }
        }

        /**
         * Rate loading state.
         */
        function setRateLoading(loading) {
            if (loading) {
                $rateValue.text(
                    $.mage.__('= Loading...')
                );
                $rateUpdated.text('');
            }
        }
        init();
    };
});