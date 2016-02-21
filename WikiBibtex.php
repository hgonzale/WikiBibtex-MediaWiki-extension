<?php
/*
  Extension of MediaWiki for creating a bibliography from bibtex entries.
  Copyright (C) 2014 Victoria Gitman

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
*/

/*
  Authors:
  =======

  The WikiBibtex extension was developed by Victoria Gitman.
  It is a modified combination of two pre-existing extensions:
  Biblio, written by Martin Jambon and others and Bibtex,
  written by Jean-Yves Didier and Kinh Tieu. Most of the code
  comes from these two original extensions. The chief modifications
  to the Biblio class consist of removing non-bibtex processing code,
  updating it to comply with new MediaWiki standards, and adding error
  catching mechanisms (e.g. when there is no entry for a cited key).
  Minor modifications to the Bibtex class consist of improving
  author name parsing, adding the ability to parse math in titles,
  adding the ability to process brackets in title and author fields.
  A major modification is that the bibtex code no longer appears in
  a pop-up but below the bibliographical item.

  Setup instructions
  ==================

  Brief instructions are given here. For more info, see
  http://boolesrings.org/victoriagitman/2014/06/06/a-wikibibtex-extension-for-mediawiki

  Requirements:

  To activate the extension, you have to:

  1) place the following files

  1) WikiBibtex.php
  2) bibtex.php
  3) accents.php
  4) toggle.js

  in the 'extensions' subdirectory of your
  MediaWiki installation.

  2) Update your LocalSettings.php file with this line:
  require_once("extensions/WikiBibtex.php");

  :

  If $BiblioForce is set to false, references that are present
  in the <biblio> section (possibly included from a bibtex database page)
  are listed only if they are actually cited in the text or
  forced using <nocite>.


  Features:
  ========

  This module provides tags "cite" and "biblio".
  "cite" tags indicate a citation in the text, one or several keys, separated by a comma, are given.
  These keys must be defined in the "biblio" section.
  There is at most one "biblio" section on the page and it must come after
  the last citation. The <biblio> tag can contain either the bibtex entry itself or a link to an
  interwiki 'database' page containing bibtex entries. A bibtex 'database' page is trying to
  simulate a bibtex file. It must contain only bibtex entries (and HTML comments).

  Example:

  some wikitext
  The result was published in <cite>Gitman:article2014</cite>, and
  similar results appeared in <cite>Hamkins:article2013</cite>
  some wikitext
  <biblio>
  @article{Gitman:article2014,...
  ...
  }
  @[[Bibtex]]
  }
  </biblio>

  The default is to display only cited items. To force all items in the database to appear, set the
  'force' parameter to 'true' in the <biblio> tag, e.g. write <biblio force=true>.

*/




// Extension credits that will show up on Special:Version
$wgExtensionCredits['validextensionclass'][] = array(
    'path'           => __FILE__,
    'name'           => 'WikiBibtex',
    'version'        => '1.0+',
    'author'         => 'Victoria Gitman',
    'url'            => 'http://boolesrings.org/victoriagitman/2014/06/06/a-wikibibtex-extension-for-mediawiki/',
    'descriptionmsg' => 'Creates citations and bibliography from bibtex entries' // Message key in i18n file.
);


// Bibtex parser
require_once($IP .'/extensions/WikiBibtex/bibtex.php');


// Print only cited bibtex entries
$BiblioForce = false;
$BiblioPrefix = "";


// Registration of this extension
$wgExtensionFunctions[] = "WikiBibtexExtension";

function WikiBibtexExtension()
{
    global $wgParser;
    // register the extension with the WikiText parser
    // the first parameter is the name of the new tag.
    // In this case it defines the tag <example> ... </example>
    // the second parameter is the callback function for
    // processing the text between the tags
    $wgParser->setHook("cite", "Biblio_render_cite");
    $wgParser->setHook("nocite", "Biblio_render_nocite");
    $wgParser->setHook("biblio", "Biblio_render_biblio");
}


