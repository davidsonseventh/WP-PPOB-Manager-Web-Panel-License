(function ($) {
    'use strict';
    $(document).ready(function () {
        
        // --- FUNGSI SINKRONISASI PRODUK ---
        $('#wppob-sync-products').on('click', function (e) {
            e.preventDefault();
            const button = $(this);
            const spinner = button.next('.spinner');
            button.prop('disabled', true);
            spinner.addClass('is-active');
            $.post(wppob_admin_params.ajax_url, {
                action: 'wppob_sync_products',
                nonce: wppob_admin_params.nonce
            })
            .done(function (response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    alert('Gagal: ' + (response.data.message || 'Respons tidak diketahui.'));
                }
            })
            .fail(function () { alert('Terjadi kesalahan koneksi saat sinkronisasi.'); })
            .always(function () { button.prop('disabled', false); spinner.removeClass('is-active'); });
        });

        // --- FUNGSI UPLOAD GAMBAR (UNTUK SEMUA UPLOADER) ---
        $(document).on('click', '.wppob-upload-btn', function(e){
            e.preventDefault();
            const button = $(this);
            const uploaderWrapper = button.closest('.wppob-image-uploader');
            const frame = wp.media({ title: 'Pilih Gambar', button: { text: 'Gunakan Gambar Ini' }, multiple: false });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                // PERBAIKAN PENTING: Picu event 'change' secara manual
                uploaderWrapper.find('.wppob-image-id').val(attachment.id).trigger('change');
                uploaderWrapper.find('.wppob-image-preview').attr('src', attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url).show();
                button.hide();
                uploaderWrapper.find('.wppob-remove-btn').show();
            });
            frame.open();
        });

        $(document).on('click', '.wppob-remove-btn', function(e){
            e.preventDefault();
            const button = $(this);
            const uploaderWrapper = button.closest('.wppob-image-uploader');
            // PERBAIKAN PENTING: Picu event 'change' secara manual
            uploaderWrapper.find('.wppob-image-id').val('').trigger('change');
            uploaderWrapper.find('.wppob-image-preview').attr('src', '').hide();
            button.hide();
            button.siblings('.wppob-upload-btn').show();
        });

        // --- FUNGSI DRAG & DROP KATEGORI ---
        const organizer = $('#wppob-category-organizer');
        if (organizer.length) {
            const spinner = organizer.parent().find('.spinner');
            organizer.find('ol.wppob-sortable-list').sortable({
                placeholder: 'wppob-sortable-placeholder',
                connectWith: '.wppob-sortable-list',
                handle: '.dashicons-move',
                update: function(event, ui) {
                    spinner.addClass('is-active');
                    const data = [];
                    organizer.find('li').each(function(index, elem) {
                        const item = $(elem);
                        const parent = item.parent('ol').parent('li');
                        data.push({ id: item.attr('id'), parent_id: parent.length ? parent.attr('id') : null });
                    });
                    $.post(wppob_admin_params.ajax_url, {
                        action: 'wppob_update_category_order',
                        nonce: wppob_admin_params.nonce,
                        order: data
                    }).done(function() {
                        spinner.removeClass('is-active');
                    });
                }
            }).disableSelection();

            organizer.on('click', '.wppob-toggle-children', function() {
                const button = $(this);
                const childrenOl = button.closest('li').children('ol.wppob-sortable-list');
                if (childrenOl.is(':visible')) {
                    childrenOl.slideUp('fast');
                    button.removeClass('dashicons-minus').addClass('dashicons-plus-alt2');
                } else {
                    childrenOl.slideDown('fast');
                    button.removeClass('dashicons-plus-alt2').addClass('dashicons-minus');
                }
            });
        }
        
        // --- FUNGSI DRAG & DROP PRODUK ---
        const sortableProducts = $('#wppob-sortable-products');
        if (sortableProducts.length) {
            sortableProducts.sortable({
                placeholder: 'wppob-sortable-placeholder',
                handle: '.dashicons-move'
            }).disableSelection();

            sortableProducts.on('change', 'input[type="checkbox"]', function() {
                const li = $(this).closest('li');
                if ($(this).is(':checked')) {
                    li.prependTo(sortableProducts);
                } else {
                    li.appendTo(sortableProducts);
                }
            });
        }

        // --- FUNGSI EDIT GAMBAR MASSAL (VERSI FINAL) ---
        const bulkEditor = $('#wppob-bulk-image-editor');
        if (bulkEditor.length) {
            const applyBtn = $('#wppob-apply-bulk-image');
            const spinner = applyBtn.next('.spinner');
            const imageIdInput = $('#wppob-new-image-id');
            const productCheckboxes = $('#bulk-product-list .wppob-product-check');

            function checkBulkButtonState() {
                const productsSelected = productCheckboxes.is(':checked');
                const imageSelected = imageIdInput.val() !== '' && imageIdInput.val() !== '0';
                
                applyBtn.prop('disabled', !(productsSelected && imageSelected));
            }
            
            productCheckboxes.on('change', checkBulkButtonState);
            imageIdInput.on('change', checkBulkButtonState);

            applyBtn.on('click', function() {
                if (!confirm('Anda yakin ingin mengganti gambar untuk semua produk yang dipilih?')) return;
                
                const product_ids = productCheckboxes.filter(':checked').map(function(){ return $(this).val(); }).get();
                const image_id = imageIdInput.val();

                spinner.addClass('is-active');
                applyBtn.prop('disabled', true);

                $.post(wppob_admin_params.ajax_url, {
                    action: 'wppob_bulk_update_images',
                    nonce: wppob_admin_params.nonce,
                    product_ids: product_ids,
                    image_id: image_id
                }).done(function(response){
                    if (response.success) {
                        alert(response.data.message);
                        window.location.reload();
                    } else {
                        alert('Gagal: ' + response.data.message);
                    }
                }).fail(function(){
                    alert('Terjadi kesalahan koneksi.');
                }).always(function(){
                    spinner.removeClass('is-active');
                    checkBulkButtonState();
                });
            });
            checkBulkButtonState();
        }
    });
})(jQuery);