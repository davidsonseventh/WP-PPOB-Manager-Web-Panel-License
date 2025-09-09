<?php $snapToken = $_GET['token'] ?? ''; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Selesaikan Pembayaran</title>
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="GANTI_DENGAN_CLIENT_KEY_ANDA"></script>
</head>
<body>
    <h1>Mohon tunggu...</h1>
    <p>Membuka jendela pembayaran yang aman.</p>
    <button id="pay-button" style="display:none;">Bayar!</button>
    
    <script type="text/javascript">
      document.getElementById('pay-button').onclick = function(){
        snap.pay('<?php echo $snapToken; ?>', {
          onSuccess: function(result){ alert("Pembayaran berhasil!"); window.location.href = '/success.php?trx_id=' + result.order_id; },
          onPending: function(result){ alert("Menunggu pembayaran Anda!"); },
          onError: function(result){ alert("Pembayaran gagal!"); },
          onClose: function(){ alert('Anda menutup pop-up pembayaran.'); }
        })
      };
      // Langsung picu tombol klik saat halaman dimuat
      document.getElementById('pay-button').click();
    </script>
</body>
</html>