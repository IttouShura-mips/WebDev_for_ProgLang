<?php
session_start();
session_unset();
session_destroy();
header("Location: /WebDev_for_ProgLang/index.html");
exit();
?>