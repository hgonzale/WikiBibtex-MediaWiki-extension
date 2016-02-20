## Authors

The WikiBibtex extension was developed by Victoria Gitman.  It is a
modified combination of two pre-existing extensions: Biblio, written
by Martin Jambon and others and Bibtex, written by Jean-Yves Didier
and Kinh Tieu. Most of the code comes from these two original
extensions. The chief modifications to the Biblio class consist of
removing non-bibtex processing code, updating it to comply with new
MediaWiki standards, and adding error catching mechanisms (e.g., when
there is no entry for a cited key).  Minor modifications to the Bibtex
class consist of improving author name parsing, adding the ability to
parse math in titles, adding the ability to process brackets in title
and author fields.  A major modification is that the bibtex code no
longer appears in a pop-up but below the bibliographical item.


## Setup instructions

Brief instructions are given here. For more info, see
http://boolesrings.org/victoriagitman/2014/06/06/a-wikibibtex-extension-for-mediawiki

To activate the extension, you have to:

1) Place files in the `extensions/WikiBibtex` subdirectory of your
MediaWiki installation.

2) Update your LocalSettings.php file with this line:
```
require_once( "$IP/extensions/WikiBibtex/WikiBibtex.php" );
```


## Features

This module provides tags `<cite>` and `<biblio>`.  `<cite>` tags indicate a
citation in the text, one or several keys, separated by a comma, are
given.  These keys must be defined in the <biblio> section.  The
`<biblio>` sections on the page and must come after the last citation.
The `<biblio>` tag can contain either the bibtex entry itself or a link
to an interwiki 'database' page containing bibtex entries.  A bibtex
'database' page is trying to simulate a bibtex file, and it is linked
using the syntax `@[[database]]` within the `<biblio>` tag.  It must
contain only bibtex entries (and HTML comments).


## Options

The tag `<biblio>` accepts the following options:
- **force** (default: *false*): The default is to display only cited
  items. Set this option to *true* to display all the items in the
  database.
- **prefix** (default: *empty*): Adds a prefix to the index of each
  entry. Useful when more than `<biblio>` tag is present in a single
  wiki page.

The tag `<cite>` accepts the following option:
- **prefix** (default: *empty*): Adds a prefix to the index of each
  entry. Useful when more than `<biblio>` tag is present in a single
  wiki page. The prefix in each cite must match the prefix in the
  corresponding `<biblio>` tag.

## Example

```
... wiki text ...
The result was published in <cite>Gitman:article2014</cite>, and
similar results appeared in <cite>Hamkins:article2013</cite>
... more wiki text ...

<biblio force=true>
@article{Gitman:article2014,
   ...
   }

@[[Bibtex]]
</biblio>
```
