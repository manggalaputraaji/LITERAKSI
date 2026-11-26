import bwipjs from 'bwip-js';

// Skrip hanya berjalan pada halaman yang menyediakan canvas Data Matrix.
document.querySelectorAll('[data-matrix-value]').forEach((canvas) => {
    const status = document.getElementById('data-matrix-render-status');
    try {
        bwipjs.toCanvas(canvas, {
            bcid: 'datamatrix',
            text: canvas.dataset.matrixValue || '',
            scale: 6,
            padding: 12,
            backgroundcolor: 'FFFFFF'
        });
        if (status) status.textContent = 'Tunjukkan kode ini kepada petugas untuk dipindai.';
    } catch (error) {
        // Jika gambar kode gagal dibuat, arahkan pemakai ke alur konfirmasi manual.
        console.error('Unable to render Data Matrix.', error);
        canvas.hidden = true;
        if (status) status.textContent = 'Kode tidak dapat ditampilkan. Minta petugas menggunakan konfirmasi manual.';
    }
});