class Biblio
{
    var $errors = array(); // records parsing errors

    // Recover a link to an internal wiki page:
    // take an expression of the form [blah] and return blah
    function unbracket($link)
    {
        $matches = array();
        preg_match ('/\[([^\]]*)\]/', $link, $matches);
        return $matches[1];
    }


    // Read the source code of a local wiki page
    function fetch_page($title)
    {
        $rev = Revision::newFromTitle($title);

        // if page does not exist report error
        if ($rev != NULL) {
            // this will need to change to getContent() eventually (getText is deprecated)!!!
            return $rev->getText();
        } else {
            $this->errors[] = "<strong>Error</strong>: Bibliography page <strong>".$title
                            ." </strong>does not exist.";
            return NULL;
        }
    }


    // Management of the citation indices
    // (order in which they appear in the text)

    // enumerates bibtex keys in order cited
    // if $BiblioForce = true, then uncited entries are continued in alphabetical order
    var $Citations = array();

    // Find the number of the reference if already cited or,
    // if $create is true then assign a new one, otherwise return -1.
    function CitationIndex($key, $create = true, $prefix)
    {
        if (!array_key_exists($prefix, $this->Citations))
            $this->Citations[$prefix] = array();

        if (array_key_exists($key, $this->Citations[$prefix])) {
            // ref was already cited
            return $this->Citations[$prefix][$key];
        } elseif ($create) {
            // ref was not cited yet
            $index = count($this->Citations[$prefix]) + 1;
            $this->Citations[$prefix][$key] = $index;
            return $index;
        } else {
            return -1;
        }
    }


    // General formatting functions

    function HtmlLink($url, $text)
    {
        return "<a href=\"$url\">$text</a>";
    }

    function error($s)
    {
        return "<span class=error>$s</span>";
    }

    function errorbox($s)
    {
        return "<div style='float:none;' class=errorbox>$s</div>";
    }

    // split entries using '@'
    function split_biblio($input)
    {
        // note that this removes @ from entry and will need to be added later
        return preg_split("/\s*@\s*/", $input, -1, PREG_SPLIT_NO_EMPTY);
    }

    // expandList expands references and links in the <biblio> tags
    function expandList($list)
    {
        $result = array();
        foreach ($list as $ref) {
            $matches = array();

            // links to pages containing bibtex entries must have form "bibtex:blah"
            preg_match ('/\[(\[[^\]]*\])\]/', $ref, $matches);

            if (isset($matches[1])) {
                // It is a link to a list of references
                $link = $matches[1];
                $name = $this->unbracket($link);
                $title = Title::newFromText($name);
                $x = $this->fetch_page($title);

                // if linked to page exists
                if ($x != NULL) {
                    //remove comments from start of bibtex database
                    $x=preg_replace('/<!--[\S|\s]*?-->/','',$x);

                    foreach ($this->split_biblio($x) as $item) {
                        $result[] = array('ref' => $item);
                    }
                }
            } else
                // A single reference
                $result[] = array('ref' => $ref);
        }
        return $result;
    }

    function parseBiblio($list)
    {
        $result = array();

        foreach ($list as $ref) {
            $matches = array();

            // bibtex entry should have format "blah {key, }"
            if (preg_match ('/\s*[A-Za-z]+\s*{\s*([A-Za-z_0-9:]+)\s*,.+}$/sm',
                            $ref['ref'],$matches) != 0) {
                $key = $matches[1];
                $bibtex = '@'.$ref['ref']; // add '@' back into entry
                $x = array('key' => $key);
                $x['bibtex'] = $bibtex;
                $result[] = $x;
            } else {
                $this->errors[] = "<strong>Error</strong>: Entry <strong>".$ref['ref']
                                ." </strong>is invalid.";
            }
        }
        return $result;
    }


