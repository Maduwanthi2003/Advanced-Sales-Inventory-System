document.addEventListener('DOMContentLoaded', function () {

    const data = window.stockReportData || {};
    const categories = Array.isArray(data.categories)
        ? data.categories
        : [];

    const statuses = Array.isArray(data.statuses)
        ? data.statuses
        : [];

    const categoryCanvas = document.getElementById('stockCategoryChart');
    const statusCanvas = document.getElementById('stockStatusChart');

    if (!categoryCanvas && !statusCanvas) {
        return;
    }

    if (typeof Chart === 'undefined') {
        console.warn('Chart.js is not loaded.');
        return;
    }

    const colors = [
        '#1479f6',
        '#06bd72',
        '#ffb000',
        '#f34853',
        '#9460f5',
        '#33b7d5',
        '#e879c6',
        '#6c8ba9'
    ];

    Chart.defaults.font.family = 'Segoe UI, Arial, sans-serif';
    Chart.defaults.color = '#5372a4';

    // STOCK BY CATEGORY
    if (categoryCanvas) {
        const names = categories.map(item => item.name);
        const values = categories.map(item => Number(item.count || 0));
        const total = values.reduce((sum, value) => sum + value, 0);

        new Chart(categoryCanvas, {
            type: 'doughnut',
            data: {
                labels: names.length ? names : ['No products'],
                datasets: [{
                    data: values.length ? values : [1],
                    backgroundColor: names.length
                        ? names.map((_, i) => colors[i % colors.length])
                        : ['#e7edf6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '0%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: total > 0
                    }
                }
            }
        });

        const legend = document.getElementById('stockCategoryLegend');

        if (legend) {
            if (total === 0) {
                legend.textContent = 'No stock data';
            } else {
                legend.innerHTML = '';

                categories.forEach((item, index) => {
                    const percentage = (
                        Number(item.count || 0) / total * 100
                    ).toFixed(0);

                    const row = document.createElement('div');
                    row.className = 'st-legend-row';

                    const dot = document.createElement('i');
                    dot.className = 'st-legend-color';
                    dot.style.backgroundColor = colors[index % colors.length];

                    const label = document.createElement('span');
                    label.textContent = `${item.name} (${percentage}%)`;

                    row.append(dot, label);
                    legend.appendChild(row);
                });
            }
        }
    }

    // STOCK STATUS OVERVIEW
    if (statusCanvas) {
        new Chart(statusCanvas, {
            type: 'bar',
            data: {
                labels: statuses.length
                    ? statuses.map(item => item.name)
                    : ['No products'],
                datasets: [
                    {
                        label: 'In Stock',
                        data: statuses.length
                            ? statuses.map(item => Number(item.in_stock || 0))
                            : [0],
                        backgroundColor: '#1269ed',
                        borderRadius: 2,
                        maxBarThickness: 25
                    },
                    {
                        label: 'Low Stock',
                        data: statuses.length
                            ? statuses.map(item => Number(item.low_stock || 0))
                            : [0],
                        backgroundColor: '#ffb300',
                        borderRadius: 2,
                        maxBarThickness: 25
                    },
                    {
                        label: 'Out of Stock',
                        data: statuses.length
                            ? statuses.map(item => Number(item.out_of_stock || 0))
                            : [0],
                        backgroundColor: '#f34750',
                        borderRadius: 2,
                        maxBarThickness: 25
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 10 },
                            maxRotation: 30
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: { size: 10 }
                        },
                        grid: {
                            color: '#eaf0fa'
                        }
                    }
                }
            }
        });
    }
});