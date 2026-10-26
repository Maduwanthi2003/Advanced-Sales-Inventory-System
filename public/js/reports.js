document.addEventListener('DOMContentLoaded', function () {

    const data = window.salesReportData || {};

    if (typeof Chart === 'undefined') {
        console.warn('Chart.js is not loaded.');
        return;
    }

    const year = Number(data.year || new Date().getFullYear());
    const monthly = data.monthly || {};
    const products = Array.isArray(data.products) ? data.products : [];

    const months = [
        'Jan', 'Feb', 'Mar', 'Apr',
        'May', 'Jun', 'Jul', 'Aug',
        'Sep', 'Oct', 'Nov', 'Dec'
    ];

    const formatMoney = value =>
        'Rs. ' + Number(value || 0).toLocaleString('en-LK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    const getYearValues = selectedYear => {
        const rows = monthly[selectedYear] || {};

        return months.map((month, index) => {
            return Number(rows[index + 1] || 0);
        });
    };

    Chart.defaults.font.family = 'Segoe UI, Arial, sans-serif';
    Chart.defaults.color = '#5876a6';

    // MONTHLY SALES - CURRENT VS PREVIOUS YEAR
    const monthlyCanvas = document.getElementById('monthlySalesChart');

    if (monthlyCanvas) {
        new Chart(monthlyCanvas, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: String(year),
                        data: getYearValues(year),
                        backgroundColor: '#1169ed',
                        borderRadius: 3,
                        maxBarThickness: 18
                    },
                    {
                        label: String(year - 1),
                        data: getYearValues(year - 1),
                        backgroundColor: '#9ac4ff',
                        borderRadius: 3,
                        maxBarThickness: 18
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: context =>
                                context.dataset.label + ': ' +
                                formatMoney(context.raw)
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: { size: 10 },
                            callback: value =>
                                Number(value).toLocaleString('en-LK')
                        },
                        grid: { color: '#e9f0fa' }
                    }
                }
            }
        });
    }

    // TOP 5 PRODUCTS
    const productsCanvas = document.getElementById('topProductsChart');

    if (productsCanvas) {
        const hasProducts = products.length > 0;

        new Chart(productsCanvas, {
            type: 'bar',
            data: {
                labels: hasProducts
                    ? products.map(product => product.name)
                    : ['No sales data'],
                datasets: [{
                    label: 'Sales (Rs.)',
                    data: hasProducts
                        ? products.map(product => Number(product.total_sales || 0))
                        : [0],
                    backgroundColor: '#3589ff',
                    borderRadius: 4,
                    maxBarThickness: 19
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: context => formatMoney(context.raw)
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }
});