    // Conversion of the contents of <cite> tags
    function render_cite($input, $render = true, $prefix)
    {
        $keys = array();
        // split on anything that is not a possible character in key
        $keys = preg_split('/\s*,\s*/', $input, -1, PREG_SPLIT_NO_EMPTY);
        $list = array();
        foreach ($keys as $key) {
            $key = trim($key);
            $index = $this->CitationIndex($key, true, $prefix);
            $list[] = array($index, $key);
        }
        if ($render) {
            sort($list);
            $links = array();
            foreach ($list as $ent) {
                $link = $this->HtmlLink("#bibkey_".$ent[1], $prefix. $ent[0]);
                $links[] = $link;
            }

            return "[". implode(", ", $links) ."]";
        } else
            return "";
    }

    // Conversion of the contents of <nocite> tags
    function render_nocite($input, $force, $prefix)
    {
        return $this->render_cite($input, false, $prefix);
    }

    // Conversion of the contents of <biblio> tags
    // $parser is passed forward to parse math content in entries
    function render_biblio($input, $parser, $force, $prefix)
    {
        $refs = array();
        $keys = array();

        $list = $this->expandList($this->split_biblio($input));
        $entries = $this->parseBiblio($list);

        foreach ($entries as $ref) {
            $key = $ref['key'];
            $bibtex = $ref['bibtex'];
            $index = $this->CitationIndex($key, $force, $prefix);
            if ($index >= 0) {
                $text = renderBibtex($bibtex, $index, $parser, $prefix);
                $refs[] = array('index' => $index,
                                'key' => $key,
                                'text' => $text);
                $keys[] = $key;
            }

        }

        // remove processed keys from Citations array
        foreach ($keys as $key)
            unset($this->Citations[$prefix][$key]);

        // print error for each unprocessed key
        foreach ($this->Citations[$prefix] as $key=>$index) {
            $text = $this->errorbox("<strong>Error:</strong> entry with key = ".$key
                                    ." does not exist");
            $refs[] = array('index' => $index,
                            'key' => $key,
                            'text' => $text);
        }

        sort($refs);
        reset($refs);

        // create errors output
        $err_msg = "";
        foreach ($this->errors as $error) {
            $err_msg .= $this->errorbox($error);
        }

        // create output array from $refs
        $result = array();

        foreach ($refs as $ref) {
            $index = $ref['index'];
            $key = $ref['key'];
            $text = $ref['text'];
            $vkey = '<span style="color:#aaa">['.$key.']</span>';
            $result[] = '<li id="bibkey_'.$key.'"'
                      . 'style="list-style:none;padding-left:0.5em;text-indent:-3em;">'
                      . '['. $prefix . $index . ']&nbsp;' . $text .'</li>';
        }

        return $err_msg .'<ol>' . implode ("", $result) . '</ol>';
    }
}


$Biblio = new Biblio;


// Conversion of the contents of <cite> tags
function Biblio_render_cite($input, array $params, Parser $parser = null, PPFrame $frame)
{
    global $Biblio, $BiblioPrefix;

    $prefix = @isset($params['prefix']) ?
            $params['prefix'] : $BiblioPrefix;

    return $Biblio->render_cite($input, true, $prefix);
}

// Conversion of the contents of <nocite> tags
function Biblio_render_nocite($input, array $params, Parser $parser = null, PPFrame $frame)
{
    global $Biblio, $BiblioPrefix;

    $prefix = @isset($params['prefix']) ?
            $params['prefix'] : $BiblioPrefix;

    return $Biblio->render_nocite($input, false, $prefix);
}

// Conversion of the contents of <biblio> tags
function Biblio_render_biblio($input, array $params, Parser $parser = null, PPFrame $frame)
{
    global $Biblio, $BiblioForce, $BiblioPrefix;
    $force = @isset($params['force']) ?
           ($params['force'] == "true") : $BiblioForce;

    $prefix = @isset($params['prefix']) ?
            $params['prefix'] : $BiblioPrefix;

    //$parser is passed forward to parse math content in entries
    return $Biblio->render_biblio($input, $parser, $force, $prefix);
}
?>
