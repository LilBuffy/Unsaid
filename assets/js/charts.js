(function () {
    var host = document.getElementById('visitChart');
    var dataNode = document.getElementById('chartData');
    if (!host || !dataNode) return;

    var data;
    try {
        data = JSON.parse(dataNode.textContent);
    } catch (err) {
        return;
    }
    if (!Array.isArray(data) || !data.length) return;

    var NS = 'http://www.w3.org/2000/svg';
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function el(name, attrs, text) {
        var node = document.createElementNS(NS, name);
        Object.keys(attrs || {}).forEach(function (key) { node.setAttribute(key, attrs[key]); });
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function label(day) {
        var parts = day.split('-');
        return months[parseInt(parts[1], 10) - 1] + ' ' + parseInt(parts[2], 10);
    }

    function niceMax(value) {
        if (value <= 4) return 4;
        var step = Math.pow(10, Math.floor(Math.log10(value)));
        return Math.ceil(value / step) * step;
    }

    function render(range) {
        var rows = data.slice(-range);
        var width = Math.max(260, Math.round(host.clientWidth || 600));
        var height = width < 520 ? 170 : 190;
        var padLeft = 30;
        var padBottom = 26;
        var padTop = 10;
        var plotW = width - padLeft - 6;
        var plotH = height - padBottom - padTop;
        var maxValue = niceMax(Math.max.apply(null, rows.map(function (r) { return r.v; }).concat([1])));
        var slot = plotW / rows.length;
        var barW = Math.max(3, Math.min(26, slot * 0.62));

        var svg = el('svg', { viewBox: '0 0 ' + width + ' ' + height, width: width, height: height, class: 'chart-svg' });

        [0, 0.5, 1].forEach(function (t) {
            var y = padTop + plotH - plotH * t;
            svg.appendChild(el('line', { x1: padLeft, x2: width - 6, y1: y, y2: y, class: 'chart-gridline' }));
            svg.appendChild(el('text', { x: padLeft - 6, y: y + 4, 'text-anchor': 'end', class: 'chart-tick' }, String(Math.round(maxValue * t))));
        });

        var every = rows.length > 8 ? Math.ceil(rows.length / (width < 520 ? 4 : 7)) : 1;
        rows.forEach(function (row, i) {
            var cx = padLeft + slot * i + slot / 2;
            var viewsH = plotH * (row.v / maxValue);
            var uniqueH = plotH * (row.u / maxValue);
            var group = el('g', { class: 'chart-bar-group' });
            group.appendChild(el('title', {}, label(row.d) + ': ' + row.v + ' visit' + (row.v === 1 ? '' : 's') + ', ' + row.u + ' unique'));
            group.appendChild(el('rect', { x: cx - slot / 2, y: padTop, width: slot, height: plotH, class: 'chart-hit' }));
            group.appendChild(el('rect', { x: cx - barW / 2, y: padTop + plotH - viewsH, width: barW, height: Math.max(viewsH, row.v > 0 ? 2 : 0), class: 'chart-bar' }));
            group.appendChild(el('rect', { x: cx - barW / 2, y: padTop + plotH - uniqueH, width: barW, height: Math.max(uniqueH, row.u > 0 ? 2 : 0), class: 'chart-bar-unique' }));
            svg.appendChild(group);
            if (i % every === 0 || i === rows.length - 1) {
                if (every === 1 || i % every === 0) {
                    svg.appendChild(el('text', { x: cx, y: height - 8, 'text-anchor': 'middle', class: 'chart-tick' }, label(row.d)));
                }
            }
        });

        host.innerHTML = '';
        host.appendChild(svg);

        var legend = document.createElement('div');
        legend.className = 'chart-legend';
        legend.innerHTML = '<span><i class="swatch swatch-views"></i>Visits</span><span><i class="swatch swatch-unique"></i>Unique visitors</span>';
        host.appendChild(legend);

        var total = rows.reduce(function (sum, r) { return sum + r.v; }, 0);
        var totalNode = document.getElementById('rangeTotal');
        var labelNode = document.getElementById('rangeLabel');
        if (totalNode) totalNode.textContent = total;
        if (labelNode) labelNode.textContent = 'Last ' + range + ' days';
    }

    var current = 7;
    document.addEventListener('unsaid:range', function (event) {
        current = event.detail === 30 ? 30 : 7;
        render(current);
    });
    var resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () { render(current); }, 150);
    });
    render(current);
})();
