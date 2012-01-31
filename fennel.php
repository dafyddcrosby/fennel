<?php
/*
Copyright (c) 2012, Dafydd Crosby
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met: 

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer. 
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution. 

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those
of the authors and should not be interpreted as representing official policies, 
either expressed or implied, of the Fennel project.
*/

$VERSION='0.1';

// Get this file's name
$FILE_NAME = basename($_SERVER["PHP_SELF"]);

// Get the file name sans extension, which we will
// use to check for the config file (and maybe the database)
$info = pathinfo($FILE_NAME);
$FILE_BASE = basename($FILE_NAME,'.'.$info['extension']);

// Check for a config file
$CONFIG_FILE = $FILE_BASE.'.config.php';
if (!file_exists($CONFIG_FILE)) {
    $config_file_contents = <<<EOF
<?php
\$CONFIG['db'] = '{$FILE_BASE}.db';
\$CONFIG['title'] = 'Fennel';
\$CONFIG['css'] = '{$FILE_NAME}?css';
?>
EOF;
    $fp = fopen($CONFIG_FILE, 'w');
    fwrite($fp, $config_file_contents);
    fclose($fp);
}
include_once($CONFIG_FILE);

// Output a CSS file
if (isset($_GET['css'])) {
    //TODO - Actually put in some really pretty CSS
    header('Content-type: text/css');
    $CSS = <<<CSS
img { border: 0px; }
#header {background-color: #7FFFD4;}
#footer {background: #7FFFD4;}
CSS;
    die($CSS);
}

// Check for a database
// Let's check and see if we can even run
$PHP_VERSION = floatval(phpversion());
if ($PHP_VERSION < 5) {
    die('You need PHP 5 or greater to run Fennel');
} else if ($PHP_VERSION < 5.3) {
    // Use sqlite for $DB
    try {
      //create or open the database
      $DB = new SQLiteDatabase($CONFIG['db'], 0666, $error);
    } catch(Exception $e) {
      die($error);
    }
} else {
    // Use sqlite3 for $DB
    class DB3 extends SQLite3
    {
        function __construct()
        {
            $this->open($CONFIG['db']);
        }
    }

    $DB = new DB3();
}

// Output a CSS file
if (isset($_GET['rss'])) {
    //TODO
    header('Content-Type: application/rss+xml; charset=ISO-8859-1');
    $RSS = '';
    die($RSS);
}

// See if 'articles' table exists
// [Before everyone complains, I do it this way since
// CREATE IF NOT EXISTS only came about in SQLite 3.3.0]
$sql = 'SELECT name FROM sqlite_master WHERE name=\'articles\'';
$res = $DB->query($sql);
$chk = $res->fetch();
if (!$chk['name']) {
    $sql = 'CREATE TABLE articles (title TEXT UNIQUE, article TEXT, last_modified TEXT)';
    $DB->query($sql);
    $sql = sprintf("INSERT INTO articles VALUES ('%s','%s',date('now'))", 
        'index',
        'This is your \'\'Fennel\'\' installation. [[Make a page]] and have fun!');
    $DB->query($sql);
}



