<?php

/**
 * default.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/3 12:06
 */
?>
<!DOCTYPE html>
<html lang="zh_CN">
<head>
    <title>Default Page</title>
</head>
<body>
<a href="<?=$context->getRouter()->generate('home') ?>">Home</a>
<p>
    Default Page
</p>
</body>
</html>
