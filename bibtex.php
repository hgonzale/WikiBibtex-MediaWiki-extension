<?php

//foreign accents tranlated from Latex to HTLM
include_once 'accents.php';

// need to add error catching

// parses a single bibtex entry
class BibTex
{
    var $type_specifier; // e.g., inproceedings
    var $fields; // array of field names and field text, e.g., author={Victoria Gitman}
    var $where_published;
    var $publishing_details;
    var $content; // original bibtex entry

    // class constructor parses bibtex entry
    function BibTex($contents, $parser)
    {
        $tmpcnt = $contents;

        // remove \n and \r from entry
        $tmpcnt = $this->cleanLi($tmpcnt);

        //initilize class variables
        $this->fields = array();
        $where_published = '' ;
        $publishing_details = '';

        // remove @
        $tmpcnt = preg_replace('/@/', '', $tmpcnt);

        // split entry into type, key, and rest
        list($type,$rest) = preg_split('/{/', $tmpcnt, 2);
        $this->type_specifier = strtolower($type);
        list($key_identifier, $rest) = explode(',', $rest, 2);

        // add <br> line breaks to bibtex entry for printing and set $contents
        $contents = "@".$this->type_specifier." {".$key_identifier.",<br>".$rest;
        $contents = str_replace(array("\"", "'"), array("&quot;", "\'"), $contents);
        $contents = preg_replace("/\},/","}, <br>", $contents);
        $this->content = $contents;

        // split entry to fields
        $fields = $this->get_fields($rest);

        // set fields
        foreach ($fields as $field) {
            $split = preg_split('/\s*=\s*/',$field, 2);
            if (count($split) >= 2) {
                $field_name = $split[0];
                $field_text = $split[1];
                $field_name = trim(strtolower($field_name));
                $field_text = trim($field_text);
                $this->set_field($field_name, $this->parse_convert($field_text));
            }
        }

        $this->parse($parser);
    }

    function get_where_published()
    {
        return $this->where_published;
    }

    function get_publishing_details()
    {
        return $this->publishing_details;
    }

    function get_field($field_name)
    {
        if (array_key_exists($field_name, $this->fields))
            return $this->fields[$field_name];
        else
            return '';
    }

    function set_field($field_name,$value)
    {
        $this->fields[$field_name] = $value;
    }

    function get_content()
    {
        return $this->content;
    }

    // parse author names and put them back together as first last, first, last,...,and first last
    function parse_author()
    {
        $author_text=$this->get_field('author');

        if ($author_text != ''){

            // split authors by 'and'
            $authors = preg_split('/\s+and\s+/',$author_text,-1, PREG_SPLIT_NO_EMPTY);
            $parsed_authors = array();

            foreach ($authors as $author){
                // if name not in last, first format (in first last format)
                if (!preg_match('/,/',$author_text)){
                    $parsed_authors[] = $author;
                } else {
                    // change into first last format
                    $names=preg_split('/\s*,\s*/',$author, -1, PREG_SPLIT_NO_EMPTY);
                    $author=$names[1].' '.$names[0];
                    $parsed_authors[] = $author;
                }
            }

            $last_author = array_pop($parsed_authors);
            $author_field = '';
            if (count($parsed_authors)) {
                $author_field = implode(', ',$parsed_authors);
                $author_field .= " and $last_author";
            } else {
                $author_field = $last_author;
            }
            // remove extra brackets
            $author_field=$this->parse_brackets($author_field);
            $this->fields['author'] = $author_field;
        }

    }

    // uses $parser to parse math in title
    function parse_title($title, $parser)
    {
        //change '' to " because parser turns '' into <i>
        $title = preg_replace('/\'\'/','"',$title);
        $title = $this->parse_math($title);
        $title = $parser->recursiveTagParse($title);
        $title = $this->parse_brackets($title);
        return $title;
    }

    // remove forced capitalization brackets
    function parse_brackets($text)
    {
        $i = 0; // characters in $text counter
        $len = strlen($text);
        $count = 0; // bracket counter

        $out = '';
        while ($i < $len) {
            // current character
            $ch = $text[$i];
            $i++;
            if ($ch == '{') {
                // new match is found
                if ($count)
                    $out .= $ch;
                $count++;
            } elseif ($ch == '}') {
                $count--;
                if ($count)
                    $out .= $ch;
            } else {
                $out .= $ch;
            }
        }

        return $out;
    }