// This is the sausage-factory part of the code
// It takes the article, and converts the markup to HTML
function format_article ($str, $file_name) {
    $lines = explode("\n", $str);
    $formatted_article = '';
    $bold = false;
    $underlined = false;
    $italicized = false;
    $in_link = false;
    $in_olist = false;
    $in_ulist = false;
    foreach ($lines as $line) {
        $is_headline = false;
        $is_olist = false;
        $is_ulist = false;
        $startofline = true;
        $depth = 0;
        $mod_line = '';
        // If line is blank, use <br>
        if (strlen(trim($line)) == 0) {
            $formatted_article .= '<br>';
            continue;
        }
        for ($i = 0; $i < strlen($line); $i++) {
            switch ($line[$i]) {
                case '!':
                    if ($startofline) {
                        $is_headline = true;
                        $depth++;
                    } else {
                        $mod_line .= $line[$i];
                    }
                    break;
                case '#':
                    // TODO
                    if ($startofline) {
                        $is_olist = true;
                    } else {
                        $mod_line .= $line[$i];
                    }
                    break;
                case '*':
                    // TODO
                    if ($startofline) {
                        $is_ulist = true;
                    } else {
                        $mod_line .= $line[$i];
                    }
                    break;
                case '{':
                    //TODO Fix inline
                    if ($line[$i+1] == '{' && $line[$i+2] == '{') {
                        $mod_line .= '<pre>';
                        $i += 3;
                    } else {
                        $mod_line .= $line[$i];
                    }
                case '}':
                    //TODO Fix inline
                    if ($line[$i+1] == '}' && $line[$i+2] == '}') {
                        $mod_line .= '</pre>';
                        $i += 3;
                    } else {
                        $mod_line .= $line[$i];
                    }
                case '_':
                    if ($line[$i+1] == '_') {
                        if (!$underlined) {
                            $mod_line .= '<u>';
                            $underlined = true;
                        } else {
                            $mod_line .= '</u>';
                            $underlined = false;
                        }
                        $i++;
                    } else {
                        $mod_line .= $line[$i];
                    }
                    break;
                case '/':
                    if ($line[$i+1] == '/') {
                        if (!$italicized) {
                            $mod_line .= '<i>';
                            $italicized = true;
                        } else {
                            $mod_line .= '</i>';
                            $italicized = false;
                        }
                        $i++;
                    } else {
                        $mod_line .= $line[$i];
                    }
                    break;
                case '\'':
                    if ($line[$i+1] == '\'') {
                        if (!$bold) {
                            $mod_line .= '<b>';
                            $bold = true;
                        } else {
                            $mod_line .= '</b>';
                            $bold = false;
                        }
                        $i++;
                    } else {
                        $mod_line .= $line[$i];
                    }
                    break;
                case '[':
                    if ($line[$i+1] == '[') {
                        $link = '';
                        for ($k = $i + 2; $k <= strlen($line); $k++) {
                            //TODO - Iterate through until you find the ]]
                            // and if you don't find it, output as normal
                            if ($line[$k] == ']' && $line[$k + 1] == ']') {
                                // Create the link
                                $url = $file_name.'?page='.urlencode(trim($link));
                                $mod_line .=  '<a href='.$url.'>'.$link.'</a>';
                                // Move $i to the right place
                                $i = $k + 1;
                                break;
                            } else {
                                $link .= $line[$k];
                            }
                        }
                    } else {
                        $mod_line .= $line[$i];
                    }
                    break;
                default:
                    $startofline = false;
                    $mod_line .= $line[$i];
                    break;
            }
        }

        if ($is_headline) {
            if ($depth > 6) {
                $depth = 6;
            }
            $mod_line = "<h{$depth}>{$mod_line}</h{$depth}>";
        }
        // Not a naturally breaking line, add a line break
        if (!$is_headline) {
            $mod_line .= '<br>';
        }
        $formatted_article .= $mod_line."\n";
    }
    // Close any existing formatting things
    if ($underlined) $formatted_article .= '</u>';
    if ($italicized) $formatted_article .= '</i>';
    if ($bold) $formatted_article .= '</b>';
    return $formatted_article;
}

