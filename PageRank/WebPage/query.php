<?php
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
$map_file = fopen('NYD Map.csv', 'r');
$url_set = array();
while (($line = fgetcsv($map_file)) !== FALSE) {
    $url_set[$line[0]] = $line[1];
}
if ($query)
{
    // The Apache Solr Client library should be on the include path
    // which is usually most easily accomplished by placing in the
    // same directory as this script ( . or current directory is a default
    // php include path entry in the php.ini)
    require_once('Apache/Solr/Service.php');
    // create a new solr service instance - host, port, and corename
    // path (all defaults in this example)
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');
    // if magic quotes is enabled then stripslashes will be needed
    if (get_magic_quotes_gpc() == 1)
    {
        $query = stripslashes($query);
    }
    // in production code you'll always want to use a try /catch for any
    // possible exceptions emitted by searching (i.e. connection
    // problems or a query parsing error)
    try
    {
        $results = $solr->search($query, 0, $limit);
    }
    catch (Exception $e)
    {
        // in production you'd probably log or email this error to an admin
        // and then show a special message to the user but for this example
        // we're going to show the full exception
        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
    }
}
?>
<html>
<head>
    <title>PHP Solr Client Example</title>
    <style>
        a:link {
            color: mediumblue;
            background-color: transparent;
            text-decoration: none;
        }

        a:visited {
            color: purple;
            background-color: transparent;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<form accept-charset="utf-8" method="get">
    <label for="q">Search:</label>
    <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
    <input type="submit">
</form>
<table>
<?php
// display results
if ($results)
{
    $total = (int) $results->response->numFound;
    $start = min(1, $total);
    $end = min($limit, $total);
    ?>
    <table>
        <td width="50%" valign="top">
            <h2>Solr Lucene Results</h2>
            <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
            <ol>
                <?php
                // iterate result documents
                foreach ($results->response->docs as $doc)
                {
                    ?>
                    <li>
                        <div>
                            <?php
                            // iterate document fields / values
                            $title = "";
                            $id = "";
                            $url = "";
                            $description = "";
                            foreach ($doc as $field => $value)
                            {
                                if($field == "title"){
                                    $title = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
                                }
                                if($field == "id"){
                                    $id = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
                                }
                                if($field == "description"){
                                    $description = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
                                }
                                if($id != ""){
                                    $url = $url_set[str_replace("/Users/zijianli/Documents/solr-7.1.0/NYD/", "", $id)];
                                }
                            }

                            echo "<a href = '{$url}'><h2>".$title."</h2></a>";
                            echo "Link: <a href = '{$url}'>".$url."</a></br></br>";
                            echo "Id: ".$id. "</br></br>";
                            echo "Description: ".$description."</br></br>";
                            ?>
                        </div>
                    </li>
                    <?php
                }
                ?>
            </ol>
        </td>
        <td width="50%" valign="top">
            <h2>Page Rank Results</h2>
        </td>
    </table>
    <?php
}
?>
</body>
</html>