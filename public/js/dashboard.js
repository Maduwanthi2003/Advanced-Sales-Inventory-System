
"use strict";

// Draw the dashboard sales and stock charts.
document.addEventListener("DOMContentLoaded", () => {
    const data = window.webAdminDashboard;

    if (!data) return;

    const canvas = document.getElementById("salesChart");
    const donut = document.getElementById("stockDonut");

    const months = [
        "Jan", "Feb", "Mar", "Apr",
        "May", "Jun", "Jul", "Aug",
        "Sep", "Oct", "Nov", "Dec"
    ];

    function drawSalesChart() {
        if (!canvas) return;

        const rect = canvas.getBoundingClientRect();

        if (!rect.width || !rect.height) return;

        const ratio = window.devicePixelRatio || 1;
        canvas.width = Math.round(rect.width * ratio);
        canvas.height = Math.round(rect.height * ratio);

        const ctx = canvas.getContext("2d");
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

        const w = rect.width;
        const h = rect.height;

        const left = 48;
        const right = 12;
        const top = 12;
        const bottom = 28;

        const chartW = w - left - right;
        const chartH = h - top - bottom;

        const values = data.monthlySales.map(Number);
        const maximum = Math.max(1, ...values);
        const maxY = maximum * 1.15;

        const x = i => left + (chartW * i / 11);
        const y = v => top + chartH - (v / maxY * chartH);

        ctx.clearRect(0, 0, w, h);
        ctx.font = "11px Segoe UI, Arial";
        ctx.textAlign = "right";

        for (let i = 0; i <= 4; i++) {
            const value = maxY * i / 4;
            const py = y(value);

            ctx.strokeStyle = "#e5edf8";
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(left, py);
            ctx.lineTo(w - right, py);
            ctx.stroke();

            ctx.fillStyle = "#6c85a7";
            ctx.fillText(
                Math.round(value).toLocaleString(),
                left - 9,
                py + 4
            );
        }

        ctx.textAlign = "center";

        months.forEach((month, i) => {
            ctx.fillStyle = "#6681a5";
            ctx.fillText(month, x(i), h - 6);
        });

        const gradient = ctx.createLinearGradient(0, top, 0, h);
        gradient.addColorStop(0, "rgba(8,120,255,0.22)");
        gradient.addColorStop(1, "rgba(8,120,255,0.01)");

        ctx.beginPath();
        ctx.moveTo(x(0), y(0));

        values.forEach((v, i) => {
            ctx.lineTo(x(i), y(v));
        });

        ctx.lineTo(x(11), y(0));
        ctx.closePath();

        ctx.fillStyle = gradient;
        ctx.fill();

        ctx.beginPath();

        values.forEach((v, i) => {
            if (i === 0) {
                ctx.moveTo(x(i), y(v));
            } else {
                ctx.lineTo(x(i), y(v));
            }
        });

        ctx.strokeStyle = "#0878ff";
        ctx.lineWidth = 2.5;
        ctx.lineJoin = "round";
        ctx.stroke();

        values.forEach((v, i) => {
            ctx.beginPath();
            ctx.arc(x(i), y(v), 4, 0, Math.PI * 2);
            ctx.fillStyle = "#0878ff";
            ctx.fill();
        });
    }

    function drawStockChart() {
        if (!donut) return;

        if (data.totalProducts === 0) {
            donut.style.background = "#e6edf7";
            return;
        }

        const stock = data.stock;
        const green = Number(stock.inStock);
        const blue = Number(stock.lowStock);
        const orange = Number(stock.outOfStock);

        const p1 = green;
        const p2 = p1 + blue;
        const p3 = p2 + orange;

        donut.style.background = `conic-gradient(
            #00c89b 0% ${p1}%,
            #0878ff ${p1}% ${p2}%,
            #ff9b2e ${p2}% ${p3}%,
            #ff3857 ${p3}% 100%
        )`;
    }

    drawSalesChart();
    drawStockChart();

    if (canvas && "ResizeObserver" in window) {
        new ResizeObserver(drawSalesChart).observe(canvas);
    } else {
        window.addEventListener("resize", drawSalesChart);
    }
});
