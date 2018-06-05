<?php

$orig = "I'll&nbsp;\"walk\" the <b>dog</b>&nbsp;now";

$a = htmlentities($orig);

$b = html_entity_decode($a);

echo $a;
echo '<br/>';
echo $b;
