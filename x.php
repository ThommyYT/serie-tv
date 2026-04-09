<?php
session_start();

unset($_SESSION['listSearch']);
session_write_close();

header('Location: ./');
exit;