    function parse_math($text)
    {
        $i = 0; // characters in $text counter
        $len = strlen($text);
        $count = 0;

        $out = '';
        while ($i < $len) {
            // current character
            $ch = $text[$i];
            $i++;
            if ($ch == '$') {
                if ($count == 0) {
                    $out .= '<math>';
                    $count++;
                }
                elseif ($count == 1) {
                    $out .= '</math>';
                    $count--;
                }
            }
            else {
                $out .= $ch;
            }
        }

        return $out;
    }

    // latex to html symbol conversion
    function parse_convert($text)
    {
        global $convert; // foreign accents array

        foreach( $convert as $key => $value ) {
            $text = str_replace( $key, $value, $text );
        }
        return $text;
    }

    // fill in generic fields
    function parse($parser)
    {
        $wbibmastersthesis = "Master's Thesis";
        $wbibphdthesis = "Ph.D. Thesis";
        $wbibtechreport = "Technical Report";
        $wbibunpublished = "Unpublished";

        $this->parse_author();

        //parse math in title and booktitle using $parser
        $this->set_field('title', $this->parse_title($this->get_field('title'), $parser));
        $this->set_field('booktitle', $this->parse_title($this->get_field('booktitle'), $parser));

        switch (trim($this->type_specifier)) {
        case 'inproceedings':
            $this->where_published = $this->get_field('booktitle');
            $this->publishing_details = $this->get_field('volume');

            if ('' != $this->publishing_details) {
                if ('' != $this->get_field('pages')) {
                    $this->publishing_details .=':'.$this->fields['pages'];
                }
            } elseif ('' != $this->get_field('pages')) {
                $this->publishing_details = 'pp. '.$this->fields['pages'];
            }
            break;

        case 'incollection':
            $this->where_published = $this->get_field('booktitle');
            $this->publishing_details = $this->get_field('volume');

            if ('' != $this->publishing_details) {
                if ('' != $this->get_field('pages')) {
                    $this->publishing_details .= ':'.$this->fields['pages'];
                }
            } elseif ('' != $this->get_field('pages')) {
                $this->publishing_details = 'pp. '.$this->fields['pages'];
            }
            break;

        case 'article':
            $journal = $this->get_field('journal');

            $this->where_published=$journal;
            $this->publishing_details=$this->get_field('volume');

            if ('' != $this->get_field('number')) {
                $this->publishing_details.= '('.$this->fields['number'].')';
            }
            if ('' != $this->publishing_details) {
                if ('' != $this->get_field('pages')) {
                    $this->publishing_details.= ':'.$this->fields['pages'];
                }
            } elseif ('' != $this->get_field('pages')) {
                $this->publishing_details = 'pp. '.$this->fields['pages'];
            }
            break;

        case 'techreport':
            $this->where_published=$wbibtechreport;

            if ( '' != $this->get_field('type'))
                $this->where_published .= " - ".$this->fields['type'];
            $this->where_published .= ", ".$this->get_field('institution');
            if ('' != $this->get_field('number')) {
                $this->publishing_details ='('.$this->fields['number'].')';
            }
            break;

        case 'mastersthesis':
            if ( '' != $this->get_field('type'))
                $this->where_published = $this->fields['type'];
            else
                $this->where_published=$wbibmastersthesis;
            $this->where_published .= ", ".$this->get_field('school');
            break;

        case 'phdthesis':
            $this->where_published=$wbibphdthesis.", ".$this->get_field('school');
            break;

        case 'unpublished':
            $this->where_published=$wbibunpublished;
            break;

        case 'book' :
            if ( '' != $this->get_field('volume') )
                $this->publishing_details .= "Vol. ".$this->fields['volume'].", ";
            if ( '' != $this->get_field('edition') )
                $this->publishing_details .= $this->fields['edition'].", ";
            $this->publishing_details .= $this->get_field('publisher');
            break;
        }

        if ('' != $this->get_field('address'))
            if ('' != $this->publishing_details )
                $this->publishing_details .= ', ';
            $this->publishing_details .= $this->get_field('address');

        if ('' != $this->get_field('month'))
            if ('' != $this->publishing_details )
                $this->publishing_details .= ', ';
            $this->publishing_details .= $this->get_field('month');

        if ('' != $this->get_field('year'))
            if ('' != $this->publishing_details )
                $this->publishing_details .= ', ';
            $this->publishing_details .= $this->get_field('year');
    }


