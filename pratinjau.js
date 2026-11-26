        document.querySelectorAll('.admin-chart-panel').forEach((chartPanel) => {
            const chartTooltip = chartPanel.querySelector('.admin-chart-tooltip');
            if (!chartTooltip) return;

            const tooltipMonth = chartTooltip.querySelector('.admin-chart-tooltip-month');
            const firstRow = chartTooltip.querySelector('.first');
            const secondRow = chartTooltip.querySelector('.second');

            function showChartTooltip(point, clientX, clientY) {
                tooltipMonth.textContent = point.dataset.month;
                firstRow.querySelector('b').textContent = point.dataset.firstLabel;
                firstRow.querySelector('.admin-chart-tooltip-first').textContent =
                    Number(point.dataset.firstValue).toLocaleString();
                firstRow.className = `first ${point.dataset.firstTone}`;
                secondRow.querySelector('b').textContent = point.dataset.secondLabel;
                secondRow.querySelector('.admin-chart-tooltip-second').textContent =
                    Number(point.dataset.secondValue).toLocaleString();
                secondRow.className = `second ${point.dataset.secondTone}`;
                chartTooltip.hidden = false;

                const panelRect = chartPanel.getBoundingClientRect();
                const pointRect = point.getBoundingClientRect();
                const x = clientX ?? pointRect.left + pointRect.width / 2;
                const y = clientY ?? pointRect.top + 18;
                const left = Math.min(
                    Math.max(8, x - panelRect.left + 12),
                    panelRect.width - chartTooltip.offsetWidth - 8
                );
                const top = Math.min(
                    Math.max(8, y - panelRect.top + 12),
                    panelRect.height - chartTooltip.offsetHeight - 8
                );
                chartTooltip.style.left = `${left}px`;
                chartTooltip.style.top = `${top}px`;
            }

            function hideChartTooltip() {
                chartTooltip.hidden = true;
            }

            chartPanel.querySelectorAll('.admin-chart-hitbox').forEach((point) => {
                point.addEventListener('pointerenter', (event) => {
                    showChartTooltip(point, event.clientX, event.clientY);
                });
                point.addEventListener('pointermove', (event) => {
                    showChartTooltip(point, event.clientX, event.clientY);
                });
                point.addEventListener('pointerleave', hideChartTooltip);
                point.addEventListener('focus', () => showChartTooltip(point));
                point.addEventListener('blur', hideChartTooltip);
            });
        });
