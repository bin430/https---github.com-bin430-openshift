<?php
if (isset($url))
{
Header("HTTP/1.1 303 See Other");
Header("Location: $url");
exit;
}
?>