    // add error catching?
    function get_fields($text)
    {
        $fields = array();
        $i = 0;
        $len = strlen($text);

        while ($i < $len) {
            $field='';

            // look for field name and text separator '='
            while ($i < $len) {
                $ch = substr($text,$i,1);
                $i++;
                $field .= $ch;
                if ('=' == $ch) {
                    break;
                }
            }

            // skip whitespace
            while ($i < $len) {
                $ch = substr($text,$i,1);
                $i++;
                if (' ' != $ch && "\t" != $ch) {
                    break;
                }
            }

            switch ($ch) {
            case '"': // look for ending '"'
                while ($i < $len) {
                    $ch = substr($text,$i,1);
                    $i++;
                    if ('"' == $ch) {
                        break;
                    } else {
                        $field .= $ch;
                    }
                }
                break;

            case '{': // match up with '}'
                $brace_count = 1;
                while ($i < $len && $brace_count > 0) {
                    $ch = substr($text,$i,1);
                    switch ($ch) {
                    case '{':
                        $brace_count++;
                        $field .= '{';
                        break;
                    case '}':
                        $brace_count--;
                        if ($brace_count > 0) {
                            $field .= '}';
                        }
                        break;
                    default:
                        $field .= $ch;
                    }
                    $i++;
                }
                break;

            default: // numbers only or predefined string key
                $field.=$ch;
                while ($i < $len && ','!=($ch=substr($text,$i,1))) {
                    if ('}' == $ch) { // hack to fix last entry
                        break;
                    }
                    $field .= $ch;
                    $i++;
                }
            }
            if ('' != $field) {
                array_push($fields,$field);
            }
            $i++; // skip comma
        }
        return $fields;
    }

    function html($id, $prefix)
    {
        // gory html output
        global $wgScriptPath; //for path to js file

        $sep = "&nbsp;&nbsp;";
        $ref_body = '';

        if ( $this->get_field('author') != '')
            $ref_body .= $this->get_field('author').". ";
        else
            $ref_body .= $this->get_field('editor').". ";

        $ref_body .= '&ldquo;'.$this->get_field('title').',&rdquo;';
        if ('' != $this->get_where_published() ) {
            $ref_body .= ' <i>'.$this->get_where_published().'</i>';
        }
        if( '' != $this->get_publishing_details() ) {
            $ref_body .= ', ' . $this->get_publishing_details() . '.  ';
        }

        $output = '<script language="javascript" src="'.$wgScriptPath
                 .'/extensions/WikiBibtex/toggle.js"></script>';
        $output .= $ref_body;
        if ($this->get_field('note') != '') {
            $note = $this->get_field('note');
            $output .= '('.$note.') ';
        }

        // link for url
        if ($this->get_field('url') != '') {
            $url = $this->get_field('url');
            $output .= '<a href="'.$url.'" class="extiw">www</a>'. $sep;
        }

        // like for arxiv
        if ($this->get_field('eprint') != '') {
            $eprint = $this->get_field('eprint');
            $output .= '<a href="http://arxiv.org/abs/'.$eprint
                    .'" class="extiw">arXiv</a>'. $sep;
        }

        // link for doi
        if ($this->get_field('doi') != '') {
            $doi = $this->get_field('doi');
            $output .= '<a href="http://dx.doi.org/'.$doi
                    .'" class="extiw">DOI</a>'. $sep;
        }

        // link for MR
        if ($this->get_field('mrnumber') != '') {
            $mr = $this->get_field('mrnumber');
            $mr = preg_replace('/^[^\d]*([0-9]+)\s.*$/','$1',$mr);
            $output .= '<a href="http://www.ams.org/mathscinet-getitem?mr='.$mr
                    .'" class="extiw">MR</a>'. $sep;
        }

        // link for bibtex
        $output .= '<a class="bibtex" href="'."javascript:toggle('".$prefix.$id."')".'">bibtex</a>';
        $output .= "<div style='display:none; font-family:Monaco,Consolas,monospace; margin:15px;'"
                ."id='".$prefix. $id."'>".$this->get_content()."</div>";

        return $output;
    }


    // removes \n and \r from entry
    function cleanLi($text)
    {
        return trim(str_replace(array("\n", "\r"), " ", $text));
    }

}


// converts bibtex entry to biblio item
function renderBibtex($input, $id, $parser, $prefix)
{
    $b = new BibTex($input, $parser);
    return $b->html($id, $prefix);
}
?>
