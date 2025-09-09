document.addEventListener('DOMContentLoaded', function() {
    const pricePerYearEl = document.getElementById('price_per_year');
    const durationValueEl = document.getElementById('duration_value');
    const durationUnitEl = document.getElementById('duration_unit');
    const totalPriceEl = document.getElementById('total_price');

    // Pastikan semua elemen ada sebelum melanjutkan
    if (!pricePerYearEl || !durationValueEl || !durationUnitEl || !totalPriceEl) {
        return;
    }

    const pricePerYear = parseFloat(pricePerYearEl.value);

    function calculatePrice() {
        const value = parseInt(durationValueEl.value) || 0;
        const unit = durationUnitEl.value;
        let finalPrice = 0;

        if (isNaN(pricePerYear) || value <= 0) {
            totalPriceEl.textContent = 'Rp 0';
            return;
        }

        if (unit === 'years') {
            finalPrice = pricePerYear * value;
        } else if (unit === 'months') {
            // Dibulatkan ke atas ke kelipatan 1000 terdekat
            finalPrice = Math.ceil(((pricePerYear / 12) * value) / 1000) * 1000;
        } else if (unit === 'days') {
            // Dibulatkan ke atas ke kelipatan 1000 terdekat
            finalPrice = Math.ceil(((pricePerYear / 365) * value) / 1000) * 1000;
        }

        // Format angka ke dalam format Rupiah
        totalPriceEl.textContent = 'Rp ' + finalPrice.toLocaleString('id-ID');
    }

    // Panggil fungsi calculatePrice saat nilai diubah
    durationValueEl.addEventListener('input', calculatePrice);
    durationUnitEl.addEventListener('change', calculatePrice);
});