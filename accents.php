<?php

$convert = array();
    $convert['\\"a']='&auml;';
    $convert['\\"A']='&Auml;';
    $convert['\\"e']='&euml;';
    $convert['\\"E']='&Euml;';
    $convert['\\"i']='&iuml;';
    $convert['\\"I']='&Iuml;';
    $convert['\\"o']='&ouml;';
    $convert['\\"O']='&Ouml;';
    $convert['\\"u']='&uuml;';
    $convert['\\"U']='&Uuml;';

    $convert['\\\'a']='&aacute;';
    $convert['\\\'A']='&Aacute;';
    $convert['\\\'e']='&eacute;';
    $convert['\\\'E']='&Eacute;';
    $convert['\\\'i']='&iacute;';
    $convert['\\\'I']='&Iacute;';
    $convert['\\\'o']='&oacute;';
    $convert['\\\'O']='&Oacute;';
    $convert['\\\'u']='&uacute;';
    $convert['\\\'U']='&Uacute;';
    $convert['\\\'n']='&#324;';
    $convert['\\\'N']='&#323;';
    $convert['\\\'c']='&#263;';
    $convert['\\\'C']='&#262;';
    $convert['\\v C']='&#268;';
    $convert['\\v c']='&#269;';
    $convert['\\\'s']='&#347;';
    $convert['\\\'S']='&#346;';
    $convert['\\\'z']='&#378;';
    $convert['\\\'Z']='&#377;';

    $convert['\\l']='&#322;';
    $convert['\\L']='&#321;';

    $convert['\\v{c}']='&#269;';
    $convert['\\v{c}']='&#268;';
    $convert['\\v{d}']='&#271;';
    $convert['\\v{D}']='&#270;';
    $convert['\\v{e}']='&#283;';
    $convert['\\v{E}']='&#282;';
    $convert['\\v{n}']='&#328;';
    $convert['\\v{N}']='&#327;';
    $convert['\\v{r}']='&#345;';
    $convert['\\v{R}']='&#344;';
    $convert['\\v{s}']='&#353;';
    $convert['\\v{S}']='&#352;';
    $convert['\\v{t}']='&#357;';
    $convert['\\v{T}']='&#356;';
    $convert['\\v{z}']='&#382;';
    $convert['\\v{Z}']='&#381;';

    $convert['\\accent\'27u']='&#367;';
    $convert['\\accent\'27U']='&#366;';

    $convert['\&']='&amp;';
    // parsing in order, '---' must go before '--'
    $convert['---']='&mdash;';
    $convert['--']='&ndash;';

?>