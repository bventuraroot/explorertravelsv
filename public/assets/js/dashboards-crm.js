/**
 * Dashboard Explorer Travel – Analytics BI
 */
'use strict';

(function () {
  /* ── resolución de colores del tema ─────────────────────────────────── */
  const dark        = (typeof isDarkStyle !== 'undefined') && isDarkStyle;
  const labelColor  = dark
    ? ((typeof config !== 'undefined' && config.colors_dark) ? config.colors_dark.textMuted   : '#b4bdc6')
    : ((typeof config !== 'undefined' && config.colors)      ? config.colors.textMuted         : '#a1acb8');
  const borderColor = dark
    ? ((typeof config !== 'undefined' && config.colors_dark) ? config.colors_dark.borderColor  : '#3b4253')
    : ((typeof config !== 'undefined' && config.colors)      ? config.colors.borderColor        : '#d9dee3');

  /* ── datos del dashboard (enviados desde la vista) ───────────────────── */
  const D = window._dash || {};

  /* ── helpers ─────────────────────────────────────────────────────────── */
  function nums(arr, key) {
    if (!Array.isArray(arr)) return [];
    return arr.map(function (item) {
      return (item && typeof item === 'object') ? (parseFloat(item[key]) || 0) : (parseFloat(item) || 0);
    });
  }

  function strs(arr, key) {
    if (!Array.isArray(arr)) return [];
    return arr.map(function (item) {
      return (item && typeof item === 'object') ? (item[key] || '') : String(item || '');
    });
  }

  var MESES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  var DIAS  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

  function fmtMes(m) {
    if (!m) return '';
    var parts = m.split('-');
    var mo = parseInt(parts[1], 10) - 1;
    return (MESES[mo] || parts[1]) + "'" + (parts[0] || '').slice(2);
  }

  function fmtDia(d) {
    if (!d) return '';
    var dt = new Date(d + 'T00:00:00');
    return DIAS[dt.getDay()] || d;
  }

  function money(v) {
    return '$' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(v);
  }

  function moneyCompact(v) {
    return '$' + new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 }).format(v);
  }

  var tooltipTheme = dark ? 'dark' : 'light';

  /* ════════════════════════════════════════════════════════════════════════
   * 1. SPARKLINE – Ventas último año (área)
   * ════════════════════════════════════════════════════════════════════════ */
  var spYearEl = document.querySelector('#salesLastYear');
  if (spYearEl) {
    new ApexCharts(spYearEl, {
      chart: {
        height: 60, type: 'area',
        sparkline: { enabled: true },
        toolbar: { show: false },
        animations: { speed: 800 }
      },
      stroke: { width: 2.5, curve: 'smooth' },
      fill:   { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.02 } },
      colors: ['#696cff'],
      series: [{ data: nums(D.ventasPorMes, 'total') }],
      markers: { size: 0 },
      tooltip: { enabled: false }
    }).render();
  }

  /* ════════════════════════════════════════════════════════════════════════
   * 2. SPARKLINE – Ventas último mes (barras)
   * ════════════════════════════════════════════════════════════════════════ */
  var spMonthEl = document.querySelector('#sessionsLastMonth');
  if (spMonthEl) {
    new ApexCharts(spMonthEl, {
      chart: {
        height: 60, type: 'bar',
        sparkline: { enabled: true },
        toolbar: { show: false }
      },
      plotOptions: { bar: { borderRadius: 3, columnWidth: '55%' } },
      colors: ['#00cfe8'],
      series: [{ data: nums(D.ventasUltimoMes, 'total') }],
      tooltip: { enabled: false }
    }).render();
  }

  /* ════════════════════════════════════════════════════════════════════════
   * 3. SPARKLINE – Revenue growth / semana (línea)
   * ════════════════════════════════════════════════════════════════════════ */
  var spWeekEl = document.querySelector('#revenueGrowth');
  if (spWeekEl) {
    new ApexCharts(spWeekEl, {
      chart: {
        height: 60, type: 'line',
        sparkline: { enabled: true },
        toolbar: { show: false }
      },
      stroke: { width: 2.5, curve: 'smooth' },
      colors: ['#ff9f43'],
      series: [{ data: nums(D.ventasUltimaSemana, 'total') }],
      markers: { size: 0 },
      tooltip: { enabled: false }
    }).render();
  }

  /* ════════════════════════════════════════════════════════════════════════
   * 4. PRINCIPAL – Tendencia mensual 12 meses (área)
   * ════════════════════════════════════════════════════════════════════════ */
  var mainEl = document.querySelector('#mainRevenueChart');
  if (mainEl) {
    var mesesLabels  = strs(D.ventasPorMes, 'mes').map(fmtMes);
    var mesesTotales = nums(D.ventasPorMes, 'total');

    new ApexCharts(mainEl, {
      chart: {
        height: 285,
        type: 'area',
        toolbar: { show: false },
        zoom: { enabled: false },
        animations: { speed: 900 }
      },
      stroke: { width: 3, curve: 'smooth' },
      fill: {
        type: 'gradient',
        gradient: { shade: 'light', type: 'vertical', opacityFrom: 0.35, opacityTo: 0.03 }
      },
      colors: ['#696cff'],
      dataLabels: { enabled: false },
      series: [{ name: 'Ventas', data: mesesTotales }],
      xaxis: {
        categories: mesesLabels,
        labels: {
          rotate: -30,
          style: { colors: labelColor, fontSize: '11px' }
        },
        axisBorder: { show: false },
        axisTicks:  { show: false }
      },
      yaxis: {
        labels: {
          formatter: moneyCompact,
          style: { colors: labelColor, fontSize: '11px' }
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 4,
        padding: { top: 0, right: 8 }
      },
      markers: {
        size: 4,
        colors: ['#696cff'],
        strokeWidth: 2,
        strokeColors: ['#fff'],
        hover: { size: 7 }
      },
      tooltip: {
        theme: tooltipTheme,
        y: { formatter: money }
      }
    }).render();
  }

  /* ════════════════════════════════════════════════════════════════════════
   * 5. TENDENCIA SEMANAL – 7 días (barras distribuidas)
   * ════════════════════════════════════════════════════════════════════════ */
  var weekEl = document.querySelector('#weeklyTrendChart');
  if (weekEl) {
    var diasLabels  = strs(D.ventasPorDia, 'dia').map(fmtDia);
    var diasTotales = nums(D.ventasPorDia, 'total');
    var palette7    = ['#696cff','#28c76f','#00cfe8','#ff9f43','#ea5455','#7367f0','#00bad1'];

    new ApexCharts(weekEl, {
      chart: {
        height: 185,
        type: 'bar',
        toolbar: { show: false },
        animations: { speed: 700 }
      },
      plotOptions: { bar: { borderRadius: 7, columnWidth: '52%', distributed: true } },
      colors: palette7,
      dataLabels: { enabled: false },
      legend: { show: false },
      series: [{ name: 'Ventas', data: diasTotales }],
      xaxis: {
        categories: diasLabels,
        labels: { style: { colors: labelColor, fontSize: '12px' } },
        axisBorder: { show: false },
        axisTicks:  { show: false }
      },
      yaxis: { show: false },
      grid: { show: false },
      tooltip: {
        theme: tooltipTheme,
        y: { formatter: money }
      }
    }).render();
  }

  /* ════════════════════════════════════════════════════════════════════════
   * 6. DONUT – Distribución últimos 6 meses
   * ════════════════════════════════════════════════════════════════════════ */
  var donutEl = document.querySelector('#feeDonutChart');
  if (donutEl) {
    var rawMeses    = strs(D.ventasPorMes, 'mes').slice(-6);
    var rawTotales  = nums(D.ventasPorMes, 'total').slice(-6);
    var paletteD    = ['#696cff','#28c76f','#00cfe8','#ff9f43','#ea5455','#7367f0'];

    new ApexCharts(donutEl, {
      chart: {
        height: 250,
        type: 'donut',
        toolbar: { show: false }
      },
      series: rawTotales,
      labels: rawMeses.map(fmtMes),
      colors: paletteD,
      plotOptions: {
        pie: {
          donut: {
            size: '68%',
            labels: {
              show: true,
              name: { show: true, fontSize: '12px' },
              value: {
                show: true,
                fontSize: '13px',
                fontWeight: 700,
                formatter: function (v) { return money(parseFloat(v)); }
              },
              total: {
                show: true,
                label: 'Total',
                formatter: function (w) {
                  var s = w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0);
                  return moneyCompact(s);
                }
              }
            }
          }
        }
      },
      dataLabels: { enabled: false },
      legend: {
        position: 'bottom',
        fontSize: '11px',
        labels: { colors: labelColor }
      },
      tooltip: {
        theme: tooltipTheme,
        y: { formatter: money }
      }
    }).render();
  }

  /* ════════════════════════════════════════════════════════════════════════
   * 7. BARRAS HORIZONTALES – análisis operativo (proveedor, destino, etc.)
   * ════════════════════════════════════════════════════════════════════════ */
  function renderHBarChart(selector, rows, color, seriesName) {
    var el = document.querySelector(selector);
    if (!el) return;
    var name = seriesName || 'Ventas';
    var list = Array.isArray(rows) && rows.length
      ? rows
      : [{ label: 'Sin datos en el período', total: 0 }];
    var labels = list.map(function (r) { return r.label || '—'; });
    var values = list.map(function (r) { return parseFloat(r.total) || 0; });
    var h = Math.max(240, labels.length * 32 + 72);
    new ApexCharts(el, {
      chart: {
        type: 'bar',
        height: h,
        toolbar: { show: false },
        animations: { speed: 650 }
      },
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 5,
          barHeight: '68%'
        }
      },
      colors: [color],
      dataLabels: {
        enabled: true,
        formatter: function (val) { return moneyCompact(val); },
        offsetX: 0,
        style: { fontSize: '10px', colors: [labelColor], fontWeight: 600 }
      },
      series: [{ name: name, data: values }],
      xaxis: {
        categories: labels,
        labels: {
          maxHeight: 140,
          style: { colors: labelColor, fontSize: '11px' }
        }
      },
      yaxis: {
        labels: {
          style: { colors: labelColor, fontSize: '11px' }
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 4,
        padding: { top: 0, right: 12, bottom: 0, left: 4 }
      },
      tooltip: {
        theme: tooltipTheme,
        y: { formatter: money }
      }
    }).render();
  }

  renderHBarChart('#chartVentasProveedor', D.ventasPorProveedor || [], '#696cff');
  renderHBarChart('#chartVentasDestino', D.ventasPorDestino || [], '#ea5455');
  renderHBarChart('#chartVentasRuta', D.ventasPorRuta || [], '#00cfe8');
  renderHBarChart('#chartVentasAerolinea', D.ventasPorAerolinea || [], '#ff9f43');
  renderHBarChart('#chartVentasCanal', D.ventasPorCanal || [], '#28c76f');
  renderHBarChart('#chartVentasClientes', D.ventasPorCliente || [], '#7367f0');

  /* Pestaña «FEE + comisiones»: render al mostrar (evita ancho 0 en Apex con pane oculto) */
  (function initBiTabFeeComisiones() {
    var rendered = false;
    var tabBtn = document.getElementById('tab-bi-fee-comisiones');
    function paint() {
      if (rendered) {
        window.dispatchEvent(new Event('resize'));
        return;
      }
      rendered = true;
      renderHBarChart('#chartFeeDestino', D.feePorDestino || [], '#28c76f', 'FEE');
      renderHBarChart('#chartFeeAerolinea', D.feePorAerolinea || [], '#28c76f', 'FEE');
      renderHBarChart('#chartComisionesDestino', D.comisionesPorDestino || [], '#00cfe8', 'Comisiones');
      renderHBarChart('#chartComisionesAerolinea', D.comisionesPorAerolinea || [], '#7367f0', 'Comisiones');
      setTimeout(function () {
        window.dispatchEvent(new Event('resize'));
      }, 120);
    }
    if (tabBtn) {
      tabBtn.addEventListener('shown.bs.tab', paint);
    }
  })();

  /* ════════════════════════════════════════════════════════════════════════
   * 8. RADIAL – progress bars (ranking de productos)
   * ════════════════════════════════════════════════════════════════════════ */
  document.querySelectorAll('.chart-progress').forEach(function (el) {
    var color  = (typeof config !== 'undefined' && config.colors)
      ? (config.colors[el.dataset.color] || '#696cff')
      : '#696cff';
    var series = parseFloat(el.dataset.series) || 0;
    new ApexCharts(el, {
      chart: { height: 48, width: 48, type: 'radialBar' },
      plotOptions: {
        radialBar: {
          hollow: { size: '33%' },
          dataLabels: { show: false },
          track: { background: borderColor }
        }
      },
      stroke: { lineCap: 'round' },
      colors: [color],
      grid: { padding: { top: -15, bottom: -15, left: -5, right: -15 } },
      series: [series],
      labels: ['']
    }).render();
  });

})();
