<?php

// 在admin_header.php中
$bootstrapJS = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/ticket/assets/js/bootstrap.bundle.min.js');
echo $bootstrapJS;
echo "<script>$bootstrapJS</script>";
