/**
 * <canvas x-data="chart('line', data, options)"> wrapper around Chart.js (lazy loaded).
 */
const css = (name, alpha = 1) => {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    if (!value) return undefined;
    return alpha === 1 ? `rgb(${value})` : `rgb(${value} / ${alpha})`;
};

const palette = () => [
    css('--primary-500'),
    'rgb(16 185 129)',
    'rgb(245 158 11)',
    'rgb(244 63 94)',
    'rgb(14 165 233)',
    'rgb(139 92 246)',
    'rgb(100 116 139)',
];

export default (type, data, options = {}) => ({
    instance: null,

    async init() {
        const { default: Chart } = await import('chart.js/auto');
        this.Chart = Chart;
        this.render();
        window.addEventListener('theme-changed', () => this.render());
    },

    render() {
        if (this.instance) {
            this.instance.destroy();
        }

        const dark = document.documentElement.classList.contains('dark');
        const grid = dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        const text = dark ? '#9ca3af' : '#6b7280';
        const colors = palette();
        const money = options.money;

        const datasets = data.datasets.map((dataset, index) => {
            const color = dataset.color || colors[index % colors.length];
            const isPie = ['doughnut', 'pie', 'polarArea'].includes(type);

            return {
                borderWidth: isPie ? 0 : 2,
                tension: 0.35,
                pointRadius: 2,
                fill: dataset.fill ?? false,
                ...dataset,
                borderColor: isPie ? undefined : color,
                backgroundColor: isPie
                    ? dataset.colors || colors
                    : dataset.fill
                      ? color.replace(')', ' / 0.12)').replace('rgb(', 'rgb(')
                      : color,
                borderRadius: type === 'bar' ? 6 : undefined,
            };
        });

        this.instance = new this.Chart(this.$el, {
            type,
            data: { labels: data.labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: options.legend ?? datasets.length > 1,
                        position: 'bottom',
                        labels: { color: text, usePointStyle: true, boxWidth: 8 },
                    },
                    tooltip: {
                        callbacks: money
                            ? { label: (ctx) => `${ctx.dataset.label ? ctx.dataset.label + ': ' : ''}${money}${Number(ctx.parsed.y ?? ctx.parsed).toLocaleString(undefined, { maximumFractionDigits: 2 })}` }
                            : {},
                    },
                },
                scales: ['doughnut', 'pie', 'polarArea'].includes(type)
                    ? {}
                    : {
                          x: { grid: { display: false }, ticks: { color: text } },
                          y: { grid: { color: grid }, ticks: { color: text }, beginAtZero: true },
                      },
                cutout: type === 'doughnut' ? '68%' : undefined,
            },
        });
    },
});
