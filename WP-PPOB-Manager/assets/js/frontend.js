(function ($) {
    'use strict';

    $(document).ready(function () {
        const app = $('#wppob-app-container');
        if (!app.length) return;

        const contentArea = app.find('#wppob-app-content');
        const title = app.find('#wppob-app-title');
        const backBtn = app.find('#wppob-app-back-btn');
        const loader = app.find('#wppob-app-loader');
        
        let navigationHistory = [];

        contentArea.on('click', '.wppob-category-item', function (e) {
            const item = $(this);
            if (item.is('a')) { return; } // Jangan hentikan jika item adalah link produk
            e.preventDefault();

            const categoryId = item.data('id');
            const categoryName = item.data('name');
            
            navigationHistory.push({ title: title.text(), content: contentArea.html() });
            loadCategoryContent(categoryId, categoryName);
        });

        backBtn.on('click', function (e) {
            e.preventDefault();
            if (navigationHistory.length > 0) {
                const lastState = navigationHistory.pop();
                title.text(lastState.title);
                contentArea.html(lastState.content);
                contentArea.removeClass('list-view').addClass('grid-view');
                if (navigationHistory.length === 0) {
                    backBtn.hide();
                }
            }
        });

        function loadCategoryContent(categoryId, categoryName) {
            title.text(categoryName);
            contentArea.empty();
            loader.show();
            backBtn.show();

            $.ajax({
                url: wppob_frontend_ajax.ajax_url, type: 'POST',
                data: { action: 'wppob_get_category_content', nonce: wppob_frontend_ajax.nonce, category_id: categoryId },
                success: function (response) {
                    if (response.success) { renderContent(response.data); } 
                    else { contentArea.html('<p>Gagal memuat konten.</p>'); }
                },
                error: function () { contentArea.html('<p>Terjadi kesalahan koneksi.</p>'); },
                complete: function () { loader.hide(); }
            });
        }

        function renderContent(data) {
            contentArea.empty();
            
            contentArea.removeClass('grid-view list-view').addClass(data.display_mode + '-view');
            
            if (data.sub_categories && data.sub_categories.length > 0) {
                data.sub_categories.forEach(function (cat) {
                    const style = `width: ${cat.image_size_px}px; height: ${cat.image_size_px}px; border-radius: ${cat.border_radius}%;`;
                    let itemHTML = `<div class="wppob-category-item" data-id="${cat.id}" data-name="${cat.name}">`;
                    if (cat.display_style !== 'text_only') {
                        itemHTML += `<div class="wppob-category-image-wrapper" style="${style}"><img src="${cat.image_url}" alt="${cat.name}"></div>`;
                    }
                    if (cat.display_style !== 'image_only') {
                        itemHTML += `<span class="wppob-category-name">${cat.name}</span>`;
                    }
                    itemHTML += `</div>`;
                    contentArea.append(itemHTML);
                });
            }

            if (data.products && data.products.length > 0) {
                data.products.forEach(function (prod) {
                    const style = `width: 80px; height: 80px; border-radius: 15%;`;
                    let itemHTML = `<a href="${prod.permalink}" class="wppob-category-item product-item">`;
                    itemHTML += `<div class="wppob-category-image-wrapper" style="${style}"><img src="${prod.image_url}" alt="${prod.name}"></div>`;
                    itemHTML += `<span class="wppob-category-name">${prod.name}</span>`;
                    itemHTML += `<span class="wppob-product-price">${prod.price_html}</span>`;
                    itemHTML += `</a>`;
                    contentArea.append(itemHTML);
                });
            }
        }
    });
})(jQuery);
