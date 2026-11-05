<div class="admin-cover-preview-popup" aria-hidden="true">
        <img alt="Pratinjau sampul buku">
    </div>
    <script>
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

        const coverPreviewPopup = document.querySelector('.admin-cover-preview-popup');
        const coverPreviewImage = coverPreviewPopup.querySelector('img');

        function showCoverPreview(trigger) {
            coverPreviewImage.src = trigger.dataset.coverPreview;
            coverPreviewPopup.style.display = 'block';
            coverPreviewPopup.setAttribute('aria-hidden', 'false');

            const rect = trigger.getBoundingClientRect();
            const popupWidth = coverPreviewPopup.offsetWidth;
            const popupHeight = coverPreviewPopup.offsetHeight;
            const left = rect.right + popupWidth + 12 <= window.innerWidth
                ? rect.right + 12
                : Math.max(12, rect.left - popupWidth - 12);
            const top = Math.min(Math.max(12, rect.top), window.innerHeight - popupHeight - 12);
            coverPreviewPopup.style.left = `${left}px`;
            coverPreviewPopup.style.top = `${top}px`;
        }

        function hideCoverPreview() {
            coverPreviewPopup.style.display = 'none';
            coverPreviewPopup.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('.admin-cover-preview-trigger').forEach((trigger) => {
            trigger.addEventListener('mouseenter', () => showCoverPreview(trigger));
            trigger.addEventListener('mouseleave', hideCoverPreview);
            trigger.addEventListener('focus', () => showCoverPreview(trigger));
            trigger.addEventListener('blur', hideCoverPreview);
        });
        window.addEventListener('scroll', hideCoverPreview, true);
    </script>