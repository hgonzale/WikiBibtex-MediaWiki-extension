<?php

$bkslash = '\\';
$squote = "'";
$dquote = '"';

$convert = array();
$convert[$bkslash.$dquote.'a']='&auml;';
$convert[$bkslash.$dquote.'A']='&Auml;';
$convert[$bkslash.$dquote.'e']='&euml;';
$convert[$bkslash.$dquote.'E']='&Euml;';
$convert[$bkslash.$dquote.'i']='&iuml;';
$convert[$bkslash.$dquote.'I']='&Iuml;';
$convert[$bkslash.$dquote.'o']='&ouml;';
$convert[$bkslash.$dquote.'O']='&Ouml;';
$convert[$bkslash.$dquote.'u']='&uuml;';
$convert[$bkslash.$dquote.'U']='&Uuml;';

$convert[$bkslash.$squote.'a']='&aacute;';
$convert[$bkslash.$squote.'A']='&Aacute;';
$convert[$bkslash.$squote.'e']='&eacute;';
$convert[$bkslash.$squote.'E']='&Eacute;';
$convert[$bkslash.$squote.'i']='&iacute;';
$convert[$bkslash.$squote.'I']='&Iacute;';
$convert[$bkslash.$squote.'o']='&oacute;';
$convert[$bkslash.$squote.'O']='&Oacute;';
$convert[$bkslash.$squote.'u']='&uacute;';
$convert[$bkslash.$squote.'U']='&Uacute;';
$convert[$bkslash.$squote.'n']='&#324;';
$convert[$bkslash.$squote.'N']='&#323;';
$convert[$bkslash.$squote.'c']='&#263;';
$convert[$bkslash.$squote.'C']='&#262;';
$convert[$bkslash.$squote.'s']='&#347;';
$convert[$bkslash.$squote.'S']='&#346;';
$convert[$bkslash.$squote.'z']='&#378;';
$convert[$bkslash.$squote.'Z']='&#377;';

$convert[$bkslash.'l']='&#322;';
$convert[$bkslash.'L']='&#321;';

$convert[$bkslash.'v C']='&#268;';
$convert[$bkslash.'v c']='&#269;';
$convert[$bkslash.'v{c}']='&#269;';
$convert[$bkslash.'v{c}']='&#268;';
$convert[$bkslash.'v{d}']='&#271;';
$convert[$bkslash.'v{D}']='&#270;';
$convert[$bkslash.'v{e}']='&#283;';
$convert[$bkslash.'v{E}']='&#282;';
$convert[$bkslash.'v{n}']='&#328;';
$convert[$bkslash.'v{N}']='&#327;';
$convert[$bkslash.'v{r}']='&#345;';
$convert[$bkslash.'v{R}']='&#344;';
$convert[$bkslash.'v{s}']='&#353;';
$convert[$bkslash.'v{S}']='&#352;';
$convert[$bkslash.'v{t}']='&#357;';
$convert[$bkslash.'v{T}']='&#356;';
$convert[$bkslash.'v{z}']='&#382;';
$convert[$bkslash.'v{Z}']='&#381;';

$convert[$bkslash.'accent'.$squote.'27u']='&#367;';
$convert[$bkslash.'accent'.$squote.'27U']='&#366;';

$convert[$bkslash.'&']='&amp;';
// parsing in order, hence '---' must go before '--'
$convert['---']='&mdash;';
$convert['--']='&ndash;';

?>