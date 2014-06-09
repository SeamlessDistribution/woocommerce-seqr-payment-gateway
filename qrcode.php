<?php

require_once(realpath($_SERVER["DOCUMENT_ROOT"]) . '/wp-load.php');
require_once('includes/phpqrcode.php');

if (isset($_GET['order'])) {
    $qrCode = get_post_meta($_GET['order'], 'SEQR Invoice QR Code', true);
    QRcode::png($qrCode, false, QR_ECLEVEL_L, 6, 0);
} else {
    QRcode::png('HTTP://SEQR.COM', false, QR_ECLEVEL_L, 6, 0);
}