// See if we're getting an existing page
if ($PAGE_REQUEST = $_REQUEST['page']) {
    $display_page = false;
    if ($_POST['save']) {
        if ($contents = $_POST['article']) {
            $sql = sprintf("INSERT OR REPLACE INTO articles VALUES ('%s','%s',date('now'))", 
                sqlite_escape_string($PAGE_REQUEST),
                sqlite_escape_string($contents));
            $DB->query($sql);
            if ($original_title = $_POST['original_title']) {
                if ($PAGE_REQUEST != $original_title) {
                    $sql = sprintf("DELETE FROM articles WHERE title = '%s'",
                        sqlite_escape_string($original_title));
                    $DB->query($sql);
                }
            }
        }
    }
    $sql = sprintf('SELECT * FROM articles WHERE title = \'%s\'', 
        sqlite_escape_string($PAGE_REQUEST));
    $result = $DB->query($sql);
    $ARTICLE = $result->fetch();
    if ($ARTICLE['title']) {
        // See if we're editing this page
        if (isset($_GET['action'])) {
            switch($_GET['action']) {
                case 'edit':
                    // Edit the page
                    break;
                case 'confirmdelete':
                    // TODO
                    $PAGE_STUFF = 'really delete?';
                    break;
                case 'delete':
                    // TODO
                    $PAGE_STUFF = 'delete!';
                    break;
                default:
                    $display_page = true;
                    break;
            }
        } else {
            // No action, so display the page
            $display_page = true;
        }
    }
    if ($display_page) {
        // Display the page
        $FMT_ARTICLE = format_article($ARTICLE['article'], $FILE_NAME);
        $PAGE_STUFF = <<<DISPLAY
<div id="article_head">
<h2>{$ARTICLE['title']}</h2>
<h5>Last modified: {$ARTICLE['last_modified']}</h5>
</div>
$FMT_ARTICLE
<br>
<br>
<a href="$FILE_NAME?page=$PAGE_REQUEST&action=edit">Edit this page</a><br>
<a href="$FILE_NAME?page=$PAGE_REQUEST">Page link</a><br>
DISPLAY;
    } else {
        // Edit the page
        if (!isset($ARTICLE['title'])) {
            $ARTICLE['title'] = $PAGE_REQUEST;
            $ARTICLE['article'] = '';
        }
        $PAGE_STUFF = <<<EDIT
<form name="input" action="{$FILE_NAME}" method="POST">
Title:<br>
<input type="text" name="page" value="{$ARTICLE['title']}"/><br>
Article:<br>
<textarea rows="30" name="article" cols="80">{$ARTICLE['article']}</textarea><br>
<input type="hidden" name="original_title" value="{$PAGE_REQUEST}">
<input type="hidden" name="save" value="true">
<input type="submit" value="Submit" />
</form> 
EDIT;
    }
} else if (isset($_GET['special'])) {
    // Special Pages
    switch ($_GET['special']) {
        case 'recent_changes':
            $PAGE_STUFF = '<h2>Recent Changes</h2><ul>';
            $sql = 'SELECT * FROM articles ORDER BY last_modified DESC';
            $q = $DB->query($sql);
            $result = $q->fetchAll();
            foreach($result as $tlink) {
                $url = $FILE_NAME.'?page='.$tlink['title'];
                $PAGE_STUFF .= "<li><a href=\"{$url}\">{$tlink['title']}</a> - {$tlink['last_modified']}</li>";
            }
            $PAGE_STUFF .= '</ul>';
            break;
        case 'about':
            $PAGE_STUFF = <<<ABOUT
Fennel, written by Dafydd Crosby, 2012<br>
Set up your own wiki quickly by visiting the Fennel website
ABOUT;
            break;
        case 'all_pages':
            $PAGE_STUFF = '<h2>All Pages</h2><ul>';
            $sql = "SELECT title FROM articles ORDER BY title ASC";
            $q = $DB->query($sql);
            $result = $q->fetchAll();
            foreach($result as $tlink) {
                $url = $FILE_NAME.'?page='.$tlink['title'];
                $PAGE_STUFF .= "<li><a href=\"{$url}\">{$tlink['title']}</a></li>";
            }
            $PAGE_STUFF .= '</ul>';
            break;
        case 'menu':
            $PAGE_STUFF = <<<MENU
<ul>
<li><a href="{$FILE_NAME}?special=all_pages">All Pages</a></li>
<li><a href="{$FILE_NAME}?special=recent_changes">Recent Changes</a></li>
<li><a href="{$FILE_NAME}?special=about">About</a></li>
</ul>
MENU;
    }
} else {
    // Home page
    // Display the page
    $sql = sprintf('SELECT * FROM articles WHERE title = \'index\'', 
        sqlite_escape_string($PAGE_REQUEST));
    $result = $DB->query($sql);
    $ARTICLE = $result->fetch();
    $PAGE_STUFF = format_article($ARTICLE['article'], $FILE_NAME);
}

echo <<<TEMPLATE
<html>
<head>
<title>{$CONFIG['title']}</title>
<link rel="stylesheet" type="text/css" href="{$CONFIG['css']}" />
</head>
<div id="header">
<h1>{$CONFIG['title']}</h1>
</div>
{$PAGE_STUFF}
<br>
<div id="footer">
Fennel<br>
<a href="{$FILE_NAME}?special=menu">Menu</a><br>
Version {$VERSION}
</div>
</body>
</html>
TEMPLATE;

// Close the database handle
unset($DB);
?>
